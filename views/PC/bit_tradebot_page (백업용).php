<?php 

$CI =& get_instance();
$CI->load->model('Bit_model');
// $result = $CI->Bit_model->save_log('test');
// var_dump($result);

// 비트맥스 값들 저장
$symbol = array(
	'XBT' => 'XBTUSD',
	'ADA' => 'ADAU19',
	'BCH' => 'BCHU19',
	'EOS' => 'EOSU19',
	'ETH' => 'ETHUSD',
	'LTC' => 'LTCU19',
	'TRX' => 'TRXU19',
	'XRP' => 'XRPU19'
);

function delZero($num) { // 123.0000 => 뒤에러에 .0제거
	while(1) {
		if(substr($num,-1,1) == '.') {
			$num = substr($num , 0, -1);
			break;
		} else if(substr($num,-1,1) == 0)
			$num = substr($num , 0, -1);
		else
			break;
	}

	return $num;
}

function checkGETdata($var, $min = '', $max = '') { // 변수 있는지 확인, 최소값, 최대값
	if(!isset($_GET[$var]) || $_GET[$var] == '' || ($min != '' && $_GET[$var] < $min) || ($max != '' && $_GET[$var] > $max) ) {
		return false;
	} else {
		return true;
	}
} // 이상있으면 false, 이상없으면 true

//에러제어 ㄱㄱ
	//bitmex.php 파일에도 추가해 줘야함 ㅇㅇ
	// 예시
		// while(1) { // 통신 및 에러 제어
		// 	// $result = $bitmex->get();
		// 	$errorCode = errorControl($result);
		// 	if(!$errorCode) {
		// 		break;
		// 	} else if ($errorCode == 1) {
		// 		sleep(2);
		// 	} else {
		// 		$CI->Bit_model->save_log($errorCode);
		// 		break; or return 0;
		// 	} 
		// }
function errorControl($result) { //반환된 값 그냥 넣으면 되도록 ㄱㄱ (error 없으면 0)
	if(!isset($result['error']['message'])) {
		// error 없으면 그냥 false ㄱㄱ
		return 0;
	}

	if(strpos($result['error']['message'], 'is currently overloaded') !== false) {
		return 1;
	}

	if(strpos($result['error']['message'], 'Account has insufficient Available Balance') !== false) {
		return 100;
	}

	return $result['error']['message'];
}

// 주문가능 소숫점 (나중에 반드시 자동화 필요 ㅇㅇ)
$DEP = array( // 일단 주문에만 사용할것 ㅇㅇ
	'XBT' => '0', //5단위만 되서 짜증나서 제거 ㅇㅇ 
	'ADA' => '8',
	'BCH' => '5',
	'EOS' => '7',
	'ETH' => '1', //5단위만 되서 짜증나서 제거 ㅇㅇ
	'LTC' => '6',
	'TRX' => '8',
	'XRP' => '8'
);

require("./static/include/BitMex.php");
$key = "secure";
$secret = "secure";
$bitmex = new BitMex($key,$secret);

//연결 테스트
	// $Ticker = $bitmex->getTicker();
	// print_r($Ticker);
	// return 0;
?>

<script src="https://code.jquery.com/jquery-1.12.4.min.js" integrity="sha256-ZosEbRLbNQzLpnKIkEdrPv7lOy9C27hHQ+Xp8a4MxAQ=" crossorigin="anonymous"></script>

