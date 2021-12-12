<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Bit_model extends CI_model {

	function __construct(){ 
		parent::__construct(); 
		// 추가해야됨 나중에 ㅇㅇ
      	$this->db_ai  = $this->load->database('ai', TRUE);
      	date_default_timezone_set("Asia/Seoul");
	} 

	function input_macro() {

		echo "정상작동";
		echo "<script>location.href='/bit/tradebot'</script>";

	}




	function save_coin() {
		require("./static/include/CoinList.php");
		require("./static/include/xcoin_api_client.php");


		$api = new XCoinAPI("secure", "secure");

		// $time = strtotime(date("Y-m-d H:i:s",time()));
		// $this->db_ai->query("INSERT into log (`log_group`, `content`, `time`) values({$log_group}, '{$content}', {$time})"); //에러 무시
	}

	function load_coin($log_group = 0) {

/*
		if(!$log_group) { //0이면 모두 불러오기 ,역순으로 불러오기
			$result = $this->db_ai->query("SELECT * FROM log ORDER BY id" )->result_array();
		}

		return $result;
*/

	}

	function load_log() {
      	$result = $this->db_ai->query("SELECT * FROM bit.log order by num desc LIMIT 10" )->result_array();
      	return $result;
	}
	function save_log($sentence) {
		$time = date("Y-m-d H:i:s",time());
		// 일단 에러는 없음으로
		$this->db_ai->query("INSERT into bit.log (`sentence`, `time`, `error`) values('{$sentence}', '{$time}', 0)"); //에러 무시
/*
		$this->db_ai->query("DELETE from log WHERE id = {$id} ");  

		redirect('/main/log');
*/
	}


	function del_log($id) {
/*
		$this->db_ai->query("DELETE from log WHERE id = {$id} ");  

		redirect('/main/log');
*/
	}
}