<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	https://codeigniter.com/userguide3/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/

$route['privacy-policy'] = 'PublicController/privacy_policy';
$route['support'] = 'PublicController/support';
$route['docs/hrms'] = 'Docs/hrms';
$route['docs/openapi/hrms.yaml'] = 'Docs/hrms_openapi';

$route['auth'] = 'auth';
$route['login'] = 'auth/login';
$route['signup'] = 'auth/signup';
$route['signup-process'] = 'auth/signup_process';

$route['admin/offices'] = 'admin/offices/index';
$route['admin/offices/create'] = 'admin/offices/create';
$route['admin/offices/(:num)/edit'] = 'admin/offices/edit/$1';
$route['admin/offices/(:num)'] = 'admin/offices/update/$1';
$route['admin/offices/(:num)/delete'] = 'admin/offices/delete/$1';
$route['admin/offices/(:num)/activate'] = 'admin/offices/activate/$1';
$route['admin/offices/(:num)/deactivate'] = 'admin/offices/deactivate/$1';

$route['admin/leave-types'] = 'admin/LeaveTypesController/index';
$route['admin/leave-types/create'] = 'admin/LeaveTypesController/create';
$route['admin/leave-types/(:num)/edit'] = 'admin/LeaveTypesController/edit/$1';
$route['admin/leave-types/(:num)'] = 'admin/LeaveTypesController/update/$1';
$route['admin/leave-types/(:num)/delete'] = 'admin/LeaveTypesController/delete/$1';

$route['admin/leave-requests'] = 'admin/LeaveRequestsController/index';
$route['admin/leave-requests/(:num)'] = 'admin/LeaveRequestsController/detail/$1';

$route['admin/leave-quotas'] = 'admin/LeaveQuotasController/index';
$route['admin/leave-quotas/users'] = 'admin/LeaveQuotasController/users';
$route['admin/leave-quotas/bulk-set'] = 'admin/LeaveQuotasController/bulk_set';
$route['admin/leave-quotas/bulk-update'] = 'admin/LeaveQuotasController/bulk_update';
$route['admin/leave-quotas/set-all'] = 'admin/LeaveQuotasController/set_all';
$route['admin/leave-quotas/manage/(:num)'] = 'admin/LeaveQuotasController/manage/$1';
$route['admin/leave-quotas/manage/(:num)/copy-from'] = 'admin/LeaveQuotasController/copy_from/$1';
$route['admin/leave-quotas/(:num)/delete'] = 'admin/LeaveQuotasController/delete/$1';

$route['admin/holidays'] = 'admin/HolidaysController/index';
$route['admin/holidays/create'] = 'admin/HolidaysController/create';
$route['admin/holidays/(:num)/edit'] = 'admin/HolidaysController/edit/$1';
$route['admin/holidays/(:num)'] = 'admin/HolidaysController/update/$1';
$route['admin/holidays/(:num)/delete'] = 'admin/HolidaysController/delete/$1';

$route['admin/attendance-settings'] = 'admin/AttendanceSettingsController/index';

$route['admin/performance-appraisal'] = 'admin/PerformanceAppraisal/index';
$route['admin/performance-appraisal/create'] = 'admin/PerformanceAppraisal/create';
$route['admin/performance-appraisal/(:num)/edit'] = 'admin/PerformanceAppraisal/edit/$1';
$route['admin/performance-appraisal/(:num)'] = 'admin/PerformanceAppraisal/update/$1';
$route['admin/performance-appraisal/(:num)/delete'] = 'admin/PerformanceAppraisal/delete/$1';
$route['admin/performance-appraisal/(:num)/items'] = 'admin/PerformanceAppraisal/item_store/$1';
$route['admin/performance-appraisal/items/(:num)'] = 'admin/PerformanceAppraisal/item_update/$1';
$route['admin/performance-appraisal/items/(:num)/delete'] = 'admin/PerformanceAppraisal/item_delete/$1';
$route['admin/performance-appraisal/(:num)/items/reorder'] = 'admin/PerformanceAppraisal/items_reorder/$1';
$route['admin/performance-appraisal/submissions'] = 'admin/PerformanceAppraisal/submissions';
$route['admin/performance-appraisal/submissions/(:num)'] = 'admin/PerformanceAppraisal/submission_detail/$1';

