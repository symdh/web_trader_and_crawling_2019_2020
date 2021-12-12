<?php defined('BASEPATH') OR exit('No direct script access allowed');


set_time_limit(100);   

return 0;



require("./static/include/xcoin_api_client.php");
// 화폐 : BTC, ETH, DASH, LTC, ETC, XRP, BCH, XMR, ZEC, QTUM, BTG, EOS (기본값: BTC), ALL
// $api_bhb = new XCoinAPI("secure", "secure");
	$str = 'EOS';
	$type = 'bid';
	$api_bhb =  new XCoinAPI("secure", "secure",$str, $type);

	$Odr_units = '0.1';
	$Odr_price = '10000';
	$Odr_result = $api_bhb->MTrade_exe_order($Odr_price, $Odr_units);
	if($Odr_result->status !== '0000') {
		// print_r($result);
		return 0;
	}
	$Odr_id = $Odr_result->order_id;
	$result_units = 0;
	foreach ($Odr_result->data as $key => $value) {
		$result_units += $value->units;
	}
	if($Odr_units == $result_units ) { 		
		echo "거래 완료";
		//log 기록
		return 1;
	}

	if($result_units == 0) {
		$weight = 1; //완료 안됬을시 가중치 분석
	}

	$sleep_time = 500000; //0.5초
	$count = 0; //취소 or 종료 요청
	while(1) { 
		$count++;
		if($count == 20)
			$weight = 0;
		usleep($sleep_time); //0.5초쉼 //가중치에 따라서 이걸 다르게 진행

		switch ($weight) { 
			case 0: //취소요청
				$cancel_result = $api_bhb->MTrade_Cancel($Odr_id);
				if($cancel_result->status === '0000') { //접속 에러 고려 필요없음.
					echo "취소 완료되었습니다. <br>";
					return 0;
				}
			break;

			case 1 :  //재확인 요청 (바로 채결을 원했을 경우)
				$Vrf_result = $api_bhb->MTrade_Result_Check($Odr_id);
				if($Vrf_result->status !== '0000') { //에러 발생시. (접속 에러시 1로 다시 요청)
					//바로 구매 안됬을 경우
					echo "구매 안됨 <br>";
					$weight = 2;
					break;
				}
				
				//'krw'은 기본
				$Vrf_units = 0;
				foreach ($Vrf_result->data as $key => $value) {
					$Vrf_units += $value->units_traded;
				}
	
				if($Odr_units == $Vrf_units ){
					echo "완료된 주문 <br>";
					return 1;
				} 
				
				echo "잔여수량 있음 <br>";
				$weight = 2;

			break;

			case 2 :  //재확인 요청 (대기 주문이였을 경우)
				$Vrf_result = $api_bhb->MTrade_Check_StandBy($Odr_id);
				if($Vrf_result->status === '0000') {
					echo "잔여수량 있음 <br>";
					$sleep_time = 2000000; //2초
				} else {
					echo "파악 안됨 <br>";
					$sleep_time = 500000; //0.5초
					$weight = 1;
				}
			break;

			case 10 : //종료 요청
				return 0;
			break;
		}
	}


?>