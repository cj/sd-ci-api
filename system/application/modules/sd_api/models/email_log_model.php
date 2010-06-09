<?php

class Email_Log_Model extends MY_Model
{
	
	function __construct()
	{
		// Call the Model constructor
		parent::__construct();
		$this->table = 'email_log';
		$this->database = DB_SD_API;
		$this->db = $this->CI->load->database(DB_SD_API, TRUE);
		$this->set_fields();
	}
}
?>