$route['leave'] = 'LeaveController/index';
$route['leave/create'] = 'LeaveController/create';
$route['leave/(:num)'] = 'LeaveController/detail/$1';
$route['leave/(:num)/cancel'] = 'LeaveController/cancel/$1';
$route['leave/(:num)/submit'] = 'LeaveController/submit/$1';
$route['leave/(:num)/progress'] = 'LeaveController/progress/$1';
$route['leave/quota'] = 'LeaveController/quota';
$route['overtime'] = 'Overtime/index';
$route['overtime/create'] = 'Overtime/create';
$route['overtime/store'] = 'Overtime/store';
$route['overtime/(:num)'] = 'Overtime/detail/$1';
$route['overtime/(:num)/cancel'] = 'Overtime/cancel/$1';

$route['approvals/leaves'] = 'approvals/LeaveApprovalController/index';
$route['approvals/leaves/(:num)'] = 'approvals/LeaveApprovalController/detail/$1';
$route['approvals/leaves/(:num)/approve'] = 'approvals/LeaveApprovalController/approve/$1';
$route['approvals/leaves/(:num)/reject'] = 'approvals/LeaveApprovalController/reject/$1';

// Dynamic Approval Routes - Inbox
$route['approvals/inbox'] = 'approvals/ApprovalInboxController/index';
$route['approvals/inbox/detail/(:num)'] = 'approvals/ApprovalInboxController/detail/$1';
$route['approvals/inbox/approve/(:num)'] = 'approvals/ApprovalInboxController/approve/$1';
$route['approvals/inbox/reject/(:num)'] = 'approvals/ApprovalInboxController/reject/$1';
$route['approvals/inbox/history'] = 'approvals/ApprovalInboxController/history';
$route['approvals/inbox/needs-route'] = 'approvals/ApprovalInboxController/needs_route';
$route['approvals/inbox/assign-route'] = 'approvals/ApprovalInboxController/assign_route';
$route['approvals/inbox/quick-approve'] = 'approvals/ApprovalInboxController/quick_approve';
$route['approvals/inbox/quick-reject'] = 'approvals/ApprovalInboxController/quick_reject';
$route['approvals/inbox/pending-count'] = 'approvals/ApprovalInboxController/pending_count';

// Overtime Approval Routes - Inbox
$route['approvals/overtime'] = 'approvals/OvertimeApprovalController/index';
$route['approvals/overtime/detail/(:num)'] = 'approvals/OvertimeApprovalController/detail/$1';
$route['approvals/overtime/approve/(:num)'] = 'approvals/OvertimeApprovalController/approve/$1';
$route['approvals/overtime/reject/(:num)'] = 'approvals/OvertimeApprovalController/reject/$1';
$route['approvals/overtime/history'] = 'approvals/OvertimeApprovalController/history';
$route['approvals/overtime/quick-approve'] = 'approvals/OvertimeApprovalController/quick_approve';
$route['approvals/overtime/quick-reject'] = 'approvals/OvertimeApprovalController/quick_reject';
$route['approvals/overtime/pending-count'] = 'approvals/OvertimeApprovalController/pending_count';

// Admin Approval Routes Management
$route['admin/approval-routes'] = 'admin/ApprovalRoutesController/index';
$route['admin/approval-routes/create'] = 'admin/ApprovalRoutesController/create';
$route['admin/approval-routes/store'] = 'admin/ApprovalRoutesController/store';
$route['admin/approval-routes/(:num)/edit'] = 'admin/ApprovalRoutesController/edit/$1';
$route['admin/approval-routes/(:num)/update'] = 'admin/ApprovalRoutesController/update/$1';
$route['admin/approval-routes/(:num)/deactivate'] = 'admin/ApprovalRoutesController/deactivate/$1';
$route['admin/approval-routes/(:num)/versions'] = 'admin/ApprovalRoutesController/versions/$1';
$route['admin/approval-routes/detail/(:num)'] = 'admin/ApprovalRoutesController/detail/$1';
$route['admin/approval-routes/preview'] = 'admin/ApprovalRoutesController/preview';
$route['admin/approval-routes/bulk'] = 'admin/ApprovalRoutesController/bulk_create';
$route['admin/approval-routes/bulk-store'] = 'admin/ApprovalRoutesController/bulk_store';
$route['admin/overtime-approval-routes'] = 'admin/OvertimeApprovalRoutesController/index';
$route['admin/overtime-approval-routes/create'] = 'admin/OvertimeApprovalRoutesController/create';
$route['admin/overtime-approval-routes/store'] = 'admin/OvertimeApprovalRoutesController/store';
$route['admin/overtime-approval-routes/(:num)/edit'] = 'admin/OvertimeApprovalRoutesController/edit/$1';
$route['admin/overtime-approval-routes/(:num)/update'] = 'admin/OvertimeApprovalRoutesController/update/$1';
$route['admin/overtime-approval-routes/(:num)/deactivate'] = 'admin/OvertimeApprovalRoutesController/deactivate/$1';
$route['admin/overtime-approval-routes/(:num)/activate'] = 'admin/OvertimeApprovalRoutesController/activate/$1';
$route['admin/overtime-approval-routes/(:num)/delete'] = 'admin/OvertimeApprovalRoutesController/delete/$1';

