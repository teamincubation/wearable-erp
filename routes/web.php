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
