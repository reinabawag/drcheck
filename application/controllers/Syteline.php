<?php

class Syteline extends CI_Controller
{
	
	function __construct()
	{
		parent::__construct();
		$this->load->model(['syteline_model', 'inventory_model']);
		$this->load->helper(['url', 'date']);
		$this->load->library(['session']);

		if ( ! isset($this->session->username)) {
			redirect('login');
		}
	}

	function index() 
	{
		$data = $this->syteline_model->import('2016-01-06');

		var_dump($data);
	}

	function import()
	{
		$date = $this->input->post('syteline-date');
		$data = array();
		$status = false;
		$count = 0;

		if ($date == '') {
			$status = FALSE;
		} else {
			$this->load->helper('download');

			$result = $this->syteline_model->import(date('Y-m-d', strtotime($date)));
			// force_download('sqlToCSV.csv', $result);
			// die();
			// var_dump($result);
			foreach ($result as $key => $value) {
				// var_dump($value);
			// 	$count++;
			// 	var_dump($value);

				// $ifExist = $this->inventory_model->checkIfExist($value->item, date('m/d/Y', strtotime($value->do_hdr_date)), is_null($value->lot) ? 'NULL' : $value->lot, number_format($value->uf_qtyshipconv, 2, '.', ''));

				// changes in checking dr list
				$ifExist = $this->inventory_model->check_if_exist_new(date('m/d/Y', strtotime($value->do_hdr_date)), $value->do_num, $value->ref_num, $value->ref_line, is_null($value->lot) ? 'NULL' : $value->lot);

				if (is_array($ifExist)) {
					$status = $this->inventory_model->updateDR(date('m/d/Y', strtotime($value->do_hdr_date)), $value->do_num, $value->name, $value->ref_num, $value->ref_line, $value->item, utf8_encode($value->description), is_null($value->lot) ? 'NULL' : $value->lot, number_format($value->uf_qtyshipconv, 2, '.', ''), $value->u_m, $value->lot_tracked, $ifExist['id']);
				} else {
					if ($this->inventory_model->insertDR(date('m/d/Y', strtotime($value->do_hdr_date)), $value->do_num, $value->name, $value->ref_num, $value->ref_line, $value->item, utf8_encode($value->description), is_null($value->lot) ? 'NULL' : $value->lot, number_format($value->uf_qtyshipconv, 2, '.', ''), $value->u_m, $value->lot_tracked)) {
						$status = TRUE;
					} else {
						$status = FALSE;
					}
				}
			}


			if ($count == 0) {
				echo json_encode(['status' => $status, 'message' => 'No DR filed for the date selected']);
			} else {
				echo json_encode(['status' => $status, 'message' => $status]);
			}
		}
	}
}