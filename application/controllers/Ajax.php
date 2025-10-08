<?php

defined('BASEPATH') or exit('No direct script access allowed');
class Ajax extends CI_Controller
{

	function refresh_token()
	{
		session_start();
	}
	public function curl()
	{
		die;
		$data_now = $this->mymodel->selectWithQuery("SELECT * FROM endorse_logs WHERE DATE(date) = '2024-06-10'");
		foreach ($data_now as $k => $v) {
			$id_endorse = $v['id_endorse'];
			$data_before = $this->mymodel->selectWithQuery("SELECT * FROM endorse_logs WHERE DATE(date) = '2024-06-09' AND id_endorse = '$id_endorse' ");
			$data_before = $data_before[0];
			// print_r($data_before);
			// die;

			$query = $v;
			$data_yesterday = $data_before;


			$dt['likes'] = intval($query_yesterday['likes_after'] - $query_yesterday['likes_after']);
			$dt['comment'] = intval($query_yesterday['comment_after']);
			$dt['share_save'] = intval($query_yesterday['share_save_after']);
			$dt['views'] = intval($query_yesterday['views_after']);

			$dt['likes_before'] = intval($query_yesterday['likes_after']);
			$dt['comment_before'] = intval($query_yesterday['comment_after']);
			$dt['share_save_before'] = intval($query_yesterday['share_save_after']);
			$dt['views_before'] = intval($query_yesterday['views_after']);

			$dt_tmp = array();
			foreach ($dt as $kt => $vt) {
				$dt_tmp[$kt] = strval($vt);
			}
			$dt = $dt_tmp;

			$this->db->update('endorse_logs', $dt, array('id' => $query['id']));

			print_r($query);
			print_r($data_yesterday);
			die;
			$dt = array();
			$dt = $v;
			print_r($dt);
			die;
		}
	}

	public function sync()
	{
		$data['table'] = $_GET['table'];
		return view("Sync", $data);
	}

	function checkbox()
	{
		$type = $_GET['type'];
		if ($type == "dashboard") {
			$dt = $_GET;
			unset($dt['type']);
			$key = $dt;
			$_SESSION['checkbox_dashboard'] = $key;
		} else if ($type == "dashboard_campaign") {
			$dt = $_GET;
			unset($dt['type']);
			$key = $dt;
			$_SESSION['checkbox_dashboard_campaign'] = $key;
		} else if ($type == "overview") {
			$dt = $_GET;
			unset($dt['type']);
			$key = $dt;
			$_SESSION['checkbox_overview'] = $key;
		} else {
			$key = $_GET;
			$_SESSION['checkbox'] = $key;
		}

		header('Content-Type: application/json; charset=utf-8');
		$html['status'] = true;
		echo json_encode($html, true);
	}

	public function get_chart_campaign()
	{
		$is_dashboard = $_GET['is_dashboard'];

		// ===== Validasi minimal 1 filter metrik dipilih (checkbox[1..5]) =====
		if ($is_dashboard != 'true') {
			$checkbox = $_SESSION['checkbox'];
		} else {
			$checkbox = $_SESSION['checkbox_dashboard_campaign'];
		}
		$skip = 0;
		for ($i = 1; $i <= 7; $i++) {
			if ($checkbox[$i] == 'false') $skip++;
		}
		if ($skip >= 7) {
			$html['html']  = '<i>Pastikan memilih minimal 1 filter!</i>';
			$html['table'] = '';
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode($html, true);
			die;
		}

		$id           = $_GET['id'];
		$id_campaign  = $_GET['id_campaign'];
		$type         = $_GET['type'];
		$start_date   = $_GET['start_date'];
		$until_date   = $_GET['until_date'];
		$start_year   = $_GET['start_year'];
		$until_year   = $_GET['until_year'];
		$start_month  = $_GET['start_month'];
		$until_month  = $_GET['until_month'];
		$start_week   = $_GET['start_week'];
		$until_week   = $_GET['until_week'];
		$brand        = $_GET['brand'];

		if (empty($start_date)) $start_date = date("Y-m-d", strtotime(date("Y-m-d") . " -31 days"));
		if (empty($until_date)) $until_date = date("Y-m-d");

		$qry_opt = " DATE(endorse_logs.date) ";
		$group   = " GROUP BY DATE(endorse_logs.date) ";

		// Detail campaign (opsional)
		$detail = $this->mymodel->selectWithQuery("SELECT * FROM endorse_campaign WHERE id = '$id_campaign'");
		$detail = $detail ? $detail[0] : null;

		$keyword_category = $_GET['keyword_category'] ? $_GET['keyword_category'] : "Nama Creator";
		$keyword = $_GET['keyword'];

		$filters_common = "";

		if ($brand) {
			$filters_common .= " AND endorse.brand = '$brand' ";
		}

		// Status upload/FYP
		$status = $_GET['status'];
		if ($status) {
			if ($status == 'Ada Link Upload') {
				$filters_common .= " AND endorse.link_upload != '' ";
			} else if ($status == 'Tidak Ada Link Upload') {
				$filters_common .= " AND endorse.link_upload = '' ";
			} else if ($status == 'FYP') {
				$filters_common .= " AND endorse.is_fyp = 1 ";
			}
		}

		$status_data = $_GET['status_data'];
		if ($status_data) {
			$filters_common .= " AND endorse.status = '$status_data' ";
		}

		$endorse_status = $_GET['endorse_status'];
		if ($endorse_status) {
			$statusArray = explode(',', $endorse_status);
			$text = '';
			foreach ($statusArray as $v) $text .= "'" . $v . "',";
			$text = rtrim($text, ',');
			if ($text) $filters_common .= " AND endorse.status_endorse IN ($text) ";
		}

		// Status payment (multi)
		$status_payment = $_GET['status_payment'];
		if ($status_payment) {
			$statusPaymentArray = explode(',', $status_payment);
			$text = '';
			foreach ($statusPaymentArray as $v) $text .= "'" . $v . "',";
			$text = rtrim($text, ',');
			if ($text) $filters_common .= " AND endorse.status_payment IN ($text) ";
		}

		// Platform
		$platform = $_GET['platform'];
		if ($platform) {
			$filters_common .= " AND endorse.platform = '$platform' ";
		}

		// Keyword
		if ($keyword) {
			if ($keyword_category == "Nama Creator") {
				$filters_common .= " AND endorse.nama_creator LIKE '%$keyword%' ";
			} else if ($keyword_category == "Link Upload") {
				$filters_common .= " AND endorse.link_upload LIKE '%$keyword%' ";
			} else if ($keyword_category == "PIC") {
				$filters_common .= " AND endorse.pic LIKE '%$keyword%' ";
			} else if ($keyword_category == "Platform") {
				$filters_common .= " AND endorse.platform LIKE '%$keyword%' ";
			} else if ($keyword_category == "Task") {
				$filters_common .= " AND endorse.task LIKE '%$keyword%' ";
			} else if ($keyword_category == "Keterangan") {
				$filters_common .= " AND endorse.`desc` LIKE '%$keyword%' ";
			}
		}

		// Multi campaign
		$ids_campaign = $_GET['ids_campaign'];
		$ids_campaign_list = '';
		if ($ids_campaign && is_array($ids_campaign)) {
			foreach ($ids_campaign as $v) $ids_campaign_list .= "'" . $v . "',";
			$ids_campaign_list = rtrim($ids_campaign_list, ',');
		}
		if ($ids_campaign_list) {
			$filters_common .= " AND endorse.id_campaign IN ($ids_campaign_list) ";
		} else {
			if ($is_dashboard != 'true' && $id_campaign) {
				$filters_common .= " AND endorse.id_campaign = '$id_campaign' ";
			}
		}

		$filters_date_on_endorse = "";
		$cat = $_GET['cat'];
		if ($cat == "Tanggal Dibuat") {
			$filters_date_on_endorse .= " AND DATE(endorse.created_at) >= '$start_date' AND DATE(endorse.created_at) <= '$until_date' ";
		} else if ($cat == "Rencana Upload") {
			$filters_date_on_endorse .= " AND DATE(endorse.rencana_at) >= '$start_date' AND DATE(endorse.rencana_at) <= '$until_date' ";
		} else if ($cat == "Tanggal Posting") {
			$filters_date_on_endorse .= " AND DATE(endorse.posting_at) >= '$start_date' AND DATE(endorse.posting_at) <= '$until_date' ";
		} else if ($cat == "Tanggal TF") {
			$filters_date_on_endorse .= " AND DATE(endorse.tgl_tf) >= '$start_date' AND DATE(endorse.tgl_tf) <= '$until_date' ";
		}

		$qry_common_for_logs = $filters_common . $filters_date_on_endorse;

		// ===== Query data endorse untuk summary & list ids =====
		if ($is_dashboard != 'true') {
			$query   = $this->mymodel->selectWithQuery("SELECT endorse.id, endorse.total_cost FROM endorse WHERE 1=1 $filters_common $filters_date_on_endorse AND endorse.id_campaign = '$id_campaign'");
			$query_2 = $this->mymodel->selectWithQuery("SELECT endorse.id FROM endorse WHERE 1=1 $filters_common $filters_date_on_endorse AND endorse.id_campaign = '$id_campaign' GROUP BY endorse.influencer");
			$q_fyp   = $this->mymodel->selectWithQuery("SELECT COUNT(endorse.id) as result FROM endorse WHERE 1=1 $filters_common $filters_date_on_endorse AND endorse.id_campaign = '$id_campaign' AND endorse.is_fyp = 1");
		} else {
			$query   = $this->mymodel->selectWithQuery("SELECT endorse.id, endorse.total_cost FROM endorse WHERE 1=1 $filters_common $filters_date_on_endorse");
			$query_2 = $this->mymodel->selectWithQuery("SELECT endorse.id FROM endorse WHERE 1=1 $filters_common $filters_date_on_endorse GROUP BY endorse.influencer");
			$q_fyp   = $this->mymodel->selectWithQuery("SELECT COUNT(endorse.id) as result FROM endorse WHERE 1=1 $filters_common $filters_date_on_endorse AND endorse.is_fyp = 1");
		}
		$endorse_fyp = $q_fyp ? $q_fyp[0]['result'] : 0;

		$influencer = 0;
		foreach ($query_2 as $row) $influencer++;

		$endorse   = 0;
		$total_cost_from_endorse = 0;
		$list_ids = '';
		foreach ($query as $row) {
			$list_ids .= "'" . $row['id'] . "',";
			$endorse++;
			$total_cost_from_endorse += (double)$row['total_cost'];
		}
		$list_ids = rtrim($list_ids, ',');

		$endorse   = 0;
		$total_cost_from_endorse = 0;
		$list_ids = '';
		foreach ($query as $row) {
			$list_ids .= "'" . $row['id'] . "',";
			$endorse++;
			$total_cost_from_endorse += (double)$row['total_cost'];
		}
		$list_ids = rtrim($list_ids, ',');

		// ===== Filter tambahan id_endorse =====
		$qry_list = '';
		$ids = $_GET['ids'];
		if ($ids) {
			$qry_list .= " AND endorse_logs.id_endorse IN ($ids) ";
		}
		if (($cat || $endorse_status) && $list_ids) {
			$qry_list .= " AND endorse_logs.id_endorse IN ($list_ids) ";
		}

		// ===  Hitung total cost langsung dari tabel endorse (bukan dari logs) ===
		$total_cost_from_endorse = 0.0;
		$sum_where = " WHERE 1=1 $filters_common $filters_date_on_endorse ";
		if ($is_dashboard != 'true' && $id_campaign) {
			$sum_where .= " AND endorse.id_campaign = '$id_campaign' ";
		}
		if (!empty($ids)) {
			$sum_where .= " AND endorse.id IN ($ids) ";
		}
		$sum_sql = "SELECT COALESCE(SUM(endorse.total_cost),0) AS total_cost FROM endorse $sum_where";
		$sum_row = $this->mymodel->selectWithQuery($sum_sql);
		if (!empty($sum_row)) {
			$total_cost_from_endorse = (float)$sum_row[0]['total_cost'];
		}

		// ===== Agregasi per hari dari logs =====
		if ($is_dashboard != 'true') {
			$sql_list = "
				SELECT 
					SUM(endorse_logs.likes_after)        AS likes, 
					SUM(endorse_logs.comment_after)      AS comment,
					SUM(endorse_logs.share_save_after)   AS share_save, 
					SUM(endorse_logs.views_after)        AS views,
					SUM(endorse_logs.total_cost)         AS cost, 
					COUNT(endorse_logs.id)               AS endorse, 
					$qry_opt                              AS opt
				FROM endorse_logs
				INNER JOIN endorse ON endorse.id = endorse_logs.id_endorse 
				WHERE endorse_logs.id_campaign = '$id_campaign' 
				$qry_list 
				$qry_common_for_logs 
				$group
				ORDER BY DATE(endorse_logs.date) ASC
			";
		} else {
			$sql_list = "
				SELECT 
					SUM(endorse_logs.likes_after)        AS likes, 
					SUM(endorse_logs.comment_after)      AS comment,
					SUM(endorse_logs.share_save_after)   AS share_save, 
					SUM(endorse_logs.views_after)        AS views,
					SUM(endorse_logs.total_cost)         AS cost,
					COUNT(endorse_logs.id)               AS endorse, 
					$qry_opt                              AS opt
				FROM endorse_logs
				INNER JOIN endorse ON endorse.id = endorse_logs.id_endorse  
				WHERE 1=1 
				$qry_list 
				$qry_common_for_logs 
				$group
				ORDER BY DATE(endorse_logs.date) ASC
			";
		}
		$list = $this->mymodel->selectWithQuery($sql_list);
		if (empty($list)) $list = array();

		// ===== Siapkan range label =====
		$range = ($this->createRange($start_date, $until_date));
		$arr   = array();

		foreach ($range as $k2 => $v2) {
			$val_1 = 0; // views kumulatif
			$val_2 = 0; // cpm
			$val_3 = 0; // engagement kumulatif
			$val_4 = 0; // cost kumulatif
			$val_5 = 0; // endorse kumulatif

			foreach ($list as $v) {
				if ($v['opt'] == $v2) {
					$val_1 = intval($v['views']);
					$val_3 = intval($v['likes']) + intval($v['comment']) + intval($v['share_save']);
					$val_4 = floatval($v['cost']);
					$val_5 = intval($v['endorse']);
					if ($val_4 > 0 && $val_1 > 0) $val_2 = ($val_4 / $val_1) * 1000;
					break;
				}
			}

			$arr[$k2] = array(
				'opt_pure' => strval($v2),
				'opt'      => date("d M Y", strtotime($v2)),
				'val_1'    => round($val_1, 2),
				'val_2'    => round($val_2, 2),
				'val_3'    => round($val_3, 2),
				'val_4'    => round($val_4, 2),
				'val_5'    => round($val_5, 2)
			);
		}

		// Flatten index
		$arr_new = array_values($arr);

		// ===== Siapkan struktur tampilan =====
		$th_table = $td_1 = $td_2 = $td_3 = $td_4 = $td_5 = "";
		$opt = $a = $b = $c = $d = $e = "";

		$val_arr_1 = $val_arr_2 = $val_arr_3 = $val_arr_4 = $val_arr_5 = array();

		// ===== Baseline D-1 untuk mode SELISIH =====
		$baseline_views = 0;
		$baseline_eng   = 0;
		$baseline_cost  = 0;
		$baseline_end   = 0;

		if ($checkbox[0] == 'true') {
			$sql_base = "
				SELECT 
					SUM(endorse_logs.views_after)                                        AS views,
					SUM(endorse_logs.likes_after + endorse_logs.comment_after + endorse_logs.share_save_after) AS engagement,
					SUM(endorse_logs.total_cost)                                         AS cost,
					COUNT(endorse_logs.id)                                               AS endorse,
					DATE(endorse_logs.date)                                              AS opt
				FROM endorse_logs
				INNER JOIN endorse ON endorse.id = endorse_logs.id_endorse
				WHERE DATE(endorse_logs.date) < '$start_date'
				$qry_list
				$qry_common_for_logs
				GROUP BY DATE(endorse_logs.date)
				ORDER BY DATE(endorse_logs.date) DESC
				LIMIT 1
			";
			$base = $this->mymodel->selectWithQuery($sql_base);
			if (!empty($base)) {
				$baseline_views = (int)$base[0]['views'];
				$baseline_eng   = (int)$base[0]['engagement'];
				$baseline_cost  = (float)$base[0]['cost'];
				$baseline_end   = (int)$base[0]['endorse'];
			}
		}

		// Inisialisasi prev_* dari baseline (delta) atau 0 (kumulatif)
		$prev_views      = ($checkbox[0] == 'true') ? $baseline_views : 0;
		$prev_engagement = ($checkbox[0] == 'true') ? $baseline_eng   : 0;
		$prev_cost       = ($checkbox[0] == 'true') ? $baseline_cost  : 0;
		$prev_endorse    = ($checkbox[0] == 'true') ? $baseline_end   : 0;

		// ===== Akumulator delta untuk summary Daily =====
		$sum_delta_views = 0;
		$sum_delta_eng   = 0;
		$sum_delta_cost  = 0;
		$sum_delta_end   = 0;

		$today = date('Y-m-d');

		foreach ($arr_new as $k => $v) {
			
			// Hitung CPM dari (val_4 / val_1)*1000 jika keduanya > 0
			if ($v['val_4'] > 0 && $v['val_1'] > 0) {
				$v['val_2'] = ($v['val_4'] / $v['val_1']) * 1000;
			} else {
				$v['val_2'] = 0;
			}

			if ($checkbox[0] == 'true') {
				// Hitung SELISIH untuk semua hari vs prev/baseline
				$current_views      = $v['val_1'];
				$current_engagement = $v['val_3'];
				$current_cost       = $v['val_4'];
				$current_endorse    = $v['val_5'];

				// Tampilkan selisih views apa adanya (termasuk minus)
				$v['val_1'] = ($current_views - $prev_views);
				$v['val_3'] = ($current_engagement - $prev_engagement);
				$v['val_4'] = ($current_cost       - $prev_cost);
				$v['val_5'] = ($current_endorse    - $prev_endorse);

				if ($v['val_4'] > 0 && $v['val_1'] > 0) {
					$v['val_2'] = ($v['val_4'] / $v['val_1']) * 1000;
				} else {
					$v['val_2'] = 0;
				}

				// AKUMULASI delta untuk summary (SELALU masuk summary meskipun tidak dicentang)
				$sum_delta_views += $v['val_1'];
				$sum_delta_eng   += $v['val_3'];
				$sum_delta_cost  += $v['val_4'];
				$sum_delta_end   += $v['val_5'];

				// Update prev_* untuk iterasi berikut
				$prev_views      = $current_views;
				$prev_engagement = $current_engagement;
				$prev_cost       = $current_cost;
				$prev_endorse    = $current_endorse;
			} else {
				// Mode kumulatif
				$prev_views      = $v['val_1'];
				$prev_engagement = $v['val_3'];
				$prev_cost       = $v['val_4'];
				$prev_endorse    = $v['val_5'];
			}

			// === NEW: Jika mode SELISIH aktif dan tanggal > hari ini → paksa semua nilai = 0 ===
			if ($checkbox[0] == 'true' && isset($v['opt_pure']) && $v['opt_pure'] > $today) {
				$v['val_1'] = 0;  // views
				$v['val_2'] = 0;  // cpm
				$v['val_3'] = 0;  // engagement
				$v['val_4'] = 0;  // cost
				$v['val_5'] = 0;  // endorse count
			}

			// Build label & tabel
			$opt .= "'" . $v['opt'] . "',";

			if ($checkbox[1] == 'true') {
				$a .= "'" . $this->template->separator_number_only($v['val_1']) . "',";
				$val_arr_1[] = round($v['val_1']);
				$td_1 .= "<td>" . $this->template->separator_only($v['val_1']) . "</td>";
			}
			if ($checkbox[2] == 'true') {
				$b .= "'" . $this->template->separator_number_only($v['val_2']) . "',";
				$val_arr_2[] = round($v['val_2']);
				$td_2 .= "<td>" . $this->template->separator_only($v['val_2']) . "</td>";
			}
			if ($checkbox[3] == 'true') {
				$c .= "'" . $this->template->separator_number_only($v['val_3']) . "',";
				$val_arr_3[] = round($v['val_3']);
				$td_3 .= "<td>" . $this->template->separator_only($v['val_3']) . "</td>";
			}
			if ($checkbox[4] == 'true') {
				$d .= "'" . $this->template->separator_number_only($v['val_4']) . "',";
				$val_arr_4[] = round($v['val_4']);
				$td_4 .= "<td>" . $this->template->separator_only($v['val_4']) . "</td>";
			}
			if ($checkbox[5] == 'true') {
				$e .= "'" . $this->template->separator_number_only($v['val_5']) . "',";
				$val_arr_5[] = round($v['val_5']);
				$td_5 .= "<td>" . $this->template->separator_only($v['val_5']) . "</td>";
			}

			if (!empty($v['opt'])) {
				$v['opt_link'] = '<a target="_blank" href="' . base_url() . 'endorse/logs' . $this->template->get_param() . '&date=' . $v['opt_pure'] . '">' . $v['opt'] . '</a>';
				$th_table .= "<th style='font-size:12px!important'>" . $v['opt_link'] . "</th>";
			}
		}

		// ===== Cari index hari terakhir yang ada datanya (non-zero) =====
		$last_nonempty_idx = -1;
		if (!empty($arr_new)) {
			for ($i = count($arr_new) - 1; $i >= 0; $i--) {
				$d = $arr_new[$i];
				$hasData = (
					(isset($d['val_1']) && (float)$d['val_1'] > 0) || // views
					(isset($d['val_3']) && (float)$d['val_3'] > 0) || // engagement
					(isset($d['val_4']) && (float)$d['val_4'] > 0) || // cost
					(isset($d['val_5']) && (float)$d['val_5'] > 0)    // endorse count
				);
				if ($hasData) { $last_nonempty_idx = $i; break; }
			}
			// jika seluruh range nol, tetap pakai elemen terakhir
			if ($last_nonempty_idx < 0) $last_nonempty_idx = count($arr_new) - 1;
		}

		// ===== Hitung summary (fallback ke hari terakhir yang ada datanya) =====
		$views = 0; $cpm = 0; $engagement = 0; $cost = 0; $endorse_cnt = 0;

		if (!empty($arr_new)) {
			if ($checkbox[0] == 'false') {
				// Mode kumulatif → ambil hari terakhir yang non-empty
				$last_day_data = $arr_new[$last_nonempty_idx];
				$views       = (float)$last_day_data['val_1'];
				$engagement  = (float)$last_day_data['val_3'];
				$endorse_cnt = (float)$last_day_data['val_5'];

				// === NEW: cost & cpm summary pakai SUM(endorse.total_cost)
				$cost = $total_cost_from_endorse;
				$cpm  = ($views > 0) ? ($cost / $views) * 1000 : 0;

			} else {
				// Mode selisih (D-1)
				$ref = $arr_new[$last_nonempty_idx];
				$views       = (float)$ref['val_1'] - (float)$baseline_views;
				$engagement  = (float)$ref['val_3'] - (float)$baseline_eng;
				$endorse_cnt = (float)$ref['val_5'] - (float)$baseline_end;

				// === NEW: pada mode selisih, summary cost tetap total dari endorse
				$cost = $total_cost_from_endorse;
				$cpm  = ($views > 0) ? ($cost / $views) * 1000 : 0;
			}
		}


		$html['summary']['query']        = $this->db->last_query();
		$html['summary']['views']        = $this->template->separator_only($views);
		$html['summary']['cpm']          = $this->template->separator_only($cpm);
		$html['summary']['engagement']   = $this->template->separator_only($engagement);
		$html['summary']['cost']         = $this->template->separator_only($cost);
		$html['summary']['endorse']      = $this->template->separator_only($endorse_fyp) . '/' . $this->template->separator_only($endorse);
		$html['summary']['influencer']   = $this->template->separator_only($influencer);
		$html['summary']['total_konten'] = $this->template->separator_only($endorse_cnt);


		// ===== Setup sumbu primer / sekunder =====
		$enabled_metrics = array();
		if ($checkbox[1] == 'true' && !empty($val_arr_1)) $enabled_metrics[] = array('name' => 'views',      'max' => max($val_arr_1), 'data' => $a);
		if ($checkbox[2] == 'true' && !empty($val_arr_2)) $enabled_metrics[] = array('name' => 'cpm',        'max' => max($val_arr_2), 'data' => $b);
		if ($checkbox[3] == 'true' && !empty($val_arr_3)) $enabled_metrics[] = array('name' => 'engagement', 'max' => max($val_arr_3), 'data' => $c);
		if ($checkbox[4] == 'true' && !empty($val_arr_4)) $enabled_metrics[] = array('name' => 'cost',       'max' => max($val_arr_4), 'data' => $d);
		if ($checkbox[5] == 'true' && !empty($val_arr_5)) $enabled_metrics[] = array('name' => 'endorse',    'max' => max($val_arr_5), 'data' => $e);

		$use_dual_axis     = false;
		$primary_max       = 0;
		$secondary_max     = 0;
		$primary_metrics   = array();
		$secondary_metrics = array();

		if (count($enabled_metrics) > 1) {
			$max_values  = array_map(function($m){ return $m['max']; }, $enabled_metrics);
			$overall_max = max($max_values);
			$overall_min = min($max_values);
			if ($overall_max > 0 && $overall_min > 0 && ($overall_max / $overall_min) >= 10) {
				$use_dual_axis = true;
				$threshold = $overall_max / 5;
				foreach ($enabled_metrics as $metric) {
					if ($metric['max'] >= $threshold) {
						$primary_metrics[] = $metric;
						if ($metric['max'] > $primary_max) $primary_max = $metric['max'];
					} else {
						$secondary_metrics[] = $metric;
						if ($metric['max'] > $secondary_max) $secondary_max = $metric['max'];
					}
				}
			}
		}
		if (!$use_dual_axis) {
			foreach ($enabled_metrics as $metric) {
				if ($metric['max'] > $primary_max) $primary_max = $metric['max'];
			}
		}

		$primary_max   = $this->calculateChartMax($primary_max);
		if ($use_dual_axis) $secondary_max = $this->calculateChartMax($secondary_max);

		$key = 'get_chart_campaign_' . date("YmdHis") . rand(1000000000, 9999999999);

		$datasets   = '';
		$table_rows = '';
		$color_index = 0;

		// Views
		if ($checkbox[1] == 'true' && !empty($a)) {
			$axis_id = 'primary';
			if ($use_dual_axis) foreach ($secondary_metrics as $sm) if ($sm['name'] === 'views') { $axis_id = 'secondary'; break; }
			$datasets   .= $this->buildChartDataset('Views', $a, $color_index + 1, $axis_id);
			$table_rows .= $this->buildTableRow('Views', $td_1, $this->template->hex($color_index));
			$color_index++;
		}
		// CPM
		if ($checkbox[2] == 'true' && !empty($b)) {
			$axis_id = 'primary';
			if ($use_dual_axis) foreach ($secondary_metrics as $sm) if ($sm['name'] === 'cpm') { $axis_id = 'secondary'; break; }
			$datasets   .= $this->buildChartDataset('CPM', $b, $color_index + 1, $axis_id);
			$table_rows .= $this->buildTableRow('CPM', $td_2, $this->template->hex($color_index));
			$color_index++;
		}
		// Engagement
		if ($checkbox[3] == 'true' && !empty($c)) {
			$axis_id = 'primary';
			if ($use_dual_axis) foreach ($secondary_metrics as $sm) if ($sm['name'] === 'engagement') { $axis_id = 'secondary'; break; }
			$datasets   .= $this->buildChartDataset('Engagement', $c, $color_index + 1, $axis_id);
			$table_rows .= $this->buildTableRow('Engagement', $td_3, $this->template->hex($color_index));
			$color_index++;
		}
		// Cost
		if ($checkbox[4] == 'true' && !empty($d)) {
			$axis_id = 'primary';
			if ($use_dual_axis) foreach ($secondary_metrics as $sm) if ($sm['name'] === 'cost') { $axis_id = 'secondary'; break; }
			$datasets   .= $this->buildChartDataset('Cost', $d, $color_index + 1, $axis_id);
			$table_rows .= $this->buildTableRow('Cost', $td_4, $this->template->hex($color_index));
			$color_index++;
		}
		// Endorse
		if ($checkbox[5] == 'true' && !empty($e)) {
			$axis_id = 'primary';
			if ($use_dual_axis) foreach ($secondary_metrics as $sm) if ($sm['name'] === 'endorse') { $axis_id = 'secondary'; break; }
			$datasets   .= $this->buildChartDataset('Endorse', $e, $color_index + 1, $axis_id);
			$table_rows .= $this->buildTableRow('Endorse', $td_5, $this->template->hex($color_index));
			$color_index++;
		}

		$datasets = rtrim($datasets, ',');

		$scales_config = $this->buildScalesConfig($use_dual_axis, $primary_max, $secondary_max);

		$html['html'] = '
			<canvas class="chart" id="' . $key . '"></canvas>
			<script>
			const ' . $key . ' = document.getElementById("' . $key . '").getContext("2d");

			var gradient_1 = ' . $key . '.createLinearGradient(0,0,0,' . $key . '.canvas.clientHeight)
			gradient_1.addColorStop(0, "' . $this->template->hex_to_rgb($this->template->hex(0)) . '")
			gradient_1.addColorStop(0.75, "rgba(225, 225, 225, 0)")

			var gradient_2 = ' . $key . '.createLinearGradient(0,0,0,' . $key . '.canvas.clientHeight)
			gradient_2.addColorStop(0, "' . $this->template->hex_to_rgb($this->template->hex(1)) . '")
			gradient_2.addColorStop(0.75, "rgba(225, 225, 225, 0)")

			var gradient_3 = ' . $key . '.createLinearGradient(0,0,0,' . $key . '.canvas.clientHeight)
			gradient_3.addColorStop(0, "' . $this->template->hex_to_rgb($this->template->hex(2)) . '")
			gradient_3.addColorStop(0.75, "rgba(225, 225, 225, 0)")

			var gradient_4 = ' . $key . '.createLinearGradient(0,0,0,' . $key . '.canvas.clientHeight)
			gradient_4.addColorStop(0, "' . $this->template->hex_to_rgb($this->template->hex(3)) . '")
			gradient_4.addColorStop(0.75, "rgba(225, 225, 225, 0)")

			var gradient_5 = ' . $key . '.createLinearGradient(0,0,0,' . $key . '.canvas.clientHeight)
			gradient_5.addColorStop(0, "' . $this->template->hex_to_rgb($this->template->hex(4)) . '")
			gradient_5.addColorStop(0.75, "rgba(225, 225, 225, 0)")

			new Chart(' . $key . ', {
				type: "line",
				data: {
					datasets: [' . $datasets . '],
					labels: [' . rtrim($opt, ',') . ']
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					aspectRatio: 3.1,
					interaction: {
						mode: "index",
						intersect: false,
					},
					plugins: {
						legend: { 
							display: false,
							labels: { font: { size: 8 } }
						},
					},
					stacked: false,
					scales: {
						x: {
							ticks: {
								autoSkip: true,
								maxTicksLimit: 10,
								font: { size: 11 }
							},
							grid: { display: false }
						},
						' . $scales_config . '
					},
				},
			});
			</script>';

		$html['table'] = '
			<div class="table-responsive">
				<table class="table table-bordered table-stats" style="margin-bottom:0px!important">
					<tr>
						<th class="text-start" style="font-size:12px!important">#</th>
						' . $th_table . '
					</tr>
					' . $table_rows . '
				</table>
			</div>';

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($html, true);
	}


	private function calculateChartMax($max_value) {
		if ($max_value <= 0) {
			return 100;
		}
		
		$max_1 = intval($max_value);
		
		if ($max_1 > 10000000) {
			$max_1 = ceil($max_1 / 10000000) * 10000000 * 1.2;
		} else if ($max_1 > 1000000) {
			$max_1 = ceil($max_1 / 1000000) * 1000000 * 1.2;
		} else if ($max_1 > 100000) {
			$max_1 = ceil($max_1 / 100000) * 100000 * 1.2;
		} else if ($max_1 > 10000) {
			$max_1 = ceil($max_1 / 10000) * 10000 * 1.2;
		} else if ($max_1 > 1000) {
			$max_1 = ceil($max_1 / 1000) * 1000 * 1.2;
		} else if ($max_1 > 100) {
			$max_1 = ceil($max_1 / 100) * 100 * 1.2;
		} else if ($max_1 > 10) {
			$max_1 = ceil($max_1 / 10) * 10 * 1.2;
		} else {
			$max_1 = ceil($max_1) * 1.2;
		}
		
		return intval($max_1);
	}

