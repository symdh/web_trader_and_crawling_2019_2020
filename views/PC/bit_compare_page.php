<?php 

$dollarTokrw = 1197; // 달러 환율 

require("./static/include/CoinList.php");
require("./static/include/xcoin_api_client.php");
$api_bhb = new XCoinAPI("secure", "secure");


// 현재가 확인 (전체)
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

	foreach ($CoinList_flip as $key_1 => $value_1) {
		if($value_1 == "KRW") { // 원화는 패스해야됨 ㅇㅇ
			continue;
		}

		$rate = 'fluctate_rate_24H';

		// 가격정보 저장
		$bithumb_price[$value_1][0] = $result->data->$value_1->closing_price;

		$bithumb_price[$value_1][1] = round($result->data->$value_1->$rate,2);
		$bithumb_price[$value_1][2] = $key_1;

	}

}



$url = 'https://pro-api.coinmarketcap.com/v1/cryptocurrency/listings/latest';
$parameters = [
  'start' => '1',
  'limit' => '5000',
  'convert' => 'USD'
];

$headers = [
  'Accepts: application/json',
  'X-CMC_PRO_API_KEY:'
];
$qs = http_build_query($parameters); // query string encode the parameters
$request = "{$url}?{$qs}"; // create the request URL


$curl = curl_init(); // Get cURL resource
// Set cURL options
curl_setopt_array($curl, array(
  CURLOPT_URL => $request,            // set the request URL
  CURLOPT_HTTPHEADER => $headers,     // set the headers 
  CURLOPT_RETURNTRANSFER => 1         // ask for raw response instead of bool
));

$response = curl_exec($curl); // Send the request, save the response
$response = json_decode($response); // print json decoded response
//print_r($response);
curl_close($curl); // Close request



echo "<table><thead><tr><th>코인이름</th><th>빗썸시세</th><th>국제시세</th><th>시세차이</th><th>국제 1 상승률</th><th>국제 24 상승률</th><th>빗썸 24 상승률</th></thead><tbody>";


$CoinList_temp = $CoinList;
for ($i = 0; $i < count($response->data); $i++) {
	if(count($CoinList_temp) < 2) { // 다 찾은것 ㅇㅇ
		break;
	}
	foreach ($CoinList_temp as $key_1 => $value_1) {

		if($response->data[$i]->symbol == $key_1) { // 동일하면 찾은것

			$current_price = $response->data[$i]->quote->USD->price;
			$price = round($current_price*$dollarTokrw, 3);
			$differ_price = round( ($bithumb_price[$key_1][0]-$price)/$price*100, 2 );

			$percent_1 = round($response->data[$i]->quote->USD->percent_change_1h,2);
			$percent_24 = round($response->data[$i]->quote->USD->percent_change_24h,2);

			//echo $key_1.'의 가격 : ';
			// echo $price;
			// echo "원<br>";

			echo "<tr>
				<td>{$key_1} ({$bithumb_price[$key_1][2]})</td>
				<td>{$bithumb_price[$key_1][0]}</td>
				<td>{$price}</td>";

			if($differ_price > 10) {
				echo"
				<td style ='color:red;'>{$differ_price}%</td>
				";	

			} else if ($differ_price < 0) {
				echo"
				<td style ='color:blue;'>{$differ_price}%</td>
				";	

			} else {
				echo"
				<td>{$differ_price}%</td>
				
				";

			}

			if($percent_24 > 30) {
			echo "<td style ='text-align:center; color:red;'>{$percent_1}%</td>";
			echo "<td style ='text-align:center; color:red;'>{$percent_24}%</td>";
			echo "<td style ='text-align:center; color:red;'>{$bithumb_price[$key_1][1]}%</td>";

			} else if ($percent_24 > 15) {
			echo "<td style ='text-align:center; color:blue;'>{$percent_1}%</td>";
			echo "<td style ='text-align:center; color:blue;'>{$percent_24}%</td>";
			echo "<td style ='text-align:center; color:blue;'>{$bithumb_price[$key_1][1]}%</td>";

			} else {
				echo "<td style ='text-align:center;'>{$percent_1}%</td>";
				echo "<td style ='text-align:center;'>{$percent_24}%</td>";
				echo "<td style ='text-align:center;'>{$bithumb_price[$key_1][1]}%</td>";

			}

			
			






			echo "</tr>";

			unset($CoinList_temp[$key_1]);
			break;
		}
	}
}


echo "</tbody></table>";

return ;


