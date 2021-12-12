<script src="https://code.jquery.com/jquery-1.12.4.min.js" integrity="sha256-ZosEbRLbNQzLpnKIkEdrPv7lOy9C27hHQ+Xp8a4MxAQ=" crossorigin="anonymous"></script> 

<style>

a {
	text-decoration: none;
	color : black;
	font-weight: bold;

}

</style>



<?php


//			https://apidocs.bithumb.com

require("./static/include/CoinList.php");
require("./static/include/xcoin_api_client.php");
$api_bhb = new XCoinAPI("secure", "secure");


$params['count'] = "30";

// $order_book = $api_bhb->xcoinApiCall("/public/orderbook/ins",$params); // test
// foreach ($order_book->data->bids as $key => $value) {
// 	print_r($value);
// 	echo "<br>";
// }
// return 0;
foreach ($CoinList as $key => $value) {
	if($key == "KRW")
		continue;

	$order_book = $api_bhb->xcoinApiCall("/public/orderbook/".$key,$params);
	if(!isset($order_book->data->bids[29]->price)) {
		continue;
	}

	$sell_1 = $order_book->data->asks[5]->price; // 떨어짐에따라 %가 왜곡되는것을 방지

	// 구매 주문 10개까지 합계
	$result_10 = 0;
	$buy_10 = $order_book->data->bids[9]->price;
	for($i = 0; $i < 10; $i++) {
		$result_10 += $order_book->data->bids[$i]->price*$order_book->data->bids[$i]->quantity;
	}

	if($result_10 > 8000000 || round($buy_10/$sell_1*100) > 98) 
		continue;

	// 구매 주문 20개까지 합계
	$result_20 = $result_10;
	$buy_20 = $order_book->data->bids[19]->price;
	for($i = 10; $i < 20; $i++) {
		$result_20 += $order_book->data->bids[$i]->price*$order_book->data->bids[$i]->quantity;
	}

	if($result_20 > 11000000 || round($buy_20/$sell_1*100) > 92)
		continue;

	// 구매 주문 30개까지 합계
	$result_30 = $result_20;
	$buy_30 = $order_book->data->bids[29]->price;
	for($i = 20; $i < 30; $i++) {
		$result_30 += $order_book->data->bids[$i]->price*$order_book->data->bids[$i]->quantity;
	}

	if($result_30 > 13000000 || round($buy_30/$sell_1*100) > 85)
		continue;

	echo "코인이름 : ".$key.'('.$value.') <br>';

	echo round($result_10/1000000)."백만원(".round($buy_10/$sell_1*100)."%)";
	echo "<br>";

	echo round($result_20/1000000)."백만원(".round($buy_20/$sell_1*100)."%)";
	echo "<br>";

	echo round($result_30/1000000)."백만원(".round($buy_30/$sell_1*100)."%)";
	echo "<br>";
	echo '<br>';

	usleep(100000); //0.1초쉼
}
echo "<br>";
echo "================================<br>";
echo "================================<br>";




// 가격정보 모두 받아오기
//$api_bhb->change_currency('ALL');


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
	//print_r($result);

	foreach ($CoinList_flip as $key_1 => $value_1) {
		if($value_1 == "KRW") { // 원화는 패스해야됨 ㅇㅇ
			continue;
		}

		echo $value_1.'('.$key_1.') <br>';
		echo '현재가 :  '.$result->data->$value_1->closing_price;
		echo "<br>";

		// 일단은 비트코인만 ㄱㄱ
		if($value_1 == "BTC")
			break;
	}
		echo "<br>";

}


echo "--------------------------------<br>";


