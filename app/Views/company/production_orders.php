<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold">Production Orders</h3>
        <p class="text-secondary m-0">Plan, launch, and monitor active garment manufacturing batches</p>
    </div>
    <div class="d-flex align-items-center">
        <button class="btn btn-outline-info rounded-pill px-3 me-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#productionWorkflowHelpModal" style="border-width: 2px;" type="button">
            <i class="fa-solid fa-circle-question me-1"></i> How It Works?
        </button>
        <a href="<?= base_url('company/production/quality') ?>" class="btn btn-outline-secondary rounded-pill px-4 me-2">
            <i class="fa-solid fa-clipboard-check me-1"></i> Quality Inspections
        </a>
        <?php if (\App\Core\Auth::hasPermission('company.production.manage')): ?>
            <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addProductionOrderModal">
                <i class="fa-solid fa-industry me-1"></i> Plan New Batch
            </button>
        <?php endif; ?>
    </div>
</div>

<div class="pepp-card">
    <div class="pepp-card-header">
        <h5 class="pepp-card-title"><i class="fa-solid fa-industry text-primary me-2"></i> Active Manufacturing Batches</h5>
    </div>
    <div class="pepp-card-body p-0">
        <div class="table-responsive border-0">
            <table class="table pepp-table mb-0">
                <thead>
                    <tr>
                        <th>Batch Code No</th>
                        <th>Linked Buyer PO</th>
                        <th>Style Description</th>
                        <th>Target Qty</th>
                        <th>Date Launched</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($orders)): ?>
                        <?php foreach ($orders as $o): ?>
                            <tr>
                                <td>
                                    <strong class="text-primary font-monospace"><?= htmlspecialchars($o['production_no']) ?></strong>
                                </td>
                                <td><span class="badge bg-light text-secondary font-monospace"><?= htmlspecialchars($o['buyer_po_no']) ?></span></td>
                                <td>
                                    <div>
                                        <strong class="text-dark"><?= htmlspecialchars($o['style_no']) ?></strong>
                                        <div class="text-secondary small"><?= htmlspecialchars($o['style_name']) ?></div>
                                    </div>
                                </td>
                                <td class="fw-bold font-monospace"><?= number_format($o['target_qty']) ?> pcs</td>
                                <td><?= date('d M Y', strtotime($o['start_date'])) ?></td>
                                <td>
                                    <span class="badge badge-pepp 
                                        <?php 
                                            if ($o['status'] === 'completed') echo 'badge-success';
                                            elseif ($o['status'] === 'running') echo 'badge-info';
                                            elseif ($o['status'] === 'pending') echo 'badge-warning';
                                            else echo 'badge-danger';
                                        ?>">
                                        <?= htmlspecialchars(ucfirst($o['status'])) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <a href="<?= base_url('company/production/stage/' . $o['id']) ?>" class="btn btn-sm btn-outline-primary px-3 rounded-pill me-1">
                                        <i class="fa-solid fa-list-check me-1"></i> Stage Tracker / WIP
                                    </a>
                                    <a href="<?= base_url('company/production/barcode?id=' . $o['id']) ?>" class="btn btn-sm btn-outline-success px-3 rounded-pill me-1">
                                        <i class="fa-solid fa-qrcode me-1"></i> RFID Barcodes
                                    </a>
                                    <form action="<?= base_url('company/production/orders/delete/' . $o['id']) ?>" method="POST" class="d-inline" onsubmit="return confirm('Delete this production order?');">
                                        <?= \App\Core\Session::csrfField() ?>
                                        <button type="submit" class="btn btn-sm btn-outline-danger border-0"><i class="fa-solid fa-trash-can"></i> Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center p-5 text-secondary">
                                <i class="fa-solid fa-industry fs-1 mb-3 text-light"></i>
                                <p class="m-0">No active production order batches registered.</p>
                                <?php if (\App\Core\Auth::hasPermission('company.production.manage')): ?>
                                    <button class="btn btn-sm btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#addProductionOrderModal">
                                        <i class="fa-solid fa-plus me-1"></i> Plan First Batch
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Production Order Modal -->
<?php if (\App\Core\Auth::hasPermission('company.production.manage')): ?>
    <div class="modal fade" id="addProductionOrderModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="<?= base_url('company/production/orders/create') ?>" method="POST">
                <?= \App\Core\Session::csrfField() ?>
                <div class="modal-content" style="border-radius: 12px;">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold">Plan Production Batch</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-start">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Link Approved Buyer PO <span class="text-danger">*</span></label>
                            <select name="po_id" class="form-select text-dark" required>
                                <?php if (empty($buyer_pos)): ?>
                                    <option value="">-- No Approved Buyer POs Available --</option>
                                <?php else: ?>
                                    <option value="">-- Select Approved PO Contract --</option>
                                    <?php foreach ($buyer_pos as $bp): ?>
                                        <option value="<?= $bp['id'] ?>">
                                            <?= htmlspecialchars($bp['po_no']) ?> | Buyer: <?= htmlspecialchars($bp['buyer_name']) ?> (<?= htmlspecialchars($bp['buyer_code']) ?>)<?= !empty($bp['brand_name']) ? ' - Brand: ' . htmlspecialchars($bp['brand_name']) : '' ?> | Style: <?= htmlspecialchars($bp['style_no']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <?php if (empty($buyer_pos)): ?>
                                <div class="form-text text-danger mt-1 small">
                                    <i class="fa-solid fa-triangle-exclamation me-1"></i> You must first create and approve a Buyer Purchase Order contract under <a href="<?= base_url('company/merchandising/buyerpos') ?>" class="text-danger fw-semibold text-decoration-underline">Merchandising > Buyer POs (Contracts)</a>.
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Production Batch Number <span class="text-danger">*</span></label>
                            <input type="text" name="production_no" class="form-control font-monospace" placeholder="e.g. BATCH-TOCCO-001" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Launch Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="start_date" class="form-control text-dark" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary px-4">Plan Batch</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<!-- Production Workflow Help Modal -->
<div class="modal fade" id="productionWorkflowHelpModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content text-start" style="border-radius: 16px; border: none; box-shadow: var(--shadow-lg);">
            <div class="modal-header bg-info text-white" style="border-top-left-radius: 16px; border-top-right-radius: 16px;">
                <h5 class="modal-title fw-bold" id="helpModalTitle" data-translate="title"><i class="fa-solid fa-circle-question me-2"></i> How Production Planning Works - Step-by-Step</h5>
                <div class="d-flex align-items-center">
                    <div class="dropdown me-3">
                        <button class="btn btn-sm btn-light dropdown-toggle px-3 py-1 rounded-pill" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="translate-help-btn" style="font-size: 13px;">
                            <i class="fa-solid fa-language me-1 text-primary"></i> Translate
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow" style="font-size: 13px;">
                            <li><a class="dropdown-item translate-opt" href="#" data-lang="en">English</a></li>
                            <li><a class="dropdown-item translate-opt" href="#" data-lang="hi">हिन्दी (Hindi)</a></li>
                            <li><a class="dropdown-item translate-opt" href="#" data-lang="ta">தமிழ் (Tamil)</a></li>
                            <li><a class="dropdown-item translate-opt" href="#" data-lang="es">Español (Spanish)</a></li>
                            <li><a class="dropdown-item translate-opt" href="#" data-lang="ar">العربية (Arabic)</a></li>
                        </ul>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body text-dark p-4">
                <p class="text-secondary small mb-4" data-translate="subtitle">Follow this step-by-step workflow to plan, track, and complete a garment manufacturing batch in the ERP. Click on any shortcut to navigate directly to that section.</p>
                
                <div class="row g-4 position-relative">
                    <!-- Step 1 -->
                    <div class="col-md-6">
                        <div class="pepp-card h-100 border-start border-info border-3">
                            <div class="pepp-card-body p-3">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge bg-info text-white me-2 rounded-circle d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 13px;">1</span>
                                    <h6 class="fw-bold mb-0 text-dark" data-translate="step-title-0">Register Buyer / Client</h6>
                                </div>
                                <p class="text-secondary small mb-2" data-translate="step-desc-0">Add the buyer/client details first to establish customer profiles in the ERP database.</p>
                                <a href="<?= base_url('company/buyers') ?>" class="btn btn-sm btn-outline-info rounded-pill px-3" data-translate="btn-1">
                                    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Go to Buyers Master
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2 -->
                    <div class="col-md-6">
                        <div class="pepp-card h-100 border-start border-info border-3">
                            <div class="pepp-card-body p-3">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge bg-info text-white me-2 rounded-circle d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 13px;">2</span>
                                    <h6 class="fw-bold mb-0 text-dark" data-translate="step-title-1">Define Styles in Style Master</h6>
                                </div>
                                <p class="text-secondary small mb-2" data-translate="step-desc-1">Register style codes, style names, and design specifics for the items you plan to manufacture.</p>
                                <a href="<?= base_url('company/styles') ?>" class="btn btn-sm btn-outline-info rounded-pill px-3" data-translate="btn-2">
                                    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Go to Style Master
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3 -->
                    <div class="col-md-6">
                        <div class="pepp-card h-100 border-start border-info border-3">
                            <div class="pepp-card-body p-3">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge bg-info text-white me-2 rounded-circle d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 13px;">3</span>
                                    <h6 class="fw-bold mb-0 text-dark" data-translate="step-title-2">Book & Approve Buyer PO</h6>
                                </div>
                                <p class="text-secondary small mb-2" data-translate="step-desc-2">Create a Buyer Purchase Order (Contract) under Merchandising, link it to a Style, and set its status to **Approved**.</p>
                                <a href="<?= base_url('company/merchandising/buyerpos') ?>" class="btn btn-sm btn-outline-info rounded-pill px-3" data-translate="btn-3">
                                    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Go to Buyer POs
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Step 4 -->
                    <div class="col-md-6">
                        <div class="pepp-card h-100 border-start border-info border-3">
                            <div class="pepp-card-body p-3">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge bg-info text-white me-2 rounded-circle d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 13px;">4</span>
                                    <h6 class="fw-bold mb-0 text-dark" data-translate="step-title-3">Configure Active WIP Stages</h6>
                                </div>
                                <p class="text-secondary small mb-2" data-translate="step-desc-3">Determine which manufacturing/WIP operational stages should be active or inactive in ERP settings.</p>
                                <a href="<?= base_url('company/settings') ?>" class="btn btn-sm btn-outline-info rounded-pill px-3" data-translate="btn-4">
                                    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Go to ERP Settings
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Step 5 -->
                    <div class="col-md-6">
                        <div class="pepp-card h-100 border-start border-info border-3">
                            <div class="pepp-card-body p-3">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge bg-info text-white me-2 rounded-circle d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 13px;">5</span>
                                    <h6 class="fw-bold mb-0 text-dark" data-translate="step-title-4">Plan & Launch Production Batch</h6>
                                </div>
                                <p class="text-secondary small mb-2" data-translate="step-desc-4">Click **Plan New Batch** on this page, link it to the Approved Buyer PO, and assign a unique Batch Code number.</p>
                                <button type="button" class="btn btn-sm btn-info text-white rounded-pill px-3" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#addProductionOrderModal" data-translate="btn-5">
                                    <i class="fa-solid fa-plus me-1"></i> Plan New Batch Now
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Step 6 -->
                    <div class="col-md-6">
                        <div class="pepp-card h-100 border-start border-info border-3">
                            <div class="pepp-card-body p-3">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge bg-info text-white me-2 rounded-circle d-flex align-items-center justify-content-center" style="width: 28px; height: 28px; font-size: 13px;">6</span>
                                    <h6 class="fw-bold mb-0 text-dark" data-translate="step-title-5">Move WIP Pipelines & Inspect</h6>
                                </div>
                                <p class="text-secondary small mb-2" data-translate="step-desc-5">Track garment quantities through the active operational stages and log Quality Inspections to finalize the batch.</p>
                                <a href="<?= base_url('company/production/quality') ?>" class="btn btn-sm btn-outline-info rounded-pill px-3" data-translate="btn-6">
                                    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Go to Quality Control
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="border-bottom-left-radius: 16px; border-bottom-right-radius: 16px;">
                <button type="button" class="btn btn-light border rounded-pill px-4" data-bs-dismiss="modal" data-translate="close-btn">Got It, Close</button>
            </div>
        </div>
    </div>
</div>

<script>
const productionTranslations = {
    en: {
        title: "How Production Planning Works - Step-by-Step",
        subtitle: "Follow this step-by-step workflow to plan, track, and complete a garment manufacturing batch in the ERP. Click on any shortcut to navigate directly to that section.",
        steps: [
            { title: "Register Buyer / Client", desc: "Add the buyer/client details first to establish customer profiles in the ERP database." },
            { title: "Define Styles in Style Master", desc: "Register style codes, style names, and design specifics for the items you plan to manufacture." },
            { title: "Book & Approve Buyer PO", desc: "Create a Buyer Purchase Order (Contract) under Merchandising, link it to a Style, and set its status to Approved." },
            { title: "Configure Active WIP Stages", desc: "Determine which manufacturing/WIP operational stages should be active or inactive in ERP settings." },
            { title: "Plan & Launch Production Batch", desc: "Click Plan New Batch on this page, link it to the Approved Buyer PO, and assign a unique Batch Code number." },
            { title: "Move WIP Pipelines & Inspect", desc: "Track garment quantities through the active operational stages and log Quality Inspections to finalize the batch." }
        ],
        btn1: "Go to Buyers Master",
        btn2: "Go to Style Master",
        btn3: "Go to Buyer POs",
        btn4: "Go to ERP Settings",
        btn5: "Plan New Batch Now",
        btn6: "Go to Quality Control",
        closeBtn: "Got It, Close"
    },
    es: {
        title: "Cómo funciona la planificación de producción paso a paso",
        subtitle: "Siga este flujo de trabajo paso a paso para planificar, rastrear y completar un lote de fabricación de prendas en el ERP. Haga clic en cualquier acceso directo para navegar directamente.",
        steps: [
            { title: "Registrar Comprador / Cliente", desc: "Agregue primero los detalles del comprador/cliente para establecer perfiles en la base de datos del ERP." },
            { title: "Definir Estilos en Style Master", desc: "Registre códigos de estilo, nombres y especificaciones de diseño para los artículos a fabricar." },
            { title: "Reservar y Aprobar Orden del Comprador", desc: "Cree una orden de compra del comprador en Merchandising, vincúlela a un estilo y configúrela como aprobada." },
            { title: "Configurar Etapas WIP Activas", desc: "Determine qué etapas operativas de fabricación/WIP deben estar activas o inactivas en la configuración." },
            { title: "Planificar y Lanzar Lote de Producción", desc: "Haga clic en Planificar nuevo lote en esta página, vincúlelo a la orden de compra aprobada y asigne un código." },
            { title: "Mover Líneas de Trabajo e Inspeccionar", desc: "Realice el seguimiento de las prendas a través de las etapas y registre inspecciones de calidad." }
        ],
        btn1: "Ir a Compradores",
        btn2: "Ir a Estilo Master",
        btn3: "Ir a Órdenes del Comprador",
        btn4: "Ir a Configuración",
        btn5: "Planificar Lote Ahora",
        btn6: "Ir a Control de Calidad",
        closeBtn: "Entendido, Cerrar"
    },
    hi: {
        title: "उत्पादन योजना कैसे काम करती है - चरण-दर-चरण",
        subtitle: "ईआरपी में परिधान निर्माण बैच की योजना बनाने, ट्रैक करने और पूरा करने के लिए इस चरण-दर-चरण कार्यप्रवाह का पालन करें। सीधे नेविगेट करने के लिए किसी भी शॉर्टकट पर क्लिक करें।",
        steps: [
            { title: "खरीदार / ग्राहक पंजीकृत करें", desc: "ईआरपी डेटाबेस में ग्राहक प्रोफाइल स्थापित करने के लिए सबसे पहले खरीदार/ग्राहक विवरण जोड़ें।" },
            { title: "स्टाइल मास्टर में स्टाइल परिभाषित करें", desc: "जिन वस्तुओं का आप निर्माण करना चाहते हैं उनके लिए स्टाइल कोड, स्टाइल नाम और डिज़ाइन विवरण दर्ज करें।" },
            { title: "खरीदार पीओ बुक और स्वीकृत करें", desc: "मर्चेंडाइजिंग के तहत एक खरीदार खरीद आदेश (अनुबंध) बनाएं, इसे एक स्टाइल से लिंक करें, और इसकी स्थिति स्वीकृत सेट करें।" },
            { title: "सक्रिय डब्लूआईपी चरणों को कॉन्फ़िगर करें", desc: "निर्धारित करें कि ईआरपी सेटिंग्स में कौन से विनिर्माण/डब्लूआईपी परिचालन चरण सक्रिय या निष्क्रिय होने चाहिए।" },
            { title: "उत्पादन बैच की योजना बनाएं और लॉन्च करें", desc: "इस पृष्ठ पर नया बैच योजना बनाएं पर क्लिक करें, इसे स्वीकृत खरीदार पीओ से लिंक करें, और एक विशिष्ट कोड असाइन करें।" },
            { title: "डब्लूआईपी पाइपलाइनों को ट्रैक करें और निरीक्षण करें", desc: "सक्रिय परिचालन चरणों के माध्यम से परिधान मात्रा को ट्रैक करें और बैच को अंतिम रूप देने के लिए गुणवत्ता निरीक्षण लॉग करें।" }
        ],
        btn1: "बायर्स मास्टर पर जाएं",
        btn2: "स्टाइल मास्टर पर जाएं",
        btn3: "खरीदार पीओ पर जाएं",
        btn4: "ईआरपी सेटिंग्स पर जाएं",
        btn5: "अभी नया बैच प्लान करें",
        btn6: "गुणवत्ता नियंत्रण पर जाएं",
        closeBtn: "समझ गए, बंद करें"
    },
    ar: {
        title: "كيف يعمل تخطيط الإنتاج - خطوة بخطوة",
        subtitle: "اتبع سير العمل خطوة بخطوة لتخطيط وتتبع وإكمال دفعة تصنيع الملابس في النظام. انقر فوق أي اختصار للانتقال مباشرة.",
        steps: [
            { title: "تسجيل المشتري / العميل", desc: "أضف تفاصيل المشتري/العميل أولاً لإنشاء ملفات تعريف العملاء في قاعدة بيانات النظام." },
            { title: "تحديد الأنماط في ماستر الأنماط", desc: "قم بتسجيل رموز الأنماط والأسماء وتفاصيل التصميم للعناصر التي تخطط لتصنيعها." },
            { title: "حجز واعتماد أمر شراء المشتري", desc: "قم بإنشاء أمر شراء المشتري (عقد) بموجب الترويج، وربطه بنمط ما، وتعيين حالته إلى معتمد." },
            { title: "تكوين مراحل العمل الجاري النشطة", desc: "حدد مراحل التصنيع/العمل الجاري التشغيلية التي يجب أن تكون نشطة أو غير نشطة في إعدادات النظام." },
            { title: "تخطيط وإطلاق دفعة الإنتاج", desc: "انقر فوق تخطيط دفعة جديدة في هذه الصفحة، وقم بربطها بأمر شراء المشتري المعتمد، وقم بتعيين رمز فريد." },
            { title: "تتبع خطوط العمل الجاري والفحص", desc: "تتبع كميات الملابس من خلال المراحل التشغيلية النشطة وسجل عمليات فحص الجودة لإنهاء الدفعة." }
        ],
        btn1: "الذهاب إلى المشتريين",
        btn2: "الذهاب إلى ماستر الأنماط",
        btn3: "الذهاب إلى أوامر المشتري",
        btn4: "الذهاب إلى الإعدادات",
        btn5: "تخطيط دفعة جديدة الآن",
        btn6: "الذهاب إلى مراقبة الجودة",
        closeBtn: "موافق، إغلاق"
    },
    ta: {
        title: "உற்பத்தி திட்டமிடல் எவ்வாறு செயல்படுகிறது - படிப்படியாக",
        subtitle: "ஆடை உற்பத்தி தொகுப்பைத் திட்டமிட, கண்காணிக்க மற்றும் முடிக்க இந்த படிப்படியான செயல்முறையைப் பின்பற்றவும். நேரடியாகச் செல்ல ஏதேனும் குறுக்குவழியை கிளிக் செய்யவும்.",
        steps: [
            { title: "கொள்முதல் செயபர் / வாடிக்கையாளர் பதிவு", desc: "வாடிக்கையாளர் சுயவிவரங்களை நிறுவ முதலில் கொள்முதல் செய்பவர்/வாடிக்கையாளர் விவரங்களைச் சேர்க்கவும்." },
            { title: "ஸ்டைல் மாஸ்டரில் ஸ்டைல்களை வரையறுக்கவும்", desc: "நீங்கள் தயாரிக்க திட்டமிட்டுள்ள பொருட்களின் ஸ்டைல் குறியீடு, ஸ்டைல் பெயர் மற்றும் வடிவமைப்பு விவரங்களைப் பதிவு செய்யவும்." },
            { title: "கொள்முதல் ஒப்பந்தத்தை பதிவுசெய்து அங்கீகரிக்கவும்", desc: "வியாபாரப் பிரிவின் கீழ் வாங்குபவரின் கொள்முதல் ஆணையை (ஒப்பந்தம்) உருவாக்கி, அதை ஒரு ஸ்டைலுடன் இணைத்து, அதன் நிலையை அங்கீகரிக்கவும்." },
            { title: "செயலில் உள்ள WIP நிலைகளை கட்டமைக்கவும்", desc: "கட்டமைப்பு அமைப்புகளில் எந்த உற்பத்தி/WIP செயல்பாட்டு நிலைகள் செயலில் அல்லது செயலற்றதாக இருக்க வேண்டும் என்பதைத் தீர்மானிக்கவும்." },
            { title: "உற்பத்தி தொகுப்பைத் திட்டமிட்டுத் தொடங்கவும்", desc: "இந்தப் பக்கத்தில் புதிய தொகுப்பைத் திட்டமிடு என்பதை கிளிக் செய்து, அங்கீகரிக்கப்பட்ட கொள்முதல் ஆணையுடன் இணைத்து, குறியீட்டை ஒதுக்கவும்." },
            { title: "WIP கண்காணிப்பு மற்றும் தர ஆய்வு", desc: "செயலில் உள்ள செயல்பாட்டு நிலைகள் மூலம் ஆடை அளவுகளைக் கண்காணித்து, தொகுப்பை இறுதி செய்ய தர ஆய்வுகளைப் பதிவு செய்யவும்." }
        ],
        btn1: "வாங்குபவர் மாஸ்டருக்குச் செல்லவும்",
        btn2: "ஸ்டைல் மாஸ்டருக்குச் செல்லவும்",
        btn3: "வாங்குபவர் கொள்முதல் ஆணைக்குச் செல்லவும்",
        btn4: "அமைப்புகளுக்குச் செல்லவும்",
        btn5: "புதிய தொகுப்பை இப்போது திட்டமிடு",
        btn6: "தரக் கட்டுப்பாட்டுக்குச் செல்லவும்",
        closeBtn: "புரிந்தது, மூடவும்"
    }
};

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.translate-opt').forEach(opt => {
        opt.addEventListener('click', function(e) {
            e.preventDefault();
            const lang = this.getAttribute('data-lang');
            translateModal(lang);
        });
    });

    function translateModal(lang) {
        const dict = productionTranslations[lang] || productionTranslations['en'];
        
        // Title & Subtitle
        const titleEl = document.getElementById('helpModalTitle');
        if (titleEl) {
            titleEl.innerHTML = '<i class="fa-solid fa-circle-question me-2"></i> ' + dict.title;
        }
        
        const subtitleEl = document.querySelector('[data-translate="subtitle"]');
        if (subtitleEl) subtitleEl.innerText = dict.subtitle;

        // Steps
        dict.steps.forEach((step, idx) => {
            const titleStep = document.querySelector(`[data-translate="step-title-${idx}"]`);
            if (titleStep) titleStep.innerText = step.title;
            
            const descStep = document.querySelector(`[data-translate="step-desc-${idx}"]`);
            if (descStep) descStep.innerText = step.desc;
        });

        // Button Labels
        const iconClasses = [
            'fa-solid fa-arrow-up-right-from-square me-1',
            'fa-solid fa-arrow-up-right-from-square me-1',
            'fa-solid fa-arrow-up-right-from-square me-1',
            'fa-solid fa-arrow-up-right-from-square me-1',
            'fa-solid fa-plus me-1',
            'fa-solid fa-arrow-up-right-from-square me-1'
        ];

        for (let i = 1; i <= 6; i++) {
            const btnEl = document.querySelector(`[data-translate="btn-${i}"]`);
            if (btnEl) {
                btnEl.innerHTML = `<i class="${iconClasses[i-1]}"></i> ${dict['btn' + i]}`;
            }
        }

        // Close Button
        const closeEl = document.querySelector('[data-translate="close-btn"]');
        if (closeEl) closeEl.innerText = dict.closeBtn;
    }
});
</script>