<?php 

	// 구매진행
	if(checkGETdata('buycurrency') && !isset($_GET['isordering']) && !isset($_GET['immopen']))  {
		if(!checkGETdata('buyleverage', 1,10) || !checkGETdata('buyseed', 10,100)) {
			echo "<p>에러. 범위 에러</p>"; 
			return 0; // 일단은 즉시 중지해도됨
		}

		if($_GET['buymin'] > $_GET['buymax']) {
			echo "<p>에러. 최소금액은 항상 최대금액보다 작을것</p>";
			return 0; // 일단은 즉시 중지해도됨
		}


		// 기본 설정 (구매시 모든 잔고 취소)
		while(1) { // 통신 및 에러 제어
			$result = $bitmex->changeSYMBOL($symbol[$_GET['buycurrency']]);
			$errorCode = errorControl($result);
			if(!$errorCode) {
				break;
			} else if ($errorCode == 1) {
				sleep(2);
			} else {
				$CI->Bit_model->save_log($errorCode);
				return 0;
			} 
		}
		while(1) { // 통신 및 에러 제어
			$result = $bitmex->setLeverage($_GET['buyleverage']);
			$errorCode = errorControl($result);
			if(!$errorCode) {
				break;
			} else if ($errorCode == 1) {
				sleep(2);
			} else {
				$CI->Bit_model->save_log($errorCode);
				return 0;
			} 
		}
		while(1) { // 통신 및 에러 제어
			$result = $bitmex->cancelAllOpenOrders();
			$errorCode = errorControl($result);
			if(!$errorCode) {
				break;
			} else if ($errorCode == 1) {
				sleep(2);
			} else {
				$CI->Bit_model->save_log($errorCode);
				return 0;
			} 
		}
		while(1) { // 통신 및 에러 제어
			$Ticker = $bitmex->getTicker();
			$errorCode = errorControl($Ticker);
			if(!$errorCode) {
				if(!isset($Ticker['ask']) || $Ticker['ask'] == 0) {
					sleep(1);
					continue;
				} else {
					break;
				}
			} else if ($errorCode == 1) {
				sleep(2);
			} else {
				$CI->Bit_model->save_log($errorCode);
				return 0;
			} 
		}

		$ask = delZero(sprintf("%.10f", $Ticker['ask']));
		$bid = delZero(sprintf("%.10f", $Ticker['bid']));
		$own[$key]['ask'] = $ask;
		$own[$key]['bid'] = $bid;

		// 일단은 구매규칙떄문에 범위가 조금이라도 포함되면 에러
		if($_GET['buyposition'] == 'long') {
			if($_GET['buymax'] > $bid ) {
				echo "<p>에러</p>";
				return 0; // 일단은 즉시 중지해도됨
			}  
		} else if($_GET['buyposition'] == 'short') {
			if($_GET['buymin'] < $ask ) {
				echo "<p>에러</p>";
				return 0; // 일단은 즉시 중지해도됨
			} 
		} 

		// 중간값 정의 && 구매 수량 파악
		while(1) { // 통신 및 에러 제어
			$wallet = $bitmex->getMargin();
				// walletBalance = 현재 잔고 (미실현손익 뺌)
				// marginBalance = 총 잔고 (미실현손익 포함)
				// availableMargin = 이용 가능 잔고
			$errorCode = errorControl($wallet);
			if(!$errorCode) {
				break;
			} else if ($errorCode == 1) {
				sleep(2);
			} else {
				$CI->Bit_model->save_log($errorCode);
				return 0;
			} 
		}

		$have_cash = ($wallet['availableMargin']/100000000)*0.01*$_GET['buyseed'] ;

		if(isset($_GET['scalping']) && $_GET['scalping'] == 1) { // 스켈핑 전용 (설명은 아래와 같음)


			//30/0/25/15/30 (퍼센트지) = 4.5(결과)
			//10/8/4.5/2.5/0 (1~10까지 길이 비율로 중간값을 찾기 위함)		
			$ratio[0] = 0.30;
			$ratio[1] = 0.15;
			$ratio[2] = 0.25;
			$ratio[3] = 0.30;

			$surface_price = abs(($_GET['buymin']-$_GET['buymax'])/10);
			


			if($_GET['buyposition'] == 'long') {

				$open_price[0] = sprintf("%.10f", $_GET['buymin']+$surface_price*10); // max
				$open_price[1] = sprintf("%.10f", $_GET['buymin']+$surface_price*7.5); // 위에서 -2.5
				$open_price[2] = sprintf("%.10f", $_GET['buymin']+$surface_price*5.5); // 위에서 -4.5
				$open_price[3] = sprintf("%.10f", $_GET['buymin']);
			} else {

				$open_price[0] = sprintf("%.10f", $_GET['buymin']);
				$open_price[1] = sprintf("%.10f", $_GET['buymin']+$surface_price*2.5);
				$open_price[2] = sprintf("%.10f", $_GET['buymin']+$surface_price*4.5);
				$open_price[3] = sprintf("%.10f", $_GET['buymin']+$surface_price*10); // max 값
			}

			$avg_buy_price = ($open_price[0]*$ratio[0]+$open_price[1]*$ratio[1]+$open_price[2]*$ratio[2]+$open_price[3]*$ratio[3]);

		} else {

			// 설명추가 : //10/7.5/5/2.5/0 (1~10까지 길이 비율로 중간값을 찾기 위함)
				// 따라서 숏롱 그냥 반대로 되도 됨 ㅇㅇ
			// 구매 비율 (숏일땐 반대로 해야함)
			$ratio[0] = 0.40;
			$ratio[1] = 0.30;
			$ratio[2] = 0.09;
			$ratio[3] = 0.09;
			$ratio[4] = 0.12;
			 if($_GET['buyposition'] == 'short') {
			 	$ratio = array_reverse($ratio);
			 }
						
			// 구매 가격  계산
			$avg = ($_GET['buymin']+$_GET['buymax'])/2;
			$open_price[0] = sprintf("%.10f", $_GET['buymin']);
			$open_price[1] = sprintf("%.10f", ($_GET['buymin']+$avg)/2);
			$open_price[2] = sprintf("%.10f", $avg);
			$open_price[3] = sprintf("%.10f", ($avg+$_GET['buymax'])/2);
			$open_price[4] = sprintf("%.10f", $_GET['buymax']);
			$avg_buy_price = ($open_price[0]*$ratio[0]+$open_price[1]*$ratio[1]+$open_price[2]*$ratio[2]+$open_price[3]*$ratio[3]+$open_price[4]*$ratio[4]);
		}

		// 수량 계산
		if($_GET['buycurrency'] == 'XBT') {
			$fee = 1.00075 + $_GET['buyleverage']*0.0015; // 수수료
			$avg_buy_num = floor($have_cash*$avg_buy_price*$_GET['buyleverage']/$fee);
		} else if ($_GET['buycurrency'] == 'ETH') {
			$fee = 1.0025 + $_GET['buyleverage']*0.005; // 수수료
				// 알트코인 중 이더리움만 usd임 ㅡㅡ
			$avg_buy_num = floor($have_cash/$avg_buy_price*$_GET['buyleverage']*1000000/$fee);
		} else {
			
			$fee = 1.0025 + $_GET['buyleverage']*0.005; // 수수료
			$avg_buy_num = floor($have_cash/$avg_buy_price*$_GET['buyleverage']/$fee);
		
		}



		// echo $have_cash.'<br>';
		// echo $avg_buy_price;
		// echo "<p>".$avg_buy_num."개 구매가능</p>" ;
		// return 0;

		// 소숫점 길면 버리자.. 소숫점 떄문에 주문 안들어간다
		foreach ($open_price as $key => $value) {
			$temp = explode( '.', $value);
			// print_r($temp);
			if( strlen($temp[1]) > $DEP[$_GET['buycurrency']]) {
				while(1) {
					$temp[1] = substr($temp[1] , 0, -1);
					if( strlen($temp[1]) <= $DEP[$_GET['buycurrency']]) {
						break;
					} 
				}

				$open_price[$key] = $temp[0].'.'.$temp[1];
			}
		}

		if($_GET['buyposition'] == 'long') {
			foreach ($open_price as $key => $value) {
				if($value > $bid) {
					unset($open_price[$key]);
				}
			}
		} else if($_GET['buyposition'] == 'short') {
			foreach ($open_price as $key => $value) {
				if($value < $ask) {
					unset($open_price[$key]);
				}
			}
		}

		if(count($open_price) == 0) {
			echo "<p>에러. 구매 범위가 현재가를 초과함</p>";
			return 0; // 일단은 즉시 중지해도됨
		}

		// 구매 실행
		if($_GET['buyposition'] == 'long') {
			$act = 'Buy';
		} else if($_GET['buyposition'] == 'short') {
			$act = 'Sell';
		}

		$ratio_num = 0;
		foreach ($open_price as $key => $value) {
			// 구매 실패 에러 제어
			$open_num = floor($avg_buy_num*$ratio[$key]);

			while(1) { // 통신 및 에러 제어
				$result = $bitmex->createOrder( 'Limit', $act, $value, $open_num, true);
				$errorCode = errorControl($result);
				if(!$errorCode) {
					break;
				} else if ($errorCode == 1) {
					sleep(2);
				} else if ($errorCode == 100) {
					sleep(1);
				 	$open_num = floor($open_num*0.99);
				} else {

					$CI->Bit_model->save_log($errorCode);
					break;
				} 
			}

			sleep(1);
		}

		sleep(3);
		echo "<p>구매 요청 완료</p>";

		$uri= $_SERVER['REQUEST_URI'];

		echo "<script>location.href='".$uri."&isordering=1'</script>";

		return 0; // 이 아래는 실행할 필요없음ㅇㅇ


		// test 주의. 너무 작은 금액이면 scam 으로 분류된다네

		// xbt 구매테스트
		//  http://autocoining.com/bit/tradebot?buycurrency=XBT&buyposition=short&buyleverage=10&buyseed=100&buymin=11750&buymax=11780&target1=16000&target2=17000&target3=18000&target4=19000&target5=20000&stoploss=10000

		// trx 구매테스트
		//  http://autocoining.com/bit/tradebot?buycurrency=TRX&buyposition=short&buyleverage=10&buyseed=100&buymin=0.00000270&buymax=0.00000280&target1=0.00000250&target2=0.00000240&target3=0.00000230&target4=0.00000220&target5=0.00000210&stoploss=0.000003
	} 

	// 즉시 매수
	if(isset($_GET['immopen']) && $_GET['immopen'] == 1 && checkGETdata('buycurrency')  && checkGETdata('buyposition') && checkGETdata('buyseed', 10,100) ) {
		
		// 구매시 모든 잔고 취소
		while(1) { // 통신 및 에러 제어
			$result = $bitmex->changeSYMBOL($symbol[$_GET['buycurrency']]);
			$errorCode = errorControl($result);
			if(!$errorCode) {
				break;
			} else if ($errorCode == 1) {
				sleep(2);
			} else {
				$CI->Bit_model->save_log($errorCode);
				return 0;
			} 
		}
		while(1) { // 통신 및 에러 제어
			$result = $bitmex->setLeverage(10);
			$errorCode = errorControl($result);
			if(!$errorCode) {
				break;
			} else if ($errorCode == 1) {
				sleep(2);
			} else {
				$CI->Bit_model->save_log($errorCode);
				return 0;
			} 
		}
		while(1) { // 통신 및 에러 제어
			$result = $bitmex->cancelAllOpenOrders();
			$errorCode = errorControl($result);
			if(!$errorCode) {
				break;
			} else if ($errorCode == 1) {
				sleep(2);
			} else {
				$CI->Bit_model->save_log($errorCode);
				return 0;
			} 
		}
		while(1) { // 통신 및 에러 제어
			$Ticker = $bitmex->getTicker();
			$errorCode = errorControl($Ticker);
			if(!$errorCode) {
				if(!isset($Ticker['ask']) || $Ticker['ask'] == 0) {
					sleep(1);
					continue;
				} else {
					break;
				}
			} else if ($errorCode == 1) {
				sleep(2);
			} else {
				$CI->Bit_model->save_log($errorCode);
				return 0;
			} 
		}
 
		$ask = delZero(sprintf("%.10f", $Ticker['ask']));
		$bid = delZero(sprintf("%.10f", $Ticker['bid']));
		$own[$key]['ask'] = $ask;
		$own[$key]['bid'] = $bid;

		while(1) { // 통신 및 에러 제어
			$wallet = $bitmex->getMargin();
				// walletBalance = 현재 잔고 (미실현손익 뺌)
				// marginBalance = 총 잔고 (미실현손익 포함)
				// availableMargin = 이용 가능 잔고
			$errorCode = errorControl($wallet);
			if(!$errorCode) {
				break;
			} else if ($errorCode == 1) {
				sleep(2);
			} else {
				$CI->Bit_model->save_log($errorCode);
				return 0;
			} 
		}
		$have_cash = ($wallet['availableMargin']/100000000)*0.01*$_GET['buyseed'];

		if( $_GET['buyposition'] == 'long') {
			$own['position'] = 'short'; // 규칙상 어쩔수 없음
			$_buy_price = $bid;
		} else if( $_GET['buyposition'] == 'short') {
			$own['position'] = 'long'; // 규칙상 어쩔수 없음
			$_buy_price = $ask;
		}

		// 수량 계산
		if($_GET['buycurrency'] == 'XBT') {
			$fee = 1.00075 + 10*0.0015; // 수수료
			$_buy_num = floor($have_cash*$_buy_price*10/$fee);
		} else if ($_GET['buycurrency'] == 'ETH') {
			$fee = 1.0025 + 10*0.005; // 수수료
				// 알트코인 중 이더리움만 usd임 ㅡㅡ
			$_buy_num = floor($have_cash/$_buy_price*10*1000000/$fee);
		} else {
			$fee = 1.0025 + 10*0.005; // 수수료
			$_buy_num = floor($have_cash/$_buy_price*10/$fee);
		} 



		// $own 값 마주 셋팅
		$own['symbol'] = $symbol[$_GET['buycurrency']];
		$own['CurrentNum'] = 0; // 어차피 동작안함
		if($bitmex->immediatelyEXIT($own, 0, $_buy_num )) {
			$CI->Bit_model->save_log('즉시 구매 성공');
			echo "<script>location.href='/bit/tradebot'</script>";
			return 0;
		} else {
			$CI->Bit_model->save_log('즉시 구매 실패');
			echo "<script> location.reload(); </script>";
			return 0;
		}	
	}


