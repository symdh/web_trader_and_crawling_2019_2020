<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Wdata extends CI_Controller {

   function __construct() {       
      parent::__construct();
      date_default_timezone_set("Asia/Seoul");
      $this->load->library('session');
   }

   function save() {
      $location = $_POST['location'];
      unset($_POST['location']);

      $this->load->model('Wdata_model');
      
      //잡내용 없는 순수한 post 필요
      $this->Wdata_model->save_wdata($location, $_POST); 
   }

}
