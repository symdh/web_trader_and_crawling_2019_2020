<?php //dc검색하기가 개 엿같아서 직접 만듬


if(!isset($_GET['search']) ) {
		echo "검색어 입력 안되어 있음 <br><br>";

		echo "<td style = 'width:500px;'>
						<form id = 'get_page' method='get' action='/main/dcsearch'>
							검색어 입력 : 
							<input type='text' name = 'search' style = 'width:200px; height : 40px;'>
							 <select name='gallery' style = 'width:200px; height : 40px; font-size:20px;'>
							    <option value='bitcoins'>비트코인 갤러리</option>
							    <option value='coin'>알트 마이너 갤러리</option>
							    <option value='satoshi'>사토시 마이너 갤러리</option>
							    <option value='revolution'>혁명 마이너 갤러리</option>
							  </select>
							<input type='submit' value='검색하기''  style='width: 100px; height:40px;''>
						</form>
					</td>";

		return 0;
} 

switch ($_GET['gallery']) {
	case 'bitcoins':
		$sub_url = '/board/lists/?id=bitcoins';
		$past_board = 1;
		break;
	case 'coin':
		$sub_url = '/mgallery/board/lists/?id=coin';
		$past_board = 0;
		break;
	case 'satoshi':
		$sub_url = '/mgallery/board/lists/?id=satoshi';
		$past_board = 0;
		break;
	case 'revolution':
		$sub_url = '/mgallery/board/lists/?id=revolution';
		$past_board = 0;
		break;
}

$_GET['search'] = preg_replace('/ /', '%20', $_GET['search']); //띄어쓰기를 검색어로 변환


//글 가져오기
function take_write($output, $past_board = 1) { //신 게시판의 경우 약간 틀림
	if($past_board == 1)
		preg_match_all("/<tr.*?<\/tr>/s", $output, $matches);
	else 
		preg_match_all("/<tr.*?<\/tr>/s", $output, $matches);


	//글 번호 뽑아내고(result) , 글 아닌것 삭제
	foreach ($matches[0] as $key => $value) {
		if($past_board == 1)
			preg_match_all("/(\d{7,})/s", $value, $arr);
		else 
			preg_match_all("/>(\d{1,})</s", $value, $arr);
		if(isset($arr[0][0]))
			$result[$key][0] = $arr[1][0];
		else 
			unset($matches[0][$key]);
	}

	//날짜 
	foreach ($matches[0] as $key => $value) {
		if($past_board == 1)
			preg_match_all("/\d{4,4}\.\d{1,2}\.\d{1,2}/s", $value, $arr);
		else 
			preg_match_all("/\d{4,4}\.\d{1,2}\.\d{1,2}/s", $value, $arr);
		if(isset($arr[0][0])) {

			$arr[0][0]=str_replace(".","-",$arr[0][0]);
			$result[$key][1] =$arr[0][0];
		} else 
			unset($matches[0][$key]);
	}

	//글 주소 뽑아냄 
	foreach ($matches[0] as $key => $value) {
		if($past_board == 1)
			preg_match_all("/href=\"(.*?)\"/s", $value, $arr);
		else 
			preg_match_all("/href=\"(.*?)\"/s", $value, $arr);
		if(isset($arr[1][0]))
			$result[$key][2] = $arr[1][0];	
		else 
			unset($matches[0][$key]);
	}
	

	//글 내용 뽑아냄 (result)
	foreach ($matches[0] as $key => $value) {
		if($past_board == 1)
			preg_match_all("/middle;\">(.*?)<\/a>/s", $value, $arr);
		else 
			preg_match_all("/_n\">(.*?)<\/a>/s", $value, $arr);
		if(isset($arr[1][0]))
			$result[$key][3] = $arr[1][0];	
		else 
			unset($matches[0][$key]);
	}

	if(!isset($result)) {
		$result[0][0] = 0;
		$result[0][1] = 0;
		$result[0][2] = 0;
		$result[0][3] = 0;
	}

	return $result;
}

function take_next_url ($output) {
	
	preg_match_all("/dgn_btn_paging\">.*?<\/div>/s", $output, $matches_1);
	preg_match_all("/>\d</s", $matches_1[0][0], $matches_2);  //page 갯수 파악 위함
	preg_match_all("/search_pos=(-?\d.*?)&/s", $matches_1[0][0], $matches_3); //다음검색 주소 파악
	$result[0] = count($matches_2[0]); //page 갯수 입력
	$result[1] = $matches_3[1][count($matches_3[1])-1];  //다음검색 주소 입력

	return $result;
}

