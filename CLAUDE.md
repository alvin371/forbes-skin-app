# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Application Overview

The **BH Skin Application** is a comprehensive e-commerce and influencer marketing management platform built with PHP CodeIgniter 3. It serves as a central hub for managing a beauty/skincare business across multiple online marketplaces including Shopee, Lazada, TikTok, and WhatsApp Business.

## Technology Stack

- **Backend**: PHP 7.4+ with CodeIgniter 3 framework  
- **Database**: MySQL with Memcached caching layer
- **Frontend**: Bootstrap 5 with jQuery and custom JavaScript
- **Excel Processing**: PhpSpreadsheet library
- **Session Management**: Custom session handling with database storage
- **APIs**: Multi-version REST APIs (Api, Api_v2, Api_v3)
- **UI Libraries**: SweetAlert2 for enhanced modals and notifications
- **Code Quality**: PHP-CS-Fixer for coding standards, Prettier for JavaScript formatting

## Core Business Modules

### Dashboard & Analytics (`application/controllers/Dashboard.php`)
- Multi-platform sales tracking and revenue analytics
- Real-time performance metrics for Shopee, Lazada, TikTok, WhatsApp
- Date range filtering with caching for performance optimization

### Transaction Management (`application/controllers/Transaction.php`)
- Order processing and fulfillment tracking
- Payment reconciliation across multiple platforms
- Order status synchronization with marketplace APIs

### Influencer Campaign Management (`application/controllers/Influencer.php`)
- End-to-end influencer marketing campaign management
- Performance tracking and commission calculations
- Content approval workflows and campaign analytics

### Product Management (`application/controllers/Product.php`)
- Inventory management with stock level monitoring
- Product catalog synchronization with external platforms
- Pricing and promotion management

### CRM System (`application/controllers/Crm.php`)
- Customer relationship management
- Service ticket tracking and resolution
- Customer communication history

### Quest Management System
- **Unified Quest Management** (`application/controllers/Quest.php`) - Consolidated controller managing main quests, enhanced side quests, and milestone system
- **Quest Levels** (`application/controllers/Quest_level.php`) - Manage Junior/Intermediate/Senior levels
- **Positions** (`application/controllers/Position.php`) - Company positions linked to quest levels
- **Benefits** (`application/controllers/Benefit.php`) - Reward system for completed quests with benefit integration
- **Employee Quest Interface** (`application/controllers/Profile.php`) - Employee quest application interface with cancellation functionality
- **Enhanced Side Quests**: Support for points, animation images, and reward descriptions
- **Milestone System**: Achievement-based progression tracking with leaderboards

### System Administration
- **Roles Management** (`application/controllers/Roles.php`) - Comprehensive RBAC system with permission matrix interface
- **Modules Management** (`application/controllers/Modules.php`) - System module management with hierarchical organization and bulk operations
- **User Management** (`application/controllers/User.php`) - User account administration and role assignments

## Architecture Patterns

### MVC Structure
- **Models**: Single model approach using `application/models/Mymodel.php` for all database operations
- **Controllers**: Feature-based controllers with shared functionality
- **Views**: Template-based views with shared components in `application/views/template/`

### Database Architecture
- Primary database configuration in `application/config/database.php`
- Memcached integration for performance optimization (`application/config/memcached.php`)
- Session data stored in database table `ci_sessions`
- Quest system tables: `quest_levels`, `positions`, `user_profile`, `benefits`, `main_quests`, `side_quests`, `main_quest_submissions`, `side_quest_submissions`
- Enhanced side quests with additional fields: `points`, `gambar_animasi`, `reward`
- Milestone system tables: `milestone_side_quests`, `milestone_achievements`, `user_side_quest_stats`
- **Benefit System Migration**: System migrated from foreign key relationships to text-based benefits
  - `main_quest_submissions.benefit_type` stores benefit as text (VARCHAR)
  - `main_quests.default_benefit` stores default benefit as text
  - Benefits table maintained for reference/management but no longer used as foreign keys
- Database setup scripts: `database_quest_module.sql` (base system), `enhanced_side_quest_schema.sql` (enhancements), `role_based_permissions.sql` (RBAC system)
- Migration scripts available for incremental updates and fixes

### Authentication & Security
- Session-based authentication with comprehensive role-based permission system
- **Hybrid Password System**: Supports both legacy MD5 and modern secure password hashing
  - New users get `password_hash()` with PASSWORD_DEFAULT (bcrypt)
  - Existing MD5 passwords automatically upgraded on login
  - Backward compatibility maintained for existing users
