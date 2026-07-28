<?php
/**
 * Application Routes Definition
 * Lead Software Architect - Antigravity
 */

use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\ApiAuthController;
use App\Controllers\DashboardController;
use App\Controllers\DeveloperController;
use App\Controllers\CompanyController;

use App\Middleware\AuthMiddleware;
use App\Middleware\PermissionMiddleware;
use App\Middleware\CsrfMiddleware;

/** @var Router $router */

// ==========================================================
// 1. PUBLIC & AUTHENTICATION ROUTES
// ==========================================================
$router->get('/', [DashboardController::class, 'landing']);

// Single Universal SaaS Portal Login Routes (Web HTML)
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login'])->middleware(CsrfMiddleware::class);
$router->get('/developer/login', [AuthController::class, 'showLogin']);
$router->get('/logout', [AuthController::class, 'logout']);

// Mobile & External REST API Routes (Returns pure JSON - No 302 Redirects)
$router->post('/api/login', [ApiAuthController::class, 'login']);
$router->post('/api/v1/login', [ApiAuthController::class, 'login']);

$router->get('/forgot-password', [AuthController::class, 'showForgotPassword']);
$router->post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware(CsrfMiddleware::class);
$router->get('/reset-password', [AuthController::class, 'showResetPassword']);
$router->post('/reset-password', [AuthController::class, 'resetPassword'])->middleware(CsrfMiddleware::class);

$router->get('/verify-email', [AuthController::class, 'verifyEmail']);

// AJAX Identifier Uniqueness Validation API
$router->get('/api/check-identifier-uniqueness', [AuthController::class, 'checkIdentifierUniqueness']);
$router->post('/api/check-identifier-uniqueness', [AuthController::class, 'checkIdentifierUniqueness']);