// 구매되어있는 내역 목록 가져오기
{
	$api_bhb->change_currency('ALL');
	$result = $api_bhb->MWallet();

	if($result->status !== '0000') {
			print_r($result);
			return 0;
	}


	// print_r($result);

	$total_price = 0; //총 평가 금액
	$url = '';
	$is_sell = 0;
	foreach ($CoinList_flip as $key => $value) {

		$value = strtolower($value); // 소문자 변환 (소문자로 받아옴)
		$total = "total_".$value;
		$in_use = "in_use_".$value;
		$available = "available_".$value;

		if($value != 'krw') {

			// 가격정보 들고옴
			$price = 'xcoin_last_'.$value;
			$price =$result->data->$price;

			// 가지고 있는 수량
			$own_num = $result->data->$total;

			if($price*$own_num < 2000 ) { // 천원 미만이면 ㅂㅂ , 원화는 가져와야함
				continue;
			} else {

				$total_price += $price*$own_num;
			}

			// 마지막 구매내역파악
			$api_bhb->change_currency(strtoupper($value));
			$result_2 = $api_bhb->MTrade_Info(1);
			if($result_2->status !== '0000') {
					print_r($result_2);
					return 0;
			};
			// print_r($result_2);
			// return 0;

			
			$buy_name = $value.'1krw';
			$last_buy_price = $result_2->data[0]->$buy_name;
			$percent = ($price-$last_buy_price)/$last_buy_price*100;
			$percent = round($percent, 2);

			echo strtoupper($value).'('.$key.')';
			echo "<br>";
			//echo '전체: '.$result->data->$total."<br>";
			// echo '사용중: '.$result->data->$in_use."<br>";
			// echo '사용가능: '.$result->data->$available."<br>";
			$my_price = (int)($price*$own_num);
			echo "최근매수 금액 : ".$last_buy_price."<br>";
			echo '<div style =\'color:blue; float:left\'>현재가 : '.$price.' </div><div style =\'color:red; float:left\'>&nbsp;('.$percent."%)</div>";
			echo "<div>&nbsp<a href = 'https://www.bithumb.com/trade/chart/".strtoupper($value)."' onclick=\"window.open(this.href,'팝업창','width=1050, height=600'); return false; \">차트보기 </a> </div>";

			echo '평가금액 : '.$my_price.'원<br>';
			echo "<br>";

			$hand_off[strtoupper($value)] = $key;
			$sellCoin =  strtoupper($value).'off';
			
			if(isset($_GET[$sellCoin]) && $_GET[$sellCoin] != '') {

				$order_book = $api_bhb->xcoinApiCall("/public/orderbook/".$value);
				$standard_bid = $order_book->data->bids[10]->price;
		
				// 현재가보다 손절가가 더 크거나 같을때
				if($standard_bid > $_GET[$sellCoin]*0.88 && $price > $_GET[$sellCoin]*0.92 && $price <= $_GET[$sellCoin]) {

					$is_sell = 1;

					// 거래내역 들고오기
					$params['currency'] = strtoupper($value);
					$result_2 = $api_bhb->xcoinApiCall("/info/orders", $params);
					if(isset($result_2->data)) {
						// 매도주문 모두 취소
						foreach ($result_2->data as $key => $value_1) {
							$params['order_id'] = $value_1->order_id;
							$params['type'] = 'ask';
							$api_bhb->xcoinApiCall("/trade/cancel", $params);
						}
					}
					unset($params);

					// 매도 하면 끝
					$result = $api_bhb->MWallet();
					$have_num = 'available_'.$value;
					$have_num = $result->data->$have_num ;

					// 시장가 매도 합시다 ㄱㄱ
						//금액이 작으니 일단 시장가 매도로 ㄱㄱ
					$params['currency'] = strtoupper($value);

					// 소숫점 4가리수 까지 가능
					$temp = explode( '.', $have_num);
					// print_r($temp);
					if( strlen($temp[1]) > 4 ) {
						while(1) {
							$temp[1] = substr($temp[1] , 0, -1);
							if( strlen($temp[1]) <= 4 ) {
								break;
							} 
						}

						$have_num = $temp[0].'.'.$temp[1];
					}
					
					$params['units'] = $have_num;
					// 매도 시도
					$num = 0;
					while(1) {
						$result_3 = $api_bhb->xcoinApiCall("/trade/market_sell", $params);
						// print_r($result_3);
						// return 0;

						if($result_3->status == '0000') {
							break;
						}
						// 시장가 매도 실패
						if($num++ == 10) {
							break;
						}
						sleep(1);						
					}
			 	} else {
			 		$url .= $sellCoin.'='.$_GET[$sellCoin]."&";
			 	}



			}

		} else { // 원화인 경우

			$total_price += $result->data->$available;
			$total_price += $result->data->$in_use;

			echo strtoupper($value).'('.$key.')';
			echo "<br>";
			echo '전체: '.$result->data->$total."<br>";
			echo '사용중: '.$result->data->$in_use."<br>";
			echo '사용가능: '.$result->data->$available."<br>";
			echo "<br>";
		}
	}
	if($is_sell) {
		// 팔렸을 경우
		echo "<script>location.href='/bit/main?".$url."'</script>";
	}


	echo '총 평가 금액 : '.(int)$total_price."원<br>";


	echo "<br>=======손절 규칙=======<br><br>";


}