- **Smart Login Redirection**: Permission-based routing after login
  - Users redirected to appropriate pages based on role and permissions
  - Fallback chain prevents "access denied" after login
  - Session-based redirect URL management for security
- **Role-Based Access Control (RBAC)**: Complete permission matrix system with granular permissions per module
- Database-driven role system with tables: `roles`, `role_permissions`, `user_roles`, `user_permission_overrides`
- **Guest User Support**: Self-registration with automatic "guest" role assignment
- API authentication using tokens and session validation

### Caching Strategy
- Memcached implementation for frequently accessed data
- Cache keys based on MD5 hashes of request parameters
- Short TTL (10 seconds) for real-time data, longer for static content

## Key Development Files

### Configuration
- `application/config/config.php` - Main application configuration
- `application/config/database.php` - Database connection settings
- `application/config/memcached.php` - Cache configuration
- `application/config/routes.php` - URL routing configuration

### Core Components
- `application/models/Mymodel.php` - Universal model for all database operations
- `application/core/BaseController.php` - **Critical**: Base controller with automatic RBAC permission middleware
- `application/controllers/Ajax.php` - AJAX endpoints for frontend interactions
- `application/controllers/Auth.php` - **Enhanced**: Authentication controller with sign-up, secure login, and smart redirection
- `application/libraries/Template.php` - Custom template helper with utility functions (endpoint URLs, color conversion, code generation)
- `application/libraries/Permission.php` - RBAC permission checking and position-based access control
- `application/libraries/` - Custom libraries including marketplace integrations
- `application/helpers/` - Utility functions and shared code

### Frontend Assets
- `assets/` - CSS, JavaScript, and image files
- Bootstrap 5 framework with custom styling
- jQuery for DOM manipulation and AJAX calls
- **SweetAlert2 Integration**: Beautiful modals and confirmations throughout the application
  - Custom styling for logout confirmations and delete operations
  - Professional loading states and user feedback
  - Consistent design system with hover effects and animations

## Marketplace Integrations

### Shopee Integration
- SDK located in `application/libraries/shopee/`
- Order synchronization and product management
- Analytics data retrieval for dashboard

### Lazada Integration
- API integration for order management
- Product catalog synchronization
- Performance metrics tracking

### TikTok Shop Integration
- Order processing and fulfillment
- Content management for TikTok campaigns
- Sales analytics and reporting

### WhatsApp Business Integration
- Customer communication management
- Order notifications and updates
- Support ticket integration

## Development Workflow

### Common Commands
- **Dependencies**: 
  - `composer install` - Install PHP dependencies including PhpSpreadsheet and Lazada SDK
  - `npm install` - Install Node.js dependencies (Prettier for code formatting)
- **Testing**: `vendor/bin/phpunit` or `composer test` - Run PHPUnit tests
- **Code Formatting**: `npx prettier --write .` - Format code using Prettier
- **Linting**: `vendor/bin/php-cs-fixer fix` - Fix PHP coding standards
- **Database Setup**: Execute `database_quest_module.sql` in MySQL to create base quest system tables
- **Enhanced Side Quest Setup**: Execute `enhanced_side_quest_schema.sql` to add milestone system and enhanced side quest features
- **Role-Based Permissions Setup**: Execute `role_based_permissions.sql` to create RBAC system (roles, permissions, user assignments)
- **Module Management Setup**: Execute `clear_and_replace_modules.sql` to populate 32 modules with proper permission matrix
- **Permission View Setup**: Execute `final_permission_view.sql` to create `user_module_permissions` view for efficient permission checking
- **Migration Scripts Available**: Multiple fix/update scripts for incremental database changes (fix_milestone_table.sql, fix_leaderboard_tables.sql, etc.)

### Development Server Setup
This is a CodeIgniter 3 application running on PHP 7.4+ with MySQL. For local development:
1. Set up XAMPP or similar LAMP/WAMP stack with PHP 7.4+, MySQL, and Apache
2. Configure virtual host pointing to application root
3. Import database schema and configure `application/config/database.php`
4. Enable Memcached for caching (configure in `application/config/memcached.php`)
5. Ensure required PHP extensions are enabled: intl, json, mbstring, gd, zip, curl, mysqli

