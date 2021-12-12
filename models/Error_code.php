<?php defined('BASEPATH') OR exit('No direct script access allowed');


class Error_code extends CI_model { //post로 넘어오는 값 체크

// [status] => 5600 [message] => Please try again    (지연시간 걸렸을때)
// [status] => 5500 [message] => Invalid Parameter  (파라매터 잘못됬을시)
// [status] => 5600 [message] => 최소 구매수량은 0.1 EOS 입니다. 
// [status] => 5600 [message] => 거래 진행중인 내역이 존재하지 않습니다.
// [status] => 5600 [message] => 거래 체결내역이 존재하지 않습니다.
}


