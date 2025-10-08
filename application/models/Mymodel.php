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

}