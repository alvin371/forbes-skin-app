<?php
defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('sidebar_registry')) {
    function sidebar_registry()
    {
        return [
            // System Management
            'dashboard' => [
                'display_name' => 'DASHBOARD',
                'controller' => 'dashboard',
                'category' => 'System Management',
                'sort_order' => 10,
                'permissions' => ['view'],
            ],
            'report' => [
                'display_name' => 'REPORT',
                'controller' => 'report',
                'category' => 'System Management',
                'sort_order' => 20,
                'permissions' => ['view'],
            ],
            'expense' => [
                'display_name' => 'PENGELUARAN',
                'controller' => 'expense',
                'category' => 'System Management',
                'sort_order' => 30,
                'permissions' => ['view', 'create', 'edit', 'delete'],
            ],

            // Marketing
            'marketing' => [
                'display_name' => 'OVERVIEW',
                'controller' => 'overview',
                'category' => 'Marketing',
                'sort_order' => 110,
                'permissions' => ['view'],
            ],
            'ads_tiktok' => [
                'display_name' => 'TIKTOK',
                'controller' => 'ads',
                'category' => 'Marketing',
                'sort_order' => 120,
                'permissions' => ['view'],
            ],
            'ads_meta' => [
                'display_name' => 'META',
                'controller' => 'ads',
                'category' => 'Marketing',
                'sort_order' => 130,
                'permissions' => ['view'],
            ],
            'ads_shopee' => [
                'display_name' => 'SHOPEE',
                'controller' => 'ads',
                'category' => 'Marketing',
                'sort_order' => 140,
                'permissions' => ['view'],
            ],
            'ads_lazada' => [
                'display_name' => 'LAZADA',
                'controller' => 'ads',
                'category' => 'Marketing',
                'sort_order' => 150,
                'permissions' => ['view'],
            ],
            'influencer' => [
                'display_name' => 'INFLUENCER',
                'controller' => 'influencer',
                'category' => 'Marketing',
                'sort_order' => 210,
                'permissions' => ['view', 'create', 'edit', 'delete'],
            ],
            'influencer_dummy' => [
                'display_name' => 'INFLUENCER LISTING',
                'controller' => 'influencer_dummy',
                'category' => 'Marketing',
                'sort_order' => 220,
                'permissions' => ['view', 'create', 'edit', 'delete'],
            ],
            'endorse_campaign' => [
                'display_name' => 'ENDORSE CAMPAIGN',
                'controller' => 'endorse_campaign',
                'category' => 'Marketing',
                'sort_order' => 230,
                'permissions' => ['view', 'create', 'edit', 'delete'],
            ],
            'calendar' => [
                'display_name' => 'ENDORSE CALENDAR',
                'controller' => 'calendar',
                'category' => 'Marketing',
                'sort_order' => 240,
                'permissions' => ['view'],
            ],
            'payment' => [
                'display_name' => 'PAYMENT & REVIEW',
                'controller' => 'payment',
                'category' => 'Marketing',
                'sort_order' => 250,
                'permissions' => ['view', 'create', 'edit', 'delete'],
            ],
            'codeboost' => [
                'display_name' => 'CODEBOOST',
                'controller' => 'codeboost',
                'category' => 'Marketing',
                'sort_order' => 260,
                'permissions' => ['view', 'create', 'edit', 'delete'],
            ],

            // Order & Customer
            'marketplace_account' => [
                'display_name' => 'TOKO',
                'controller' => 'marketplace_account',
                'category' => 'Order & Customer',
                'sort_order' => 310,
                'permissions' => ['view', 'create', 'edit', 'delete'],
            ],
            'transaction' => [
                'display_name' => 'ORDER',
                'controller' => 'transaction',
                'category' => 'Order & Customer',
                'sort_order' => 320,
                'permissions' => ['view', 'create', 'edit', 'delete'],
            ],
            'transaction_item' => [
                'display_name' => 'ORDER ITEM',
                'controller' => 'transaction_item',
                'category' => 'Order & Customer',
                'sort_order' => 330,
                'permissions' => ['view', 'create', 'edit', 'delete'],
            ],
            'crm_mg' => [
                'display_name' => 'CRM MG',
                'controller' => 'crm',
                'category' => 'Order & Customer',
                'sort_order' => 340,
                'permissions' => ['view', 'create', 'edit', 'delete'],
            ],
            'crm_pome' => [
                'display_name' => 'CRM POME',
                'controller' => 'crm',
                'category' => 'Order & Customer',
                'sort_order' => 350,
                'permissions' => ['view', 'create', 'edit', 'delete'],
            ],
            'group_wa' => [
                'display_name' => 'GRUP WA',
                'controller' => 'group_wa',
                'category' => 'Order & Customer',
                'sort_order' => 360,
                'permissions' => ['view', 'create', 'edit', 'delete'],
            ],

            // Operasional
            'stock' => [
                'display_name' => 'STOK',
                'controller' => 'stock',
                'category' => 'Operasional',
                'sort_order' => 410,
                'permissions' => ['view', 'create', 'edit', 'delete'],
            ],
            'product' => [
                'display_name' => 'KONFIGURASI',
                'controller' => 'product',
                'category' => 'Operasional',
                'sort_order' => 420,
                'permissions' => ['view', 'create', 'edit', 'delete'],
            ],

            // HR Management
            'attendance' => [
                'display_name' => 'ATTENDANCE',
                'controller' => 'attendance',
                'category' => 'HR Management',
                'sort_order' => 510,
                'permissions' => ['view', 'create', 'edit', 'delete'],
            ],
            'attendance_report' => [
                'display_name' => 'ATTENDANCE REPORT',
                'controller'   => 'attendancereport',
                'category'     => 'HR Management',
                'sort_order'   => 515,
                'permissions'  => ['view'],
            ],
            'leave' => [
                'display_name' => 'LEAVE REQUESTS',
                'controller' => 'leave',
                'category' => 'HR Management',
                'sort_order' => 520,
                'permissions' => ['view', 'create', 'edit', 'delete'],
            ],
            'overtime' => [
                'display_name' => 'OVERTIME REQUESTS',
                'controller' => 'overtime',
                'category' => 'HR Management',
                'sort_order' => 525,
                'permissions' => ['view', 'create', 'delete'],
            ],
            'leave_approvals' => [
                'display_name' => 'LEAVE APPROVALS',
                'controller' => 'leaveapprovalcontroller',
                'category' => 'HR Management',
                'sort_order' => 530,
                'permissions' => ['view', 'approve'],
            ],
            'overtime_approvals' => [
                'display_name' => 'OVERTIME APPROVALS',
                'controller' => 'overtimeapprovalcontroller',
                'category' => 'HR Management',
                'sort_order' => 535,
                'permissions' => ['view', 'approve'],
            ],
            'offices' => [
                'display_name' => 'OFFICES',
                'controller' => 'offices',
                'category' => 'HR Management',
                'sort_order' => 540,
                'permissions' => ['view', 'create', 'edit', 'delete'],
            ],
            'attendance_settings' => [
                'display_name' => 'ATTENDANCE SETTINGS',
                'controller' => 'attendancesettingscontroller',
                'category' => 'HR Management',
                'sort_order' => 550,
                'permissions' => ['view', 'create', 'edit', 'delete'],
            ],
            'holidays' => [
                'display_name' => 'HOLIDAYS',
                'controller' => 'holidayscontroller',
                'category' => 'HR Management',
                'sort_order' => 560,
                'permissions' => ['view', 'create', 'edit', 'delete'],
            ],
            'leave_types' => [
                'display_name' => 'LEAVE TYPES',
                'controller' => 'leavetypescontroller',
                'category' => 'HR Management',
                'sort_order' => 570,
                'permissions' => ['view', 'create', 'edit', 'delete'],
            ],
            'leave_quotas' => [
                'display_name' => 'LEAVE QUOTAS',
                'controller' => 'leavequotascontroller',
                'category' => 'HR Management',
                'sort_order' => 580,
                'permissions' => ['view', 'create', 'edit', 'delete'],
            ],
            'approval_routes' => [
                'display_name' => 'APPROVAL ROUTES',
                'controller' => 'approvalroutescontroller',
                'category' => 'HR Management',
                'sort_order' => 590,
                'permissions' => ['view', 'create', 'edit', 'delete'],
            ],
            'performance_admin' => [
                'display_name' => 'PERFORMANCE APPRAISAL',
                'controller' => 'performanceappraisal',
                'category' => 'HR Management',
                'sort_order' => 600,
                'permissions' => ['view', 'create', 'edit', 'delete'],
            ],
            'recruitment' => [
                'display_name' => 'RECRUITMENT',
                'controller' => 'recruitment',
                'category' => 'HR Management',
                'sort_order' => 610,
                'permissions' => ['view', 'approve'],
                'is_active' => 0,
            ],
            'quest_level' => [
                'display_name' => 'QUEST LEVELS',
                'controller' => 'quest_level',
                'category' => 'HR Management',
                'sort_order' => 620,
                'permissions' => ['view', 'create', 'edit', 'delete'],
                'is_active' => 0,
            ],
            'position' => [
                'display_name' => 'POSITIONS',
                'controller' => 'position',
                'category' => 'HR Management',
                'sort_order' => 630,
                'permissions' => ['view', 'create', 'edit', 'delete'],
                'is_active' => 0,
            ],
            'benefit' => [
                'display_name' => 'BENEFITS',
                'controller' => 'benefit',
                'category' => 'HR Management',
                'sort_order' => 640,
                'permissions' => ['view', 'create', 'edit', 'delete'],
                'is_active' => 0,
            ],
            'quest' => [
                'display_name' => 'QUEST MANAGEMENT',
                'controller' => 'quest',
                'category' => 'HR Management',
                'sort_order' => 650,
                'permissions' => ['view', 'create', 'edit', 'delete'],
                'is_active' => 0,
            ],
            'milestone' => [
                'display_name' => 'MILESTONE & LEADERBOARD',
                'controller' => 'milestone',
                'category' => 'HR Management',
                'sort_order' => 660,
                'permissions' => ['view', 'create', 'edit', 'delete'],
                'is_active' => 0,
            ],

            // Account
            'user' => [
                'display_name' => 'USER',
                'controller' => 'user',
                'category' => 'Account',
                'sort_order' => 710,
                'permissions' => ['view', 'create', 'edit', 'delete'],
            ],
            'roles' => [
                'display_name' => 'ROLE MANAGEMENT',
                'controller' => 'roles',
                'category' => 'Account',
                'sort_order' => 720,
                'permissions' => ['view', 'create', 'edit', 'delete'],
            ],
            'modules' => [
                'display_name' => 'MODULES & PERMISSIONS',
                'controller' => 'modules',
                'category' => 'Account',
                'sort_order' => 730,
                'permissions' => ['view'],
            ],
            'profile' => [
                'display_name' => 'AKUN SAYA',
                'controller' => 'profile',
                'category' => 'Account',
                'sort_order' => 740,
                'permissions' => ['view'],
            ],

            // Reports & Analytics
            'scraper' => [
                'display_name' => 'SCRAPER',
                'controller' => 'scraper',
                'category' => 'Reports & Analytics',
                'sort_order' => 810,
                'permissions' => ['view', 'create', 'edit', 'delete'],
            ],
        ];
    }
}

