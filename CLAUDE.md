# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Framework & Technology Stack

This is a **CodeIgniter 3** PHP application with some CodeIgniter 4 patterns. The codebase runs on XAMPP (Windows environment) and uses MySQL for data persistence.

### Core Technologies
- **Framework**: CodeIgniter 3.x
- **PHP Version**: 7.4 or 8.0+
- **Database**: MySQL (managed via mysqli driver)
- **Server**: Apache with mod_rewrite
- **Dependency Management**: Composer

### Key Dependencies
- `phpoffice/phpspreadsheet` - Excel file generation and manipulation
- `appolous/lazada-php-sdk` - Lazada marketplace API integration
- `laminas/laminas-escaper` - HTML/XML escaping
- `nesbot/carbon` - Date/time manipulation

## Development Environment Setup

### Initial Setup
1. Copy `.env.example` to `.env` and configure all credentials
2. Configure Apache with mod_rewrite enabled (see SETUP.md)
3. Set `AllowOverride All` in Apache httpd.conf for clean URLs
4. Import database schema files (check for .sql files in root)
5. Ensure session directory exists: `application/cache/sessions/`

### Environment Configuration
The application uses a custom environment variable system via `application/helpers/env_helper.php`:
- Configuration loaded from `.env` file in project root
- Access variables using `env('KEY_NAME', 'default_value')`
- Database configuration in `application/config/database.php` uses env() helper
- Supports Google OAuth, SMTP, and database credentials

### Apache Configuration
For clean URLs (removing `index.php`):
1. Enable mod_rewrite in `httpd.conf`
2. Set `AllowOverride All` for the project directory
3. Restart Apache
4. `.htaccess` file in project root handles URL rewriting

## Architecture Overview

### MVC Pattern with Mixed Controller Types

The codebase uses **two types of controllers**:

1. **Legacy CI_Controller** (CodeIgniter 3 style)
   - Used in: Api.php, Api_v2.php, Api_v3.php, Ajax.php, Auth.php, and others
   - Direct CodeIgniter 3 controller extension
   - Manual library/model loading in constructor

2. **Modern BaseController** (CodeIgniter 4 style pattern)
   - Located at: `application/controllers/BaseController.php`
   - Used in: Dashboard.php, Influencer.php, Endorse.php, Transaction.php, etc.
   - Pre-initializes common dependencies: database, template, spreadsheet
   - Provides helper methods: `selectWithQuery()`, `deleteData()`
   - Supports caching (Memcached with file fallback)

**When creating new controllers**: Extend `BaseController` for feature modules, use `CI_Controller` for APIs and utilities.

### Authentication & Authorization

#### Session-Based Authentication
- Login handled by `Auth` controller
- Session stores `$_SESSION['is_login']` and `$_SESSION['user']`
- User redirects after login based on role and permissions via `get_user_default_page()`

#### Password System
- **Hybrid password verification**: Supports both MD5 (legacy) and password_hash()
- On login, MD5 passwords are automatically upgraded to password_hash()
- New registrations use secure password_hash() with PASSWORD_DEFAULT

#### Role-Based Access Control (RBAC)
- Permission library: `application/libraries/permission.php`
- Database tables: `roles`, `user_roles`, `modules`, `role_permissions`
- Check access with: `$this->permission->has_module_access($user_id, 'module_name')`
- Roles have hierarchical levels and display names

### Database Layer

#### Configuration
- Database config: `application/config/database.php`
- Uses environment variables via `env()` helper
- Connection established in BaseController or manually with `$this->load->database()`

#### Data Access Patterns
1. **Mymodel** (`application/models/mymodel.php`)
   - Common CRUD operations
   - `selectWithQuery($sql)` - Raw SQL queries
   - `selectDataOne($table, $where)` - Single record fetch

2. **Query Builder**
   - Available via `$this->db`
   - Used for complex queries and prepared statements

3. **Raw SQL**
   - Common pattern: `$this->mymodel->selectWithQuery("SELECT ...")`
   - **Security Note**: Many queries use string concatenation - be careful with SQL injection

### Template & Views

#### Template System
- Template library: `application/libraries/template.php`
- Main templates:
  - `Template.php` - Public pages (login, signup)
  - `TemplateDashboard.php` - Authenticated pages with navigation
- Views organized by feature in `application/views/`

#### View Loading Pattern
```php
$data['title'] = 'Page Title - ' . $this->template->title();
$data['content'] = $this->load->view('module/view_name', $data, true);
$this->load->view('TemplateDashboard', $data);
```

### Routing

Routes defined in `application/config/routes.php`:
- Default controller: `home/index`
- 404 override: `page/error`
- Extensive custom routes for APIs, authentication, and marketplace callbacks
- URI dashes automatically translated to underscores

## Key Features & Modules

### Marketplace Integration
The application integrates with multiple e-commerce platforms:

1. **Shopee** - Order sync, product management, ads data
2. **Lazada** - API integration via `appolous/lazada-php-sdk`
3. **TikTok** - Campaign data, GMV tracking, ad spend
4. **Meta (Facebook)** - Ads API integration

#### API Controllers
- `Api.php` - Legacy marketplace APIs (Shopee, Lazada, TikTok auth & sync)
- `Api_v2.php` - Marketplace callbacks, webhooks, order management, cronjobs
- `Api_v3.php` - Ads data, TikTok campaigns, product sync cronjobs

#### Marketplace Configuration
- Database table: `marketplace_config`
- Stores shop IDs, access tokens, refresh tokens
- Token refresh endpoints in Api_v2 controller

### Influencer & Endorsement Management

**Endorse Campaign System**:
- Campaign planning and tracking (`endorse_campaign` table)
- Individual endorsements (`endorse` table)
- Payment logging (`payment_logs` table)
- Analytics and reporting by brand

**Key Controllers**:
- `Influencer.php` - Influencer database management
- `Endorse.php` - Endorsement tracking
- `Endorse_campaign.php` - Campaign management
- `Review_endorse.php` - Review workflow

### Dashboard & Analytics

**Dashboard Controller** (`application/controllers/Dashboard.php`):
- Multi-source spending aggregation (Ads, KOL, Expenses)
- Performance optimization with Memcached caching (60-second TTL)
- Brand-based filtering
- Key methods:
  - `index()` - Main dashboard with GMV, spending, revenue
  - `expense()` - Expense breakdown by category
  - `hpp()` - Cost of goods sold (HPP) calculation
  - `hpp_bundling()` - Bundle product HPP analysis
  - `laba_bersih()` - Net profit calculation
  - `marketplace_fee()` - Platform fee analysis

**Caching Strategy**:
- Brands list cached for 60 seconds
- Channels list cached for 60 seconds
- Spending calculations cached with MD5 hash keys

### CRM & Customer Management

**CRM Controller** features:
- Customer interaction tracking
- Follow-up scheduling
- Transaction history
- WhatsApp group management (`group_wa` table)

**Customer Controller**:
- Customer database management
- Purchase history
- Segmentation

### Transaction & Inventory

**Transaction Management**:
- POS and marketplace order processing
- Order status tracking (CANCELLED, RETURN, REFUND, etc.)
- Multi-marketplace reconciliation
- Stock movement tracking

**Stock Management**:
- Real-time inventory tracking
- Stock in/out logging with order association
- SKU-based and ID-based tracking
- Return handling (GOOD vs BAD returns)

### Google Integration

**Google Meet** (`Googlemeet.php`):
- OAuth2 integration for Google Calendar/Meet
- Meeting scheduling and management

**Google MOU** (`Googlemou.php`):
- Document generation via Google Docs API
- MOU (Memorandum of Understanding) PDF generation
- OAuth2 callback handling

### Expense Management

**Expense Controller**:
- Recurring expense generation (cronjob)
- Category-based tracking
- Brand-specific expense filtering
- Integration with dashboard analytics

## Common Development Tasks

### Adding a New Feature Module

1. **Create Controller** (extend BaseController):
```php
<?php
defined('BASEPATH') or exit('No direct script access allowed');
require_once APPPATH . 'core/BaseController.php';

class MyModule extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('mymodel');
        $this->load->library('permission');
        $this->load->library('template');
    }

    public function index()
    {
        $data['title'] = 'My Module - ' . $this->template->title();
        $data['content'] = $this->load->view('mymodule/index', $data, true);
        $this->load->view('TemplateDashboard', $data);
    }
}
```

2. **Add Route** in `application/config/routes.php`
3. **Create Views** in `application/views/mymodule/`
4. **Add Permissions** to `modules` table and configure role access

### Working with APIs

**API Response Pattern**:
```php
$this->output
    ->set_content_type('application/json')
    ->set_output(json_encode([
        'success' => true,
        'data' => $result
    ]));
```

### Database Queries

**Safe Query Pattern** (avoid SQL injection):
```php
// Use escape_str for user input
$brand = $this->db->escape_str($brand_input);
$sql = "SELECT * FROM table WHERE brand = '$brand'";

// Or use query bindings
$sql = "SELECT * FROM table WHERE brand = ?";
$query = $this->db->query($sql, array($brand_input));
```

### Session Management

**Check Authentication**:
```php
if (!isset($_SESSION['is_login']) || !$_SESSION['is_login']) {
    redirect(base_url() . 'auth/login');
}
```

**Check Permissions**:
```php
$user_id = $_SESSION['user']['id'];
if (!$this->permission->has_module_access($user_id, 'module_name')) {
    // Access denied
}
```

### Debugging

**Environment Setting**:
- Environment defined in `index.php`: `define('ENVIRONMENT', 'development');`
- In development: `error_reporting(0);` is set (errors suppressed)
- **To enable errors**: Change to `error_reporting(-1);` and `ini_set('display_errors', 1);`