?>




<?php 

	if(isset($_GET['currency']) || isset($_GET['buycurrency'])) {
		echo "<p id = 'state' style='font-size:40px; font-weight:blod;'> 10초후 새로고침 </p>";
	} else {
		echo "<p id = 'state' style='font-size:40px; font-weight:blod;'> 120초후 새로고침 </p>";
	}

?>

<script>
$(document).ready(function(){
	if($('#state').length > 0) {

<?php 
	if(isset($_GET['currency']) || isset($_GET['buycurrency'])) {
		echo "var time = 10;"; // 1분마다 새로고침
 	} else {
 		echo "var time = 120;"; // 1분마다 새로고침
 	}
?>


		window.setInterval(function(){
			time = time - 5;
		 	if(time == 5) {
		   		$('#state').html("<div style ='width:400px;'>5초후 새로 고침</div>");
		  		setTimeout(function() {
		  			location.reload();
				}, 5000);
			} else {
				$('#state').html("<div style ='width:400px;'>"+time+"초후 새로 고침</div>");
			}
			
		}, 5000);
	}
});

</script>


==========주문 도와주기 <br><br>


<?php

	// 구매 감시 (10초마다 reload())
	if(isset($_GET['isordering']) && $_GET['isordering'] == 1) {
		echo "<p> 구매감시중 </p>";

		$is_done = 0; 
		// 모두 구매 안되고 target1 달성시 취소하고 넘김
		while(1) { // 통신 및 에러 제어
			$result = $bitmex->changeSYMBOL($symbol[$_GET['buycurrency']]);
			$errorCode = errorControl($result);
			if(!$errorCode) {
				break;
			} else if ($errorCode == 1) {
				sleep(2);
			} else {
				$CI->Bit_model->save_log($errorCode);
				return 0;
			} 
		}
		while(1) { // 통신 및 에러 제어
			$Ticker = $bitmex->getTicker();
			$errorCode = errorControl($Ticker);
			if(!$errorCode) {
				if(!isset($Ticker['ask']) || $Ticker['ask'] == 0) {
					sleep(1);
					continue;
				} else {
					break;
				}
			} else if ($errorCode == 1) {
				sleep(2);
			} else {
				$CI->Bit_model->save_log($errorCode);
				return 0;
			} 
		}
		$ask = delZero(sprintf("%.10f", $Ticker['ask']));
		$bid = delZero(sprintf("%.10f", $Ticker['bid']));

		// 순간 올라가거나 내려갔다가 복귀되는거 캐치 못함 ㅇ
		if($_GET['buyposition'] == 'long') {
			if( $ask < $_GET['buymin']) {
				$is_done = 1;
			}

			if( $ask >= $_GET['target1'] ) { // touch 까지임 ㅇㅇ
				$is_done = 1;	
				while(1) { // 통신 및 에러 제어
					$result = $bitmex->cancelAllOpenOrders();
					$errorCode = errorControl($result);
					if(!$errorCode) {
						break;
					} else if ($errorCode == 1) {
						sleep(2);
					} else {
						$CI->Bit_model->save_log($errorCode);
						return 0;
					} 
				}
			}


		} else if($_GET['buyposition'] == 'short') {
			if( $bid > $_GET['buymax']) {
				$is_done = 1;
			}
			if( $bid <= $_GET['target1'] ) { // touch 까지임 ㅇㅇ
				$is_done = 1;	
				while(1) { // 통신 및 에러 제어
					$result = $bitmex->cancelAllOpenOrders();
					$errorCode = errorControl($result);
					if(!$errorCode) {
						break;
					} else if ($errorCode == 1) {
						sleep(2);
					} else {
						$CI->Bit_model->save_log($errorCode);
						return 0;
					} 
				}
			}

		}

		if(!isset($_GET['target1'])) {
			$_GET['target1'] = '';
		}

		if(!isset($_GET['target2'])) {
			$_GET['target2'] = '';
		}

		if(!isset($_GET['target3'])) {
			$_GET['target3'] = '';
		}

		if(!isset($_GET['target4'])) {
			$_GET['target4'] = '';
		}

		if(!isset($_GET['target5'])) {
			$_GET['target5'] = '';
		}

		if(!isset($_GET['stoploss'])) {
			$_GET['stoploss'] = '';
		}

		if($is_done) {

			// 오류 있어서 로그 남김 ㅇㅇ
			$test = 'ask는 '.$ask.'// bid는 '.$bid.'에서 open처리 완료';
			$CI->Bit_model->save_log($test);


			// 수정된 판매 비율
				// 정보가 여기에 다 남아서 여기서 진행
				// 롱기준 (target 1 - buymax)*1.5 <<< (buymin - stoploss) 시행
			if($_GET['buyposition'] == 'long') {
				if( ($_GET['target1'] - $_GET['buymax'])*1.5 < $_GET['buymin'] - $_GET['stoploss']) {
					$modifyclose = 1;
				}	else {
					$modifyclose = 0;
				}
			} else if($_GET['buyposition'] == 'short') {
				if( ($_GET['buymin'] - $_GET['target1'])*1.5 < $_GET['stoploss'] - $_GET['buymax']) {
					$modifyclose = 1;
				}	else {
					$modifyclose = 0;
				}
			}


			echo "<script>location.href='/bit/tradebot?currency=".$_GET['buycurrency']."&target1=".$_GET['target1']."&target2=".$_GET['target2']."&target3=".$_GET['target3']."&target4=".$_GET['target4']."&target5=".$_GET['target5']."&stoploss=".$_GET['stoploss']."&modifyclose=".$modifyclose."&targetsetting=1'</script>";
		}

		echo "<p> 구매 종목 : ".$_GET['buycurrency']."</p>";
		echo "<p> 포지션 :  ".$_GET['buyposition']."</p>";
		echo "<p> target1 : ".$_GET['target1']." </p>";
		echo "<p> stoploss : ".$_GET['stoploss']." </p>";

		if($_GET['buyposition'] == 'long') {
			if($_GET['stoploss'] > $_GET['target1']) {
				echo "<p style = 'color:red;'> stoploss 값 확인할것 </p>";
			}
		} else {
			if($_GET['stoploss'] < $_GET['target1']) {
				echo "<p style = 'color:red;'> stoploss 값 확인할것 </p>";
			}
		}


		return 0; // 아래 필요없음 ㅇ
	}
	
	

