
<!-- 시간에 대한 값 -->
<script src="https://code.jquery.com/jquery-1.12.4.min.js" integrity="sha256-ZosEbRLbNQzLpnKIkEdrPv7lOy9C27hHQ+Xp8a4MxAQ=" crossorigin="anonymous"></script>

	<?php 

		if(isset($_GET['buycurrency'])) {
			echo "<div id = 'state' style='font-size:40px; font-weight:blod;  float:left;'> 10초후 새로고침 </div>";
		} else {
			echo "<div id = 'state' style='font-size:40px; font-weight:blod;  float:left;' > 120초후 새로고침 </div>";
		}

	?>

	<div> <button id = "stop_timer" style ='margin-height : 20px; width:250px; height : 50px'> 동작 금지 </button> </div>
	<br>



	<script>
	$(document).ready(function(){
		if($('#state').length > 0) {

	<?php 
		if(isset($_GET['buycurrency'])) {
			echo "var time = 10;"; // 1분마다 새로고침
	 	} else {
	 		echo "var time = 120;"; // 1분마다 새로고침
	 	}
	?>
			is_palse = 0;
			$('#stop_timer').click(function (){
			   	$('#stop_timer').css('background-color','red');
			    is_palse=1;
			  
			});


			window.setInterval(function(){
				time = time - 5;
			 	if(time == 5) {
			   		$('#state').html("<div style ='width:400px; float:left;'>5초후 새로 고침</div>");
			  		setTimeout(function() {
			  			if(!is_palse) { // 중지시 동작안함
			  				location.reload();
			  			} else {
			  				$('#state').html("<div style ='width:400px; color : red; float:left;'>"+"새로 고침 중지됨</div>");
			  				return;
			  			}
					}, 5000);
				} else {
					if(!is_palse) { // 중지시 동작안함
						$('#state').html("<div style ='width:400px; float:left;'>"+time+"초후 새로 고침</div>");
		  			} else {
		  				$('#state').html("<div style ='width:400px; color : red; float:left;'>"+"새로 고침 중지됨</div>");
		  				return;
		  			}	

				}
				
			}, 5000);
		}




	});

</script>



