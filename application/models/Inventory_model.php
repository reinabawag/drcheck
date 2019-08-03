<?php
/**
* 
*/
class Inventory_model extends CI_Model
{
	
	function __construct()
	{
		parent::__construct();
	}

	function insertFromExcel() {
		
	}

	function insertDR($date, $number, $customer, $co, $co_line, $itemCode, $description, $lotNo, $qty, $um, $lot_tracked) {
		$db = $this->load->database('dr_checking', TRUE);

		$data = [
			'date' => $date,
			'number' => $number,
			'customer' => $customer,
			'co' => $co,
			'co_line' => $co_line,
			'itemCode' => $itemCode,
			'description' => $description,
			'lotNo' => $lotNo,
			'qty' => $qty,
			'um' => $um,
			'lot_tracked' => $lot_tracked
		];
		
		$db->trans_begin();
		$db->insert('dr_list', $data);

		if ($db->trans_status() === FALSE) {
			$db->trans_rollback();
			return FALSE;
		} else {
			$db->trans_commit();
			return TRUE;
		}
	}

	function checkIfExist($bCode, $date, $lotNo, $qty) {
		$db = $this->load->database('dr_checking', TRUE);

		$result = $db->where(['itemCode' => $bCode, 'date' => $date, 'lotNo' => $lotNo, 'qty' => $qty])->get('dr_list');
		// $result = $db->where(['itemCode' => $bCode, 'date' => $date])->get('dr_list');
		return $result->row_array();
	}

	function check_if_exist_new($date, $number, $co, $line, $lot)
	{
		$db = $this->load->database('dr_checking', TRUE);

		return $db->get_where('dr_list', ['date' => $date, 'number' => $number, 'co' => $co, 'co_line' => $line, 'lotNo' => $lot])->row_array();
	}

	function updateDR($date, $number, $customer, $co, $co_line, $itemCode, $description, $lotNo, $qty, $um, $lot_tracked, $id) {
		$db = $this->load->database('dr_checking', TRUE);

		$data = [
			'date' => $date,
			'number' => $number,
			'customer' => $customer,
			'co' => $co,
			'co_line' => $co_line,
			'itemCode' => $itemCode,
			'description' => $description,
			'lotNo'=> $lotNo,
			'qty' => $qty,
			'um' => $um,
			'lot_tracked' => $lot_tracked
		];

		try {			
			$db->where('id', $id);
			$db->update('dr_list', $data);

			// var_dump($db->last_query());

			return TRUE;
		} catch (Exception $e) {
			var_dump($e);
			return FALSE;
		}
	}

	function count_dr() {
		$db = $this->load->database('dr_checking', true);

		return $db->count_all('dr_list');
	}

	function getDRList($per_page, $offset) {
		$db = $this->load->database('dr_checking', TRUE);

		$db->limit($per_page, $offset);
		// $db->order_by('date', 'desc');
		$result = $db->get('dr_list');
		return $result->result();
	}

	function searchDR($search, $page, $offset) {
		$db = $this->load->database('dr_checking', TRUE);

		if ($search != '') {
			$db->limit($page, $offset);
			$db->or_like(['itemCode' => $search, 'number' => $search]);
			// $db->order_by('date', 'DESC');
			$result = $db->get('dr_list');
			$count = count($db->or_like(['itemCode' => $search, 'number' => $search])->get('dr_list')->result());
		} else {
			$count = $db->count_all('dr_list');
			// $db->order_by('date', 'DESC');
			$db->limit($page, $offset);
			$result = $db->get('dr_list');
			
		}
		// for debugging purposes
		// var_dump($db->last_query());
		return array('results' => $result->result(), 'count_results' => $count);
	}

	function getDRByDate($date, $number) {
		$db = $this->load->database('dr_checking', TRUE);
		$result = $db->get_where('dr_list', array('date' => $date, 'number' => $number));

		return $result->result();
	}

	// updated controller for checking if not checked
	function checkBCodeByDate($bCode, $date, $number) {
		$db = $this->load->database('dr_checking', TRUE);

		$result = $db->get_where('dr_list', array('itemCode' => $bCode, 'date' => $date, 'number' => $number, 'status' => 0));
		return $result->row();
	}

	function checkBCodeByDateNoLot($bCode, $date, $number) {
		$db = $this->load->database('dr_checking', TRUE);

		$result = $db->get_where('dr_list', array('itemCode' => $bCode, 'date' => $date, 'number' => $number));
		return $result->row();
	}

	function checkLotNo($bCode, $date, $lotNo, $number) {
		$db = $this->load->database('dr_checking', TRUE);

		$result = $db->where(array('itemCode' => $bCode, 'date' => $date, 'lotNo' => $lotNo, 'number' => $number, 'status' => 0))->get('dr_list');

		return $result->row_array();
	}

	function checkQTY($bCode, $date, $lotNo, $qty, $number, $co_line) {
		$db = $this->load->database('dr_checking', TRUE);

		$result = $db->get_where('dr_list', ['itemCode' => $bCode, 'date' => $date, 'lotNo' => $lotNo, 'qty' => $qty, 'number' => $number, 'co_line' => $co_line]);

		return $result->result();
	}

