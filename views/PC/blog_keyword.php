
 
<?php


//url 형식 지정
$url_pc = "https://ac.search.naver.com/nx/ac?_callback=window.__jindo_callback._$3361_0&q=".$keyword."&q_enc=UTF-8&frm=nv&r_format=json&r_enc=UTF-8&r_unicode=0&t_koreng=1&ans=2&run=2&rev=4&con=1&st=100";
$url_mobile =  "https://mac.search.naver.com/mobile/ac?_callback=_jsonp_8&q=".$keyword."&con=1&q_enc=UTF-8&st=1&frm=mobile_nv&r_format=json&r_enc=UTF-8&r_unicode=0&t_koreng=1&ans=2&run=2&rev=4";


$ch = curl_init(); 
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); 
curl_setopt($ch, CURLOPT_HEADER, 0); 
curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); 
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 

curl_setopt($ch, CURLOPT_URL, $url_pc); 	// set url 
$output['pc'] = curl_exec($ch); 
//print_r($output['pc'] );

curl_setopt($ch, CURLOPT_URL, $url_mobile); 	// set url 
$output['mobile'] = curl_exec($ch); 
//print_r($output['mobile'] );



	//가공시작
$i = 0;
foreach ($output as $key => $value) {
		//기본적인 가공
	preg_match_all("/\[\".*?\"\]/s", $value, $output[$key]);	
		//이상한것까지 딸려와서 그거 제거하기 위한 가공
	foreach ($output[$key][0] as $key => $value) {
		preg_match_all("/\"(.*?)\"/s", $value, $temp);

		if($temp[1][0] !== 0 ) {
			$temp[1][0] = str_replace(" ", "", $temp[1][0]);
			$result[$i++] = $temp[1][0];
		}
	}

	// print_r($result);
	// echo "<br>";
}

	// print_r($result);
	// echo "<br>";
		//중복제거 
	$result = array_unique($result);
	// print_r(	$result);
	// echo "<br>";


	//2차 키워드 검색
	unset($output);
	$j = 0;
	foreach ($result as $key => $value) {
		$url_pc = "https://ac.search.naver.com/nx/ac?_callback=window.__jindo_callback._$3361_0&q=".$value."&q_enc=UTF-8&frm=nv&r_format=json&r_enc=UTF-8&r_unicode=0&t_koreng=1&ans=2&run=2&rev=4&con=1&st=100";
		$url_mobile =  "https://mac.search.naver.com/mobile/ac?_callback=_jsonp_8&q=".$value."&con=1&q_enc=UTF-8&st=1&frm=mobile_nv&r_format=json&r_enc=UTF-8&r_unicode=0&t_koreng=1&ans=2&run=2&rev=4";

		curl_setopt($ch, CURLOPT_URL, $url_pc); 	// set url 
		$output[$j++] = curl_exec($ch);

		curl_setopt($ch, CURLOPT_URL, $url_mobile); 	// set url 
		$output[$j++] = curl_exec($ch);

		usleep(200000); //0.2초
	}

	//위와 동일하게 가공 (이때, i는 위 기준임)
	foreach ($output as $key => $value) {
			//기본적인 가공
		preg_match_all("/\[\".*?\"\]/s", $value, $output[$key]);	
			//이상한것까지 딸려와서 그거 제거하기 위한 가공
		foreach ($output[$key][0] as $key => $value) {
			preg_match_all("/\"(.*?)\"/s", $value, $temp);

			if($temp[1][0] !== 0 ) {
				$temp[1][0] = str_replace(" ", "", $temp[1][0]);
				$result[$i++] = $temp[1][0];
			}
		}

		// print_r($result);
		// echo "<br>";
	}

			//중복제거 
	$result = array_unique($result);



	//3차 키워드는 일단 검색치 않음


	//조회수 조회 및 출력
	
	ini_set("default_socket_timeout", 30);
	require_once 'static\lib\naver-api\restapi.php';
	
	$config = parse_ini_file('static\lib\naver-api\sample.ini');
	// print_r($config);

	$api = new RestApi($config['BASE_URL'], $config['API_KEY'], $config['SECRET_KEY'], $config['CUSTOMER_ID']);

		echo "설명 : 조회수 3000이상 = 빨간색, 조회수 1500이상 = 파란색 <br>";
		echo "경고 : 모바일 진행 안됨 (..... 모바일 키워드 말하는듯 ㅇㅇ) <br>";

		echo "
			<table>
				<th>키워드</th>
				<th style = 'width: 100px'>pc</th>
				<th style = 'width: 100px'>모바일</th>
				<th style = 'width: 100px'>페이지</th>
				";

	foreach ($result as $key => $value) {

		$customerlist = $api->GET('/keywordstool', array('hintKeywords' => $value ));

		if(!isset($customerlist['keywordList'][0]['relKeyword']) || $customerlist['keywordList'][0]['relKeyword'] != strtoupper($value) )  {
			// echo "$value\n";
			// print_r($customerlist);
			// echo "키워드 에러 확인할것";
		
			echo "
				<tr>
					<td>".$value."</td>
					<td>"."error"."</td>
					<td>"."error"."</td>
				</tr>
			";

			
		} else { //키워드 에러 없을때 정상출력

			$views_pc = (int)$customerlist['keywordList'][0]['monthlyPcQcCnt'];
			$views_mobile = (int)$customerlist['keywordList'][0]['monthlyMobileQcCnt'];

			echo "
					<tr>
					";


			if($views_pc + $views_mobile > 1500) {
					
				if($views_pc + $views_mobile > 3000) {
					echo "
						<td style = 'color:red; '>".$value."</td>
					";
				} else {
					echo "
						<td style = 'color:blue; '>".$value."</td>
					";
				}

				$url_pc =	page_URL(10, $value); //10page 부터 확인
				curl_setopt($ch, CURLOPT_URL, $url_pc);  
				$temp_output = curl_exec($ch);

				usleep(200000); //0.2초 

				//10page에 존재한다면
				if( check_page($temp_output, $value) > 4 ) {
					$page = "11p 이상";
				} else {

					$url_pc =	page_URL(5, $value); 
					curl_setopt($ch, CURLOPT_URL, $url_pc);  
					$temp_output = curl_exec($ch);	

					usleep(200000); //0.2초 

					//5p부터는 순차적으로 계속 진행
					$page = "5p";
					if( check_page($temp_output, $value) > 4 ) {
						for( $j = 1; $j < 5; $j++) {
							$url_pc =	page_URL(5 + $j, $value); 
							curl_setopt($ch, CURLOPT_URL, $url_pc);  
							$temp_output = curl_exec($ch);	
							if( check_page($temp_output, $value) > 4 ) {	
								$page = (5 + $j)."p";
							} else {
								break;
							}

							usleep(200000); //0.2초 
						}


					} else {
						for( $j = 1; $j < 5; $j++) {
							$url_pc =	page_URL(5 - $j, $value); 
							curl_setopt($ch, CURLOPT_URL, $url_pc);  
							$temp_output = curl_exec($ch);	
							if( check_page($temp_output, $value) <= 4 ) {	
								$page = (5 - $j)."p";
							} else {
								break;
							}

							usleep(200000); //0.2초 
						}
					}
				}

			} else {
					echo "
							<td>".$value."</td>
					";
					$page = "";
			}

			echo "
						<td>".$customerlist['keywordList'][0]['monthlyPcQcCnt']."</td>
						<td>".$customerlist['keywordList'][0]['monthlyMobileQcCnt']."</td>
						<td>".$page."</td>
					</tr>
				";
		}
		usleep(200000); //0.2초 
	}

	echo "</table>";