<?php // 기본 동작 정의

	$CI =& get_instance();
	$CI->load->model('Bit_model');


	require("./static/include/BitMex_Trade.php");
	$key = "secure";
	$secret = "secure";
	$Trade = new BitMex_Trade($key,$secret);

	if(isset($_GET['state']) && ($_GET['state'] == 1 || $_GET['state'] == 2 || $_GET['state'] == 4) ) { // 단순 구매
		$result = $Trade->Synchronization_price($_GET['buyposition']); // 숏에 대한 동기화 우선진행
		if($result) { // 즉시 구매 실패시 새로고침 ㄱㄱ
			echo $result;
			$CI->Bit_model->save_log($result);
			echo "<script> location.reload(); </script>";
			return 0; // 아래 실행하면 안됨 ㅇ
		} 

		$result = $Trade->Utilize($_GET['state'], @$_GET['buycurrency'], @$_GET['buyleverage'], @$_GET['buyseed'], @$_GET['buymin'], @$_GET['buymax'], @$_GET['buyposition']);
		if(!$result) { // 0이면 성공
			$result = $Trade->Change();
			if(!$result) {
				$result = $Trade->Execute();
				$CI->Bit_model->save_log($Trade->test); // 테스트후 정규식 사용해서 지울것 ㅇㅇ
				if( !$result ) {
					sleep(3);
					echo "<p>구매 요청 완료</p>";
					$uri= $_SERVER['REQUEST_URI'];
					if($_GET['state'] == 4) {
						$uri  = preg_replace("/state=[0-9]{1,}/", "state=11", $uri);
					} else {
						$uri  = preg_replace("/state=[0-9]{1,}/", "state=10", $uri);
					}
					echo "<script>location.href='".$uri."'</script>";
				} else {
					echo $result;
					$CI->Bit_model->save_log($result);
				}
			} else {
				echo $result;
				$CI->Bit_model->save_log($result);
			}
		} else {
			echo $result;
			$CI->Bit_model->save_log($result);
		}
		return 0; // 이 아래는 실행할 필요없음ㅇㅇ
	} else if(isset($_GET['state']) && $_GET['state'] == 3) { // 즉시 구매
		$result = $Trade->Utilize($_GET['state'], @$_GET['buycurrency'], 10, @$_GET['buyseed'], @$_GET['buymin'], @$_GET['buymax'], @$_GET['buyposition']);
		if(!$result) { // 0이면 성공
			$result = $Trade->Change();
			if(!$result) {
				$result = $Trade->Execute();
				$CI->Bit_model->save_log($Trade->test); // 테스트후 정규식 사용해서 지울것 ㅇㅇ
				if( !$result ) {
					$CI->Bit_model->save_log('즉시 구매 성공');
					echo "<script>location.href='/bit/tradebot'</script>";
				} else {
					echo $result;
					$CI->Bit_model->save_log($result);
					echo "<script> location.reload(); </script>";
				}
			} else {
				echo $result;
				$CI->Bit_model->save_log($result);
			}
		} else {
			echo $result;
			$CI->Bit_model->save_log($result);
		}
		return 0; // 아래 필요없음 ㅇ
	} else if(isset($_GET['state']) && ($_GET['state'] == 10 || $_GET['state'] == 11) ) { // 구매 감시
		$result = $Trade->Utilize($_GET['state'], @$_GET['buycurrency'], @$_GET['buyleverage'], @$_GET['buyseed'], @$_GET['buymin'], @$_GET['buymax'], @$_GET['buyposition'], @$_GET['target1']);
		if(!$result) { // 0이면 성공
			$result = $Trade->Execute();
			$CI->Bit_model->save_log($Trade->test); // 테스트후 정규식 사용해서 지울것 ㅇㅇ
			if( !$result ) {
				if($Trade->need_move) {
					$uri= $_SERVER['REQUEST_URI'];
					$uri  = preg_replace("/state=[0-9]{1,}/", "state=".$Trade->need_move, $uri);
					echo "<script>location.href='".$uri."'</script>";
					return 0;
				} else if($Trade->is_done) {
					// 수정된 판매 비율
						// 정보가 여기에 다 남아서 여기서 진행
						// 롱기준 (target 1 - buymax)*1.5 <<< (buymin - stoploss) 시행

					// 금액 도달 안했는데 그냥 넘어가서 오류 확인용 (나중에 기억안나면 지원도됨)
						$str = "ask = ".$Trade->ask." bid = ".$Trade->bid."(금액 도달 후 넘어가는건지 확인용)";
						$CI->Bit_model->save_log($str);

					$uri= $_SERVER['REQUEST_URI'];
					if($_GET['buyposition'] == 'long') {
						if( ($_GET['target1'] - $_GET['buymax'])*1.5 < $_GET['buymin'] - $_GET['stoploss']) {
							$uri  = preg_replace("/state=[0-9]{1,}/", "state=21", $uri); // 수정된 값 ㅇㅇ
						}	else {
							$uri  = preg_replace("/state=[0-9]{1,}/", "state=20", $uri);
						}
					} else if($_GET['buyposition'] == 'short') {
						if( ($_GET['buymin'] - $_GET['target1'])*1.5 < $_GET['stoploss'] - $_GET['buymax']) {
							$uri  = preg_replace("/state=[0-9]{1,}/", "state=21", $uri); // 수정된 값 ㅇㅇ
						}	else {
							$uri  = preg_replace("/state=[0-9]{1,}/", "state=20", $uri);
						}
					}
					echo "<script>location.href='".$uri."'</script>";
				} else {
					if($_GET['state'] == 11) {
						echo "<p> *** 진입 범위 가격 도달시까지 대기 중 ***</p>";
					} else {
						echo "<p> 구매감시중 </p>";
					}

					echo "<p> 구매 종목 : ".$_GET['buycurrency']."</p>";
					echo "<p> 포지션 :  ".$_GET['buyposition']."</p>";
					echo "<p> target1 : ".$_GET['target1']." </p>";
					echo "<p> 최소구매가 : ".$_GET['buymin']." </p>";
					echo "<p> 최대구매가 : ".$_GET['buymax']." </p>";
					if(isset($_GET['stoploss'])) 	echo "<p> 스탑로스 : ".$_GET['stoploss']." </p>";
				}

				// 잘못 넣었을 까봐.... 나중에 삭제할 수 있을듯 ㅇㅇ
				if($_GET['buyposition'] == 'long') {
					if($_GET['stoploss'] > $_GET['target1']) {
						echo "<p style = 'color:red;'> stoploss 값 확인할것 </p>";
					}
				} else {
					if($_GET['stoploss'] < $_GET['target1']) {
						echo "<p style = 'color:red;'> stoploss 값 확인할것 </p>";
					}
				}
			} else {
				echo $result;
				$CI->Bit_model->save_log($result);
			}
		} else {
			echo $result;
			$CI->Bit_model->save_log($result);
		}
		return 0; // 아래 필요없음 ㅇ
	} else if(isset($_GET['state']) && ($_GET['state'] == 20 || $_GET['state'] == 21) ) { // 판매 등록
		$result = $Trade->Utilize($_GET['state'], $_GET['buycurrency']);
		if(!$result) { // 0이면 성공
			$result = $Trade->OpenInfo();
			if(!$result) {
				$result = $Trade->Traget_Check($_GET['target1'],$_GET['target2'],$_GET['target3'],$_GET['target4'],$_GET['target5']);
				if(!$result) {
					$result = $Trade->Execute();
					$CI->Bit_model->save_log($Trade->test); // 테스트후 정규식 사용해서 지울것 ㅇㅇ
					if( !$result ) {
						echo "<p> 주문 등록 성공 ㄱㄱ</p>";
						$CI->Bit_model->save_log("주문 등록 성공 ㄱㄱ");
						$uri= $_SERVER['REQUEST_URI'];
						$uri  = preg_replace("/state=[0-9]{1,}/", "state=30", $uri);
						echo "<script>location.href='".$uri."'</script>";
					} else {
						echo $result;
						$CI->Bit_model->save_log($result);
					}
				} else { // 페이지 이동 필요... ㅇㅇ
					echo $result;
					$CI->Bit_model->save_log($result);
					echo "<script>location.href='/bit/tradebot'</script>";
				}
			} else {
				echo $result;
				$CI->Bit_model->save_log($result);
			}
		} else {
			echo $result;
			$CI->Bit_model->save_log($result);
		}
		return 0;
	} 