$route['diagnostic/check-leave-data'] = 'DiagnosticController/check_leave_data';
$route['fix/leave-status'] = 'FixLeaveStatusController/update_status';
$route['migrate/leave-status'] = 'MigrateLeaveStatusController/submitted_to_pending';

$route['seed/run'] = 'SeedRunner/index';
$route['seed/attendance-office'] = 'SeedRunner/attendance_office';
$route['seed/leave-types'] = 'SeedRunner/leave_types';
$route['seed/holidays'] = 'SeedRunner/holidays';
$route['seed/performance-2026'] = 'SeedRunner/performance_2026';
$route['seed/approval-modules'] = 'SeedRunner/approval_modules';
$route['seed/approval-routes'] = 'SeedRunner/approval_routes';
$route['migrate/latest'] = 'MigrationRunner/latest';
$route['schema/hr'] = 'SchemaBootstrap/hr';
$route['schema/hrms-api'] = 'SchemaBootstrap/hrms_api';

$route['default_controller'] = 'home/index';
$route['404_override'] = 'page/error';
$route['translate_uri_dashes'] = TRUE;

// NEW

$route['api/marketplace/callback/tiktok'] = 'Api_v2/marketplace_callback_tiktok';
$route['api/marketplace/callback/shopee'] = 'Api_v2/marketplace_callback_shopee';
$route['api/marketplace/callback/lazada'] = 'Api_v2/marketplace_callback_lazada';
$route['api/marketplace/token/refresh'] = 'Api_v2/marketplace_token_refresh';
$route['api/marketplace/config'] = 'Api_v2/marketplace_config';
$route['api/marketplace/order'] = 'Api_v2/marketplace_order';
$route['api/marketplace/order/ingest'] = 'Api_v2/marketplace_order_ingest';
$route['api/marketplace/order/detail'] = 'Api_v2/marketplace_order_detail';
$route['api/marketplace/product'] = 'Api_v2/marketplace_product';
$route['api/marketplace/product/ingest'] = 'Api_v2/marketplace_product_ingest';
$route['api/marketplace/webhook/refresh'] = 'Api_v2/marketplace_webhook_refresh';
$route['api/marketplace/webhook/reset'] = 'Api_v2/marketplace_webhook_reset';
$route['api/marketplace/order/tracking'] = 'Api_v2/marketplace_order_tracking';
$route['api/marketplace/order/download'] = 'Api_v2/marketplace_order_download';

$route['api/hrms/auth/login'] = 'Api_hrms/auth_login';
$route['api/hrms/auth/refresh'] = 'Api_hrms/auth_refresh';
$route['api/hrms/profile'] = 'Api_hrms/profile';
$route['api/hrms/pin/setup'] = 'Api_hrms/pin_setup';
$route['api/hrms/pin/verify'] = 'Api_hrms/pin_verify';
$route['api/hrms/pin/reset'] = 'Api_hrms/pin_reset';
$route['api/hrms/config'] = 'Api_hrms/config';
$route['api/hrms/attendance/office-proof'] = 'Api_hrms/attendance_office_proof';
$route['api/hrms/attendance/check-in'] = 'Api_hrms/attendance_check_in';
$route['api/hrms/attendance/check-out'] = 'Api_hrms/attendance_check_out';
$route['api/hrms/attendance/history'] = 'Api_hrms/attendance_history';
$route['api/hrms/attendance/recap'] = 'Api_hrms/attendance_recap';
$route['api/hrms/attendance/recap-all'] = 'Api_hrms/attendance_recap_all';
$route['api/hrms/attendance/report'] = 'Api_hrms/attendance_report';
$route['api/hrms/holidays'] = 'Api_hrms/holidays';
$route['api/hrms/upload'] = 'Api_hrms/upload';
$route['api/hrms/leave'] = 'Api_hrms/leave';
$route['api/hrms/leave/(:num)'] = 'Api_hrms/leave_detail/$1';
$route['api/hrms/leave/(:num)/cancel'] = 'Api_hrms/leave_cancel/$1';
$route['api/hrms/leave/(:num)/submit'] = 'Api_hrms/leave_submit/$1';
$route['api/hrms/leave/(:num)/progress'] = 'Api_hrms/leave_progress/$1';
$route['api/hrms/leave/quota'] = 'Api_hrms/leave_quota';
$route['api/hrms/leave/quota/detail'] = 'Api_hrms/leave_quota_detail';
$route['api/hrms/approvals/inbox'] = 'Api_hrms/approvals_inbox';
$route['api/hrms/approvals/inbox/count'] = 'Api_hrms/approvals_inbox_count';
$route['api/hrms/approvals/(:num)/approve'] = 'Api_hrms/approval_approve/$1';
$route['api/hrms/approvals/(:num)/reject'] = 'Api_hrms/approval_reject/$1';
$route['api/hrms/approvals/history'] = 'Api_hrms/approvals_history';
$route['api/hrms/performance/templates/active'] = 'Api_hrms/performance_templates_active';
$route['api/hrms/performance/submissions'] = 'Api_hrms/performance_submissions';
$route['api/hrms/performance/submissions/(:num)'] = 'Api_hrms/performance_submission_detail/$1';
$route['api/hrms/performance/submissions/(:num)/cancel'] = 'Api_hrms/performance_submission_cancel/$1';