// 외부에서 판매 되었을때 제거
$is_detect = 0;
$url = '';
$change_url = 0;
foreach ($_GET as $key_1 => $value_1) {
	if(!isset($hand_off)) {
		$change_url = 1;
		break;
	}


	foreach ($hand_off as $key_2 => $value_2) {
		if($key_1 == $key_2.'off') {

			$url .= $key_1.'='.$value_1."&";
			$is_detect = 0;
			unset($_GET[$key_1]);
			break;
		} else {
			$is_detect = 1;
		}
	}

	if($is_detect) {
		$change_url = 1;
	}
}


// 없는 코인이 url에 있을 경우 이동 명령
if($change_url) {
	echo "<script>location.href='/bit/main?".$url."'</script>";
	return 0;
}



?>

<?php

/* public api
 * /public/ticker
 * /public/recent_ticker
 * /public/orderbook
 * /public/recent_transactions
 */

// $result = $api->xcoinApiCall("/public/ticker");
// echo "status : " . $result->status . "<br />";
// echo "last : " . $result->data->closing_price . "<br />";
// echo "sell : " . $result->data->sell_price . "<br />";
// echo "buy : " . $result->data->buy_price . "<br />";


/*
 * private api
 *
 * endpoint				=> parameters
 * /info/current		=> array('current' => 'btc');
 * /info/account
 * /info/balance		=> array('current' => 'btc');
 * /info/wallet_address	=> array('current' => 'btc');
 */



/*
 * date example
 * 2014-12-30 13:29:49 = 1419913789000
 * 2014-12-29 14:29:49 = 1419830989000
 * 2014-12-23 14:29:49 = 1419312589000
 * 2014-11-30 14:29:49 = 1417325389000
 */


?>


손절가 (시장가임... 추격매도는 나중에 필요하면 만들것) <br>
====의도적인 급략 대비 ====<br>
주문가 손절가의 -8%보다 더 떨어지면 판매x <br>
위에서 10번째 매수주문이 손절가의 -12%보다 낮으면 안됨


<div style = 'height: 50px;'>
	<form  method='get' action='/bit/main'>
			<?php

				if(isset($hand_off)) {
					foreach ($hand_off as $key => $value) {
						// 짜증나게 적용이 안되되 시발
						echo 	"<div style = 'margin-bottom: :20px; width:150px; float:left;'>";
						echo 		$key."(".$value.")".": ";
						echo 	"</div>";
						echo "<div style ='margin-bottom: :20px;''>";
						echo 		"<input style = 'width:100px;' type = 'text' name= '".$key."off' />";
						echo 		"원 이하 손절  <br>";
						echo "</div>";
					}
				}
			?>
		<button style = 'margin: 20px 0px 0px 100px; width:100px; font-size: 20px;' type="submit">적용</button>

	</form>
</div>


<br>


<script type="text/javascript">
	function getUrlVars() {
		var vars = {};
		var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi,    
		function(m,key,value) {
			vars[key] = value;
		});
		return vars;
	}

<?php

	foreach ($hand_off as $key => $value) {
		echo "var ".$key."off = getUrlVars()[\"".$key."off\"];";
		echo "$(\"input[name=".$key."off]\").val(".$key."off);";

	}

