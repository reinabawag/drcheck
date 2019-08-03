<?php

class Reports_model extends CI_Model
{
	
	public function __construct()
	{
		parent::__construct();
		$this->load->database();
		$this->load->helper(['date', 'url']);
		$this->load->library(['session']);

		if (!isset($this->session->username)) {
			redirect('login');
		}
	}

	public function login_report($userId, $startDate, $endDate)
	{
		$db = $this->load->database('dr_checking', TRUE);
		$data = [];

		$startDate  = mdate($startDate);
		$endDate = mdate(date('Y-m-d', strtotime('tomorrow', strtotime($endDate))));

		$db->where('dateTime >=', $startDate);
		$db->where('dateTime <=', $endDate);
		$result = $db->get('logs');

		foreach ($result->result() as $key => $value) {
			$data[] = array('name' => @$this->getUserById($value->userId)->lastname.', '.@$this->getUserById($value->userId)->firstname, 'description' => $value->description, 'dateTime' => $value->dateTime);
		}

		if (is_null($data)) {
			return false;
		} else {
			return $data;	
		}
	}

	public function dr_report($startDate, $endDate) {
		$db = $this->load->database('dr_checking', TRUE);
		$data = [];

		$startDate  = date('Y-m-d', strtotime($startDate));
		$endDate = mdate(date('Y-m-d', strtotime('tomorrow', strtotime($endDate))));

		$db->where('date >=', $startDate);
		$db->where('date <=', $endDate);
		$result = $db->get('scanlogs')->result();

		foreach ($result as $key => $value) {
			$data[] = array('name' =>  $this->getUserById($value->userId)->lastname.', '.$this->getUserById($value->userId)->firstname, 'item' => $value->itemCode, 'description' => $value->description, 'date' => $value->date);
		}

		if (is_null($data)) {
			return false;
		} else {
			return $data;
		}
	}	

	public function dr_summary($date, $drNumber) {
		$db = $this->load->database('dr_checking', TRUE);

		$db->select('description, lotNo, qty, status, name, ctr');
		$result = $db->get_where('dr_list', array('date' => $date, 'number' => $drNumber));

		return $result->result();
	}

	public function getUserById($id) 
	{
		$db = $this->load->database('amwire_security', TRUE);

		$db->select('empinfo.lastName AS lastname, empinfo.firstName AS firstname');
		$db->join('empinfo', 'empinfo.userId=user.userId', 'left');
		return $db->get_where('user', array('user.userId' => $id))->row();
	}
}