?>


==========주문 도와주기 <br><br>


<script>
function form_submit()
{
	/* confirm 함수는 확인창 결과값으로 TRUE 와 FALSE 값을 return 하게 된다*/
	var check_submit=confirm('진짜로 구매??? (레버 10배)');
	return check_submit;
}
</script>

<div> <!--  즉시구매 form -->  
	<form id = 'post_page_1' method='get' onSubmit="return form_submit();" action='/bit/tradebot'>
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
								<option value = 'XBTUSD'>XBT</option>
								<option value = 'ADAZ19'>ADA</option>
								<option value = 'BCHZ19'>BCH</option>
								<option value = 'EOSZ19'>EOS</option>
								<option value = 'ETHUSD'>ETH</option>
								<option value = 'LTCZ19'>LTC</option>
								<option value = 'TRXZ19'>TRX</option>
								<option value = 'XRPZ19'>XRP</option>
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
							<input type = 'hidden'  name = 'state' value = 3 />
							<button type = 'submit'>즉시 OPEN 실행 (레버 10배) </bottom> 
						</th>

					</tr>
				</tbody>
			</table>

	</form>
</div>

<br><br>

<div> <!--  상세구매 form --> 
	<form id = 'post_page_2' onsubmit= 'return controll_state();' method='get' action='/bit/tradebot'>
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
					<th style="width: 250px;">진입가 올 때까지 감시</th>
				</thead>
				<tbody>
					<tr>
						<th>
							<select name = 'buycurrency' style="font-size: 20px; width: 80px;">
								<option value = ''></option>
								<option value = 'XBTUSD'>XBT</option>
								<option value = 'ADAZ19'>ADA</option>
								<option value = 'BCHZ19'>BCH</option>
								<option value = 'EOSZ19'>EOS</option>
								<option value = 'ETHUSD'>ETH</option>
								<option value = 'LTCZ19'>LTC</option>
								<option value = 'TRXZ19'>TRX</option>
								<option value = 'XRPZ19'>XRP</option>
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
						<input type="checkbox" name = "is_scalping" style = "width: 25; height: 25;">
						
					</th>
					<th>
						<input type="checkbox" name = "is_standby" style = "width: 25; height: 25;">
					</th>
					<!-- <input type="hidden" name="state" value="2" style = "width: 25; height: 25;"> -->
				</tr>
			</tbody>
			</table>
		</div>

		<script>
			function controll_state () {
				if($('input[name=is_scalping]').is(':checked')) {
					$('#post_page_2').append("<input type='hidden' name='state' value='1'> ");
				} else if($('input[name=is_standby]').is(':checked')) {
					$('#post_page_2').append("<input type='hidden' name='state' value='4'> ");
				} else {
					$('#post_page_2').append("<input type='hidden' name='state' value='2'> ");
				}
				return true;
			}
		</script>



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

