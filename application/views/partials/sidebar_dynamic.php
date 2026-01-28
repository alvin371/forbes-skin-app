<?php
/**
 * Dynamic Sidebar Partial
 *
 * This partial renders the sidebar menu dynamically based on:
 * - modules table configuration (category, url, icon, show_in_sidebar)
 * - user permissions from role_permissions table
 *
 * Required variables:
 * - $user_id: Current user ID
 * - $template: Template library instance
 *
 * Usage in TemplateDashboard.php:
 * $this->load->view('partials/sidebar_dynamic', ['user_id' => $user_id, 'template' => $this->template]);
 */

$CI = &get_instance();
$CI->load->library('template');
$CI->load->library('permission');

// Get sidebar menu from database
$sidebar_menu = $CI->template->get_sidebar_menu($user_id);
$active_menu = $CI->template->get_active_menu();

// Get current URI info for active state
$uri_1 = $CI->uri->segment(1);
$uri_2 = $CI->uri->segment(2);
$m = isset($_GET['m']) ? $_GET['m'] : '';
$brand = isset($_GET['brand']) ? $_GET['brand'] : '';

// Helper function to check if module is active
function is_active($module_name, $active_menu)
{
    return $active_menu['module'] === $module_name ? 'active' : '';
}

// Helper function to check if category is expanded
function is_expanded($category, $active_menu)
{
    return $active_menu['category'] === $category ? 'show' : '';
}

// Helper function to get icon - with fallback
function get_icon($module)
{
    if (!empty($module['icon'])) {
        return $module['icon'];
    }
    // Fallback icons based on module name
    $default_icons = [
        'dashboard' => 'bi bi-house',
        'report' => 'bi bi-graph-up-arrow',
        'expense' => 'bi bi-credit-card',
        'marketing' => 'bi bi-megaphone',
        'influencer' => 'bi bi-person-bounding-box',
        'influencer_dummy' => 'bi bi-person-lines-fill',
        'endorse_campaign' => 'bi bi-person-video2',
        'calendar' => 'bi bi-calendar-week',
        'payment' => 'bi bi-wallet2',
        'codeboost' => 'bi bi-box-arrow-in-up',
        'transaction' => 'bi bi-handbag',
        'transaction_item' => 'bi bi-arrow-left-right',
        'crm_mg' => 'bi bi-person-heart',
        'crm_pome' => 'bi bi-person-heart',
        'group_wa' => 'bi bi-whatsapp',
        'stock' => 'bi bi-arrow-left-right',
        'product' => 'bi bi-box',
        'attendance' => 'bi bi-calendar-check',
        'leave' => 'bi bi-journal-text',
        'leave_approvals' => 'bi bi-check2-circle',
        'offices' => 'bi bi-building',
        'holidays' => 'bi bi-calendar-event',
        'leave_types' => 'bi bi-clipboard2-plus',
        'leave_quotas' => 'bi bi-calendar3-range',
        'attendance_settings' => 'bi bi-gear',
        'performance_admin' => 'bi bi-graph-up',
        'recruitment' => 'bi bi-person-fill-up',
        'quest_level' => 'bi bi-award',
        'position' => 'bi bi-briefcase',
        'benefit' => 'bi bi-gift',
        'quest' => 'bi bi-trophy',
        'milestone' => 'bi bi-trophy-fill',
        'user' => 'bi bi-person-vcard',
        'roles' => 'bi bi-shield-check',
        'modules' => 'bi bi-shield-lock',
        'profile' => 'bi bi-person-circle',
        'ads_tiktok' => '',
        'ads_meta' => '',
        'ads_shopee' => '',
        'ads_lazada' => ''
    ];
    return $default_icons[$module['name']] ?? 'bi bi-circle';
}

// Marketplace images for ads
$marketplace_images = [
    'ads_tiktok' => 'assets/img/marketplace/3.png',
    'ads_meta' => 'assets/img/marketplace/5.png',
    'ads_shopee' => 'assets/img/marketplace/1.png',
    'ads_lazada' => 'assets/img/marketplace/2.png'
];

