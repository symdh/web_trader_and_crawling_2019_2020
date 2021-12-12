<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Bit extends CI_Controller {

	function __construct() {       
      parent::__construct();
      date_default_timezone_set("Asia/Seoul");
      $this->load->library('session');
    }

    public function main(){//$keyword) {
    	//$keyword = "콜레스테롤";
      
    	//if(is_null($keyword)) {
    	//	echo "1차 키워드 입력해라 : http://autocoining.com/blog/keyword/1차키워드";
    	//	return 0;
    	//}


    	//$this->load->view('PC/blog_keyword',array("keyword"=>$keyword));


      $this->load->view('PC/bit_main_page');

    }


      public function auto(){//$keyword) {
        $this->load->model('WA_sys');
        $this->WA_sys->add_attribute($location);

      }

       public function compare(){
        //해외랑 시세 비교 ㄱㄱ

        $this->load->model('WA_sys');
        $this->load->view('PC/bit_compare_page');

      }

      public function decrease(){
        //하락장 하락 퍼센트 공유

        $this->load->view('PC/bit_decrease_page');

      }

      public function tradebot(){
        //하락장 하락 퍼센트 공유

        $this->load->view('PC/bit_tradebot_page');

      }

      public function inputmacro(){
         $this->load->model('Bit_model');
          $this->Bit_model->input_macro();
      }


}