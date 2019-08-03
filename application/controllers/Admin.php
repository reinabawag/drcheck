<?php

use JasperPHP\JasperPHP;

class Admin extends CI_Controller
{
	
	function __construct()
	{
		parent::__construct();
		$this->load->model(array('users_model', 'inventory_model', 'reports_model', 'penlogs_model'));
		$this->load->helper(array('form', 'url', 'html', 'date', 'path'));
		$this->load->library(array('form_validation', 'session', 'excel'));

		if (!isset($this->session->username)) {
			redirect('login/admin');
		}
	}

	public function index()
	{
		if (!boolval($this->session->is_admin) && !boolval($this->session->is_supervisor)) {
			redirect('main');
		}

		$this->load->view('template/header');
		$this->load->view('template/navbar-admin');
		$this->load->view('admin/index');
		$this->load->view('template/footer');
	}

	public function create_dr()
	{
		if ( ! $this->input->is_ajax_request()) {
			$this->penlogs_model->log($this->session->recId, $this->input->server('HTTP_HOST'), 'Accessing '. uri_string());
			die('Hacking is not allowed.<br>Your IP is '.$this->input->server('HTTP_HOST'));
		}
		
		$this->load->model('inventory_model');
		$json = array();

		$this->form_validation->set_rules('date', 'Date', 'required');
		$this->form_validation->set_rules('drNumber', 'DR Number', 'required|');
		$this->form_validation->set_rules('itemCode', 'Item Code', 'required|alpha_numeric');
		$this->form_validation->set_rules('drNumber', 'DR Number', 'required|alpha_dash');

		if($this->form_validation->run()) {
			$date = $this->input->post('date');
			$number = $this->input->post('drNumber');
			$itemCode = $this->input->post('itemCode');
			$lotNo = $this->input->post('lotNo');
			$qty = $this->input->post('qty');

			if ($this->inventory_model->insertDR($date, $number, $itemCode, $lotNo, $qty)) {
				$json = array('status' => true, 'message' => 'Successfully saved DR');
			} else {
				$json = array('status' => false, 'message' => 'Error in creating DR');
			}

			echo json_encode($json);
		} else {
			echo json_encode(array('status' => false, 'message' => validation_errors()));
		}
	}

	public function parse_excel() {
		if ( ! $this->input->is_ajax_request()) {
			$this->penlogs_model->log($this->session->recId, $this->input->server('HTTP_HOST'), 'Accessing '. uri_string());
			die('Hacking is not allowed.<br>Your IP is '.$this->input->server('HTTP_HOST'));
		}

		$this->load->model('inventory_model');
		$this->load->helper('file');

		$status = FALSE;
		$json = array();
		$filename = $this->input->post('filename');
		$file = './import/'.$filename;

		$objPHPExcel= PHPExcel_IOFactory::load($file);		

		$cell_collection = $objPHPExcel->getActiveSheet()->getCellCollection();

		// foreach ($cell_collection as $cell) {
		//     $column = $objPHPExcel->getActiveSheet()->getCell($cell)->getColumn();
		//     $row = $objPHPExcel->getActiveSheet()->getCell($cell)->getRow();
		//     $data_value = $objPHPExcel->getActiveSheet()->getCell($cell)->getValue();
		//     //header will/should be in row 1 only. of course this can be modified to suit your need.
		//     if ($row == 1) {
		//         $header[][$row][$column] = $data_value;
		//     } else {
		//         $arr_data[$row][$column] = $data_value;
		//     }
		// }

		$dataArr = array();
		 
		foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {
		    $worksheetTitle     = $worksheet->getTitle();
		    $highestRow         = $worksheet->getHighestRow(); // e.g. 10
		    $highestColumn      = $worksheet->getHighestColumn(); // e.g 'F'
		    $highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
		     
		    for ($row = 1; $row <= $highestRow; ++ $row) {
		        for ($col = 0; $col < $highestColumnIndex; ++ $col) {
		            $cell = $worksheet->getCellByColumnAndRow($col, $row);
		            $val = $cell->getValue();
		            $dataArr[$row][$col] = $val;
		        }
		    }
		}

		if (! is_array($dataArr) || is_null($dataArr)) {
			$status = FALSE;
		}
		unset($dataArr[1]);

		//send the data in an array format
		// $data['header'] = $header;
		// $data['values'] = $arr_data;

		// print_r($data['header']);
		// echo '<br>';
		// print_r($data['values']);
		// foreach ($arr_data as $hea => $val) {
		// 	var_dump($hea[1][1]);
		// }
		// print_r($dataArr);

		foreach ($dataArr as $key => $value) {
			$date 			= date('m/d/Y', PHPExcel_Shared_Date::ExcelToPHP($value[0]));
			$number 		= $value[1];
			$customer 		= $value[2];
			$co 			= $value[3];
			$co_line 		= $value[4];
			$itemCode 		= $value[5];
			$description 	= $value[6];
			$lotNo 			= $value[7];
			$qty 			= number_format($value[8], 2, '.', '');
			$um 			= $value[9];
			$lot_tracked 	= $value[10];

			// $ifExist = $this->inventory_model->checkIfExist($itemCode, date('m/d/Y', PHPExcel_Shared_Date::ExcelToPHP($value[0])), $lotNo, $qty);

			// changes in checking dr list
			$ifExist = $this->inventory_model->check_if_exist_new($date, $number, $co, $co_line, $lotNo);

			try {
				if (is_array($ifExist)) {
					$this->inventory_model->updateDR($date, $number, $customer, $co, $co_line, $itemCode, $description, $lotNo, $qty, $um, $lot_tracked, $ifExist['id']);
				} else {
					if ($this->inventory_model->insertDR($date, $number, $customer, $co, $co_line, $itemCode, $description, $lotNo, $qty, $um, $lot_tracked)) {
						$status = TRUE;
					} 
				}
				
				$status = TRUE;
			} catch (Exception $e) {
				$status = FALSE;
			}
		}

		if ($status) {
			$fInfo = get_file_info($file);
			$this->inventory_model->importLogs($this->session->recId, $fInfo['name'], unix_to_human($fInfo['date']));
			$json = array('status' => true, 'message' => 'Successfully imported DR');
		} else {
			$json = array('status' => false, 'message' => 'Error in importing DR');
		}

		echo json_encode($json);
	}

