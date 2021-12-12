<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Blog extends CI_Controller {

	function __construct() {       
      parent::__construct();
      date_default_timezone_set("Asia/Seoul");
      $this->load->library('session');
    }

    public function keyword($keyword) {
    	//$keyword = "콜레스테롤";
      
    	if(is_null($keyword)) {
    		echo "1차 키워드 입력해라 : http://autocoining.com/blog/keyword/1차키워드";
    		return 0;
    	}


    	$this->load->view('PC/blog_keyword',array("keyword"=>$keyword));

    }

    public function research($blogname) {
      //$keyword = "콜레스테롤";
      
      if(is_null($blogname)) {
        echo "blog 이름 입력해라";
        return 0;
      }


      $this->load->view('PC/blog_research',array("blogname"=>$blogname));

    }


}