### Database Operations
All database queries go through `application/models/Mymodel.php` using methods like:
- `selectWithQuery($sql)` - Custom SQL queries with result arrays
- `selectData($table)` - Simple SELECT operations
- `selectWhere($table, $where)` - SELECT with WHERE conditions  
- `insertData($table, $data)` - INSERT operations with data validation
- `updateData($table, $data, $where)` - UPDATE operations with where conditions
- `deleteData($table, $where)` - DELETE operations with safety checks

### Bulk Operations Pattern
Modern CRUD controllers support bulk operations following this pattern:
- `bulk_delete()` method with transaction safety and detailed validation
- Smart pre-validation (check dependencies, foreign keys, business rules)
- Comprehensive result reporting (success/partial/failure with specific reasons)
- UI integration with checkbox selection and SweetAlert2 confirmations

### AJAX Patterns
Frontend-backend communication primarily through `application/controllers/Ajax.php`:
- JSON responses for all AJAX calls
- Consistent error handling and response formats
- Caching integration for performance optimization

### Error Handling
- Database errors logged and handled gracefully
- User-friendly error messages in frontend
- API errors return consistent JSON error responses
- **Beautiful Error Pages**: Custom 404 and 403 error pages with modern design and branding
  - `application/views/errors/html/error_404.php` - Page not found with search functionality
  - `application/views/errors/html/error_403.php` - Access restricted with user context and request access options

## File Upload & Processing

### Excel File Processing
- PhpSpreadsheet library for reading/writing Excel files
- Bulk import functionality for products and orders
- Template-based export for various data types

### Image Handling
- Product images stored in `assets/uploads/`
- Side quest animation images stored in `assets/uploads/side_quest_animations/`
- Automatic file cleanup on update/delete operations
- Supported formats: JPG, PNG, GIF, WEBP (max 2MB)
- Thumbnail generation and optimization
- CDN integration for external image hosting

## Performance Considerations

### Caching Implementation
- Memcached for database query results
- Cache invalidation on data updates
- Per-user caching for personalized content

### Database Optimization
- Indexed columns for frequently queried data
- Query optimization in model methods
- Connection pooling and persistent connections

### Frontend Optimization
- Minified CSS and JavaScript in production
- Lazy loading for large data sets
- AJAX pagination for better user experience

## API Structure

### Versioning
The application uses a multi-version REST API approach:
- `application/controllers/Api.php` - Original API endpoints (v1)
- `application/controllers/Api_v2.php` - Enhanced API with additional features
- `application/controllers/Api_v3.php` - Latest API version with improved security
- All APIs follow the same base controller pattern but with incremental improvements

### Authentication
- Token-based authentication for API calls
- Session validation for web interface
- Role-based access control for different user types
- **Self-Registration API**: Sign-up endpoint with comprehensive validation
  - Strong password requirements (8+ chars, mixed case, numbers, symbols)
  - Real-time client-side validation with visual feedback
  - Automatic guest role assignment and RBAC integration

## Quest System Architecture

### Unified Quest Controller Structure
The quest system is managed through a single `Quest` controller with method-based separation:
- **Main Quest Methods**: `main_quest_tab()`, `main_quest_item()`, `main_quest_create_page()`, `main_quest_store()`, `main_quest_edit_page()`, `main_quest_update()`, `main_quest_detail()`, `main_quest_delete()`
- **Side Quest Methods**: `side_quest_tab()`, `side_quest_item()`, `side_quest_create_page()`, `side_quest_store()`, `side_quest_edit_page()`, `side_quest_update()`, `side_quest_detail()`, `side_quest_delete()`
- **Milestone & Leaderboard Methods**: `milestone_leaderboard_tab()`, milestone CRUD operations, leaderboard calculations
- **Submission Management**: `main_quest_submissions()`, `main_quest_submissions_item()`, `approve_main_quest_submission()`, `deny_main_quest_submission()`, `side_quest_submissions()`, `side_quest_submissions_item()`, `approve_side_quest_submission()`, `deny_side_quest_submission()`
- **Routes**: All quest management accessed via `/quest` base route (e.g., `/quest/main_quest_create_page`)
- **Three-Tab Interface**: Main Quests | Side Quests | Milestone & Leaderboard

### Level-Based Hierarchy
- **Quest Levels**: Junior (1), Intermediate (2), Senior (3) - stored in `quest_levels` table
- **Positions**: Company roles linked to quest levels - determines employee quest access
- **User Profiles**: Extended employee data with position assignment in `user_profile` table

