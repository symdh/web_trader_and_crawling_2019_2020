<script src="https://code.jquery.com/jquery-1.12.4.min.js" integrity="sha256-ZosEbRLbNQzLpnKIkEdrPv7lOy9C27hHQ+Xp8a4MxAQ=" crossorigin="anonymous"></script> 

<?php defined('BASEPATH') OR exit('No direct script access allowed');
//write analysis page

	//글 가져오기
	function take_write($url, $index = '0') { //index는 몇번째 result 인지 구분해야됨
		$ch = curl_init(); 
		curl_setopt($ch, CURLOPT_URL, $url); 	// set url 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
		$output = curl_exec($ch); 

		preg_match_all("/<tr.*?<\/tr>/s", $output, $matches);

		//글 번호 뽑아내고(result) , 글 아닌것 삭제
		foreach ($matches[0] as $key => $value) {
			preg_match_all("/\d{7,}/s", $value, $arr);
			if(isset($arr[0][0]))
				$result[$key][0] = $arr[0][0];
			else 
				unset($matches[0][$key]);
		}

		//날짜 (result) (날짜는 get상태가 아니라면 현재 시간) - 어차피 +5분뒤에 저장됨
		if(isset($_GET['page'])) {
			foreach ($matches[0] as $key => $value) {
				preg_match_all("/\d{4,4}\.\d{1,2}\.\d{1,2}/s", $value, $arr);
				if(isset($arr[0][0])) {

					$arr[0][0]=str_replace(".","-",$arr[0][0]);
					$result[$key][1] =strtotime($arr[0][0]);
				}
				else 
					unset($matches[0][$key]);
			}
		} else {
			foreach ($matches[0] as $key => $value) {
				$result[$key][1] =strtotime(date("Y-m-d H:i:s",time()));
			}
		}

		//글 내용 뽑아냄 (result)
		foreach ($matches[0] as $key => $value) {
			preg_match_all("/middle;\">(.*?)<\/a>/s", $value, $arr);
			if(isset($arr[1][0]))
				$result[$key][1] = $result[$key][1].$arr[1][0];	
			else 
				unset($matches[0][$key]);
		}
		curl_close($ch);     

		foreach ($result as $key => $value) {
			$return_result[$key+$index*100] = $value;
		}
		if(!isset($return_result)) //에러시 0 반환
			return 0;

		return $return_result;
	}

	if(isset($_GET['page'])) {
		$url = "http://gall.dcinside.com/board/lists/?id=bitcoins&page=".$_GET['page'];
	} else {
		$url = "http://gall.dcinside.com/board/lists/?id=bitcoins&page=1";
	}

	$result = take_write($url,0);
	if($result  === 0) { //에러 상황일때, 600초 후 reload, 정지되므로 log저장 없음
		echo "
<script>
$(document).ready(function(){

	//page 과거글 저장 상태 아닐때 실행
	$('#state').html(\"<div style ='width:300px;height:100px;'>600초후 자동 새로고침</div>\");
	var time = 600;
	window.setInterval(function(){
	  time = time - 10;
	  if(time == 0) {
	   $('form[name=save]').submit();
	   $('#state').html(\"<div style ='width:300px;height:100px;'>10초후 새로 고침</div>\");
	  	setTimeout(function() {
	  		location.reload();
		}, 10000);
	  } else {
	  	 $('#state').html(\"<div style ='width:300px;height:100px;'>\"+time+\"초후 자동 새로고침</div>\");
	  }
	}, 10000);

});
</script>
<div id = state> </div>
";
		return 0;
	}
	//내용 보이기
	print_r($result);
	// print_r($matches);

	

?>
<script>
//페이지가 없을때 아래문이 실행됨 
function upload_write (formData) {
	//console.log(formData);

	var Data = $(formData).serialize(); //post 데이터
	Data[$('#ci_t').attr('name')] = $('#ci_t').val();
	var ajax_url = formData.action;

	$.ajax({
		url: ajax_url,
		dataType: "html",
		processData: true,
		contentType: 'application/x-www-form-urlencoded',
		data: Data,
		type: 'POST',
		success: function(result){	
			//console.log(result[0]['answer_content']);
			var now = new Date(); 
         	var nowTime = now.getDate() + "일 " + now.getHours() + "시 " + now.getMinutes() + "분 " + now.getSeconds() + "초";


			//하나라도 존재할때 실행
			if (result.length != 0) {
				$('#success').html("<div style ='width:300px;height:100px;'>"+nowTime+" "+result+"</div>");
			} 
		}, error : function(request, status, error ) {   // 오류가 발생했을 때 호출된다. 
			console.log("code:"+request.status+"\n"+"message:"+request.responseText+"\n"+"error:"+error);
			$('#fail').html("<div style ='width:300px;height:100px;'>에러있음</div>");
		}
	});


	return false;
}
</script>






<!-- 5분 설정되어 있는데   글 리젠이 5분이 넘어갈 경우. -->
<!-- 중간에 에러가 날 경우, 기록이 필요 -->






	<form name="save" method="post" action="/Wdata/save" onsubmit = 'upload_write(this); return false; '>
		<input type='hidden' name='<?=$this->security->get_csrf_token_name()?>' value='<?=$this->security->get_csrf_hash()?>' />


<?php
		echo "<input name = 'location' type='hidden' value='dc_analysis'>";

		foreach ($result as $key => $value) {
			echo "<input name = '{$value[0]}' type='hidden' value='{$value[1]}'>";
		}
?>

		<input type='submit' value="내용 즉시 저장"  style="width: 200px; height:100px;">
	</form>	


	<form id = 'get_page' method="get" action="/main/wanalysis">

<?php
	if(!isset($_GET['page'])) {
		echo "<input type='hidden' name='page' value='1'>";
	} else {
		$page = $_GET['page'] + 1 ;
		echo "<input type='hidden' name='page' value='{$page}'>";
	}
?>
		<input type='submit' value="6000page 까지 내용 저장 시작 "  style="width: 200px; height:100px;">
	</form>




	<div id = 'success'>


	</div>

	<div style = 'color:red;' id = 'fail'>
		

	</div>

	<div style = 'color:blue;' id = 'state'>
		

	</div>









<script>

var getParameters = function (paramName) { //get 변수 가져오기.
	var returnValue; // 리턴값을 위한 변수 선언
	var url = location.href; // 현재 URL 가져오기

		// get 파라미터 값을 가져올 수 있는 ? 를 기점으로 slice 한 후 split 으로 나눔
	var parameters = (url.slice(url.indexOf('?') + 1, url.length)).split('&');
		// 나누어진 값의 비교를 통해 paramName 으로 요청된 데이터의 값만 return
	for (var i = 0; i < parameters.length; i++) {
		var varName = parameters[i].split('=')[0];
		if (varName.toUpperCase() == paramName.toUpperCase()) {
			returnValue = parameters[i].split('=')[1];
			return decodeURIComponent(returnValue);
		}
	}

	//값이 없으면 리턴0
	returnValue = '0'
	return 0;	

	//get 없으면 input에 넣음.. 
	// var search_query = getParameters('query');
	// if( search_query != '0' ) {
	// 	$('input[name=query]').val(search_query);
	// }
};


$(document).ready(function(){
	var get_page = getParameters('page');

	if(get_page == '6000') {
		return 0;
	}

	if(get_page != '0') {
		$('#state').html("<div style ='width:300px;height:100px;'>page 발견 3초 후 자동 실행</div>");
		setTimeout(function() {
	  		$('form[name=save]').submit();
	  		$('#state').html("<div style ='width:300px;height:100px;'>3초 후 page 이동</div>");
	  		setTimeout(function() {
	  			$('#get_page').submit();
			}, 3000);
		}, 3000);

		return 0;
	}

	//page 과거글 저장 상태 아닐때 실행
	$('#state').html("<div style ='width:300px;height:100px;'>60초후 자동 저장</div>");
	var time = 60;
	window.setInterval(function(){
	  time = time - 10;
	  if(time == 0) {
	   $('form[name=save]').submit();
	   $('#state').html("<div style ='width:300px;height:100px;'>10초후 새로 고침</div>");
	  	setTimeout(function() {
	  		location.reload();
		}, 10000);
	  } else {
	  	 $('#state').html("<div style ='width:300px;height:100px;'>"+time+"초후 자동 저장</div>");
	  }
	}, 10000);

});

</script>