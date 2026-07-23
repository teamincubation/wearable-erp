<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Database;
use App\Models\Style;
use App\Models\TechPack;
use App\Models\AuditLog;
use App\Models\StyleVariable;

/**
 * Style Master Operations Controller
 * Full Stack Developer - Antigravity
 */
class StyleMasterController extends Controller {
    /**
     * Styles List View
     */
    public function index(Request $request, Response $response): void {
        $styleModel = new Style();
        $styles = $styleModel->all();

        $db = Database::getInstance();
        $companyId = Session::get('company_id');
        $stmt = $db->prepare("SELECT id, name, code, brand_name FROM contacts WHERE company_id = ? AND type = 'buyer' AND status = 'active' AND deleted_at IS NULL ORDER BY name ASC");
        $stmt->execute([$companyId]);
        $buyers = $stmt->fetchAll() ?: [];

        $styleVars = [];
        try {
            $styleVarModel = new StyleVariable();
            $styleVars = $styleVarModel->all() ?: [];
        } catch (\Throwable $e) {
            \App\Core\Migrator::runAutoMigration();
            try {
                $styleVarModel = new StyleVariable();
                $styleVars = $styleVarModel->all() ?: [];
            } catch (\Throwable $ex) {
                $styleVars = [];
            }
        }

        $styleVariables = [
            'category' => [],
            'gsm' => [],
            'color' => [],
            'brand' => [],
            'size_range' => []
        ];
        foreach ($styleVars as $sv) {
            if (isset($styleVariables[$sv['type']])) {
                $styleVariables[$sv['type']][] = $sv['value'];
            }
        }

        $this->renderView('company/styles', [
            'title' => 'Style Master | ERP',
            'styles' => $styles,
            'buyers' => $buyers,
            'styleVariables' => $styleVariables
        ]);
    }

    /**
     * Create new style master record
     */
    public function create(Request $request, Response $response): void {
        $styleNo = trim($request->get('style_no'));
        $name = trim($request->get('name'));
        $description = trim($request->get('description'));
        $category = $request->get('category');
        $composition = trim($request->get('composition'));
        $gsm = trim($request->get('gsm'));
        $color = trim($request->get('color'));
        $brand = trim($request->get('brand'));
        $sizeRange = trim($request->get('size_range'));

        if (empty($styleNo) || empty($name)) {
            Session::setFlash('error', 'Style Number and Name are required fields.');
            $this->redirect('company/styles');
        }

        $styleModel = new Style();
        
        // Check duplicate style_no
        $companyId = Session::get('company_id');
        $existing = $styleModel->findOneBy([
            'company_id' => $companyId,
            'style_no' => $styleNo
        ]);

        if ($existing) {
            Session::setFlash('error', "Style number '{$styleNo}' already exists.");
            $this->redirect('company/styles');
        }

        // Insert style
        $styleId = $styleModel->insert([
            'style_no' => $styleNo,
            'name' => $name,
            'description' => $description,
            'category' => $category ?: 'unisex',
            'composition' => $composition,
            'gsm' => $gsm,
            'color' => $color,
            'brand' => $brand,
            'size_range' => $sizeRange,
            'created_by' => Session::get('user_id')
        ]);

        // Provision default empty tech pack
        $techPackModel = new TechPack();
        $techPackModel->insert([
            'style_id' => $styleId,
            'bom_json' => json_encode([]),
            'sizing_json' => json_encode([]),
            'printing_specs' => '',
            'embroidery_specs' => '',
            'packing_specs' => '',
            'created_by' => Session::get('user_id')
        ]);

        AuditLog::log($companyId, Session::get('user_id'), 'create_style', 'Style', $styleId, null, null, "Created style {$styleNo}: {$name}");
        Session::setFlash('success', 'Style Master created and initialized successfully.');
        $this->redirect('company/styles');
    }

    /**
     * Edit existing style master details
     */
    public function edit(Request $request, Response $response, string $id): void {
        $styleModel = new Style();
        $style = $styleModel->find($id);

        if (!$style) {
            Session::setFlash('error', 'Style not found.');
            $this->redirect('company/styles');
        }

        $name = trim($request->get('name'));
        $description = trim($request->get('description'));
        $category = $request->get('category');
        $composition = trim($request->get('composition'));
        $gsm = trim($request->get('gsm'));
        $color = trim($request->get('color'));
        $brand = trim($request->get('brand'));
        $sizeRange = trim($request->get('size_range'));

        if (empty($name)) {
            Session::setFlash('error', 'Style Name is required.');
            $this->redirect('company/styles');
        }

        $styleModel->update($id, [
            'name' => $name,
            'description' => $description,
            'category' => $category,
            'composition' => $composition,
            'gsm' => $gsm,
            'color' => $color,
            'brand' => $brand,
            'size_range' => $sizeRange,
            'updated_by' => Session::get('user_id')
        ]);

        AuditLog::log(Session::get('company_id'), Session::get('user_id'), 'edit_style', 'Style', (int)$id, null, null, "Updated style details for {$style['style_no']}");
        Session::setFlash('success', 'Style Master updated successfully.');
        $this->redirect('company/styles');
    }