### Quest Flow Logic
- **Main Quests**: Position-restricted with **required** default benefit selection, HR approval workflow, and automatic benefit assignment
- **Side Quests**: Open to all employees with enhanced features:
  - Points system (configurable point values per quest)
  - Animation images for visual appeal
  - Detailed reward descriptions
  - Traditional scoring: notes_point + presentation_point + quest_points
- **Milestone Quests**: Achievement-based progression system:
  - Triggered by side quest completion milestones
  - Three types: quest_count, total_points, monthly_points
  - Automatic detection and reward assignment
- **Submissions**: Tracked in separate tables with status (pending/approved/denied) and HR notes
- **Quest Cancellation**: Users can cancel pending quest applications through Profile interface
  - Only pending submissions can be cancelled (not approved/denied)
  - Security validation ensures users can only cancel their own submissions
  - Cancellation permanently deletes submission record from database
- **Benefits**: Text-based benefit system with configurable rewards (promotion, bonus, salary, leave, wfa) that are **automatically assigned** during approval based on quest's default benefit
- **Quest Levels**: Include level_order field for proper hierarchical ordering
- **Benefit Workflow**: HR enters benefit type as text during quest creation (required field) → Employee applies → HR approves/denies → System automatically assigns pre-selected benefit type

### Role-Based Access Control
- **HR/Admin (roles 1,2)**: Full access to quest management, submissions review, benefit assignment, milestone management, leaderboard analytics
- **Employees**: Quest application interface, personal submission history, quest eligibility based on position level, milestone progress tracking, leaderboard viewing

## Role-Based Permission System

### Architecture Overview
The application uses a comprehensive Role-Based Access Control (RBAC) system with automatic middleware enforcement:

- **BaseController Middleware** (`application/core/BaseController.php`): Automatic permission checking for all controllers
- **Dynamic Permission Matrix**: Permission matrix generates dynamically based on actual module functionality analysis
- **47+ Module System**: Complete module registry with accurate permission mappings based on real functionality
- **Dynamic Sidebar**: Menu items automatically hidden based on user view permissions
- **Roles** (`application/controllers/Roles.php`): Complete role management with dynamic permission matrix interface
- **Modules** (`application/controllers/Modules.php`): System module registry with hierarchical organization and bulk operations
- **Smart Permission Types**: Only relevant permissions shown per module (View, Create, Update, Delete, Approve)
- **Module Categories**: System Management, HR Management, Marketing, Operations, Reports & Analytics, Account Management
- **Form-Based Management**: Role creation/editing uses single form submission with integrated permission matrix

### Dynamic Permission Configuration
The system uses a comprehensive module permission mapping in `Roles::get_module_permissions()`:
- **Full CRUD Modules (30+ modules)**: Standard Create, Edit, Delete, View permissions
- **Approval Workflow Modules (2 modules)**: interview, recruitment (View + Approve only)
- **Read-Only Modules (15+ modules)**: dashboard, profile, report, marketing analytics (View only)
- **Module-Specific Permissions**: Each module shows only permissions that actually exist in functionality

### Permission Middleware System
All controllers should extend `BaseController` for automatic permission enforcement:

```php
class YourController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        
        // Set public methods (no permission required)
        $this->set_public_methods(['api_endpoint', 'webhook']);
        
        // Override method-to-action mapping
        $this->set_method_permissions([
            'export' => 'view',
            'import' => 'create',
            'approve_submission' => 'approve'
        ]);
    }
}
```

**Automatic Features:**
- **Method-to-Action Mapping**: `index`→view, `store`→create, `update`→edit, `delete`→delete, `approve`→approve
- **Controller-to-Module Mapping**: 32 modules mapped including parameter-based modules (ads, crm)
- **403 Error Pages**: Unauthorized access shows beautiful error page instead of redirect
- **AJAX Protection**: `require_ajax_permission()` method for AJAX endpoints
- **Backward Compatibility**: Existing `enforce_permission()` calls continue to work

### RBAC Database Schema
- `roles`: Role definitions with levels and descriptions
- `role_permissions`: Permission matrix linking roles to modules with granular permissions
- `user_roles`: User-to-role assignments (many-to-many relationship)
- `modules`: System modules with categorization and metadata
- `user_permission_overrides`: Individual user permission exceptions (optional)