// Overtime API Routes
$route['api/hrms/overtime/types'] = 'Api_hrms/overtime_types';
$route['api/hrms/overtime'] = 'Api_hrms/overtime';
$route['api/hrms/overtime/summary'] = 'Api_hrms/overtime_summary';
$route['api/hrms/overtime/(:num)'] = 'Api_hrms/overtime_detail/$1';
$route['api/hrms/overtime/(:num)/cancel'] = 'Api_hrms/overtime_cancel/$1';
$route['api/hrms/overtime/approvals/inbox'] = 'Api_hrms/overtime_approvals_inbox';
$route['api/hrms/overtime/approvals/inbox/count'] = 'Api_hrms/overtime_approvals_inbox_count';
$route['api/hrms/overtime/approvals/(:num)/approve'] = 'Api_hrms/overtime_approval_approve/$1';
$route['api/hrms/overtime/approvals/(:num)/reject'] = 'Api_hrms/overtime_approval_reject/$1';
$route['api/hrms/overtime/approvals/history'] = 'Api_hrms/overtime_approvals_history';

$route['api/attendance/confirm'] = 'AttendanceController/confirm';
$route['api/attendance/status'] = 'AttendanceController/status';
$route['api/attendance/logs'] = 'AttendanceController/logs';
$route['attendance'] = 'AttendancePageController/index';
$route['attendance/report'] = 'AttendanceReport/index';
$route['attendance/report/pdf'] = 'AttendanceReport/export_pdf';
$route['attendance/report/set-schedule']['POST'] = 'AttendanceReport/set_user_schedule';

$route['admin/performance/roles']['get'] = 'Api_performance/roles';
$route['admin/performance/templates']['get'] = 'Api_performance/templates';
$route['admin/performance/templates']['post'] = 'Api_performance/template_create';
$route['admin/performance/templates/(:num)']['get'] = 'Api_performance/template/$1';
$route['admin/performance/templates/(:num)']['put'] = 'Api_performance/template_update/$1';
$route['admin/performance/templates/(:num)']['delete'] = 'Api_performance/template_delete/$1';
$route['admin/performance/templates/(:num)/items']['post'] = 'Api_performance/item_create/$1';
$route['admin/performance/items/(:num)']['put'] = 'Api_performance/item_update/$1';
$route['admin/performance/items/(:num)']['delete'] = 'Api_performance/item_delete/$1';
$route['admin/performance/templates/(:num)/items/reorder']['post'] = 'Api_performance/items_reorder/$1';
$route['admin/performance/submissions']['get'] = 'Api_performance/submissions';

$route['performance/templates/active']['get'] = 'Api_performance/templates_active';
$route['performance/submissions']['post'] = 'Api_performance/submission_create';
$route['performance/submissions/me']['get'] = 'Api_performance/submissions_me';
$route['performance/submissions/(:num)']['get'] = 'Api_performance/submission/$1';

$route['api/cronjob/endorse-campaign'] = 'Api_v2/cronjob_endorse_campaign';
$route['api/cronjob/endorse'] = 'Api_v2/cronjob_endorse';
$route['api/cronjob/endorse-sync-campaign'] = 'Api_v2/cronjob_endorse_by_campaign';
$route['ajax/refresh-campaign-endorses'] = 'Ajax/refresh_campaign_endorses';
$route['api/cronjob/influencer'] = 'Api_v2/cronjob_influencer';
$route['api/cronjob/influencer-dummy'] = 'Api_v2/cronjob_influencer_dummy';
$route['api/cronjob/scraping-submit'] = 'Api_v2/cronjob_scraping_submit';
$route['api/cronjob/scraping-poll'] = 'Api_v2/cronjob_scraping_poll';
$route['api/cronjob/scraping-enqueue'] = 'Api_v2/cronjob_scraping_enqueue';
$route['api/cronjob/tiktok-sync'] = 'Api_v2/cronjob_tiktok_sync';
$route['cronjob/update-customer'] = 'Api/cronjob_update_customer';