	public function get_dr_list($offest = 0) {
		if ( ! $this->input->is_ajax_request()) {
			$this->penlogs_model->log($this->session->recId, $this->input->server('HTTP_HOST'), 'Accessing '. uri_string());
			die('Hacking is not allowed.<br>Your IP is '.$this->input->server('HTTP_HOST'));
		}

		$this->load->model('inventory_model');
		$this->load->library('pagination');

		$config['base_url'] = site_url('admin/get_dr_list');
		$config['total_rows'] = $this->inventory_model->count_dr();
		$config['per_page'] = 10;

		$config['full_tag_open'] = "<ul class='pagination'>";
		$config['full_tag_close'] ="</ul>";
		$config['num_tag_open'] = '<li>';
		$config['num_tag_close'] = '</li>';
		$config['cur_tag_open'] = "<li class='disabled'><li class='active'><a href='#'>";
		$config['cur_tag_close'] = "<span class='sr-only'></span></a></li>";
		$config['next_tag_open'] = "<li>";
		$config['next_tagl_close'] = "</li>";
		$config['prev_tag_open'] = "<li>";
		$config['prev_tagl_close'] = "</li>";
		$config['first_tag_open'] = "<li>";
		$config['first_tagl_close'] = "</li>";
		$config['last_tag_open'] = "<li>";
		$config['last_tagl_close'] = "</li>";

		$this->pagination->initialize($config);

		$json = $this->inventory_model->getDRList($config['per_page'], $offest);

		echo json_encode(array('1' => $json, 'pagination' => $this->pagination->create_links()));
	}

	public function search($offest = 0) {
		if ( ! $this->input->is_ajax_request()) {
			$this->penlogs_model->log($this->session->recId, $this->input->server('HTTP_HOST'), 'Accessing '. uri_string());
			die('Hacking is not allowed.<br>Your IP is '.$this->input->server('HTTP_HOST'));
		}

		$this->load->library('pagination');
		$this->load->model('inventory_model');
		$search = $this->input->get('search');

		$config['per_page'] = 10;

		$json = $this->inventory_model->searchDR($search, $config['per_page'], $offest);

		$config['base_url'] = site_url('admin/search');
		$config['total_rows'] = $json['count_results'];

		$config['full_tag_open'] = "<ul class='pagination'>";
		$config['full_tag_close'] ="</ul>";
		$config['num_tag_open'] = '<li>';
		$config['num_tag_close'] = '</li>';
		$config['cur_tag_open'] = '<li class="active"><a href="#">';
        $config['cur_tag_close'] = '</a></li>';
		$config['next_tag_open'] = "<li>";
		$config['next_tagl_close'] = "</li>";
		$config['prev_tag_open'] = "<li>";
		$config['prev_tagl_close'] = "</li>";
		$config['first_tag_open'] = "<li>";
		$config['first_tagl_close'] = "</li>";
		$config['last_tag_open'] = "<li>";
		$config['last_tagl_close'] = "</li>";

		// $json = $this->inventory_model->searchDR($search, $config['per_page'], $offest);

		$this->pagination->initialize($config);

		echo json_encode(array($json, 'pagination' => $this->pagination->create_links()));
	}

