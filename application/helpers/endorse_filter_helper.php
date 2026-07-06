<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Single source of truth for the "which endorses qualify" filter used by the
 * campaign chart (Ajax::get_chart_campaign), the summary tiles
 * (Ajax::get_summary_campaign) and the logs page (Endorse::logs).
 *
 * Before this helper each of those three built its own WHERE, and they drifted:
 * the chart filtered PIC with `pic IN(...)` (exact) while logs/tiles used
 * `pic LIKE` or ignored the PIC dropdown entirely, and only the chart applied
 * brand/product. That made the same PIC show different totals on each page —
 * the reason employee-performance numbers couldn't be trusted.
 *
 * Returns the endorse-set predicate WITHOUT any date clause: date handling is
 * per-surface (chart = range on endorse_logs.log_date, logs = single day,
 * tiles = as-of). Every clause is anchored on the `endorse` table alias.
 *
 * @param array   $get  Request array ($_GET-shaped).
 * @param CI_DB_driver $db Active DB driver, used only for escaping.
 * @return array{where:string, join:string}
 */
function endorse_filter_where(array $get, $db)
{
    $where = '';
    $join  = '';

    $esc = function ($v) use ($db) {
        return $db->escape_str((string) $v);
    };
    $esc_like = function ($v) use ($db) {
        return $db->escape_like_str((string) $v);
    };
    // Build an IN(...) list of quoted, escaped values; '' if nothing usable.
    $in_list = function ($raw) use ($db) {
        if (!is_array($raw)) {
            $raw = ($raw === null || $raw === '') ? array() : explode(',', $raw);
        }
        $parts = array();
        foreach ($raw as $v) {
            if ($v === '' || $v === null) continue;
            $parts[] = "'" . $db->escape_str((string) $v) . "'";
        }
        return implode(',', $parts);
    };

    if (!empty($get['brand'])) {
        $where .= " AND endorse.brand = '" . $esc($get['brand']) . "' ";
    }

    // Upload/FYP status
    if (!empty($get['status'])) {
        if ($get['status'] === 'Ada Link Upload') {
            $where .= " AND endorse.link_upload != '' ";
        } else if ($get['status'] === 'Tidak Ada Link Upload') {
            $where .= " AND endorse.link_upload = '' ";
        } else if ($get['status'] === 'FYP') {
            $where .= " AND endorse.is_fyp = 1 ";
        }
    }

    if (!empty($get['status_data'])) {
        $where .= " AND endorse.status = '" . $esc($get['status_data']) . "' ";
    }

    if (!empty($get['endorse_status'])) {
        $list = $in_list($get['endorse_status']);
        if ($list) $where .= " AND endorse.status_endorse IN ($list) ";
    }

    if (!empty($get['status_payment'])) {
        $list = $in_list($get['status_payment']);
        if ($list) $where .= " AND endorse.status_payment IN ($list) ";
    }

    if (!empty($get['platform'])) {
        $where .= " AND endorse.platform = '" . $esc($get['platform']) . "' ";
    }

    // PIC dropdown (multi) — EXACT match everywhere. This is the employee filter.
    if (!empty($get['pic'])) {
        $list = $in_list($get['pic']);
        if ($list) $where .= " AND endorse.pic IN ($list) ";
    }

    // Product (multi)
    if (!empty($get['product'])) {
        $list = $in_list($get['product']);
        if ($list) $where .= " AND endorse.product IN ($list) ";
    }

    // Endorsement category (internal/external) — needs endorse_campaign join
    if (isset($get['endorse_category'])) {
        if ($get['endorse_category'] === 'internal') {
            $where .= " AND endorse_campaign.is_internal = 1 ";
            $join   = " INNER JOIN endorse_campaign ON endorse_campaign.id = endorse.id_campaign ";
        } else if ($get['endorse_category'] === 'external') {
            $where .= " AND endorse_campaign.is_internal = 0 ";
            $join   = " INNER JOIN endorse_campaign ON endorse_campaign.id = endorse.id_campaign ";
        }
    }

    // Free-text keyword search (LIKE) — a search box, kept identical across surfaces.
    if (!empty($get['keyword'])) {
        $kw  = $esc_like($get['keyword']);
        $cat = !empty($get['keyword_category']) ? $get['keyword_category'] : 'Nama Creator';
        if ($cat === 'Nama Creator') {
            $where .= " AND endorse.nama_creator LIKE '%$kw%' ";
        } else if ($cat === 'Link Upload') {
            $where .= " AND endorse.link_upload LIKE '%$kw%' ";
        } else if ($cat === 'PIC') {
            $where .= " AND endorse.pic LIKE '%$kw%' ";
        } else if ($cat === 'Platform') {
            $where .= " AND endorse.platform LIKE '%$kw%' ";
        } else if ($cat === 'Task') {
            $where .= " AND endorse.task LIKE '%$kw%' ";
        } else if ($cat === 'Keterangan') {
            $where .= " AND endorse.`desc` LIKE '%$kw%' ";
        }
    }

    // Campaign scope: multi (ids_campaign) wins, else single id_campaign.
    $ids_campaign = $in_list($get['ids_campaign'] ?? '');
    if ($ids_campaign) {
        $where .= " AND endorse.id_campaign IN ($ids_campaign) ";
    } else if (!empty($get['id_campaign'])) {
        $where .= " AND endorse.id_campaign = '" . $esc($get['id_campaign']) . "' ";
    }

    return array('where' => $where, 'join' => $join);
}