?>

<script>
function form_submit()
{
	/* confirm 함수는 확인창 결과값으로 TRUE 와 FALSE 값을 return 하게 된다*/
	var check_submit=confirm('진짜로 구매??? (레버 10배)');
	return check_submit;
}
</script>



<div> <!--  즉시구매 form -->  
	<form id = 'post_page' method='get' onSubmit="return form_submit();" action='/bit/tradebot'>
		<div style = 'height: 50px; float: left;'>
			<div style = ' padding-top:20px; '>
				즉시매수 
			</div>
		</div>
		<div style = 'height: 50px;'>
			<table>
				<thead>
					<th style="width: 100px;">종목</th>
					<th style="width: 100px;">포지션</th>
					<th style="width: 100px;">시드</th>
					<th style="width: 100px;">(레버10배) 즉시 OPEN</th>
				</thead>
				<tbody>
					<tr>
						<th>
							<select name = 'buycurrency' style="font-size: 20px; width: 80px;">
								<option value = ''></option>
							<?php 
								foreach ($symbol as $key => $value) {
									echo "<option value = '".$key."''>".$key."</option>";
								}
							?>
							</select>
						</th>

						<th>
							<select name = 'buyposition' style="font-size: 20px; width: 80px;">
								<option value = ''></option>
								<option value = 'long'>롱</option>
								<option value = 'short'>숏</option>							
							</select>
						</th>

					<th>
						<select name = 'buyseed' style="font-size: 20px; width: 80px;">
							<option value = ''></option>
							<option value = '10'>10%</option>
							<option value = '30'>30%</option>
							<option value = '50'>50%</option>
							<option value = '70'>70%</option>
							<option value = '90'>90%</option>
							<option value = '100'>100%</option>							
						</select>
					</th>

						<th>
							<input type = 'hidden'  name = 'immopen' value = 1 />
							<button type = 'submit'>즉시 OPEN 실행 (레버 10배) </bottom> 
						</th>

					</tr>
				</tbody>
			</table>

	</form>
</div>

<br><br>

