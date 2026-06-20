<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Announcement category registry.
 *
 * Single source of truth for the Announcement module categories + subcategories,
 * used by the views (dependent dropdowns) AND the controller (server-side
 * validation that a subcategory belongs to its category). Labels are stored
 * verbatim in announcements.category / announcements.subcategory.
 */

if (!function_exists('announcement_categories')) {
    function announcement_categories()
    {
        return [
            'Administrative & Operations' => [
                'Policy Memorandums',
                'Office Closures & Holidays',
                'IT & System Maintenance',
                'Facility Updates',
            ],
            'Human Resources & People' => [
                'New Hire Welcomes',
                'Promotions & Achievements',
                'Benefits & Enrollment',
                'Training & Development',
            ],
            'Culture & Community' => [
                'Social Events',
                'Volunteering & Charity',
                'Employee Resource Groups',
                'Surveys & Feedback',
            ],
        ];
    }
}

if (!function_exists('announcement_category_names')) {
    function announcement_category_names()
    {
        return array_keys(announcement_categories());
    }
}

if (!function_exists('announcement_subcategory_valid')) {
    /**
     * True when $subcategory is a registered child of $category.
     */
    function announcement_subcategory_valid($category, $subcategory)
    {
        $registry = announcement_categories();
        if (!isset($registry[$category])) {
            return false;
        }
        return in_array($subcategory, $registry[$category], true);
    }
}

if (!function_exists('announcement_statuses')) {
    function announcement_statuses()
    {
        return ['DRAFT', 'PUBLISHED', 'ARCHIVED'];
    }
}

if (!function_exists('announcement_priorities')) {
    function announcement_priorities()
    {
        return ['LOW', 'MEDIUM', 'HIGH', 'URGENT'];
    }
}