	function change_item_status($date, $barcode, $lotNo, $qty, $co_line, $number) {
		$db = $this->load->database('dr_checking', true);

		$name = $this->session->lastName.', '.$this->session->firstName;
		$db->where(['date' => $date, 'itemCode' => $barcode, 'lotNo' => $lotNo, 'qty' => $qty, 'number' => $number, 'co_line' => $co_line]);
		$result = $db->get('dr_list')->row();

		if ($result->lot_tracked == 1) {
			try {
				$db->where(array('date' => $date, 'itemCode' => $barcode, 'lotNo' => $lotNo, 'qty' => $qty, 'co_line' => $co_line, 'number' => $number));
				// fixed bug when scanning all item resulted counter to 1
				// $db->update('dr_list', ['status' => 1, 'ctr' => 1,'name' => $name]);
				// item/s delivered had the option to reset the returned count
				$db->update('dr_list', ['status' => 1, 'ctr' => 1, 'returned' => 0, 'name' => $name]);

				return true;			
			} catch (Exception $e) {
				return false;
			}
		} else {
			try {
				$db->where(array('date' => $date, 'itemCode' => $barcode, 'lotNo' => $lotNo, 'qty' => $qty, 'co_line' => $co_line, 'number' => $number));
				// fixed bug when scanning all item resulted counter to 1
				// $db->update('dr_list', ['status' => 1, 'ctr' => 1,'name' => $name]);
				// item/s delivered had the option to reset the returned count
				$db->update('dr_list', ['status' => 1, 'returned' => 0, 'name' => $name]);

				return true;			
			} catch (Exception $e) {
				return false;
			}
		}
	}

	function change_p_status_by_item($date, $barcode, $lotNo, $qty, $co_line, $number)
	{
		$db = $this->load->database('dr_checking', TRUE);
		$name = $this->session->lastName.', '.$this->session->firstName;

		$db->where(['date' => $date, 'itemCode' => $barcode, 'lotNo' => $lotNo, 'qty' => $qty, 'number' => $number, 'co_line' => $co_line]);
		return $db->update('dr_list', ['p_status' => 1, 'name' => $name]);
	}

	function set_ctr_by_item($date, $number, $item, $ctr, $co_line)
	{
		$db = $this->load->database('dr_checking', TRUE);
		$name = $this->session->lastName.', '.$this->session->firstName;

		$db->where(['date' => $date, 'itemCode' => $item, 'lotNo' => 'NULL', 'lot_tracked' => 0, 'co_line' => $co_line]);
		$result = $db->get('dr_list')->row();

		if ($result->returned > 0) {
			$return = $result->returned - 1;			
			$db->where(['date' => $date, 'number' => $number, 'itemCode' => $item, 'lot_tracked' => 0, 'co_line' => $co_line]);
			$result = $db->update('dr_list', ['ctr' => $ctr, 'returned' => $return, 'name' => $name]);
		} else {
			$db->where(['date' => $date, 'number' => $number, 'itemCode' => $item, 'lot_tracked' => 0, 'co_line' => $co_line]);
			$result = $db->update('dr_list', ['ctr' => $ctr, 'name' => $name]);
		}

		// var_dump($db->last_query());

		return $result;
	}

	public function set_p_ctr_by_item($date, $number, $item, $ctr, $co_line)
	{
		$db = $this->load->database('dr_checking', TRUE);
		$name = $this->session->lastName.', '.$this->session->firstName;

		$db->where(['date' => $date, 'number' => $number, 'itemCode' => $item, 'lot_tracked' => 0, 'p_status' => 0, 'co_line' => $co_line]);
		return $db->update('dr_list', ['p_ctr' => $ctr, 'name' => $name]);
	}

	function log($empId, $desc){
		$db = $this->load->database('dr_checking', TRUE);

		try {
			$db->insert('logs', array('userId' => $empId, 'description' => strtoupper($desc)));

			return TRUE;
		} catch (Exception $e) {
			return FALSE;
		}
	}

	function scanLog($userId, $itemCode, $description) {
		$db = $this->load->database('dr_checking', TRUE);

		try {
			$name = $this->session->lastName.', '.$this->session->firstName;
			$db->insert('scanlogs', array('userId' => $userId, 'itemCode' => '\''.$itemCode, 'description' => strtoupper($description), 'name' => $name));

			return TRUE;
		} catch (Exception $e) {
			return FALSE;
		}
	}

	function importLogs($userId, $fileName, $date) {
		$db = $this->load->database('dr_checking', TRUE);

		try {
			$db->insert('importlogs', array('userId' => $userId, 'fileName' => $fileName, 'date' => $date));
			
			return TRUE;
		} catch (Exception $e) {
			return FALSE;
		}
	}