### Permission Management Workflow
1. **Role Creation**: Administrators create roles with comprehensive permission matrix
2. **User Assignment**: Users assigned to roles through `user_roles` table
3. **Permission Inheritance**: Users inherit all permissions from assigned roles
4. **Automatic Enforcement**: BaseController middleware checks permissions on every request
5. **Dynamic UI**: Sidebar menu items automatically hidden based on view permissions
6. **Error Handling**: 403 error page shown for unauthorized access with user context
7. **Module Organization**: Permissions organized by collapsible module categories

### Key RBAC Features
- **Permission Matrix Interface**: Visual grid showing all modules and permission types
- **Module Categorization**: Logical grouping of related modules for easier management
- **Role Hierarchy**: Level-based role ordering (1-10) with higher levels having more authority
- **System Role Protection**: Core roles (super_admin, admin, employee) have restricted editing
- **Database Transactions**: Atomic role and permission updates for data consistency

### Milestone & Leaderboard System
- **Milestone Types**: 
  - `quest_count`: Achievements based on number of completed side quests
  - `total_points`: Achievements based on cumulative points earned
  - `monthly_points`: Achievements based on points earned in current month
- **Automatic Detection**: System tracks side quest completions and automatically awards milestones
- **Leaderboard Features**:
  - Monthly leaderboard: Resets each month, shows current month rankings
  - Ongoing leaderboard: All-time point totals with milestone bonus points
  - Real-time updates with caching for performance optimization
- **Statistics Tracking**: `user_side_quest_stats` table maintains running totals for efficient calculations
- **Achievement Management**: `milestone_achievements` table tracks user milestone completions with claim status

## Common Development Tasks

### Adding New CRUD Controllers
1. Create controller extending `BaseController` in `application/controllers/`
2. Configure permissions in constructor using `set_public_methods()` and `set_method_permissions()`
3. Implement standard methods: `index()`, `item()`, `create_page()`, `store()`, `edit_page()`, `update()`, `detail()`, `remove()`, `delete()`
4. Add bulk operations: `bulk_delete()` with transaction safety and validation
5. Use `require_ajax_permission()` for AJAX endpoints
6. Create corresponding views directory in `application/views/` with view files: `all.php`, `item.php`, `create_page.php`, `edit_page.php`, `detail_page.php`, `delete.php`
7. Follow existing patterns from Roles, Modules, Quest_level, Position, or Benefit controllers
8. Add module to `clear_and_replace_modules.sql` if new functionality
9. Implement AJAX-powered data loading with proper error handling and loading states

### Database Schema Changes
1. Update SQL in `database_quest_module.sql` for new installations
2. Create migration scripts for existing databases
3. Use `selectWithQuery()` method in Mymodel for complex joins and relationships
4. Test foreign key constraints and cascading deletes

### Quest System Extensions
1. New quest types: Follow `Quest` controller patterns with separate methods for main_quest_*, side_quest_*, milestone_* 
2. Additional benefits: Add to `benefits` table for reference/management - benefits stored as text in quest and submission records
3. Enhanced approval workflows: Extend submission tables with additional status fields
4. File upload handling: Side quest images use CodeIgniter upload library with automatic cleanup
5. Milestone system: Automatic detection based on `user_side_quest_stats` table calculations
6. Leaderboard features: Monthly/ongoing rankings with caching for performance
7. Reporting features: Create queries joining user_profile, positions, quest_levels, benefits, submission tables, and milestone tables
8. Benefit system: Main quests support text-based default benefits that are automatically assigned during approval, with HR override capability
9. Quest cancellation: Users can cancel pending applications via `Profile::cancel_main_quest()` and `Profile::cancel_side_quest()` methods

### Role Permission System Extensions
1. **Adding New Modules**: Add module entry to `Roles::get_module_permissions()` array with appropriate permission set
2. **Permission Analysis**: Analyze all.php view files to determine actual CRUD functionality before adding permissions
3. **Dynamic Matrix Updates**: Permission matrix automatically adjusts to show only relevant permissions per module
4. **Module Categories**: Add new modules to appropriate category in `get_module_category()` method
5. **Permission Validation**: Server-side validation prevents assignment of non-existent permissions during role creation/editing

## Testing & Debugging

### Debugging
- Error logging enabled in development environment
- Database query logging for performance analysis
- Browser developer tools for frontend debugging

### Data Validation
- Input validation in controller methods
- Database constraints for data integrity
- Frontend validation using JavaScript/jQuery

## Deployment Considerations

