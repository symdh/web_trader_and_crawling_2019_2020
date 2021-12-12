<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Telegram extends CI_Controller {

	function __construct() {       
      parent::__construct();
      date_default_timezone_set("Asia/Seoul");
      $this->load->library('session');
    }

    public function list() {

		$this->config->set_item('title','텔레그램 불러오기');
		$this->load->model('Telegram_model');		
		$load_list = $this->Telegram_model->load_list();
		$this->load->view('PC/telegram_page', array("load_list"=>$load_list));
	}

	public function add($list) {
		$this->config->set_item('title','텔레그램 불러오기');
		$this->load->model('Telegram_model');		
		$this->Telegram_model->add_list($id = $list);
	}

	public function update($list) {
		$this->config->set_item('title','텔레그램 불러오기');
		$this->load->model('Telegram_model');		
		$this->Telegram_model->update_list($num = $list);
	}

	public function delete($list) {
		$this->config->set_item('title','텔레그램 불러오기');
		$this->load->model('Telegram_model');		
		$this->Telegram_model->delete_list($id = $list);
	}

	public function classify() {
		$this->config->set_item('title','텔레그램 불러오기');
		$this->load->model('Telegram_model');		
		$this->Telegram_model->add_classify();
	}



}