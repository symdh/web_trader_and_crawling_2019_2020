<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Telegram_model extends CI_model {

	function __construct(){ 
		parent::__construct(); 
      	$this->db_telegram  = $this->load->database('telegram', TRUE);
      	date_default_timezone_set("Asia/Seoul");
	} 


	function load_list() {

		//역순출력
		if(isset($_GET['mode']) && $_GET['mode'] == 1)
			return array_reverse($result = $this->db_telegram->query("SELECT * FROM list WHERE id IS NOT NULL" )->result_array() );
		if(isset($_GET['mode']) && $_GET['mode'] == 2)
			return $result = $this->db_telegram->query("SELECT * FROM list WHERE coin IS NOT NULL" )->result_array();	
		if(isset($_GET['mode']) && $_GET['mode'] == 3)
			return $result = $this->db_telegram->query("SELECT * FROM list WHERE chat IS NOT NULL" )->result_array();	
		if(isset($_GET['mode']) && $_GET['mode'] == 5) {
			$query = $this->db_telegram->query("SELECT * FROM classify WHERE coin IS NOT NULL ORDER BY time DESC " )->result_array();
			foreach ($query as $key => $value) {
				if(!is_null($value['chat']) && !is_null($value['position'])) {
					$result[$key] = $query[$key];
				}
			}
			return $result;
		}
		if(isset($_GET['mode']) && $_GET['mode'] == 6)
			return $result = $this->db_telegram->query("SELECT * FROM classify WHERE chat IS NULL ORDER BY time DESC " )->result_array();	

		if(isset($_GET['mode']) && $_GET['mode'] == 7) {
			$query = $this->db_telegram->query("SELECT * FROM classify WHERE chat IS NOT NULL ORDER BY time DESC " )->result_array();
			foreach ($query as $key => $value) {
				if(is_null($value['coin'])) {
					$result[$key] = $query[$key];
				}
			}
			return $result;
		}

		if(isset($_GET['mode']) && $_GET['mode'] == 8) {
			$query = $this->db_telegram->query("SELECT * FROM classify WHERE chat IS NOT NULL ORDER BY time DESC " )->result_array();
			foreach ($query as $key => $value) {
				if(!is_null($value['coin']) && is_null($value['position'])) {
					$result[$key] = $query[$key];
				}
			}
			return $result;
		}

	}


	function add_list($id) { //1은성공 0은실패

		if(is_numeric($id)) {
			$result = $this->db_telegram->query("SELECT * FROM list WHERE id ='{$id}' " )->result_array();
			if(isset($result[0]['id']) ) {//이미 존재하면 0
				print_r(0);
				return 0;
			}
			$this->db_telegram->query("INSERT into list (`id`, `title`) values('{$id}', '{$_POST['title']}')");
			print_r(1);
		} else if (isset($_POST['coin'])) {	

			$this->db_telegram->query("INSERT into list (`coin`, `title`) values('{$_POST['coin']}', '{$_POST['title']}')");

			redirect('./telegram/list?mode=2');

		} else if (isset($_POST['chat'])) {	

			$this->db_telegram->query("INSERT into list (`chat`, `title`) values('{$_POST['chat']}', '{$_POST['chat']}')");

			redirect('./telegram/list?mode=3');
		}

	
		
	}


	function update_list($num) { //3은성공 0은실패

		if($_POST['coin'] == '') 
			return 0;

		$this->db_telegram->query("UPDATE list set coin = concat(coin, '|', '{$_POST['coin']}') WHERE num = '{$num}' ");  
		print_r(3);
	}


	function delete_list($id) { //2은성공 0은실패

		if(isset($_POST['num'])) {
			$this->db_telegram->query("DELETE from list WHERE num = '{$id}' ");
		} else {
			$this->db_telegram->query("DELETE from list WHERE id = '{$id}' ");
		}
		  
		print_r(2);
	}

	function add_classify() {
		echo "<script src=\"https://code.jquery.com/jquery-1.12.4.min.js\" integrity=\"sha256-ZosEbRLbNQzLpnKIkEdrPv7lOy9C27hHQ+Xp8a4MxAQ=\" crossorigin=\"anonymous\"></script> ";

		$load_id = $this->db_telegram->query("SELECT * FROM list WHERE id IS NOT NULL" )->result_array();
		$load_coin = $this->db_telegram->query("SELECT * FROM list WHERE coin IS NOT NULL" )->result_array();	
		$load_chat = $this->db_telegram->query("SELECT * FROM list WHERE chat IS NOT NULL" )->result_array();	
		$max_time = $this->db_telegram->query("SELECT MAX(time) FROM classify" )->result_array();

		//7일전꺼 삭제
		$current_time = strtotime('now') - 60*60*24*7;
		$this->db_telegram->query("DELETE from classify WHERE time < {$current_time}");


		if (!file_exists('madeline.php')) {
		    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
		}
		include 'madeline.php';
		$MadelineProto = new \danog\MadelineProto\API('session.madeline');
		$MadelineProto->start();
		//채팅목록 최소한번 로딩해야됨

		$is_buy_coin = 1;
		foreach ($load_id as $key1 => $value1) {
			$peer['_'] ="peerChannel";
			$peer['channel_id'] = $value1['id'];

			$messages_Messages = $MadelineProto->messages->getHistory(['peer' => $peer , 'offset_id' => 0, 'offset_date' => 0 , 'add_offset' => 0, 'limit' => 10, 'max_id' =>0 , 'min_id' => 0  ]);

			//print_r($messages_Messages); return 0;
			$pattern = "/[a-zA-Z가-힣0-9@#:\/, \.*\?\!\-\%]/"; //이상한 그림들 다 제거를 위함
			foreach ($messages_Messages['messages'] as $key2 => $value2) {

				//시간으로 저장안된 글인지 판단
				if( $value2['date'] <= $max_time[0]['MAX(time)'])
					continue;


				// 일단 삭제
				//$value2['message']=preg_replace('/\r\n|\r|\n/','&&&',$value2['message']);
				// $value2['message']=preg_replace('/[^A-Za-z0-9 @#:\/, \.*\?\!\-\%#_\-\+\&]/','',$value2['message']);
				// $value2['message']=preg_replace('/# /','',$value2['message']);

				//어떤 코인인지 검사
				$is_set_coin = 0;
				foreach ($load_coin as $key3 => $value3) {
					preg_match('/'.$value3['coin'].'/i', $value2['message'], $matches);
					if(isset($matches[0][0])) {
						$coin = $value3['title'];
						$is_set_coin = 1;
						break;
					}
				}

				//우선순위가 부여됨 
				preg_match('/ long/i', $value2['message'], $matches1);
				preg_match('/ short/i', $value2['message'], $matches2);
				preg_match('/ entry/i', $value2['message'], $matches3);
				preg_match('/ hit /i', $value2['message'], $matches4);
				if(isset($matches1[0][0])) {
					$position = "long";
				} else if(isset($matches2[0][0])) {
					$position = "short";
				} else if(isset($matches3[0][0])) {
					$position = "entry";
				} else if(isset($matches4[0][0])) {
					$position = "hit";
				} else {
					$position = "null";
				}

				//어떤 채널인지 조사
				$is_set_chat = 0;
				foreach ($load_chat as $key3 => $value3) {
					preg_match('/#'.$value3['chat'].'/i', $value2['message'], $matches);
					if(isset($matches[0][0])) {
						$chat = $value3['title'];
						$is_set_chat = 1;
						break;
					}
				}
				//분류 안될때는 미분류인지 검사
				if(!$is_set_chat) {
					preg_match('/#$/i', $value2['message'], $matches);
					if(isset($matches[0][0])) {
						$chat = "미분류";
						$is_set_chat = 1;
						break;
					}
				}

				// 따로 하는게 나을듯 ㄱㄱ (구매만 통합 ㄱ)			
				if($is_buy_coin) {
					if($is_set_coin == 1 && $coin == '비트코인') {
						if($is_set_chat == 1 && $chat == '델타') {
							preg_match('/Open SHORT|Open LONG/i', $value2['message'], $matches);
							if(isset($matches[0][0])) {
								// print_r($value2['message']); echo "<br>";
								
								// open position 설정
								preg_match('/SHORT|LONG/i', $value2['message'], $matches);
								$open['position'] = $matches[0];
								
								// open price 뽑아내기
								preg_match('/between \$?(\d{3,}\.?\d{1,2})\$? \- \$?(\d{3,}\.?\d{1,2})\$?/i', $value2['message'], $matches);
								$open['price_min'] = $matches[1];
								$open['price_max'] = $matches[2];
								// print_r($matches); echo "<br>";
								
								// seed
								preg_match('/with (\d{1,3})% of/i', $value2['message'], $matches);
								$open['seed'] = $matches[1]; 
									// 100% 기준 seed 이니 10배해줘야함 ㅇ
								if(is_numeric($open['seed'])) $open['seed'] = $open['seed']*10;
								//print_r($matches); echo "<br>";
								
								// leverage
									// cross = x10
								preg_match('/with X(\d{1,3}) leverage|with (cross) leverage/i', $value2['message'], $matches);
								if(isset($matches[2])) 
									$open['leverage'] = $matches[2];
								else 
									$open['leverage'] = $matches[1];
								//print_r($matches); echo "<br>";
								if($open['leverage'] == 'cross') { // 비맥 시드 10%이므로 교차이면 10배
									$open['leverage'] = 10;
								}	
								// print_r($matches); echo "<br>";
								
								// target (최초 5개만 우선 ㄱ)
								preg_match_all('/price \$(\d{3,}\.?\d{1,2})/i', $value2['message'], $matches);
								$open['target'] = $matches[1];
								// print_r($matches); echo "<br>";
								
								// stoploss
								preg_match('/STOP LOSS: \$?(\d{3,})/i', $value2['message'], $matches);
								$open['stop_loss'] = $matches[1];
								// print_r($matches); echo "<br>";

								//print_r($open); echo "<br>";
							

								// 1개만 뽑아냄 ㅇㅇ
								$is_buy_coin = 0;
								foreach ($open as $key => $value) {
									if(!is_array($value) && $value == '') {
										$is_buy_coin = 1;
									}
								}

								// 전송 (새창으로 열기 ㄱㄱ)
								if(!$is_buy_coin) { // 정상적인 open 일 때

									// echo "<script>window.open('http://autocoining.com/bit/tradebot?test', '_blank')</script>"; 

// http://autocoining.com/bit/tradebot?buycurrency=XBTUSD&buyposition=long&buyleverage=4&buyseed=70&buymin=10000&buymax=11000&target1=12000&target2=13000&target3=14000&target4=15000&target5=16000&stoploss=9000&state=2$from=telegram

									// 받는부분에서 (테스트 못해서 안만들었음)
										// 이미 open 상태면 무시 ㄱㄱ
										// 완료 후 창 닫기 ㄱㄱ
								}
							}
						}
					}
				// 위에 예외조건 만들것 ㄱㄱ (오류메시지 저장하게 만들어야함) - 일단 보류
				}




				// $value2['message'];
				// preg_match('/#$/i', $value2['message'], $matches);



				if($is_set_coin && $position != "null" && $is_set_chat) {
					$title =  $this->db->escape($value1['title']);
					$content =  $this->db->escape($value2['message']);
					$this->db_telegram->query("INSERT into classify (`id`, `coin`, `chat`, `title`, `time`, `position`, `content`) values('{$value1['id']}', '{$coin}',  '{$chat}',  {$title},  '{$value2['date']}',  '{$position}',  {$content} )");

				} else { //모두다 null 일경우 어차피 만들어줘야함
					$title =  $this->db->escape($value1['title']);
					$content =  $this->db->escape($value2['message']);
					$query_into = "INSERT into classify (`id`,`title`,`time`,`content`";
					$query_values = "values('{$value1['id']}', {$title},  '{$value2['date']}', {$content} ";

					if($is_set_chat) {
						$query_into = $query_into.", `chat`";
						$query_values = $query_values.", '{$chat}' ";
					}

					if($is_set_coin)  {
						$query_into = $query_into.", `coin`";
						$query_values = $query_values.", '{$coin}' ";
					}

					if($position != "null")  {
						$query_into = $query_into.", `position`";
						$query_values = $query_values.", '{$position}' ";
					}
					$query_into = $query_into.")";
					$query_values = $query_values.")";
					$this->db_telegram->query($query_into.' '.$query_values);
				}



				//출력을 위해 몇분전인지 구함
				$current_time = strtotime('now');
				$past_time = $current_time - $value2['date'];
				$time = (($past_time/60/60/24)%7)."일 ".(($past_time/60/60)%24)."시간".(($past_time/60)%60)."분".($past_time%60)."초 전";

				echo "<div style = 'width:120px; height:30px; float:left'>".substr($value1['title'],0,15)."</div>";
				if($is_set_chat) {			
					echo "<div style = 'width:120px; height:30px; float:left'>#".$chat."</div>";
				} else {
					echo "<div style = 'width:120px; height:30px; float:left'>null</div>";
				} 
				if($is_set_coin) {			
					echo "<div style = 'width:120px; height:30px; float:left'>".$coin."</div>";
				} else {
					echo "<div style = 'width:120px; height:30px; float:left'>null</div>";
				} 

				echo "<div style = 'width:80px; height:30px; float:left'>".$position."</div>";
				echo "<div style = 'width:200px;  height:30px; float:left'>".$time."</div>";
				echo "<div style = 'width:1000px; float:left;  '>".$value2['message']."</div>";
				echo "<div style = 'clear:both;'> </div>";
			}
		}

		$current_time = strtotime('now');
			$current_time = "마지막 새로고침: ".date('H시 i분');
			echo "<div style = 'color:red; font-size:20px'>{$current_time}</div>";
			echo "<div id = 'state' style = 'color:red; font-size:20px'>180초후 새로고침</div>";

		echo "<script>
					$(document).ready(function(){
							var time = 180;
							window.setInterval(function(){
								time = time - 5;
							 	if(time == 5) {
							   		$('#state').html(\"<div style ='width:300px;height:100px;'>5초후 새로 고침</div>\");
							  		setTimeout(function() {
							  			location.reload();
									}, 5000);
								} else {
									$('#state').html(\"<div style ='width:300px;height:100px;'>\"+time+\"초후 새로 고침</div>\");
								}
							}, 5000);
					});

					</script> ";

	}


	// function save_log($log_group = 11) {
	// 	$time = strtotime(date("Y-m-d H:i:s",time()));
	// 	$this->db_ai->query("INSERT into log (`log_group`, `content`, `time`) values({$log_group}, '{$content}', {$time})"); //에러 무시
	// }

	// function load_log($log_group = 0) {


	// 	return $result;
	// }

	// function del_log($id) {
	// 	$this->db_ai->query("DELETE from log WHERE id = {$id} ");  

	// 	redirect('/main/log');
	// }
}