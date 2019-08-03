<?php
/**
* 
*/
class Penlogs_model extends CI_Model
{
	
	function __construct()
	{
		parent::__construct();
		
	}

	function log($id, $ip, $desc)
	{
		$db = $this->load->database('dr_checking', TRUE);

		$data = array(
			'userId' => $id,
			'ip' => $ip,
			'description' => $desc
		);

		$db->insert('penlogs', $data);
	}
}