	public function show_dr_by_date() {
		if ( ! $this->input->is_ajax_request()) {
			$this->penlogs_model->log($this->session->recId, $this->input->server('HTTP_HOST'), 'Accessing '. uri_string());
			die('Hacking is not allowed.<br>Your IP is '.$this->input->server('HTTP_HOST'));
		}

		$this->load->model('inventory_model');

		$date = $this->input->post('date');
		// Additional
		$number = $this->input->post('number');
		$toggle = $this->input->post('toggle');
		$json = array();

		// for improvements added toggle check functionality for update list
		// foreach ($this->inventory_model->getDRByDate($date, $number) as $key => $value) {
		// 	if ($value->status) {
		// 		$img = img('assets/images/Ok-icon.png', FALSE, array('width' => '15px'));
		// 	} elseif (($value->ctr >= 1) && ($value->status != 1)) {
		// 		$img = img('assets/images/checked-symbol(1).png', FALSE);
		// 	} else {
		// 		$img = '';
		// 	}
		// 	$json[] = ['description' => $value->description, 'lotNo' => $value->lotNo, 'qty' => $value->qty, 'img' => $img];
		// }

		foreach ($this->inventory_model->getDRByDate($date, $number) as $key => $value) {
			if ($toggle == "true") {
				if ($value->p_status) {
					$img = img('assets/images/Ok-icon.png', FALSE, array('width' => '15px'));
				} elseif (($value->p_ctr >= 1) && ($value->p_status != 1)) {
					$img = '<strong><span>Count '.$value->p_ctr.'</span></strong>';
				} else {
					$img = '';
				}
				// $img = '';
				// img('assets/images/checked-symbol(1).png
			} else {
				if ($value->status) {
					$img = img('assets/images/Ok-icon.png', FALSE, array('width' => '15px'));
				} elseif (($value->ctr >= 1) && ($value->status != 1)) {
					$img = img('assets/images/checked-symbol(1).png', FALSE);
				} else {
					$img = '';
				}
			}

			$json[] = ['customer' => $value->customer, 'description' => $value->description, 'lotNo' => $value->lotNo, 'qty' => $value->qty, 'img' => $img];
		}

		echo json_encode($json);
	}

	public function checkBCode() 
	{
		if ( ! $this->input->is_ajax_request()) {
			$this->penlogs_model->log($this->session->recId, $this->input->server('HTTP_HOST'), 'Accessing '. uri_string());
			die('Hacking is not allowed.<br>Your IP is '.$this->input->server('HTTP_HOST'));
		}

		$bCode = $this->input->post('bCode');
		$date = $this->input->post('date');
		$number = $this->input->post('number');
		$noLot = $this->input->post('noLot');

		// Additional 6/6/17 added for checking
		$co_line = $this->input->post('co_line');

		$toggle = $this->input->post('toggle-status');

		$data = $this->inventory_model->checkBCodeByDate($bCode, $date, $number);

		if (is_null($data)) {
			$data = $this->inventory_model->checkBCodeByDateNoLot($bCode, $date, $number);
			if (is_null($data)) {
				$this->inventory_model->scanLog($this->session->recId, $bCode, 'Scanned Invalid Item Code. DR Number and Item Code doesn\'t matched');
			}
		} else {
			$this->inventory_model->scanLog($this->session->recId, $bCode, 'Item Code Matched the DR LIST');
		}

		echo json_encode($data);
	}

	public function update_item()
	{
		$date = $this->input->post('date');
		$number = $this->input->post('number');
		$item = $this->input->post('item');
		$lot = $this->input->post('lot');
		$toggle = $this->input->post('toggle');
		$ctr = $this->input->post('ctr');

		// Additional 6/6/17
		$co_line = $this->input->post('co_line');

		if ($toggle == 'true') {
			$this->inventory_model->set_p_ctr_by_item($date, $number, $item, $ctr, $co_line);
		} else {
			$this->inventory_model->set_ctr_by_item($date, $number, $item, $ctr, $co_line);
		}
	}