<div> <!--  상세구매 form --> 
	<form id = 'post_page' method='get' action='/bit/tradebot'>
		<div style = 'height: 50px; float: left;'>
			<div style = ' padding-top:20px; '>
				기본설정 
			</div>
		</div>
		<div style = 'height: 50px;'>
			<table>
				<thead>
					<th style="width: 100px;">종목</th>
					<th style="width: 100px;">포지션</th>
					<th style="width: 100px;">레버리지</th>
					<th style="width: 100px;">시드</th>
					<th style="width: 250px;">스켈핑 전용 (중간값 0.45) </th>
				</thead>
				<tbody>
					<tr>
						<th>
							<select name = 'buycurrency' style="font-size: 20px; width: 80px;">
								<option value = ''></option>
						<?php 
								foreach ($symbol as $key => $value) {
									echo "<option value = '".$key."''>".$key."</option>";
								}
						?>
							</select>
					</th>
					<th>
						<select name = 'buyposition' style="font-size: 20px; width: 80px;">
							<option value = ''></option>
							<option value = 'long'>롱</option>
							<option value = 'short'>숏</option>							
						</select>
					</th>
					<th>
						<select name = 'buyleverage' style="font-size: 20px; width: 80px;">
							<option value = ''></option>
							<option value = '1'>1배</option>
							<option value = '2'>2배</option>
							<option value = '3'>3배</option>
							<option value = '4'>4배</option>
							<option value = '5'>5배</option>
							<option value = '6'>6배</option>
							<option value = '8'>8배</option>
							<option value = '10'>10배</option>							
						</select>
					</th>
					<th>
						<select name = 'buyseed' style="font-size: 20px; width: 80px;">
							<option value = ''></option>
							<option value = '10'>10%</option>
							<option value = '30'>30%</option>
							<option value = '50'>50%</option>
							<option value = '70'>70%</option>
							<option value = '90'>90%</option>
							<option value = '100'>100%</option>							
						</select>
					</th>
					<th>
						<input type="checkbox" name="scalping" value="1" style = "width: 25; height: 25;">
					</th>
				</tr>
			</tbody>
			</table>



		</div>

		<div style = 'height: 30px; float: left; padding-top:20px; '>
			구매범위&nbsp&nbsp
		</div>
		<div style = 'height: 30px; padding-top:20px; '>
			<input type='text' name = 'buymin' style = 'width:100px; height : 26px;'> ~ 
			<input type='text' name = 'buymax' style = 'width:100px; height : 26px;'>
			<b>(왼쪽 무조건 작은값, 오른쪽 무조건 큰값)</b>
		</div>

		<P>
				&nbsp&nbsp&nbsp&nbsp****범위가 현재가 이내로 들어가면 안됨 ==> 나중에 필요하면 수정 
		</P>

		<div style = 'height: 30px; float: left; padding-top:20px; '>
			TARGET&nbsp&nbsp&nbsp
		</div>
		<div style = 'height: 30px; padding-top:20px; '>
			<?php
				for($i = 1; $i < 6; $i++) {
					echo "<input type='text' name = 'target".$i."' style = 'width:100px; height : 26px;'> , "; 
				}
			?>
		</div>
		
		<div style = 'height: 30px; float: left; padding-top:20px; '>
			stoploss&nbsp&nbsp
		</div>
		<div style = 'height: 30px; padding-top:20px; '>
			<input type='text' name = 'stoploss' style = 'width:100px; height : 26px;'>
		</div>


		<div style="padding-left: 200px;">
			<input type='submit' value='입력''  style='width: 100px; height:40px;  font-size: 20px;''>
		</div>
	</form>
</div>


==========OPEN orders

<?php 

//기본 저장 (현재 열려있는 포지션 및 값 저장)
{
	while(1) { // 통신 및 에러 제어
		$order = $bitmex->getOpenPositions();

		// 아무래도 배열로 넘어와서 이런듯
		// 아래 에러메시지 없이 다시 리디렉트 뜬다면 이거 오류가 아닌것
		if(isset($order[0]['error']['message'])) {
			sleep(2);
			// 오류 확인되면, 그냥 foreach로 여기다가 규칙 넣어면 될듯 ㅇ
			$CI->Bit_model->save_log('여기 오류뜸 반드시 확인 ㄱㄱ');
			continue;
		}

		$errorCode = errorControl($order);
		if(!$errorCode) {
			break;
		} else if ($errorCode == 1) {
			sleep(2);
		} else {
			$CI->Bit_model->save_log($errorCode);
			return 0;
		} 
	}
	$own = array();
	foreach ($order as $key => $value) {
		if(!isset($value['underlying'])) { // 알수 없는 에러일때
			sleep(2);
			$CI->Bit_model->save_log('코인목록 못 받아옴 ㅇㅇ');
			echo "<p> 알수없는 에러 </p> <script> location.reload(); </script>";
			return 0;
		}

		// 종목 넣기
		$own[$value['underlying']]['currency'] = $value['underlying'];

		// 심볼 넣기
		$own[$value['underlying']]['symbol'] = $symbol[$value['underlying']];

		// 진입가
		$own[$value['underlying']]['EntryP'] = delZero(sprintf("%.10f", $value['avgEntryPrice']));

		//롱/숏 여부는 currentQty 수량 확인하면 됨
		if($value['currentQty']	< 0) {
			$own[$value['underlying']]['position'] = 'short';
		} else {
			$own[$value['underlying']]['position'] = 'long';
		}

		if( $own[$value['underlying']]['position'] == 'long') { // 롱하고 숏일때 틀림 ㅇㅇ
			// 총 구매량
			$own[$value['underlying']]['SumOpen'] =  $value['openingQty']+$value['execBuyQty'];

			// 판매량 
			$own[$value['underlying']]['SumClose'] =  $value['execSellQty'];

			//현재 수량
			$own[$value['underlying']]['CurrentNum'] =  $value['openingQty']+$value['execBuyQty']-$value['execSellQty'];
		} else {
			// 총 구매량
			$own[$value['underlying']]['SumOpen'] =  abs($value['openingQty'])+$value['execSellQty'];

			// 판매량 
			$own[$value['underlying']]['SumClose'] =  $value['execBuyQty'];

			//현재 수량
			$own[$value['underlying']]['CurrentNum'] =  abs($value['openingQty'])+$value['execSellQty']-$value['execBuyQty'];
		}

		if(abs($value['currentQty']) != $own[$value['underlying']]['CurrentNum']) {
			echo "<P>수량에러</P>";
		}

		// 손익
		$own[$value['underlying']]['profit'] = $value['unrealisedPnlPcnt']*1000;


		// 레버리지
		$own[$value['underlying']]['leverage'] = $value['leverage'];

		// 청산가
		$own[$value['underlying']]['DoneP'] = $value['liquidationPrice'];
	}

	foreach ($own as $key => $value) {
		while(1) { // 통신 및 에러 제어
			$result = $bitmex->changeSYMBOL($symbol[$value['currency']]);
			$errorCode = errorControl($result);
			if(!$errorCode) {
				break;
			} else if ($errorCode == 1) {
				sleep(2);
			} else {
				$CI->Bit_model->save_log($errorCode);
				return 0;
			} 
		}
		while(1) { // 통신 및 에러 제어
			$Ticker = $bitmex->getTicker();
			$errorCode = errorControl($Ticker);
			if(!$errorCode) {
				if( !isset($Ticker['ask']) || $Ticker['ask'] == 0 ) {
					sleep(1);
					continue;
				} else { // 성공시 넘어가게됨
					// 오류 메시지 찾기가 너무 귀찮고 어려워서 여기서 진행
					// 오류 메시지 확인후 다시 아래꺼 사용 ㄱㄱ
							// 뒤에 0 짤라내기 
					$ask = delZero(sprintf("%.10f", $Ticker['ask']));
					$bid = delZero(sprintf("%.10f", $Ticker['bid']));
					$own[$key]['ask'] = $ask;
					$own[$key]['bid'] = $bid;


						// $test =  http_build_query($Ticker,'',', ');
						// //echo $test2; // ~~~ = 값, 이런식으로 저장됨 ㅇㅇ
						// $CI->Bit_model->save_log($test);

					if(!isset($ask) || $ask < 1000) { // 오류 메시지 확인용 ㄱㄱ

						$test =  http_build_query($Ticker,'',', ');
						//echo $test2; // ~~~ = 값, 이런식으로 저장됨 ㅇㅇ
						$CI->Bit_model->save_log($test);

						sleep(1);
						continue; 
					}


					break;
				}
			} else if ($errorCode == 1) {
				sleep(2);
			} else {
				$CI->Bit_model->save_log("알수 없는 에러 발견됨. 에러코드 나와야함");
				$CI->Bit_model->save_log($errorCode);
				return 0;
			} 

			// $ask = delZero(sprintf("%.10f", $Ticker['ask']));
			// $bid = delZero(sprintf("%.10f", $Ticker['bid']));
			// $own[$key]['ask'] = $ask;
			// $own[$key]['bid'] = $bid;


		}


	}

}


