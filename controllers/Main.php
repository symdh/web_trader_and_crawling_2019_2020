<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Main extends CI_Controller {

	function __construct() {       
      parent::__construct();
      date_default_timezone_set("Asia/Seoul");
      $this->load->library('session');
    }

        //index.php/main/index 
	public function index() {
		$this->config->set_item('title','자동거래시스템');
		$this->load->view('PC/main_page');
	}

	//index.php/main/result 
	public function result() {
		$this->config->set_item('title','결과 페이지');
		$this->load->view('PC/result_page');
	}

	public function wanalysis () { //write analysis
		$this->config->set_item('title','글 분석');
		$this->load->view('PC/wanalysis_page');
	}

	public function dcsearch () { //디시 검색
		$this->config->set_item('title','디시 검색');
		$this->load->view('PC/dcsearch_page.php' );
	}

	public function bobserve () { //빗썸 공지 감시
		$this->config->set_item('title','빗썸 공지 감시');
		$this->load->view('PC/bobserve_page.php' );

	}


	public function wacheck($location) {
		if(!isset($location)) {echo "위치 지정 해라"; return 0;}

		$this->load->model('WA_sys');
		$coin_term = $this->WA_sys->call_coin_term();
		$F_content = $this->WA_sys->load_contents($location); //fail content


		$this->config->set_item('title','글 분석');
		$this->load->view('PC/wacheck_page', array("coin_term"=> $coin_term, "F_content"=> $F_content, "location" => $location) );
	}

	public function startcheck($location) {
		if(!isset($location)) {echo "위치 지정 해라"; return 0;}

		$this->load->model('WA_sys');
		$this->WA_sys->muchine_check($location);
	}

	public function passcheck($location, $num) {
		if(!isset($location)) {echo "위치 지정 해라"; return 0;}

		$this->load->model('WA_sys');
		$this->WA_sys->pass_check($location, $num);

	}

	public function addattribute($location) {
		if(!isset($location)) {echo "위치 지정 해라"; return 0;}

		$this->load->model('WA_sys');
		$this->WA_sys->add_attribute($location);
	}

}