// Module display name overrides
$display_name_overrides = [
    'marketing' => 'OVERVIEW',
    'attendance' => 'ATTENDANCE',
    'leave' => 'LEAVE REQUESTS',
    'leave_approvals' => 'LEAVE APPROVALS',
    'leave_types' => 'LEAVE TYPES',
    'leave_quotas' => 'LEAVE QUOTAS',
    'attendance_settings' => 'ATTENDANCE SETTINGS',
    'performance_admin' => 'PERFORMANCE APPRAISAL',
    'quest_level' => 'QUEST LEVELS',
    'position' => 'POSITIONS',
    'benefit' => 'BENEFITS',
    'quest' => 'QUEST MANAGEMENT',
    'milestone' => 'MILESTONE & LEADERBOARD',
    'roles' => 'ROLE MANAGEMENT',
    'modules' => 'MODULES & PERMISSIONS',
    'profile' => 'AKUN SAYA',
    'group_wa' => 'GRUP WA',
    'stock' => 'STOK',
    'product' => 'KONFIGURASI',
    'transaction' => 'ORDER',
    'transaction_item' => 'ORDER ITEM',
    'calendar' => 'ENDORSE CALENDAR',
    'payment' => 'PAYMENT & REVIEW',
    'influencer_dummy' => 'INFLUENCER LISTING',
    'endorse_campaign' => 'ENDORSE CAMPAIGN'
];
?>