// ==========================================================
// 2. DEVELOPER PORTAL ROUTES (Global Super Admin Only)
// ==========================================================
$router->get('/developer/dashboard', [DeveloperController::class, 'dashboard'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('developer.dashboard');

$router->get('/developer/companies', [DeveloperController::class, 'companies'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('developer.companies');

$router->post('/developer/companies/create', [DeveloperController::class, 'createCompany'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('developer.companies');

$router->post('/developer/companies/edit/{id}', [DeveloperController::class, 'editCompany'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('developer.companies');

$router->post('/developer/companies/delete/{id}', [DeveloperController::class, 'deleteCompany'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('developer.companies');

$router->get('/developer/subscriptions', [DeveloperController::class, 'subscriptions'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('developer.subscriptions');

$router->post('/developer/subscriptions/create', [DeveloperController::class, 'createSubscriptionPlan'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('developer.subscriptions');

$router->post('/developer/subscriptions/edit/{id}', [DeveloperController::class, 'editSubscriptionPlan'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('developer.subscriptions');

$router->get('/developer/versions', [DeveloperController::class, 'versions'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('developer.versions');

$router->post('/developer/versions/create', [DeveloperController::class, 'createVersion'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('developer.versions');

$router->get('/developer/logs', [DeveloperController::class, 'logs'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('developer.logs');

$router->get('/developer/settings', [DeveloperController::class, 'settings'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('developer.settings');

$router->post('/developer/settings', [DeveloperController::class, 'saveSettings'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('developer.settings');

$router->post('/developer/settings/generate-credentials', [DeveloperController::class, 'generateMissingDevCredentials'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('developer.settings');

$router->get('/developer/marketplace', [DeveloperController::class, 'marketplace'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('developer.settings');

$router->get('/developer/db-monitor', [DeveloperController::class, 'dbMonitor'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('developer.settings');

$router->get('/developer/cron-jobs', [DeveloperController::class, 'cronJobs'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('developer.settings');

// ==========================================================
// 3. COMPANY ERP PORTAL ROUTES (Tenant Admin & Employees)
// ==========================================================
$router->get('/company/dashboard', [DashboardController::class, 'index'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.dashboard');

// User Management
$router->get('/company/users', [CompanyController::class, 'users'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.users.view');

$router->post('/company/users/create', [CompanyController::class, 'createUser'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.users.create');

$router->post('/company/users/edit/{id}', [CompanyController::class, 'editUser'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.users.edit');

$router->post('/company/users/delete/{id}', [CompanyController::class, 'deleteUser'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.users.delete');

// Role Management
$router->get('/company/roles', [CompanyController::class, 'roles'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.roles.view');

$router->post('/company/roles/create', [CompanyController::class, 'createRole'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.roles.manage');

$router->post('/company/roles/edit/{id}', [CompanyController::class, 'editRole'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.roles.manage');

$router->post('/company/roles/delete/{id}', [CompanyController::class, 'deleteRole'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.roles.manage');

$router->post('/company/roles/bulk-delete', [CompanyController::class, 'bulkDeleteRoles'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.roles.manage');

// Audit Trails
$router->get('/company/logs', [CompanyController::class, 'logs'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.logs');

// Style Master
$router->get('/company/styles', [\App\Controllers\StyleMasterController::class, 'index'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.styles.view');

$router->post('/company/styles/create', [\App\Controllers\StyleMasterController::class, 'create'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.styles.manage');

$router->post('/company/styles/edit/{id}', [\App\Controllers\StyleMasterController::class, 'edit'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.styles.manage');

$router->get('/company/styles/techpack/{id}', [\App\Controllers\StyleMasterController::class, 'techpack'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.styles.view');

$router->post('/company/styles/techpack/{id}', [\App\Controllers\StyleMasterController::class, 'techpackUpdate'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.styles.manage');

// Master Data Hub (Contacts, BOM Categories, Warehouses, Branches)
$router->get('/company/masterdata', [\App\Controllers\MasterDataController::class, 'index'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.styles.view');

$router->get('/company/masterdata/export-csv', [\App\Controllers\MasterDataController::class, 'exportCsv'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.styles.view');

$router->post('/company/masterdata/import-csv', [\App\Controllers\MasterDataController::class, 'importCsv'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.styles.manage');

$router->post('/company/masterdata/contacts/create', [\App\Controllers\MasterDataController::class, 'createContact'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.styles.manage');

$router->post('/company/masterdata/bomcategories/create', [\App\Controllers\MasterDataController::class, 'createBomCategory'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.styles.manage');

$router->post('/company/masterdata/bomcategories/edit/{id}', [\App\Controllers\MasterDataController::class, 'editBomCategory'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.styles.manage');

$router->post('/company/masterdata/stylevariables/create', [\App\Controllers\MasterDataController::class, 'createStyleVariable'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.styles.manage');

$router->post('/company/masterdata/stylevariables/edit/{id}', [\App\Controllers\MasterDataController::class, 'editStyleVariable'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.styles.manage');

$router->post('/company/masterdata/warehouses/create', [\App\Controllers\MasterDataController::class, 'createWarehouse'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.styles.manage');

$router->post('/company/masterdata/branches/create', [\App\Controllers\MasterDataController::class, 'createBranch'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.styles.manage');

$router->post('/company/masterdata/generalhours', [\App\Controllers\MasterDataController::class, 'updateGeneralHours'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.styles.manage');

$router->post('/company/masterdata/shifts/create', [\App\Controllers\MasterDataController::class, 'createShift'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.styles.manage');

$router->post('/company/masterdata/shifts/edit/{id}', [\App\Controllers\MasterDataController::class, 'editShift'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.styles.manage');

$router->post('/company/masterdata/shifts/delete/{id}', [\App\Controllers\MasterDataController::class, 'deleteShift'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.styles.manage');

$router->post('/company/masterdata/hrpolicies', [\App\Controllers\MasterDataController::class, 'updateHrPolicies'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.styles.manage');

$router->post('/company/masterdata/holidays/create', [\App\Controllers\MasterDataController::class, 'createHoliday'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.styles.manage');

$router->post('/company/masterdata/holidays/delete/{id}', [\App\Controllers\MasterDataController::class, 'deleteHoliday'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.styles.manage');

$router->post('/company/masterdata/holidays/generate', [\App\Controllers\MasterDataController::class, 'generateWeekends'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.styles.manage');

$router->post('/company/masterdata/holidays/clone', [\App\Controllers\MasterDataController::class, 'cloneHolidays'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.styles.manage');

$router->post('/company/masterdata/designations/create', [\App\Controllers\MasterDataController::class, 'createDesignation'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.styles.manage');

$router->post('/company/masterdata/designations/edit/{id}', [\App\Controllers\MasterDataController::class, 'editDesignation'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.styles.manage');

$router->post('/company/masterdata/designations/delete/{id}', [\App\Controllers\MasterDataController::class, 'deleteDesignation'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.styles.manage');

// Buyer & Client Master Management
$router->get('/company/buyers', [\App\Controllers\BuyerController::class, 'index'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.styles.view');

$router->post('/company/buyers/create', [\App\Controllers\BuyerController::class, 'create'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.styles.manage');

$router->post('/company/buyers/edit/{id}', [\App\Controllers\BuyerController::class, 'edit'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.styles.manage');

$router->post('/company/buyers/status/{id}', [\App\Controllers\BuyerController::class, 'updateStatus'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.styles.manage');

$router->get('/company/buyers/sample-template', [\App\Controllers\BuyerController::class, 'downloadSampleTemplate'])
       ->middleware(AuthMiddleware::class);

$router->post('/company/buyers/import', [\App\Controllers\BuyerController::class, 'importExcel'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.styles.manage');

$router->post('/company/buyers/delete/{id}', [\App\Controllers\BuyerController::class, 'delete'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.styles.manage');

// Merchandising (Cost Sheets & Buyer POs)
$router->get('/company/merchandising/costsheets', [\App\Controllers\MerchandisingController::class, 'costsheets'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.styles.view');

$router->post('/company/merchandising/costsheets/create', [\App\Controllers\MerchandisingController::class, 'createCostsheet'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.styles.manage');

$router->post('/company/merchandising/costsheets/edit/{id}', [\App\Controllers\MerchandisingController::class, 'editCostsheet'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.styles.manage');

$router->get('/company/merchandising/buyerpos', [\App\Controllers\MerchandisingController::class, 'buyerpos'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.styles.view');

$router->get('/company/merchandising/completed-contracts', [\App\Controllers\MerchandisingController::class, 'completedContracts'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.styles.view');

$router->post('/company/merchandising/buyerpos/create', [\App\Controllers\MerchandisingController::class, 'createBuyerpo'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.styles.manage');

$router->post('/company/merchandising/buyerpos/approve/{id}', [\App\Controllers\MerchandisingController::class, 'approveBuyerpo'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.styles.manage');

// Procurement & Purchase Workflow
$router->get('/company/purchase/requisitions', [\App\Controllers\PurchaseController::class, 'requisitions'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.styles.view');

$router->post('/company/purchase/requisitions/create', [\App\Controllers\PurchaseController::class, 'createRequisition'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.styles.manage');

$router->get('/company/purchase/orders', [\App\Controllers\PurchaseController::class, 'orders'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.styles.view');

$router->post('/company/purchase/orders/create', [\App\Controllers\PurchaseController::class, 'createOrder'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.styles.manage');

$router->post('/company/purchase/orders/edit/{id}', [\App\Controllers\PurchaseController::class, 'editOrder'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.styles.manage');

$router->post('/company/purchase/orders/update-status/{id}', [\App\Controllers\PurchaseController::class, 'updateStatus'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.styles.manage');

$router->get('/company/purchase/grns', [\App\Controllers\PurchaseController::class, 'grns'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.styles.view');

$router->post('/company/purchase/grns/create', [\App\Controllers\PurchaseController::class, 'createGrn'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.styles.manage');

// Inventory Ledger & Stock Management
$router->get('/company/inventory/ledger', [\App\Controllers\InventoryController::class, 'ledger'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.inventory.view');

$router->get('/company/inventory/balances', [\App\Controllers\InventoryController::class, 'balances'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.inventory.view');

$router->post('/company/inventory/transfer', [\App\Controllers\InventoryController::class, 'transfer'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.inventory.manage');

$router->get('/company/inventory/barcode', [\App\Controllers\InventoryController::class, 'barcode'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.inventory.view');

// Production stage tracking & Quality audits
$router->get('/company/production/orders', [\App\Controllers\ProductionController::class, 'orders'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.production.view');

$router->get('/company/production/orders/check-batch-no', [\App\Controllers\ProductionController::class, 'checkBatchNo'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.production.view');

$router->post('/company/production/orders/create', [\App\Controllers\ProductionController::class, 'createOrder'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.production.manage');

$router->post('/company/production/start/{id}', [\App\Controllers\ProductionController::class, 'startOrder'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.production.manage');

$router->post('/company/production/complete/{id}', [\App\Controllers\ProductionController::class, 'completeOrder'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.production.manage');

$router->get('/company/production/completed', [\App\Controllers\ProductionController::class, 'completedProducts'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.production.view');

$router->get('/company/production/stage/{id}', [\App\Controllers\ProductionController::class, 'stage'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.production.view');

$router->get('/company/production/track-qr-unit', [\App\Controllers\ProductionController::class, 'trackQrUnit'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.production.view');

$router->post('/company/production/stage/{id}/log', [\App\Controllers\ProductionController::class, 'logStage'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.production.manage');

$router->post('/company/production/stage-log/edit/{id}', [\App\Controllers\ProductionController::class, 'editStageLog'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.production.manage');

$router->post('/company/production/stage-log/delete/{id}', [\App\Controllers\ProductionController::class, 'deleteStageLog'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.production.manage');

$router->post('/company/production/stage/{id}/clear-logs', [\App\Controllers\ProductionController::class, 'clearLogs'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.production.manage');

$router->get('/company/production/stage/{id}/export', [\App\Controllers\ProductionController::class, 'exportStageLogs'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.production.view');

$router->get('/company/production/stage/{id}/live-report', [\App\Controllers\ProductionController::class, 'stageLiveReport'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.production.view');

// Finished Goods Dispatch & Packing Hub
$router->get('/company/dispatch', [\App\Controllers\DispatchController::class, 'index'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.dispatch.view');

$router->post('/company/dispatch/cartons/create', [\App\Controllers\DispatchController::class, 'createCarton'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.dispatch.manage');

$router->post('/company/dispatch/cartons/{id}/reopen', [\App\Controllers\DispatchController::class, 'reopenCarton'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.dispatch.manage');

$router->post('/company/dispatch/cartons/{id}/status', [\App\Controllers\DispatchController::class, 'updateCartonStatus'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.dispatch.manage');

$router->get('/company/dispatch/cartons/print', [\App\Controllers\DispatchController::class, 'printCartonQr'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.dispatch.view');

$router->post('/company/dispatch/shipments/create', [\App\Controllers\DispatchController::class, 'createShipment'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.dispatch.manage');

$router->post('/company/dispatch/shipments/{id}/status', [\App\Controllers\DispatchController::class, 'updateShipmentStatus'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.dispatch.manage');

// ==================== PACKING QR MODULE ROUTES ====================
$router->get('/company/packing-qr', [\App\Controllers\PackingQrController::class, 'index'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.packing.qr');

$router->get('/company/packing-qr/assign/{id}', [\App\Controllers\PackingQrController::class, 'showAssign'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.packing.qr');

$router->get('/company/packing-qr/api/eligible-products', [\App\Controllers\PackingQrController::class, 'getEligibleProducts'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.packing.qr');

$router->post('/company/packing-qr/api/scan-product', [\App\Controllers\PackingQrController::class, 'scanProduct'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.packing.qr');

$router->post('/company/packing-qr/api/assign-bulk', [\App\Controllers\PackingQrController::class, 'assignBulkProducts'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.packing.qr');

$router->post('/company/packing-qr/api/remove-product', [\App\Controllers\PackingQrController::class, 'removeProductFromCarton'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.packing.qr');

$router->get('/company/packing-qr/traceability', [\App\Controllers\PackingQrController::class, 'showTraceability'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.packing.qr');

$router->get('/company/production/stage/{id}/live-api', [\App\Controllers\ProductionController::class, 'stageLiveApi'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.production.view');

$router->get('/company/production/stage/{id}/clear-logs', [\App\Controllers\ProductionController::class, 'clearStageLogs'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.production.manage');

$router->post('/company/production/stage/{id}/clear-logs', [\App\Controllers\ProductionController::class, 'clearStageLogs'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.production.manage');

$router->post('/company/production/quality/clear-all', [\App\Controllers\ProductionController::class, 'clearAllQualityInspections'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.production.manage');


$router->get('/company/production/barcode', [\App\Controllers\ProductionController::class, 'generateBatchBarcodes'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.production.view');

$router->get('/company/production/quality', [\App\Controllers\ProductionController::class, 'quality'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.production.view');

$router->post('/company/production/quality/create', [\App\Controllers\ProductionController::class, 'createInspection'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.production.manage');

// QR Code Mobile Tracking
$router->get('/company/production/qr-tracking', [\App\Controllers\ProductionController::class, 'qrTracking'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.production.rfid_tracking');

$router->post('/company/production/qr-tracking/log', [\App\Controllers\ProductionController::class, 'logQrActivity'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.production.rfid_tracking');

$router->post('/company/production/qr-tracking/verify', [\App\Controllers\ProductionController::class, 'verifyQrCode'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.production.rfid_tracking');

// HR & Payroll management
$router->get('/company/hr/attendance', [\App\Controllers\HrPayrollController::class, 'attendance'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.users.view');

$router->post('/company/hr/attendance/clock', [\App\Controllers\HrPayrollController::class, 'clock'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.users.create');

$router->get('/company/hr/loans', [\App\Controllers\HrPayrollController::class, 'loansPage'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.users.view');

$router->post('/company/hr/loans/create', [\App\Controllers\HrPayrollController::class, 'createLoan'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.users.create');

$router->post('/company/hr/loans/delete/{id}', [\App\Controllers\HrPayrollController::class, 'deleteLoan'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.users.create');

$router->get('/company/hr/payroll', [\App\Controllers\HrPayrollController::class, 'payroll'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.users.view');

$router->post('/company/hr/payroll/process', [\App\Controllers\HrPayrollController::class, 'processPayroll'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.users.create');

$router->post('/company/hr/payroll/pay/{id}', [\App\Controllers\HrPayrollController::class, 'payPayroll'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.users.create');

$router->get('/company/hr/payroll/calculate', [\App\Controllers\HrPayrollController::class, 'calculatePayrollStats'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.users.create');

// Tally Voucher Exports
$router->get('/company/tally/vouchers', [\App\Controllers\TallyController::class, 'vouchers'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.tally.export');

$router->post('/company/tally/vouchers/create', [\App\Controllers\TallyController::class, 'generateVoucher'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.tally.export');

$router->get('/company/tally/vouchers/download/{id}', [\App\Controllers\TallyController::class, 'downloadXml'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.tally.export');

$router->get('/company/tally/vouchers/csv', [\App\Controllers\TallyController::class, 'exportCsv'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.tally.export');

// Executive Sales & Reports Dashboard
$router->get('/company/sales-reports', [\App\Controllers\SalesReportsController::class, 'index'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.sales_reports');

$router->get('/company/sales-reports/api/carton-details/{id}', [\App\Controllers\SalesReportsController::class, 'getCartonDetails'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.sales_reports');

$router->get('/company/sales-reports/export-batch-financials', [\App\Controllers\SalesReportsController::class, 'exportBatchFinancials'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.sales_reports');

$router->get('/company/sales-reports/export-carton-analysis', [\App\Controllers\SalesReportsController::class, 'exportCartonAnalysis'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.sales_reports');

$router->get('/company/sales-reports/api/batch-payments/{id}', [\App\Controllers\SalesReportsController::class, 'getBatchPayments'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.sales_reports');

$router->post('/company/sales-reports/api/record-payment', [\App\Controllers\SalesReportsController::class, 'recordPayment'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.sales_reports');

// Settings
$router->get('/company/settings', [CompanyController::class, 'settings'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.settings');

$router->post('/company/settings', [CompanyController::class, 'saveSettings'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.settings');

$router->post('/company/settings/payment-accounts/create', [CompanyController::class, 'createPaymentAccount'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.settings');

$router->post('/company/settings/payment-accounts/edit/{id}', [CompanyController::class, 'editPaymentAccount'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.settings');

$router->post('/company/settings/payment-accounts/delete/{id}', [CompanyController::class, 'deletePaymentAccount'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.settings');

$router->post('/company/settings/menu-order', [CompanyController::class, 'saveMenuOrder'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.settings');

$router->post('/company/settings/wip-stages', [CompanyController::class, 'saveWipStages'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.settings');

$router->post('/company/settings/wip-stages/add', [CompanyController::class, 'addWipStage'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.settings');

$router->post('/company/settings/wip-stages/edit', [CompanyController::class, 'editWipStage'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.settings');

$router->post('/company/settings/wip-stages/delete/{key}', [CompanyController::class, 'deleteWipStage'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.settings');

$router->get('/company/production/batch-stages/{id}', [\App\Controllers\ProductionController::class, 'getBatchStages'])
       ->middleware(AuthMiddleware::class);

// Generic Item Deletion Endpoints for Super Admin & Authorized Roles
$router->post('/company/masterdata/contacts/delete/{id}', [\App\Controllers\MasterDataController::class, 'deleteContact'])
       ->middleware(AuthMiddleware::class)->middleware(CsrfMiddleware::class);

$router->post('/company/masterdata/bomcategories/delete/{id}', [\App\Controllers\MasterDataController::class, 'deleteBomCategory'])
       ->middleware(AuthMiddleware::class)->middleware(CsrfMiddleware::class);

$router->post('/company/masterdata/stylevariables/delete/{id}', [\App\Controllers\MasterDataController::class, 'deleteStyleVariable'])
       ->middleware(AuthMiddleware::class)->middleware(CsrfMiddleware::class);

$router->post('/company/masterdata/warehouses/delete/{id}', [\App\Controllers\MasterDataController::class, 'deleteWarehouse'])
       ->middleware(AuthMiddleware::class)->middleware(CsrfMiddleware::class);

$router->post('/company/masterdata/warehouses/edit/{id}', [\App\Controllers\MasterDataController::class, 'editWarehouse'])
       ->middleware(AuthMiddleware::class)->middleware(CsrfMiddleware::class);

$router->post('/company/masterdata/branches/delete/{id}', [\App\Controllers\MasterDataController::class, 'deleteBranch'])
       ->middleware(AuthMiddleware::class)->middleware(CsrfMiddleware::class);

$router->post('/company/masterdata/branches/edit/{id}', [\App\Controllers\MasterDataController::class, 'editBranch'])
       ->middleware(AuthMiddleware::class)->middleware(CsrfMiddleware::class);

$router->post('/company/masterdata/warehousetypes/create', [\App\Controllers\MasterDataController::class, 'createWarehouseType'])
       ->middleware(AuthMiddleware::class)->middleware(CsrfMiddleware::class);

$router->post('/company/masterdata/warehousetypes/edit/{id}', [\App\Controllers\MasterDataController::class, 'editWarehouseType'])
       ->middleware(AuthMiddleware::class)->middleware(CsrfMiddleware::class);

$router->post('/company/masterdata/warehousetypes/delete/{id}', [\App\Controllers\MasterDataController::class, 'deleteWarehouseType'])
       ->middleware(AuthMiddleware::class)->middleware(CsrfMiddleware::class);

$router->post('/company/styles/delete/{id}', [\App\Controllers\StyleMasterController::class, 'deleteStyle'])
       ->middleware(AuthMiddleware::class)->middleware(CsrfMiddleware::class);

$router->post('/company/merchandising/costsheets/delete/{id}', [\App\Controllers\MerchandisingController::class, 'deleteCostSheet'])
       ->middleware(AuthMiddleware::class)->middleware(CsrfMiddleware::class);

$router->post('/company/merchandising/buyerpos/edit/{id}', [\App\Controllers\MerchandisingController::class, 'editBuyerpo'])
       ->middleware(AuthMiddleware::class)->middleware(CsrfMiddleware::class);

$router->post('/company/merchandising/buyerpos/delete/{id}', [\App\Controllers\MerchandisingController::class, 'deleteBuyerPo'])
       ->middleware(AuthMiddleware::class)->middleware(CsrfMiddleware::class);

$router->post('/company/purchase/requisitions/delete/{id}', [\App\Controllers\PurchaseController::class, 'deleteRequisition'])
       ->middleware(AuthMiddleware::class)->middleware(CsrfMiddleware::class);

$router->post('/company/purchase/orders/delete/{id}', [\App\Controllers\PurchaseController::class, 'deleteOrder'])
       ->middleware(AuthMiddleware::class)->middleware(CsrfMiddleware::class);

$router->post('/company/purchase/grn/delete/{id}', [\App\Controllers\PurchaseController::class, 'deleteGrn'])
       ->middleware(AuthMiddleware::class)->middleware(CsrfMiddleware::class);

$router->post('/company/inventory/delete/{id}', [\App\Controllers\InventoryController::class, 'deleteTransaction'])
       ->middleware(AuthMiddleware::class)->middleware(CsrfMiddleware::class);

$router->post('/company/production/orders/delete/{id}', [\App\Controllers\ProductionController::class, 'deleteOrder'])
       ->middleware(AuthMiddleware::class)->middleware(CsrfMiddleware::class);

$router->post('/company/production/quality/delete/{id}', [\App\Controllers\ProductionController::class, 'deleteInspection'])
       ->middleware(AuthMiddleware::class)->middleware(CsrfMiddleware::class);

$router->post('/company/hr/attendance/delete/{id}', [\App\Controllers\HrPayrollController::class, 'deleteAttendance'])
       ->middleware(AuthMiddleware::class)->middleware(CsrfMiddleware::class);

$router->post('/company/hr/payroll/delete/{id}', [\App\Controllers\HrPayrollController::class, 'deletePayroll'])
       ->middleware(AuthMiddleware::class)->middleware(CsrfMiddleware::class);

$router->post('/company/tally/vouchers/delete/{id}', [\App\Controllers\TallyController::class, 'deleteVoucher'])
       ->middleware(AuthMiddleware::class)->middleware(CsrfMiddleware::class);