	function checkLotNo() {
		if ( ! $this->input->is_ajax_request()) {
			$this->penlogs_model->log($this->session->recId, $this->input->server('HTTP_HOST'), 'Accessing '. uri_string());
			die('Hacking is not allowed.<br>Your IP is '.$this->input->server('HTTP_HOST'));
		}

		$bCode = $this->input->post('bCode');
		$date = $this->input->post('date');
		$lotNo = $this->input->post('lotNo');
		$number = $this->input->post('number');

		// Additional 6/6/17
		// $co_line = $this->input->post('co_line');

		$data = $this->inventory_model->checkLotNo($bCode, $date, $lotNo, $number);

		if ( ! is_array($data) || is_null($data)) {
			$this->inventory_model->scanLog($this->session->recId, $bCode, 'Mismatched Item Code: '.$bCode.' and Lot No.: '.$lotNo);
		} else {
			$this->inventory_model->scanLog($this->session->recId, $bCode, 'ITEM CODE and LOT NO matched the DR LIST');
		}

		echo json_encode($data);
	}

	function check_qty() {
		if ( ! $this->input->is_ajax_request()) {
			$this->penlogs_model->log($this->session->recId, $this->input->server('HTTP_HOST'), 'Accessing '. uri_string());
			die('Hacking is not allowed.<br>Your IP is '.$this->input->server('HTTP_HOST'));
		}

		$bCode = $this->input->post('bCode');
		$date = $this->input->post('date');
		$lotNo = $this->input->post('lotNo');
		$qty = $this->input->post('qty');
		$number = $this->input->post('number');
		$toggle = $this->input->post('toggle');

		// Additional 6/6/17
		$co_line = $this->input->post('co_line');

		// For removal of comma 7/21/2017
		$data = $this->inventory_model->checkQTY($bCode, $date, $lotNo, str_replace(',', '', $qty), $number, $co_line);

		if (is_null($data) || ! is_array($data) || $data == '') {
			$this->inventory_model->scanLog($this->session->recId, $bCode, 'Mismatched Item Code: '.$bCode.' and Lot No.: '.$lotNo.' Quantity: '.$qty);
		} else {
			$this->inventory_model->scanLog($this->session->recId, $bCode, 'ITEM CODE, LOT NO and QUANTITY matched the DR LIST');
		}

		if ( ! is_array($data)) {
			$json = ['status' => false, 'message' => 'Error in inventory_model/checkQTY'];
		} else {
			if (count($this->inventory_model->check_scanned($date, $number, $lotNo, $bCode, $qty, $toggle, $co_line)) > 0) {
				echo json_encode(array('status' => 'exist'));
			} else {
				echo json_encode($data);
			}
		}
	}

	function scan_log() {
		if ( ! $this->input->is_ajax_request()) {
			$this->penlogs_model->log($this->session->recId, $this->input->server('HTTP_HOST'), 'Accessing '. uri_string());
			die('Hacking is not allowed.<br>Your IP is '.$this->input->server('HTTP_HOST'));
		}

		$username = $this->input->post('username');
		$dr_id = $this->input->post('dr_id');
		$status = $this->input->post('status');

		if ($this->inventory_model->scan_log($username, $dr_id, $status)) {
			echo json_encode(['status' => TRUE]);
		} else {
			echo json_encode(['status' => FALSE]);
		}
	}

	function change_item_status() {
		if ( ! $this->input->is_ajax_request()) {
			$this->penlogs_model->log($this->session->recId, $this->input->server('HTTP_HOST'), 'Accessing '. uri_string());
			die('Hacking is not allowed.<br>Your IP is '.$this->input->server('HTTP_HOST'));
		}

		$rsp;

		$number = $this->input->post('number');
		$date = $this->input->post('date');
		$barcode = $this->input->post('barcode');
		$lotNo = $this->input->post('lotNo');
		$qty = $this->input->post('qty');
		$toggle = $this->input->post('toggle');

		// Additional 6/6/17
		$co_line = $this->input->post('co_line');
		// remove comma 7/21/2017
		if ($toggle == 'false') {
			$rsp = $this->inventory_model->change_item_status($date, $barcode, $lotNo, str_replace(',', '', $qty), $co_line, $number);
		} else {
			$rsp = $this->inventory_model->change_p_status_by_item($date, $barcode, $lotNo, str_replace(',', '', $qty), $co_line, $number);
		}		

		echo json_encode($rsp);
	}