### Environment Configuration
- Separate configuration files for development/production
- Database credentials and API keys in config files
- Memcached configuration for production performance

### Security
- Input sanitization and validation
- SQL injection prevention through parameterized queries
- XSS protection in view rendering
- CSRF protection for form submissions

## Development Standards

### Controller Patterns
- **BaseController Extension**: All new controllers should extend `BaseController` for automatic permission middleware
- **Permission Configuration**: Use `set_public_methods()` and `set_method_permissions()` in constructor
- **Standard CRUD operations**: Follow existing naming conventions (`index`, `item`, `create_page`, `store`, `edit_page`, `update`, `detail`, `remove`, `delete`)
- **AJAX Security**: Use `require_ajax_permission()` for AJAX endpoints with automatic 403 JSON responses
- **Manual Permission Checks**: Use `require_permission()`, `has_permission()`, and `get_permission_data()` when needed
- **Legacy Support**: Existing role-based checks `in_array($_SESSION['user']['role'], array(...))` still supported
- **Database Transactions**: Use transactions for complex operations (roles with permissions, quest submissions with benefits)
- **File upload handling**: Use CodeIgniter upload library with validation (2MB limit, specific formats)
- **AJAX responses**: Use template helper methods for consistent success/error messaging
- **Data validation**: Check for existing records before insert/update operations, validate required fields
- **File cleanup**: Remove associated files on record deletion

### View Patterns  
- Bootstrap 5 styling with consistent modern design system
- AJAX-powered data loading with loading states and error handling
- Modal confirmations for delete operations with SweetAlert2 integration
- Form validation with real-time feedback
- Responsive design with mobile-first approach
- **Dynamic Permission Matrix Interface**: Collapsible module groups with comprehensive checkbox grid
  - Variable permission types per module (View, Create, Update, Delete, Approve) - only shows relevant permissions
  - Module categorization with expand/collapse functionality
  - Hierarchical check-all controls (Master → Column → Group → Module → Individual)
  - Form-based submission (not real-time updates)
  - Pre-populated checkboxes for edit modes with proper state initialization
  - Visual indicators for read-only modules and approval workflows
  - Unavailable permissions shown as "—" with tooltips