curl_close($ch); 



	function page_URL ($page_num, $keyword) { //페이지 url 리턴 받기
		$num = 1+($page_num-1)*10;
		$url = "https://search.naver.com/search.naver?date_from=&date_option=0&date_to=&dup_remove=1&nso=&post_blogurl=&post_blogurl_without=&query=".$keyword."&sm=tab_pge&srchby=all&st=sim&where=post&start=".$num;

		return $url;
	}

	function check_page ($temp_output, $keyword) {  //해당 페이지에 검색어가 나타나는지 확인
		//제목 추출한것을 몇개가 있는지 확인
		$result = 0;

		//제목 뽑아내기
		preg_match_all("/<dl>.?<dt>.*?\" title=\".*?\"/s", $temp_output, $output);	
		// print_r($output);
		//print_r($output[0]);
		$title = array();
		foreach ($output[0] as $key => $value) {
			preg_match_all("/title=\"(.*?)\"/s",$value, $temp_title); 
			// print_r($temp_title);
			array_push($title, strtoupper($temp_title[1][0]) ); //대문자로 해야됨
		}
		// print_r($title);
		// echo "<br>";


		//제목에 키워드 있는지 확인
		foreach ($title as $key => $value) {

			//검사를 위해 지속적으로 키워드 선언함
			$temp_array = array();
			for($j=0; $j < mb_strlen($keyword, "UTF-8"); $j++) {
				//대문자로 진행
				array_push($temp_array, strtoupper( mb_substr( $keyword, $j, 1, "UTF-8") ) );
			}

			//제목 길이만큼 검사
			for($i =0; $i < mb_strlen($value); $i++) {

				foreach ($temp_array as $key_2 => $value_2) {
					//동일한 문자 발견시 해당 배열원소 삭제
					if( mb_substr( $value, $i, 1, "UTF-8") == $value_2) {
						unset($temp_array[$key_2]);
						break;
					}
					
				}

				//검색어에 해당하는 문자가 모두 삭제되면 키워드 존재하는것
				if(count($temp_array) == 0) {
					$result ++;					
					break;
				}

			}
		}

		return $result;
	}

