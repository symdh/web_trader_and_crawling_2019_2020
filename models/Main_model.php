<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Main_model extends CI_model {

	function __construct(){ 
		parent::__construct(); 
      	$this->db_ai  = $this->load->database('ai', TRUE);
      	date_default_timezone_set("Asia/Seoul");
	} 

	function save_log($log_group = 11) {
		$time = strtotime(date("Y-m-d H:i:s",time()));
		$this->db_ai->query("INSERT into log (`log_group`, `content`, `time`) values({$log_group}, '{$content}', {$time})"); //에러 무시
	}

	function load_log($log_group = 0) {

		if(!$log_group) { //0이면 모두 불러오기 ,역순으로 불러오기
			$result = $this->db_ai->query("SELECT * FROM log ORDER BY id" )->result_array();
		}

		return $result;
	}

	function del_log($id) {
		$this->db_ai->query("DELETE from log WHERE id = {$id} ");  

		redirect('/main/log');
	}
}