// stoploss 규칙진행
{

	if(isset($_GET['currency']) && $_GET['currency'] != '' && isset($_GET['stoploss']) && $_GET['stoploss'] != '' && isset($own[$_GET['currency']]) ) {

		// stoploss 주문진행
		$is_stoploss = 0;
		if( $own[$_GET['currency']]['position'] =='long' ) {
			if( $own[$_GET['currency']]['bid'] <= $_GET['stoploss']) {
			// sell 주문
				$is_stoploss = 1;
			} 
		} else if ($own[$_GET['currency']]['position'] =='short') {
			if( $own[$_GET['currency']]['bid'] >= $_GET['stoploss']) {
				$is_stoploss = 1;
			}
		}

		// stoploss && 즉시종료
		if($is_stoploss){
			while(1) { // 통신 및 에러 제어
				$result = $bitmex->changeSYMBOL($symbol[$_GET['currency']]);
				$errorCode = errorControl($result);
				if(!$errorCode) {
					break;
				} else if ($errorCode == 1) {
					sleep(2);
				} else {
					$CI->Bit_model->save_log($errorCode);
					return 0;
				} 
			}

			$result = $bitmex->immediatelyEXIT($own[$_GET['currency']]);
			if($result) {
				$CI->Bit_model->save_log('stoploss로 Close 완료');
				echo "<script>location.href='/bit/tradebot'</script>";
				return 0;
			} else {
				$CI->Bit_model->save_log('에러. Close 실패');
				echo "<script> location.reload(); </script>";
				return 0;
			}
		}

		if( (isset($_GET['targetsetting']) && $_GET['targetsetting'] == 1) || !$is_stoploss ) { // 스탑로스 변화에도 진행

			// target 주문진행
				// 타겟 저장
			$target_temp[0] = $_GET['target1'];
			$target_temp[1] = $_GET['target2'];
			$target_temp[2] = $_GET['target3'];
			$target_temp[3] = $_GET['target4'];
			$target_temp[4] = $_GET['target5'];
			$i = 0;
			$is_error = 0;
			// 값 없는거는 그냥 빼고 진행 ㅇㅇ

			foreach ($target_temp as $key => $value) {
				if($value != '' && is_numeric($value) ) {

					// 검증 (stoploss 및 값)
						// 타겟은 구매 최소값보다 가치가 높아야함
					if( $own[$_GET['currency']]['position'] =='long' ) { 
						if( $own[$_GET['currency']]['EntryP'] > $value) {
							// 비정상
							$is_error = 1;
							break;
						}

						// 롱일경우 이전값보다 작아지면 에러
						if($i != 0) {

							if($target_temp[$i - 1] >=  $value) {
								$is_error = 1;
								break;
							}
						}

					} else if ( $own[$_GET['currency']]['position'] =='short') {
						if( $own[$_GET['currency']]['EntryP'] < $value) {
							// 비정상
							$is_error = 1;
							break;
						}

						// 숏일경우 이전값보다 커지면 에러
						if($i != 0) {
							if($target_temp[$i - 1] <=  $value) {
								$is_error = 1;
								break;
							}
						}

					} 

					$target[$i++] = $value;
				}
			}

			// 타겟 규칙
				// 롱을 예시로 |buymax-target| << |binmax-stoploss| 1.5배이상 차이나면
					// 확신을 못하는 픽이니 타겟을 조정함
					// 손실이 너무 큼, 일부로 수수료 때문에 이런픽 낼수도 있다는 생각.. 
			$need_redirect = 0;
			if( isset($_GET['targetsetting']) && $_GET['targetsetting'] == 1  ) { // target 등록 진행
				if($is_error || !isset($target)) {
					echo "<p style = 'font-size:20px; color: red;'>입력오류. target 잘못됨 </p>";
					$need_redirect = 1;
				} else {
					// 판매 비율 설정
					if(isset($ratio)) {
						unset($ratio);
					}

					if( isset($_GET['modifyclose']) && $_GET['modifyclose'] == 1  ) {
						if(count($target) == 5) {
							$ratio[0] = 0.40;
							$ratio[1] = 0.30;
							$ratio[2] = 0.05;
							$ratio[3] = 0.05;
							$ratio[4] = 0.20;
						} else if (count($target) == 4){
							$ratio[0] = 0.40;
							$ratio[1] = 0.30;
							$ratio[2] = 0.10;
							$ratio[3] = 0.20;
						} else if (count($target) == 3){
							$ratio[0] = 0.40;
							$ratio[1] = 0.40;
							$ratio[2] = 0.20;
						} else if (count($target) == 2){
							$ratio[0] = 0.60;
							$ratio[1] = 0.40;
						} else if (count($target) == 1){
							$ratio[0] = 1;
						}
					} else {
						if(count($target) == 5) {
							$ratio[0] = 0.20;
							$ratio[1] = 0.20;
							$ratio[2] = 0.10;
							$ratio[3] = 0.10;
							$ratio[4] = 0.40;
						} else if (count($target) == 4){
							$ratio[0] = 0.20;
							$ratio[1] = 0.25;
							$ratio[2] = 0.15;
							$ratio[3] = 0.40;
						} else if (count($target) == 3){
							$ratio[0] = 0.30;
							$ratio[1] = 0.30;
							$ratio[2] = 0.40;
						} else if (count($target) == 2){
							$ratio[0] = 0.60;
							$ratio[1] = 0.40;
						} else if (count($target) == 1){
							$ratio[0] = 1;
						}
					}


					// 판매 수량 계산 (이것도 규칙에 따라서)
						// 무조건 현재 가지고 있는 수량 기준으로 진행함 ㄱㄱ
					$target_num = 0;
					if($own[$_GET['currency']]['position']  == 'long') {
						$act = 'Sell';
						foreach ($target as $key => $value) {
							if( $own[$_GET['currency']]['ask'] > $value ) { 
								$target_num++;
							}
						}
					} else if($own[$_GET['currency']]['position']  == 'short') {
						$act = 'Buy';
						foreach ($target as $key => $value) {
							if( $own[$_GET['currency']]['bid'] < $value ) {
								$target_num++;
							}
						}
					}

				 	// 기존 주문 모두 취소
					while(1) { // 통신 및 에러 제어
						$result = $bitmex->cancelAllOpenOrders();
						$errorCode = errorControl($result);
						if(!$errorCode) {
							break;
						} else if ($errorCode == 1) {
							sleep(2);
						} else {
							$CI->Bit_model->save_log($errorCode);
							return 0;
						} 
					}
					while(1) { // 통신 및 에러 제어
						$result = $bitmex->changeSYMBOL($symbol[$_GET['currency']]);
						$errorCode = errorControl($result);
						if(!$errorCode) {
							break;
						} else if ($errorCode == 1) {
							sleep(2);
						} else {
							$CI->Bit_model->save_log($errorCode);
							return 0;
						} 
					}
					// 판매가 = 현재 즉시 추격 판매
						// 판매 수량
					if($target_num != 0) {
						if($target_num == count($target) ) {

							// 즉시 종료
							$result = $bitmex->immediatelyEXIT($own[$_GET['currency']]);
							if($result) {
								$CI->Bit_model->save_log('즉시 매도완료');
								echo "<p>즉시매도완료 </p>";
								echo "<script>location.href='/bit/tradebot'</script>";
							} else {
								$CI->Bit_model->save_log('즉시 매도실패');
								echo "<script> location.reload(); </script>";
							}

							// 즉시 page 종료해야됨 ㅇㅇ
							return 0;

						} else {
							if($target_num == 5) {
								$ratio_temp = $ratio[0]+$ratio[1]+$ratio[2]+$ratio[3]+$ratio[4];
							} else if($target_num == 4) {
								$ratio_temp = $ratio[0]+$ratio[1]+$ratio[2]+$ratio[3];
							} else if($target_num == 3) {
								$ratio_temp = $ratio[0]+$ratio[1]+$ratio[2];
							} else if($target_num == 2) {
								$ratio_temp = $ratio[0]+$ratio[1];
							} else if($target_num == 1) {
								$ratio_temp = $ratio[0];
							} 


							if(!$bitmex->immediatelyEXIT($own[$_GET['currency']],0, floor($own[$_GET['currency']]['CurrentNum']*$ratio_temp))) {
								sleep(1);
								// 에러 발생시 3회정도 진행하도록 수정할것 ㄱㄱ (아직까지 이거 실행된적 없음 ㅇㅇ)
								$CI->Bit_model->save_log('강제 close 실패');
								echo "주문 오류 <script> location.reload(); </script>";

								return 0;
							} else {
								$CI->Bit_model->save_log('Target 도달되어. 도달된만큼 즉시 close 성공');
								$_GET['arriveTarget'] = $ratio_temp; // target 바로 적용하면 됨 ㅇㅇ

								// 아래에 똑같은거 있음
								if($_GET['arriveTarget'] == 1) {
									$_GET['stoploss'] = $own[$_GET['currency']]['EntryP'] ;
								} else if ( $_GET['arriveTarget'] == 2) {
									$_GET['stoploss'] = delZero(sprintf("%.10f", ($own[$_GET['currency']]['EntryP'] + $target[0])/2 )); 
								} else if ( $_GET['arriveTarget'] == 3) {
									// $_GET['stoploss'] = $target[2];
									$_GET['stoploss'] =$target[0];
								} else if ( $_GET['arriveTarget'] == 4) {
									$_GET['stoploss'] = $target[1];
								} 
							}
						}
					}

					for($i = 0; $i < count($target); $i++) {
						if($i == count($target) - 1 ) { // 마지막일때
							//모든 수량 종료 (에러 있을수도 있음)
								// 아주 소량 남아 있을수 있으니 그거는 나중에 수정하려면 합시다 ㄱㄱ
							while(1) { // 통신 및 에러 제어
								$result = $bitmex->createOrder( 'Limit', $act, $target[$i], floor($own[$_GET['currency']]['CurrentNum']*$ratio[$i]), true);
								$errorCode = errorControl($result);
								if(!$errorCode) {
									sleep(1);
									break;
								} else if ($errorCode == 1) {
									sleep(2);
								} else {
									$CI->Bit_model->save_log($errorCode);
									return 0;
								} 
							}
						} else {
							if($target_num != 0) { // 이미 팔린 물량 만큼 pass
								$target_num--;
								continue;
							}
							while(1) { // 통신 및 에러 제어
								$result = $bitmex->createOrder( 'Limit', $act, $target[$i], floor($own[$_GET['currency']]['CurrentNum']*$ratio[$i]), true);
								$errorCode = errorControl($result);
								if(!$errorCode) {
									sleep(1);
									break;
								} else if ($errorCode == 1) {
									sleep(2);
								} else {
									$CI->Bit_model->save_log($errorCode);
									return 0;
								} 
							}
						}
					}

					echo "<p> 주문 등록 성공 ㄱㄱ</p>";
					$need_redirect = 1;
				}
			} else { // stoploss 규칙 진행


				if($is_error || !isset($target)) { 
					// 일단 아무것도 출력안함
				} else {
					
					if(!isset($_GET['arriveTarget'])) {
						$_GET['arriveTarget'] = 0;
					}

					$arriveTarget = 0;
					if( $own[$_GET['currency']]['position'] =='long' ) { 
						foreach ($target as $key => $value) {
							if( $own[$_GET['currency']]['ask'] > $value ) { 
								$arriveTarget  = $key + 1;
							}
						}

					} else {
						foreach ($target as $key => $value) {
							if( $own[$_GET['currency']]['bid'] < $value ) {
								$arriveTarget  = $key + 1;
							}
						}
					}

					if($arriveTarget > $_GET['arriveTarget']) {
						$_GET['arriveTarget'] = $arriveTarget;
						$need_redirect = 1;

						// 위에 똑같은거 있음
						if($_GET['arriveTarget'] == 1) {
							$_GET['stoploss'] = $own[$_GET['currency']]['EntryP'] ;
						} else if ( $_GET['arriveTarget'] == 2) {
							$_GET['stoploss'] = delZero(sprintf("%.10f", ($own[$_GET['currency']]['EntryP'] + $target[0])/2 )); 
						} else if ( $_GET['arriveTarget'] == 3) {
							// $_GET['stoploss'] = $target[2];
							$_GET['stoploss'] =$target[0];
						} else if ( $_GET['arriveTarget'] == 4) {
							$_GET['stoploss'] = $target[1];
						} 
					}

				}				
			}

			if($need_redirect) { 
				if(!isset($_GET['target1'])) {
					$_GET['target1'] = '';
				}

				if(!isset($_GET['target2'])) {
					$_GET['target2'] = '';
				}

				if(!isset($_GET['target3'])) {
					$_GET['target3'] = '';
				}

				if(!isset($_GET['target4'])) {
					$_GET['target4'] = '';
				}

				if(!isset($_GET['target5'])) {
					$_GET['target5'] = '';
				}

				if(!isset($_GET['arriveTarget'])) {
					$_GET['arriveTarget'] = 0;
				}

				echo "<script>location.href='/bit/tradebot?currency=".$_GET['currency']."&target1=".$_GET['target1']."&target2=".$_GET['target2']."&target3=".$_GET['target3']."&target4=".$_GET['target4']."&target5=".$_GET['target5']."&stoploss=".$_GET['stoploss']."&arriveTarget=".$_GET['arriveTarget']."'</script>";
			}

		}
	} else {
		// 이미 close 된 목록
		//	(1) 새로고침 방지 팔요
		// (2) get초기화 및 입력된 정보 초기화 필요 ㅇㅇ
		// (3) url로 진행하면 될듯

		// 구매중이 아니여야함 ㅇ
		if($_SERVER['REQUEST_URI'] != "/bit/tradebot" ) {
			sleep(2);
			$str = '';
			if(isset($_GET['currency'])) {
				$str = $str."currency는".$_GET['currency']." // ";
			}

			if(isset($_GET['arriveTarget'])) {
				$str = $str."arriveTarget은".$_GET['arriveTarget']." // ";
			}

			if(isset($_GET['stoploss'])) {
				$str = $str."stoploss는".$_GET['stoploss']." // ";
			}

			if(isset($own[$_GET['currency']]['ask'])) {
				$str = $str."이 코인의 현재가격은 ".$own[$_GET['currency']]['ask']." // ";
			} else {
				$str = $str."이 코인의 현재가격은 없음 // ";
			}

			$str = $str.'리디렉트 실행됨';


			$CI->Bit_model->save_log($str);
			echo "<script>location.href='/bit/tradebot'</script>";

		}


	}
}


