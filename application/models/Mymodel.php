<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Mymodel extends CI_Model {

		public function __construct()
		{
			$this->load->database(); 
		}

		public function selectData($table)
		{
			
			$query = $this->db->get($table);
			return $query->result_array();
		}

		public function selectWithQuery($str)
    	{
			
    		$query = $this->db->query($str);
    		return $query->result_array();
    	}


		public function selectWhere($table,$where)
		{
				
				$query = $this->db->get_where($table,$where);
				return $query->result_array();
		 }

		public function selectDataone($table,$where)
		{

				$query = $this->db->get_where($table,$where);
				return $query->row_array();
		}

		public function selectDatarows($table,$where)
		{

				$query = $this->db->get_where($table,$where);
				return $query->num_rows();
		}

		public function deleteData($table,$id)
		{

			
			$this->db->where($id);
			$cekdata = $this->db->get($table)->result_array();
			// echo $this->db->last_query();
			$datajson ='';
			foreach ($cekdata as $key => $value) {
				$datajson = $datajson.json_encode($value);
			}

			if(count($cekdata) > 0){
		

			$this->db->where($id);
			$result = $this->db->delete($table);
			return true;

			}else{

			return  false;
			
			}
			
		}

		public function insertData($table,$data)
		{

		
			$result = $this->db->insert($table,$data);

			return $result;
		}


		public function updateData($table,$data,$where)
		{
			$this->db->where($where);
			$cekdata = $this->db->get($table)->result_array();

			$datajson ='';
			foreach ($cekdata as $key => $value) {
				$datajson = $datajson.json_encode($value);
			}

			if(count($cekdata) > 0){
			

			$result = $this->db->update($table,$data,$where);

			// return $this->alert->alertsuccess('Success Update Data');
				return true;

			}else{

				// return  $this->alert->alertdanger("ID data tidak ditemukan");
				return false;
			}


		}

		public function saveStatusForm($nama_table, $noyes_value, $id_form,$jumlah_ttd = null)
		{
				
			/*noyes_value Value Yes OR No*/	

			$id = explode(',', $id_form);
			if ($nama_table == 'work_request') {
				$table_id = "idwr";
			}else{
				$table_id = "id_".$nama_table; 
			}
			$whereArray = array();
			// $whereArray[$table_id] = $id_form;
			$this->db->where_in($table_id, $id);
			$data_table = $this->mymodel->selectDataone($nama_table,null);

			$this->db->select('idmanualttd');
			$this->db->where('mtd_tablename', $nama_table);
			$this->db->where_in('mtd_coloumnvalue', $id);
			$dataTandaTanggan = $this->mymodel->selectDataone('form_manual_ttd', []);

			$this->db->select('form_type');
			$getFormDynamic = $this->mymodel->selectDataone('form_dynamic', ['form_code'=>$nama_table]);

			$tipe = $getFormDynamic['form_type'];

			if ($tipe == 'biasa') {
				if ($nama_table == 'p2h_unit_truck') {
					/*VALUE TTD TABLE*/
					$datattd = array(
						'ttd_OperatorAwalShift'=>($data_table['ttd_OperatorAwalShift'] != '') ? 1 : 0,
						'ttd_PemeriksaAwalShift'=>($data_table['ttd_PemeriksaAwalShift'] != '') ? 1 : 0,
						'ttd_MekanikAwalShift'=>($data_table['ttd_MekanikAwalShift'] != '') ? 1 : 0,
						'ttd_OperatorAkhirShift'=>($data_table['ttd_OperatorAkhirShift'] != '') ? 1 : 0,
						'ttd_PemeriksaAkhirShift'=>($data_table['ttd_PemeriksaAkhirShift'] != '') ? 1 : 0,
						'ttd_MekanikAkhirShift'=>($data_table['ttd_MekanikAkhirShift'] != '') ? 1 : 0
					);
					/*END VALUE*/

					/*Patern Approval*/
					if ($jumlah_ttd == 3) {
						$patern_proses = array(
							'ttd_OperatorAwalShift'=>1,
							'ttd_PemeriksaAwalShift'=>1,
							'ttd_MekanikAwalShift'=>1,
							'ttd_OperatorAkhirShift'=>0,
							'ttd_PemeriksaAkhirShift'=>0,
							'ttd_MekanikAkhirShift'=>0
						);
						$patern_close = array(
							'ttd_OperatorAwalShift'=>1,
							'ttd_PemeriksaAwalShift'=>1,
							'ttd_MekanikAwalShift'=>1,
							'ttd_OperatorAkhirShift'=>1,
							'ttd_PemeriksaAkhirShift'=>1,
							'ttd_MekanikAkhirShift'=>1
						);
					}else{
						$patern_proses = array(
							'ttd_OperatorAwalShift'=>1,
							'ttd_PemeriksaAwalShift'=>1,
							'ttd_MekanikAwalShift'=>0,
							'ttd_OperatorAkhirShift'=>0,
							'ttd_PemeriksaAkhirShift'=>0,
							'ttd_MekanikAkhirShift'=>0
						);
						$patern_close = array(
							'ttd_OperatorAwalShift'=>1,
							'ttd_PemeriksaAwalShift'=>1,
							'ttd_MekanikAwalShift'=>0,
							'ttd_OperatorAkhirShift'=>1,
							'ttd_PemeriksaAkhirShift'=>1,
							'ttd_MekanikAkhirShift'=>0
						);
					}
					/*END PATERN*/
				}elseif($nama_table == 'Form_Pre_Loading_Assessment'){
					/*VALUE TTD TABLE*/
					$datattd = array(
						'ttd_Pengawas'=>($data_table['ttd_Pengawas'] != '') ? 1 : 0,
						'ttd_OperatorMPU'=>($data_table['ttd_OperatorMPU'] != '') ? 1 : 0
					);
					if ($dataTandaTanggan) {
						$datattd['ttd_customer'] = 1;
					}else{
						$datattd['ttd_customer'] = 0;
					}
					/*END VALUE*/

					/*Patern*/
					if ($jumlah_ttd) {
						$patern_proses = array(
							'ttd_Pengawas'=>1,
							'ttd_OperatorMPU'=>1,
							'ttd_customer'=>0
						);
					}else{
						$patern_close = array(
							'ttd_Pengawas'=>1,
							'ttd_OperatorMPU'=>1,
							'ttd_customer'=>1
						);
					}
					/*END PATERN*/
				}else{
					/*VALUE TTD TABLE*/
					$datattd = array(
						'ttd_Operator'=>($data_table['ttd_Operator'] != '') ? 1 : 0,
						'ttd_Pemeriksa'=>($data_table['ttd_Pemeriksa'] != '') ? 1 : 0,
						'ttd_Mekanik'=>($data_table['ttd_Mekanik'] != '') ? 1 : 0
					);
					/*END VALUE*/

					/*Patern*/
					$patern_close = array(
						'ttd_Operator'=>1,
						'ttd_Pemeriksa'=>1,
						'ttd_Mekanik'=>1
					);
					/*END PATERN*/

				}
			}elseif ($tipe == 'add row') {
				/*VALUE TTD TABLE*/
				$datattd = array(
					'ttd_Pengawas'=>($data_table['ttd_Pengawas'] != '') ? 1 : 0,
					'ttd_OperatorMPU'=>($data_table['ttd_OperatorMPU'] != '') ? 1 : 0
				);
				if ($dataTandaTanggan) {
					$datattd['ttd_customer'] = 1;
				}else{
					$datattd['ttd_customer'] = 0;
				}
				/*END VALUE*/

				/*Patern*/
				if ($jumlah_ttd) {
					$patern_proses = array(
						'ttd_Pengawas'=>1,
						'ttd_OperatorMPU'=>1,
						'ttd_customer'=>0
					);
				}else{
					$patern_close = array(
						'ttd_Pengawas'=>1,
						'ttd_OperatorMPU'=>1,
						'ttd_customer'=>1
					);
				}
				/*END PATERN*/

			}elseif ($tipe == 'qc') {
				/*VALUE TTD TABLE*/
				$datattd = array(
					'ttd_QC'=>($data_table['ttd_QC'] != '') ? 1 : 0,
					'ttd_Pengawas'=>($data_table['ttd_Pengawas'] != '') ? 1 : 0
				);
				if ($dataTandaTanggan) {
					$datattd['ttd_customer'] = 1;
				}else{
					$datattd['ttd_customer'] = 0;
				}
				/*END VALUE*/

				/*Patern*/
				if ($jumlah_ttd) {
					$patern_proses = array(
						'ttd_QC'=>1,
						'ttd_Pengawas'=>1,
						'ttd_customer'=>0
					);
				}else{
					$patern_close = array(
						'ttd_QC'=>1,
						'ttd_Pengawas'=>1,
						'ttd_customer'=>1
					);
				}
				/*END PATERN*/

			}elseif($tipe == 'timesheet'){
				/*VALUE TTD TABLE*/
				$datattd = array(
					'ttd_Operator'=>($data_table['ttd_Operator'] != '') ? 1 : 0,
					'ttd_Pengawas'=>($data_table['ttd_Pengawas'] != '') ? 1 : 0
				);
				/*END VALUE*/

				/*Patern*/
				$patern_close = array(
					'ttd_Operator'=>1,
					'ttd_Pengawas'=>1
				);
				/*END PATERN*/

			}elseif ($tipe == 'with image') {
				/*VALUE TTD TABLE*/
				$datattd = array(
					'ttd_Blaster'=>($data_table['ttd_Blaster'] != '') ? 1 : 0,
					'ttd_Pengawas'=>($data_table['ttd_Pengawas'] != '') ? 1 : 0
				);
				if ($dataTandaTanggan) {
					$datattd['ttd_customer'] = 1;
				}else{
					$datattd['ttd_customer'] = 0;
				}
				/*END VALUE*/

				/*Patern*/
				if ($jumlah_ttd) {
					$patern_proses = array(
						'ttd_Blaster'=>1,
						'ttd_Pengawas'=>1,
						'ttd_customer'=>0
					);
				}else{
					$patern_close = array(
						'ttd_Blaster'=>1,
						'ttd_Pengawas'=>1,
						'ttd_customer'=>1
					);
				}
				/*END PATERN*/

			}else{
				/*VALUE TTD TABLE*/
				$datattd = array(
					'ttd_Operator'=>($data_table['ttd_Operator'] != '') ? 1 : 0,
					'ttd_Pemeriksa'=>($data_table['ttd_Pemeriksa'] != '') ? 1 : 0,
					'ttd_Mekanik'=>($data_table['ttd_Mekanik'] != '') ? 1 : 0
				);
				/*END VALUE*/

				/*Patern*/
				$patern_close = array(
					'ttd_Operator'=>1,
					'ttd_Pemeriksa'=>1,
					'ttd_Mekanik'=>1
				);
				/*END PATERN*/

			}

			/*CHECL PATERN*/
			if (@$patern_proses) {
				if ($datattd === $patern_proses) {
					$arrayUpdate = array(
						'status'=>'process'
					);
				}
			}

			if (@$patern_close) {
				if ($datattd === $patern_close) {
					$arrayUpdate = array(
						'status'=>'close'
					);
				}
			}

			// print_r($arrayUpdate);

			// /*UPDATE*/
			if (@$arrayUpdate) {
				$this->db->where_in($table_id, $id);
				$updateData = $this->db->update($nama_table, $arrayUpdate);
			}

			$res['status'] = true;
			$res['message'] = 'sukses';

			return $res;

		}

		public function getNameFromNumber($num) {
			$numeric = $num % 26;
			$letter = chr(65 + $numeric);
			$num2 = intval($num / 26);
			if ($num2 > 0) {
				return getNameFromNumber($num2 - 1) . $letter;
			} else {
				return $letter;
			}
		}

		/**
		 * Canonical endorsement totals — the single number every surface
		 * (overview KOL chart, endorse chart/tiles, endorse logs) reconciles to.
		 *
		 * Prod harness (2026-07-06) proved SUM(endorse.views) === SUM(latest
		 * endorse_logs.views_after) across all endorses (diff 0), i.e. the
		 * `endorse` row already holds each endorse's current snapshot. So the
		 * current-total is a plain SUM over `endorse` — no endorse_logs scan.
		 *
		 * @param string $where  endorse-set predicate from endorse_filter_where()
		 * @param string $join   optional join (endorse_campaign) from the helper
		 * @return array  keys: views likes comment share_save cost endorse influencer fyp
		 */
		public function endorseCanonicalTotals($where, $join = '')
		{
			$sql = "SELECT
					COALESCE(SUM(endorse.views), 0)        AS views,
					COALESCE(SUM(endorse.likes), 0)        AS likes,
					COALESCE(SUM(endorse.comment), 0)      AS comment,
					COALESCE(SUM(endorse.share_save), 0)   AS share_save,
					COALESCE(SUM(endorse.total_cost), 0)   AS cost,
					COUNT(endorse.id)                      AS endorse,
					COUNT(DISTINCT NULLIF(endorse.influencer, '')) AS influencer,
					COALESCE(SUM(endorse.is_fyp = 1), 0)   AS fyp
				FROM endorse
				$join
				WHERE 1=1 $where";
			$row = $this->db->query($sql)->row_array();
			return $row ?: array(
				'views' => 0, 'likes' => 0, 'comment' => 0, 'share_save' => 0,
				'cost' => 0, 'endorse' => 0, 'influencer' => 0, 'fyp' => 0,
			);
		}

		/**
		 * As-of-past-date twin of endorseCanonicalTotals(): sums each qualifying
		 * endorse's latest endorse_logs snapshot with log_date <= $as_of. Used
		 * when the viewer scopes to a historical date; for today/future the
		 * cheaper endorseCanonicalTotals() over `endorse` gives the same numbers.
		 * Tiebreak on the latest row is MAX(id) (matches the refresh write order).
		 *
		 * @param string $where  endorse-set predicate (endorse_filter_where())
		 * @param string $as_of  'Y-m-d'
		 * @param string $join   optional endorse_campaign join
		 */
		public function endorseCanonicalTotalsAsOf($where, $as_of, $join = '')
		{
			$as_of = $this->db->escape($as_of);
			$sql = "SELECT
					COALESCE(SUM(el.views_after), 0)      AS views,
					COALESCE(SUM(el.likes_after), 0)      AS likes,
					COALESCE(SUM(el.comment_after), 0)    AS comment,
					COALESCE(SUM(el.share_save_after), 0) AS share_save,
					COALESCE(SUM(e.total_cost), 0)        AS cost,
					COUNT(*)                              AS endorse,
					COUNT(DISTINCT NULLIF(e.influencer, '')) AS influencer,
					COALESCE(SUM(e.is_fyp = 1), 0)        AS fyp
				FROM (
					SELECT endorse.id, endorse.total_cost, endorse.influencer, endorse.is_fyp
					FROM endorse
					$join
					WHERE 1=1 $where
				) e
				INNER JOIN (
					SELECT id_endorse, MAX(id) AS mid
					FROM endorse_logs
					WHERE log_date <= $as_of
					GROUP BY id_endorse
				) last ON last.id_endorse = e.id
				INNER JOIN endorse_logs el ON el.id = last.mid";
			$row = $this->db->query($sql)->row_array();
			return $row ?: array(
				'views' => 0, 'likes' => 0, 'comment' => 0, 'share_save' => 0,
				'cost' => 0, 'endorse' => 0, 'influencer' => 0, 'fyp' => 0,
			);
		}

}