<?php defined('BASEPATH') OR exit('No direct script access allowed');

//반드시 DB 형식이 같아야함
class WA_sys  extends CI_model { //write analysis

	private $coin_term;
	private $cycle_num; //한번에 처리할 갯수 

	function __construct($cycle_num = 20){ 
		parent::__construct(); 
      	$this->db_ai  = $this->load->database('ai', TRUE);
      	date_default_timezone_set("Asia/Seoul");

      	//초기화
      	$result = $this->db_ai->query("SELECT * FROM coin_term ORDER BY priority " )->result_array();

		foreach ($result as $key => $value) {
			// $arr =explode('|',$value['attribute']); 
			// $this->coin_term["{$value['name']}"] = $arr;
			$this->coin_term["{$value['name']}"] = $value['attribute'];
		}

		$this->cycle_num = $cycle_num;
		// print_r($this->coin_term);
		
	}

	function call_coin_term () {
		return $this->coin_term;
	}


	//is_check = 1일 때 분석 필요없음
	//is_check = 0이고, is_new = 1일때 직접분석필요
	//is_check = 0이고, is_new = 0일때 기계분석필요

	//직접 분석을 위한 것	
	function load_contents($location) { 
		$content = $this->db_ai->query("SELECT * FROM {$location}_analysis WHERE is_check = 0 ORDER BY num LIMIT 30" )->result_array();
	
		if(count($content) > 0)
			return $content;
		else 
			return 0;	

		// print_r($content);
	}

	//기계 분석 시작
	function muchine_check ($location) {

		if(isset($_GET['check']) && $_GET['check'] == 1) {
			$content = $this->db_ai->query("SELECT * FROM {$location}_analysis WHERE is_check = 0 ORDER BY num LIMIT 30" )->result_array();
		} else {
			$content = $this->db_ai->query("SELECT * FROM {$location}_analysis WHERE is_check = 0 ORDER BY num LIMIT 200" )->result_array();
		}


		foreach ($content as $key => $value) {

			$is_check = 0;

			foreach ($this->coin_term as $key_2 => $value_2) {
				if(preg_match("/{$value_2}/i", $value['title']) ) {
					
				}

				$this->db_ai->query("UPDATE {$location}_analysis set is_check = 1 WHERE num = {$value['num']} ");
				$is_check = 1;
				break;
			}

		}

		redirect("/main/wacheck/{$location}");
	}

	function pass_check ($location, $num) {
		$this->db_ai->query("UPDATE {$location}_analysis set is_check = 1 WHERE num = {$num} ");

		redirect("/main/wacheck/{$location}");
	}


	//속성 추가
	function add_attribute($location)  {
		if($_GET['attribute']=='') {
			echo "에러 속성 값 없음";
			return 0;
		}

		$this->db_ai->query("UPDATE coin_term set attribute = concat(attribute,'|{$_GET['attribute']}') WHERE name = '{$_GET['name']}' ");


		redirect("/main/wacheck/{$location}");
	}


}