// open 모록 출력
{
	echo "<table>
					<thead>
						<tr>
							<th>종목</th>
							<th>현재가(ask)</th>
							<th>이익</th>
							<th>진입가</th>
							<th>청산가</th>
							<th>레버리지</th>
							<th>CLOSE</th>
						</tr>
					</thead>
					<tbody>";


	foreach ($own as $key => $value) {
		echo "<tr>";

		echo "<th style = 'padding-top: 20px;'>".$value['currency']."</th>";

		echo "<th style = 'padding-top: 20px;'>".$value['ask']."</th>";

		if($value['profit'] >= 0) {
			echo "<th style = 'color:red; padding-top: 20px;'>".$value['profit']."%</th>";
		} else {
			echo "<th style = 'color:blue; padding-top: 20px;'>".$value['profit']."%</th>";
		}

		echo "<th style = 'padding-top: 20px;'> ".$value['EntryP']."</th>";
		echo "<th style = 'padding-top: 20px;'> ".$value['DoneP']."</th>";
		echo "<th style = 'padding-top: 20px;'> ".$value['leverage']."배</th>";


		if($value['position'] == 'long') {
			$temp = $value['ask']*100; 
		} else {
			$temp = $value['ask']/100;
		}

		echo "<th style = 'padding-top: 20px;'><a href = 'http://autocoining.com/bit/tradebot?currency=".$value['currency']."&stoploss=".$temp."'><button type ='submit'>즉시종료</button></th>";

		echo "</tr>";

	}
	echo "</tbody></table>";

}