	// Additional if item is doesnt suffice the quantity definec in the DR LIST
	function set_ctr_by_item()
	{
		if ($this->input->is_ajax_request() == FALSE) {
			die('Hacking is not allowed your IP is '.$this->input->ip_address());
		}

		$date = $this->input->post('date');
		$number = $this->input->post('number');
		$item = $this->input->post('item');
		$ctr = $this->input->post('ctr');

		try {
			$this->inventory_model->set_ctr_by_item($date, $number, $item, $ctr);
		} catch (Exception $e) {
			echo $e;
		}
	}

	public function set_p_ctr_by_item()
	{
		if ($this->input->is_ajax_request() == FALSE) {
			die('Hacking is not allowed your IP is '.$this->input->ip_address());
		}

		$date = $this->input->post('date');
		$number = $this->input->post('number');
		$item = $this->input->post('item');
		$ctr = $this->input->post('ctr');

		if ($this->inventory_model->set_p_ctr_by_item($date, $number, $item, $ctr) == FALSE) {
			die('Error in setting p_ctr');
		}
	}

	function read_directory() {
		if ( ! $this->input->is_ajax_request()) {
			$this->penlogs_model->log($this->session->recId, $this->input->server('HTTP_HOST'), 'Accessing '. uri_string());
			die('Hacking is not allowed.<br>Your IP is '.$this->input->server('HTTP_HOST'));
		}

		$this->load->helper(['file', 'date_helper']);
		$data = get_dir_file_info('import');
		$json = array();

		foreach ($data as $key => $value) {
			$json[] = ['filename' => $value['name'], 'date' => unix_to_human($value['date'])];
		}

		echo json_encode($json);
	}

	function file_upload() {
		if ( ! $this->input->is_ajax_request()) {
			$this->penlogs_model->log($this->session->recId, $this->input->server('HTTP_HOST'), 'Accessing '. uri_string());
			die('Hacking is not allowed.<br>Your IP is '.$this->input->server('HTTP_HOST'));
		}

		$config['upload_path']          = './import/';
        $config['allowed_types']        = '*';
        $config['remove_spaces']		= false;

        $this->load->library('upload', $config);

        if ( ! $this->upload->do_upload('userfile'))
        {
            $error = array('error' => $this->upload->display_errors());

            echo json_encode(array('status' => false, 'message' => $this->upload->display_errors(), 'ext' => $this->upload->data('file_ext')));
        }
        else
        {
       		echo json_encode(array('status' => true, 'message' => 'File Uploaded Successfully', 'upload_data' => $this->upload->data()));
        }
	}

	function report() {
		if ( ! boolval($this->session->is_admin) && ! boolval($this->session->is_supervisor)) {
			redirect('main');
		}

		$this->load->view('template/header');
		$this->load->view('template/navbar-admin');
		$this->load->view('admin/report');
		$this->load->view('template/footer');
	}

	function get_dr_number_by_date() {

		if ( ! $this->input->is_ajax_request()) {
			$this->penlogs_model->log($this->session->recId, $this->input->server('HTTP_HOST'), 'Accessing '. uri_string());
			die('Hacking is not allowed.<br>Your IP is '.$this->input->server('HTTP_HOST'));
		}

		$drDate = $this->input->get('drDate');

		$db = $this->load->database('dr_checking', TRUE);

		$db->distinct();
		$db->group_by('number');
		$result = $db->get_where('dr_list', array('date' => $drDate));
		echo json_encode($result->result());
	}

	function generate_report() {
		if ( ! $this->input->is_ajax_request()) {
			$this->penlogs_model->log($this->session->recId, $this->input->server('HTTP_HOST'), 'Accessing '. uri_string());
			die('Hacking is not allowed.<br>Your IP is '.$this->input->server('HTTP_HOST'));
		}

		$this->load->library('pdf');

		$pdf = new pdf();
		$pdf->AliasNbPages();
		$pdf->AddPage();
		$pdf->SetFont('Times','',12);
		for($i=1;$i<=40;$i++)
		    $pdf->Cell(0,10,'Printing line number '.$i,0,1);

		$startDate = $this->input->post('startDate');
		$endDate = $this->input->post('endDate');
		$type = $this->input->post('type');

		switch ($type) {
			case 1:
				echo json_encode($this->report_login($startDate, $endDate));
				break;
			case 2:
				$rspType = $type;
				break;
			default:
				# code...
				break;
		}
	}