    /**
     * View Tech Pack / BOM configurations
     */
    public function techpack(Request $request, Response $response, string $id): void {
        $styleModel = new Style();
        $style = $styleModel->find($id);

        if (!$style) {
            Session::setFlash('error', 'Style not found.');
            $this->redirect('company/styles');
        }

        $techPackModel = new TechPack();
        $techpack = $techPackModel->findOneBy(['style_id' => $id]);

        // If techpack does not exist, provision one now
        if (!$techpack) {
            $techPackId = $techPackModel->insert([
                'style_id' => $id,
                'bom_json' => json_encode([]),
                'sizing_json' => json_encode([]),
                'printing_specs' => '',
                'embroidery_specs' => '',
                'packing_specs' => '',
                'created_by' => Session::get('user_id')
            ]);
            $techpack = $techPackModel->find($techPackId);
        }

        // Decode JSON configurations for the view
        $bomList = json_decode($techpack['bom_json'] ?? '[]', true) ?: [];
        $sizeGuide = json_decode($techpack['sizing_json'] ?? '[]', true) ?: [];

        // Fetch current stock inventory items for cascading dropdown selection in BOM editor
        $inventoryService = new \App\Services\InventoryService();
        $stockSummary = $inventoryService->getInventorySummary(Session::get('company_id'));

        $this->renderView('company/techpack', [
            'title' => "Tech Pack: {$style['style_no']} | ERP",
            'style' => $style,
            'techpack' => $techpack,
            'bom_list' => $bomList,
            'size_guide' => $sizeGuide,
            'stock_summary' => $stockSummary
        ]);
    }

    /**
     * Update Tech Pack BOM & Sizing details
     */
    public function techpackUpdate(Request $request, Response $response, string $id): void {
        $techPackModel = new TechPack();
        $techpack = $techPackModel->find($id);

        if (!$techpack) {
            Session::setFlash('error', 'Tech Pack specification sheet not found.');
            $this->redirect('company/styles');
        }

        $bomNames = $request->get('bom_item_name') ?: [];
        $bomTypes = $request->get('bom_item_type') ?: [];
        $bomColors = $request->get('bom_color') ?: [];
        $bomUoms = $request->get('bom_uom') ?: [];
        $bomQtys = $request->get('bom_qty') ?: [];

        $bom = [];
        for ($i = 0; $i < count($bomNames); $i++) {
            if (!empty($bomNames[$i])) {
                $bom[] = [
                    'item_name' => trim($bomNames[$i]),
                    'item_type' => trim($bomTypes[$i] ?? 'fabric'),
                    'color' => trim($bomColors[$i] ?? ''),
                    'uom' => trim($bomUoms[$i] ?? 'pcs'),
                    'qty' => (float)($bomQtys[$i] ?? 0.00)
                ];
            }
        }

        $sizeParams = $request->get('size_parameter') ?: [];
        $sizesInput = $_POST['sizes'] ?? [];

        $sizing = [];
        for ($i = 0; $i < count($sizeParams); $i++) {
            if (!empty($sizeParams[$i])) {
                $item = [
                    'parameter' => trim($sizeParams[$i])
                ];
                foreach ($sizesInput as $sizeName => $values) {
                    $item[strtolower(trim($sizeName))] = trim($values[$i] ?? '');
                }
                $sizing[] = $item;
            }
        }

        $printingSpecs = trim($request->get('printing_specs'));
        $embroiderySpecs = trim($request->get('embroidery_specs'));
        $packingSpecs = trim($request->get('packing_specs'));

        $techPackModel->update($id, [
            'bom_json' => json_encode($bom),
            'sizing_json' => json_encode($sizing),
            'printing_specs' => $printingSpecs,
            'embroidery_specs' => $embroiderySpecs,
            'packing_specs' => $packingSpecs,
            'updated_by' => Session::get('user_id')
        ]);

        AuditLog::log(Session::get('company_id'), Session::get('user_id'), 'edit_techpack', 'TechPack', (int)$id, null, null, "Updated techpack specification sheet");
        Session::setFlash('success', 'Tech Pack specifications updated successfully.');
        $this->redirect("company/styles/techpack/{$techpack['style_id']}");
    }

    public function deleteStyle(Request $request, Response $response, string $id): void {
        $styleModel = new Style();
        $styleModel->delete($id, Session::get('user_id'));
        Session::setFlash('success', 'Style master deleted successfully.');
        $this->redirect('company/styles');
    }
}
