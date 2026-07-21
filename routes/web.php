<?php
/**
 * Application Routes Definition
 * Lead Software Architect - Antigravity
 */

use App\Core\Router;
use App\Controllers\AuthController;
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

$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login'])->middleware(CsrfMiddleware::class);
$router->get('/logout', [AuthController::class, 'logout']);

$router->get('/forgot-password', [AuthController::class, 'showForgotPassword']);
$router->post('/forgot-password', [AuthController::class, 'forgotPassword'])->middleware(CsrfMiddleware::class);
$router->get('/reset-password', [AuthController::class, 'showResetPassword']);
$router->post('/reset-password', [AuthController::class, 'resetPassword'])->middleware(CsrfMiddleware::class);

$router->get('/verify-email', [AuthController::class, 'verifyEmail']);
$router->get('/two-factor', [AuthController::class, 'showTwoFactor']);
$router->post('/two-factor', [AuthController::class, 'verifyTwoFactor'])->middleware(CsrfMiddleware::class);

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

$router->get('/developer/subscriptions', [DeveloperController::class, 'subscriptions'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('developer.subscriptions');

$router->post('/developer/subscriptions/create', [DeveloperController::class, 'createSubscriptionPlan'])
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

$router->get('/company/merchandising/buyerpos', [\App\Controllers\MerchandisingController::class, 'buyerpos'])
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

$router->post('/company/production/orders/create', [\App\Controllers\ProductionController::class, 'createOrder'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.production.manage');

$router->get('/company/production/stage/{id}', [\App\Controllers\ProductionController::class, 'stage'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.production.view');

$router->post('/company/production/stage/{id}/log', [\App\Controllers\ProductionController::class, 'logStage'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.production.manage');

$router->get('/company/production/quality', [\App\Controllers\ProductionController::class, 'quality'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.production.view');

$router->post('/company/production/quality/create', [\App\Controllers\ProductionController::class, 'createInspection'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.production.manage');

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

$router->get('/company/hr/payroll', [\App\Controllers\HrPayrollController::class, 'payroll'])
       ->middleware(AuthMiddleware::class)
       ->middleware(PermissionMiddleware::class)
       ->permission('company.users.view');

$router->post('/company/hr/payroll/process', [\App\Controllers\HrPayrollController::class, 'processPayroll'])
       ->middleware(AuthMiddleware::class)
       ->middleware(CsrfMiddleware::class)
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
