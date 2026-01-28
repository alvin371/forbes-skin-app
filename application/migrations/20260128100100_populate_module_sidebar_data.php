<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Migration: Populate module sidebar data
 *
 * Populates existing modules with category, url, available_permissions, and show_in_sidebar
 * values based on the previously hardcoded configuration in Roles.php
 */
class Migration_Populate_module_sidebar_data extends CI_Migration
{
    /**
     * Module configuration mapping
     * Format: module_name => [category, url, permissions[], show_in_sidebar]
     */
    private function get_module_config()
    {
        return [
            // =========================================================================
            // SYSTEM MANAGEMENT MODULES
            // =========================================================================
            'dashboard' => [
                'category' => 'System Management',
                'url' => 'dashboard',
                'permissions' => ['view'],
                'show_in_sidebar' => 1
            ],
            'profile' => [
                'category' => 'System Management',
                'url' => 'profile',
                'permissions' => ['view'],
                'show_in_sidebar' => 1
            ],
            'modules' => [
                'category' => 'System Management',
                'url' => 'modules',
                'permissions' => ['view', 'create', 'edit', 'delete'],
                'show_in_sidebar' => 1
            ],
            'roles' => [
                'category' => 'System Management',
                'url' => 'roles',
                'permissions' => ['view', 'create', 'edit', 'delete'],
                'show_in_sidebar' => 1
            ],
            'user' => [
                'category' => 'System Management',
                'url' => 'user',
                'permissions' => ['view', 'create', 'edit', 'delete'],
                'show_in_sidebar' => 1
            ],
            'home' => [
                'category' => 'System Management',
                'url' => null,
                'permissions' => ['view'],
                'show_in_sidebar' => 0
            ],
            'auth' => [
                'category' => 'System Management',
                'url' => null,
                'permissions' => ['view'],
                'show_in_sidebar' => 0
            ],

            // Dashboard Cards (hidden from sidebar)
            'dashboard_card_jumlah_order' => [
                'category' => 'System Management',
                'url' => null,
                'permissions' => ['view'],
                'show_in_sidebar' => 0
            ],
            'dashboard_card_order_belum_proses' => [
                'category' => 'System Management',
                'url' => null,
                'permissions' => ['view'],
                'show_in_sidebar' => 0
            ],
            'dashboard_card_order_belum_cairkan' => [
                'category' => 'System Management',
                'url' => null,
                'permissions' => ['view'],
                'show_in_sidebar' => 0
            ],
            'dashboard_card_belum_cairkan' => [
                'category' => 'System Management',
                'url' => null,
                'permissions' => ['view'],
                'show_in_sidebar' => 0
            ],
            'dashboard_card_penjualan_kotor' => [
                'category' => 'System Management',
                'url' => null,
                'permissions' => ['view'],
                'show_in_sidebar' => 0
            ],
            'dashboard_card_diskon' => [
                'category' => 'System Management',
                'url' => null,
                'permissions' => ['view'],
                'show_in_sidebar' => 0
            ],
            'dashboard_card_penjualan_bersih' => [
                'category' => 'System Management',
                'url' => null,
                'permissions' => ['view'],
                'show_in_sidebar' => 0
            ],
            'dashboard_card_laba_bersih' => [
                'category' => 'System Management',
                'url' => null,
                'permissions' => ['view'],
                'show_in_sidebar' => 0
            ],
            'dashboard_card_marketplace_fee' => [
                'category' => 'System Management',
                'url' => null,
                'permissions' => ['view'],
                'show_in_sidebar' => 0
            ],
            'dashboard_card_pengeluaran' => [
                'category' => 'System Management',
                'url' => null,
                'permissions' => ['view'],
                'show_in_sidebar' => 0
            ],
            'dashboard_card_hpp_produk' => [
                'category' => 'System Management',
                'url' => null,
                'permissions' => ['view'],
                'show_in_sidebar' => 0
            ],
            'dashboard_card_order_return' => [
                'category' => 'System Management',
                'url' => null,
                'permissions' => ['view'],
                'show_in_sidebar' => 0
            ],
            'dashboard_card_nilai_produk' => [
                'category' => 'System Management',
                'url' => null,
                'permissions' => ['view'],
                'show_in_sidebar' => 0
            ],
            'dashboard_card_ongkir' => [
                'category' => 'System Management',
                'url' => null,
                'permissions' => ['view'],
                'show_in_sidebar' => 0
            ],
            'dashboard_card_penjualan_return' => [
                'category' => 'System Management',
                'url' => null,
                'permissions' => ['view'],
                'show_in_sidebar' => 0
            ],

            // =========================================================================
            // HR MANAGEMENT MODULES
            // =========================================================================
            'hr_management' => [
                'category' => 'HR Management',
                'url' => null,
                'permissions' => ['view'],
                'show_in_sidebar' => 0
            ],
            'attendance' => [
                'category' => 'HR Management',
                'url' => 'attendance',
                'permissions' => ['view', 'create', 'edit', 'delete'],
                'show_in_sidebar' => 1
            ],
            'leave' => [
                'category' => 'HR Management',
                'url' => 'leave',
                'permissions' => ['view', 'create', 'edit', 'delete'],
                'show_in_sidebar' => 1
            ],
            'leave_approvals' => [
                'category' => 'HR Management',
                'url' => 'approvals/leaves',
                'permissions' => ['view', 'approve'],
                'show_in_sidebar' => 1
            ],
            'leave_types' => [
                'category' => 'HR Management',
                'url' => 'admin/leave-types',
                'permissions' => ['view', 'create', 'edit', 'delete'],
                'show_in_sidebar' => 1
            ],
            'leave_quotas' => [
                'category' => 'HR Management',
                'url' => 'admin/leave-quotas',
                'permissions' => ['view', 'create', 'edit', 'delete'],
                'show_in_sidebar' => 1
            ],
            'approval_routes' => [
                'category' => 'HR Management',
                'url' => 'admin/approval-routes',
                'permissions' => ['view', 'create', 'edit', 'delete'],
                'show_in_sidebar' => 0
            ],
            'holidays' => [
                'category' => 'HR Management',
                'url' => 'admin/holidays',
                'permissions' => ['view', 'create', 'edit', 'delete'],
                'show_in_sidebar' => 1
            ],
            'attendance_settings' => [
                'category' => 'HR Management',
                'url' => 'admin/attendance-settings',
                'permissions' => ['view', 'create', 'edit', 'delete'],
                'show_in_sidebar' => 1
            ],
            'offices' => [
                'category' => 'HR Management',
                'url' => 'admin/offices',
                'permissions' => ['view', 'create', 'edit', 'delete'],
                'show_in_sidebar' => 1
            ],
            'performance_admin' => [
                'category' => 'HR Management',
                'url' => 'admin/performance-appraisal',
                'permissions' => ['view', 'create', 'edit', 'delete'],
                'show_in_sidebar' => 1
            ],
            'quest' => [
                'category' => 'HR Management',
                'url' => 'quest',
                'permissions' => ['view', 'create', 'edit', 'delete'],
                'show_in_sidebar' => 1
            ],
            'quest_level' => [
                'category' => 'HR Management',
                'url' => 'quest_level',
                'permissions' => ['view', 'create', 'edit', 'delete'],
                'show_in_sidebar' => 1
            ],
            'position' => [
                'category' => 'HR Management',
                'url' => 'position',
                'permissions' => ['view', 'create', 'edit', 'delete'],
                'show_in_sidebar' => 1
            ],
            'benefit' => [
                'category' => 'HR Management',
                'url' => 'benefit',
                'permissions' => ['view', 'create', 'edit', 'delete'],
                'show_in_sidebar' => 1
            ],
            'milestone' => [
                'category' => 'HR Management',
                'url' => 'milestone',
                'permissions' => ['view', 'create', 'edit', 'delete'],
                'show_in_sidebar' => 1
            ],
            'recruitment' => [
                'category' => 'HR Management',
                'url' => 'recruitment',
                'permissions' => ['view', 'approve'],
                'show_in_sidebar' => 1
            ],
            'interview' => [
                'category' => 'HR Management',
                'url' => 'interview',
                'permissions' => ['view', 'approve'],
                'show_in_sidebar' => 0
            ],

            // =========================================================================
            // MARKETING MODULES
            // =========================================================================
            'marketing' => [
                'category' => 'Marketing',
                'url' => 'overview',
                'permissions' => ['view'],
                'show_in_sidebar' => 1
            ],
            'influencer' => [
                'category' => 'Marketing',
                'url' => 'influencer',
                'permissions' => ['view', 'create', 'edit', 'delete'],
                'show_in_sidebar' => 1
            ],
            'influencer_dummy' => [
                'category' => 'Marketing',
                'url' => 'influencer-dummy',
                'permissions' => ['view', 'create', 'edit', 'delete'],
                'show_in_sidebar' => 1
            ],
            'endorse' => [
                'category' => 'Marketing',
                'url' => 'endorse',
                'permissions' => ['view', 'create', 'edit', 'delete'],
                'show_in_sidebar' => 0
            ],
            'endorse_campaign' => [
                'category' => 'Marketing',
                'url' => 'endorse-campaign',
                'permissions' => ['view', 'create', 'edit', 'delete'],
                'show_in_sidebar' => 1
            ],
            'review_endorse' => [
                'category' => 'Marketing',
                'url' => 'review-endorse',
                'permissions' => ['view', 'approve'],
                'show_in_sidebar' => 0
            ],
            'payment' => [
                'category' => 'Marketing',
                'url' => 'payment',
                'permissions' => ['view', 'create', 'edit', 'delete'],
                'show_in_sidebar' => 1
            ],
            'calendar' => [
                'category' => 'Marketing',
                'url' => 'calendar?group_by[]=rencana_at&group_by[]=posting_at',
                'permissions' => ['view'],
                'show_in_sidebar' => 1
            ],
            'ads' => [
                'category' => 'Marketing',
                'url' => null,
                'permissions' => ['view'],
                'show_in_sidebar' => 0
            ],
            'ads_tiktok' => [
                'category' => 'Marketing',
                'url' => 'ads?m=tiktok',
                'permissions' => ['view'],
                'show_in_sidebar' => 1
            ],
            'ads_meta' => [
                'category' => 'Marketing',
                'url' => 'ads?m=meta',
                'permissions' => ['view'],
                'show_in_sidebar' => 1
            ],
            'ads_shopee' => [
                'category' => 'Marketing',
                'url' => 'ads?m=shopee',
                'permissions' => ['view'],
                'show_in_sidebar' => 1
            ],
            'ads_lazada' => [
                'category' => 'Marketing',
                'url' => 'ads?m=lazada',
                'permissions' => ['view'],
                'show_in_sidebar' => 1
            ],
            'advertiser' => [
                'category' => 'Marketing',
                'url' => null,
                'permissions' => ['view'],
                'show_in_sidebar' => 0
            ],
            'marketplace_account' => [
                'category' => 'Marketing',
                'url' => 'marketplace-account',
                'permissions' => ['view', 'create', 'edit', 'delete'],
                'show_in_sidebar' => 0
            ],
            'meta_account' => [
                'category' => 'Marketing',
                'url' => 'meta-account',
                'permissions' => ['view', 'create', 'edit', 'delete'],
                'show_in_sidebar' => 0
            ],
            'codeboost' => [
                'category' => 'Marketing',
                'url' => 'codeboost',
                'permissions' => ['view', 'create', 'edit', 'delete'],
                'show_in_sidebar' => 1
            ],

            // =========================================================================
            // OPERATIONS MODULES (Order & Customer)
            // =========================================================================
            'transaction' => [
                'category' => 'Operations',
                'url' => 'transaction',
                'permissions' => ['view', 'create', 'edit', 'delete'],
                'show_in_sidebar' => 1
            ],
            'transaction_item' => [
                'category' => 'Operations',
                'url' => 'transaction-item',
                'permissions' => ['view', 'create', 'edit', 'delete'],
                'show_in_sidebar' => 1
            ],
            'order_customer' => [
                'category' => 'Operations',
                'url' => null,
                'permissions' => ['view', 'create', 'edit', 'delete'],
                'show_in_sidebar' => 0
            ],
            'crm' => [
                'category' => 'Operations',
                'url' => null,
                'permissions' => ['view', 'create', 'edit', 'delete'],
                'show_in_sidebar' => 0
            ],
            'crm_mg' => [
                'category' => 'Operations',
                'url' => 'crm?brand=MG',
                'permissions' => ['view', 'create', 'edit', 'delete'],
                'show_in_sidebar' => 1
            ],
            'crm_pome' => [
                'category' => 'Operations',
                'url' => 'crm?brand=POME',
                'permissions' => ['view', 'create', 'edit', 'delete'],
                'show_in_sidebar' => 1
            ],
            'customer' => [
                'category' => 'Operations',
                'url' => 'customer',
                'permissions' => ['view', 'create', 'edit', 'delete'],
                'show_in_sidebar' => 0
            ],
            'group_wa' => [
                'category' => 'Operations',
                'url' => 'group-wa',
                'permissions' => ['view', 'create', 'edit', 'delete'],
                'show_in_sidebar' => 1
            ],
            'product' => [
                'category' => 'Operations',
                'url' => 'product',
                'permissions' => ['view', 'create', 'edit', 'delete'],
                'show_in_sidebar' => 1
            ],
            'product_3rd' => [
                'category' => 'Operations',
                'url' => 'product-3rd',
                'permissions' => ['view', 'create', 'edit', 'delete'],
                'show_in_sidebar' => 0
            ],
            'stock' => [
                'category' => 'Operations',
                'url' => 'stock',
                'permissions' => ['view', 'create', 'edit', 'delete'],
                'show_in_sidebar' => 1
            ],
            'marketplace' => [
                'category' => 'Operations',
                'url' => 'marketplace',
                'permissions' => ['view', 'create', 'edit', 'delete'],
                'show_in_sidebar' => 0
            ],
            'shipping' => [
                'category' => 'Operations',
                'url' => 'shipping',
                'permissions' => ['view', 'create', 'edit', 'delete'],
                'show_in_sidebar' => 0
            ],
            'discount' => [
                'category' => 'Operations',
                'url' => 'discount',
                'permissions' => ['view', 'create', 'edit', 'delete'],
                'show_in_sidebar' => 0
            ],
            'label' => [
                'category' => 'Operations',
                'url' => 'label',
                'permissions' => ['view', 'create', 'edit', 'delete'],
                'show_in_sidebar' => 0
            ],
            'testimoni' => [
                'category' => 'Operations',
                'url' => 'testimoni',
                'permissions' => ['view', 'create', 'edit', 'delete'],
                'show_in_sidebar' => 0
            ],
            'admin_fee_configuration' => [
                'category' => 'Operations',
                'url' => 'admin-fee-configuration',
                'permissions' => ['view', 'create', 'edit', 'delete'],
                'show_in_sidebar' => 0
            ],
            'operasional' => [
                'category' => 'Operations',
                'url' => null,
                'permissions' => ['view', 'create', 'edit', 'delete'],
                'show_in_sidebar' => 0
            ],

            // =========================================================================
            // REPORTS & ANALYTICS MODULES
            // =========================================================================
            'report' => [
                'category' => 'Reports & Analytics',
                'url' => 'report',
                'permissions' => ['view'],
                'show_in_sidebar' => 1
            ],
            'expense' => [
                'category' => 'Reports & Analytics',
                'url' => 'expense',
                'permissions' => ['view', 'create', 'edit', 'delete'],
                'show_in_sidebar' => 1
            ],
            'notifications' => [
                'category' => 'Reports & Analytics',
                'url' => 'notifications',
                'permissions' => ['view'],
                'show_in_sidebar' => 0
            ],
            'scraper' => [
                'category' => 'Reports & Analytics',
                'url' => 'scraper',
                'permissions' => ['view', 'create', 'edit', 'delete'],
                'show_in_sidebar' => 0
            ],

            // =========================================================================
            // GOOGLE INTEGRATION MODULES
            // =========================================================================
            'googlemeet' => [
                'category' => 'Google Integration',
                'url' => 'googlemeet',
                'permissions' => ['view', 'create'],
                'show_in_sidebar' => 0
            ],
            'googlemou' => [
                'category' => 'Google Integration',
                'url' => 'googlemou',
                'permissions' => ['view', 'create'],
                'show_in_sidebar' => 0
            ],

            // =========================================================================
            // API MODULES (Hidden from sidebar)
            // =========================================================================
            'api' => [
                'category' => 'API Modules',
                'url' => null,
                'permissions' => ['view'],
                'show_in_sidebar' => 0
            ],
            'api_v2' => [
                'category' => 'API Modules',
                'url' => null,
                'permissions' => ['view'],
                'show_in_sidebar' => 0
            ],
            'api_v3' => [
                'category' => 'API Modules',
                'url' => null,
                'permissions' => ['view'],
                'show_in_sidebar' => 0
            ],
            'ajax' => [
                'category' => 'API Modules',
                'url' => null,
                'permissions' => ['view'],
                'show_in_sidebar' => 0
            ],
        ];
    }

    public function up()
    {
        $module_config = $this->get_module_config();

        foreach ($module_config as $module_name => $config) {
            // Check if module exists
            $existing = $this->db->query("SELECT id FROM modules WHERE name = ?", [$module_name])->row();

            $data = [
                'category' => $config['category'],
                'url' => $config['url'],
                'available_permissions' => json_encode($config['permissions']),
                'show_in_sidebar' => $config['show_in_sidebar']
            ];

            if ($existing) {
                // Update existing module
                $this->db->where('id', $existing->id);
                $this->db->update('modules', $data);
            }
            // Note: We don't create new modules here, only update existing ones
            // New modules should be created through the Modules management interface
        }

        // Log migration completion
        log_message('info', 'Migration 20260128100100: Populated module sidebar data for ' . count($module_config) . ' modules');
    }

    public function down()
    {
        // Reset all modules to default values
        $this->db->update('modules', [
            'category' => 'System Management',
            'url' => null,
            'available_permissions' => null,
            'show_in_sidebar' => 1
        ]);

        log_message('info', 'Migration 20260128100100: Reverted module sidebar data');
    }
}