$route['api/webhook'] = 'Api_v2/webhook';
$route['api/customer/summary'] = 'Api_v2/customer_summary';
$route['cronjob/expense'] = 'Expense/generate_recurring_expense';

$route['endorse/action_generate_mou_pdf_gdocs'] = 'googlemou/action_generate_mou_pdf'; 
$route['googlemou/oauth2callback']              = 'googlemou/oauth2callback';
$route['googlemou']                             = 'googlemou/index';


// OLD

$route['profile'] = 'Profile/index';
$route['profile/update-process'] = 'Profile/update_process';
$route['profile/quest-history'] = 'Profile/quest_history';
$route['profile/apply-main-quest'] = 'Profile/apply_main_quest';
$route['profile/apply-side-quest'] = 'Profile/apply_side_quest';

$route['api'] = 'Api/index';
$route['api/refresh-order'] = 'Api/refresh_order';
$route['api/refresh-customer'] = 'Api/refresh_customer';
$route['api/reset-webhook'] = 'Api/reset_webhook';
$route['api/get-order'] = 'Api/get_order';
$route['api/get-order-detail'] = 'Api/get_order_detail';
$route['api/update-order'] = 'Api/update_order';

$route['api/cronjob-order'] = 'Api/cronjob_order';
$route['api/cronjob-finance'] = 'Api/cronjob_finance';
$route['api/cronjob-endorse-campaign'] = 'Api/cronjob_endorse_campaign';
$route['api/cronjob-endorse'] = 'Api/cronjob_endorse';
$route['api/cronjob-influencer'] = 'Api/cronjob_influencer';

$route['api/auth/shopee'] = 'Api/auth_shopee';
$route['api/auth/tiktok'] = 'Api/auth_tiktok';
$route['api/auth/lazada'] = 'Api/auth_lazada';

$route['api/auth/marketplace/shopee'] = 'Api/auth_marketplace_shopee';
$route['api/auth/refresh-token/shopee'] = 'Api/shopee_refresh_token';

$route['api/auth/marketplace/lazada'] = 'Api/auth_marketplace_lazada';
$route['api/auth/refresh-token/lazada'] = 'Api/lazada_refresh_token';

$route['api/auth/marketplace/tiktok'] = 'Api/auth_marketplace_tiktok';
$route['api/auth/refresh-token/tiktok'] = 'Api/tiktok_refresh_token';

$route['api/shopee/get-product'] = 'Api/shopee_get_product';
$route['api/shopee/get-order'] = 'Api/shopee_get_order';
$route['api/shopee/get-finance'] = 'Api/shopee_get_finance';

$route['api/lazada/get-product'] = 'Api/lazada_get_product';
$route['api/lazada/get-order'] = 'Api/lazada_get_order';
$route['api/lazada/get-finance'] = 'Api/lazada_get_finance';

$route['api/tiktok/get-product'] = 'Api/tiktok_get_product';
$route['api/tiktok/get-order'] = 'Api/tiktok_get_order';
$route['api/tiktok/get-finance'] = 'Api/tiktok_get_finance';

// Bulk Cetak Resi & Scan Ready To Ship
$route['transaction/cetak-resi'] = 'Transaction/cetak_resi';
$route['transaction/cetak-resi/preview'] = 'Transaction/cetak_resi_preview';
$route['transaction/scan-ready-to-ship'] = 'Transaction/scan_ready_to_ship';
$route['transaction/scan-ready-to-ship/submit'] = 'Transaction/scan_ready_to_ship_submit';

// $route['api/webhook'] = 'Api/webhook';
$route['api/webhook-api'] = 'Api/webhook_api';
$route['api/webhook-test'] = 'Api/webhook_test';

// v3
$route['api/marketplace/ads'] = 'Api_v3/marketplace_ads';
$route['api/tiktok/campaign'] = 'Api_v3/get_tiktok_campaign';
$route['api/tiktok/gmv'] = 'Api_v3/get_tiktok_gmv';
$route['auth/redirect'] = 'TiktokAuth/redirect_to_auth';
$route['auth/callback'] = 'TiktokAuth/callback';        
$route['cronjob/expense'] = 'Api_v3/generate_recurring_expense';
$route['cronjob/sync-product'] = 'Api_v3/sync_all_product';
