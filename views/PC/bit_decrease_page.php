<?php 

/* 하락장 퍼센트 확인*/


//			https://apidocs.bithumb.com

require("./static/include/CoinList.php");
require("./static/include/xcoin_api_client.php");
$api_bhb = new XCoinAPI("secure", "secure");



// 가격정보 모두 받아오기
//$api_bhb->change_currency('ALL');




echo "<table><thead><tr><th>코인이름</th><th>24최고가</th><th>24평균가</th><th>현재가</th><th>최고가 대비</th><th>평균가 대비</th></thead><tbody>";



// 하락장 하락비율 (값비교)
{
	$i = 0;
	while (1) {
		$api_bhb->change_currency('ALL');
		$result = $api_bhb->CoinStatic();

		if($result->status === '0000')  {
			// print_r($result);
			break;
		} else {
			if($i++ == 10) { //10초동안 안되면 정지
				print_r($result);
				return 0;
			} 

			usleep(1000000); //1초쉼
		}
	}
	//print_r($result);

	foreach ($CoinList_flip as $key_1 => $value_1) {
		if($value_1 == "KRW") { // 원화는 패스해야됨 ㅇㅇ
			continue;
		}

		$hight_24_price = $result->data->$value_1->max_price;
		$current_price = $result->data->$value_1->closing_price;
		$avrg_price = $result->data->$value_1->average_price;
		$differ_percent = round(($current_price-$hight_24_price)/$hight_24_price*100, 1);
		$differ_percent_avrg =  round(($current_price-$avrg_price)/$avrg_price*100, 1);

		if($value_1 == "BTC") {
			$BTC_percent = $differ_percent;
		}


		echo "<tr>
				<td>{$value_1}({$key_1})</td>
				<td>{$hight_24_price}</td>
				<td>{$avrg_price}</td>
				<td>{$current_price}</td>";

			if($differ_percent < $BTC_percent*1.5) {
				echo"
				<td style ='color:red;'>{$differ_percent}%</td>
				<td>{$differ_percent_avrg}%</td>
				</tr>
				";	

			} else if ($differ_percent < $BTC_percent*1) {
				echo"
				<td style ='color:blue;'>{$differ_percent}%</td>
				<td>{$differ_percent_avrg}%</td>
				</tr>
				";	

			} else {
				echo"
				<td>{$differ_percent}%</td>
				<td>{$differ_percent_avrg}%</td>
				</tr>
				";

			}

	}
		echo "<br>";

}




?>