- **Consistent List Pages**: All CRUD list pages follow standardized patterns:
  - Unified table structure with proper tbody IDs for AJAX loading
  - Checkbox selection columns for bulk operations (first column, 40px width)
  - Consistent action icons: Blue (#1890ff) for view/edit, Red (#ff4d4f) for delete
  - Right-aligned action columns with proper spacing (`me-2` margins)
  - SweetAlert2 confirmation dialogs for delete operations
  - Loading spinners and error handling during AJAX operations
  - Bulk action bars that appear/hide based on selection state
- **Quest Submission Pages**: Beautiful, consistent styling with:
  - Color-coded status badges with emojis (🟡 Pending, ✅ Approved, ❌ Denied)
  - Professional table headers with contextual icons
  - Grouped action buttons for pending submissions
  - Enhanced modals with proper color schemes and contextual messaging
  - Empty states with helpful messaging and icons

### Database Patterns
- Foreign key relationships with cascading deletes where appropriate (except benefits which are text-based)
- Status enums for workflow states (pending/approved/denied)
- Audit fields: created_by, created_at, updated_by, updated_at where applicable
- Soft deletes not used - hard deletes with referential integrity checks
- **RBAC Tables**: Roles, permissions, and user assignments with proper foreign key constraints
- **Permission Matrix Storage**: `role_permissions` table stores granular permissions (can_view, can_create, can_edit, can_delete, can_approve)
- **Text-Based Benefits**: Benefits stored as VARCHAR fields instead of foreign keys for flexibility
- **Main Quest Deletion**: Special handling for main quests with submissions - uses database transactions to first delete all related submissions before deleting the main quest, ensuring referential integrity
- **Benefit Validation**: When joining with benefits table, match on `benefit_type` text field rather than foreign key relationships

## UI/UX Design System

### Navigation & User Experience
- **Dynamic Profile Dropdown**: Modern header profile menu with user context
  - Avatar image with hover effects and responsive sizing
  - User information display (name, role) in dropdown header
  - "My Profile" and "Logout" options with Bootstrap Icons
  - SweetAlert2 logout confirmation with custom styling
  - Smooth animations and professional visual feedback
- **Smart Redirection System**: Role-based navigation after login
  - Permission-aware landing pages prevent access denied errors
  - Graceful fallbacks for limited-access users
  - Session-based redirect URL management

### Color Palette & Status Indicators
- **Pending Status**: Yellow theme (`#faad14`, `#fffbe6`, `#ffe58f`) with 🟡 emoji
- **Approved Status**: Green theme (`#52c41a`, `#f6ffed`, `#b7eb8f`) with ✅ emoji  
- **Denied Status**: Red theme (`#ff4d4f`, `#fff2f0`, `#ffccc7`) with ❌ emoji
- **Info/Primary**: Blue theme (`#1890ff`, `#e6f7ff`, `#91d5ff`)

### Benefit System Color Coding
- **Promotion**: Blue (`#1890ff`, `#f0f5ff`) with arrow-up-circle icon
- **Bonus**: Orange (`#fa8c16`, `#fff7e6`) with currency-dollar icon
- **Salary**: Green (`#52c41a`, `#f6ffed`) with cash-stack icon  
- **Leave**: Pink (`#eb2f96`, `#fff2f0`) with calendar-heart icon
- **WFA**: Purple (`#722ed1`, `#f9f0ff`) with house icon

### Component Standards
- **Tables**: Clean headers with contextual icons, proper padding (16px), subtle borders (`#f0f0f0`)
- **Badges**: Rounded (16px border-radius), proper padding (4px 12px), consistent font weight (500)
- **Buttons**: Grouped for related actions, consistent border-radius (6px), proper hover states
- **Modals**: Centered, modern shadows, rounded corners (8px), proper spacing (20px/24px padding)
- **Empty States**: Large icons (48px), helpful messaging, proper spacing (60px padding)

## Common Issues & Troubleshooting

### BaseController Integration Issues
- **"Class 'BaseController' not found" errors**: Controllers extending BaseController need proper include
  - Add `require_once APPPATH . 'core/BaseController.php';` before class declaration
  - Load required libraries in constructor: `$this->load->database();` and `$this->load->model('mymodel');`
  - Example affected controllers: Transaction, Product, Label, Discount

### Database Access Patterns
- **Mixed database access methods**: Controllers using both `$this->mymodel` and `$this->db` need proper setup
  - Ensure database library is loaded: `$this->load->database();` in constructor
  - Use `$this->db->escape_str()` for SQL injection prevention
  - Prefer `$this->mymodel->selectWithQuery()` for consistency

### View File Issues
- **Undefined array key errors**: Always use `isset()` when accessing $_GET/$_POST arrays
  - Change `$_GET['key'] ? $_GET['key'] : 'default'` to `isset($_GET['key']) ? $_GET['key'] : 'default'`
  - Use null coalescing operator: `$_GET['key'] ?? 'default'` (PHP 7.0+)
  - Example: `transaction/item.php` view file

### Navigation & Sidebar Issues
- **Module placement in sidebar**: Role Management and Modules & Permissions moved to Account (Akun) section
- **Permission-based visibility**: Sidebar items automatically hidden based on user view permissions
- **Dynamic navigation**: Menu structure updates based on RBAC permissions and user roles
- **Module categorization**: 6 main categories: System Management, HR Management, Marketing, Operations, Reports & Analytics, Account Management

### Database Migration Issues
- **"Unknown column 'benefit_id'" errors**: System migrated from foreign key to text-based benefits
  - Replace `LEFT JOIN benefits b ON table.benefit_id = b.id` with direct text field access
  - Use `table.benefit_type` instead of `b.type as benefit_type`
  - Match benefits by text: `WHERE benefit_type = '$benefit_text'` instead of `WHERE benefit_id = '$id'`

### Quest System Dependencies
- **Quest levels** must exist before creating positions (foreign key dependency)
- **Positions** must exist before creating user profiles (foreign key dependency)  
- **Benefits** are reference data only - actual benefit assignment uses text fields
- **Main quest deletions** require special handling due to submission relationships

### AJAX Loading Patterns
- All list pages use dedicated tbody elements (`#table-name-tbody`) for content loading
- Loading states must clear existing content with `.html()` not `.append()`
- Error handling should display user-friendly messages in table rows
- SweetAlert2 used for delete confirmations, not traditional modals
- Bulk selection requires `updateBulkActions()` JavaScript function for real-time UI updates

### Module Management Architecture
- **Hierarchical Structure**: Parent-child relationships with validation to prevent circular dependencies
- **Permission Integration**: Direct integration with RBAC system showing role permissions per module
- **Bulk Operations**: Mass deletion with smart validation (prevents deletion of modules with children or role dependencies)
- **Category Auto-Detection**: Automatic categorization based on module naming patterns
- **Icon Management**: Bootstrap Icons integration with live preview functionality

### Controller Autoload Issues (Critical)
- **PhpSpreadsheet Path Problems**: Controllers with `require 'vendor/autoload.php'` cause 500 server errors
  - **Affected Controllers**: Transaction.php, Transaction_item.php, Crm.php
  - **Root Cause**: Incorrect relative path from `application/controllers/` directory
  - **Symptoms**: Infinite loading on pages, 500 internal server errors in network tab
  - **Solution**: Comment out autoload and use statements for basic functionality:
    ```php
    // PhpSpreadsheet autoload commented out to prevent PHP version conflicts
    // Only load when specifically needed for Excel operations
    // require FCPATH . 'vendor/autoload.php';
    ```
  - **Note**: Excel operations require loading PhpSpreadsheet conditionally within specific methods
- **PHP Version Compatibility**: PhpSpreadsheet requires PHP 8.2+, but system may run PHP 8.1
- **BaseController vs CI_Controller**: Controllers extending BaseController may have additional failure points
  - **Working Pattern**: Stock.php extends CI_Controller directly - simpler and more reliable
  - **Complex Pattern**: Transaction.php extends BaseController - additional permission system overhead

### 403 Error Page Issues (Critical)
- **Problem**: Controllers using old `enforce_permission()` method cause redirect loops showing Chrome's default "Access denied" error
- **Solution**: All controllers extending BaseController automatically show beautiful custom 403 error page
- **BaseController Implementation**: Direct 403 error handling in middleware:
  ```php
  $this->output->set_status_header(403);
  $this->load->view('errors/html/error_403', $error_data);
  exit;
  ```
- **Custom 403 Features**: Professional error page with user context, action buttons, branding, and animations
- **Migration Pattern**: Change `class Controller extends CI_Controller` to `class Controller extends BaseController` and add proper includes

### Dynamic Permission Matrix Issues
- **Missing Module Permissions**: If a module shows default 5 permissions instead of dynamic ones, add module to `Roles::get_module_permissions()` array
- **Permission Validation Errors**: Server-side validation in `save_role_permissions()` prevents invalid permission assignments
- **JavaScript Check-All Issues**: The hierarchical check-all system works with dynamic columns, but may need state updates if module permissions change
- **View Permission Requirements**: All modules must have at least 'view' permission - modules without any permissions will default to view-only

### Quest Cancellation Issues  
- **Missing Cancel Buttons**: Ensure recent submissions query includes `submission_id` and proper status filtering
- **Permission Errors**: Cancellation methods validate user ownership and pending status before deletion
- **UI State Updates**: Cancel button only appears for pending submissions, disappears after status change
- **Database Referential Integrity**: Cancellation directly deletes submission records - ensure no foreign key constraints

### Authentication & Registration Issues
- **Foreign Key Constraints**: Self-registration handles NULL values for `assigned_by` and `created_by` fields
  - Guest role creation if no existing guest role found
  - Proper session cleanup after redirect URL usage
  - Email and username uniqueness validation with case-insensitive checking
- **Password Security**: Strong password validation with real-time feedback
  - Minimum 8 characters with uppercase, lowercase, numbers, and symbols
  - Client-side strength indicator and server-side validation
  - Automatic password hashing upgrade from MD5 to bcrypt on login
- **Smart Login Flow**: Permission-based redirection prevents access denied errors
  - Session-based redirect URL storage for security
  - Role-specific landing page determination
  - Fallback chain ensures users always reach accessible pages

### UI/UX Component Issues
- **Profile Dropdown**: Bootstrap 5 dropdown integration with custom styling
  - Proper ARIA labels and accessibility features
  - SweetAlert2 logout confirmation with custom CSS classes
  - Responsive design with mobile-friendly touch targets
- **Form Validation**: Real-time validation patterns for sign-up forms
  - Visual feedback with color-coded requirements checklist
  - Password strength indicator with dynamic progress bar
  - Submit button state management based on validation status

# important-instruction-reminders
Do what has been asked; nothing more, nothing less.
NEVER create files unless they're absolutely necessary for achieving your goal.
ALWAYS prefer editing an existing file to creating a new one.
NEVER proactively create documentation files (*.md) or README files. Only create documentation files if explicitly requested by the User.