	function check_scanned($date, $number, $lotNo, $itemCode, $qty, $bool, $co_line) {
		$db = $this->load->database('dr_checking', true);

		if ($bool == 'false') {
			$status = 'status';
		} else {
			$status = 'p_status';
		}

		$result = $db->get_where('dr_list', array('date' => $date, 'number' => $number, 'lotNo' => $lotNo, 'itemCode' => $itemCode, 'qty' => $qty, $status => 1, 'co_line' => $co_line));
		return $result->result();
	}

	function insert_drpartial($number, $itemCode, $qty) {
		$db = $this->load->database('dr_checking', TRUE);
		$data = [
			'number' => $number,
			'itemCode' => $itemCode,
			'qty' => $qty
		];

		if ($this->check_drpartial_if_exist($number, $itemCode, $qty) == 0) {
			$result = $db->insert('drpartial', $data);
		}
	}

	function check_drpartial_if_exist($number, $itemCode, $qty)
	{
		$db = $this->load->database('dr_checking', TRUE);

		$data = [
			'number' => $number,
			'itemCode' => $itemCode,
			'qty' => $qty
		];

		$db->get_where('drpartial', $data);

		return $db->count_all_results('drpartial');
	}

	function update_drpartial($number, $itemCode, $qty)
	{
		$db = $this->load->database('dr_checking', TRUE);

		$db->where(['number' => $number, 'itemCode' => $itemCode, 'qty' => $qty]);
		return $db->update('drpartial', ['count' => $count]);
	}

	// for return items
	public function getDRNumber($date)
	{
		$db = $this->load->database('dr_checking', TRUE);

		$db->distinct();
		$db->where(['date' => $date]);
		$db->select('number');
		return $db->get('dr_list')->result();
	}

	public function getDRlistReturn($date, $number)
	{
		$db = $this->load->database('dr_checking', TRUE);

		$db->where('date', $date);
		$db->where('number', $number);
		$db->where('ctr >=', 1);
		// $db->where('status', 0);
		// $db->or_where('status', 1);		
		// $db->where('p_ctr >=', 1);		
		return $db->get('dr_list')->result();
		var_dump($db->last_query());
	}

	public function process_return_item($date, $number, $item)
	{
		$db = $this->load->database('dr_checking', TRUE);
		// $count = $this->get_item_p_count($date, $number, $item)['p_ctr'];
		$count = $this->get_item_p_count($date, $number, $item)['ctr'];
		$return = $this->get_item_p_count($date, $number, $item)['returned'];
		$name = $this->session->lastName.', '.$this->session->firstName;

		$db->trans_begin();

		// $db->where(['date' => $date, 'number' => $number, 'itemCode' => $item, 'p_ctr >=' => 1]);
		$db->where(['date' => $date, 'number' => $number, 'itemCode' => $item, 'ctr >=' => 1]);
		$db->update('dr_list', ['ctr' => $count - 1, 'status' => 0, 'returned' => $return + 1, 'name' => 'Ret. By: '.$name]);

		if ($db->trans_status() == FALSE) {
			$db->trans_rollback();
			return FALSE;
		} else {
			$db->trans_commit();
			return TRUE;
		}
	}

	public function get_item_with_lot($date, $number, $item)
	{
		$db = $this->load->database('dr_checking', TRUE);

		$db->where(['date' => $date, 'number' => $number, 'itemCode' => $item, 'status' => 1]);
		return $db->get('dr_list')->row();
	}

	public function get_item_p_count($date, $number, $item)
	{
		$db = $this->load->database('dr_checking', TRUE);

		$db->where(['date' => $date, 'number' => $number, 'itemCode' => $item, 'ctr >=' => 1, 'lot_tracked' => 0]);		
		return $db->get('dr_list')->row_array();
	}

	public function return_check_lot($date, $number, $item, $lot, $co_line)
	{
		$db = $this->load->database('dr_checking', TRUE);

		$db->where(['date' => $date, 'number' => $number, 'itemCode' => $item, 'lotNo' => $lot, 'status' => 1, 'co_line' => $co_line]);
		return $db->get('dr_list')->row();
	}

	public function return_check_qty($date, $number, $item, $lot, $qty, $co_line)
	{
		$db = $this->load->database('dr_checking', TRUE);

		$db->where(['date' => $date, 'number' => $number, 'itemCode' => $item, 'lotNo' => $lot, 'qty' => $qty, 'co_line' => $co_line]);
		$result = $db->get('dr_list')->row();
		return $result;
	}

	public function return_update_with_lot($date, $number, $item, $lot, $qty, $co_line)
	{
		if ($this->input->is_ajax_request() == FALSE) {
			die('Hacking is not allowed your IP is '.$this->input->ip_address());
		}

		$db = $this->load->database('dr_checking', TRUE);

		$db->where(['date' => $date, 'number' => $number, 'itemCode' => $item, 'lotNo' => $lot, 'qty' => $qty, 'co_line' => $co_line]);
		return $db->update('dr_list', ['returned' => 1, 'ctr' => 0, 'status' => 0, 'name' => "Ret. By ".$this->session->lastName.', '.$this->session->firstName]);
	}
	// end for return items
}