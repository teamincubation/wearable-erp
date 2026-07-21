# Wearable ERP - Apparel Manufacturing SaaS Platform

A multi-tenant Software-as-a-Service (SaaS) Enterprise Resource Planning (ERP) platform customized for the Garment and Wearable Manufacturing Industry in India. Developed with Object-Oriented PHP (MVC) and MySQL, it features a modern premium UI inspired by the PEPP Learning portal design (clean spacing, rounded widgets, smooth layouts).

The first pilot customer is **TOCCO Exports** (T-Shirt Manufacturer, Tiruppur, Tamil Nadu).

---

## 🚀 Key Features (Phase 1)

1. **Multi-Tenant SaaS Routing**: Resolves the tenant instance automatically via subdomain (e.g., `tocco.mywellgro.online`). Fallback query parameter `?tenant=tocco` is provided for local DNS-free testing.
2. **Dynamic Onboarding**: SaaS Owner/Developer Portal to onboard tenants instantly, seeding default roles, feature sets, licenses, and admin accounts.
3. **Role-Based Access Control (RBAC)**: Fine-grained permission middleware protecting controller endpoints.
4. **Strong Security**:
   - Prepared Statements (PDO) protecting against SQL Injection.
   - Core tenant-scoping isolation in `Model.php` to prevent cross-tenant data leaks.
   - CSRF & XSS prevention filters.
   - Email verification & Two-Factor Authentication (MFA).
5. **Security Audit Logs**: Track platform-wide events (logins, deactivations, setting modifications).

---

## 📂 Project Structure

```text
wearable-erp/
├── app/
│   ├── Controllers/    # Auth, Developer Portal, Company ERP Controllers
│   ├── Models/         # Database entities (User, Company, Role, AuditLog)
│   ├── Core/           # Router, Database, Session, Request, Response, Auth
│   ├── Middleware/     # Auth, Permission, CSRF, Tenant Resolution
│   └── Views/          # UI templates (Auth, Developer Portal, Tenant ERP)
├── config/             # App & Database settings
├── database/           # schema.sql schema & initial seeds
├── public_html/        # Web Server Root (index.php, .htaccess, assets)
│   └── assets/         # App stylesheets (app.css), js, images
├── routes/             # routes/web.php mapping definitions
└── README.md
```

---

## 🛠️ Installation & Setup

### 1. Database Setup
1. Start your local MySQL / MariaDB server.
2. Create a database named `wearable_erp`.
3. Import the database schema and seeds from `database/schema.sql`:
   ```bash
   mysql -u root -p wearable_erp < database/schema.sql
   ```

### 2. Configure Database Connection
Edit [config/database.php](file:///d:/Adnan%20Vellicheri/WORKS/Wearable_ERP/config/database.php) if your local MySQL database username or password differs from the default:
```php
return [
    'host'      => '127.0.0.1',
    'database'  => 'wearable_erp',
    'username'  => 'root',
    'password'  => '', // Your database password
];
```

### 3. Run Development Server
Boot PHP's built-in web server pointing to the `public_html` root directory:
```bash
php -S localhost:8000 -t public_html
```

---

## 🧪 Demonstration & Testing Guide

Since local testing might not have DNS subdomains set up, the platform features a developer-friendly query parameter fallback to test different tenant subdomains directly:

| Target Portal | Test URL Shortcut | Test Username | Test Password |
|---|---|---|---|
| **SaaS Landing Page** | [http://localhost:8000/](http://localhost:8000/) | N/A | Choose Tenant from list |
| **Developer Portal** | [http://localhost:8000/login?tenant=erp](http://localhost:8000/login?tenant=erp) | `admin@mywellgro.online` | `Admin@1234` |
| **TOCCO Exports (Pilot)** | [http://localhost:8000/login?tenant=tocco](http://localhost:8000/login?tenant=tocco) | `adnan@toccoexports.com` | `Tocco@1234` |

### Two-Factor Authentication (2FA)
The TOCCO Exports administrator has 2FA enabled. When prompted for the verification code, enter **`123456`** to proceed.

---

## 📝 Git Branch & Commit Workflows
To maintain a professional codebase history, commit modules separately. If pushing directly is locked:

1. **Initial Project Structure**:
   ```bash
   git init
   git add .
   git commit -m "feat: complete MVC folder structure and configurations"
   ```
2. **Database Architecture**:
   ```bash
   git add database/schema.sql
   git commit -m "db: normalized database schema, seeds, and indexes"
   ```
3. **Core MVC Framework**:
   ```bash
   git add app/Core/ app/Middleware/ public_html/.htaccess public_html/index.php
   git commit -m "feat: core router, session security, tenant middleware, and RBAC pipeline"
   ```
4. **Authentication & Roles**:
   ```bash
   git add app/Controllers/AuthController.php app/Models/ app/Views/auth/
   git commit -m "feat: login, logout, password recovery, and 2FA authentication views"
   ```
5. **Developer Portal**:
   ```bash
   git add app/Controllers/DeveloperController.php app/Views/developer/
   git commit -m "feat: SaaS developer portal company onboarding, plans, and releases"
   ```
6. **Company ERP Tenant Dashboards**:
   ```bash
   git add app/Controllers/CompanyController.php app/Views/company/
   git commit -m "feat: tenant employee management, roles editing, and audit history logs"
   ```