<div class="pt-0 d-flex flex-column gap-5">
    <div class="menu p-0">
        <?php
        foreach ($sidebar_menu as $category => $category_data):
            $items = $category_data['items'];
            $is_flat = $category_data['is_flat'];
            $submenu_groups = $category_data['submenu_groups'] ?? null;
            $category_expanded = is_expanded($category, $active_menu);

            if ($is_flat):
                // Flat categories render items at top level (Dashboard, Report, Expense)
                foreach ($items as $module):
                    $icon = get_icon($module);
                    $display_name = $display_name_overrides[$module['name']] ?? strtoupper($module['display_name']);
                    $is_module_active = is_active($module['name'], $active_menu);
        ?>
                    <a href="<?= base_url() ?><?= $module['url'] ?>" class="item-menu <?= $is_module_active ?>">
                        <i class="icon <?= $icon ?>"></i>
                        <?= $display_name ?>
                    </a>
                <?php endforeach; ?>
            <?php else:
                // Collapsible categories (Marketing, Operations, HR, etc.)
                $collapsed_class = $category_expanded ? '' : 'collapsed';
                $chevron_class = $category_expanded ? 'bi-chevron-up' : 'bi-chevron-down';
                $submenu_id = 'submenu-' . strtolower(str_replace(' ', '-', $category));
            ?>
                <a class="item-menu fw-bold <?= $collapsed_class ?> d-flex align-items-center justify-content-between"
                   data-bs-toggle="collapse"
                   href="#<?= $submenu_id ?>"
                   role="button"
                   aria-expanded="<?= $category_expanded ? 'true' : 'false' ?>"
                   aria-controls="<?= $submenu_id ?>">
                    <?= $category_data['display_name'] ?>
                    <i class="bi <?= $chevron_class ?> icon-side ms-auto"></i>
                </a>

                <div class="collapse <?= $category_expanded ?>" id="<?= $submenu_id ?>">
                    <?php
                    if ($submenu_groups):
                        // Category with submenu groups (Marketing with Advertiser, Endorsement submenus)
                        foreach ($submenu_groups as $group_name => $group_modules):
                            // Filter items that belong to this group
                            $group_items = array_filter($items, function ($item) use ($group_modules) {
                                return in_array($item['name'], $group_modules);
                            });

                            if (empty($group_items)) continue;

                            if ($group_name === 'overview'):
                                // Overview items render directly
                                foreach ($group_items as $module):
                                    $display_name = $display_name_overrides[$module['name']] ?? strtoupper($module['display_name']);
                                    $is_module_active = is_active($module['name'], $active_menu);
                    ?>
                                    <a href="<?= base_url() ?><?= $module['url'] ?>" class="ms-2 item-menu <?= $is_module_active ?>">
                                        <?= $display_name ?>
                                    </a>
                                <?php endforeach; ?>
                            <?php else:
                                // Advertiser and Endorsement get their own collapsible submenus
                                $group_expanded = ($active_menu['submenu'] === $group_name) ? 'show' : '';
                                $group_collapsed = $group_expanded ? '' : 'collapsed';
                                $group_chevron = $group_expanded ? 'bi-chevron-up' : 'bi-chevron-down';
                                $group_id = 'submenu-' . $group_name;
                                $group_display = strtoupper($group_name);
                            ?>
                                <a class="item-menu <?= $group_collapsed ?> d-flex align-items-center justify-content-between ms-2"
                                   data-bs-toggle="collapse"
                                   href="#<?= $group_id ?>"
                                   role="button"
                                   aria-expanded="<?= $group_expanded ? 'true' : 'false' ?>"
                                   aria-controls="<?= $group_id ?>">
                                    <?= $group_display ?>
                                    <i class="bi <?= $group_chevron ?> icon-side ms-auto"></i>
                                </a>

                                <div class="collapse <?= $group_expanded ?>" id="<?= $group_id ?>">
                                    <?php foreach ($group_items as $module):
                                        $icon = get_icon($module);
                                        $display_name = $display_name_overrides[$module['name']] ?? strtoupper($module['display_name']);
                                        $is_module_active = is_active($module['name'], $active_menu);

                                        // Check if this is an ads module with marketplace image
                                        $has_marketplace_image = isset($marketplace_images[$module['name']]);
                                    ?>
                                        <a href="<?= base_url() ?><?= $module['url'] ?>" class="ms-<?= $group_name === 'advertiser' ? '2' : '3' ?> item-menu <?= $is_module_active ?>">
                                            <?php if ($has_marketplace_image): ?>
                                                <i class="icon">
                                                    <img src="<?= base_url() ?><?= $marketplace_images[$module['name']] ?>" alt="<?= $display_name ?>" class="rounded-circle border" style="width: 35px; height: 35px;">
                                                </i>
                                            <?php elseif ($icon): ?>
                                                <i class="icon <?= $icon ?>"></i>
                                            <?php endif; ?>
                                            <?= $display_name ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php else:
                        // Regular collapsible category without submenu groups
                        foreach ($items as $module):
                            $icon = get_icon($module);
                            $display_name = $display_name_overrides[$module['name']] ?? strtoupper($module['display_name']);
                            $is_module_active = is_active($module['name'], $active_menu);
                    ?>
                            <a href="<?= base_url() ?><?= $module['url'] ?>" class="ms-3 item-menu <?= $is_module_active ?>">
                                <?php if ($icon): ?>
                                    <i class="icon <?= $icon ?>"></i>
                                <?php endif; ?>
                                <?= $display_name ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>

        <?php
        // Always show Account section with logout
        $akun_expanded = in_array($active_menu['module'], ['user', 'roles', 'modules', 'profile']) ? 'show' : '';
        $akun_collapsed = $akun_expanded ? '' : 'collapsed';
        $akun_chevron = $akun_expanded ? 'bi-chevron-up' : 'bi-chevron-down';

        // Check if user has access to any account module
        $can_view_user = $CI->permission->check_permission($user_id, 'user', 'view');
        $can_view_roles = $CI->permission->check_permission($user_id, 'roles', 'view');
        $can_view_modules = $CI->permission->check_permission($user_id, 'modules', 'view');
        $can_view_profile = $CI->permission->check_permission($user_id, 'profile', 'view');
        $can_view_akun = $can_view_user || $can_view_roles || $can_view_modules || $can_view_profile;

        if ($can_view_akun):
        ?>
            <a class="item-menu fw-bold <?= $akun_collapsed ?> d-flex align-items-center justify-content-between"
               data-bs-toggle="collapse"
               href="#submenu-akun"
               role="button"
               aria-expanded="<?= $akun_expanded ? 'true' : 'false' ?>"
               aria-controls="submenu-akun">
                AKUN
                <i class="bi <?= $akun_chevron ?> icon-side ms-auto"></i>
            </a>

            <div class="collapse <?= $akun_expanded ?>" id="submenu-akun">
                <?php if ($can_view_user): ?>
                    <a href="<?= base_url() ?>user" class="ms-3 item-menu <?= is_active('user', $active_menu) ?>">
                        <i class="icon bi bi-person-vcard"></i>
                        USER
                    </a>
                <?php endif; ?>
                <?php if ($can_view_roles): ?>
                    <a href="<?= base_url() ?>roles" class="ms-3 item-menu <?= is_active('roles', $active_menu) ?>">
                        <i class="icon bi bi-shield-check"></i>
                        ROLE MANAGEMENT
                    </a>
                <?php endif; ?>
                <?php if ($can_view_modules): ?>
                    <a href="<?= base_url() ?>modules" class="ms-3 item-menu <?= is_active('modules', $active_menu) ?>">
                        <i class="icon bi bi-shield-lock"></i>
                        MODULES & PERMISSIONS
                    </a>
                <?php endif; ?>
                <?php if ($can_view_profile): ?>
                    <a href="<?= base_url() ?>profile" class="ms-3 item-menu <?= is_active('profile', $active_menu) ?>">
                        <i class="icon bi bi-person-circle"></i>
                        AKUN SAYA
                    </a>
                <?php endif; ?>
                <a href="<?= base_url() ?>auth/logout-process" class="ms-3 item-menu">
                    <i class="icon bi bi-door-open"></i>
                    KELUAR
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>