	private function buildChartDataset($label, $data, $gradient_index, $axis_id) {
		$axis_key = $axis_id === 'primary' ? 'y' : 'y1';
		
		$data = rtrim($data, ',');
		
		return '{
			type: "line",
			label: "' . $label . '",
			fill: "start",
			backgroundColor: gradient_' . $gradient_index . ',
			borderColor: ["' . $this->template->hex($gradient_index - 1) . '"],
			borderWidth: 2,
			pointRadius: 0,
			pointHoverRadius: 0,
			cubicInterpolationMode: "monotone",
			data: [' . $data . '],
			yAxisID: "' . $axis_key . '",
		},';
	}

	private function buildTableRow($label, $data, $color) {
		return '<tr>
			<td class="text-start"> 
				<div class="d-flex justify-content-start">
					<div style="background-color: ' . $color . '; width: 7px; height: 7px;margin-right:5px;margin-top:4px">
					</div>' . $label . '
				</div>
			</td>
			' . $data . '
		</tr>';
	}

	private function buildScalesConfig($use_dual_axis, $primary_max, $secondary_max) {
		if ($use_dual_axis) {
			return "
			y: {
				type: 'linear',
				display: true,
				position: 'left',
				min: 0,
				max: $primary_max,
				ticks: {
					autoSkip: false,
					font: {
						size: 8,
					},
					callback: function(value, index, values) {
						return value.toLocaleString();
					}
				},
				grid: {
					drawBorder: false
				}
			},
			y1: {
				type: 'linear',
				display: true,
				position: 'right',
				min: 0,
				max: $secondary_max,
				ticks: {
					autoSkip: false,
					font: {
						size: 8,
					},
					callback: function(value, index, values) {
						return value.toLocaleString();
					}
				},
				grid: {
					drawOnChartArea: false,
					drawBorder: false
				}
			}";
		} else {
			return "
			y: {
				type: 'linear',
				display: true,
				position: 'left',
				min: 0,
				max: $primary_max,
				ticks: {
					autoSkip: false,
					font: {
						size: 8,
					},
					callback: function(value, index, values) {
						return value.toLocaleString();
					}
				},
				grid: {
					drawBorder: false
				}
			}";
		}
	}

	function get_chart_endorse()
	{

		$checkbox = $_SESSION['checkbox'];

		$skip = 0;
		for ($i = 1; $i <= 5; $i++) {
			if ($checkbox[$i] == 'false') {
				$skip++;
			}
		}
		if ($skip >= 7) {
			$html['html'] = '<i>Pastikan memilih minimal 1 filter!</i>';
			$html['table'] = '';
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode($html, true);
			die;
		}


		$id = $_GET['id'];
		$type = $_GET['type'];
		$start_date = $_GET['start_date'];
		$until_date = $_GET['until_date'];
		$start_year = $_GET['start_year'];
		$until_year = $_GET['until_year'];
		$start_month = $_GET['start_month'];
		$until_month = $_GET['until_month'];
		$start_week = $_GET['start_week'];
		$until_week = $_GET['until_week'];
		$site = $_GET['site'];
		$customer = $_GET['customer'];
		$mpu = $_GET['mpu'];
		$platform = $_GET['platform'];
		$qry = '';
		if ($type == "Yearly") {
			$qry_opt = " YEAR(date) ";
			$start_date = $start_year . '-01-01';
			$until_date = $until_year . '-12-31';
			$group = "  GROUP BY YEAR(date) ";
		} else if ($type == "Monthly") {
			$qry_opt = " MONTH(date) ";
			$start_month = str_pad($start_month, 2, "0", STR_PAD_LEFT);
			$until_month = str_pad($until_month, 2, "0", STR_PAD_LEFT);
			$start_date = $start_year . '-' . $start_month . '-01';
			$until_date = $start_year . '-' . $until_month . '-31';
			$group = "  GROUP BY MONTH(date) ";
		} else if ($type == "Weekly") {
			$qry_opt = " WEEK(date) ";
			$start_week = str_pad($start_week, 2, "0", STR_PAD_LEFT);
			$until_week = str_pad($until_week, 2, "0", STR_PAD_LEFT);

			$year = $start_year;
			$week = $start_week;
			$start_date = date("Y-m-d", strtotime($year . "W" . $week . "1"));

			$year = $start_year;
			$week = $until_week;
			$until_date = date("Y-m-d", strtotime($year . "W" . $week . "7"));
			$group = "  GROUP BY WEEK(date) ";
		} else {
			$qry_opt = " DATE(date) ";
			$group = "  GROUP BY DATE(date) ";
		}


		if ($checkbox[0] != 'true') {
			$list = $this->mymodel->selectWithQuery("SELECT likes_after as likes, comment_after as comment,share_save_after as share_save, views_after as views,
			SUM(total_cost) as cpm,
			$qry_opt as opt 
			FROM endorse_logs
			WHERE id_endorse = '$id' $qry $group
			");
		} else {
			$list = $this->mymodel->selectWithQuery("SELECT likes, comment,share_save, views,
			SUM(total_cost) as cpm,
			$qry_opt as opt 
			FROM endorse_logs
			WHERE id_endorse = '$id' $qry $group
			");
		}



		$arr = array();

		$tmp_likes = 0;
		$tmp_comment = 0;
		$tmp_share_save = 0;
		$tmp_views = 0;
		$tmp_cpm = 0;

		if ($type == "Yearly") {
			$range = array();
			for ($i = $start_year; $i <= $until_year; $i++) {
				$range[] = intval($i);
			}
			foreach ($range as $k2 => $v2) {

				$val_1 = 0;
				$val_2 = 0;
				$val_3 = 0;
				$val_4 = 0;
				$val_5 = 0;

				foreach ($list as $k => $v) {
					if ($v['opt'] == $v2) {
						if ($v['likes'] > 0) {
							$tmp_likes = $v['likes'];
						}
						if ($v['comment'] > 0) {
							$tmp_comment = $v['comment'];
						}
						if ($v['share_save'] > 0) {
							$tmp_share_save = $v['share_save'];
						}
						if ($v['views'] > 0) {
							$tmp_views = $v['views'];
						}
						if ($v['cpm'] > 0) {
							$tmp_cpm = $v['cpm'];
						}
						$val_1 += $v['likes'];
						$val_2 += $v['comment'];
						$val_3 += $v['share_save'];
						$val_4 += $v['views'];
						$val_5 += $v['cpm'];
					}
				}
				if ($tmp_likes > 0 && $val_1 == 0) {
					$val_1 = $tmp_likes;
				}
				if ($tmp_comment > 0 && $val_2 == 0) {
					$val_2 = $tmp_comment;
				}
				if ($tmp_share_save > 0 && $val_3 == 0) {
					$val_3 = $tmp_share_save;
				}
				if ($tmp_views > 0 && $val_4 == 0) {
					$val_4 = $tmp_views;
				}
				if ($tmp_cpm > 0 && $val_5 == 0) {
					$val_5 = $tmp_cpm;
				}

				$i = $k2;
				// $v['opt'] = substr($v2, -2);
				$v['opt'] = $v2;
				$arr[$i]['opt'] = $v['opt'];
				$arr[$i]['val_1'] = $val_1;
				$arr[$i]['val_1'] = round($arr[$i]['val_1'], 2);
				$arr[$i]['val_2'] = $val_2;
				$arr[$i]['val_2'] = round($arr[$i]['val_2'], 2);
				$arr[$i]['val_3'] = $val_3;
				$arr[$i]['val_3'] = round($arr[$i]['val_3'], 2);
				$arr[$i]['val_4'] = $val_4;
				$arr[$i]['val_4'] = round($arr[$i]['val_4'], 2);
				$arr[$i]['val_5'] = $val_5;
				$arr[$i]['val_5'] = round($arr[$i]['val_5'], 2);
			}
		} else if ($type == "Monthly") {
			$range = array();
			for ($i = $start_month; $i <= $until_month; $i++) {
				$range[] = intval($i);
			}
			foreach ($range as $k2 => $v2) {


				$val_1 = 0;
				$val_2 = 0;
				$val_3 = 0;
				$val_4 = 0;
				$val_5 = 0;

				foreach ($list as $k => $v) {
					if ($v['opt'] == $v2) {
						if ($v['likes'] > 0) {
							$tmp_likes = $v['likes'];
						}
						if ($v['comment'] > 0) {
							$tmp_comment = $v['comment'];
						}
						if ($v['share_save'] > 0) {
							$tmp_share_save = $v['share_save'];
						}
						if ($v['views'] > 0) {
							$tmp_views = $v['views'];
						}
						if ($v['cpm'] > 0) {
							$tmp_cpm = $v['cpm'];
						}
						$val_1 += $v['likes'];
						$val_2 += $v['comment'];
						$val_3 += $v['share_save'];
						$val_4 += $v['views'];
						$val_5 += $v['cpm'];
					}
				}
				if ($tmp_likes > 0 && $val_1 == 0) {
					$val_1 = $tmp_likes;
				}
				if ($tmp_comment > 0 && $val_2 == 0) {
					$val_2 = $tmp_comment;
				}
				if ($tmp_share_save > 0 && $val_3 == 0) {
					$val_3 = $tmp_share_save;
				}
				if ($tmp_views > 0 && $val_4 == 0) {
					$val_4 = $tmp_views;
				}
				if ($tmp_cpm > 0 && $val_5 == 0) {
					$val_5 = $tmp_cpm;
				}

				$i = $k2;
				$v['opt'] = substr($v2, -2);
				$arr[$i]['opt'] = $v['opt'];
				$arr[$i]['val_1'] = $val_1;
				$arr[$i]['val_1'] = round($arr[$i]['val_1'], 2);
				$arr[$i]['val_2'] = $val_2;
				$arr[$i]['val_2'] = round($arr[$i]['val_2'], 2);
				$arr[$i]['val_3'] = $val_3;
				$arr[$i]['val_3'] = round($arr[$i]['val_3'], 2);
				$arr[$i]['val_4'] = $val_4;
				$arr[$i]['val_4'] = round($arr[$i]['val_4'], 2);
				$arr[$i]['val_5'] = $val_5;
				$arr[$i]['val_5'] = round($arr[$i]['val_5'], 2);
			}
		} else if ($type == "Weekly") {
			$range = array();
			for ($i = $start_week; $i <= $until_week; $i++) {
				$range[] = intval($i);
			}

			foreach ($range as $k2 => $v2) {


				$val_1 = 0;
				$val_2 = 0;
				$val_3 = 0;
				$val_4 = 0;
				$val_5 = 0;

				foreach ($list as $k => $v) {
					if ($v['opt'] == $v2) {
						if ($v['likes'] > 0) {
							$tmp_likes = $v['likes'];
						}
						if ($v['comment'] > 0) {
							$tmp_comment = $v['comment'];
						}
						if ($v['share_save'] > 0) {
							$tmp_share_save = $v['share_save'];
						}
						if ($v['views'] > 0) {
							$tmp_views = $v['views'];
						}
						if ($v['cpm'] > 0) {
							$tmp_cpm = $v['cpm'];
						}
						$val_1 += $v['likes'];
						$val_2 += $v['comment'];
						$val_3 += $v['share_save'];
						$val_4 += $v['views'];
						$val_5 += $v['cpm'];
					}
				}
				if ($tmp_likes > 0 && $val_1 == 0) {
					$val_1 = $tmp_likes;
				}
				if ($tmp_comment > 0 && $val_2 == 0) {
					$val_2 = $tmp_comment;
				}
				if ($tmp_share_save > 0 && $val_3 == 0) {
					$val_3 = $tmp_share_save;
				}
				if ($tmp_views > 0 && $val_4 == 0) {
					$val_4 = $tmp_views;
				}
				if ($tmp_cpm > 0 && $val_5 == 0) {
					$val_5 = $tmp_cpm;
				}

				$i = $k2;
				$v['opt'] = substr($v2, -2);
				$arr[$i]['opt'] = $v['opt'];
				$arr[$i]['val_1'] = $val_1;
				$arr[$i]['val_1'] = round($arr[$i]['val_1'], 2);
				$arr[$i]['val_2'] = $val_2;
				$arr[$i]['val_2'] = round($arr[$i]['val_2'], 2);
				$arr[$i]['val_3'] = $val_3;
				$arr[$i]['val_3'] = round($arr[$i]['val_3'], 2);
				$arr[$i]['val_4'] = $val_4;
				$arr[$i]['val_4'] = round($arr[$i]['val_4'], 2);
				$arr[$i]['val_5'] = $val_5;
				$arr[$i]['val_5'] = round($arr[$i]['val_5'], 2);
			}
		} else {
			$range = ($this->createRange($start_date, $until_date));
			foreach ($range as $k2 => $v2) {

				$val_1 = 0;
				$val_2 = 0;
				$val_3 = 0;
				$val_4 = 0;
				$val_5 = 0;

				foreach ($list as $k => $v) {
					if ($v['opt'] == $v2) {
						if ($v['likes'] > 0) {
							$tmp_likes = $v['likes'];
						}
						if ($v['comment'] > 0) {
							$tmp_comment = $v['comment'];
						}
						if ($v['share_save'] > 0) {
							$tmp_share_save = $v['share_save'];
						}
						if ($v['views'] > 0) {
							$tmp_views = $v['views'];
						}
						if ($v['cpm'] > 0) {
							$tmp_cpm = $v['cpm'];
						}
						$val_1 += $v['likes'];
						$val_2 += $v['comment'];
						$val_3 += $v['share_save'];
						$val_4 += $v['views'];
						$val_5 += $v['cpm'];
					}
				}
				if ($tmp_likes > 0 && $val_1 == 0) {
					$val_1 = $tmp_likes;
				}
				if ($tmp_comment > 0 && $val_2 == 0) {
					$val_2 = $tmp_comment;
				}
				if ($tmp_share_save > 0 && $val_3 == 0) {
					$val_3 = $tmp_share_save;
				}
				if ($tmp_views > 0 && $val_4 == 0) {
					$val_4 = $tmp_views;
				}
				if ($tmp_cpm > 0 && $val_5 == 0) {
					$val_5 = $tmp_cpm;
				}

				$i = $k2;
				$v['opt'] = DATE("d M Y", strtotime($v2));
				$arr[$i]['opt'] = $v['opt'];
				$arr[$i]['val_1'] = $val_1;
				$arr[$i]['val_1'] = round($arr[$i]['val_1'], 2);
				$arr[$i]['val_2'] = $val_2;
				$arr[$i]['val_2'] = round($arr[$i]['val_2'], 2);
				$arr[$i]['val_3'] = $val_3;
				$arr[$i]['val_3'] = round($arr[$i]['val_3'], 2);
				$arr[$i]['val_4'] = $val_4;
				$arr[$i]['val_4'] = round($arr[$i]['val_4'], 2);
				$arr[$i]['val_5'] = $val_5;
				$arr[$i]['val_5'] = round($arr[$i]['val_5'], 2);
			}
		}


		$arr_new = array();

		$count = count($arr);

		if ($type == "Yearly") {
			for ($i = 0; $i < $count; $i++) {
				$arr_new[$i] = $arr[$i];
			};
		} else if ($type == "Monthly") {
			for ($i = 0; $i < $count; $i++) {
				$arr_new[$i] = $arr[$i];
			};
		} else if ($type == "Weekly") {
			for ($i = 0; $i < $count; $i++) {
				$arr_new[$i] = $arr[$i];
			};
		} else {
			for ($i = 0; $i < $count; $i++) {
				$arr_new[$i] = $arr[$i];
			};
		}

		$th_table = "";
		$td_1 = "";
		$td_2 = "";
		$td_3 = "";
		$td_4 = "";
		$td_5 = "";

		$opt = "";
		$val = "";
		$a = "";
		$b = "";
		$c = "";
		$d = "";
		$e = "";
		$color = "";
		$val_arr_1 = array(0, 0);
		$val_arr_2 = array(0, 0);
		$val_arr_3 = array(0, 0);
		$val_arr_4 = array(0, 0);
		$val_arr_5 = array(0, 0);
		foreach ($arr_new as $k => $v) {

			if ($v['val_5'] > 0 &&  $v['val_4'] > 0) {
				$v['val_5'] = $v['val_5'] / $v['val_4'] * 1000;
			} else {
				$v['val_5'] = 0;
			}


			$opt .= "'" . $v['opt'] . "',";
			if ($v) {
				$a .= "'" . $this->template->separator_number_only($v['val_1']) . "',";
				$b .= "'" . $this->template->separator_number_only($v['val_2']) . "',";
				$c .= "'" . $this->template->separator_number_only($v['val_3']) . "',";
				$d .= "'" . $this->template->separator_number_only($v['val_4']) . "',";
				$e .= "'" . $this->template->separator_number_only($v['val_5']) . "',";

				$val_arr_1[] = round($v['val_4']);
				$val_arr_2[] = round($v['val_5']);
				$val_arr_3[] = round($v['val_1']);
				$val_arr_4[] = round($v['val_2']);
				$val_arr_5[] = round($v['val_3']);
			}
			if ($v['opt']) {
				$th_table .= "<th  style='font-size:12px!important'>" . $v['opt'] . "</th>";
				$td_1 .= "<td>" . $this->template->separator_only((($v['val_1']))) . "</td>";
				$td_2 .= "<td>" . $this->template->separator_only((($v['val_2']))) . "</td>";
				$td_3 .= "<td>" . $this->template->separator_only((($v['val_3']))) . "</td>";
				$td_4 .= "<td>" . $this->template->separator_only((($v['val_4']))) . "</td>";
				$td_5 .= "<td>" . $this->template->separator_only((($v['val_5']))) . "</td>";
			}

			$color .= "'" . $v['color'] . "',";
		}


		$min_1 = min($val_arr_1);
		$max_1 = max($val_arr_1);
		$min_2 = min($val_arr_2);
		$max_2 = max($val_arr_2);
		$min_3 = min($val_arr_3);
		$max_3 = max($val_arr_3);
		$min_4 = min($val_arr_4);
		$max_4 = max($val_arr_4);
		$min_5 = min($val_arr_5);
		$max_5 = max($val_arr_5);

		$min_1 = 0;
		$min_2 = 0;
		if ($checkbox[1] == 'true') {
			$max_1 = $max_1;
		} else {
			$max_1 = 0;
		}
		if ($checkbox[2] == 'true') {
			if ($max_2 > $max_1) {
				$max_1 = $max_2;
			}
		}
		if ($checkbox[3] == 'true') {
			if ($max_3 > $max_1) {
				$max_1 = $max_3;
			}
		}
		if ($checkbox[4] == 'true') {
			if ($max_4 > $max_1) {
				$max_1 = $max_4;
			}
		}
		if ($checkbox[5] == 'true') {
			if ($max_5 > $max_1) {
				$max_1 = $max_5;
			}
		}

		$max_1 = intval($max_1);

		if ($max_1 > 10000000) {
			$max_1 =  ($max_1 - ($max_1 % 10000000)) * 2.2;
		} else if ($max_1 > 1000000) {
			$max_1 =  ($max_1 - ($max_1 % 1000000)) * 2.2;
		} else if ($max_1 > 100000) {
			$max_1 =  ($max_1 - ($max_1 % 100000)) * 2.2;
		} else if ($max_1 > 10000) {
			$max_1 =  ($max_1 - ($max_1 % 10000)) * 2.2;
		} else if ($max_1 > 1000) {
			$max_1 =  ($max_1 - ($max_1 % 1000)) * 2.2;
		} else if ($max_1 > 100) {
			$max_1 =  ($max_1 - ($max_1 % 100)) * 2.2;
		} else if ($max_1 > 10) {
			$max_1 =  ($max_1 - ($max_1 % 10)) * 2.2;
		} else if ($max_1 > 0) {
			$max_1 =  ($max_1 - ($max_1 % 1)) * 2.2;
		}
		// echo $max_1;die;

		$item_1 = '';
		$item_2 = '';
		$item_3 = '';

		if ($checkbox[1] == 'true') {
			$item_1 .= '
			{
				type: "line",
				label: " Views ",
				fill: "start",
    			backgroundColor: gradient_4,
				borderColor: ["' . $this->template->hex(3) . '"],
				borderWidth: 2, pointRadius: 0, cubicInterpolationMode: "monotone",
				data: [' . $d . '],
				yAxisID: "l4",
			},	
			';
			$item_3 .= '
			<tr>
													<td class="text-start"> 
														<div class="d-flex justify-content-start">
															<div style="background-color: ' . $this->template->hex(3) . '; width: 7px; height: 7px;margin-right:5px;margin-top:4px">
															</div>Views
														</div>
													</td>
													' . $td_4 . '
													</tr>
			';
		}
		if ($checkbox[2] == 'true') {
			$item_1 .= '
			{
				type: "line",
				label: " CPM ",
				fill: "start",
    			backgroundColor: gradient_5,
				borderColor: ["' . $this->template->hex(4) . '"],
				borderWidth: 2, pointRadius: 0, cubicInterpolationMode: "monotone",
				data: [' . $e . '],
				yAxisID: "l5",
			},
			';
			$item_3 .= '
			
			<tr>
			<td class="text-start"> 
				<div class="d-flex justify-content-start">
					<div style="background-color: ' . $this->template->hex(4) . '; width: 7px; height: 7px;margin-right:5px;margin-top:4px">
					</div>CPM
				</div>
			</td>
			' . $td_5 . '
			</tr>
			';
		}

		if ($checkbox[3] == 'true') {
			$item_1 .= '
			{
				type: "line",
				label: " Likes ",
				fill: "start",
    			backgroundColor: gradient_1,
				borderColor: ["' . $this->template->hex(0) . '"],
				borderWidth: 2, pointRadius: 0, cubicInterpolationMode: "monotone",
				data: [' . $a . '],
				yAxisID: "l1",
			},
			';
			$item_3 .= '
			<tr>
													<td class="text-start"> 
														<div class="d-flex justify-content-start">
															<div style="background-color: ' . $this->template->hex(0) . '; width: 7px; height: 7px;margin-right:5px;margin-top:4px">
															</div>Likes
														</div>
													</td>
													' . $td_1 . '
													</tr>
													';
		}
		if ($checkbox[4] == 'true') {
			$item_1 .= '
			{
				type: "line",
				label: " Comments ",
				fill: "start",
    			backgroundColor: gradient_2,
				borderColor: ["' . $this->template->hex(1) . '"],
				borderWidth: 2, pointRadius: 0, cubicInterpolationMode: "monotone",
				data: [' . $b . '],
				yAxisID: "l2",
			},
			';
			$item_3 .= '
			<tr>
													<td class="text-start"> 
														<div class="d-flex justify-content-start">
															<div style="background-color: ' . $this->template->hex(1) . '; width: 7px; height: 7px;margin-right:5px;margin-top:4px">
															</div>Comments
														</div>
													</td>
													' . $td_2 . '
													</tr>
			';
		}
		if ($checkbox[5] == 'true') {
			$item_1 .= '
			{
				type: "line",
				label: " Share & Save ",
				fill: "start",
    			backgroundColor: gradient_3,
				borderColor: ["' . $this->template->hex(2) . '"],
				borderWidth: 2, pointRadius: 0, cubicInterpolationMode: "monotone",
				data: [' . $c . '],
				yAxisID: "l3",
			},	
			';
			$item_3 .= '
			<tr>
													<td class="text-start"> 
														<div class="d-flex justify-content-start">
															<div style="background-color: ' . $this->template->hex(2) . '; width: 7px; height: 7px;margin-right:5px;margin-top:4px">
															</div>Share & Save
														</div>
													</td>
													' . $td_3 . '
													</tr>
			';
		}


		$key = 'get_chart_campaign_' . DATE("Ymdhis") . rand(1000000000, 9999999999);

		$html['html'] = '
                                                    <canvas class="chart" id="' . $key . '"></canvas>
                                                    <script>
													const ' . $key . ' = document.getElementById(
                                                        "' . $key . '").getContext("2d");


var gradient_1 = ' . $key . '.createLinearGradient(0,0,0,' . $key . '.canvas.clientHeight)
gradient_1.addColorStop(0, "' . $this->template->hex_to_rgb($this->template->hex(0)) . '")
gradient_1.addColorStop(0.75, "rgba(225, 225, 225, 0)")

var gradient_2 = ' . $key . '.createLinearGradient(0,0,0,' . $key . '.canvas.clientHeight)
gradient_2.addColorStop(0, "' . $this->template->hex_to_rgb($this->template->hex(1)) . '")
gradient_2.addColorStop(0.75, "rgba(225, 225, 225, 0)")

var gradient_3 = ' . $key . '.createLinearGradient(0,0,0,' . $key . '.canvas.clientHeight)
gradient_3.addColorStop(0, "' . $this->template->hex_to_rgb($this->template->hex(2)) . '")
gradient_3.addColorStop(0.75, "rgba(225, 225, 225, 0)")

var gradient_4 = ' . $key . '.createLinearGradient(0,0,0,' . $key . '.canvas.clientHeight)
gradient_4.addColorStop(0, "' . $this->template->hex_to_rgb($this->template->hex(3)) . '")
gradient_4.addColorStop(0.75, "rgba(225, 225, 225, 0)")

var gradient_5 = ' . $key . '.createLinearGradient(0,0,0,' . $key . '.canvas.clientHeight)
gradient_5.addColorStop(0, "' . $this->template->hex_to_rgb($this->template->hex(4)) . '")
gradient_5.addColorStop(0.75, "rgba(225, 225, 225, 0)")

var gradient_6 = ' . $key . '.createLinearGradient(0,0,0,' . $key . '.canvas.clientHeight)
gradient_6.addColorStop(0, "' . $this->template->hex_to_rgb($this->template->hex(5)) . '")
gradient_6.addColorStop(0.75, "rgba(225, 225, 225, 0)")

                                                    new Chart(' . $key . ', {
                                                        type: "line",
                                                        data: {
                                                            datasets: [
                                                                ' . $item_1 . '									
                                                            ],
                                                            labels: [' . $opt . ']
                                                        },
                                                        options: {
															responsive: true,
															maintainAspectRatio: false, 
															aspectRatio: 3.1, 
															interaction: {
															mode: "index",
															intersect: false,
														},
														plugins:{
															legend: { 
																display:false,
																labels: {
																  font: {
																	size: 8
																  }
																}
															},
														},
														stacked: false,
														
														scales: {
														x:{
															ticks: {												
																autoSkip: true,	
																maxTicksLimit: 10,											
																font: {													
																	size: 11,												
																}											
															},
															grid: {
																display: false,
															}
														},
														l1: {
															type: "linear",
															display: true,
															position: "left",
															min:0,
															max:' . $max_1 . ',
															ticks: {												
																autoSkip: false,												
																font: {													
																	size: 8,												
																}											
															}
														},
														l2: {
															type: "linear",
															display: false,
															position: "left",
															min:0,
															max:' . $max_1 . ',
														},
														l3: {
															type: "linear",
															display: false,
															position: "left",
															min:0,
															max:' . $max_1 . ',
														},
														l4: {
															type: "linear",
															display: false,
															position: "left",
															min:0,
															max:' . $max_1 . ',
														},
														l5: {
															type: "linear",
															display: false,
															position: "left",
															min:0,
															max:' . $max_1 . ',
														},
														},
														},

                                                    });
                                                    </script>';

		$html['table'] = '
													<div class="table-responsive">
													<table class="table able-bordered table-stats" style="margin-bottom:0px!important">
													<tr>
													<th class="text-start" style="font-size:12px!important">#</th>
													' . $th_table . '
													</tr>
													' . $item_3 . '
													</table>
													</div>
													';
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($html, true);
	}


	function get_report()
	{
		$code = $_GET['code'];
		$type = $_GET['type'];
		$start_date = $_GET['start_date'];
		$until_date = $_GET['until_date'];
		$start_year = $_GET['start_year'];
		$until_year = $_GET['until_year'];
		$start_month = $_GET['start_month'];
		$until_month = $_GET['until_month'];
		$start_week = $_GET['start_week'];
		$until_week = $_GET['until_week'];
		$brand = $_GET['brand'];

		if ($type == "Yearly") {
			$start_date = $start_year . '-01-01';
			$until_date = $until_year . '-12-31';
		} else if ($type == "Monthly") {
			$start_month = str_pad($start_month, 2, "0", STR_PAD_LEFT);
			$until_month = str_pad($until_month, 2, "0", STR_PAD_LEFT);
			$start_date = $start_year . '-' . $start_month . '-01';
			$until_date = $start_year . '-' . $until_month . '-31';
		} else if ($type == "Weekly") {
			$start_week = str_pad($start_week, 2, "0", STR_PAD_LEFT);
			$until_week = str_pad($until_week, 2, "0", STR_PAD_LEFT);

			$year = $start_year;
			$week = $start_week;
			$start_date = date("Y-m-d", strtotime($year . "W" . $week . "1"));

			$year = $start_year;
			$week = $until_week;
			$until_date = date("Y-m-d", strtotime($year . "W" . $week . "7"));
		}

		$qry = "";

		$qry .= " WHERE DATE(date) >= '$start_date' AND DATE(date) <= '$until_date' ";

		if ($brand) {
			$qry .= " AND brand = '$brand' ";
		}
		$channel = $_GET['channel'];
		if ($channel) {
			$qry .= " AND marketplace = '$channel' ";
		}

		if ($code == "1") {
			$query = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count,shipping, shipping as name FROM transaction $qry AND type_sub = 'POS' AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID')
			GROUP BY shipping
			ORDER BY count DESC
			");

			$query_3 = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count,shipping FROM transaction $qry AND type_sub = 'POS' AND order_status IN ('READY_TO_SHIP','PENDING')
			GROUP BY shipping");
			$arr_3 = array();
			foreach ($query_3 as $k => $v) {
				$arr_3[$v['shipping']] = $v;
			}

			$query_2 = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count,shipping FROM transaction $qry AND type_sub = 'POS' AND order_status IN ('PROCESSED')
			GROUP BY shipping");
			$arr_2 = array();
			foreach ($query_2 as $k => $v) {
				$arr_2[$v['shipping']] = $v;
			}
			$total_1 = 0;
			$total_2 = 0;
			$total_3 = 0;
			foreach ($query as $k => $v) {
				$text .= '
				<tr>
					<td class="text-center">' . ($k + 1) . '</td>
					<td class="text-start td-breakline">' . $v['shipping'] . '</td>
					<td class="text-end">' . $this->template->separator_only($v['count']) . '</td>
					<td class="text-end">' . $this->template->separator_only($arr_3[$v['name']]['count']) . '</td>
					<td class="text-end">' . $this->template->separator_only($arr_2[$v['name']]['count']) . '</td>
				</tr>
				';
				$total_1 += $v['count'];
				$total_2 += $arr_3[$v['name']]['count'];
				$total_3 += $arr_2[$v['name']]['count'];
			}
			$text = '<div class="table-responsive">
				<table class="table table-hover table-striped table-bordered">
					<tr class="bg-primary">
						<th class="text-center" style="width:20px!important">#</th>
						<th class="text-start">Ekspedisi</th>
						<th class="text-end" style="min-width:20px!important">Jumlah Order</th>
						<th class="text-end" style="min-width:20px!important">Menunggu Diproses</th>
						<th class="text-end" style="min-width:20px!important">Menunggu Dipickup</th>
					</tr>
					<tr>
						<th class="text-center">#</th>
						<th class="text-start td-breakline">Total</th>
						<th class="text-end">' . $this->template->separator_only($total_1) . '</th>
						<th class="text-end">' . $this->template->separator_only($total_2) . '</th>
						<th class="text-end">' . $this->template->separator_only($total_3) . '</th>
					</tr>
					
					' . $text . '
				</table></div>';
		} else if ($code == "1a") {
			$query = $this->mymodel->selectWithQuery("SELECT *, SUM(qty_out) as count FROM `stock_product_3rd`
			$qry
			GROUP BY varian_id, marketplace
			HAVING SUM(qty_out) > 0
			ORDER BY SUM(qty_out) DESC
			-- LIMIT 18
			");
			foreach ($query as $k => $v) {

				if ($v['marketplace'] == "SHOPEE") {
					$v['img'] = base_url() . '/assets/img/icon/icon-shopee.png';
				} else if ($v['marketplace'] == "LAZADA") {
					$v['img'] = base_url() . '/assets/img/icon/icon-lazada.png';
				} else if ($v['marketplace'] == "TIKTOK") {
					$v['img'] = base_url() . '/assets/img/icon/icon-tiktok.png';
				} else {
					$v['img'] = base_url() . '/assets/img/icon/icon-no.png';
				}

				$id_product = $v['product_id'];
				$marketplace = $v['marketplace'];
				$product = $this->mymodel->selectWithQuery("SELECT img
				FROM product_3rd
				WHERE id_product = '$id_product' AND marketplace = '$marketplace'");
				$product = $product[0];
				if ($product['img']) {
					$product['img'] = $product['img'];
				} else {
					$product['img'] = base_url() . '/assets/img/icon/icon-no.png';
				}

				if ($v['varian_text'] != $v['product_text']) {
					$v['varian_text'] = $v['product_text'] . '<hr class="mt-1 mb-1">' . $v['varian_text'];
				}
				$text .= '
				<tr>
					<td class="text-center">' . ($k + 1) . '</td>
					<td class="text-start td-breakline">' . $v['varian_sku'] . '</td>
					<td class="text-start td-breakline">
					<div class="row">
                        <div class="col-12" style="position:relative">
                        <div class="row">
                        <div class="firstDivImg">
                            <a href="' . $product['img'] . '" target="_blank"><img class="divIcon" src="' . $product['img'] . '" alt=""></a>
                        </div>
                        <div class="secondDivImg">
                            ' . $v['varian_text'] . '
                        </div>
                        </div>
                        </div>
                    </div>  
					</td>
					<td class="text-center"><img style="width:35px;border-radius:10px;" src="' . $v['img'] . '"></td>
					<td class="text-end">' . $this->template->separator_only($v['count']) . '</td>
				</tr>
				';
			}
			$text = '<div class="table-responsive">
				<table class="table table-hover table-striped table-bordered">
					<tr class="bg-primary">
						<th class="text-center" style="width:20px!important">#</th>
						<th class="text-start">SKU</th>
						<th class="text-start">Nama Produk</th>
						<th class="text-center">MP</th>
						<th class="text-end" style="min-width:20px!important">Qty</th>
					</tr>
					' . $text . '
				</table></div>';
		} else if ($code == "11") {
			$status = $_GET['status'] ?? '';
			$jenis  = $_GET['jenis'] ?? '';

			$statusFilter = "";
			$jenisFilter  = "";

			if ($status != '') {
				$statusFilter = " AND status = " . $this->db->escape($status) . " ";
			}

			if ($jenis != '') {
				if ($jenis == 'produk_jual') {
					$jenisFilter = " AND is_operational = 0 ";
				} else if ($jenis == 'produk_operasional') {
					$jenisFilter = " AND is_operational = 1 ";
				}
			}

			$query = $this->mymodel->selectWithQuery("
				SELECT a.*, 
					b.qty_in, b.qty_in_pos, b.qty_out, b.qty_out_pos, b.qty_out_retur, (b.qty + c.qty_retur_in - c.qty_retur_out) as qty,
					c.qty_retur
				FROM (
					SELECT * FROM product 
					WHERE 1=1 AND is_varian = 0 $statusFilter $jenisFilter
				) a 
				LEFT JOIN (
					SELECT s.product,
						SUM(s.qty_in)        AS qty_in,
						SUM(s.qty_in_pos)    AS qty_in_pos,
						SUM(s.qty_out)       AS qty_out,
						SUM(s.qty_out_pos)   AS qty_out_pos,
						SUM(s.qty_out_retur) AS qty_out_retur,
						SUM(s.qty_in + s.qty_in_pos - s.qty_out - s.qty_out_pos) AS qty
					FROM stock s
					$qry
					AND s.order_status NOT IN ('IN_CANCELLED','REFUND','CANCELLED','RETURN', 'RETURN_UNSHIPPED')
					GROUP BY s.product
				) b ON a.id = b.product
				LEFT JOIN (
					SELECT s.product,
						SUM(COALESCE(s.qty_in_pos,0)) as qty_retur_in,
						SUM(COALESCE(s.qty_out_retur,0)) as qty_retur_out,
						SUM(COALESCE(s.qty_in_pos,0) + COALESCE(s.qty_out_retur,0)) AS qty_retur
					FROM stock s
					$qry
					AND s.order_status LIKE '%RETURN%'
					GROUP BY s.product
				) c ON a.id = c.product
				ORDER BY b.qty_out_pos DESC
			");

			$qry_2 .= " WHERE DATE(date) < '$start_date' ";

			$query_before = $this->mymodel->selectWithQuery("SELECT * FROM
			(SELECT * FROM product WHERE 1=1 AND is_varian = 0 $statusFilter $jenisFilter) a 
			LEFT JOIN
			(SELECT product, SUM(qty) as qty 
			FROM stock a
			$qry_2
			GROUP BY a.product) b
			ON a.id = b.product
			ORDER BY b.qty DESC");
			
			$arr = array();
			foreach ($query_before as $k => $v) {
				$arr[$v['id']] = $v;
			}

			foreach ($query as $k => $v) {
				$before = $arr[$v['id']];
				$v['qty_sebelumnya'] = $before['qty'] ?? 0;
				$v['qty_akhir'] = $v['qty_sebelumnya'] + $v['qty'];
				$text .= '
					<tr>
						<td class="text-center">' . ($k + 1) . '</td>
						<td class="text-start td-breakline">' . $v['name'] . '</td>
						<td class="text-end">' . $this->template->separator_only($v['qty_in']) . '</td>
						<td class="text-end">
							<span class="tippy-return"
								data-product-id="' . (int)$v['id'] . '"
								data-start-date="' . htmlspecialchars($start_date, ENT_QUOTES, 'UTF-8') . '"
								data-until-date="' . htmlspecialchars($until_date, ENT_QUOTES, 'UTF-8') . '">'
								. $this->template->separator_only($v['qty_retur']) .
							'</span>
						</td>
						<td class="text-end">' . $this->template->separator_only($v['qty_out']) . '</td>
						<td class="text-end">' . $this->template->separator_only($v['qty_out_pos']) . '</td>
						<td class="text-end">' . $this->template->separator_only($v['qty']) . '</td>
						<td class="text-end">' . $this->template->separator_only($v['qty_sebelumnya']) . '</td>
						<td class="text-end">' . $this->template->separator_only($v['qty_akhir']) . '</td>
					</tr>';

			}
			$text = '<div class="table-responsive">
				<table class="table table-hover table-striped table-bordered">
					<tr class="bg-primary">
						<th class="text-center" style="width:20px!important">#</th>
						<th class="text-start">Nama<br>Produk</th>
						<th class="text-end" style="min-width:20px!important">Stok<br>Masuk</th>
						<th class="text-end" style="min-width:20px!important">Stok<br>Retur</th>
						<th class="text-end" style="min-width:20px!important">Stok<br>Keluar</th>
						<th class="text-end" style="min-width:20px!important">Stok<br>Terjual</th>
						<th class="text-end" style="min-width:20px!important">Stok</th>
						<th class="text-end" style="min-width:20px!important">Stok<br>Sebelumnya</th>
						<th class="text-end" style="min-width:20px!important">Stok<br>Akhir</th>
					</tr>
					' . $text . '
				</table></div>';
		} else if ($code == "11b") {
			$status = $_GET['status'] ?? '';
			$jenis  = $_GET['jenis'] ?? '';

			$statusFilter = "";
			$jenisFilter  = "";

			if ($status != '') {
				$statusFilter = " AND status = " . $this->db->escape($status) . " ";
			}

			if ($jenis != '') {
				if ($jenis == 'produk_jual') {
					$jenisFilter = " AND is_operational = 0 ";
				} else if ($jenis == 'produk_operasional') {
					$jenisFilter = " AND is_operational = 1 ";
				}
			}

			$query = $this->mymodel->selectWithQuery("
				SELECT a.*, 
					b.qty_in, b.qty_in_pos, b.qty_out, b.qty_out_pos, b.qty_out_retur, (b.qty + c.qty_retur_in - c.qty_retur_out) as qty,
					c.qty_retur, c.qty_retur_in, c.qty_retur_out
				FROM (
					SELECT * FROM product 
					WHERE 1=1 AND is_varian = 0 $statusFilter $jenisFilter
				) a 
				LEFT JOIN (
					SELECT s.product,
						SUM(s.qty_in)        AS qty_in,
						SUM(s.qty_in_pos)    AS qty_in_pos,
						SUM(s.qty_out)       AS qty_out,
						SUM(s.qty_out_pos)   AS qty_out_pos,
						SUM(s.qty_out_retur) AS qty_out_retur,
						SUM(s.qty_in + s.qty_in_pos - s.qty_out - s.qty_out_pos) AS qty
					FROM stock s
					$qry
					AND s.order_status NOT IN ('IN_CANCELLED','REFUND','CANCELLED','RETURN', 'RETURN_UNSHIPPED')
					GROUP BY s.product
				) b ON a.id = b.product
				LEFT JOIN (
					SELECT s.product,
						SUM(COALESCE(s.qty_in_pos,0)) as qty_retur_in,
						SUM(COALESCE(s.qty_out_retur,0)) as qty_retur_out,
						SUM(COALESCE(s.qty_in_pos,0) + COALESCE(s.qty_out_retur,0)) AS qty_retur
					FROM stock s
					$qry
					AND s.order_status LIKE '%RETURN%'
					GROUP BY s.product
				) c ON a.id = c.product
				ORDER BY b.qty_out_pos DESC
			");

			$qry_2 .= " WHERE DATE(date) < '$start_date' ";

			$query_before = $this->mymodel->selectWithQuery("SELECT * FROM
			(SELECT * FROM product WHERE 1=1 AND is_varian = 0 $statusFilter $jenisFilter) a 
			LEFT JOIN
			(SELECT product, SUM(qty) as qty 
			FROM stock a
			$qry_2
			GROUP BY a.product) b
			ON a.id = b.product
			ORDER BY b.qty DESC");
			
			$arr = array();
			foreach ($query_before as $k => $v) {
				$arr[$v['id']] = $v;
			}

			foreach ($query as $k => $v) {
				$before = $arr[$v['id']];
				$v['qty_sebelumnya'] = $before['qty'] ?? 0;
				$v['qty_akhir'] = $v['qty_sebelumnya'] + $v['qty'];
				$text .= '
					<tr>
						<td class="text-center">' . ($k + 1) . '</td>
						<td class="text-start td-breakline">' . $v['name'] . '</td>
						<td class="text-end">' . $this->template->separator_only($v['qty_out_pos'] + $v['qty_out_retur'] + $v['qty_out']) . '</td>
						<td class="text-end">' . $this->template->separator_only($v['qty_akhir']) . '</td>
					</tr>';

			}
			$text = '<div class="table-responsive">
				<table class="table table-hover table-striped table-bordered">
					<tr class="bg-primary">
						<th class="text-center" style="width:20px!important">#</th>
						<th class="text-start">Nama<br>Produk</th>
						<th class="text-end" style="min-width:20px!important">Stok<br>Keluar</th>
						<th class="text-end" style="min-width:20px!important">Stok<br>Akhir</th>
					</tr>
					' . $text . '
				</table></div>';
		} else if ($code == "2") {
			$query = $this->mymodel->selectWithQuery("SELECT *
			FROM 
			(SELECT id,full_name FROM customer) a
			JOIN
			(SELECT customer, COUNT(id) as count_order, SUM(price_total) as nominal_order FROM transaction
			$qry
			GROUP BY customer) b 
			ON a.id = b.customer
			ORDER BY count_order DESC, full_name ASC
			LIMIT 10");
			foreach ($query as $k => $v) {
				$text .= '
				<tr>
					<td class="text-center">' . ($k + 1) . '</td>
					<td class="text-start"><a style="text-decoration:none!important" href="' . base_url() . '/crm/detail?id=' . $v['id'] . '&start_date=' . $start_date . '&until_date=' . $until_date . '" target="_blank">' . $v['full_name'] . '</a></td>
					<td class="text-end">' . $this->template->separator_only($v['count_order']) . '</td>
					<td class="text-end">' . $this->template->separator_only($v['nominal_order']) . '</td>
				</tr>
				';
			}
			$text = '
			<div class="table-responsive">
				<table class="table table-hover table-striped table-bordered">
				<tr class="bg-primary">
						<th class="text-center" style="width:20px!important">#</th>
						<th class="text-start">Nama Pelanggan</th>
						<th class="text-end" style="min-width:20px!important">Jumlah Order</th>
						<th class="text-end" style="min-width:20px!important">Nominal Order</th>
					</tr>
					' . $text . '
				</table></div>';
		} else if ($code == "3") {
			$query = $this->mymodel->selectWithQuery("SELECT *
			FROM
			(SELECT * FROM marketplace) a
			LEFT JOIN
			(SELECT marketplace, COUNT(id) as count_order, SUM(omset_bersih) as nominal_order
			FROM transaction
			$qry AND type_sub = 'POS'
			GROUP BY marketplace) b
			ON a.name = b.marketplace
			ORDER BY count_order DESC, name ASC
			LIMIT 10");
			foreach ($query as $k => $v) {
				$text .= '
				<tr>
					<td class="text-center">' . ($k + 1) . '</td>
					<td class="text-start">' . $v['name'] . '</td>
					<td class="text-end">' . $this->template->separator_only($v['count_order']) . '</td>
					<td class="text-end">' . $this->template->separator_only($v['nominal_order']) . '</td>
				</tr>
				';
			}
			$text = '<div class="table-responsive">
				<table class="table table-hover table-striped table-bordered">
				<tr class="bg-primary">
						<th class="text-center" style="width:20px!important">#</th>
						<th class="text-start">Nama Channel</th>
						<th class="text-end" style="min-width:20px!important">Jumlah Order</th>
						<th class="text-end" style="min-width:20px!important">Nominal Order</th>
					</tr>
					' . $text . '
				</table></div>';
		} else if ($code == "4") {
			$query = $this->mymodel->selectWithQuery("SELECT city_text, COUNT(id) as count_order, SUM(price_total) as nominal_order
			FROM transaction
			$qry AND type_sub = 'POS'			
			GROUP BY city_text
			ORDER BY count_order DESC, city_text ASC
			LIMIT 12");
			foreach ($query as $k => $v) {
				$text .= '
				<tr>
					<td class="text-center">' . ($k + 1) . '</td>
					<td class="text-start">' . $v['city_text'] . '</td>
					<td class="text-end">' . $this->template->separator_only($v['count_order']) . '</td>
					<td class="text-end">' . $this->template->separator_only($v['nominal_order']) . '</td>
				</tr>
				';
			}
			$text = '<div class="table-responsive">
				<table class="table table-hover table-striped table-bordered">
				<tr class="bg-primary">
						<th class="text-center" style="width:20px!important">#</th>
						<th class="text-start">Nama Kota</th>
						<th class="text-end" style="min-width:20px!important">Jumlah Order</th>
<th class="text-end" style="min-width:20px!important">Nominal Order</th>
					</tr>
					' . $text . '
				</table></div>';
		} else if ($code == "5") {
			$year = DATE("Y", strtotime($start_date));
			$qry = '';
			$qry .= " AND YEAR(date) = '$year' ";
			if ($brand) {
				$qry .= " AND brand = '$brand' ";
			}
			$query = $this->mymodel->selectWithQuery("SELECT COUNT(id) as count, SUM(ABS(price_total)) as price_total, MONTH(date) as month
			FROM transaction
			WHERE category = 'Gift' $qry
			GROUP BY MONTH(date)");
			$arr_query = array();
			foreach ($query as $k => $v) {
				$arr_query[$v['month'] - 1]['price_total'] = $v['price_total'];
				$arr_query[$v['month'] - 1]['count'] = $v['count'];
			}
			$month = array(
				'Januari',
				'Februari',
				'Maret',
				'April',
				'Mei',
				'Juni',
				'Juli',
				'Agustus',
				'September',
				'Oktober',
				'November',
				'Desember'
			);
			$arr = array();
			foreach ($month as $k => $v) {
				$arr[$k]['month'] = $v;
				$arr[$k]['count'] = $arr_query[$k]['count'];
				$arr[$k]['price_total'] = $arr_query[$k]['price_total'];
			}
			foreach ($arr as $k => $v) {
				$text .= '
				<tr>
					<td class="text-center">' . ($k + 1) . '</td>
					<td class="text-start">' . $v['month'] . '</td>
					<td class="text-end">' . $this->template->separator_only($v['count']) . '</td>
					<td class="text-end">' . $this->template->separator_only($v['price_total']) . '</td>
				</tr>
				';
			}
			$text = '<div class="table-responsive">
				<table class="table table-hover table-striped table-bordered">
				<tr class="bg-primary">
						<th class="text-center" style="width:20px!important">#</th>
						<th class="text-start">Bulan</th>
						<th class="text-end" style="min-width:20px!important">Jumlah Gift</th>
						<th class="text-end" style="min-width:20px!important">Nominal Gift</th>
					</tr>
					' . $text . '
				</table></div>';
		} else if ($code == "6") {
			$year = DATE("Y", strtotime($start_date));
			$qry = '';
			$qry .= " AND YEAR(date) = '$year' ";
			if ($brand) {
				$qry .= " AND brand = '$brand' ";
			}
			$query = $this->mymodel->selectWithQuery("SELECT * FROM `endorse` 
			WHERE link_upload != '' $qry
			ORDER BY CAST(`views` AS UNSIGNED) DESC");
			foreach ($query as $k => $v) {
				$text .= '
				<tr>
					<td class="text-center">' . ($k + 1) . '</td>
					<td class="text-start"><a target="_blank" href="' . $v['link_upload'] . '">' . $v['nama_creator'] . '</a></td>
					<td class="text-start">' . $v['platform'] . '</td>
					<td class="text-end">' . $this->template->separator_only(doubleval($v['total_cost'])) . '</td>
					<td class="text-end">' . $this->template->separator_only(doubleval($v['views'])) . '</td>
					<td class="text-end">' . $this->template->separator_only(doubleval($v['likes'])) . '</td>
					<td class="text-end">' . $this->template->separator_only(doubleval($v['comment'])) . '</td>
					<td class="text-end">' . $this->template->separator_only(doubleval($v['share_save'])) . '</td>
					<td class="text-end">' . $this->template->separator_only(doubleval($v['cost_per_1000_impression'])) . '</td>
				</tr>
				';
			}
			$text = '<div class="table-responsive">
				<table class="table table-hover table-striped table-bordered">
				<tr class="bg-primary">
						<th class="text-center" style="width:20px!important">#</th>
						<th class="text-start">Nama Creator</th>
						<th class="text-start">Platform</th>
						<th class="text-end" style="min-width:20px!important">Cost</th>
						<th class="text-end" style="min-width:20px!important">Views</th>
						<th class="text-end" style="min-width:20px!important">Likes</th>
						<th class="text-end" style="min-width:20px!important">Comments</th>
						<th class="text-end" style="min-width:20px!important">Share & Save</th>
						<th class="text-end" style="min-width:20px!important">CPM</th>
					</tr>
					' . $text . '
				</table></div>';
		} else if ($code == "7") {
			$year = DATE("Y", strtotime($start_date));
			$qry = '';
			$qry .= " AND YEAR(date) = '$year' ";
			if ($brand) {
				$qry .= " AND brand = '$brand' ";
			}
			$query = $this->mymodel->selectWithQuery("SELECT * FROM
			(SELECT id, url
			FROM influencer) a
			JOIN
			(SELECT 
				 influencer,
				nama_creator,
				COUNT(id) as count,
				SUM(total_cost) as total_cost,
				SUM(views) as views,
				SUM(likes) as likes,
				SUM(comment) as comment,
				SUM(share_save) as share_save,
				AVG(cost_per_1000_impression) as cost_per_1000_impression 
			FROM `endorse` 
			WHERE link_upload != ''
			GROUP BY influencer
			) b
			ON a.id = b.influencer
			ORDER BY views DESC");
			foreach ($query as $k => $v) {
				$text .= '
				<tr>
					<td class="text-center">' . ($k + 1) . '</td>
					<td class="text-start"><a target="_blank" href="' . $v['url'] . '">' . $v['nama_creator'] . '</a></td>
					<td class="text-end">' . $this->template->separator_only(doubleval($v['count'])) . '</td>
					<td class="text-end">' . $this->template->separator_only(doubleval($v['total_cost'])) . '</td>
					<td class="text-end">' . $this->template->separator_only(doubleval($v['views'])) . '</td>
					<td class="text-end">' . $this->template->separator_only(doubleval($v['likes'])) . '</td>
					<td class="text-end">' . $this->template->separator_only(doubleval($v['comment'])) . '</td>
					<td class="text-end">' . $this->template->separator_only(doubleval($v['share_save'])) . '</td>
					<td class="text-end">' . $this->template->separator_only(doubleval($v['cost_per_1000_impression'])) . '</td>
				</tr>
				';
			}
			$text = '<div class="table-responsive">
				<table class="table table-hover table-striped table-bordered">
				<tr class="bg-primary">
						<th class="text-center" style="width:20px!important">#</th>
						<th class="text-start">Nama Creator</th>
						<th class="text-end" style="min-width:20px!important">Konten</th>
						<th class="text-end" style="min-width:20px!important">Cost</th>
						<th class="text-end" style="min-width:20px!important">Views</th>
						<th class="text-end" style="min-width:20px!important">Likes</th>
						<th class="text-end" style="min-width:20px!important">Comments</th>
						<th class="text-end" style="min-width:20px!important">Share & Save</th>
						<th class="text-end" style="min-width:20px!important">CPM</th>
					</tr>
					' . $text . '
				</table></div>';
		} else if ($code == "hpp") {
            $text = '';

            // === mapping jenis produk (boleh datang dari ?jenis_produk= "Produk Jual/Produk Operasional" atau ?jenis= 'produk_jual/produk_operasional')
            $jenis_produk = $_GET['jenis_produk'] ?? '';
            if (!$jenis_produk && !empty($_GET['jenis'])) {
                if ($_GET['jenis'] === 'produk_jual') $jenis_produk = 'Produk Jual';
                if ($_GET['jenis'] === 'produk_operasional') $jenis_produk = 'Produk Operasional';
            }

            // ==== Build filter dasar untuk product/stock/transaction ====
            $qry_stock = "1=1";
            $qry_transaction = "1=1";

            if (!empty($brand)) {
                $brand_esc = $this->db->escape_str($brand);
                $qry_stock       .= " AND p.brand = '{$brand_esc}'";
                $qry_transaction .= " AND t.brand = '{$brand_esc}'";
            }

            if ($jenis_produk === "Produk Jual") {
                $qry_stock .= " AND p.is_operational = '0'";
            } elseif ($jenis_produk === "Produk Operasional") {
                $qry_stock .= " AND p.is_operational = '1'";
            }

            // ==== Ambil daftar product id yang lolos filter brand/jenis ====
            $filterByProduct = (!empty($brand) || in_array($jenis_produk, ['Produk Jual','Produk Operasional']));
            $allowedProductId = null; // null = tidak filter

            if ($filterByProduct) {
                $cond = "1=1";
                if (!empty($brand)) {
                    $cond .= " AND brand = '".$this->db->escape_str($brand)."'";
                }
                if ($jenis_produk === 'Produk Jual') {
                    $cond .= " AND is_operational = '0'";
                } elseif ($jenis_produk === 'Produk Operasional') {
                    $cond .= " AND is_operational = '1'";
                }
                $rowsAllowed = $this->mymodel->selectWithQuery("SELECT id, name FROM product WHERE $cond");
                $allowedProductId = [];
                foreach ($rowsAllowed as $ra) $allowedProductId[(int)$ra['id']] = $ra['name'];
            }

            // ==== 1) Transaksi
            $trxRows = $this->mymodel->selectWithQuery("
                SELECT
                    t.order_id,
                    (t.omset_kotor - t.diskon_penjual) AS omset_bersih,
                    t.json,
                    t.is_manual
                FROM transaction t
                $qry
                AND t.order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID')
                AND t.type_sub = 'POS'
                AND $qry_transaction
            ");

            // ==== 2) Stock OUT (POS + Stock) pada periode
            $stockRows = $this->mymodel->selectWithQuery("
                SELECT
                    s.order_id,
                    s.product,
                    s.sku,
                    s.product_text,
                    s.type_sub,
                    CASE
                        WHEN s.type_sub = 'POS' THEN COALESCE(s.qty_out_pos, 0)
                        WHEN s.type_sub = 'Stock' AND s.qty_out IS NOT NULL AND s.qty_out <> 0 THEN s.qty_out
                        ELSE 0
                    END AS qty_out
                FROM stock s
                JOIN product p ON p.id = s.product
                WHERE DATE(s.date) >= '$start_date'
                AND DATE(s.date) <= '$until_date'
                AND s.type = 'Out'
                AND s.type_sub IN ('POS','Stock')
                AND s.status = 'Aktif'
                AND (s.order_status = '' OR s.order_status NOT IN ('CANCELLED','IN_CANCELLED','RETURN','REFUND'))
                AND $qry_stock
            ");

            // Stock diindeks per order untuk ambil qty real
            $stockByOrderId = [];
            $stockNonTrx = [];
            foreach ($stockRows as $sr) {
                $pid = (int)($sr['product'] ?? 0);
                $qty = (int)($sr['qty_out'] ?? 0);
                if ($qty <= 0) continue;

                if (is_array($allowedProductId) && ($pid === 0 || !isset($allowedProductId[$pid]))) {
                    continue;
                }

                if ($sr['type_sub'] === 'POS' && !empty($sr['order_id'])) {
                    $oid = $sr['order_id'];
                    if (!isset($stockByOrderId[$oid])) $stockByOrderId[$oid] = [];
                    if (!isset($stockByOrderId[$oid][$pid])) $stockByOrderId[$oid][$pid] = 0;
                    $stockByOrderId[$oid][$pid] += $qty;
                } else {
                    $stockNonTrx[] = [
                        'product'      => $pid,
                        'sku'          => trim((string)($sr['sku'] ?? '')),
                        'product_text' => (string)($sr['product_text'] ?? ''),
                        'qty_out'      => $qty,
                    ];
                }
            }

            // Kanon nama/sku dari JSON transaksi
            $canonByPid = []; $canonBySku = [];
            foreach ($trxRows as $r) {
                $items = is_array($r['json']) ? $r['json'] : json_decode($r['json'], true);
                if (!$items) continue;
                foreach ($items as $it) {
                    $pid  = isset($it['product']) ? (int)$it['product'] : 0;
                    $sku  = isset($it['sku']) ? trim((string)$it['sku']) : '';
                    $name = (string)($it['product_text'] ?? '');
                    $keyType = ($sku !== '') ? 'sku' : 'name';
                    if ($pid > 0 && !isset($canonByPid[$pid])) $canonByPid[$pid] = ['keyType'=>$keyType, 'sku'=>$sku, 'name'=>$name];
                    if ($sku !== '' && !isset($canonBySku[$sku])) $canonBySku[$sku] = ['name'=>$name];
                }
            }

            // === Bentuk baris awal (rows) per order/composition
            $rows = [];

            foreach ($trxRows as $r) {
                $oid      = $r['order_id'];
                $isManual = (int)$r['is_manual'] === 1;
                $items    = is_array($r['json']) ? $r['json'] : json_decode($r['json'], true);
                $items    = $items ?: [];

                // filter items by allowedProductId & override qty with stock-out
                $list = [];
                foreach ($items as $it) {
                    $pid = isset($it['product']) ? (int)$it['product'] : 0;
                    if (is_array($allowedProductId) && ($pid === 0 || !isset($allowedProductId[$pid]))) continue;
                    $qty = ($oid && isset($stockByOrderId[$oid][$pid])) ? (int)$stockByOrderId[$oid][$pid] : 0;
                    if ($qty <= 0) continue;
                    $it['qty'] = $qty;
                    $list[] = $it;
                }
                if (!$list) continue;

                if ($isManual) {
                    foreach ($list as $it) {
                        $pid  = (int)($it['product'] ?? 0);
                        $sku  = trim((string)($it['sku'] ?? ''));
                        $name = (string)($it['product_text'] ?? '');
                        $rows[] = [
                            'product_ids'     => $pid ? [$pid] : [],
                            'sku_join'        => $sku,
                            'name_join'       => $name,
                            'comp_count'      => 1,
                            'qty'             => (int)$it['qty'],
                            'omset_bersih'    => (float)($it['price_total'] ?? 0),
                            'items'           => [ $it ],
                        ];
                    }
                } else {
                    if (count($list) <= 1) {
                        $it   = $list[0];
                        $pid  = (int)($it['product'] ?? 0);
                        $sku  = trim((string)($it['sku'] ?? ''));
                        $name = (string)($it['product_text'] ?? '');
                        $rows[] = [
                            'product_ids'     => $pid ? [$pid] : [],
                            'sku_join'        => $sku,
                            'name_join'       => $name,
                            'comp_count'      => 1,
                            'qty'             => (int)$it['qty'],
                            'omset_bersih'    => (float)$r['omset_bersih'],
                            'items'           => [ $it ],
                        ];
                    } else {
                        // bundling: normalisasi pasangan sku/name & qty min sebagai qty bundle
                        $pairs = []; $bundleQty = null; $product_ids = [];
                        foreach ($list as $it) {
                            $pid  = (int)($it['product'] ?? 0);
                            $sku  = (string)($it['sku'] ?? '');
                            $name = (string)($it['product_text'] ?? '');
                            $q    = (int)$it['qty'];
                            $bundleQty = is_null($bundleQty) ? $q : min($bundleQty, $q);
                            $pairs[] = ['sku'=>$sku,'name'=>$name];
                            if ($pid) $product_ids[$pid] = true;
                        }
                        usort($pairs, function($a,$b){
                            return strcmp($a['sku'].$a['name'], $b['sku'].$b['name']);
                        });
                        $sku_join  = implode(' + ', array_filter(array_column($pairs,'sku')));
                        $name_join = implode(' + ', array_filter(array_column($pairs,'name')));
                        $rows[] = [
                            'product_ids'     => array_map('intval', array_keys($product_ids)),
                            'sku_join'        => $sku_join,
                            'name_join'       => $name_join,
                            'comp_count'      => max(1, count($pairs)),
                            'qty'             => max(1,(int)$bundleQty),
                            'omset_bersih'    => (float)$r['omset_bersih'],
                            'items'           => $list,
                        ];
                    }
                }
            }

            // === Tambahkan keluaran stok non-transaksi sebagai baris sendiri
            foreach ($stockNonTrx as $si) {
                $pid = (int)($si['product'] ?? 0);
                $rows[] = [
                    'product_ids'     => $pid ? [$pid] : [],
                    'sku_join'        => trim((string)($si['sku'] ?? '')),
                    'name_join'       => (string)($si['product_text'] ?? ''),
                    'comp_count'      => 1,
                    'qty'             => (int)$si['qty_out'],
                    'omset_bersih'    => 0.0,
                    'items'           => [ $si ],
                ];
            }

            // === Ambil HPP per product (price_buy)
            $allPids = [];
            foreach ($rows as $r) foreach ($r['product_ids'] as $pid) $allPids[$pid]=true;
            $hppById = [];
            if ($allPids) {
                $inId = implode(',', array_map('intval', array_keys($allPids)));
                $pRows = $this->mymodel->selectWithQuery("SELECT id, name, price_buy FROM product WHERE id IN ($inId)");
                foreach ($pRows as $p) {
                    $hppById[(int)$p['id']] = (float)$p['price_buy'];
                    // lengkapi nama product untuk dropdown filter (kalau filterByProduct kosong sekalipun)
                    if (!isset($allowedProductId[(int)$p['id']])) {
                        $allowedProductId[(int)$p['id']] = $p['name'];
                    }
                }
            }

            // === Hitung total HPP per baris
            foreach ($rows as &$r) {
                $lineHpp = 0.0;
                foreach ($r['items'] as $it) {
                    $pid = isset($it['product']) ? (int)$it['product'] : 0;
                    $qty = (int)($it['qty'] ?? 0);
                    $hpp = $hppById[$pid] ?? 0.0;
                    $lineHpp += ($qty * $hpp);
                }
                $r['line_hpp'] = $lineHpp;
            }
            unset($r);

            // === Agregasi akhir per komposisi (key & label konsisten)
			$agg = [];
			foreach ($rows as $r) {
				// pilih label: utamakan nama produk (lebih terbaca)
				$label_raw = $r['name_join'] ?: $r['sku_join'];

				// normalisasi label (hapus spasi ganda, trim)
				$label = trim(preg_replace('/\s+/', ' ', (string)$label_raw));
				if ($label === '') continue;

				// key kanonik: lowercase supaya stabil & tidak duplikat karena kapitalisasi
				$key = mb_strtolower($label, 'UTF-8');

				if (!isset($agg[$key])) {
					$agg[$key] = [
						'produk_bundling'        => $label,           // << label tampilan = sama dengan key (tanpa lowercase)
						'jumlah_produk_bundling' => (int)$r['comp_count'],
						'qty_bundling'           => 0,
						'total_hpp'              => 0.0,
						'omset_bersih'           => 0.0,
					];
				}
				$agg[$key]['qty_bundling'] += (int)$r['qty'];
				$agg[$key]['total_hpp']    += (float)$r['line_hpp'];
				$agg[$key]['omset_bersih'] += (float)$r['omset_bersih'];
			}


            // === Bentuk array final + kumpulkan opsi filter BUNDLING (bukan per-produk)
			$hpp = [];
			$bundleFilterMap = []; // hash => label
			$grand_hpp = 0;
			$grand_omset = 0;

			foreach ($agg as $bundle_key => $v) {
				$laba = $v['omset_bersih'] - $v['total_hpp'];
				$pct  = $v['omset_bersih'] > 0 ? ($v['total_hpp'] / $v['omset_bersih']) * 100 : 0;

				// pakai hash dari key kanonik supaya unik & stabil
				$bundle_hash = md5($bundle_key);

				$grand_hpp   += $v['total_hpp'];
				$grand_omset += $v['omset_bersih'];

				$bundleFilterMap[$bundle_hash] = $v['produk_bundling'];

				$hpp[] = [
					'bundle_hash'            => $bundle_hash,
					'produk_bundling'        => $v['produk_bundling'],
					'jumlah_produk_bundling' => (int)$v['jumlah_produk_bundling'],
					'qty_bundling'           => (int)$v['qty_bundling'],
					'total_hpp'              => (float)$v['total_hpp'],
					'omset_bersih'           => (float)$v['omset_bersih'],
					'laba_bundling'          => (float)$laba,
					'persentase_hpp'         => (float)$pct,
				];
			}

			$grand_laba = $grand_omset - $grand_hpp;
			// $grand_pct  = $grand_omset > 0 ? ($grand_hpp / $grand_omset) * 100 : 0; // kalau mau tampilkan persentase lagi

			// urutkan opsi filter by label
			asort($bundleFilterMap, SORT_NATURAL | SORT_FLAG_CASE);



            // === Build HTML table + dropdown filter di header
            // Build opsi filter (urut alfabet)
            // pastikan bukan null
			if (!is_array($productFilterMap)) {
				$productFilterMap = [];
			}

			asort($productFilterMap, SORT_NATURAL | SORT_FLAG_CASE);

            ob_start();
            ?>
            <div class="table-responsive" style="min-height:720px">
                <table class="table table-hover" id="hppTableReport">
                    <thead>
                        <tr>
                            <th style="width: 56px">No</th>
                            <th class="sortable">
								Komposisi Bundling
								<div class="dropdown d-inline-block ms-1">
									<button class="btn btn-sm btn-light border dropdown-toggle"
											type="button"
											data-bs-toggle="dropdown"
											data-bs-auto-close="outside"
											aria-expanded="false">
									<i class="bi bi-funnel"></i>
									</button>

									<!-- HAPUS d-flex/flex-column dari dropdown-menu -->
									<div class="dropdown-menu dropdown-menu-end p-0" id="hpp-product-filter">
									<!-- Pindahkan d-flex/flex-column ke panel dalam -->
									<div class="p-3 d-flex flex-column" style="min-width:420px;max-width:500px;max-height:420px;">
										<div class="mb-2">
										<input type="text" class="form-control form-control-sm" id="hpp-filter-search" placeholder="Cari bundling...">
										</div>

										<div class="d-flex gap-2 mb-2">
										<button type="button" class="btn btn-sm btn-outline-primary" id="hpp-filter-checkall">Pilih semua</button>
										<button type="button" class="btn btn-sm btn-outline-secondary" id="hpp-filter-uncheck">Kosongkan</button>
										</div>

										<!-- Bagian ini saja yang scroll -->
										<div class="border rounded p-2 flex-grow-1 overflow-auto" style="max-height:280px;min-height:0;">
										<?php foreach ($bundleFilterMap as $bh => $blabel): ?>
											<div class="form-check py-1 hpp-bundle-item">
											<input class="form-check-input hpp-bundle" type="checkbox"
													value="<?= htmlspecialchars($bh, ENT_QUOTES, 'UTF-8') ?>"
													id="bundle-<?= htmlspecialchars($bh, ENT_QUOTES, 'UTF-8') ?>" checked>
											<label class="form-check-label small" for="bundle-<?= htmlspecialchars($bh, ENT_QUOTES, 'UTF-8') ?>">
												<?= htmlspecialchars($blabel, ENT_QUOTES, 'UTF-8') ?>
											</label>
											</div>
										<?php endforeach; ?>
										</div>

										<!-- Footer tetap, tidak ikut scroll -->
										<div class="mt-2 d-grid position-sticky bottom-0 bg-white pt-2" style="box-shadow:0 -4px 8px rgba(0,0,0,.05);">
										<button type="button" class="btn btn-sm btn-primary" id="hpp-filter-apply">Terapkan</button>
										</div>
									</div>
									</div>
								</div>
								</th>

                            <th class="sortable">Qty Order</th>
                            <th class="sortable">Omset Bersih</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $no = 1; foreach ($hpp as $row): ?>
						<tr data-bundle="<?= htmlspecialchars($row['bundle_hash'], ENT_QUOTES, 'UTF-8') ?>">
							<td><?= $no++ ?></td>
							<td class="text-start"
								data-sort="<?= htmlspecialchars(mb_strtolower($row['produk_bundling'], 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>"
								style="max-width:360px; white-space:normal; word-break:break-word;">
								<?= htmlspecialchars($row['produk_bundling'], ENT_QUOTES, 'UTF-8') ?>
							</td>
							<td class="text-end" data-sort="<?= (int)$row['qty_bundling'] ?>"><?= $this->template->separator_only($row['qty_bundling']) ?></td>
							<td class="text-end" data-sort="<?= (float)$row['omset_bersih'] ?>"><?= $this->template->separator_only($row['omset_bersih']) ?></td>
						</tr>
						<?php endforeach; ?>

                        <tr class="fw-bold" id="hpp-grand-row">
                            <td colspan="3" class="text-end">Grand Total</td>
                            <td class="text-end" id="grand-omset"><?= $this->template->separator_only($grand_omset) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <script>
			(function(){
				const table   = document.getElementById('hppTableReport');
				const headers = table.querySelectorAll('th.sortable');
				const grandRow= document.getElementById('hpp-grand-row');
				const menu    = document.getElementById('hpp-product-filter');
				const applyBtn= document.getElementById('hpp-filter-apply');
				const chkAll  = document.getElementById('hpp-filter-checkall');
				const uncheck = document.getElementById('hpp-filter-uncheck');
				const search  = document.getElementById('hpp-filter-search');
				const fmt     = new Intl.NumberFormat('id-ID');

				// helper number
				const toNumber = (cell) => {
					const raw = (cell.getAttribute('data-sort') ?? cell.textContent).toString();
					const num = parseFloat(raw.replace(/[^\d.-]/g, ''));
					return Number.isNaN(num) ? 0 : num;
				};

				// sorting (biarkan seperti punyamu)
				headers.forEach(h => {
					const icon = document.createElement('i');
					icon.className = 'bi bi-arrow-down-up ms-1';
					h.appendChild(icon);
					h.addEventListener('click', () => sortTable(h));
				});
				function sortTable(header){
					const idx = Array.from(header.parentNode.children).indexOf(header);
					const rows = Array.from(table.querySelectorAll('tbody tr:not(.fw-bold)'));
					const isAsc = !header.classList.contains('asc');

					headers.forEach(x=>{ x.classList.remove('asc','desc'); x.querySelector('i').className='bi bi-arrow-down-up ms-1'; });

					rows.sort((a,b)=>{
					const aCell = a.children[idx], bCell = b.children[idx];
					const aVal = aCell.getAttribute('data-sort') ?? aCell.textContent.trim();
					const bVal = bCell.getAttribute('data-sort') ?? bCell.textContent.trim();
					if (idx === 1) {
						return isAsc
						? aVal.localeCompare(bVal,'id',{sensitivity:'base'})
						: bVal.localeCompare(aVal,'id',{sensitivity:'base'});
					}
					const aNum = parseFloat(String(aVal).replace(/[^\d.-]/g,'')); 
					const bNum = parseFloat(String(bVal).replace(/[^\d.-]/g,''));
					if (!Number.isNaN(aNum) && !Number.isNaN(bNum)) return isAsc ? aNum - bNum : bNum - aNum;
					return isAsc ? String(aVal).localeCompare(String(bVal)) : String(bVal).localeCompare(String(aVal));
					});

					const tbody = table.querySelector('tbody');
					rows.forEach(r => tbody.insertBefore(r, grandRow));

					// re-number
					Array.from(table.querySelectorAll('tbody tr:not(.fw-bold)')).forEach((r,i)=>{ r.cells[0].textContent = i+1; });

					header.classList.add(isAsc ? 'asc':'desc');
					header.querySelector('i').className = isAsc ? 'bi bi-arrow-up ms-1' : 'bi bi-arrow-down ms-1';
				}

				// === GRAND TOTAL (baru)
				function recalcGrandTotals(){
					let om = 0, pf = 0;
					const rows = table.querySelectorAll('tbody tr:not(.fw-bold)');
					rows.forEach(tr=>{
					if (tr.style.display === 'none') return;
					om += toNumber(tr.children[4]); // Omset
					pf += toNumber(tr.children[5]); // Profit
					});
					document.getElementById('grand-omset').textContent  = fmt.format(Math.round(om));
				}

				function closeDropdownMenu(menuEl){
					const ddWrap  = menuEl.closest('.dropdown');
					if (!ddWrap) return;
					const toggleEl = ddWrap.querySelector('[data-bs-toggle="dropdown"]');

					// Bootstrap 5 API jika ada
					if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown && toggleEl) {
					const dd = bootstrap.Dropdown.getOrCreateInstance(toggleEl);
					dd.hide();
					return;
					}
					// Fallback manual
					menuEl.classList.remove('show');
					ddWrap.classList.remove('show');
					if (toggleEl) toggleEl.setAttribute('aria-expanded','false');
				}

				function applyFilter(){
					const chosen = new Set(Array.from(menu.querySelectorAll('input.hpp-bundle:checked')).map(x=>x.value));
					const rows = table.querySelectorAll('tbody tr:not(.fw-bold)');

					rows.forEach(tr=>{
					const hash = tr.getAttribute('data-bundle') || '';
					tr.style.display = chosen.size === 0 ? 'none' : (chosen.has(hash) ? '' : 'none');
					});

					// re-number + grand total
					let i=1;
					rows.forEach(tr=>{ if (tr.style.display !== 'none') tr.cells[0].textContent = i++; });
					recalcGrandTotals?.();

					// Tutup dropdown
					closeDropdownMenu(menu);
				}

				applyBtn?.addEventListener('click', applyFilter);
				chkAll?.addEventListener('click', ()=> menu.querySelectorAll('input.hpp-bundle').forEach(x=> x.checked = true));
				uncheck?.addEventListener('click', ()=> menu.querySelectorAll('input.hpp-bundle').forEach(x=> x.checked = false));
				search?.addEventListener('input', ()=>{
					const q = search.value.trim().toLowerCase();
					menu.querySelectorAll('.hpp-bundle-item').forEach(div=>{
					const label = div.querySelector('label')?.textContent.toLowerCase() || '';
					div.style.display = label.includes(q) ? '' : 'none';
					});
				});

				// optional: pastikan default semua terpilih & total awal benar
				recalcGrandTotals();
			})();
			</script>

			<?php
            $text = ob_get_clean();
        } 

        $html['html'] = $text;
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($html, true);
    }

	// public function return_conditions()
	// {
	// 	// ===== Validasi minimum =====
	// 	$product_id = (int)($this->input->get('product_id') ?? 0);
	// 	if (!$product_id) {
	// 		return $this->output
	// 			->set_content_type('application/json')
	// 			->set_output(json_encode(['ok' => false, 'error' => 'product_id required']));
	// 	}

	// 	// ===== Ambil filter opsional =====
	// 	$start_date = $this->input->get('start_date'); 
	// 	$until_date = $this->input->get('until_date'); 

	// 	// --- Ambil semua baris stock untuk order_id yang punya RETURN di periode tsb (produk dibatasi) ---
	// 	$row = $this->mymodel->selectWithQuery("
	// 		SELECT s.*
	// 		FROM stock s
	// 		JOIN (
	// 			SELECT DISTINCT order_id
	// 			FROM stock
	// 			WHERE order_status = 'RETURN'
	// 			AND DATE(date) >= ".$this->db->escape($start_date)."
	// 			AND DATE(date) <= ".$this->db->escape($until_date)."
	// 		) r ON r.order_id = s.order_id
	// 		WHERE s.product = ".$this->db->escape($product_id)."
	// 		ORDER BY s.order_id, s.date, s.id
	// 	");

	// 	// ===== Pencocokan: kelompokkan per order_id lalu hitung qty_out_pos & qty_in_pos =====
	// 	$byOrder = [];
	// 	foreach ($row as $r) {
	// 		$oid = $r['order_id'];
	// 		if (!isset($byOrder[$oid])) {
	// 			$byOrder[$oid] = ['sum_out_pos' => 0, 'sum_in_pos' => 0];
	// 		}
	// 		$byOrder[$oid]['sum_out_pos'] += (int)($r['qty_out_pos'] ?? 0);
	// 		$byOrder[$oid]['sum_in_pos']  += (int)($r['qty_in_pos']  ?? 0);
	// 	}

	// 	// ===== Akumulasi good/bad =====
	// 	$good_qty = 0;           // total qty_out_pos yang match balik (good)
	// 	$bad_qty  = 0;           // total selisih unmatched out_pos (bad)
	// 	$good_orders = [];       // order_id list good
	// 	$bad_orders  = [];       // order_id list bad

	// 	foreach ($byOrder as $oid => $agg) {
	// 		$out = (int)$agg['sum_out_pos'];
	// 		$in  = (int)$agg['sum_in_pos'];

	// 		// Abaikan order yang tidak punya pergerakan POS sama sekali
	// 		if ($out === 0 && $in === 0) continue;

	// 		if ($out === $in) {
	// 			// Good: qty_out_pos == qty_in_pos
	// 			$good_qty += $out; // atau $in (sama nilainya)
	// 			$good_orders[$oid] = [
	// 				'sum_out_pos' => $out,
	// 				'sum_in_pos'  => $in,
	// 			];
	// 		} else {
	// 			// Bad: hitung selisih unmatched out_pos (yang tidak kembali sebagai in_pos)
	// 			$diff = max(0, $out - $in);
	// 			if ($diff > 0) {
	// 				$bad_qty += $diff;
	// 			}
	// 			$bad_orders[$oid] = [
	// 				'sum_out_pos' => $out,
	// 				'sum_in_pos'  => $in,
	// 				'unmatched'   => $diff, // berapa yang dianggap bad dari order ini
	// 			];
	// 		}
	// 	}

	// 	$resp = [
	// 		'ok'   => true,
	// 		'data' => [
	// 			// Ringkasan Good
	// 			'good_conditions' => [
	// 				'order_count' => count($good_orders),
	// 				'total_qty'   => $good_qty, // total qty_out_pos yang kembali (matched)
	// 				'order_ids'   => implode(',', array_keys($good_orders)),
	// 				'per_order'   => $good_orders, // detail per order
	// 			],

	// 			// Ringkasan Bad
	// 			'bad_conditions' => [
	// 				'order_count' => count($bad_orders),
	// 				'total_qty'   => $bad_qty, // total unmatched out_pos (rusak/hilang)
	// 				'order_ids'   => implode(',', array_keys($bad_orders)),
	// 				'per_order'   => $bad_orders, // detail per order (ada 'unmatched')
	// 			],
	// 		],
	// 	];

	// 	return $this->output
	// 		->set_content_type('application/json')
	// 		->set_output(json_encode($resp));
	// }

	public function return_conditions()
	{
		$product_id = (int)($this->input->get('product_id') ?? 0);
		$start_date = $this->input->get('start_date'); 
		$until_date = $this->input->get('until_date'); 

		if (!$product_id) {
			return $this->output
				->set_content_type('application/json')
				->set_output(json_encode(['ok' => false, 'error' => 'product_id required']));
		}		

		$row = $this->mymodel->selectWithQuery("
			SELECT SUM(qty_in_pos) AS good_count, SUM(qty_out_retur) AS bad_count
			FROM stock s
			WHERE s.product = ".$this->db->escape($product_id)."
			AND DATE(date) >= ".$this->db->escape($start_date)."
			AND DATE(date) <= ".$this->db->escape($until_date)."
			AND order_status LIKE '%RETURN%'
			ORDER BY s.order_id, s.date, s.id;
		");

		

		$resp = [
			'ok'   => true,
			'data' => [
				'good_conditions' => [
					'total_qty' => $row[0]['good_count'],
				],
				'bad_conditions' => [
					'total_qty' => $row[0]['bad_count'],
				]
			],
		];

		return $this->output
			->set_content_type('application/json')
			->set_output(json_encode($resp));
	}




	function get_report_influencer()
	{
		$code = $_GET['code'];
		$type = $_GET['type'];
		$start_date = $_GET['start_date'];
		$until_date = $_GET['until_date'];
		$start_year = $_GET['start_year'];
		$until_year = $_GET['until_year'];
		$start_month = $_GET['start_month'];
		$until_month = $_GET['until_month'];
		$start_week = $_GET['start_week'];
		$until_week = $_GET['until_week'];
		$brand = $_GET['brand'];
		if ($type == "Yearly") {
			$start_date = $start_year . '-01-01';
			$until_date = $until_year . '-12-31';
		} else if ($type == "Monthly") {
			$start_month = str_pad($start_month, 2, "0", STR_PAD_LEFT);
			$until_month = str_pad($until_month, 2, "0", STR_PAD_LEFT);
			$start_date = $start_year . '-' . $start_month . '-01';
			$until_date = $start_year . '-' . $until_month . '-31';
		} else if ($type == "Weekly") {
			$start_week = str_pad($start_week, 2, "0", STR_PAD_LEFT);
			$until_week = str_pad($until_week, 2, "0", STR_PAD_LEFT);

			$year = $start_year;
			$week = $start_week;
			$start_date = date("Y-m-d", strtotime($year . "W" . $week . "1"));

			$year = $start_year;
			$week = $until_week;
			$until_date = date("Y-m-d", strtotime($year . "W" . $week . "7"));
		}

		$qry = "";

		$qry .= " WHERE DATE(date) >= '$start_date' AND DATE(date) <= '$until_date' ";

		if ($brand) {
			$qry .= " AND brand = '$brand' ";
		}
		$channel = $_GET['channel'];
		if ($channel) {
			$qry .= " AND marketplace = '$channel' ";
		}
		$campaign = $_GET['campaign'];
		$platform = $_GET['platform'];
		$qry = "";
		if ($code == "1") {
			$total_1 = 0;
			$total_summary = array();
			$total = array();
			$text = "";
			if ($campaign) {
				$qry .= " AND id_campaign = '$campaign' ";
			}
			if ($platform) {
				$qry .= " AND platform = '$platform' ";
			}
			$query = $this->mymodel->selectWithQuery("SELECT nama_creator as username, influencer as id,platform, COUNT(id) as count
			FROM endorse 
			WHERE 1=1 $qry
			GROUP BY influencer
			ORDER BY count DESC
			");
			$list = $this->mymodel->selectWithQuery("SELECT influencer as id,status_endorse
			FROM endorse 
			WHERE 1=1 $qry
			");

			$arr = array();
			$arr[] = "1. Review";
			$arr[] = "2. Hold";
			$arr[] = "3. Acc";
			$arr[] = "4. DP";
			$arr[] = "5. FP";
			$arr[] = "6. Barang<br>Dikirim";
			$arr[] = "7. Draft<br>Content";
			$arr[] = "8. Posted<br>Content";
			$arr[] = "9. Reject";

			foreach ($list as $k => $v) {
				$i = 0;
				if ($v['status_endorse'] == "1. Review") {
					$i = 0;
					$total[$v['id']][$i] = $total[$v['id']][$i] + 1;
					$total_summary[$i] = $total_summary[$i] + 1;
				} else if ($v['status_endorse'] == "2. Hold") {
					$i = 1;
					$total[$v['id']][$i] = $total[$v['id']][$i] + 1;
					$total_summary[$i] = $total_summary[$i] + 1;
				} else if ($v['status_endorse'] == "3. Acc") {
					$i = 2;
					$total[$v['id']][$i] = $total[$v['id']][$i] + 1;
					$total_summary[$i] = $total_summary[$i] + 1;
				} else if ($v['status_endorse'] == "4. DP") {
					$i = 3;
					$total[$v['id']][$i] = $total[$v['id']][$i] + 1;
					$total_summary[$i] = $total_summary[$i] + 1;
				} else if ($v['status_endorse'] == "5. FP") {
					$i = 4;
					$total[$v['id']][$i] = $total[$v['id']][$i] + 1;
					$total_summary[$i] = $total_summary[$i] + 1;
				} else if ($v['status_endorse'] == "6. Barang Dikirim") {
					$i = 5;
					$total[$v['id']][$i] = $total[$v['id']][$i] + 1;
					$total_summary[$i] = $total_summary[$i] + 1;
				} else if ($v['status_endorse'] == "7. Draft Content") {
					$i = 6;
					$total[$v['id']][$i] = $total[$v['id']][$i] + 1;
					$total_summary[$i] = $total_summary[$i] + 1;
				} else if ($v['status_endorse'] == "8. Posted Content") {
					$i = 7;
					$total[$v['id']][$i] = $total[$v['id']][$i] + 1;
					$total_summary[$i] = $total_summary[$i] + 1;
				} else if ($v['status_endorse'] == "9. Reject") {
					$i = 8;
					$total[$v['id']][$i] = $total[$v['id']][$i] + 1;
					$total_summary[$i] = $total_summary[$i] + 1;
				}
			}

			foreach ($query as $k => $v) {
				$td = '';
				$th = '';
				foreach ($arr as $k2 => $v2) {
					$td .= '<td class="text-end">' . $this->template->separator_only($total[$v['id']][$k2]) . '</td>';
				}
				foreach ($arr as $k2 => $v2) {
					$th .= '<th class="text-end">' . $this->template->separator_only($total_summary[$k2]) . '</th>';
				}
				$text .= '
				<tr>
					<td class="text-center">' . ($k + 1) . '</td>
					<td class="text-start td-breakline">' . $v['username'] . '</td>
					<td class="text-start td-breakline">' . $v['platform'] . '</td>
					<td class="text-end">' . $this->template->separator_only($v['count']) . '</td>
					' . $td . '
				</tr>
				';
				$total_1 += $v['count'];
			}
			$th_status = '';

			foreach ($arr as $k => $v) {
				$th_status .= '<th class="text-end" style="min-width:20px!important">' . $v . '</th>';
			}
			$text = '<div class="table-responsive">
				<table class="table table-hover table-striped table-bordered">
					<tr class="bg-primary">
						<th class="text-center" style="width:20px!important">#</th>
						<th class="text-start">Username</th>
						<th class="text-start">Platform</th>
						<th class="text-end">Konten</th>
						' . $th_status . '
					</tr>
					<tr>
						<th class="text-center">#</th>
						<th class="text-start td-breakline">Total</th>
						<th class="text-start td-breakline"></th><th class="text-end" style="min-width:20px!important">' . $total_1 . '</th>
						' . $th . '
					</tr>
					
					' . $text . '
				</table></div>';
		}
		$html['html'] = $text;
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($html, true);
	}

	function minimize_sidebar()
	{
		$session = $_SESSION['minimize_sidebar'];
		if ($session) {
			$_SESSION['minimize_sidebar'] = false;
		} else {
			$_SESSION['minimize_sidebar'] = true;
		}
		$html['html'] = "";
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($html, true);
	}
	function get_price_total()
	{
		$id_trx = $_GET['id_trx'];
		$query = $this->mymodel->selectWithQuery("SELECT json FROM transaction WHERE id = '$id_trx' ");

		$query = $query[0];
		$json = json_decode($query['json'], true);
		$price = 0;
		foreach ($json as $k => $v) {
			$price += doubleval($v['price_total']);
		}

		$dt = array();
		$dt['price_total'] = strval($price);

		$this->db->update('transaction', $dt, array('id' => $id_trx));

		header('Content-Type: application/json; charset=utf-8');
		$html = array();
		$html['price_total'] = $price;
		$msg = "Update data berhasil!";
		$html['msg'] = $this->template->alert_success($msg);
		echo json_encode($html, true);
	}

	function get_birthday_list()
	{
		$type = $_GET['type'];
		if ($type == 'today') {
			$today = DATE("Y-m-d");
			$query = $this->mymodel->selectWithQuery("SELECT id, full_name, phone, birth_date
			FROM customer 
			WHERE DATE_FORMAT(birth_date, '%m-%d') = DATE_FORMAT('$today', '%m-%d')
			ORDER BY full_name");

			foreach ($query as $k => $v) {
				$bg = '#FFF';
				$bg_2 = '#60bb55';
				if (empty($v['phone'])) {
					$bg = '#fef2f2';
					$bg_2 = '#ed7881';
				}
				if (substr($v['phone'], 0, 1) === "0") {
					$v['phone'] = "62" . substr($v['phone'], 1);
				}
				$dateOfBirth = $v['birth_date'];
				$today = date("Y-m-d");
				$age = date("Y", strtotime($today)) - date("Y", strtotime($dateOfBirth));
				$text .= '
				<a style="color:#000;text-decoration: none;" target="_blank" href="https://wa.me/' . $v['phone'] . '">
				<div class="box-chart-2 tr-shadow mb-2" style="background:' . $bg . '">
				<div class="row">
				<div class="firstDiv">
				<div class="firstCircle" style="background:' . $bg_2 . '">
				<div class="centeredElement">
				' . strtoupper($v['full_name'][0]) . '
				</div>
				</div>
				</div>
				<div class="secondDiv">
				' . $v['full_name'] . '
				<br>
				Tanggal : ' . $v['birth_date'] . '
				<br>
				Usia : ' . $age . ' Tahun
				</div>
				</div>
				</div></a>';
			}
			if (empty($query)) {
				$text = 'Data tidak ditemukan!';
			}
		} else if ($type == '+1') {
			$today = DATE("Y-m-d");
			$today = date("Y-m-d", strtotime($today . " +1 days"));
			$query = $this->mymodel->selectWithQuery("SELECT id, full_name, phone, birth_date
			FROM customer 
			WHERE DATE_FORMAT(birth_date, '%m-%d') = DATE_FORMAT('$today', '%m-%d')
			ORDER BY full_name");

			foreach ($query as $k => $v) {
				$bg = '#FFF';
				$bg_2 = '#60bb55';
				if (empty($v['phone'])) {
					$bg = '#fef2f2';
					$bg_2 = '#ed7881';
				}
				if (substr($v['phone'], 0, 1) === "0") {
					$v['phone'] = "62" . substr($v['phone'], 1);
				}
				$dateOfBirth = $v['birth_date'];
				$today = date("Y-m-d");
				$age = date("Y", strtotime($today)) - date("Y", strtotime($dateOfBirth));
				$text .= '
				<a style="color:#000;text-decoration: none;" target="_blank" href="https://wa.me/' . $v['phone'] . '">
				<div class="box-chart-2 tr-shadow mb-2" style="background:' . $bg . '">
				<div class="row">
				<div class="firstDiv">
				<div class="firstCircle" style="background:' . $bg_2 . '">
				<div class="centeredElement">
				' . strtoupper($v['full_name'][0]) . '
				</div>
				</div>
				</div>
				<div class="secondDiv">
				' . $v['full_name'] . '
				<br>
				Tanggal : ' . $v['birth_date'] . '
				<br>
				Usia : ' . $age . ' Tahun
				</div>
				</div>
				</div></a>';
			}
			if (empty($query)) {
				$text = 'Data tidak ditemukan!';
			}
		} else if ($type == '+2') {
			$today = DATE("Y-m-d");
			$today = date("Y-m-d", strtotime($today . " +2 days"));
			$query = $this->mymodel->selectWithQuery("SELECT id, full_name, phone, birth_date
			FROM customer 
			WHERE DATE_FORMAT(birth_date, '%m-%d') = DATE_FORMAT('$today', '%m-%d')
			ORDER BY full_name");

			foreach ($query as $k => $v) {
				$bg = '#FFF';
				$bg_2 = '#60bb55';
				if (empty($v['phone'])) {
					$bg = '#fef2f2';
					$bg_2 = '#ed7881';
				}
				if (substr($v['phone'], 0, 1) === "0") {
					$v['phone'] = "62" . substr($v['phone'], 1);
				}
				$dateOfBirth = $v['birth_date'];
				$today = date("Y-m-d");
				$age = date("Y", strtotime($today)) - date("Y", strtotime($dateOfBirth));
				$text .= '
				<a style="color:#000;text-decoration: none;" target="_blank" href="https://wa.me/' . $v['phone'] . '">
				<div class="box-chart-2 tr-shadow mb-2" style="background:' . $bg . '">
				<div class="row">
				<div class="firstDiv">
				<div class="firstCircle" style="background:' . $bg_2 . '">
				<div class="centeredElement">
				' . strtoupper($v['full_name'][0]) . '
				</div>
				</div>
				</div>
				<div class="secondDiv">
				' . $v['full_name'] . '
				<br>
				Tanggal : ' . $v['birth_date'] . '
				<br>
				Usia : ' . $age . ' Tahun
				</div>
				</div>
				</div></a>';
			}
			if (empty($query)) {
				$text = 'Data tidak ditemukan!';
			}
		} else {
			$today = DATE("Y-m-d");
			$today = date("Y-m-d", strtotime($today . " +2 days"));
			$query = $this->mymodel->selectWithQuery("SELECT id, full_name, phone, birth_date
			FROM customer 
			WHERE DATE_FORMAT(birth_date, '%m-%d') > DATE_FORMAT('$today', '%m-%d')
			ORDER BY DATE_FORMAT(birth_date, '%m-%d') ASC,full_name ASC
			LIMIT 10");

			foreach ($query as $k => $v) {
				$bg = '#FFF';
				$bg_2 = '#60bb55';
				if (empty($v['phone'])) {
					$bg = '#fef2f2';
					$bg_2 = '#ed7881';
				}
				if (substr($v['phone'], 0, 1) === "0") {
					$v['phone'] = "62" . substr($v['phone'], 1);
				}
				$dateOfBirth = $v['birth_date'];
				$today = date("Y-m-d");
				$age = date("Y", strtotime($today)) - date("Y", strtotime($dateOfBirth));
				$text .= '
				<a style="color:#000;text-decoration: none;" target="_blank" href="https://wa.me/' . $v['phone'] . '">
				<div class="box-chart-2 tr-shadow mb-2" style="background:' . $bg . '">
				<div class="row">
				<div class="firstDiv">
				<div class="firstCircle" style="background:' . $bg_2 . '">
				<div class="centeredElement">
				' . strtoupper($v['full_name'][0]) . '
				</div>
				</div>
				</div>
				<div class="secondDiv">
				' . $v['full_name'] . '
				<br>
				Tanggal : ' . $v['birth_date'] . '
				<br>
				Usia : ' . $age . ' Tahun
				</div>
				</div>
				</div></a>';
			}
			if (empty($query)) {
				$text = 'Data tidak ditemukan!';
			}
		}

		header('Content-Type: application/json');
		$html = array();
		$html['html'] = $text;
		echo json_encode($html, true);
	}
	// function get_filter()
	// {
	// 	$page = $_GET['page'];
	// 	$type = $_GET['type'];
	// 	$start_date = $_GET['start_date'];
	// 	$until_date = $_GET['until_date'];
	// 	$start_year = $_GET['start_year'];
	// 	$until_year = $_GET['until_year'];
	// 	$start_month = $_GET['start_month'];
	// 	$until_month = $_GET['until_month'];
	// 	$start_week = $_GET['start_week'];
	// 	$until_week = $_GET['until_week'];
	// 	$site = $_GET['site'];
	// 	$customer = $_GET['customer'];
	// 	$mpu = $_GET['mpu'];
	// 	$start_year = $_GET['start_year'];
	// 	$until_year = $_GET['until_year'];
	// 	$start_month = $_GET['start_month'];
	// 	$until_month = $_GET['until_month'];
	// 	$start_week = $_GET['start_week'];
	// 	$until_week = $_GET['until_week'];

	// 	$text = "";

	// 	if ($type == "Yearly") {
	// 		for ($i = 2020; $i <= DATE("Y"); $i++) {
	// 			$s = "";
	// 			if ($i == $start_year) {
	// 				$s = "selected";
	// 			}
	// 			$opt_year_1 .= '<option ' . $s . ' value="' . $i . '">' . $i . '</option>';
	// 		}

	// 		for ($i = 2020; $i <= DATE("Y"); $i++) {
	// 			$s = "";
	// 			if ($i == $until_year) {
	// 				$s = "selected";
	// 			}
	// 			$opt_year_2 .= '<option ' . $s . ' value="' . $i . '">' . $i . '</option>';
	// 		}
	// 		$text = '
		
	// 	<div class="col-md-4">
	// 			<div class="d-flex">
	// 			<select type="date" class="form-control " name="start_year" style="border-top-right-radius: 0px !important; border-bottom-right-radius: 0px !important; width:100%;">
	// 			' . $opt_year_1 . '
	// 			</select>
	// 			<select type="date" class="form-control " name="until_year" style="border-top-left-radius: 0px !important; border-bottom-left-radius: 0px !important; width:100%;">
	// 		' . $opt_year_2 . '
	// 		</select>
	// 			</div>
	// 			</div>
	// 		</div>

	// 	';
	// 	} else if ($type == "Monthly") {

	// 		for ($i = 2020; $i <= DATE("Y"); $i++) {
	// 			$s = "";
	// 			if ($i == $start_year) {
	// 				$s = "selected";
	// 			}
	// 			$opt_year_1 .= '<option ' . $s . ' value="' . $i . '">' . $i . '</option>';
	// 		}

	// 		for ($i = 1; $i <= 12; $i++) {
	// 			$s = "";
	// 			if ($i == $start_month) {
	// 				$s = "selected";
	// 			}
	// 			$opt_month_1 .= '<option ' . $s . ' value="' . $i . '">' . $i . '</option>';
	// 		}
	// 		for ($i = 1; $i <= 12; $i++) {
	// 			$s = "";
	// 			if ($i == $until_month) {
	// 				$s = "selected";
	// 			}
	// 			$opt_month_2 .= '<option ' . $s . ' value="' . $i . '">' . $i . '</option>';
	// 		}

	// 		$text = '<div class="col-md-3">
	// 		<select type="date" class="form-control " name="start_year">
	// 		' . $opt_year_1 . '
	// 		</select>
	// 	</div>

	// 	<div class="col-md-3">
	// 			<div class="d-flex">
	// 			<select type="date" class="form-control " name="start_month" style="border-top-right-radius: 0px !important; border-bottom-right-radius: 0px !important; width:100%;">
	// 			' . $opt_month_1 . '
	// 			</select>
	// 			<select type="date" class="form-control " name="until_month" style="border-top-left-radius: 0px !important; border-bottom-left-radius: 0px !important; width:100%;">
	// 		' . $opt_month_2 . '
	// 		</select>
	// 			</div>
	// 			</div>
	// 		</div>

	// 		';
	// 	} else if ($type == "Weekly") {

	// 		for ($i = 2020; $i <= DATE("Y"); $i++) {
	// 			$s = "";
	// 			if ($i == $start_year) {
	// 				$s = "selected";
	// 			}
	// 			$opt_year_1 .= '<option ' . $s . ' value="' . $i . '">' . $i . '</option>';
	// 		}

	// 		for ($i = 1; $i <= 53; $i++) {
	// 			$s = "";
	// 			if ($i == $start_week) {
	// 				$s = "selected";
	// 			}
	// 			$opt_week_1 .= '<option ' . $s . ' value="' . $i . '">' . $i . '</option>';
	// 		}
	// 		for ($i = 1; $i <= 53; $i++) {
	// 			$s = "";
	// 			if ($i == $until_week) {
	// 				$s = "selected";
	// 			}
	// 			$opt_week_2 .= '<option ' . $s . ' value="' . $i . '">' . $i . '</option>';
	// 		}

	// 		$text = '
	// 		<div class="col-md-3">
	// 			<select type="date" class="form-control " name="start_year">
	// 			' . $opt_year_1 . '
	// 			</select>
	// 		</div>

	// 		<div class="col-md-3">
	// 			<div class="d-flex">
	// 				<select type="date" class="form-control" name="start_week" style="border-top-right-radius: 0px !important; border-bottom-right-radius: 0px !important; width:50%;">
	// 				' . $opt_week_1 . '
	// 				</select>
	// 				<select type="date" class="form-control " name="until_week" style="border-top-left-radius: 0px !important; border-bottom-left-radius: 0px !important; width:50%;">
	// 				' . $opt_week_2 . '
	// 				</select>
	// 				</div>
	// 			</div>
	// 		</div>

		

	// 	';
	// 	} else {
	// 		$text = '
	// 		<div class="col-md-7">
	// 			<div class="d-flex">
	// 				<input type="date" name="start_date" class="form-control" value="' . $start_date . '" style="border-top-right-radius: 0px !important; border-bottom-right-radius: 0px !important; width:100%;">
	// 				<input type="date" name="until_date" class="form-control" value="' . $until_date . '" style="border-top-left-radius: 0px !important; border-bottom-left-radius: 0px !important; width:100%;">
	// 			</div>
	// 		</div>
	// 		';
	// 	}
	// 	if ($page == "endorse") {
	// 		$text .= ' <div class="col-md-3">
	// 				<button class="btn btn-primary w-100 form-control" type="submit"><i class="bi bi-search fs-16"></i> Cari Data</button>
	// 			</div>';
	// 	} else {
	// 		$text .= ' <div class="col-md-3">
	// 				<button class="btn btn-primary w-100 form-control" type="submit"><i class="bi bi-search fs-16"></i> Cari Data</button>
	// 			</div>';
	// 	}
	// 	// echo $text;
	// 	$html = array();
	// 	$html['html'] = $text;
	// 	header('Content-Type: application/json; charset=utf-8');
	// 	echo json_encode($html, true);
	// }

	function get_filter()
	{
		$start_date = $_GET['start_date'] ?? date('Y-m-d');
		$until_date = $_GET['until_date'] ?? date('Y-m-d');

		$text = <<<'HTML'
		<style>
			.daterangepicker {
				width: auto !important;
				max-width: 600px !important;
				padding: 10px !important;
				font-size: 13px;
			}
			.daterangepicker .calendar {
				width: 100% !important;
				max-width: 250px !important;
			}
			.daterangepicker .drp-calendar {
				margin: 0 5px !important;
			}
			.daterangepicker .calendar-table {
				table-layout: fixed !important;
				width: 100% !important;
				border-collapse: collapse !important;
				border-spacing: 0 !important;
			}
			.daterangepicker .calendar-table th,
			.daterangepicker .calendar-table td {
				width: 30% !important;
				max-width: 30% !important;
				height: 15px !important;
				line-height: 15px !important;
				margin: 0 !important;
				text-align: center !important;
				vertical-align: middle !important;
				box-sizing: border-box !important;
				overflow: hidden !important;
				white-space: nowrap !important;
				text-overflow: ellipsis !important;
				font-size: 11px !important;
				font-weight: normal !important;
			}
			.daterangepicker td.in-range {
				background-color: #f0f8ff !important;
				color: #000 !important;
			}
			.daterangepicker td.active,
			.daterangepicker td.active:hover,
			.daterangepicker td.start-date,
			.daterangepicker td.end-date {
				background-color: #357ebd !important;
				color: #fff !important;
				width: 30% !important;
				max-width: 30% !important;
				height: 15px !important;
				line-height: 15px !important;
				margin: 0 !important;
				box-sizing: border-box !important;
				text-align: center !important;
				overflow: hidden !important;
				white-space: nowrap !important;
			}
			.daterangepicker .drp-buttons {
				margin-top: 10px !important;
				padding-top: 10px !important;
				border-top: 1px solid #eee !important;
			}
			.daterangepicker .ranges {
				display: none !important;
			}
			.custom-ranges {
				display: flex;
				flex-wrap: wrap;
				gap: 6px;
				padding: 10px 10px 0;
				justify-content: center;
			}
			.custom-ranges button {
				padding: 5px 10px;
				font-size: 12px;
				border: 1px solid #ccc;
				background: #f9f9f9;
				border-radius: 4px;
				cursor: pointer;
			}
			.custom-ranges button.active {
				background-color: #007bff;
				color: white;
				border-color: #007bff;
			}
		</style>

		<script>
			$(function () {
				const startDate = moment("START_DATE", "YYYY-MM-DD");
				const endDate = moment("UNTIL_DATE", "YYYY-MM-DD");

				const minDate = moment().subtract(2, "years");
				const maxDate = moment().add(2, "years");

				const picker = $("#tanggal").daterangepicker({
					showDropdowns: true,
					ranges: {},
					alwaysShowCalendars: true,
					startDate: startDate,
					endDate: endDate,
					minDate: minDate,
					maxDate: maxDate,
					opens: "center",
					drops: "auto",
					autoUpdateInput: true,
					locale: {
						format: "DD/MM/YYYY",
						separator: " - ",
						applyLabel: "Terapkan",
						cancelLabel: "Batal",
						fromLabel: "Dari",
						toLabel: "Sampai",
						customRangeLabel: "Custom",
						daysOfWeek: ["Mg", "Sn", "Sl", "Rb", "Km", "Jm", "Sb"],
						monthNames: [
							"Januari", "Februari", "Maret", "April", "Mei", "Juni",
							"Juli", "Agustus", "September", "Oktober", "November", "Desember"
						],
						firstDay: 1
					}
				}, function(start, end, label) {
					$("#start_date").val(start.format("YYYY-MM-DD"));
					$("#end_date").val(end.format("YYYY-MM-DD"));
					updateActiveButton(start, end);
				});

				$("#start_date").val(startDate.format("YYYY-MM-DD"));
				$("#end_date").val(endDate.format("YYYY-MM-DD"));

				const presetRanges = {
					"Hari Ini": [moment(), moment()],
					"Kemarin": [moment().subtract(1, "days"), moment().subtract(1, "days")],
					"7 Hari Terakhir": [moment().subtract(6, "days"), moment()],
					"30 Hari Terakhir": [moment().subtract(29, "days"), moment()],
					"Bulan Ini": [moment().startOf("month"), moment().endOf("month")],
					"Bulan Lalu": [moment().subtract(1, "month").startOf("month"), moment().subtract(1, "month").endOf("month")]
				};

				const container = $(".daterangepicker");
				const rangeContainer = $("<div class='custom-ranges'></div>");

				function updateActiveButton(start, end) {
					$(".custom-ranges button").removeClass("active");

					Object.entries(presetRanges).forEach(([label, dates]) => {
						if (start.isSame(dates[0], 'day') && end.isSame(dates[1], 'day')) {
							$(".custom-ranges button").filter(function() {
								return $(this).text() === label;
							}).addClass("active");
						}
					});
				}

				$.each(presetRanges, function(label, dates) {
					const btn = $("<button type='button'></button>").text(label);
					btn.on("click", function () {
						const drp = picker.data("daterangepicker");
						drp.setStartDate(dates[0]);
						drp.setEndDate(dates[1]);
						drp.updateCalendars();
						drp.updateInput();

						$("#start_date").val(dates[0].format("YYYY-MM-DD"));
						$("#end_date").val(dates[1].format("YYYY-MM-DD"));

						$(".custom-ranges button").removeClass("active");
						$(this).addClass("active");
					});
					rangeContainer.append(btn);
				});

				container.prepend(rangeContainer);
				updateActiveButton(startDate, endDate);
			});

		</script>
	HTML;

		$text = str_replace("START_DATE", $start_date, $text);
		$text = str_replace("UNTIL_DATE", $until_date, $text);

		$html = array();
		$html['html'] = $text;
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($html, true);
	}

	function get_influencer_list()
	{
		$q = $_GET['search'] ?? '';

		$query = $this->mymodel->selectWithQuery("SELECT id as id, CONCAT(username,' | ',type) as text
			FROM influencer 
			WHERE username LIKE '%$q%'
			ORDER BY username ASC
			LIMIT 10");



		header('Content-Type: application/json');
		echo json_encode($query, true);
	}
	function get_product_list()
	{
		$q = $_GET['search'] ?? '';

		$query = $this->mymodel->selectWithQuery("SELECT id as id, name as text
			FROM product 
			WHERE name LIKE '%$q%' 
			AND is_operational = 0 
			AND status = 'Aktif' 
                AND (
                    is_varian = 1 
                    OR (is_varian = 0 AND (parent_id IS NULL OR parent_id = ''))
                )
			ORDER BY name ASC
			LIMIT 10");



		header('Content-Type: application/json');
		echo json_encode($query, true);
	}
	function get_customer_list()
	{
		$q = $_GET['search'] ?? '';

		$query = $this->mymodel->selectWithQuery("SELECT id as id, CONCAT(full_name,' | ',phone,' | ',username) as text, phone, username
			FROM customer 
			WHERE full_name LIKE '%$q%' OR username LIKE '%$q%' OR phone LIKE '%$q%'
			ORDER BY full_name ASC
			LIMIT 10");



		header('Content-Type: application/json');
		echo json_encode($query, true);
	}

	function get_customer_detail()
	{
		$save = $_GET['save'];
		$id = $_GET['id'];
		$id_trx = $_GET['id_trx'];

		$query = $this->mymodel->selectWithQuery("SELECT *
		FROM customer WHERE id = '$id' ");

		$query = $query[0];

		if ($save == 'true') {
			if ($id_trx) {
				$dt = array();
				$dt['customer'] = strval($id);
				$dt['customer_text'] = strval($query['full_name']);

				$data = $this->mymodel->selectWithQuery("SELECT customer
				FROM transaction WHERE id = '$id_trx' ");
				$data = $data[0];
				if ($data['customer'] != $id) {


					$this->db->update('expense', $dt, array('id' => $id_trx));

					if ($id > 0) {
						$this->refresh_gift($id);
					}
					if ($data['customer'] > 0) {
						$this->refresh_gift($data['customer']);
					}
				} else {

					$this->db->update('expense', $dt, array('id' => $id_trx));

					$this->refresh_gift($id);
				}
			}
		}

		$html = $query;
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($html, true);
	}

	function refresh_gift($id_customer)
	{
		$data = $this->mymodel->selectWithQuery("SELECT id, date, title,transaction.desc,qty,price,price_total FROM transaction
        WHERE customer = '$id_customer' AND category = 'Gift'
        ORDER BY date ASC");
		$dt = array();
		$dt['gift'] = json_encode($data, true);
		$this->db->update('customer', $dt, array('id' => $id_customer));
	}

	function get_product_detail()
	{
		$id = $_GET['id'];
		$id_trx = $_GET['id_trx'];
		$id_customer = $_GET['id_customer'];

		$query = $this->mymodel->selectWithQuery("SELECT *
		ct WHERE id = '$id' ");

		$data = $query[0];

		if ($data) {
			$data['price'] = $data['price_normal'];
			if ($id_customer) {

				$query = $this->mymodel->selectWithQuery("SELECT akun_type
				FROM customer WHERE id = '$id_customer' ");

				$query = $query[0];
				if ($query['akun_type'] == "Pelanggan") {
					$data['price'] = $data['price_normal'];
				} else if ($query['akun_type'] == "Distributor") {
					$data['price'] = $data['price_distributor'];
				} else if ($query['akun_type'] == "Reseller") {
					$data['price'] = $data['price_reseller'];
				} else {
					$data['price'] = $data['price_normal'];
				}
			}
		}

		if ($id_trx) {
			$query = $this->mymodel->selectWithQuery("SELECT * FROM transaction WHERE id = '$id_trx' ");

			$query = $query[0];
			$json = json_decode($query['json'], true);
			$json[$id]['price'] = $data['price'];
			$json[$id]['price_total'] = doubleval($json[$id]['price']) * doubleval($json[$id]['qty']);
			$price_total = 0;
			foreach ($json as $k => $v) {
				$price_total += doubleval($v['price_total']);
			}
			$data['price_total'] = $price_total;
			$dt = array();
			$dt['price_total'] = $price_total;
			$dt['json'] = json_encode($json, true);


			$this->db->update('transaction', $dt, array('id' => $id_trx));
		}

		$html = $data;
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($html, true);
	}
	function get_user_detail()
	{
		$id = $_GET['id'];
		$id_trx = $_GET['id_trx'];

		$query = $this->mymodel->selectWithQuery("SELECT *
		FROM user WHERE full_name = '$id' ");

		$query = $query[0];

		if ($id_trx) {
			$dt = array();
			$dt['cs_phone'] = strval($query['phone']);


			$this->db->update('transaction', $dt, array('id' => $id_trx));
		}


		$html = $query;
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($html, true);
	}

	function get_summary_campaign()
	{
		$id = $_GET['id'];
		$id_campaign = $_GET['id_campaign'];
		$type = $_GET['type'];
		$start_date = $_GET['start_date'];
		$until_date = $_GET['until_date'];
		$start_year = $_GET['start_year'];
		$until_year = $_GET['until_year'];
		$start_month = $_GET['start_month'];
		$until_month = $_GET['until_month'];
		$start_week = $_GET['start_week'];
		$until_week = $_GET['until_week'];
		$brand = $_GET['brand'];

		$type = $_GET['type'];
		$start_date = $_GET['start_date'];
		$until_date = $_GET['until_date'];
		$start_year = $_GET['start_year'];
		$until_year = $_GET['until_year'];
		$start_month = $_GET['start_month'];
		$until_month = $_GET['until_month'];
		$start_week = $_GET['start_week'];
		$until_week = $_GET['until_week'];
		$site = $_GET['site'];
		$customer = $_GET['customer'];
		$mpu = $_GET['mpu'];

		if ($type == "Yearly") {
			$qry_opt = " YEAR(date) ";
			$start_date = $start_year . '-01-01';
			$until_date = $until_year . '-12-31';
			$group = "  GROUP BY YEAR(date) ";
		} else if ($type == "Monthly") {
			$qry_opt = " MONTH(date) ";
			$start_month = str_pad($start_month, 2, "0", STR_PAD_LEFT);
			$until_month = str_pad($until_month, 2, "0", STR_PAD_LEFT);
			$start_date = $start_year . '-' . $start_month . '-01';
			$until_date = $start_year . '-' . $until_month . '-31';
			$group = "  GROUP BY MONTH(date) ";
		} else if ($type == "Weekly") {
			$qry_opt = " WEEK(date) ";
			$start_week = str_pad($start_week, 2, "0", STR_PAD_LEFT);
			$until_week = str_pad($until_week, 2, "0", STR_PAD_LEFT);

			$year = $start_year;
			$week = $start_week;
			$start_date = date("Y-m-d", strtotime($year . "W" . $week . "1"));

			$year = $start_year;
			$week = $until_week;
			$until_date = date("Y-m-d", strtotime($year . "W" . $week . "7"));
			$group = "  GROUP BY WEEK(date) ";
		} else {
			$qry_opt = " DATE(date) ";
			$group = "  GROUP BY DATE(date) ";
		}


		$detail = $this->mymodel->selectWithQuery("SELECT * FROM endorse_campaign
		WHERE id = '$id_campaign'");
		$detail = $detail[0];

		if ($type == "Yearly") {
			$start_date = $start_year . '-01-01';
			$until_date = $until_year . '-12-31';
		} else if ($type == "Monthly") {
			$start_month = str_pad($start_month, 2, "0", STR_PAD_LEFT);
			$until_month = str_pad($until_month, 2, "0", STR_PAD_LEFT);
			$start_date = $start_year . '-' . $start_month . '-01';
			$until_date = $start_year . '-' . $until_month . '-31';
		} else if ($type == "Weekly") {
			$start_week = str_pad($start_week, 2, "0", STR_PAD_LEFT);
			$until_week = str_pad($until_week, 2, "0", STR_PAD_LEFT);

			$year = $start_year;
			$week = $start_week;
			$start_date = date("Y-m-d", strtotime($year . "W" . $week . "1"));

			$year = $start_year;
			$week = $until_week;
			$until_date = date("Y-m-d", strtotime($year . "W" . $week . "7"));
		}

		$qry = "";
		if ($brand) {
			$qry .= " AND brand = '$brand' ";
		}

		if ($_GET['keyword_category']) {
			$keyword_category = $_GET['keyword_category'];
		} else {
			$keyword_category = "Nama Creator";
		}
		$data['keyword_category'] = $keyword_category;
		$keyword = $_GET['keyword'];

		if ($_GET['start_date']) {
			$start_date = $_GET['start_date'];
		} else {
			$start_date = DATE("Y-m-01");
			
		}
		if ($_GET['until_date']) {
			$until_date = $_GET['until_date'];
		} else {
			$until_date = DATE('Y-m-d');
		}
		$data['start_date'] = $start_date;
		$data['until_date'] = $until_date;
		$qry = "";

		$ids = $_GET['ids'];
		$data['ids'] = $ids;
		if ($ids) {
			$qry .= " AND id  IN ($ids) ";
		}

		if ($brand) {
			$qry .= " AND brand = '$brand' ";
		}

		$cat = $_GET['cat'];
		if ($cat == "Tanggal Dibuat") {
			$qry .= " AND DATE(created_at) >= '$start_date' AND DATE(created_at) <= '$until_date' ";
		} else if ($cat == "Rencana Upload") {
			$qry .= " AND DATE(rencana_at) >= '$start_date' AND DATE(rencana_at) <= '$until_date' ";
		} else if ($cat == "Tanggal Posting") {
			$qry .= " AND DATE(posting_at) >= '$start_date' AND DATE(posting_at) <= '$until_date' ";
		} else {
			// $qry .= " AND DATE(created_at) >= '$start_date' AND DATE(created_at) <= '$until_date' ";
		}

		$status = $_GET['status'];
		if ($status) {
			if ($status == 'Ada Link Upload') {
				$qry .= " AND link_upload != '' ";
			} else if ($status == 'Tidak Ada Link Upload') {
				$qry .= " AND link_upload = '' ";
			} else if ($status == 'FYP') {
				$qry .= " AND is_fyp = 1 ";
			}
		}

		$status_payment = $_GET['status_payment'];
		$statusPaymentArray = $status_payment ? explode(',', $status_payment) : [];
		$text = '';
		foreach ($statusPaymentArray as $k => $v) {
			$text .= "'" . $v . "',";
		}
		$text = substr($text, 0, -1);

		if ($text) {
			$qry .= " AND status_payment IN ($text) ";
		}

		$status = $_GET['endorse_status'];
		$statusArray = $status ? explode(',', $status) : [];
		$text = '';
		foreach ($statusArray as $k => $v) {
			$text .= "'" . $v . "',";
		}
		$text = substr($text, 0, -1);

		if ($text) {
			$qry .= " AND status_endorse IN ($text) ";
		}

		$platform = $_GET['platform'];
		if ($platform) {
			$qry .= " AND platform = '$platform' ";
		}

		if ($keyword) {
			if ($keyword_category == "Nama Creator") {
				$qry .= " AND nama_creator LIKE '%$keyword%' ";
			} else if ($keyword_category == "Link Upload") {
				$qry .= " AND link_upload LIKE '%$keyword%' ";
			} else if ($keyword_category == "PIC") {
				$qry .= " AND pic LIKE '%$keyword%' ";
			} else if ($keyword_category == "Platform") {
				$qry .= " AND platform LIKE '%$keyword%' ";
			} else if ($keyword_category == "Task") {
				$qry .= " AND task LIKE '%$keyword%' ";
			} else if ($keyword_category == "Keterangan") {
				$qry .= " AND endorse.desc LIKE '%$keyword%' ";
			}
		}

		$status_data = $_GET['status_data'];

		if ($status_data) {
			$qry .= " AND endorse.status = '$status_data' ";
		}

		// echo $qry;die;

		$query = $this->mymodel->selectWithQuery("SELECT id
        FROM endorse
        WHERE id_campaign = '$id_campaign' $qry 
        ");
		
		$list = '';
		foreach ($query as $k => $v) {
			$list .= "'" . $v['id'] . "',";
		}
		$list = substr($list, 0, -1);

		// if ($list) {
		// 	$qry_list .= " AND id_endorse IN ($list) ";
		// }

		$text = "0";


		$data['checkbox'] = $_SESSION['checkbox'];

		if ($id == "mar-1") {
			$text = $this->template->separator_only($detail['budget']);
		} else if ($id == "mar-2") {
			$query = $this->mymodel->selectWithQuery("SELECT SUM(total_cost) as result FROM endorse WHERE id_campaign = '$id_campaign' $qry");
			$query = $query[0];
			$text = $this->template->separator_only($query['result']);
		} else if ($id == "mar-3") {
			// $query = $this->mymodel->selectWithQuery("SELECT 
			// -- AVG(cpm) as result
			// SUM(total_cost) / SUM(views) * 1000 as result
			// FROM endorse WHERE id_campaign = '$id_campaign' $qry");
			// $query = $query[0];
			$text = $this->template->separator_only($query['result']);
		} else if ($id == "mar-4") {
			$query = $this->mymodel->selectWithQuery("SELECT influencer as id FROM endorse WHERE id_campaign = '$id_campaign' $qry GROUP BY influencer");
			$text = "0,";
			foreach ($query as $k => $v) {
				$text .= $v['id'] . ',';
			}
			$text = substr($text, 0, -1);
			$qry = "";
			$query = $this->mymodel->selectWithQuery("SELECT COUNT(id) AS count
            FROM influencer
            WHERE id IN ($text) $qry 
            ");
			$text = $this->template->separator_only($query[0]['count']);
		} else if ($id == "mar-5") {
			$query = $this->mymodel->selectWithQuery("SELECT COUNT(id) as result FROM endorse WHERE id_campaign = '$id_campaign' AND is_fyp = 1 $qry");
			$query = $query[0];

			$query_2 = $this->mymodel->selectWithQuery("SELECT COUNT(id) as result FROM endorse WHERE id_campaign = '$id_campaign' $qry");
			$query_2 = $query_2[0];

			$text = $this->template->separator_only($query['result']) . '/' . $this->template->separator_only($query_2['result']);
		} else if ($id == "mar-6") {
			// $list = $this->mymodel->selectWithQuery("SELECT *
			// FROM endorse_logs
			// WHERE DATE(date) >= '$start_date' AND DATE(date) <= '$until_date' $qry_list AND id_campaign = '$id_campaign'
			// GROUP BY id_endorse
			// ORDER BY DATE(date) DESC");
			// $query['result'] = 0;
			// foreach($list as $k=>$v){
			// 	if($data['checkbox'][0]=='false'){
			// 		$query['result'] += intval($v['views_after']);
			// 	}else{
			// 		$query['result'] += intval($v['views']);
			// 	}
			// }
			$text = $this->template->separator_only($query['result']);
		} else if ($id == "mar-7") {
			// $list = $this->mymodel->selectWithQuery("SELECT *
			// FROM endorse_logs
			// WHERE DATE(date) >= '$start_date' AND DATE(date) <= '$until_date' $qry_list AND id_campaign = '$id_campaign'
			// GROUP BY id_endorse
			// ORDER BY DATE(date) DESC");
			// $query['result'] = 0;
			// foreach($list as $k=>$v){
			// 	if($data['checkbox'][0]=='false'){
			// 		$query['result'] += intval($v['likes_after']);
			// 	}else{
			// 		$query['result'] += intval($v['likes']);
			// 	}
			// }
			$text = $this->template->separator_only($query['result']);
		} else if ($id == "mar-8") {
			// $list = $this->mymodel->selectWithQuery("SELECT *
			// FROM endorse_logs
			// WHERE DATE(date) >= '$start_date' AND DATE(date) <= '$until_date' $qry_list AND id_campaign = '$id_campaign'
			// GROUP BY id_endorse
			// ORDER BY DATE(date) DESC
			// ");
			// $query['result'] = 0;
			// foreach($list as $k=>$v){
			// 	if($data['checkbox'][0]=='false'){
			// 		$query['result'] += intval($v['comment_after']);
			// 	}else{
			// 		$query['result'] += intval($v['comment']);
			// 	}
			// }
			$text = $this->template->separator_only($query['result']);
		}
		$html['html'] = $text;
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($html, true);
	}

	function get_summary()
	{
		// Initialize all variables to prevent undefined variable warnings
		$qry = "";
		$qry_2 = "";
		$qry_trx = "";
		$qry_stock = "";
		$grand_total_hpp = 0;
		
		// Get input parameters
		$id = $_GET['id'] ?? '';
		$type = $_GET['type'] ?? '';
		$channel = $_GET['channel'] ?? '';
		$start_date = $_GET['start_date'] ?? '';
		$until_date = $_GET['until_date'] ?? '';
		$start_year = $_GET['start_year'] ?? '';
		$until_year = $_GET['until_year'] ?? '';
		$start_month = $_GET['start_month'] ?? '';
		$until_month = $_GET['until_month'] ?? '';
		$start_week = $_GET['start_week'] ?? '';
		$until_week = $_GET['until_week'] ?? '';
		$brand = $_GET['brand'] ?? '';

		// Initialize cache system
		try {
			$this->load->driver('cache', array('adapter' => 'memcached'));
		} catch (Exception $e) {
			// If cache fails, continue without caching
			log_message('error', 'Cache initialization failed in get_summary: ' . $e->getMessage());
		}

		// Generate granular cache key based on specific ID and parameters
		$cache_key_params = array(
			'id' => $id,
			'type' => $type,
			'channel' => $channel,
			'brand' => $brand,
			'start_date' => $start_date,
			'until_date' => $until_date
		);
		$granular_cache_key = 'get_summary_' . $id . '_' . md5(serialize($cache_key_params));
		
		// Try to get individual ID result from cache first
		try {
			if (isset($this->cache)) {
				$cached_result = $this->cache->get($granular_cache_key);
				if ($cached_result !== FALSE) {
					header('Content-Type: application/json; charset=utf-8');
					echo json_encode($cached_result, true);
					return;
				}
			}
		} catch (Exception $e) {
			log_message('error', 'Failed to get cache in get_summary: ' . $e->getMessage());
		}

		// Optimize date calculations using helper method
		$date_range = $this->calculateDateRange($type, $start_date, $until_date, $start_year, $until_year, $start_month, $until_month, $start_week, $until_week);
		$start_date = $date_range['start_date'];
		$until_date = $date_range['until_date'];

		// Build query conditions more efficiently
		$query_conditions = $this->buildQueryConditions($brand, $channel);
		$qry = $query_conditions['qry'];
		$qry_2 = $query_conditions['qry_2'];
		$qry_trx = $query_conditions['qry_trx'];
		$qry_stock = $query_conditions['qry_stock'];


		$until_date_2 = $start_date;
		$until_date_2 = date("Y-m-d", strtotime($until_date_2 . " -1 days"));
		$timestamp1 = strtotime($start_date);
		$timestamp2 = strtotime($until_date);
		$interval = abs($timestamp2 - $timestamp1);
		$interval_days = floor($interval / (60 * 60 * 24));
		$start_date_2 = date("Y-m-d", strtotime($until_date_2 . " -$interval_days days"));


		$text = "0";
		$progress = '<div class="text-black"><i class="bi bi-chevron-double-right"></i> 0%</div>';

		// Fast-path optimization for most common dashboard queries
		$fast_path_queries = array(
			'order-1' => array(
				'current' => "SELECT COUNT(id) as result FROM transaction WHERE DATE(date) >= '$start_date' AND DATE(date) <= '$until_date' AND type_sub = 'POS' AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') $qry",
				'previous' => "SELECT COUNT(id) as result FROM transaction WHERE DATE(date) >= '$start_date_2' AND DATE(date) <= '$until_date_2' AND type_sub = 'POS' AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') $qry"
			),
			'order-2' => array(
				'current' => "SELECT COUNT(id) as result FROM transaction WHERE DATE(date) >= '$start_date' AND DATE(date) <= '$until_date' AND order_status IN ('PENDING','READY_TO_SHIP')  AND type_sub = 'POS' $qry",
				'previous' => "SELECT COUNT(id) as result FROM transaction WHERE DATE(date) >= '$start_date_2' AND DATE(date) <= '$until_date_2' AND order_status IN ('PENDING','READY_TO_SHIP')  AND type_sub = 'POS' $qry"
			),
			'order-5' => array(
				'current' => "SELECT SUM(omset_kotor) as result FROM transaction WHERE DATE(date) >= '$start_date' AND DATE(date) <= '$until_date' AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') AND type_sub = 'POS' $qry",
				'previous' => "SELECT SUM(omset_kotor) as result FROM transaction WHERE DATE(date) >= '$start_date_2' AND DATE(date) <= '$until_date_2' AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') AND type_sub = 'POS' $qry"
			)
		);

		if (isset($fast_path_queries[$id])) {
			$cache_key_current = "fast_{$id}_current_{$start_date}_{$until_date}_" . md5($qry);
			$cache_key_previous = "fast_{$id}_previous_{$start_date_2}_{$until_date_2}_" . md5($qry);
			
			$query = $this->executeCachedQuery($fast_path_queries[$id]['current'], $cache_key_current, 60);
			$query = $query[0];
			$text = $this->template->separator_only($query['result']);

			$query_2 = $this->executeCachedQuery($fast_path_queries[$id]['previous'], $cache_key_previous, 60);
			$query_2 = $query_2[0];
		} else if ($id == "order-1") {
			$cache_key_1 = "order1_{$start_date}_{$until_date}_" . md5($qry);
			$query = $this->executeCachedQuery("SELECT COUNT(id) as result FROM transaction WHERE DATE(date) >= '$start_date' AND DATE(date) <= '$until_date' AND type_sub = 'POS' AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') $qry", $cache_key_1, 60);
			$query = $query[0];
			$text = $this->template->separator_only($query['result']);

			$cache_key_1_2 = "order1_prev_{$start_date_2}_{$until_date_2}_" . md5($qry);
			$query_2 = $this->executeCachedQuery("SELECT COUNT(id) as result FROM transaction WHERE DATE(date) >= '$start_date_2' AND DATE(date) <= '$until_date_2' AND type_sub = 'POS' AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') $qry", $cache_key_1_2, 60);
			$query_2 = $query_2[0];
		} else if ($id == "order-2") {
			$cache_key_2 = "order2_{$start_date}_{$until_date}_" . md5($qry);
			$query = $this->executeCachedQuery("SELECT COUNT(id) as result FROM transaction WHERE DATE(date) >= '$start_date' AND DATE(date) <= '$until_date' AND order_status IN ('PENDING','READY_TO_SHIP')  AND type_sub = 'POS' $qry", $cache_key_2, 60);
			$query = $query[0];
			$text = $this->template->separator_only($query['result']);

			$cache_key_2_2 = "order2_prev_{$start_date_2}_{$until_date_2}_" . md5($qry);
			$query_2 = $this->executeCachedQuery("SELECT COUNT(id) as result FROM transaction WHERE DATE(date) >= '$start_date_2' AND DATE(date) <= '$until_date_2' AND order_status IN ('PENDING','READY_TO_SHIP')  AND type_sub = 'POS' $qry", $cache_key_2_2, 60);
			$query_2 = $query_2[0];
		} else if ($id == "order-3") {
			// Batch execute both current and previous queries for order-3
			$queries_batch = array(
				'current' => "SELECT COUNT(id) as result FROM transaction WHERE DATE(date) >= '$start_date' AND DATE(date) <= '$until_date' AND payment_status IN ('Unpaid') AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') AND type_sub = 'POS' $qry",
				'previous' => "SELECT COUNT(id) as result FROM transaction WHERE DATE(date) >= '$start_date_2' AND DATE(date) <= '$until_date_2' AND payment_status IN ('Unpaid') AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') AND type_sub = 'POS' $qry"
			);
			
			$cache_key_3 = "order3_batch_{$start_date}_{$until_date}_" . md5($qry);
			$cache_key_3_2 = "order3_batch_prev_{$start_date_2}_{$until_date_2}_" . md5($qry);
			
			$query = $this->executeCachedQuery($queries_batch['current'], $cache_key_3, 60);
			$query = $query[0];
			$text = $this->template->separator_only($query['result']);

			$query_2 = $this->executeCachedQuery($queries_batch['previous'], $cache_key_3_2, 60);
			$query_2 = $query_2[0];

		} else if ($id == "order-4") {
			// Batch execute both current and previous queries for order-4
			$queries_batch = array(
				'current' => "SELECT COUNT(id) as result FROM transaction WHERE DATE(date) >= '$start_date' AND DATE(date) <= '$until_date' AND order_status IN ('RETURN')  AND type_sub = 'POS' $qry",
				'previous' => "SELECT COUNT(id) as result FROM transaction WHERE DATE(date) >= '$start_date_2' AND DATE(date) <= '$until_date_2' AND order_status IN ('RETURN') AND type_sub = 'POS' $qry"
			);
			
			$cache_key_4 = "order4_batch_{$start_date}_{$until_date}_" . md5($qry);
			$cache_key_4_2 = "order4_batch_prev_{$start_date_2}_{$until_date_2}_" . md5($qry);
			
			$query = $this->executeCachedQuery($queries_batch['current'], $cache_key_4, 60);
			$query = $query[0];
			$text = $this->template->separator_only($query['result']);

			$query_2 = $this->executeCachedQuery($queries_batch['previous'], $cache_key_4_2, 60);
			$query_2 = $query_2[0];
		} else if ($id == "order-5") {
			$cache_key_5 = "order5_{$start_date}_{$until_date}_" . md5($qry);
			$query = $this->executeCachedQuery("SELECT SUM(omset_kotor) as result FROM transaction WHERE DATE(date) >= '$start_date' AND DATE(date) <= '$until_date' AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') AND type_sub = 'POS' $qry", $cache_key_5, 60);
			$query = $query[0];
			$text = $this->template->separator_only($query['result']);

			$cache_key_5_2 = "order5_prev_{$start_date_2}_{$until_date_2}_" . md5($qry);
			$query_2 = $this->executeCachedQuery("SELECT SUM(omset_kotor) as result FROM transaction WHERE DATE(date) >= '$start_date_2' AND DATE(date) <= '$until_date_2' AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') AND type_sub = 'POS' $qry", $cache_key_5_2, 60);
			$query_2 = $query_2[0];
		} else if ($id == "order-6") {
			// Batch execute both current and previous queries for order-6
			$cache_key_6 = "order6_batch_{$start_date}_{$until_date}_" . md5($qry);
			$cache_key_6_2 = "order6_batch_prev_{$start_date_2}_{$until_date_2}_" . md5($qry);
			
			$query = $this->executeCachedQuery("SELECT SUM(diskon_penjual) as result FROM transaction WHERE DATE(date) >= '$start_date' AND DATE(date) <= '$until_date' AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') AND type_sub = 'POS' $qry", $cache_key_6, 60);
			$query = $query[0];
			$text = $this->template->separator_only($query['result']);

			$query_2 = $this->executeCachedQuery("SELECT SUM(diskon_penjual) as result FROM transaction WHERE DATE(date) >= '$start_date_2' AND DATE(date) <= '$until_date_2' AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') AND type_sub = 'POS' $qry", $cache_key_6_2, 60);
			$query_2 = $query_2[0];
		} else if ($id == "order-7") {
			// Batch execute both current and previous queries for order-7
			$cache_key_7 = "order7_batch_{$start_date}_{$until_date}_" . md5($qry);
			$cache_key_7_2 = "order7_batch_prev_{$start_date_2}_{$until_date_2}_" . md5($qry);
			
			$query = $this->executeCachedQuery("SELECT SUM(omset_kotor-diskon_penjual) as result FROM transaction WHERE DATE(date) >= '$start_date' AND DATE(date) <= '$until_date'AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') AND type_sub = 'POS' $qry", $cache_key_7, 60);
			$query = $query[0];
			$text = $this->template->separator_only($query['result']);

			$query_2 = $this->executeCachedQuery("SELECT SUM(omset_kotor-diskon_penjual) as result FROM transaction WHERE DATE(date) >= '$start_date_2' AND DATE(date) <= '$until_date_2'AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') AND type_sub = 'POS' $qry", $cache_key_7_2, 60);
			$query_2 = $query_2[0];
		} else if ($id == "order-8") {
			$brand_filter = $this->input->get('brand');

			// Use the original hitung_pengeluaran method with caching
			$cache_key_spending = "pengeluaran_{$start_date}_{$until_date}_" . md5($brand_filter);
			$cached_spending = null;

			if (isset($this->cache)) {
				$cached_spending = $this->cache->get($cache_key_spending);
			}

			if ($cached_spending !== FALSE && $cached_spending !== null) {
				$result = $cached_spending;
			} else {
				$result = $this->hitung_pengeluaran($start_date, $until_date, $start_date, $until_date, $brand_filter);
				if (isset($this->cache)) {
					$this->cache->save($cache_key_spending, $result, 60);
				}
			}

			$total_spending = isset($result['text']) ? $result['text'] : 0;

			// Use cached transaction summary for net sales after marketplace fee
			$net_sales_after_fee = $this->getCachedTransactionSummary($start_date, $until_date, $qry, 'net_sales_after_fee');
			if (!$net_sales_after_fee) {
				$net_sales_after_fee = $this->mymodel->selectWithQuery("
					SELECT SUM(omset_kotor - diskon_penjual - marketplace_fee) as result
					FROM transaction
					WHERE DATE(date) BETWEEN '$start_date' AND '$until_date'
					AND type_sub = 'POS'
					AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') $qry
				");
			}
			$net_sales_after_fee_value = isset($net_sales_after_fee[0]['result']) ? $net_sales_after_fee[0]['result'] : 0;

			// Use cached HPP calculation
			$grand_total_hpp = $this->getCachedHPPCalculations($start_date, $until_date, $qry_stock);

			// Calculate net profit
			$net_profit = intval($net_sales_after_fee_value) - intval($total_spending) - intval($grand_total_hpp);
			$text = $this->template->separator_only($net_profit);

			// Use cached net sales for percentage calculation
			$penjualan_bersih = $this->getCachedTransactionSummary($start_date, $until_date, $qry, 'net_sales');
			$penjualan_bersih_result = isset($penjualan_bersih[0]['result']) ? doubleval($penjualan_bersih[0]['result']) : 0;

			if ($penjualan_bersih_result > 0) {
				$val = ($net_profit / $penjualan_bersih_result) * 100;
				$progress = '<div class="text-blue">' . intval($val) . '%</div>';
			} else {
				$progress = '<div class="text-black"> N/A</div>';
			}
		} else if ($id == "order-9") {
			// Use cached marketplace fee calculation
			$marketplace_fee = $this->getCachedTransactionSummary($start_date, $until_date, $qry, 'marketplace_fee');
			$marketplace_fee_value = isset($marketplace_fee[0]['result']) ? $marketplace_fee[0]['result'] : 0;
			$text = $this->template->separator_only($marketplace_fee_value);

			// Use cached net sales calculation for percentage
			$penjualan_bersih = $this->getCachedTransactionSummary($start_date, $until_date, $qry, 'net_sales');
			$penjualan_bersih_result = isset($penjualan_bersih[0]['result']) ? doubleval($penjualan_bersih[0]['result']) : 0;

			if ($penjualan_bersih_result > 0) {
				$val = ($marketplace_fee_value / $penjualan_bersih_result) * 100;
				$progress = '<div class="text-blue">' . intval($val) . '%</div>';
			} else {
				$progress = '<div class="text-black"> N/A</div>';
			}
		} else if ($id == "order-10") {
			$query = $this->mymodel->selectWithQuery("SELECT SUM(komisi_afiliasi) as result FROM transaction WHERE DATE(date) >= '$start_date' AND DATE(date) <= '$until_date' AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') AND type_sub = 'POS' $qry");
			$query = $query[0];
			$text = $this->template->separator_only($query['result']);

			$query_2 = $this->mymodel->selectWithQuery("SELECT SUM(komisi_afiliasi) as result FROM transaction WHERE DATE(date) >= '$start_date_2' AND DATE(date) <= '$until_date_2' AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') AND type_sub = 'POS' $qry");
			$query_2 = $query_2[0];
		} else if ($id == "order-11") {
			$brand_filter = $this->input->get('brand');

			// Use the same calculation as Dashboard hitung_pengeluaran method with caching
			$cache_key_spending = "order11_pengeluaran_{$start_date}_{$until_date}_" . md5($brand_filter);
			$cached_spending = null;

			if (isset($this->cache)) {
				$cached_spending = $this->cache->get($cache_key_spending);
			}

			if ($cached_spending !== FALSE && $cached_spending !== null) {
				$result = $cached_spending;
			} else {
				$result = $this->hitung_pengeluaran($start_date, $until_date, $start_date, $until_date, $brand_filter);
				if (isset($this->cache)) {
					$this->cache->save($cache_key_spending, $result, 60);
				}
			}

			$total_spend = isset($result['text']) ? $result['text'] : 0;
			$text = $this->template->separator_only($total_spend);

			// Use cached net sales calculation for percentage
			$penjualan_bersih = $this->getCachedTransactionSummary($start_date, $until_date, $qry, 'net_sales');
			$penjualan_bersih_result = isset($penjualan_bersih[0]['result']) ? doubleval($penjualan_bersih[0]['result']) : 0;

			if ($penjualan_bersih_result > 0) {
				$val = ($total_spend / $penjualan_bersih_result) * 100;
				$progress = '<div class="text-blue">' . intval($val) . '%</div>';
			} else {
				$progress = '<div class="text-black"> N/A</div>';
			}
		} else if ($id == "order-12") {
			// Use cached HPP calculation for much better performance
			$grand_total_hpp = $this->getCachedHPPCalculations($start_date, $until_date, $qry_stock);
			$text = $this->template->separator_only($grand_total_hpp);

			// Use cached net sales calculation for percentage
			$penjualan_bersih = $this->getCachedTransactionSummary($start_date, $until_date, $qry, 'net_sales');
			$penjualan_bersih_result = isset($penjualan_bersih[0]['result']) ? doubleval($penjualan_bersih[0]['result']) : 0;

			if ($penjualan_bersih_result > 0) {
				$val = ($grand_total_hpp / $penjualan_bersih_result) * 100;
				$progress = '<div class="text-blue">' . number_format($val, 0) . '%</div>';
			} else {
				$progress = '<div class="text-black"> N/A</div>';
			}
		} else if ($id == "order-13") {
			// $query = $this->mymodel->selectWithQuery("SELECT SUM(qty*hpp) as result FROM stock WHERE DATE(date) >= '$start_date' AND DATE(date) <= '$until_date'");
			// $query = $query[0];
			// $text = $this->template->separator_only($query['result']);

			// $query_2 = $this->mymodel->selectWithQuery("SELECT SUM(qty*hpp) as result FROM stock WHERE DATE(date) >= '$start_date_2' AND DATE(date) <= '$until_date_2'");
			// $query_2 = $query_2[0];

			$query = $this->mymodel->selectWithQuery("SELECT SUM(stock*price_buy) as result FROM product");
			$query = $query[0];
			$text = $this->template->separator_only($query['result']);

			// $query_2 = $this->mymodel->selectWithQuery("SELECT SUM(stock*price_buy) as result FROM product");
			// $query_2 = $query_2[0];
			$query_2 = $query;
		} else if ($id == "order-14") {
			$query = $this->mymodel->selectWithQuery("SELECT SUM(ongkir) as result FROM transaction WHERE DATE(date) >= '$start_date' AND DATE(date) <= '$until_date' AND type_sub = 'POS' $qry");
			$query = $query[0];
			$text = $this->template->separator_only($query['result']);

			$query_2 = $this->mymodel->selectWithQuery("SELECT SUM(ongkir) as result FROM transaction WHERE DATE(date) >= '$start_date_2' AND DATE(date) <= '$until_date_2' AND type_sub = 'POS' $qry");
			$query_2 = $query_2[0];
		} else if ($id == "order-15") {
			$query = $this->mymodel->selectWithQuery("SELECT SUM(omset_kotor) as result FROM transaction WHERE DATE(date) >= '$start_date' AND DATE(date) <= '$until_date' AND payment_status = 'Unpaid' AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED') AND type_sub = 'POS' $qry");
			$query = $query[0];
			$text = $this->template->separator_only($query['result']);

			$query_2 = $this->mymodel->selectWithQuery("SELECT SUM(omset_kotor) as result FROM transaction WHERE DATE(date) >= '$start_date_2' AND DATE(date) <= '$until_date_2' AND payment_status = 'Unpaid' AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED') AND type_sub = 'POS' $qry");
			$query_2 = $query_2[0];
		} else if ($id == "order-16") {
			$query = $this->mymodel->selectWithQuery("SELECT SUM(omset_kotor) as result FROM transaction WHERE DATE(date) >= '$start_date' AND DATE(date) <= '$until_date' AND order_status IN ('RETURN')  AND type_sub = 'POS' $qry");
			$query = $query[0];
			$text = $this->template->separator_only($query['result']);

			$query_2 = $this->mymodel->selectWithQuery("SELECT SUM(omset_kotor) as result FROM transaction WHERE DATE(date) >= '$start_date_2' AND DATE(date) <= '$until_date_2' AND order_status IN ('RETURN') AND type_sub = 'POS' $qry");
			$query_2 = $query_2[0];
		} else if ($id == "order-17") {
			$query = $this->mymodel->selectWithQuery("SELECT SUM(omset_kotor-diskon_penjual) as result FROM transaction WHERE DATE(date) >= '$start_date' AND DATE(date) <= '$until_date' AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') AND type_sub = 'POS' AND marketplace = '$channel' $qry");
			$query = $query[0];
			$text = $this->template->separator_only($query['result']);

			$query_2 = $this->mymodel->selectWithQuery("SELECT SUM(omset_kotor-diskon_penjual) as result FROM transaction WHERE DATE(date) >= '$start_date_2' AND DATE(date) <= '$until_date_2' AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID')  AND type_sub = 'POS' AND marketplace = '$channel' $qry");
			$query_2 = $query_2[0];
		} else if ($id == "order-18") {
			$query = $this->mymodel->selectWithQuery("SELECT SUM(komisi_afiliasi+diskon_penjual+marketplace_fee) as result FROM transaction WHERE DATE(date) >= '$start_date' AND DATE(date) <= '$until_date' AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') AND type_sub = 'POS' AND marketplace = '$channel' $qry");
			$query = $query[0];
			$text = $this->template->separator_only($query['result']);

			$query_2 = $this->mymodel->selectWithQuery("SELECT SUM(komisi_afiliasi+diskon_penjual+marketplace_fee) as result FROM transaction WHERE DATE(date) >= '$start_date_2' AND DATE(date) <= '$until_date_2' AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') AND type_sub = 'POS' AND marketplace = '$channel' $qry");
			$query_2 = $query_2[0];
		} else if ($id == "order-19") {
			$query = $this->mymodel->selectWithQuery("SELECT SUM(dana_pencairan) as result FROM transaction WHERE DATE(date) >= '$start_date' AND DATE(date) <= '$until_date' AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') AND type_sub = 'POS' AND marketplace = '$channel' $qry");
			$query = $query[0];
			$text = $this->template->separator_only($query['result']);

			$query_2 = $this->mymodel->selectWithQuery("SELECT SUM(dana_pencairan) as result FROM transaction WHERE DATE(date) >= '$start_date_2' AND DATE(date) <= '$until_date_2' AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') AND type_sub = 'POS' AND marketplace = '$channel' $qry");
			$query_2 = $query_2[0];
		} else if ($id == "order-20") {
			$query = $this->mymodel->selectWithQuery("SELECT COUNT(id) as result FROM transaction WHERE DATE(date) >= '$start_date' AND DATE(date) <= '$until_date' AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') AND type_sub = 'POS' AND marketplace = '$channel' $qry");
			$query = $query[0];
			$text = $this->template->separator_only($query['result']);

			$query_2 = $this->mymodel->selectWithQuery("SELECT COUNT(id) as result FROM transaction WHERE DATE(date) >= '$start_date_2' AND DATE(date) <= '$until_date_2' AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') AND type_sub = 'POS' AND marketplace = '$channel' $qry");
			$query_2 = $query_2[0];
		} else if ($id == "order-21") {
			$query = $this->mymodel->selectWithQuery("SELECT COUNT(id) as result FROM transaction WHERE DATE(date) >= '$start_date' AND DATE(date) <= '$until_date' AND dana_pencairan > 0 AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') AND type_sub = 'POS' AND marketplace = '$channel' $qry");
			$query = $query[0];

			$query_b = $this->mymodel->selectWithQuery("SELECT COUNT(id) as result FROM transaction WHERE DATE(date) >= '$start_date' AND DATE(date) <= '$until_date' AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') AND type_sub = 'POS' AND marketplace = '$channel' $qry");
			$query_b = $query_b[0];

			$text = $this->template->separator_only($query['result']) . '/' . $this->template->separator_only($query_b['result']);

			// $query_2 = $this->mymodel->selectWithQuery("SELECT COUNT(id) as result FROM transaction WHERE DATE(date) >= '$start_date_2' AND DATE(date) <= '$until_date_2' AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') AND type_sub = 'POS' AND marketplace = '$channel' $qry");
			// $query_2 = $query_2[0];

		} else if ($id == "order-22") {
			$query = $this->mymodel->selectWithQuery("
				SELECT SUM(omset_bersih) - FLOOR(SUM(marketplace_fee)) AS result 
				FROM transaction 
				WHERE DATE(date) >= '$start_date' AND DATE(date) <= '$until_date' 
				AND order_status IN ('PROCESSED','SHIPPED','COMPLETED', 'READY_TO_SHIP', 'DELIVERED') 
				AND dana_pencairan = 0 AND is_disbursement = 0
				AND type_sub = 'POS' $qry
			");
			$query = $query[0];
			$text = $this->template->separator_only($query['result']);
		
			$penjualan_bersih = $this->mymodel->selectWithQuery("
				SELECT SUM(omset_kotor - diskon_penjual) AS result 
				FROM transaction 
				WHERE DATE(date) >= '$start_date' AND DATE(date) <= '$until_date' 
				AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') AND c_type NOT IN ('Affiliate','Endorse','Free') AND type_sub = 'POS' $qry
			");
			$penjualan_bersih_result = doubleval($penjualan_bersih[0]['result']);
		
			if ($penjualan_bersih_result > 0) {
				$val = (intval($query['result']) / $penjualan_bersih_result) * 100;
				$text_2 = intval($val) . '%';
				$progress = '<div class="text-blue">' . intval($val) . '%</div>';
			} else {
				$text_2 = 'N/A';
				$progress = '<div class="text-black">N/A</div>';
			}
		} else if ($id == "order-23") {
			$query = $this->mymodel->selectWithQuery("SELECT COUNT(id) AS result FROM transaction WHERE DATE(date) >= '$start_date' AND DATE(date) <= '$until_date' AND order_status IN ('PROCESSED','SHIPPED','COMPLETED', 'READY_TO_SHIP', 'DELIVERED') AND dana_pencairan = 0 AND is_disbursement = 0 AND c_type NOT IN ('Affiliate','Endorse','Free') $qry");
			$query = $query[0];
			$text = $this->template->separator_only($query['result']);

			$penjualan_bersih = $this->mymodel->selectWithQuery("SELECT COUNT(id) as result FROM transaction WHERE DATE(date) >= '$start_date' AND DATE(date) <= '$until_date' AND type_sub = 'POS' AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') $qry ");
			
			$penjualan_bersih_result = doubleval($penjualan_bersih[0]['result']);
		
			if ($penjualan_bersih_result > 0) {
				$val = (intval($query['result']) / $penjualan_bersih_result) * 100;
				$text_2 = intval($val) . '%';
				$progress = '<div class="text-blue">' . intval($val) . '%</div>';
			} else {
				$text_2 = 'N/A';
				$progress = '<div class="text-black">N/A</div>';
			}
		}

		if ($id == "calendar") {

			$list = $this->mymodel->selectWithQuery("SELECT *
			FROM endorse");
			foreach ($list as $k => $v) {
				$date_1 = DATE("Y-m-d", strtotime($v['created_at'])) . 'T08:00';
				$date_2 = $date_1;
				if ($v['nama_creator'] == "") {
					$v['nama_creator'] = '-';
				}
				$title = $v['nama_creator'];
				$item .= ' {
					title: "' . $title . '",
					start: "' . $date_1 . '",
					end: "' . $date_2 . '",
					id: "' . $v['id'] . '",
					type: "endorse",
					color: "#4caf50"
				},';
			}
			$text = '
			<script>
			$(document).ready(function() {
			
				$("#calendar").fullCalendar({
					header: {
						left: "prev,next today",
						center: "title",
						right: "listDay,listWeek,month"
					},
					timeFormat: "H:mm",
			
					// customize the button names,
					// otherwise they"d all just say "list"
					views: {
						listDay: {
							buttonText: "list day"
						},
						listWeek: {
							buttonText: "list week"
						}
					},
			
					eventClick: function(arg) {
						window.open("' . base_url() . '/endorse/detail?id="+arg.id, "_blank");
					},
			
					defaultView: "month",
					defaultDate: "' . DATE("Y-m-d") . '",
					navLinks: true, // can click day/week names to navigate views
					editable: false,
					eventLimit: true, // allow "more" link when too many events
					events: [
						' . $item . '
						
						
								]
				});
			
			});
			</script>
			
			<div id="calendar"></div>
			';
		} else if ($id == "order-21") {
			if ($query['result'] > 0 && $query_b['result'] > 0) {
				$val = $query['result'] * 100 / ($query_b['result']);
				$val = $this->template->separator_only($val);
				$progress = '<div class="text-black"><i class="bi bi-chevron-double-right"></i> ' . $val . '%</div>';
			} else {
				$progress = '<div class="text-black"><i class="bi bi-chevron-double-right"></i> 0%</div>';
			}
		} else if ($id !== "order-8" && $id !== "order-9" && $id !== "order-11" && $id !== "order-12" && $id !== "order-22"){
			$query['result'] = doubleval($query['result']);
			$query_2['result'] = doubleval($query_2['result']);

			if ($query['result'] > 0 && $query_2['result'] == 0) {
			} else if ($query['result'] == 0 && $query_2['result'] > 0) {
				$progress = '<div class="text-red"><i class="bi bi-chevron-double-down"></i> 100%</div>';
			} else {
				if ($query['result'] > 0 && $query_2['result'] > 0) {
					if ($query['result'] > $query_2['result']) {
						$val = $query['result'] * 100 / ($query['result'] + $query_2['result']);
						$val = $this->template->separator_only($val);
					} else {
						$val = $query_2['result'] * 100 / ($query['result'] + $query_2['result']);
						$val = $this->template->separator_only($val);
					}
				}
				if ($query['result'] > $query_2['result']) {
					$progress = '<div class="text-green"><i class="bi bi-chevron-double-up"></i> ' . $val . '%</div>';
				} else if ($query['result'] < $query_2['result']) {
					$progress = '<div class="text-red"><i class="bi bi-chevron-double-down"></i> ' . $val . '%</div>';
				} else {
					$progress = '<div class="text-black"><i class="bi bi-chevron-double-right"></i> 0%</div>';
				}
			}
		}

		$html['html'] = $text;
		$html['progress'] = $progress;
		
		// Cache the final result for faster subsequent requests (30 second aggressive caching)
		try {
			if (isset($this->cache)) {
				$this->cache->save($granular_cache_key, $html, 30); // Aggressive 30-second cache for performance
			}
		} catch (Exception $e) {
			log_message('error', 'Failed to save cache in get_summary: ' . $e->getMessage());
		}
		
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($html, true);
	}
	
	function get_summary_batch()
	{
		$start_time = microtime(true);
		
		// Initialize response array
		$batch_response = array();
		$errors = array();
		
		// Get batch request parameters
		$metric_ids = $_GET['metric_ids'] ?? array();
		$common_params = array(
			'type' => $_GET['type'] ?? '',
			'channel' => $_GET['channel'] ?? '',
			'start_date' => $_GET['start_date'] ?? '',
			'until_date' => $_GET['until_date'] ?? '',
			'start_year' => $_GET['start_year'] ?? '',
			'until_year' => $_GET['until_year'] ?? '',
			'start_month' => $_GET['start_month'] ?? '',
			'until_month' => $_GET['until_month'] ?? '',
			'start_week' => $_GET['start_week'] ?? '',
			'until_week' => $_GET['until_week'] ?? '',
			'brand' => $_GET['brand'] ?? '',
			'site' => $_GET['site'] ?? ''
		);
		
		// Validate input
		if (empty($metric_ids)) {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array('error' => 'No metric IDs provided'), true);
			return;
		}
		
		// If metric_ids is a string, convert to array
		if (is_string($metric_ids)) {
			$metric_ids = explode(',', $metric_ids);
		}
		
		// Initialize cache system
		try {
			$this->load->driver('cache', array('adapter' => 'memcached'));
		} catch (Exception $e) {
			log_message('error', 'Cache initialization failed in get_summary_batch: ' . $e->getMessage());
		}
		
		// Generate batch cache key
		$batch_cache_key_params = array_merge($common_params, array('metrics' => implode(',', $metric_ids)));
		$batch_cache_key = 'batch_summary_v2_' . md5(serialize($batch_cache_key_params));
		
		// Try to get entire batch from cache first (2-minute cache for faster iteration)
		try {
			if (isset($this->cache)) {
				$cached_batch = $this->cache->get($batch_cache_key);
				if ($cached_batch !== FALSE && isset($cached_batch['data'])) {
					$cached_batch['cached'] = true;
					$cached_batch['execution_time'] = microtime(true) - $start_time;
					header('Content-Type: application/json; charset=utf-8');
					echo json_encode($cached_batch, true);
					return;
				}
			}
		} catch (Exception $e) {
			log_message('error', 'Failed to get batch cache: ' . $e->getMessage());
		}
		
		// SIMPLIFIED: Process each metric individually but with shared setup
		$original_get = $_GET;
		
		foreach ($metric_ids as $metric_id) {
			$metric_id = trim($metric_id);
			if (empty($metric_id)) continue;
			
			try {
				// Set parameters for individual get_summary call
				$_GET = array_merge($common_params, array('id' => $metric_id));
				
				// Capture output from get_summary function
				ob_start();
				$this->get_summary();
				$output = ob_get_clean();
				
				// Parse the JSON response
				$metric_data = json_decode($output, true);
				
				if ($metric_data && !empty($metric_data)) {
					$batch_response[$metric_id] = $metric_data;
				} else {
					$errors[$metric_id] = 'Failed to parse response: ' . substr($output, 0, 100);
				}
				
			} catch (Exception $e) {
				$errors[$metric_id] = $e->getMessage();
			}
		}
		
		// Restore original $_GET
		$_GET = $original_get;
		
		// Prepare final response
		$final_response = array(
			'success' => true,
			'data' => $batch_response,
			'processed_count' => count($batch_response),
			'total_requested' => count($metric_ids),
			'execution_time' => microtime(true) - $start_time,
			'cached' => false
		);
		
		if (!empty($errors)) {
			$final_response['errors'] = $errors;
			$final_response['error_count'] = count($errors);
		}
		
		// Cache the entire batch result for 2 minutes
		try {
			if (isset($this->cache) && count($batch_response) > 0) {
				$this->cache->save($batch_cache_key, $final_response, 120);
			}
		} catch (Exception $e) {
			log_message('error', 'Failed to save batch cache: ' . $e->getMessage());
		}
		
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($final_response, true);
	}
	
	private function process_metrics_batch_optimized($metric_ids, $common_params, &$errors)
	{
		$batch_response = array();
		
		// Optimize date calculations once for all metrics
		$date_range = $this->calculateDateRange(
			$common_params['type'], 
			$common_params['start_date'], 
			$common_params['until_date'],
			$common_params['start_year'], 
			$common_params['until_year'],
			$common_params['start_month'], 
			$common_params['until_month'],
			$common_params['start_week'], 
			$common_params['until_week']
		);
		$start_date = $date_range['start_date'];
		$until_date = $date_range['until_date'];
		
		// Build query conditions once for all metrics
		$query_conditions = $this->buildQueryConditions($common_params['brand'], $common_params['channel']);
		$qry = $query_conditions['qry'];
		$qry_stock = $query_conditions['qry_stock'];
		
		// Calculate previous period dates once
		$until_date_2 = date("Y-m-d", strtotime($start_date . " -1 days"));
		$timestamp1 = strtotime($start_date);
		$timestamp2 = strtotime($until_date);
		$interval = abs($timestamp2 - $timestamp1);
		$interval_days = floor($interval / (60 * 60 * 24));
		$start_date_2 = date("Y-m-d", strtotime($until_date_2 . " -$interval_days days"));
		
		// Group metrics by type for batch SQL execution
		$metric_groups = array(
			'order_counts' => array(),
			'order_sums' => array(),
			'calculated' => array()
		);
		
		foreach ($metric_ids as $metric_id) {
			$metric_id = trim($metric_id);
			if (empty($metric_id)) continue;
			
			if (in_array($metric_id, array('order-1', 'order-2', 'order-4'))) {
				$metric_groups['order_counts'][] = $metric_id;
			} elseif (in_array($metric_id, array('order-5', 'order-6', 'order-7', 'order-9'))) {
				$metric_groups['order_sums'][] = $metric_id;
			} else {
				$metric_groups['calculated'][] = $metric_id;
			}
		}
		
		// Execute batch queries for each group
		try {
			// Batch process order counts
			if (!empty($metric_groups['order_counts'])) {
				$count_results = $this->executeBatchOrderCounts($metric_groups['order_counts'], $start_date, $until_date, $start_date_2, $until_date_2, $qry);
				$batch_response = array_merge($batch_response, $count_results);
			}
			
			// Batch process order sums
			if (!empty($metric_groups['order_sums'])) {
				$sum_results = $this->executeBatchOrderSums($metric_groups['order_sums'], $start_date, $until_date, $start_date_2, $until_date_2, $qry);
				$batch_response = array_merge($batch_response, $sum_results);
			}
			
			// Process calculated metrics individually (these are complex)
			foreach ($metric_groups['calculated'] as $metric_id) {
				try {
					$original_get = $_GET;
					$_GET = array_merge($common_params, array('id' => $metric_id));
					
					ob_start();
					$this->get_summary();
					$output = ob_get_clean();
					
					$_GET = $original_get;
					
					$metric_data = json_decode($output, true);
					if ($metric_data) {
						$batch_response[$metric_id] = $metric_data;
					} else {
						$errors[$metric_id] = 'Failed to parse complex metric response';
					}
				} catch (Exception $e) {
					$errors[$metric_id] = $e->getMessage();
					$_GET = $original_get;
				}
			}
			
		} catch (Exception $e) {
			log_message('error', 'Batch processing error: ' . $e->getMessage());
			$errors['batch_processing'] = $e->getMessage();
		}
		
		return $batch_response;
	}
	
	private function executeBatchOrderCounts($metric_ids, $start_date, $until_date, $start_date_2, $until_date_2, $qry)
	{
		$start_time = microtime(true);
		$results = array();
		
		// Build one massive query that gets all count metrics at once
		$cache_key = "batch_counts_{$start_date}_{$until_date}_" . md5($qry);
		
		$sql = "
			SELECT 
				COUNT(CASE WHEN order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') THEN 1 END) as order_1_current,
				COUNT(CASE WHEN order_status IN ('PENDING','READY_TO_SHIP') THEN 1 END) as order_2_current,
				COUNT(CASE WHEN order_status IN ('RETURN') THEN 1 END) as order_4_current
			FROM transaction 
			WHERE DATE(date) >= '$start_date' AND DATE(date) <= '$until_date' 
			AND type_sub = 'POS' $qry
		";
		
		$current_results = $this->executeCachedQuery($sql, $cache_key, 60);
		log_message('debug', 'Batch counts current query time: ' . round((microtime(true) - $start_time), 3) . 's');
		
		// Previous period query
		$cache_key_prev = "batch_counts_prev_{$start_date_2}_{$until_date_2}_" . md5($qry);
		$sql_prev = "
			SELECT 
				COUNT(CASE WHEN order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') THEN 1 END) as order_1_previous,
				COUNT(CASE WHEN order_status IN ('PENDING','READY_TO_SHIP') THEN 1 END) as order_2_previous,
				COUNT(CASE WHEN order_status IN ('RETURN') THEN 1 END) as order_4_previous
			FROM transaction 
			WHERE DATE(date) >= '$start_date_2' AND DATE(date) <= '$until_date_2' 
			AND type_sub = 'POS' $qry
		";
		
		$previous_results = $this->executeCachedQuery($sql_prev, $cache_key_prev, 60);
		
		if (!empty($current_results) && !empty($previous_results)) {
			$current = $current_results[0];
			$previous = $previous_results[0];
			
			foreach ($metric_ids as $metric_id) {
				$current_key = $metric_id . '_current';
				$previous_key = $metric_id . '_previous';
				
				if (isset($current[$current_key]) && isset($previous[$previous_key])) {
					$current_value = intval($current[$current_key]);
					$previous_value = intval($previous[$previous_key]);
					$progress_percent = $previous_value > 0 ? round((($current_value - $previous_value) / $previous_value) * 100, 1) : 0;
					
					$results[$metric_id] = array(
						'html' => $this->template->separator_only($current_value),
						'progress' => '<div class="text-black"><i class="bi bi-chevron-double-right"></i> ' . $progress_percent . '%</div>'
					);
				}
			}
		}
		
		return $results;
	}
	
	private function executeBatchOrderSums($metric_ids, $start_date, $until_date, $start_date_2, $until_date_2, $qry)
	{
		$results = array();
		
		// Build one query for all sum metrics
		$cache_key = "batch_sums_{$start_date}_{$until_date}_" . md5($qry);
		
		$sql = "
			SELECT 
				SUM(CASE WHEN order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') THEN omset_kotor ELSE 0 END) as order_5_current,
				SUM(CASE WHEN order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') THEN diskon_penjual ELSE 0 END) as order_6_current,
				SUM(CASE WHEN order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') THEN (omset_kotor - diskon_penjual) ELSE 0 END) as order_7_current,
				SUM(CASE WHEN order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') THEN marketplace_fee ELSE 0 END) as order_9_current
			FROM transaction 
			WHERE DATE(date) >= '$start_date' AND DATE(date) <= '$until_date' 
			AND type_sub = 'POS' $qry
		";
		
		$current_results = $this->executeCachedQuery($sql, $cache_key, 60);
		
		// Previous period query
		$cache_key_prev = "batch_sums_prev_{$start_date_2}_{$until_date_2}_" . md5($qry);
		$sql_prev = "
			SELECT 
				SUM(CASE WHEN order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') THEN omset_kotor ELSE 0 END) as order_5_previous,
				SUM(CASE WHEN order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') THEN diskon_penjual ELSE 0 END) as order_6_previous,
				SUM(CASE WHEN order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') THEN (omset_kotor - diskon_penjual) ELSE 0 END) as order_7_previous,
				SUM(CASE WHEN order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') THEN marketplace_fee ELSE 0 END) as order_9_previous
			FROM transaction 
			WHERE DATE(date) >= '$start_date_2' AND DATE(date) <= '$until_date_2' 
			AND type_sub = 'POS' $qry
		";
		
		$previous_results = $this->executeCachedQuery($sql_prev, $cache_key_prev, 60);
		
		if (!empty($current_results) && !empty($previous_results)) {
			$current = $current_results[0];
			$previous = $previous_results[0];
			
			foreach ($metric_ids as $metric_id) {
				$current_key = $metric_id . '_current';
				$previous_key = $metric_id . '_previous';
				
				if (isset($current[$current_key]) && isset($previous[$previous_key])) {
					$current_value = floatval($current[$current_key]);
					$previous_value = floatval($previous[$previous_key]);
					$progress_percent = $previous_value > 0 ? round((($current_value - $previous_value) / $previous_value) * 100, 1) : 0;
					
					$results[$metric_id] = array(
						'html' => $this->template->separator_only($current_value),
						'progress' => '<div class="text-black"><i class="bi bi-chevron-double-right"></i> ' . $progress_percent . '%</div>'
					);
				}
			}
		}
		
		return $results;
	}
	
	function hitung_pengeluaran($start_date, $until_date, $start_date_2, $until_date_2, $brand_filter)
	{
		$firstLetter = !empty($brand_filter) ? strtoupper(substr($brand_filter, 0, 1)) : '';

		$shopee_brand = !empty($firstLetter) ? "AND shop_name LIKE '{$firstLetter}%'" : "";
		$tiktok_brand = !empty($firstLetter) ? "AND advertiser_name LIKE '{$firstLetter}%'" : "";
		$meta_brand = !empty($firstLetter) ? "AND account_name LIKE '{$firstLetter}%'" : "";
		$gmv_brand = !empty($firstLetter) ? "AND advertiser_name LIKE '{$firstLetter}%'" : "";
		$brand_like = !empty($firstLetter) ? "AND brand LIKE '{$firstLetter}%'" : "";

		// === PERIODE 1 ===
		$sql_spend_ads = "
			SELECT 
				COALESCE(SUM(shopee.expense), 0) + 
				COALESCE(SUM(meta.spend), 0) + 
				COALESCE(SUM(tiktok.spend_idr), 0) + 
				COALESCE(SUM(gmv.spend_idr_after_tax), 0) AS total_spend_ads
			FROM (
				SELECT DATE(date) AS date, SUM(expense_after_tax) AS expense
				FROM shopee_ads_data
				INNER JOIN marketplace_config ON marketplace_config.shop_id = shopee_ads_data.shop_id
				WHERE DATE(date) BETWEEN '$start_date' AND '$until_date' $shopee_brand
				GROUP BY DATE(date)
			) AS shopee
			LEFT JOIN (
				SELECT DATE(date) AS date, SUM(spend_after_tax) AS spend
				FROM meta_ads_data
				INNER JOIN ads_meta_account ON meta_ads_data.account_id = ads_meta_account.account_id
				WHERE DATE(date) BETWEEN '$start_date' AND '$until_date' $meta_brand
				GROUP BY DATE(date)
			) AS meta ON shopee.date = meta.date
			LEFT JOIN (
				SELECT DATE(date) AS date, SUM(spend_idr_after_tax) AS spend_idr
				FROM tiktok_ads_data
				WHERE DATE(date) BETWEEN '$start_date' AND '$until_date' $tiktok_brand
				GROUP BY DATE(date)
			) AS tiktok ON shopee.date = tiktok.date
			LEFT JOIN (
				SELECT DATE(date) AS date, SUM(spend_idr_after_tax) AS spend_idr_after_tax
				FROM advertiser_spend
				WHERE DATE(date) BETWEEN '$start_date' AND '$until_date' $gmv_brand
				GROUP BY DATE(date)
			) AS gmv ON shopee.date = gmv.date;
		";

		$data['spend_ads'] = $this->mymodel->selectWithQuery($sql_spend_ads);
		$data['spend_ads'] = !empty($data['spend_ads']) ? $data['spend_ads'][0] : ['total_spend_ads' => 0];

		$sql_spend_kol = "
            SELECT
                SUM(pl.nominal_dibayarkan) AS total_spend_kol
            FROM payment_logs pl
            JOIN endorse_campaign ec ON pl.id_campaign = ec.id
            WHERE DATE(pl.created_at) >= '$start_date' AND DATE(pl.created_at) <= '$until_date' AND pl.status_payment IN ('FP', 'DP')
            " . (!empty($firstLetter) ? "AND ec.brand LIKE '{$firstLetter}%'" : "") . "
        ";

		$data['spend_kol'] = $this->mymodel->selectWithQuery($sql_spend_kol);
		$data['spend_kol'] = !empty($data['spend_kol']) ? $data['spend_kol'][0] : ['total_spend_kol' => 0];

		$sql_spend_etc = "
			SELECT ABS(SUM(e.price_total)) AS total_spend_etc
			FROM expense e
			WHERE DATE(e.date) BETWEEN '$start_date' AND '$until_date' $brand_like;
		";

		$data['spend_etc'] = $this->mymodel->selectWithQuery($sql_spend_etc);
		$data['spend_etc'] = !empty($data['spend_etc']) ? $data['spend_etc'][0] : ['total_spend_etc' => 0];

		$total_spend = $data['spend_ads']['total_spend_ads'] + $data['spend_kol']['total_spend_kol'] + $data['spend_etc']['total_spend_etc'];

		// === PERIODE 2 === (Menggunakan query yang sama dengan periode 1, hanya ganti tanggal)
		$sql_spend_ads_2 = str_replace(["$start_date", "$until_date"], ["$start_date_2", "$until_date_2"], $sql_spend_ads);
		$sql_spend_kol_2 = str_replace(["$start_date", "$until_date"], ["$start_date_2", "$until_date_2"], $sql_spend_kol);
		$sql_spend_etc_2 = str_replace(["$start_date", "$until_date"], ["$start_date_2", "$until_date_2"], $sql_spend_etc);

		$spend_ads_2 = $this->mymodel->selectWithQuery($sql_spend_ads_2);
		$spend_kol_2 = $this->mymodel->selectWithQuery($sql_spend_kol_2);
		$spend_etc_2 = $this->mymodel->selectWithQuery($sql_spend_etc_2);

		$spend_ads_2 = !empty($spend_ads_2) ? $spend_ads_2[0]['total_spend_ads'] : 0;
		$spend_kol_2 = !empty($spend_kol_2) ? $spend_kol_2[0]['total_spend_kol'] : 0;
		$spend_etc_2 = !empty($spend_etc_2) ? $spend_etc_2[0]['total_spend_etc'] : 0;

		$total_spend_2 = $spend_ads_2 + $spend_kol_2 + $spend_etc_2;

		return [
			'text' => $total_spend,
			'text_2' => $this->template->separator_only($total_spend_2)
		];
	}

	function get_summary_v2()
	{
		$id = $_GET['id'];
		$type = $_GET['type'];
		$start_date = $_GET['start_date'];
		$until_date = $_GET['until_date'];
		$start_year = $_GET['start_year'];
		$until_year = $_GET['until_year'];
		$start_month = $_GET['start_month'];
		$until_month = $_GET['until_month'];
		$start_week = $_GET['start_week'];
		$until_week = $_GET['until_week'];
		$brand = $_GET['brand'];

		if ($type == "Yearly") {
			$start_date = $start_year . '-01-01';
			$until_date = $until_year . '-12-31';
		} else if ($type == "Monthly") {
			$start_month = str_pad($start_month, 2, "0", STR_PAD_LEFT);
			$until_month = str_pad($until_month, 2, "0", STR_PAD_LEFT);
			$start_date = $start_year . '-' . $start_month . '-01';
			$until_date = $start_year . '-' . $until_month . '-31';
		} else if ($type == "Weekly") {
			$start_week = str_pad($start_week, 2, "0", STR_PAD_LEFT);
			$until_week = str_pad($until_week, 2, "0", STR_PAD_LEFT);

			$year = $start_year;
			$week = $start_week;
			$start_date = date("Y-m-d", strtotime($year . "W" . $week . "1"));

			$year = $start_year;
			$week = $until_week;
			$until_date = date("Y-m-d", strtotime($year . "W" . $week . "7"));
		}

		$qry = "";
		if ($brand) {
			$qry .= " AND brand = '$brand' ";
		}

		$qry .= " AND status = 'Aktif' AND status_campaign = 'Aktif' ";

		$text = "0";

		if ($id == "kol-1") {

			$query = $this->mymodel->selectWithQuery("SELECT COUNT(id) as result FROM endorse
			WHERE DATE(posting_at) >= '$start_date' AND DATE(posting_at) <= '$until_date'
			GROUP BY influencer
			");

			$query = $query[0];
			$text = $this->template->separator_only($query['result']);
		} else if ($id == "kol-2") {

			$query = $this->mymodel->selectWithQuery("SELECT COUNT(id) as result FROM endorse
			WHERE DATE(posting_at) >= '$start_date' AND DATE(posting_at) <= '$until_date'");

			$query = $query[0];
			$text = $this->template->separator_only($query['result']);
		} else if ($id == "kol-3") {
			$status = "'DP','FP','Barang Dikirim','Draft Content','Posted Content'";
			$query = $this->mymodel->selectWithQuery("SELECT SUM(total_cost) as result FROM endorse
			WHERE DATE(posting_at) >= '$start_date' AND DATE(posting_at) <= '$until_date' AND 
			status_endorse IN ($status)
 			");

			$query = $query[0];
			$text = $this->template->separator_only($query['result']);
		} else if ($id == "kol-4") {

			// $query = $this->mymodel->selectWithQuery("SELECT SUM(views) as result FROM endorse");
			// 
			// $query = $query[0];
			$text = $this->template->separator_only($query['result']);
		} else if ($id == "kol-5") {

			// $query = $this->mymodel->selectWithQuery("SELECT SUM(endorse.likes) as result FROM endorse");
			// 
			// $query = $query[0];
			$text = $this->template->separator_only($query['result']);
		} else if ($id == "kol-6") {

			// $query = $this->mymodel->selectWithQuery("SELECT SUM(comment) as result FROM endorse");
			// 
			// $query = $query[0];
			$text = $this->template->separator_only($query['result']);
		} else if ($id == "kol-7") {

			// $query = $this->mymodel->selectWithQuery("SELECT SUM(share_save) as result FROM endorse");
			// 
			// $query = $query[0];
			$text = $this->template->separator_only($query['result']);
		} else if ($id == "kol-8") {

			// $query = $this->mymodel->selectWithQuery("SELECT SUM(total_cost) / SUM(views) * 1000 as result FROM endorse");
			// 
			// $query = $query[0];
			$text = $this->template->separator_only($query['result']);
		}

		$html['html'] = $text;
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($html, true);
	}

	function createRange($start, $end, $gormat = 'Y-m-d')
	{
		$start = strtotime($start);
		$end = strtotime($end);
		$range = array();

		$date = strtotime("-1 day", $start);
		while ($date < $end) {
			$date = strtotime("+1 day", $date);
			$range[] = date('Y-m-d', $date);
		}
		return $range;
	}



	function get_chart()
	{
		$checkbox = $_SESSION['checkbox_dashboard'];

		$skip = 0;
		for ($i = 0; $i <= 3; $i++) {
			if ($checkbox[$i] == 'false') {
				$skip++;
			}
		}
		if ($skip >= 5) {
			$html['html'] = '<i>Pastikan memilih minimal 1 filter!</i>';
			$html['table'] = '';
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode($html, true);
			die;
		}

		// Initialize cache system
		try {
			$this->load->driver('cache', array('adapter' => 'memcached'));
		} catch (Exception $e) {
			// If cache fails, continue without caching
			log_message('error', 'Cache initialization failed in get_chart: ' . $e->getMessage());
		}

		$type = $_GET['type'];
		$start_date = $_GET['start_date'];
		$until_date = $_GET['until_date'];
		$start_year = $_GET['start_year'];
		$until_year = $_GET['until_year'];
		$start_month = $_GET['start_month'];
		$until_month = $_GET['until_month'];
		$start_week = $_GET['start_week'];
		$until_week = $_GET['until_week'];
		$site = $_GET['site'];
		$customer = $_GET['customer'];
		$mpu = $_GET['mpu'];

		// Generate cache key based on request parameters
		$cache_key_params = array(
			'type' => $type,
			'start_date' => $start_date,
			'until_date' => $until_date,
			'start_year' => $start_year,
			'until_year' => $until_year,
			'start_month' => $start_month,
			'until_month' => $until_month,
			'start_week' => $start_week,
			'until_week' => $until_week,
			'brand' => $_GET['brand'] ?? '',
			'channel' => $_GET['channel'] ?? '',
			'code' => $_GET['code'] ?? '',
			'title' => $_GET['title'] ?? '',
			'checkbox' => serialize($checkbox)
		);
		$cache_key = 'get_chart_' . md5(serialize($cache_key_params));
		
		// Try to get result from cache first
		try {
			if (isset($this->cache)) {
				$cached_result = $this->cache->get($cache_key);
				if ($cached_result !== FALSE) {
					header('Content-Type: application/json; charset=utf-8');
					echo json_encode($cached_result, true);
					return;
				}
			}
		} catch (Exception $e) {
			log_message('error', 'Failed to get cache in get_chart: ' . $e->getMessage());
		}

		if ($type == "Yearly") {
			$qry_opt = " YEAR(date) ";
			$start_date = $start_year . '-01-01';
			$until_date = $until_year . '-12-31';
			$group = "  GROUP BY YEAR(date) ";
		} else if ($type == "Monthly") {
			$qry_opt = " MONTH(date) ";
			$start_month = str_pad($start_month, 2, "0", STR_PAD_LEFT);
			$until_month = str_pad($until_month, 2, "0", STR_PAD_LEFT);
			$start_date = $start_year . '-' . $start_month . '-01';
			$until_date = $start_year . '-' . $until_month . '-31';
			$group = "  GROUP BY MONTH(date) ";
		} else if ($type == "Weekly") {
			$qry_opt = " WEEK(date) ";
			$start_week = str_pad($start_week, 2, "0", STR_PAD_LEFT);
			$until_week = str_pad($until_week, 2, "0", STR_PAD_LEFT);

			$year = $start_year;
			$week = $start_week;
			$start_date = date("Y-m-d", strtotime($year . "W" . $week . "1"));

			$year = $start_year;
			$week = $until_week;
			$until_date = date("Y-m-d", strtotime($year . "W" . $week . "7"));
			$group = "  GROUP BY WEEK(date) ";
		} else {
			$qry_opt = " DATE(date) ";
			$group = "  GROUP BY DATE(date) ";
		}

		$qry = " DATE(date) >= '$start_date' AND DATE(date) <= '$until_date' ";
		$qry_2 = " DATE(date) >= '$start_date' AND DATE(date) <= '$until_date' ";

		// $qry = "";
		// $qry_2 = "";
		$brand = $_GET['brand'];
		if ($brand) {
			$qry .= " AND brand = '$brand' ";
			$qry_2 .= " AND brand = '$brand' ";
		}
		$channel = $_GET['channel'];
		if ($channel) {
			$qry .= " AND marketplace = '$channel' ";
		}



		$arr = array();

		$code = $_GET['code'];
		$title = $_GET['title'];

		$list = $this->mymodel->selectWithQuery("SELECT COUNT(id) as val, $qry_opt as opt
		FROM transaction
		WHERE $qry AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') AND type_sub = 'POS' $group");

		$list_2 = $this->mymodel->selectWithQuery("SELECT SUM(omset_kotor) as val, $qry_opt as opt
		FROM transaction
		WHERE $qry AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') AND type_sub = 'POS' $group");

		$list_3 = $this->mymodel->selectWithQuery("SELECT SUM(omset_kotor-diskon_penjual) as val, $qry_opt as opt
		FROM transaction
		WHERE $qry AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') AND type_sub = 'POS' $group");

		$list_4 = $this->mymodel->selectWithQuery("SELECT SUM(omset_kotor-diskon_penjual-hpp-diskon_penjual-marketplace_fee-komisi_afiliasi) as val, $qry_opt as opt
		FROM transaction
		WHERE $qry AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') AND type_sub = 'POS' $group");

		$list_5 = $this->mymodel->selectWithQuery("SELECT ABS(SUM(price_total)) as val, $qry_opt as opt
		FROM transaction
		WHERE $qry_2 AND type_sub = 'Expense' $group");



		if ($type == "Yearly") {
			$range = array();
			for ($i = $start_year; $i <= $until_year; $i++) {
				$range[] = intval($i);
			}
			foreach ($range as $k2 => $v2) {
				$val = 0;
				$val_2 = 0;
				$val_3 = 0;
				$val_4 = 0;
				$val_5 = 0;
				foreach ($list as $k => $v) {
					if ($v['opt'] == $v2) {
						$val += $v['val'];
					}
				}
				foreach ($list_2 as $k => $v) {
					if ($v['opt'] == $v2) {
						$val_2 += $v['val'];
					}
				}
				foreach ($list_3 as $k => $v) {
					if ($v['opt'] == $v2) {
						$val_3 += $v['val'];
					}
				}
				foreach ($list_4 as $k => $v) {
					if ($v['opt'] == $v2) {
						$val_4 += $v['val'];
					}
				}
				foreach ($list_5 as $k => $v) {
					if ($v['opt'] == $v2) {
						$val_5 += $v['val'];
					}
				}
				$i = $k2;
				// $v['opt'] = substr($v2, -2);
				$v['opt'] = $v2;
				$arr[$i]['opt'] = $v['opt'];
				$arr[$i]['val'] = round($val, 2);
				$arr[$i]['val_2'] = round($val_2, 2);
				$arr[$i]['val_3'] = round($val_3, 2);
				$arr[$i]['val_4'] = round($val_4, 2);
				$arr[$i]['val_5'] = round($val_5, 2);
			}
		} else if ($type == "Monthly") {
			$range = array();
			for ($i = $start_month; $i <= $until_month; $i++) {
				$range[] = intval($i);
			}
			foreach ($range as $k2 => $v2) {
				$val = 0;
				$val_2 = 0;
				$val_3 = 0;
				$val_4 = 0;
				$val_5 = 0;
				foreach ($list as $k => $v) {
					if ($v['opt'] == $v2) {
						$val += $v['val'];
					}
				}
				foreach ($list_2 as $k => $v) {
					if ($v['opt'] == $v2) {
						$val_2 += $v['val'];
					}
				}
				foreach ($list_3 as $k => $v) {
					if ($v['opt'] == $v2) {
						$val_3 += $v['val'];
					}
				}
				foreach ($list_4 as $k => $v) {
					if ($v['opt'] == $v2) {
						$val_4 += $v['val'];
					}
				}
				foreach ($list_5 as $k => $v) {
					if ($v['opt'] == $v2) {
						$val_5 += $v['val'];
					}
				}
				$i = $k2;
				$v['opt'] = substr($v2, -2);
				$arr[$i]['opt'] = $v['opt'];
				$arr[$i]['val'] = round($val, 2);
				$arr[$i]['val_2'] = round($val_2, 2);
				$arr[$i]['val_3'] = round($val_3, 2);
				$arr[$i]['val_4'] = round($val_4, 2);
				$arr[$i]['val_5'] = round($val_5, 2);
			}
		} else if ($type == "Weekly") {
			$range = array();
			for ($i = $start_week; $i <= $until_week; $i++) {
				$range[] = intval($i);
			}

			foreach ($range as $k2 => $v2) {
				$val = 0;
				$val_2 = 0;
				$val_3 = 0;
				$val_4 = 0;
				$val_5 = 0;
				foreach ($list as $k => $v) {
					if ($v['opt'] == $v2) {
						$val += $v['val'];
					}
				}
				foreach ($list_2 as $k => $v) {
					if ($v['opt'] == $v2) {
						$val_2 += $v['val'];
					}
				}
				foreach ($list_3 as $k => $v) {
					if ($v['opt'] == $v2) {
						$val_3 += $v['val'];
					}
				}
				foreach ($list_4 as $k => $v) {
					if ($v['opt'] == $v2) {
						$val_4 += $v['val'];
					}
				}
				foreach ($list_5 as $k => $v) {
					if ($v['opt'] == $v2) {
						$val_5 += $v['val'];
					}
				}
				$i = $k2;
				$v['opt'] = substr($v2, -2);
				$arr[$i]['opt'] = $v['opt'];
				$arr[$i]['val'] = round($val, 2);
				$arr[$i]['val_2'] = round($val_2, 2);
				$arr[$i]['val_3'] = round($val_3, 2);
				$arr[$i]['val_4'] = round($val_4, 2);
				$arr[$i]['val_5'] = round($val_5, 2);
			}
		} else {
			$range = ($this->createRange($start_date, $until_date));
			foreach ($range as $k2 => $v2) {
				$val = 0;
				$val_2 = 0;
				$val_3 = 0;
				$val_4 = 0;
				$val_5 = 0;
				foreach ($list as $k => $v) {
					if ($v['opt'] == $v2) {
						$val += $v['val'];
					}
				}
				foreach ($list_2 as $k => $v) {
					if ($v['opt'] == $v2) {
						$val_2 += $v['val'];
					}
				}
				foreach ($list_3 as $k => $v) {
					if ($v['opt'] == $v2) {
						$val_3 += $v['val'];
					}
				}
				foreach ($list_4 as $k => $v) {
					if ($v['opt'] == $v2) {
						$val_4 += $v['val'];
					}
				}
				foreach ($list_5 as $k => $v) {
					if ($v['opt'] == $v2) {
						$val_5 += $v['val'];
					}
				}
				$i = $k2;
				// $v['opt'] = substr($v2, -2);
				$v['opt'] = $v2;
				$arr[$i]['opt'] = $v['opt'];
				$arr[$i]['val'] = round($val, 2);
				$arr[$i]['val_2'] = round($val_2, 2);
				$arr[$i]['val_3'] = round($val_3, 2);
				$arr[$i]['val_4'] = round($val_4, 2);
				$arr[$i]['val_5'] = round($val_5, 2);
			}
		}


		$arr_new = array();

		$count = count($arr);

		if ($type == "Yearly") {
			for ($i = 0; $i < $count; $i++) {
				$arr_new[$i] = $arr[$i];
			};
		} else if ($type == "Monthly") {
			for ($i = 0; $i < $count; $i++) {
				$arr_new[$i] = $arr[$i];
			};
		} else if ($type == "Weekly") {
			for ($i = 0; $i < $count; $i++) {
				$arr_new[$i] = $arr[$i];
			};
		} else {
			for ($i = 0; $i < $count; $i++) {
				$arr_new[$i] = $arr[$i];
			};
		}

		$th_table = "";
		$td_1 = "";
		$td_2 = "";
		$td_3 = "";
		$td_4 = "";
		$td_5 = "";

		$opt = "";
		$val = "";
		$a = "";

		$color = "";
		$val_arr_1 = array(0, 0);
		$val_arr_2 = array(0, 0);
		$val_arr_3 = array(0, 0);
		$val_arr_4 = array(0, 0);
		$val_arr_5 = array(0, 0);
		foreach ($arr_new as $k => $v) {
			// $v['opt'] = substr($v['opt'], -2);

			if ($type == "Yearly") {
			} else if ($type == "Monthly") {
			} else if ($type == "Weekly") {
			} else {
				$v['opt'] = DATE("d M Y", strtotime($v['opt']));
			}

			$opt .= "'" . $v['opt'] . "',";

			if ($v['opt']) {
				$a .= "'" . $this->template->separator_number_only($v['val']) . "',";
				$b .= "'" . $this->template->separator_number_only($v['val_2']) . "',";
				$c .= "'" . $this->template->separator_number_only($v['val_3']) . "',";
				$d .= "'" . $this->template->separator_number_only($v['val_4']) . "',";
				$e .= "'" . $this->template->separator_number_only($v['val_5']) . "',";
				$th_table .= "<th>" . $v['opt'] . "</th>";
				$td_1 .= "<td>" . $this->template->separator_only((($v['val']))) . "</td>";
				$td_2 .= "<td>" . $this->template->separator_only((($v['val_2']))) . "</td>";
				$td_3 .= "<td>" . $this->template->separator_only((($v['val_3']))) . "</td>";
				$td_4 .= "<td>" . $this->template->separator_only((($v['val_4']))) . "</td>";
				$td_5 .= "<td>" . $this->template->separator_only((($v['val_5']))) . "</td>";
				$val_arr_1[] = round($v['val']);
				$val_arr_2[] = round($v['val_2']);
				$val_arr_3[] = round($v['val_3']);
				$val_arr_4[] = round($v['val_4']);
				$val_arr_5[] = round($v['val_5']);
			}

			$color .= "'" . $v['color'] . "',";
		}

		$min_1 = min($val_arr_1);
		$max_1 = max($val_arr_1);
		$min_2 = min($val_arr_2);
		$max_2 = max($val_arr_2);
		$min_3 = min($val_arr_3);
		$max_3 = max($val_arr_3);
		$min_4 = min($val_arr_4);
		$max_4 = max($val_arr_4);
		$min_5 = min($val_arr_5);
		$max_5 = max($val_arr_5);


		$min_1 = 0;
		$min_2 = 0;


		if ($checkbox[0] == 'true') {
			$max_1 = $max_1;
		} else {
			$max_1 = 0;
		}
		if ($checkbox[1] == 'true') {
			if ($max_2 > $max_1) {
				$max_1 = $max_2;
			}
		}
		if ($checkbox[2] == 'true') {
			if ($max_3 > $max_1) {
				$max_1 = $max_3;
			}
		}
		if ($checkbox[3] == 'true') {
			if ($max_4 > $max_1) {
				$max_1 = $max_4;
			}
		}
		if ($checkbox[4] == 'true') {
			if ($max_5 > $max_1) {
				$max_1 = $max_5;
			}
		}

		$max_1 = intval($max_1);


		if ($max_1 > 10000000) {
			$max_1 =  ($max_1 - ($max_1 % 10000000)) * 2.2;
		} else if ($max_1 > 1000000) {
			$max_1 =  ($max_1 - ($max_1 % 1000000)) * 2.2;
		} else if ($max_1 > 100000) {
			$max_1 =  ($max_1 - ($max_1 % 100000)) * 2.2;
		} else if ($max_1 > 10000) {
			$max_1 =  ($max_1 - ($max_1 % 10000)) * 2.2;
		} else if ($max_1 > 1000) {
			$max_1 =  ($max_1 - ($max_1 % 1000)) * 2.2;
		} else if ($max_1 > 100) {
			$max_1 =  ($max_1 - ($max_1 % 100)) * 2.2;
		} else if ($max_1 > 10) {
			$max_1 =  ($max_1 - ($max_1 % 10)) * 2.2;
		} else if ($max_1 > 0) {
			$max_1 =  ($max_1 - ($max_1 % 1)) * 2.2;
		}


		$item_1 = '';
		$item_2 = '';
		$item_3 = '';

		if ($checkbox[0] == 'true') {
			$item_1 .= '
			{
				type: "line",
				label: " Jumlah Order ",
				fill: "start",
    			backgroundColor: gradient_1,
				borderColor: ["' . $this->template->hex(4) . '"],
				borderWidth: 2, pointRadius: 0, cubicInterpolationMode: "monotone",
				data: [' . $a . '],
				yAxisID: "y1",
			},	
			';
			$item_3 .= '
			<tr>
			<td class="text-start"> 
				<div class="d-flex justify-content-start">
					<div style="background-color: ' . $this->template->hex(4) . '; width: 7px; height: 7px;margin-right:5px;margin-top:4px">
					</div>Jumlah Order
				</div>
			</td>
			' . $td_1 . '
			</tr>
													';
		}
		if ($checkbox[1] == 'true') {
			$item_1 .= '
			{
				type: "line",
				label: " Penjualan Kotor ",
				fill: "start",
    			backgroundColor: gradient_2,
				borderColor: ["' . $this->template->hex(1) . '"],
				borderWidth: 2, pointRadius: 0, cubicInterpolationMode: "monotone",
				data: [' . $b . '],
				yAxisID: "y2",
			},		
			';
			$item_3 .= '
		
			<tr>
			<td class="text-start"> 
				<div class="d-flex justify-content-start">
					<div style="background-color: ' . $this->template->hex(1) . '; width: 7px; height: 7px;margin-right:5px;margin-top:4px">
					</div>Penjualan Kotor
				</div>
			</td>
			' . $td_2 . '
			</tr>
			';
		}
		if ($checkbox[2] == 'true') {
			$item_1 .= '
			{
				type: "line",
				label: " Penjualan Bersih ",
				fill: "start",
    			backgroundColor: gradient_3,
				borderColor: ["' . $this->template->hex(2) . '"],
				borderWidth: 2, pointRadius: 0, cubicInterpolationMode: "monotone",
				data: [' . $c . '],
				yAxisID: "y2",
			},		
			';
			$item_3 .= '
		
			<tr>
			<td class="text-start"> 
				<div class="d-flex justify-content-start">
					<div style="background-color: ' . $this->template->hex(2) . '; width: 7px; height: 7px;margin-right:5px;margin-top:4px">
					</div>Penjualan Bersih
				</div>
			</td>
			' . $td_3 . '
			</tr>
			';
		}
		if ($checkbox[3] == 'true') {
			$item_1 .= '
			
			{
				type: "line",
				label: " Laba Bersih ",
				fill: "start",
    			backgroundColor: gradient_4,
				borderColor: ["' . $this->template->hex(0) . '"],
				borderWidth: 2, pointRadius: 0, cubicInterpolationMode: "monotone",
				data: [' . $d . '],
				yAxisID: "y3",
			},	
			';
			$item_3 .= '
			
			<tr>
			<td class="text-start"> 
				<div class="d-flex justify-content-start">
					<div style="background-color: ' . $this->template->hex(0) . '; width: 7px; height: 7px;margin-right:5px;margin-top:4px">
					</div>Laba Bersih
				</div>
			</td>
			' . $td_4 . '
			</tr>
			';
		}
		if ($checkbox[4] == 'true') {
			$item_1 .= '
			
			{
				type: "line",
				label: " Pengeluaran ",
				fill: "start",
    			backgroundColor: gradient_5,
				borderColor: ["' . $this->template->hex(3) . '"],
				borderWidth: 2, pointRadius: 0, cubicInterpolationMode: "monotone",
				data: [' . $e . '],
				yAxisID: "y4",
			},	
			';
			$item_3 .= '
		
			<tr>
			<td class="text-start"> 
				<div class="d-flex justify-content-start">
					<div style="background-color: ' . $this->template->hex(3) . '; width: 7px; height: 7px;margin-right:5px;margin-top:4px">
					</div>Pengeluaran
				</div>
			</td>
			' . $td_5 . '
			</tr>
			';
		}


		$key = 'get_chart_' . DATE("Ymdhis");

		$html['html'] = '
                                                    <canvas class="chart" id="' . $key . '"></canvas>
                                                    <script>
                                                    const ' . $key . ' = document.getElementById(
                                                        "' . $key . '").getContext("2d");


var gradient_1 = ' . $key . '.createLinearGradient(0,0,0,' . $key . '.canvas.clientHeight)
gradient_1.addColorStop(0, "' . $this->template->hex_to_rgb($this->template->hex(4)) . '")
gradient_1.addColorStop(0.75, "rgba(225, 225, 225, 0)")

var gradient_2 = ' . $key . '.createLinearGradient(0,0,0,' . $key . '.canvas.clientHeight)
gradient_2.addColorStop(0, "' . $this->template->hex_to_rgb($this->template->hex(1)) . '")
gradient_2.addColorStop(0.75, "rgba(225, 225, 225, 0)")

var gradient_3 = ' . $key . '.createLinearGradient(0,0,0,' . $key . '.canvas.clientHeight)
gradient_3.addColorStop(0, "' . $this->template->hex_to_rgb($this->template->hex(2)) . '")
gradient_3.addColorStop(0.75, "rgba(225, 225, 225, 0)")

var gradient_4 = ' . $key . '.createLinearGradient(0,0,0,' . $key . '.canvas.clientHeight)
gradient_4.addColorStop(0, "' . $this->template->hex_to_rgb($this->template->hex(0)) . '")
gradient_4.addColorStop(0.75, "rgba(225, 225, 225, 0)")

var gradient_5 = ' . $key . '.createLinearGradient(0,0,0,' . $key . '.canvas.clientHeight)
gradient_5.addColorStop(0, "' . $this->template->hex_to_rgb($this->template->hex(3)) . '")
gradient_5.addColorStop(0.75, "rgba(225, 225, 225, 0)")

                                                    new Chart(' . $key . ', {
                                                        type: "line",
                                                        data: {
                                                            datasets: [
                                                                		' . $item_1 . '					
                                                            ],
                                                            labels: [' . $opt . ']
                                                        },
                                                        options: {
															responsive: true,
															bezierCurve : false,
															maintainAspectRatio: false, 
															aspectRatio: 3.1, 
															interaction: {
															mode: "index",
															intersect: false,
														},
														plugins:{
															legend: { display:false,
																labels: {
																  font: {
																	size: 8
																  }
																}
															},
														},
														stacked: false,
														
														scales: {
														x:{
															ticks: {												
																autoSkip: true,	
																maxTicksLimit: 10,											
																font: {													
																	size: 11,												
																}											
															},
															grid: {
																display: false,
															}
														},
														y1: {
															type: "linear",
															display: true,
															position: "left",
															min:0,
															max:' . $max_1 . ',
															ticks: {												
																autoSkip: false,												
																font: {													
																	size: 8,												
																}											
															}
														},
														y2: {
															type: "linear",
															display: false,
															position: "left",
															min:0,
															max:' . $max_1 . ',
															ticks: {												
																autoSkip: false,												
																font: {													
																	size: 8,												
																}											
															}
														},
														y3: {
															type: "linear",
															display: false,
															position: "left",
															min:0,
															max:' . $max_1 . ',
															ticks: {												
																autoSkip: false,												
																font: {													
																	size: 8,												
																}											
															}
														},
														y4: {
															type: "linear",
															display: false,
															position: "left",
															min:0,
															max:' . $max_1 . ',
															ticks: {												
																autoSkip: false,												
																font: {													
																	size: 8,												
																}											
															}
														},
														},
														},

                                                    });
                                                    </script>';

		$html['table'] = '
													<div class="table-responsive">
													<table class="table able-bordered table-stats">
													<tr>
													<th class="text-start">#</th>
													' . $th_table . '
													</tr>
													' . $item_3 . '
													</table>
													</div>
													';
		
		// Save result to cache with 30 seconds TTL
		try {
			if (isset($this->cache)) {
				$this->cache->save($cache_key, $html, 30);
			}
		} catch (Exception $e) {
			log_message('error', 'Failed to save cache in get_chart: ' . $e->getMessage());
		}
		
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($html, true);
	}

	function get_chart_overview()
	{
		$checkbox = $_SESSION['checkbox_overview'];

		$skip = 0;
		for ($i = 0; $i <= 3; $i++) {
			if ($checkbox[$i] == 'false') {
				$skip++;
			}
		}
		if ($skip >= 5) {
			$html['html'] = '<i>Pastikan memilih minimal 1 filter!</i>';
			$html['table'] = '';
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode($html, true);
			die;
		}

		$type = $_GET['type'];
		$today = date('Y-m-d');
		// Ensure $start_date and $end_date are correctly set
		$start_date = isset($start_date) ? $start_date : date('Y-m-d', strtotime("$today -6 days"));
		$end_date = isset($end_date) ? $end_date : $today;

		// Build brand condition string based on user input
		$brand_condition = '';
		if (!empty($brand_filter)) {
			$brand_condition = "AND transaction.brand = " . $this->db->escape($brand_filter);
		}

		$firstLetter = str_split($brand_filter)[0];

		$shopee_brand = '';
		if (!empty($brand_filter)) {
			$shopee_brand = "AND shop_name LIKE '$firstLetter%'";
		}

		$tiktok_brand = '';
		if (!empty($brand_filter)) {
			$tiktok_brand = "AND advertiser_name LIKE '$firstLetter%'";
		}

		$meta_brand = '';
		if ($firstLetter == 'P') {
			$meta_brand = "AND account_name LIKE 'c%' OR account_name LIKE 'p%'";
		} else if ($firstLetter == 'M') {
			$meta_brand = "AND account_name LIKE 'm%'";
		}

		$list = $this->mymodel->selectWithQuery(
			"
			
			SELECT 
            DATE_FORMAT(dates.date, '%d-%m-%Y') AS date,
            COALESCE(shopee.expense, 0) AS shopee_spend,
            COALESCE(meta.spend, 0) AS meta_spend,
            COALESCE(tiktok.spend_idr, 0) AS tiktok_spend,
            COALESCE(shopee.expense, 0) + COALESCE(meta.spend, 0) + COALESCE(tiktok.spend_idr, 0) AS total_spend
        FROM 
            (
                SELECT DISTINCT DATE(date) AS date 
                FROM shopee_ads_data
                UNION
                SELECT DISTINCT DATE(date) AS date 
                FROM meta_ads_data
                UNION
                SELECT DISTINCT DATE(date) AS date 
                FROM tiktok_ads_data
                UNION
                SELECT DISTINCT DATE(date) AS date 
                FROM transaction
            ) AS dates
        LEFT JOIN (
            SELECT DATE(date) AS date, SUM(expense) AS expense
            FROM shopee_ads_data
            INNER JOIN marketplace_config 
                ON marketplace_config.shop_id = shopee_ads_data.shop_id
            WHERE DATE(date) BETWEEN '$start_date' AND '$end_date'
            $shopee_brand
            GROUP BY DATE(date)
        ) AS shopee ON shopee.date = dates.date
        LEFT JOIN (
            SELECT DATE(date) AS date, SUM(spend) AS spend
            FROM meta_ads_data
            INNER JOIN ads_meta_account 
                ON meta_ads_data.account_id = ads_meta_account.account_id
            WHERE DATE(date) BETWEEN '$start_date' AND '$end_date'
            $meta_brand
            GROUP BY DATE(date)
        ) AS meta ON meta.date = dates.date
        LEFT JOIN (
            SELECT DATE(date) AS date, SUM(spend_idr) AS spend_idr
            FROM tiktok_ads_data
            WHERE DATE(date) BETWEEN '$start_date' AND '$end_date'
            $tiktok_brand
            GROUP BY DATE(date)
        ) AS tiktok ON tiktok.date = dates.date
        WHERE dates.date BETWEEN '$start_date' AND '$end_date'
        GROUP BY dates.date
        ORDER BY dates.date DESC;"
		);

		// $list_2 = $this->mymodel->selectWithQuery("SELECT SUM(omset_kotor) as val, $qry_opt as opt
		// FROM transaction
		// WHERE $qry AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') AND type_sub = 'POS' $group");

		// $list_3 = $this->mymodel->selectWithQuery("SELECT SUM(omset_kotor-diskon_penjual) as val, $qry_opt as opt
		// FROM transaction
		// WHERE $qry AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') AND type_sub = 'POS' $group");

		// $list_4 = $this->mymodel->selectWithQuery("SELECT SUM(omset_kotor-diskon_penjual-hpp-diskon_penjual-marketplace_fee-komisi_afiliasi) as val, $qry_opt as opt
		// FROM transaction
		// WHERE $qry AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') AND type_sub = 'POS' $group");

		// $list_5 = $this->mymodel->selectWithQuery("SELECT ABS(SUM(price_total)) as val, $qry_opt as opt
		// FROM transaction
		// WHERE $qry_2 AND type_sub = 'Expense' $group");

		$arr_new = array();

		$count = count($arr);


		for ($i = 0; $i < $count; $i++) {
			$arr_new[$i] = $arr[$i];
		};

		$th_table = "";
		$td_1 = "";
		$td_2 = "";
		$td_3 = "";
		$td_4 = "";
		$td_5 = "";

		$opt = "";
		$val = "";
		$a = "";

		$color = "";
		$val_arr_1 = array(0, 0);
		$val_arr_2 = array(0, 0);
		$val_arr_3 = array(0, 0);
		$val_arr_4 = array(0, 0);
		$val_arr_5 = array(0, 0);
		foreach ($arr_new as $k => $v) {
			// $v['opt'] = substr($v['opt'], -2);


			$v['opt'] = DATE("d M Y", strtotime($v['opt']));

			$opt .= "'" . $v['opt'] . "',";

			if ($v['opt']) {
				$a .= "'" . $this->template->separator_number_only($v['val']) . "',";
				$b .= "'" . $this->template->separator_number_only($v['val_2']) . "',";
				$c .= "'" . $this->template->separator_number_only($v['val_3']) . "',";
				$d .= "'" . $this->template->separator_number_only($v['val_4']) . "',";
				$e .= "'" . $this->template->separator_number_only($v['val_5']) . "',";
				$th_table .= "<th>" . $v['opt'] . "</th>";
				$td_1 .= "<td>" . $this->template->separator_only((($v['val']))) . "</td>";
				$td_2 .= "<td>" . $this->template->separator_only((($v['val_2']))) . "</td>";
				$td_3 .= "<td>" . $this->template->separator_only((($v['val_3']))) . "</td>";
				$td_4 .= "<td>" . $this->template->separator_only((($v['val_4']))) . "</td>";
				$td_5 .= "<td>" . $this->template->separator_only((($v['val_5']))) . "</td>";
				$val_arr_1[] = round($v['val']);
				$val_arr_2[] = round($v['val_2']);
				$val_arr_3[] = round($v['val_3']);
				$val_arr_4[] = round($v['val_4']);
				$val_arr_5[] = round($v['val_5']);
			}

			$color .= "'" . $v['color'] . "',";
		}

		$min_1 = min($val_arr_1);
		$max_1 = max($val_arr_1);
		$min_2 = min($val_arr_2);
		$max_2 = max($val_arr_2);
		$min_3 = min($val_arr_3);
		$max_3 = max($val_arr_3);
		$min_4 = min($val_arr_4);
		$max_4 = max($val_arr_4);
		$min_5 = min($val_arr_5);
		$max_5 = max($val_arr_5);


		$min_1 = 0;
		$min_2 = 0;


		if ($checkbox[0] == 'true') {
			$max_1 = $max_1;
		} else {
			$max_1 = 0;
		}
		if ($checkbox[1] == 'true') {
			if ($max_2 > $max_1) {
				$max_1 = $max_2;
			}
		}
		if ($checkbox[2] == 'true') {
			if ($max_3 > $max_1) {
				$max_1 = $max_3;
			}
		}
		if ($checkbox[3] == 'true') {
			if ($max_4 > $max_1) {
				$max_1 = $max_4;
			}
		}
		if ($checkbox[4] == 'true') {
			if ($max_5 > $max_1) {
				$max_1 = $max_5;
			}
		}

		$max_1 = intval($max_1);


		if ($max_1 > 10000000) {
			$max_1 =  ($max_1 - ($max_1 % 10000000)) * 2.2;
		} else if ($max_1 > 1000000) {
			$max_1 =  ($max_1 - ($max_1 % 1000000)) * 2.2;
		} else if ($max_1 > 100000) {
			$max_1 =  ($max_1 - ($max_1 % 100000)) * 2.2;
		} else if ($max_1 > 10000) {
			$max_1 =  ($max_1 - ($max_1 % 10000)) * 2.2;
		} else if ($max_1 > 1000) {
			$max_1 =  ($max_1 - ($max_1 % 1000)) * 2.2;
		} else if ($max_1 > 100) {
			$max_1 =  ($max_1 - ($max_1 % 100)) * 2.2;
		} else if ($max_1 > 10) {
			$max_1 =  ($max_1 - ($max_1 % 10)) * 2.2;
		} else if ($max_1 > 0) {
			$max_1 =  ($max_1 - ($max_1 % 1)) * 2.2;
		}


		$item_1 = '';
		$item_2 = '';
		$item_3 = '';

		if ($checkbox[0] == 'true') {
			$item_1 .= '
			{
				type: "line",
				label: " Jumlah Order ",
				fill: "start",
    			backgroundColor: gradient_1,
				borderColor: ["' . $this->template->hex(4) . '"],
				borderWidth: 2, pointRadius: 0, cubicInterpolationMode: "monotone",
				data: [' . $a . '],
				yAxisID: "y1",
			},	
			';
			$item_3 .= '
			<tr>
			<td class="text-start"> 
				<div class="d-flex justify-content-start">
					<div style="background-color: ' . $this->template->hex(4) . '; width: 7px; height: 7px;margin-right:5px;margin-top:4px">
					</div>Jumlah Order
				</div>
			</td>
			' . $td_1 . '
			</tr>
													';
		}
		if ($checkbox[1] == 'true') {
			$item_1 .= '
			{
				type: "line",
				label: " Penjualan Kotor ",
				fill: "start",
    			backgroundColor: gradient_2,
				borderColor: ["' . $this->template->hex(1) . '"],
				borderWidth: 2, pointRadius: 0, cubicInterpolationMode: "monotone",
				data: [' . $b . '],
				yAxisID: "y2",
			},		
			';
			$item_3 .= '
		
			<tr>
			<td class="text-start"> 
				<div class="d-flex justify-content-start">
					<div style="background-color: ' . $this->template->hex(1) . '; width: 7px; height: 7px;margin-right:5px;margin-top:4px">
					</div>Penjualan Kotor
				</div>
			</td>
			' . $td_2 . '
			</tr>
			';
		}
		if ($checkbox[2] == 'true') {
			$item_1 .= '
			{
				type: "line",
				label: " Penjualan Bersih ",
				fill: "start",
    			backgroundColor: gradient_3,
				borderColor: ["' . $this->template->hex(2) . '"],
				borderWidth: 2, pointRadius: 0, cubicInterpolationMode: "monotone",
				data: [' . $c . '],
				yAxisID: "y2",
			},		
			';
			$item_3 .= '
		
			<tr>
			<td class="text-start"> 
				<div class="d-flex justify-content-start">
					<div style="background-color: ' . $this->template->hex(2) . '; width: 7px; height: 7px;margin-right:5px;margin-top:4px">
					</div>Penjualan Bersih
				</div>
			</td>
			' . $td_3 . '
			</tr>
			';
		}
		if ($checkbox[3] == 'true') {
			$item_1 .= '
			
			{
				type: "line",
				label: " Laba Bersih ",
				fill: "start",
    			backgroundColor: gradient_4,
				borderColor: ["' . $this->template->hex(0) . '"],
				borderWidth: 2, pointRadius: 0, cubicInterpolationMode: "monotone",
				data: [' . $d . '],
				yAxisID: "y3",
			},	
			';
			$item_3 .= '
			
			<tr>
			<td class="text-start"> 
				<div class="d-flex justify-content-start">
					<div style="background-color: ' . $this->template->hex(0) . '; width: 7px; height: 7px;margin-right:5px;margin-top:4px">
					</div>Laba Bersih
				</div>
			</td>
			' . $td_4 . '
			</tr>
			';
		}
		if ($checkbox[4] == 'true') {
			$item_1 .= '
			
			{
				type: "line",
				label: " Pengeluaran ",
				fill: "start",
    			backgroundColor: gradient_5,
				borderColor: ["' . $this->template->hex(3) . '"],
				borderWidth: 2, pointRadius: 0, cubicInterpolationMode: "monotone",
				data: [' . $e . '],
				yAxisID: "y4",
			},	
			';
			$item_3 .= '
		
			<tr>
			<td class="text-start"> 
				<div class="d-flex justify-content-start">
					<div style="background-color: ' . $this->template->hex(3) . '; width: 7px; height: 7px;margin-right:5px;margin-top:4px">
					</div>Pengeluaran
				</div>
			</td>
			' . $td_5 . '
			</tr>
			';
		}


		$key = 'get_chart_' . DATE("Ymdhis");

		$html['html'] = '
                                                    <canvas class="chart" id="' . $key . '"></canvas>
                                                    <script>
                                                    const ' . $key . ' = document.getElementById(
                                                        "' . $key . '").getContext("2d");


var gradient_1 = ' . $key . '.createLinearGradient(0,0,0,' . $key . '.canvas.clientHeight)
gradient_1.addColorStop(0, "' . $this->template->hex_to_rgb($this->template->hex(4)) . '")
gradient_1.addColorStop(0.75, "rgba(225, 225, 225, 0)")

var gradient_2 = ' . $key . '.createLinearGradient(0,0,0,' . $key . '.canvas.clientHeight)
gradient_2.addColorStop(0, "' . $this->template->hex_to_rgb($this->template->hex(1)) . '")
gradient_2.addColorStop(0.75, "rgba(225, 225, 225, 0)")

var gradient_3 = ' . $key . '.createLinearGradient(0,0,0,' . $key . '.canvas.clientHeight)
gradient_3.addColorStop(0, "' . $this->template->hex_to_rgb($this->template->hex(2)) . '")
gradient_3.addColorStop(0.75, "rgba(225, 225, 225, 0)")

var gradient_4 = ' . $key . '.createLinearGradient(0,0,0,' . $key . '.canvas.clientHeight)
gradient_4.addColorStop(0, "' . $this->template->hex_to_rgb($this->template->hex(0)) . '")
gradient_4.addColorStop(0.75, "rgba(225, 225, 225, 0)")

var gradient_5 = ' . $key . '.createLinearGradient(0,0,0,' . $key . '.canvas.clientHeight)
gradient_5.addColorStop(0, "' . $this->template->hex_to_rgb($this->template->hex(3)) . '")
gradient_5.addColorStop(0.75, "rgba(225, 225, 225, 0)")

                                                    new Chart(' . $key . ', {
                                                        type: "line",
                                                        data: {
                                                            datasets: [
                                                                		' . $item_1 . '					
                                                            ],
                                                            labels: [' . $opt . ']
                                                        },
                                                        options: {
															responsive: true,
															bezierCurve : false,
															maintainAspectRatio: false, 
															aspectRatio: 3.1, 
															interaction: {
															mode: "index",
															intersect: false,
														},
														plugins:{
															legend: { display:false,
																labels: {
																  font: {
																	size: 8
																  }
																}
															},
														},
														stacked: false,
														
														scales: {
														x:{
															ticks: {												
																autoSkip: true,	
																maxTicksLimit: 10,											
																font: {													
																	size: 11,												
																}											
															},
															grid: {
																display: false,
															}
														},
														y1: {
															type: "linear",
															display: true,
															position: "left",
															min:0,
															max:' . $max_1 . ',
															ticks: {												
																autoSkip: false,												
																font: {													
																	size: 8,												
																}											
															}
														},
														y2: {
															type: "linear",
															display: false,
															position: "left",
															min:0,
															max:' . $max_1 . ',
															ticks: {												
																autoSkip: false,												
																font: {													
																	size: 8,												
																}											
															}
														},
														y3: {
															type: "linear",
															display: false,
															position: "left",
															min:0,
															max:' . $max_1 . ',
															ticks: {												
																autoSkip: false,												
																font: {													
																	size: 8,												
																}											
															}
														},
														y4: {
															type: "linear",
															display: false,
															position: "left",
															min:0,
															max:' . $max_1 . ',
															ticks: {												
																autoSkip: false,												
																font: {													
																	size: 8,												
																}											
															}
														},
														},
														},

                                                    });
                                                    </script>';

		$html['table'] = '
													<div class="table-responsive">
													<table class="table able-bordered table-stats">
													<tr>
													<th class="text-start">#</th>
													' . $th_table . '
													</tr>
													' . $item_3 . '
													</table>
													</div>
													';
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($html, true);
	}

	/**
	 * Helper method to calculate date ranges based on type
	 */
	private function calculateDateRange($type, $start_date, $until_date, $start_year, $until_year, $start_month, $until_month, $start_week, $until_week)
	{
		if ($type == "Yearly") {
			$start_date = $start_year . '-01-01';
			$until_date = $until_year . '-12-31';
		} else if ($type == "Monthly") {
			$start_month = str_pad($start_month, 2, "0", STR_PAD_LEFT);
			$until_month = str_pad($until_month, 2, "0", STR_PAD_LEFT);
			$start_date = $start_year . '-' . $start_month . '-01';
			$until_date = $start_year . '-' . $until_month . '-31';
		} else if ($type == "Weekly") {
			$start_week = str_pad($start_week, 2, "0", STR_PAD_LEFT);
			$until_week = str_pad($until_week, 2, "0", STR_PAD_LEFT);

			$year = $start_year;
			$week = $start_week;
			$start_date = date("Y-m-d", strtotime($year . "W" . $week . "1"));

			$year = $start_year;
			$week = $until_week;
			$until_date = date("Y-m-d", strtotime($year . "W" . $week . "7"));
		}
		
		return array(
			'start_date' => $start_date,
			'until_date' => $until_date
		);
	}

	/**
	 * Helper method to build query conditions
	 */
	private function buildQueryConditions($brand, $channel)
	{
		$qry = "";
		$qry_2 = "";
		$qry_trx = "";
		$qry_stock = "";
		
		if ($brand) {
			$qry .= " AND brand = '$brand' ";
			$qry_2 .= " AND brand = '$brand' ";
			$qry_trx .= " AND p.brand = '$brand' ";
			$qry_stock .= " AND a.brand = '$brand' ";
		}
		
		if ($channel) {
			$qry .= " AND marketplace = '$channel' ";
			$qry_stock .= " AND b.marketplace = '$channel' ";
		}
		
		return array(
			'qry' => $qry,
			'qry_2' => $qry_2,
			'qry_trx' => $qry_trx,
			'qry_stock' => $qry_stock
		);
	}

	/**
	 * Helper method to execute cached queries with error handling
	 */
	private function executeCachedQuery($sql, $cache_key_suffix = '', $cache_ttl = 300)
	{
		try {
			// Enhanced cache key generation with better uniqueness
			$cache_key = 'query_v2_' . md5($sql . $cache_key_suffix . date('Y-m-d-H'));
			
			// Initialize cache if not already done
			if (!isset($this->cache)) {
				try {
					$this->load->driver('cache', array('adapter' => 'memcached'));
				} catch (Exception $e) {
					log_message('error', 'Cache initialization failed: ' . $e->getMessage());
					// Fallback to direct query execution
					return $this->mymodel->selectWithQuery($sql);
				}
			}
			
			$result = $this->cache->get($cache_key);
			if ($result === FALSE) {
				$start_time = microtime(true);
				$result = $this->mymodel->selectWithQuery($sql);
				$execution_time = microtime(true) - $start_time;
				
				if ($result !== FALSE) {
					// Use adaptive caching: longer cache for slower queries
					$adaptive_ttl = $execution_time > 2 ? 600 : $cache_ttl; // 10 minutes for slow queries
					$this->cache->save($cache_key, $result, $adaptive_ttl);
					
					// Log slow queries for optimization
					if ($execution_time > 3) {
						log_message('info', 'Slow query detected (' . round($execution_time, 2) . 's): ' . substr($sql, 0, 200));
					}
				} else {
					// Return empty array if query fails
					$result = array();
				}
			}
			
			return $result;
		} catch (Exception $e) {
			// Log error and fallback to direct query
			log_message('error', 'Database query error in executeCachedQuery: ' . $e->getMessage());
			try {
				return $this->mymodel->selectWithQuery($sql);
			} catch (Exception $fallback_e) {
				log_message('error', 'Fallback query also failed: ' . $fallback_e->getMessage());
				return array();
			}
		}
	}

	/**
	 * Batch processing function specifically optimized for KOL metrics
	 * Handles progressive loading of KOL dashboard metrics with enhanced performance
	 */
	function get_kol_metrics_batch()
	{
		$start_time = microtime(true);
		
		// Initialize response array
		$batch_response = array();
		$errors = array();
		
		// Get batch request parameters
		$metric_ids = $_GET['metric_ids'] ?? array();
		$common_params = array(
			'type' => $_GET['type'] ?? 'Daily',
			'brand' => $_GET['brand'] ?? '',
			'start_date' => $_GET['start_date'] ?? date('Y-m-01'),
			'until_date' => $_GET['until_date'] ?? date('Y-m-d'),
			'start_year' => $_GET['start_year'] ?? date('Y'),
			'until_year' => $_GET['until_year'] ?? date('Y'),
			'start_month' => $_GET['start_month'] ?? '1',
			'until_month' => $_GET['until_month'] ?? date('m'),
			'start_week' => $_GET['start_week'] ?? '1',
			'until_week' => $_GET['until_week'] ?? date('W'),
			'site' => $_GET['site'] ?? ''
		);
		
		// Validate input
		if (empty($metric_ids)) {
			header('Content-Type: application/json; charset=utf-8');
			echo json_encode(array('error' => 'No metric IDs provided'), true);
			return;
		}
		
		// If metric_ids is a string, convert to array
		if (is_string($metric_ids)) {
			$metric_ids = explode(',', $metric_ids);
		}
		
		// Initialize cache system
		try {
			$this->load->driver('cache', array('adapter' => 'memcached'));
		} catch (Exception $e) {
			log_message('error', 'Cache initialization failed in get_kol_metrics_batch: ' . $e->getMessage());
		}
		
		// Generate batch cache key for KOL metrics
		$batch_cache_key_params = array_merge($common_params, array('kol_metrics' => implode(',', $metric_ids)));
		$batch_cache_key = 'batch_kol_metrics_' . md5(serialize($batch_cache_key_params));
		
		// Try to get entire batch from cache first (5-minute cache for KOL metrics)
		try {
			if (isset($this->cache)) {
				$cached_batch = $this->cache->get($batch_cache_key);
				if ($cached_batch !== FALSE) {
					$execution_time = microtime(true) - $start_time;
					$cached_batch['execution_time'] = round($execution_time, 3);
					$cached_batch['cached'] = true;
					
					header('Content-Type: application/json; charset=utf-8');
					echo json_encode($cached_batch, true);
					return;
				}
			}
		} catch (Exception $e) {
			log_message('error', 'Failed to get batch cache in get_kol_metrics_batch: ' . $e->getMessage());
		}
		
		// Process each KOL metric individually
		foreach ($metric_ids as $metric_id) {
			try {
				// Set parameters for individual KOL metric call
				$_GET['id'] = $metric_id;
				foreach ($common_params as $key => $value) {
					$_GET[$key] = $value;
				}
				
				// Capture output from get_summary_v2 function
				ob_start();
				$this->get_summary_v2();
				$json_output = ob_get_clean();
				
				// Parse the JSON response
				$individual_response = json_decode($json_output, true);
				
				if ($individual_response && json_last_error() === JSON_ERROR_NONE) {
					$batch_response['data'][$metric_id] = $individual_response;
				} else {
					$errors[$metric_id] = 'Invalid JSON response or empty data';
					log_message('error', 'KOL Batch: Invalid response for metric ' . $metric_id);
				}
				
			} catch (Exception $e) {
				$errors[$metric_id] = $e->getMessage();
				log_message('error', 'KOL Batch: Exception for metric ' . $metric_id . ': ' . $e->getMessage());
			}
		}
		
		// Build final response
		$execution_time = microtime(true) - $start_time;
		$final_response = array(
			'success' => !empty($batch_response['data']),
			'data' => $batch_response['data'] ?? array(),
			'processed_count' => count($batch_response['data'] ?? array()),
			'total_requested' => count($metric_ids),
			'execution_time' => round($execution_time, 3),
			'cached' => false,
			'errors' => $errors
		);
		
		// Cache the successful batch response (5 minutes for KOL metrics)
		if ($final_response['success'] && isset($this->cache)) {
			try {
				$this->cache->save($batch_cache_key, $final_response, 60);
			} catch (Exception $e) {
				log_message('error', 'Failed to save batch cache in get_kol_metrics_batch: ' . $e->getMessage());
			}
		}
		
		// Return JSON response
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($final_response, true);
	}

	/**
	 * Cached helper method for getting brands list
	 */
	private function getCachedBrands()
	{
		$cache_key = 'brands_list_enable';

		if (isset($this->cache)) {
			$cached_result = $this->cache->get($cache_key);
			if ($cached_result !== FALSE) {
				return $cached_result;
			}
		}

		$result = $this->mymodel->selectWithQuery("SELECT * FROM brand WHERE status = 'ENABLE' ORDER BY name ASC");

		if (isset($this->cache)) {
			$this->cache->save($cache_key, $result, 60);
		}

		return $result;
	}

	/**
	 * Cached helper method for getting channels list
	 */
	private function getCachedChannels($type = 'all')
	{
		$cache_key = 'channels_list_' . $type;

		if (isset($this->cache)) {
			$cached_result = $this->cache->get($cache_key);
			if ($cached_result !== FALSE) {
				return $cached_result;
			}
		}

		if ($type === 'main') {
			$result = $this->mymodel->selectWithQuery("SELECT * FROM marketplace WHERE name IN ('SHOPEE','LAZADA','TIKTOK','WA') ORDER BY name ASC");
		} else {
			$result = $this->mymodel->selectWithQuery("SELECT * FROM marketplace ORDER BY name ASC");
		}

		if (isset($this->cache)) {
			$this->cache->save($cache_key, $result, 60);
		}

		return $result;
	}

	/**
	 * Cached helper method for common transaction aggregations
	 */
	private function getCachedTransactionSummary($start_date, $until_date, $qry, $type = 'count')
	{
		$cache_key = "transaction_summary_{$type}_{$start_date}_{$until_date}_" . md5($qry);

		try {
			if (isset($this->cache)) {
				$cached_result = $this->cache->get($cache_key);
				if ($cached_result !== FALSE) {
					return $cached_result;
				}
			}
		} catch (Exception $e) {
			log_message('error', 'Cache get failed in getCachedTransactionSummary: ' . $e->getMessage());
		}

		$result = null;
		try {
			switch ($type) {
				case 'count':
					$result = $this->mymodel->selectWithQuery("SELECT COUNT(id) as result FROM transaction WHERE DATE(date) >= '$start_date' AND DATE(date) <= '$until_date' AND type_sub = 'POS' AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') $qry");
					break;
				case 'gross_sales':
					$result = $this->mymodel->selectWithQuery("SELECT SUM(omset_kotor) as result FROM transaction WHERE DATE(date) >= '$start_date' AND DATE(date) <= '$until_date' AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') AND type_sub = 'POS' $qry");
					break;
				case 'net_sales':
					$result = $this->mymodel->selectWithQuery("SELECT SUM(omset_kotor-diskon_penjual) as result FROM transaction WHERE DATE(date) >= '$start_date' AND DATE(date) <= '$until_date' AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') AND type_sub = 'POS' $qry");
					break;
				case 'discount':
					$result = $this->mymodel->selectWithQuery("SELECT SUM(diskon_penjual) as result FROM transaction WHERE DATE(date) >= '$start_date' AND DATE(date) <= '$until_date' AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') AND type_sub = 'POS' $qry");
					break;
				case 'marketplace_fee':
					$result = $this->mymodel->selectWithQuery("SELECT SUM(marketplace_fee) as result FROM transaction WHERE DATE(date) >= '$start_date' AND DATE(date) <= '$until_date' AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') AND type_sub = 'POS' $qry");
					break;
				case 'net_sales_after_fee':
					$result = $this->mymodel->selectWithQuery("SELECT SUM(omset_kotor - diskon_penjual - marketplace_fee) as result FROM transaction WHERE DATE(date) >= '$start_date' AND DATE(date) <= '$until_date' AND type_sub = 'POS' AND order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED','UNPAID') $qry");
					break;
				default:
					log_message('error', 'Unknown transaction summary type: ' . $type);
					return false;
			}
		} catch (Exception $e) {
			log_message('error', 'Database query failed in getCachedTransactionSummary for type ' . $type . ': ' . $e->getMessage());
			return false;
		}

		try {
			if ($result && isset($this->cache)) {
				$this->cache->save($cache_key, $result, 60);
			}
		} catch (Exception $e) {
			log_message('error', 'Cache save failed in getCachedTransactionSummary: ' . $e->getMessage());
		}

		return $result;
	}

	/**
	 * Cached helper method for spending data calculations
	 */
	private function getCachedSpendingData($start_date, $until_date, $brand_filter = '')
	{
		$cache_key = "spending_data_{$start_date}_{$until_date}_" . md5($brand_filter);

		try {
			if (isset($this->cache)) {
				$cached_result = $this->cache->get($cache_key);
				if ($cached_result !== FALSE) {
					return $cached_result;
				}
			}
		} catch (Exception $e) {
			log_message('error', 'Cache get failed in getCachedSpendingData: ' . $e->getMessage());
		}

		$firstLetter = !empty($brand_filter) ? strtoupper(substr($brand_filter, 0, 1)) : '';
		$shopee_brand = !empty($firstLetter) ? "AND mc.shop_name LIKE '{$firstLetter}%'" : "";
		$tiktok_brand = !empty($firstLetter) ? "AND tad.advertiser_name LIKE '{$firstLetter}%'" : "";
		$meta_brand = !empty($firstLetter) ? "AND ama.account_name LIKE '{$firstLetter}%'" : "";

		// Calculate ads spending
		$sql_ads = "
			SELECT
				COALESCE(SUM(sad.expense_after_tax), 0) +
				COALESCE(SUM(mad.spend_after_tax), 0) +
				COALESCE(SUM(tad.spend_idr_after_tax), 0) +
				COALESCE(SUM(ads.spend_idr_after_tax), 0) AS total_spend_ads
			FROM (SELECT 1 as dummy) d
			LEFT JOIN shopee_ads_data sad ON sad.date >= '$start_date' AND sad.date <= '$until_date'
			LEFT JOIN marketplace_config mc ON mc.shop_id = sad.shop_id $shopee_brand
			LEFT JOIN meta_ads_data mad ON mad.date >= '$start_date' AND mad.date <= '$until_date'
			LEFT JOIN ads_meta_account ama ON mad.account_id = ama.account_id $meta_brand
			LEFT JOIN tiktok_ads_data tad ON tad.date >= '$start_date' AND tad.date <= '$until_date' $tiktok_brand
			LEFT JOIN advertiser_spend ads ON ads.date >= '$start_date' AND ads.date <= '$until_date'
				AND ads.advertiser_name LIKE '{$firstLetter}%'
		";
		$ads_result = $this->mymodel->selectWithQuery($sql_ads);
		$total_ads = isset($ads_result[0]['total_spend_ads']) ? $ads_result[0]['total_spend_ads'] : 0;

		// Calculate KOL spending
		$sql_kol = "
			SELECT COALESCE(SUM(DISTINCT e.nominal_dibayarkan), 0) AS total_spend_kol
			FROM endorse e
			INNER JOIN endorse_campaign c ON c.id = e.id_campaign
			WHERE e.tgl_tf >= '$start_date'
			AND e.tgl_tf <= '$until_date'
			" . (!empty($firstLetter) ? "AND c.brand LIKE '{$firstLetter}%'" : "") . "
		";
		$kol_result = $this->mymodel->selectWithQuery($sql_kol);
		$total_kol = isset($kol_result[0]['total_spend_kol']) ? $kol_result[0]['total_spend_kol'] : 0;

		// Calculate etc spending
		$sql_etc = "
			SELECT COALESCE(ABS(SUM(e.price_total)), 0) AS total_spend_etc
			FROM expense e
			WHERE e.date >= '$start_date'
			AND e.date <= '$until_date'
			" . (!empty($firstLetter) ? "AND e.brand LIKE '{$firstLetter}%'" : "") . "
		";
		$etc_result = $this->mymodel->selectWithQuery($sql_etc);
		$total_etc = isset($etc_result[0]['total_spend_etc']) ? $etc_result[0]['total_spend_etc'] : 0;

		$result = array(
			'ads' => $total_ads,
			'kol' => $total_kol,
			'etc' => $total_etc,
			'total' => $total_ads + $total_kol + $total_etc
		);

		try {
			if (isset($this->cache)) {
				$this->cache->save($cache_key, $result, 60);
			}
		} catch (Exception $e) {
			log_message('error', 'Cache save failed in getCachedSpendingData: ' . $e->getMessage());
		}

		return $result;
	}

	/**
	 * Cached helper method for HPP calculations
	 */
	private function getCachedHPPCalculations($start_date, $until_date, $qry_stock)
	{
		$cache_key = "hpp_calc_{$start_date}_{$until_date}_" . md5($qry_stock);

		if (isset($this->cache)) {
			$cached_result = $this->cache->get($cache_key);
			if ($cached_result !== FALSE) {
				return $cached_result;
			}
		}

		$result = $this->mymodel->selectWithQuery("
			SELECT COALESCE(SUM((qty_out_pos + qty_out) * a.price_buy), 0) AS total_hpp
			FROM product a
			INNER JOIN (
				SELECT
					a.product,
					a.brand,
					a.marketplace,
					SUM(a.qty_in) AS qty_in,
					SUM(a.qty_in_pos) AS qty_in_pos,
					SUM(a.qty_out) AS qty_out,
					SUM(a.qty_out_pos) AS qty_out_pos,
					SUM(a.qty) AS qty
				FROM stock a
				LEFT JOIN product p ON a.product = p.id
				WHERE DATE(a.date) >= '$start_date' AND DATE(a.date) <= '$until_date'
				AND a.type_sub = 'POS' $qry_stock
				AND a.order_status NOT IN ('RETURN','REFUND','CANCELLED','IN_CANCELLED')
				GROUP BY a.product
			) b ON a.id = b.product
			WHERE a.is_varian = 0
		");

		$total_hpp = isset($result[0]['total_hpp']) ? $result[0]['total_hpp'] : 0;

		if (isset($this->cache)) {
			$this->cache->save($cache_key, $total_hpp, 60);
		}

		return $total_hpp;
	}

	/**
	 * Get percentage calculation with cache for performance comparison
	 */
	private function getCachedPercentageData($current_value, $previous_value, $id, $start_date, $until_date)
	{
		$cache_key = "percentage_{$id}_{$start_date}_{$until_date}_" . md5($current_value . '_' . $previous_value);

		if (isset($this->cache)) {
			$cached_result = $this->cache->get($cache_key);
			if ($cached_result !== FALSE) {
				return $cached_result;
			}
		}

		$percentage = 0;
		$trend_class = 'text-black';
		$trend_icon = 'bi bi-chevron-double-right';

		if ($previous_value > 0) {
			$percentage = (($current_value - $previous_value) / $previous_value) * 100;

			if ($percentage > 0) {
				$trend_class = 'text-success';
				$trend_icon = 'bi bi-chevron-double-up';
			} elseif ($percentage < 0) {
				$trend_class = 'text-danger';
				$trend_icon = 'bi bi-chevron-double-down';
			}
		}

		$result = array(
			'percentage' => round($percentage, 1),
			'trend_class' => $trend_class,
			'trend_icon' => $trend_icon,
			'progress_html' => '<div class="' . $trend_class . '"><i class="' . $trend_icon . '"></i> ' . round(abs($percentage), 1) . '%</div>'
		);

		if (isset($this->cache)) {
			$this->cache->save($cache_key, $result, 60);
		}

		return $result;
	}

}
