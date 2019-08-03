<?php
/**
* 
*/
class Main extends CI_Controller
{
	
	function __construct()
	{
		parent::__construct();
		$this->load->model(array('users_model'));
		$this->load->helper(array('form', 'url', 'html', 'date'));
		$this->load->library(array('form_validation', 'session'));
		$this->load->model(['inventory_model']);

		if (!$this->session->username) {
			redirect('login/admin');
		}
	}

	public function index()
	{
		$this->load->view('template/header');
		$this->load->view('template/navbar-main');
		$this->load->view('main/index');
		$this->load->view('template/footer');
	}

	function getDummyData() {
		$json = array();

		$json = '[{"itemCode":1276,"lotNo":1508,"quantity":5},{"itemCode":1304,"lotNo":1876,"quantity":4},{"itemCode":1779,"lotNo":1348,"quantity":4},{"itemCode":1901,"lotNo":1836,"quantity":5},{"itemCode":1138,"lotNo":1844,"quantity":2},{"itemCode":1101,"lotNo":1989,"quantity":3},{"itemCode":1499,"lotNo":1749,"quantity":4},{"itemCode":1067,"lotNo":1792,"quantity":1},{"itemCode":1849,"lotNo":1787,"quantity":4},{"itemCode":1678,"lotNo":1045,"quantity":1}]';

		echo $json;
	}

	public function return_item()
	{
		if (! $this->session->is_supervisor && ! $this->session->is_admin) {
			redirect('main');
		}

		$this->load->view('template/header');
		$this->load->view('template/navbar-main');
		$this->load->view('main/return');
		$this->load->view('template/footer');
	}

	// for return items
	public function getDRNumberByDate()
	{
		if ($this->input->is_ajax_request() == FALSE) {
			die('Hacking is not allowed your IP is '.$this->input->ip_address());
		}

		$date = $this->input->post('date');
		$data = $this->inventory_model->getDRNumber($date);
		echo json_encode($data);
	}

	public function getDrList()
	{
		if ($this->input->is_ajax_request() == FALSE) {
			die('Hacking is not allowed your IP is '.$this->input->ip_address());
		}

		$date = $this->input->post('date');
		$number = $this->input->post('number');

		$data = $this->inventory_model->getDRlistReturn($date, $number);

		echo json_encode($data);
	}

	public function process_return_item()
	{
		if ($this->input->is_ajax_request() == FALSE) {
			die('Hacking is not allowed you IP is '.$this->input->ip_address());
		}

		$date = $this->input->post('date');
		$number = $this->input->post('number');
		$item = $this->input->post('item');

		// check if item code has lot no		
		$data = $this->inventory_model->get_item_p_count($date, $number, $item);

		if (count($data) > 0) {
			$bool = $this->inventory_model->process_return_item($date, $number, $item);
			if ($bool) {
				$this->inventory_model->scanLog($this->session->recId, $data['description'], "Date: $data[date] Number: $data[number] Item: $data[itemCode] Delivered: $data[p_ctr] Returned: $data[returned]");
			}
			echo json_encode(['status' => $bool, 'info' => $data]);
		} else {
			$data = [];
			$data = $this->inventory_model->get_item_with_lot($date, $number, $item);

			if (count($data) > 0) {
				$bool = $this->inventory_model->scanLog($this->session->recId, $data->description, 'ITEM scanned matched the DR List');
				echo json_encode(['status' => $bool, 'info' => $data]);
			} else {
				$this->inventory_model->scanLog($this->session->recId, $item, "ITEM scanned doesn't matched the DR List");			
				echo json_encode(['status' => FALSE, 'msg' => 'Item scanned doesn\'t matched the DR List or Item wasn\'t delivered']);
			}
		}
	}

	public function checkLotNo()
	{
		if ($this->input->is_ajax_request() == FALSE) {
			die('Hacking is not allowed your IP is '.$this->input->ip_address());
		}

		$date = $this->input->post('date');
		$number = $this->input->post('number');
		$item = $this->input->post('item');
		$lot = $this->input->post('lot');

		// Additional 6/6/17
		$co_line = $this->input->post('co_line');

		$data = $this->inventory_model->return_check_lot($date, $number, $item, $lot, $co_line);

		if (is_null($data)) {
			$this->inventory_model->scanLog($this->session->recId, $item, "LOT NO scanned doesn't matched with the ITEM CODE");
			echo json_encode(['status' => FALSE, 'msg' => 'LOT NO doesn\'t matched the DR LIST']);
		} else {
			echo json_encode(['status' => TRUE, 'msg' => 'LOT NO matched the DR LIST']);
		}
	}

	public function checkQty()
	{
		if ($this->input->is_ajax_request() == FALSE) {
			die('Hacking is not allowed your IP is '.$this->input->ip_address());
		}

		$date = $this->input->post('date');
		$number = $this->input->post('number');
		$item = $this->input->post('item');
		$lot = $this->input->post('lot');
		$qty = $this->input->post('qty');

		// Additional 6/6/17
		$co_line = $this->input->post('co_line');
		// 7/21/2017
		$data = $this->inventory_model->return_check_qty($date, $number, $item, $lot, str_replace(',', '', $qty), $co_line);

		if (count($data) > 0) {
			$bool = $this->inventory_model->return_update_with_lot($date, $number, $item, $lot, str_replace(',', '', $qty), $co_line);
			if ($bool) {
				$this->inventory_model->scanLog($this->session->recId, $item, 'QUANTITY scanned matched with the ITEM CODE and LOT NO');
				echo json_encode(['status' => TRUE, 'msg' => 'ITEM returned successfully']);
			}
		} else {
			$this->inventory_model->scanLog($this->session->recId, $item, "QUANTITY scanned doesn't matched with LOT NO and ITEM CODE");
			echo json_encode(['status' => FALSE, 'msg' => 'QUANTITY defined in the system doesn\'t matched']);
		}
	}

	public function test()
	{
		$this->load->view('template/header');
		$this->load->view('template/navbar-admin');
		$this->load->view('test');
		$this->load->view('template/footer');
	}

	public function ajax_test()
	{
		echo json_encode(array('data' => $this->input->post('id')));
	}
}