	function report_login() {
		if ( ! $this->input->is_ajax_request()) {
			$this->penlogs_model->log($this->session->recId, $this->input->server('HTTP_HOST'), 'Accessing '. uri_string());
			die('Hacking is not allowed.<br>Your IP is '.$this->input->server('HTTP_HOST'));
		}

		$this->load->library('pdf');
		$db = $this->load->database('dr_checking', true);
		
		$startDate = $this->input->post('startDate');
		$endDate = $this->input->post('endDate');

		$pdf = new pdf();
		$pdf->AliasNbPages();
		$pdf->AddPage();
		$pdf->SetFont('Times','',10);
		$pdf->Cell(20,10,'Title',1,1,'C');

		$db->like(array('dateTime' => date('Y-m-d', strtotime($startDate))));
		$db->or_like(array('dateTime' => date('Y-m-d',strtotime($endDate))));
		$result = $db->get('logs');

		foreach ($result->result() as $key => $value) {
			$pdf->Cell(0,10,$value->userId,0,1);
			$pdf->Cell(0,0,$value->description,0,1);
		}
		$pdf->Output();
	}

	function login_report_new() 
	{
		if ($this->session->is_admin == 0 && $this->session->is_supervisor == 0) {
			redirect('main');
		}

		$this->load->library('pdf');
		$startDate = $this->input->get('startDate');
		$endDate = $this->input->get('endDate');

		$result = $this->reports_model->login_report($this->session->recId, date('Y-m-d', strtotime($startDate)), date('Y-m-d', strtotime($endDate)));

		$pdf = new pdf();
		$pdf->AliasNbPages();
		$pdf->AddPage();

		$pdf->cell(45);
		$pdf->Cell(100,3,'Login Report',0,1,'C');
		$pdf->ln(5);

		$pdf->SetFont('Arial', 'B', 8);
		$pdf->Cell(0,0,'FROM: '.$startDate.' TO: '.$endDate, 0,0);
		$pdf->ln();
		// $pdf->Cell(156);
		$pdf->MultiCell(0,0,'GENERATED BY: '.$this->session->lastName.', '.$this->session->firstName, 0, 'R');
		$pdf->ln(5);
		$pdf->MultiCell(0,0, 'DATE: '.date('m/d/Y'), 0, 'R');
		$pdf->ln(5);

		$pdf->SetFont('Arial','',8);
		$pdf->Cell(63,4,'Name',1);
		$pdf->Cell(63,4,'Description',1);
		$pdf->Cell(63,4,'Date Time',1);

		foreach($result as $row) {
			$pdf->SetFont('Arial','',8);	
			$pdf->Ln();
			foreach($row as $column)
			$pdf->Cell(63,4,$column,1);
		}
		$pdf->Output('', 'login_report_'.date('mdY').'.pdf');
	}

	function dr_summary() {
		
		$this->load->library('pdf');
		$date = $this->input->get('date');
		$number = $this->input->get('drNumber');

		$result = $this->reports_model->dr_summary($date, $number);

		$pdf = new pdf();
		$pdf->AliasNbPages();
		$pdf->AddPage();

		$pdf->cell(45);
		$pdf->Cell(100,3,'DR SUMMARY',0,1,'C');
		$pdf->ln(5);

		$pdf->SetFont('Arial', 'B', 8);
		$pdf->Cell(0,0,'DR DATE: '.$date.' NUMBER: '.$number, 0,0);
		$pdf->ln();
		// $pdf->Cell(156);
		$pdf->MultiCell(0,0,'GENERATED BY: '.$this->session->lastName.', '.$this->session->firstName, 0, 'R');
		$pdf->ln(5);
		$pdf->MultiCell(0,0, 'DATE: '.date('m/d/Y'), 0, 'R');
		$pdf->ln(5);

		$pdf->SetFont('Arial','',8);
		$pdf->Cell(85,4,'Description',1);
		$pdf->Cell(20,4,'Lot No',1);
		$pdf->Cell(19,4,'Quantity',1);
		$pdf->Cell(30,4,'Status',1);
		$pdf->Cell(35,4,'Name',1);

		foreach($result as $row) {
			$pdf->SetFont('Arial','',8);	
			$pdf->Ln();
			// foreach($row as $column)
			// $pdf->Cell(45,4,$column,1);
			$pdf->Cell(85,4,$row->description,1);
			$pdf->Cell(20,4,$row->lotNo,1);
			$pdf->Cell(19,4,$row->qty,1);
			if ($row->ctr > 0 && $row->status == 0) {
				$pdf->Cell(30,4,'PARTIAL '.$row->ctr,1);
			} else {
				$row->status == 1 ? $pdf->Cell(30,4,'CHECKED',1) : $pdf->Cell(30,4,'NOT CHECKED',1);
			}
			$pdf->Cell(35,4,$row->name,1);
		}
		$pdf->SetTitle('DR SUMMARY');

		$pdf->Output('', 'dr_summary_'.date('mdY').'.pdf');
		
	}