// OPEN 값 및 target, stoploss 감시
{
	$result = $Trade->OpenInfo();

	if(!$result) {
		// 없으면 오류 무시하고 넘어가면 됨 ㅇㅇ
		if( isset($_GET['stoploss']) && $_GET['stoploss'] != '' && isset($Trade->own[$_GET['buycurrency']]) ) {
			$result = $Trade->Utilize(30, $_GET['buycurrency'], NULL, NULL, NULL, NULL, $Trade->own[$_GET['buycurrency']]['position'], NULL,  $_GET['stoploss']);
			$uri= $_SERVER['REQUEST_URI'];

			// target이 없으면 stoploss만 감시하면 됨 ㅇㅇ
			if(!$result) { // 0이면 성공
				if( isset($_GET['target1']) && $_GET['target1'] != '' ) {
					$result = $Trade->Traget_Check($_GET['target1'],$_GET['target2'],$_GET['target3'],$_GET['target4'],$_GET['target5']);
					if(!$result) {
						$stoploss = $Trade->Execute(31);
						$CI->Bit_model->save_log($Trade->test); // 테스트후 정규식 사용해서 지울것 ㅇㅇ
						$uri  = preg_replace("/stoploss=[0-9]{1,}\.?[0-9]{1,}/", "stoploss=".$stoploss, $uri);
						if($_GET['stoploss'] != $stoploss) { 
							// stoploss 때문에 청산 에러 뜨는거 같으니 바로 리디렉트 ㄱㄱ
								// 이러면 직관상 값 못받아 왔다가 중복 stoploss되는걸 방지
							echo "<script>location.href='".$uri."'</script>";
							return 0;
						}
					} else { // 타겟 자체가 에러가 있으면 본 페이지로 돌아감 ㅇ
						echo $result;
						$CI->Bit_model->save_log($result);
						echo "<script>location.href='/bit/tradebot'</script>";
						return 0;
					}
				}
			} else {
				echo $result;
				$CI->Bit_model->save_log($result);
			}
				
			if(!$result) { // 0이면 성공
				// $result = $Trade->Change(); // 필요없음
				$result = $Trade->Execute(30);
				$CI->Bit_model->save_log($Trade->test); // 테스트후 정규식 사용해서 지울것 ㅇㅇ
				if( !$result ) {
					$CI->Bit_model->save_log('stoploss로 Close 완료');
					echo "<script>location.href='/bit/tradebot'</script>";
					return 0;
				} else {
					if($result != '1') { // 너무 로그에 많이 찍혀서 ㅇㅇ
						echo $result;
						$CI->Bit_model->save_log($result);
					}
				}
			} else {
				echo $result;
				$CI->Bit_model->save_log($result);
			}
		} else if( isset($_GET['buycurrency']) && !isset($Trade->own[$_GET['buycurrency']]) ) {
			// 아마 자바스크립트 때문에 제대로 안넘어 가는듯 ㅇㅇ
			echo "<script>location.href='/bit/tradebot'</script>";
			return 0;
		}
	} else {
		echo $result;
		$CI->Bit_model->save_log($result);
	}
}