function print_result ($result) {
	echo "<table>";
	foreach ($result as $key => $value) {
			echo "<tr>";
			echo "<td style = 'width:100px;' >{$value[0]}</td>";
			echo "<td style = 'width:150px;' >{$value[1]}</td>";
			echo "<td><a href = \"http://gall.dcinside.com{$value[2]}\"  target='_blank' >{$value[3]}</a></td>";
			echo "</tr>";
	}
	echo "</table>";
}

echo "검색어 : ".$_GET['search'];

$count = 1; 

if(!isset($_GET['continue'])) {
	while (1) { //처음 검색눌렀을시 (왜 다른지 모르겠다 개씨발년들) 
		if($count == 1) //처음검색
			$url = "http://gall.dcinside.com".$sub_url."&s_type=search_subject&s_keyword=".$_GET['search'];
		else { //페이지 검색
			$url = "http://gall.dcinside.com".$sub_url."&page=".$count."&search_pos=&s_type=search_subject&s_keyword=".$_GET['search'];
		}		

		$ch = curl_init(); 
		curl_setopt($ch, CURLOPT_URL, $url); 	// set url 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		$output = curl_exec($ch); 
		curl_close($ch);

		$result = take_write($output, $past_board);

		if($count == 1)		//처음 검색시만 페이지 파악함
			$next_url = take_next_url($output);
		print_result ($result); //출력

		if($next_url[0] <= $count ) { //페이지 총량이 count보다 적으면 아래로 이동
			$count = 1; 
			break;
		} else 
			$count++;
	}
} else {
	$next_url[0] = 0;
	$next_url[1] = $_GET['continue'];
}


$is_done = 1; 
while (1) { //추가검색 진행
	
	if($count == 1) { //재검색
		$url = "http://gall.dcinside.com".$sub_url."&page=1&search_pos=".$next_url[1]."&s_type=search_subject&s_keyword=".$_GET['search'];
		$current_url = $next_url[1]; //이거 검색후로 next_url 변경되므로 미리 저장
	} else //페이지 검색
		$url = "http://gall.dcinside.com".$sub_url."&page=".$count."&search_pos=".$current_url."&s_type=search_subject&s_keyword=".$_GET['search'];
	
	$ch = curl_init(); 
	curl_setopt($ch, CURLOPT_URL, $url); 	// set url 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
	$output = curl_exec($ch); 
	curl_close($ch);

	$result = take_write($output, $past_board);
	if($count == 1)		//처음 검색이면 페이지 파악함
		$next_url = take_next_url($output);

	print_result ($result); //출력

	if($next_url[0] <= $count ) { //페이지 총량이 count보다 적으면 다음검색으로 이동
		$count = 1;
		if($is_done > 8)
				break;
	} else 
		$count++;

	$is_done++;
	sleep(1);

}

echo"	<td style = 'width:500px;'>
				<form id = 'get_page' method='get' action='/main/dcsearch'>
					<input type='hidden' name = 'search' value = '{$_GET['search']}'>
					<input type='hidden' name = 'continue' value = '{$next_url[1]}' >
					<input type='hidden' name = 'gallery' value = '{$_GET['gallery']}' >
					<input type='submit' value='계속검색''  style='width: 100px; height:40px;'>
				</form>
			</td>"; 
			
?>

	다른 검색어로 진행
	<td style = 'width:500px;'>
		<form id = 'get_page' method='get' action='/main/dcsearch'>
			검색어 입력 : 
			<input type='text' name = 'search' style = 'width:200px; height : 40px;'>
			<select name='gallery' style = 'width:200px; height : 40px; font-size:20px;'>
			   <option value='bitcoins'>비트코인 갤러리</option>
			   <option value='coin'>알트 마이너 갤러리</option>
			   <option value='satoshi'>사토시 마이너 갤러리</option>
			   <option value='revolution'>혁명 마이너 갤러리</option>
			</select>
			<input type='submit' value='검색하기''  style='width: 100px; height:40px;''>
		</form>
	</td>