if (!function_exists('sidebar_registry_categories')) {
    function sidebar_registry_categories()
    {
        return [
            'System Management',
            'Marketing',
            'Order & Customer',
            'Operasional',
            'HR Management',
            'Account',
            'Reports & Analytics',
        ];
    }
}

if (!function_exists('sidebar_registry_names')) {
    function sidebar_registry_names()
    {
        $names = [];
        foreach (sidebar_registry() as $name => $config) {
            if (isset($config['is_active']) && (int) $config['is_active'] !== 1) {
                continue;
            }
            $names[] = $name;
        }
        return $names;
    }
}

if (!function_exists('sidebar_registry_permissions')) {
    function sidebar_registry_permissions()
    {
        $permissions = [];
        foreach (sidebar_registry() as $name => $config) {
            if (isset($config['is_active']) && (int) $config['is_active'] !== 1) {
                continue;
            }
            $permissions[$name] = $config['permissions'];
        }
        return $permissions;
    }
}

if (!function_exists('sidebar_registry_category_for')) {
    function sidebar_registry_category_for($module_name)
    {
        $registry = sidebar_registry();
        return $registry[$module_name]['category'] ?? 'System Management';
    }
}

if (!function_exists('sidebar_registry_sync')) {
    function sidebar_registry_sync($ci)
    {
        if (!$ci || !isset($ci->db)) {
            return;
        }

        $registry = sidebar_registry();

        foreach ($registry as $name => $config) {
            $existing = $ci->db
                ->where('name', $name)
                ->limit(1)
                ->get('modules')
                ->row_array();

            $payload = [
                'name' => $name,
                'display_name' => $config['display_name'],
                'sort_order' => $config['sort_order'],
                'is_active' => array_key_exists('is_active', $config) ? (int) $config['is_active'] : 1,
            ];

            if (!empty($config['controller'])) {
                $payload['controller'] = $config['controller'];
            }

            if ($existing) {
                $update = [];
                foreach ($payload as $key => $value) {
                    if (!array_key_exists($key, $existing) || $existing[$key] !== $value) {
                        $update[$key] = $value;
                    }
                }
                if (!empty($update)) {
                    $ci->db->where('id', $existing['id'])->update('modules', $update);
                }
            } else {
                $ci->db->insert('modules', $payload);
            }
        }
    }
}
