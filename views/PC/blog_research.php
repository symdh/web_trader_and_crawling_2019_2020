<?php

$i = 0;
while(1) {
	$i++;

	//url 형식 지정
	$url_1 = "https://blog.naver.com/PostTitleListAsync.nhn?blogId=".$blogname."&viewdate=&currentPage=".$i."&categoryNo=0&parentCategoryNo=&countPerPage=30";


	$ch = curl_init(); 
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); 
	curl_setopt($ch, CURLOPT_HEADER, 0); 
	curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 

	curl_setopt($ch, CURLOPT_URL, $url_1); 	// set url 
	$output[$i] = curl_exec($ch); 
	
	preg_match_all("/span>(.*)strong>.*?<a/s", $output[$i], $result);
	if(!isset($result[0][0])) {
		echo "<p>총 탐색 페이지수 = ".$i."</p>";
		break;
	}

	sleep(2);
}

//가공시작

$i = 0;
foreach ($output as $key => $value) {

	preg_match_all("/logNo\":\"(.*?)\",\"title\"/s", $value, $result);
	$url_num[$i] = $result[1];
	// print_r($url_num);

	preg_match_all("/\"title\":\"(.*?)\",/s", $value, $result);
	$title[$i] = $result[1];
	// print_r($title);

	$i++;
}

echo "
	<table>
		<th>제목</th>
		<th style = 'width: 100px'>주소</th>
		<th style = 'width: 100px'>예비1</th>
		<th style = 'width: 100px'>예비2</th>
		<tbody>
	";


foreach ($url_num as $key_1 => $value_1) {
	$test = $value_1;
	foreach ($test as $key_2 => $value_2) {
		echo "<tr>";

		echo "<td>";
		echo urldecode($title[$key_1][$key_2]);
		echo "</td>";

		echo "<td>";
		echo "<a href = 'https://blog.naver.com/".$blogname."/".$value_2."' onclick=\"window.open(this.href,'팝업창','width=1050, height=600'); return false; \">";
		echo "클릭 </a>";
		echo "</td>";

		echo "<td>";
		echo "</td>";

		echo "<td>";
		echo "</td>";
	}
}

echo "</tbody></table>";