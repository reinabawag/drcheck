<?php 

class Login extends CI_Controller {

	public function __construct() {
		parent::__construct();
		$this->load->model(array('users_model', 'inventory_model', 'penlogs_model'));
		$this->load->helper(array('form', 'url', 'html'));
		$this->load->library(array('form_validation', 'session'));
	}

	public function admin() {
		if (isset($this->session->username)) {
			redirect('main');
		}

		$this->load->view('template/header');
		$this->load->view('login/index');
		$this->load->view('template/footer');
	}

	public function get_login() 
	{
		if ( ! $this->input->is_ajax_request()) {
			$this->penlogs_model->log($this->session->recid, $this->input->ip_addr(), 'Accessing '. uri_string());
			die('Hacking is not allowed.<br>Your IP is '.$this->input->server('REMOTE_ADDR'));
		}

		$json = array();
		$username = $this->input->post('username');
		$password = $this->input->post('password');
		$empInfo = $this->users_model->check_login($username, $password);

		if ( ! is_array($empInfo) || is_null($json)) {
			$json = array('status' => FALSE, 'message' => 'Invalid username or password');
		} else {
			$this->session->set_userdata($empInfo);
			$this->inventory_model->log($empInfo['recid'], 'Logged In');
			$json = array('status' => TRUE, 'message' => 'Login successful', 'url' => site_url('main'));
		}

		echo json_encode($json);
	}

	public function get_login_new()
	{
		if ($this->input->is_ajax_request() == FALSE) {
			die('Hacking is not allowed your IP is '.$this->input->ip_address());
		}

		$json = array();
		$username = $this->input->post('username');
		$password = $this->input->post('password');
		$empInfo = $this->users_model->check_login_new($username, $password, 1);

		if ( ! is_array($empInfo) || is_null($empInfo) || is_bool($empInfo)) {
			$json = array('status' => FALSE, 'message' => 'Invalid credentials or you don\'t have access to the system');
		} else {
			$this->session->set_userdata($empInfo);
			$this->inventory_model->log($this->session->recId, 'Logged in');
			$json = array('status' => TRUE, 'message' => 'Login successful', 'url' => site_url('main'));
		}

		echo json_encode($json);
	}

	public function getLogout()
	{
		if ($this->session->has_userdata('recId') == FALSE) {
			redirect('login');
		} else {
			$this->inventory_model->log($this->session->recId, 'Logged out');
			$this->session->sess_destroy();
			redirect('login');
		}
	}
}