?>

</script>






<?php 

	if($_SERVER['REQUEST_URI'] != "/bit/main" ) {
		echo "<p id = 'state' style='font-size:40px; font-weight:blod;'> 10초후 새로고침 </p>";
	} else {
		echo "<p id = 'state' style='font-size:40px; font-weight:blod;'> 55초후 새로고침 </p>";
	}

?>

<script>
$(document).ready(function(){
	if($('#state').length > 0) {

<?php 
	if($_SERVER['REQUEST_URI'] != "/bit/main" ) {
		echo "var time = 10;"; // 1분마다 새로고침
 	} else {
 		echo "var time = 55;"; // 1분마다 새로고침
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






<?php 
	return 0;

	// 빗썸 공지
	$url = 'https://cafe.bithumb.com/boards/43/contents';
	$ch = curl_init(); 
	$postfields = 'draw=1&columns%5B0%5D%5Bdata%5D=0&columns%5B0%5D%5Bname%5D=&columns%5B0%5D%5Bsearchable%5D=true&columns%5B0%5D%5Borderable%5D=false&columns%5B0%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B0%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B1%5D%5Bdata%5D=1&columns%5B1%5D%5Bname%5D=&columns%5B1%5D%5Bsearchable%5D=true&columns%5B1%5D%5Borderable%5D=false&columns%5B1%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B1%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B2%5D%5Bdata%5D=2&columns%5B2%5D%5Bname%5D=&columns%5B2%5D%5Bsearchable%5D=true&columns%5B2%5D%5Borderable%5D=false&columns%5B2%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B2%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B3%5D%5Bdata%5D=3&columns%5B3%5D%5Bname%5D=&columns%5B3%5D%5Bsearchable%5D=true&columns%5B3%5D%5Borderable%5D=false&columns%5B3%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B3%5D%5Bsearch%5D%5Bregex%5D=false&columns%5B4%5D%5Bdata%5D=4&columns%5B4%5D%5Bname%5D=&columns%5B4%5D%5Bsearchable%5D=true&columns%5B4%5D%5Borderable%5D=false&columns%5B4%5D%5Bsearch%5D%5Bvalue%5D=&columns%5B4%5D%5Bsearch%5D%5Bregex%5D=false&start=0&length=30&search%5Bvalue%5D=&search%5Bregex%5D=false';

	curl_setopt($ch, CURLOPT_HTTPHEADER, array('Host: cafe.bithumb.com'));

	curl_setopt($ch, CURLOPT_URL, $url); 	// set url 
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
 	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); 
 	curl_setopt($ch, CURLOPT_HEADER, 0); 
 	curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); 
 	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
 	curl_setopt($ch, CURLOPT_POST, 1); // 포스트 전송 활성화 
 	curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields); // curl에 포스트값 셋팅
 	curl_setopt($ch, CURLOPT_REFERER, 'https://cafe.bithumb.com/view/boards/43'); // https, http 구분하세요
 	$output = curl_exec($ch); 
 	curl_close($ch);


 	$dateString = date("Y", time());
 	// $dateString ='2019.07.19';
 	$output = json_decode($output);
 	// print_r($output);

 	foreach ($output->data as $key => $value) {
 		
 		if(strpos($value[4] , $dateString) === false) { // 오늘 날짜는 시간으로 표시됨 ㅇㅇ
 			if(strpos($value[2] , '이벤트') == true) {
 				continue;
 			}
 			if(strpos($value[2] , '에어드랍') == true) {
 				continue;
 			}
 			if(strpos($value[2] , 'THETA') == true) {
 				continue;
 			}
 			echo "<p style = 'color:red; font-size:20px;'>";
 			echo "오늘자 공지 발견됨<br>";
 			echo "제목 : ".$value[2];
 			echo "</p>";

 			echo "<iframe width=\"560\" height=\"315\" src=\"https://www.youtube.com/embed/hT_nvWreIhg?autoplay=1\" frameborder=\"0\" allow=\"accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe>";

 		}
 	}
 	return 0;

 

?>