	public function dr_summary_new()
	{
		$date = $this->input->get('date');
		$name = $this->session->lastName.', '.$this->session->firstName;
		$this->load->helper('file');
		$jasper = new JasperPHP;
		$input = set_realpath('rpt/dr_summary.jrxml');
		$jasper->compile($input)->execute();
		$options = [
		    'format' => ['pdf'],
		    'locale' => 'en',
		    'params' => ['date' => $date, 'name' => $name],
		    'db_connection' => [
		        'driver' => 'mysql',
		        'username' => 'root',
		        'host' => 'localhost',
		        'database' => 'drcheck',
		        'port' => '3306'
		    ]
		];

		$jasper->process(
			$input,
			FALSE,
			$options
		)->execute();

		header('Content-type: application/pdf');
		header('Content-Disposition: inline; filename=" DR_SUMMARY_'.date('mdY').'.pdf"');
		header('Content-Transfer-Encoding: binary');
		header('Accept-Ranges: bytes');
		echo '<title>BULK DR SUMMARY</title>';
		readfile(FCPATH."rpt/dr_summary".'.pdf');
	}

	function dr_report() {
		if ($this->session->is_admin == 0 && $this->session->is_supervisor == 0) {
			redirect('main');
		}

		$this->load->library('pdf');
		$this->load->dbutil();
		$this->load->helper('download');

		$startDate = $this->input->get('startDate');
		$endDate = $this->input->get('endDate');

		$result = $this->reports_model->dr_report($startDate, $endDate);
		// echo $this->dbutil->csv_from_result($result);
		// var_dump($result);

		// $pdf = new pdf();
		// $pdf->AliasNbPages();
		// $pdf->AddPage();

		// $pdf->cell(45);
		// $pdf->Cell(100,3,'DR LOGS',0,1,'C');
		// $pdf->ln(5);

		// foreach($result as $row) {
		// 	$pdf->SetFont('Arial','',8);	
		// 	// $pdf->Ln(5);
		// 	$pdf->Cell(40,4,$row['name'], 1);
		// 	// $pdf->Cell(40);
		// 	$pdf->Cell(40,4,$row['itemCode'], 1);
		// 	// $pdf->Cell(60);
		// 	$pdf->Cell(40,4,$row['description'], 1);
		// 	// $pdf->Cell(80);
		// 	$pdf->Cell(40,4,$row['date'], 1);

		// 	$pdf->ln(5);
		// 	// foreach($row as $column)
		// 	// $pdf->Cell(63,4,$column,1);
		// }
		// // foreach ($variable as $key => $value) {
		// // 	$pdf->SetFont('Arial','',8);

		// // }

		// $pdf->Output('', 'dr_report_'.date('mdY'));
		if (count($result) > 0) {
			force_download('dr_report_'.date('mdY').'.csv', $this->str_putcsv($result));
			// $objPHPExcel = new PHPExcel();
        
	        // Fill worksheet from values in array
	        // $objPHPExcel->getActiveSheet()->fromArray($result, null, 'A1');
	        
	        // Rename worksheet
	        // $objPHPExcel->getActiveSheet()->setTitle('DR_REPORT');
	        // $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        	// $objWriter->save('dr_logs_'.date('mdY').'.xlsx');
        	// force_download('dr_logs_'.date('mdY').'.xlsx', NULL);
		} else {
			echo 'No transaction made from: '.$startDate.' to: '.$endDate;	
		}
	}

	function str_putcsv($data) {
        # Generate CSV data from array
        $fh = fopen('php://temp', 'rw'); # don't create a file, attempt
                                         # to use memory instead

        # write out the headers
        fputcsv($fh, array_keys(current($data)));

        # write out the data
        foreach ( $data as $row ) {
            fputcsv($fh, $row);
        }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);

