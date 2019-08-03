<?php

class Users_model extends CI_Model {

	public function __construct() {
		parent::__construct();
		$this->load->database();
	}

	public function check_login($username, $password) {

		$query = $this->db->get_where('users', array('username' => $username, 'password' => sha1($password)));
		// die($this->db->last_query());
		return $query->row_array();
	}

	public function check_login_new($username, $password, $systemId)
	{
		$db = $this->load->database('amwire_security', TRUE);
		
		$db->select('user.userId AS recId, user.username, user.password, empinfo.lastName, empinfo.firstName, empinfo.middleName, empinfo.is_admin, empinfo.is_supervisor');
		$db->where(array('username' => $username, 'systemId' => $systemId));
		$db->join('empinfo', 'empinfo.userId=user.userId', 'left');
		$db->join('system_user', 'system_user.userId=empinfo.userId');
		$query = $db->get('user');
		
		if (is_null($query->row()) == FALSE) {
			if (password_verify($password, $query->row()->password)) {
				return $query->row_array();
			} else {
				return FALSE;
			}
		}

		return FALSE;		
	}
}