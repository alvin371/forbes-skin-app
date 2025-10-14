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

$route['auth'] = 'auth';
$route['login'] = 'auth/login';
$route['signup'] = 'auth/signup';
$route['signup-process'] = 'auth/signup_process';

$route['default_controller'] = 'home/index';
$route['404_override'] = 'page/error';
$route['translate_uri_dashes'] = TRUE;

// NEW

$route['api/marketplace/callback/tiktok'] = 'Api_v2/marketplace_callback_tiktok';
$route['api/marketplace/callback/shopee'] = 'Api_v2/marketplace_callback_shopee';
$route['api/marketplace/callback/lazada'] = 'Api_v2/marketplace_callback_lazada';
$route['api/marketplace/token/refresh'] = 'Api_v2/marketplace_token_refresh';
$route['api/marketplace/order'] = 'Api_v2/marketplace_order';
$route['api/marketplace/order/detail'] = 'Api_v2/marketplace_order_detail';
$route['api/marketplace/product'] = 'Api_v2/marketplace_product';
$route['api/marketplace/webhook/refresh'] = 'Api_v2/marketplace_webhook_refresh';
$route['api/marketplace/webhook/reset'] = 'Api_v2/marketplace_webhook_reset';
$route['api/marketplace/order/tracking'] = 'Api_v2/marketplace_order_tracking';
$route['api/marketplace/order/download'] = 'Api_v2/marketplace_order_download';

$route['api/cronjob/endorse-campaign'] = 'Api_v2/cronjob_endorse_campaign';
$route['api/cronjob/endorse'] = 'Api_v2/cronjob_endorse';
$route['api/cronjob/influencer'] = 'Api_v2/cronjob_influencer';
$route['api/cronjob/influencer-dummy'] = 'Api_v2/cronjob_influencer_dummy';
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