// open 목록 출력
{
	echo "<table>
					<thead>
						<tr>
							<th>종목</th>
							<th>시장평균가</th>
							<th>이익</th>
							<th>진입가</th>
							<th>청산가</th>
							<th>레버리지</th>
							<th>CLOSE</th>
						</tr>
					</thead>
					<tbody>";


	foreach ($Trade->own as $key => $value) {
		echo "<tr>";

		echo "<th style = 'padding-top: 20px;'>".$value['currency']."</th>";

		echo "<th style = 'padding-top: 20px;'>".$value['markPrice']."</th>";

		if($value['profit'] >= 0) {
			echo "<th style = 'color:red; padding-top: 20px;'>".$value['profit']."%</th>";
		} else {
			echo "<th style = 'color:blue; padding-top: 20px;'>".$value['profit']."%</th>";
		}

		echo "<th style = 'padding-top: 20px;'> ".$value['EntryP']."</th>";
		echo "<th style = 'padding-top: 20px;'> ".$value['DoneP']."</th>";
		echo "<th style = 'padding-top: 20px;'> ".$value['leverage']."배</th>";


		if($value['position'] == 'long') {
			$temp = $value['markPrice']*100; 
		} else {
			$temp = $value['markPrice']/100;
		}

		echo "<th style = 'padding-top: 20px;'><a href = 'http://autocoining.com/bit/tradebot?buycurrency=".$value['currency']."&stoploss=".$temp."'><button type ='submit'>즉시종료</button></th>";

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
		<th>주문취소 후 taeget 재설정</th>
		</thead><tbody>

	<form id = 'post_page_3' onsubmit= 'return controll_state_2();' method='get' action='/bit/tradebot'>
	<tr>
		<th>
		<select name = 'buycurrency' style="font-size: 20px; width: 80px;">
								<option value = ''></option>
								<option value = 'XBTUSD'>XBT</option>
								<option value = 'ADAZ19'>ADA</option>
								<option value = 'BCHZ19'>BCH</option>
								<option value = 'EOSZ19'>EOS</option>
								<option value = 'ETHUSD'>ETH</option>
								<option value = 'LTCZ19'>LTC</option>
								<option value = 'TRXZ19'>TRX</option>
								<option value = 'XRPZ19'>XRP</option>
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
		<th> <input type="checkbox" name = "is_ReOrder" style = "width: 25; height: 25;"> </th>

		</tr>
	<tbody><table>

		<div style="padding-left: 200px;">
			<input type='submit' value='입력''  style='width: 100px; height:40px;  font-size: 20px;''>
		</div>


	</form>

		<script>
			function controll_state_2 () {
				if($('input[name=is_ReOrder]').is(':checked')) {
					$('#post_page_3').append("<input type='hidden' name='state' value='20'> ");
				} else {
					// stoploss만 재설정 (target 주문은 따로 안들어감)
					$('#post_page_3').append("<input type='hidden' name='state' value='30'> ");
				}
				return true;
			}
		</script>



<?php  // 로그 출력

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
	var currency = getUrlVars()["buycurrency"];
	var stoploss = getUrlVars()["stoploss"];
	var target1 = getUrlVars()["target1"];
	var target2 = getUrlVars()["target2"];
	var target3 = getUrlVars()["target3"];
	var target4 = getUrlVars()["target4"];
	var target5 = getUrlVars()["target5"];

	$("select[name=buycurrency]").val(currency).attr('selected','selected');
	$("input[name=stoploss]").val(stoploss);
	$("input[name=target1]").val(target1);
	$("input[name=target2]").val(target2);
	$("input[name=target3]").val(target3);
	$("input[name=target4]").val(target4);
	$("input[name=target5]").val(target5);
</script>


