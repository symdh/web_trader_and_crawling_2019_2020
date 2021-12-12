<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Wdata_model extends CI_model {

	function __construct(){ 
		parent::__construct(); 
      	$this->db_ai  = $this->load->database('ai', TRUE);
      	date_default_timezone_set("Asia/Seoul");
	} 

	function save_wdata($location, $data) {
		foreach ($data as $key => $value) {
			$date = substr($value, 0,10);
			$value = substr($value, 10,strlen($value));
			$value = addslashes($value);
			$this->db_ai->query("INSERT IGNORE into {$location} (`num`,`date`, `title`) values({$key}, {$date},'{$value}')"); //에러 무시
		}

		echo "db 저장 완료";
	}
}