?>


<br><br> <!-- open 된 목록 규칙설정 form-->
	<table><thead>
		<th>종목</th>
		<th>stoploss</th>
		<th>target1</th>
		<th>target2</th>
		<th>target3</th>
		<th>target4</th>
		<th>target5</th>
		</thead><tbody>

	<form id = 'get_page' method='get' action='/bit/tradebot'>
	<tr>
		<th>
		<select name = 'currency' style="font-size: 20px; width: 80px;">
								<option value = ''></option>
	<?php

	foreach ($symbol as $key => $value) {
		echo "<option value = '".$key."''>".$key."</option>";
	}
	?>
		</select>
		</th>
		<th>
			<input type='text' name = 'stoploss' style = 'width:100px; height : 26px;'>
		</th>

		<?php
		for($i = 1; $i < 6; $i++) {
			echo "<th><input type='text' name = 'target".$i."' style = 'width:100px; height : 26px;'> </td>"; 
		}
		?>


		</tr>
	<tbody><table>
		<input type='hidden' name = 'targetsetting' value="1">
		<input type='hidden' name = 'arriveTarget' value="0">

		<div style="padding-left: 200px;">
			<input type='submit' value='입력''  style='width: 100px; height:40px;  font-size: 20px;''>
		</div>

		

	</form>



<?php 

$result = $CI->Bit_model->load_log();
// $result = array_reverse($result);
      	echo "
      	<br>
      	<br>
      	==========로그 저장 내용 (9/2까지 수정 완료 ㅇㅇ)
		<table>
				<thead>
					<th>시간</th>
					<th>내용</th>
				</thead><tbody>
				";

			foreach ($result as $key => $value) {
					echo "<tr>";
					echo 		"<th>";
					echo 					$value['time'];
					echo 		"</th>";
					echo 		"<th>";
					echo 					$value['sentence'];
					echo 		"</th>";
					echo 	"</tr>";
			}

		echo "
				</tbody>
			</table>
				";



?>




<style>
	th{
		min-width: 70px;
	}
</style>

<script type="text/javascript">
	function getUrlVars() {
		var vars = {};
		var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi,    
		function(m,key,value) {
			vars[key] = value;
		});
		return vars;
	}
	var currency = getUrlVars()["currency"];
	var stoploss = getUrlVars()["stoploss"];
	var target1 = getUrlVars()["target1"];
	var target2 = getUrlVars()["target2"];
	var target3 = getUrlVars()["target3"];
	var target4 = getUrlVars()["target4"];
	var target5 = getUrlVars()["target5"];

	$("select[name=currency]").val(currency).attr('selected','selected');
	$("input[name=stoploss]").val(stoploss);
	$("input[name=target1]").val(target1);
	$("input[name=target2]").val(target2);
	$("input[name=target3]").val(target3);
	$("input[name=target4]").val(target4);
	$("input[name=target5]").val(target5);

</script>