**Logging**:
- Log threshold: `$config['log_threshold'] = 0;` (disabled by default)
- Log path: `application/logs/`
- Change threshold in `application/config/config.php`

## Important Patterns & Conventions

### Naming Conventions
- Controllers: PascalCase (e.g., `Endorse_campaign.php`)
- Methods: snake_case (e.g., `login_process()`)
- Views: snake_case directories, PascalCase files (e.g., `dashboard/all.php`)
- Database tables: snake_case (e.g., `endorse_campaign`)

### Date/Time Handling
- Default timezone: Asia/Jakarta (set in `index.php`)
- Use Carbon library for complex date operations
- Database format: `Y-m-d H:i:s`
- Template helper: `$this->template->date_format_indo($date)`

### Security Considerations
- Many queries use string concatenation - **always sanitize inputs**
- MD5 passwords are being phased out - use password_hash()
- CSRF protection is disabled: `$config['csrf_protection'] = FALSE;`
- XSS filtering is disabled: `$config['global_xss_filtering'] = FALSE;`
- **When handling user input**: Use `$this->db->escape_str()` or prepared statements

### File Upload Pattern
```php
$config['upload_path'] = './assets/uploads/';
$config['allowed_types'] = 'jpg|jpeg|png|pdf';
$config['max_size'] = 2048;
$this->load->library('upload', $config);
if ($this->upload->do_upload('file_field')) {
    $file_data = $this->upload->data();
}
```

## API Endpoints Reference

### Authentication
- POST `/auth/login` - Login process
- POST `/auth/signup-process` - User registration
- GET `/auth/get_redirect_url` - Get post-login redirect URL

### Marketplace APIs (Api_v2)
- GET `/api/marketplace/callback/shopee` - Shopee OAuth callback
- GET `/api/marketplace/callback/lazada` - Lazada OAuth callback
- GET `/api/marketplace/callback/tiktok` - TikTok OAuth callback
- POST `/api/marketplace/token/refresh` - Refresh marketplace tokens
- GET `/api/marketplace/order` - Fetch marketplace orders
- GET `/api/marketplace/product` - Fetch marketplace products

### Cronjobs
- GET `/api/cronjob/endorse-campaign` - Sync endorsement campaigns
- GET `/api/cronjob/endorse` - Sync endorsements
- GET `/api/cronjob/influencer` - Sync influencer data
- GET `/cronjob/expense` - Generate recurring expenses
- GET `/cronjob/sync-product` - Sync products from marketplaces

### Webhooks
- POST `/api/webhook` - Receive marketplace webhooks

## Database Schema Notes

### Key Tables
- `user` - User accounts with role references
- `roles` - Role definitions with hierarchy
- `user_roles` - User-role assignments (RBAC)
- `modules` - Feature modules for permission system
- `role_permissions` - Module access by role
- `transaction` - Orders from all marketplaces
- `stock` - Inventory movements
- `product` - Product catalog
- `brand` - Brand management
- `marketplace_config` - Marketplace API credentials
- `endorse` - Individual endorsements
- `endorse_campaign` - Endorsement campaigns
- `payment_logs` - Payment tracking for endorsements
- `expense` - Expense tracking by category

### Important Fields
- Most tables have: `created_at`, `updated_at`, `created_by`, `updated_by`
- Status fields typically use: 'Aktif'/'Tidak Aktif' or 'ENABLE'/'DISABLE'
- Order status: 'CANCELLED', 'RETURN', 'REFUND', 'IN_CANCELLED', 'UNPAID'

## Performance Optimization

### Caching
The Dashboard controller implements a sophisticated caching strategy:
- Uses Memcached with file-based fallback
- Cache keys generated with MD5 hashes of parameters
- Default TTL: 60 seconds
- Implemented in: `calculate_ads_spending()`, `calculate_kol_spending()`, `calculate_etc_spending()`

### Query Optimization
- Avoid `DATE()` functions in WHERE clauses - use direct comparison
- Use indexed columns for filtering (shop_id, account_id, date ranges)
- Aggregate before joining when possible
- Cache frequently accessed reference data (brands, channels)

## Troubleshooting

### Common Issues

**404 Errors on Clean URLs**:
- Verify mod_rewrite is enabled
- Check `AllowOverride All` in httpd.conf
- Verify `.htaccess` exists in project root
- Check `$config['index_page'] = '';` in config.php

**Database Connection Errors**:
- Verify `.env` file exists and has correct credentials
- Check `env_helper.php` is loaded in database.php
- Verify MySQL service is running

**Session Issues**:
- Check session directory exists: `application/cache/sessions/`
- Verify directory is writable
- Check session configuration in `application/config/config.php`

**Permission Denied**:
- Verify user has role assignment in `user_roles` table
- Check module exists in `modules` table
- Verify role has permission in `role_permissions` table

### Log Files
- Application logs: `application/logs/log-YYYY-MM-DD.php`
- Apache error logs: `C:\xampp\apache\logs\error.log`
- PHP error logs: Check php.ini for error_log setting