        return $csv;
	}

	public function new_report()
	{
		if ($this->session->is_admin == 0 || $this->session->is_supervisor == 0) {
			redirect('main');
		}

		$startDate = $this->input->get('startDate');
		$endDate = $this->input->get('endDate');

		// inhibit DOMPDF's auto-loader
		define('DOMPDF_ENABLE_AUTOLOAD', false);
		define('DOMPDF_ENABLE_CSS_FLOAT', true);

		//include the DOMPDF config file (required)
		require 'dompdf/dompdf_config.inc.php';

		//if you get errors about missing classes please also add:
		require_once('dompdf/include/autoload.inc.php');

		$result = $this->reports_model->dr_report($startDate, $endDate);

		$this->load->view('admin/index');

		$html = '<style>* {font-family: "Arial"}</style>
			<div style="text-align: center; font-weight: bold">
				<img src="assets/images/amwire_logo.gif" width="90"><br>
				<span>American Wire & Cable Co., Inc.</span><br>
				<span>DR REPORT</span><br><br>
			</div>
			<table class="noBorder" border="0"><tr><td>DATE: '.date('m/d/Y').'</td></tr><table>';

		$html .= '
			
			<table border="1" width="100%" style="border-collapse: collapse; font-size: 10pt;">
			<tr><th>Name</th><th>Item</th><th>Description</th><th>Date</th></tr>';
		foreach ($result as $key => $value) {
			$html .= '<tr>';
			$html .= '<td>'.$value['name'].'</td>';
			$html .= '<td width="20px">'.$value['item'].'</td>';
			$html .= '<td>'.$value['description'].'</td>';
			$html .= '<td>'.$value['date'].'</td>';
			$html .= '</tr>';
		}
		$html .= '</table>';

		$dompdf = new DOMPDF();
		$dompdf->load_html($html);
		$dompdf->render();
		$dompdf->stream("dr_report_".date('mdY').'.pdf', array("Attachment"=>0));
	}

	public function dr_logs_new()
	{
		include_once('class/tcpdf/tcpdf.php');
		include_once("class/PHPJasperXML.inc.php");

		$PHPJasperXML = new PHPJasperXML();
		$PHPJasperXML->debugsql=false;
		$PHPJasperXML->arrayParameter=array("startDate" => date('m/d/Y'), 'endDate' => date('m/d/Y'));
		$PHPJasperXML->load_xml_file("rpt/dr_logs.jrxml");

		$PHPJasperXML->transferDBtoArray('localhost','root','','drcheck');
		$PHPJasperXML->outpage("I");    //page output method I:standard output  D:Download file
	}

	public function return_item_report()
	{
		include_once('class/tcpdf/tcpdf.php');
		include_once("class/PHPJasperXML.inc.php");

		$PHPJasperXML = new PHPJasperXML();
		$PHPJasperXML->debugsql=false;
		$PHPJasperXML->arrayParameter=array("startDate" => date('m/d/Y'), 'endDate' => date('m/d/Y'));
		$PHPJasperXML->load_xml_file("rpt/item_return_report.jrxml");

		$PHPJasperXML->transferDBtoArray('localhost','root','','drcheck');
		$PHPJasperXML->outpage('I');
	}

	public function dr_logs_report()
	{
		$this->load->helper('file');
		delete_files('rpt/dr_Logs_report.pdf');
		$jasper = new JasperPHP;
		$input = set_realpath('rpt/dr_Logs_report.jrxml');
		$jasper->compile($input)->execute();
		// echo set_realpath('rpt/dr_logs_report.pdf', TRUE);
		$options = [
		    'format' => ['pdf'],
		    'locale' => 'en',
		    'params' => ['startDate' => date('Y-m-d', strtotime($this->input->get('startDate'))), 'endDate' => date('Y-m-d', strtotime($this->input->get('endDate'))).' 23:59:59'],
		    'db_connection' => [
		        'driver' => 'mysql',
		        'username' => 'root',
		        'host' => 'localhost',
		        'database' => 'drcheck',
		        'port' => '3306'
		    ]
		];

		$jasper->process(
			$input,
			FALSE,
			$options
		)->execute();

		$filename = 'DR_LOGS_'.date('Ymd');
		header('Content-type: application/pdf');
		header('Content-Disposition: inline; filename="' . $filename . '.pdf"');
		header('Content-Transfer-Encoding: binary');
		header('Accept-Ranges: bytes');
		echo file_get_contents(FCPATH."rpt/dr_Logs_report".'.pdf');
	}
}