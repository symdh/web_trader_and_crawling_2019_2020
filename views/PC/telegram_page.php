
<style>
form {
	margin:0px;
}
</style>


<script src="https://code.jquery.com/jquery-1.12.4.min.js" integrity="sha256-ZosEbRLbNQzLpnKIkEdrPv7lOy9C27hHQ+Xp8a4MxAQ=" crossorigin="anonymous"></script> 

<script type="text/javascript">

function ajax_list (e)  {
	var formData = $(e).serialize();

    $.ajax({
        url: $(e).attr('action'),
        dataType: "text",
        processData: true,
        contentType: 'application/x-www-form-urlencoded',
        data: formData,
        type: 'POST',
        success: function(result){

        		if(result == 0) { //실패시
        			alert("0 반환됨");
        		} else if(result == 1) { //등록 성공시
        			var token_name =$(e).children("input[name=token_name]").val();
        			$('#added_list').after("<div style = 'color:black;'>"+

        					"<div style = 'width: 500px; float:left; text-align:right; padding-right:50px;'>" +$(e).children("input[name=title]").val()+ "</div>"+
						"<form action = '/telegram/delete/"+$(e).children("input[name=id]").val()+"' method='post' onsubmit = 'ajax_list(this); return false;'>"+
							"<input type='hidden' name='"+token_name+"' value='" + $(e).children("input[name=" +token_name+ "]").val() + "' />"+
							"<button> 삭제하기</button>"+
						"</form>"+
						"</div>"+
        				"</div>");
        		} else if(result == 2) { //삭제 성공시
	     			$(e).parent().remove();
	     		} else if(result == 3) { //업데이트 성공시
	     			var num = $(e).children("input[name=num]").val();
	     			var content  = $('#' + num ).html();
	     			 $('#' + num ).html( content + "|" +$(e).children("input[name=coin]").val() );
	     		}

        },
        error:function(){
         	alert("코드오류 or 통신불량으로 실패");
        }	
    });    
}

</script>

<div style="margin-right: 10px; width:150px; float: left">
	<form action = '/telegram/list' >
		<input type="hidden" name = "mode" value="1">
		<button style = 'width:150px; height : 80px; font-size: 20px;'> list 추가/삭제 </button>
	</form>
</div>

<div style="margin-right: 10px; width:150px; float: left">
	<form action = '/telegram/list' >
		<input type="hidden" name = "mode" value="2">
		<button style = 'width:150px; height : 80px; font-size: 20px;'> 코인 분류 추가/삭제 </button>
	</form>
</div>

<div style="margin-right: 10px; width:150px; float: left">
	<form action = '/telegram/list' >
		<input type="hidden" name = "mode" value="3">
		<button style = 'width:150px; height : 80px; font-size: 20px;'> 채널 추가/삭제 </button>
	</form>
</div>

<div style="margin-right: 10px; width:150px;  float: left">
	<form action = '/telegram/list' style="width:150px;">
		<input type="hidden" name = "mode" value="5">
		<button style = 'width:150px; height : 80px; font-size: 20px;'> 결과확인 </button>
	</form>
</div>

<div style="margin-right: 10px; width:150px;  float: left">
	<form action = '/telegram/list' style="width:150px;">
		<input type="hidden" name = "mode" value="6">
		<button style = 'width:150px; height : 80px; font-size: 20px;'> 채널 없는거 가져오기 </button>
	</form>
</div>

<div style="margin-right: 10px; width:150px;  float: left">
	<form action = '/telegram/list' style="width:150px;">
		<input type="hidden" name = "mode" value="7">
		<button style = 'width:150px; height : 80px; font-size: 20px;'> 채널 있고 코인없는거 가져옴 </button>
	</form>
</div>

<div style="margin-right: 10px; width:150px;  float: left">
	<form action = '/telegram/list' style="width:150px;">
		<input type="hidden" name = "mode" value="8">
		<button style = 'width:150px; height : 80px; font-size: 20px;'> 채널, 코인 있고 포지션없는거 가져옴 </button>
	</form>
</div>

<?php 
	$current_time = strtotime('now');
	$current_time = date('H시 i분');
	echo "<button style = 'width:150px; height : 80px; font-size: 20px;'> {$current_time}</button>";

?>

<div style="clear : both"> </div>

<?php  

if(!isset($_GET['mode'])) {
	echo "버튼을 선택해주세요!!";
} else if($_GET['mode'] == 1) {

// ㅅㅂ bot이 아닌 user 로그인으로 해야됨 ㅡ

if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';

$MadelineProto = new \danog\MadelineProto\API('session.madeline');
$MadelineProto->async(true);
$MadelineProto->loop(function () use ($MadelineProto) {
   	yield $MadelineProto->start();

   	$me = yield $MadelineProto->getSelf();
   	$MadelineProto->logger($me);
   	yield $MadelineProto->echo('OK, done!');


	$dialogs = yield $MadelineProto->getFullDialogs();
	foreach ($dialogs as $dialog) {
		// print_r($dialog);
	    $MadelineProto->logger($dialog);
	}
	// print_r($MadelineProto->API->chats);


// $peer = array(
// 	'_' => "peerChannel",
// 	'channel_id' => ""
// );

// 	$Chat = yield $MadelineProto->getFullInfo($peer);

// 	print_r($Chat);

});


echo " <div id = 'added_list' style = 'color:red;'>등록된 채널 </div>";
foreach ($load_list as $key => $value) {
	echo "<div>
					<div style = 'width: 300px; float:left; text-align:right; padding-right:50px;'> {$value['title']} </div>
						<form action = '/telegram/delete/{$value{'id'}}' method='post' onsubmit = 'ajax_list(this); return false;'>
							<input type='hidden' name='".$this->security->get_csrf_token_name()."' value='".$this->security->get_csrf_hash()."' />
							<button> 삭제하기</button>
						</form>

					</div>
				</div>
				";				
}

echo " <div id = 'added_list' style = 'color:red;'> 채널 목록</div>";
foreach ($MadelineProto->API->chats as $bot_api_id => $chat) {
	if(isset($chat) && $chat['_'] == 'channel') {

		//print_r($chat);
		echo "<div style = 'width: 500px; float:left;'> 제목 : {$chat['title']} </div>";
		echo "<div style = 'width: 200px; float:left;'> id : {$chat['id']} </div>";
		echo "<div style = 'width: 200px; float:left; height : 25px; '>
						<form action = '/telegram/add/{$chat{'id'}}' method='post' onsubmit = 'ajax_list(this); return false;'>
							<input type='hidden' name='id' value='{$chat['id']}'>
							<input type='hidden' name='title' value='{$chat['title']}'>
							<input type='hidden' name='token_name' value='".$this->security->get_csrf_token_name()."'>
							<input type='hidden' name='".$this->security->get_csrf_token_name()."' value='".$this->security->get_csrf_hash()."' />
							<button> 추가하기</button>
						</form>
					</div>
					";
		//if(isset($chat['username']))
			//echo "<div style = 'width: 400px; float:left;'> username : {$chat['username']} </div>";
		echo "<br>";
	}
}

//모든 채팅 목록을 가져 오는 두 가지 방법
///1
// $dialogs = $MadelineProto->get_dialogs();
// // print_r($dialogs);
// foreach ($dialogs as $peer) {
// 	print_r($peer);
//     // $MadelineProto->messages->sendMessage(['peer' => $peer, 'message' => 'Hi! Testing MadelineProto broadcasting!']);
// }
///2 (이걸로 사용합시다)


// $peer = array(
// 	'_' => "peerChannel",
// 	'channel_id' => "1284864364"
// );
// $messages_Messages = $MadelineProto->messages->getUnreadMentions(['peer' => $peer , 'offset_id' => 0, 'add_offset' => 0, 'limit' => 1, 'max_id' => 100, 'min_id' => 1, ]);
// print_r($messages_Messages);



// $messages_Messages = $MadelineProto->messages->getHistory(['peer' => $peer , 'offset_id' => 0, 'offset_date' => 0 , 'add_offset' => 0, 'limit' => 10, 'max_id' => 3571, 'min_id' => 3160 ]);
// // print_r($messages_Messages );

// print_r($messages_Messages);


//////////////////////진행할 내용//////////////////////
//////////////////////1. peer 선언 (모든 채팅 목록을 가져 오는 두 가지 방법)
//////////////////////2. 가져와서 db넣기
//////////////////////3. 시간기준으로 비교하여 최신순 들어가는지 확인할것
//////////////////////4. 분류작업
//////////////////////5. 끝... ㅅㅅ


// $peer = array(
// 	'_' => "peerChannel",
// 	'channel_id' => "1395359084"
// );

// $messages_Messages = $MadelineProto->messages->getHistory(['peer' => $peer , 'offset_id' => 0, 'offset_date' => 0 , 'add_offset' => 0, 'limit' => 10, 'max_id' =>0 , 'min_id' => 0  ]);
// print_r($messages_Messages );

// ㅡㅡ 모두 0 으로 하면 최신순으로 온다.
// // 'offset_id' => 7, 'offset_date' => 중간시간  기준 이하로 들고옴. 그때부터 불러오는것이 아닌 그때까지 불러옴
// 	// offset_id = 0 이면 기준 없음. offset_date = 0 이면 기준없음 
// // 그때 이후로 최대 n개 까지 들고오라는 문을 짤 수 있음
// // 마지막 메세지 id값만 들고 오면 충분히 구현 가능
// foreach ($messages_Messages['messages'] as $key => $value) {
// 	print_r($value);
// 	echo "<br>";
// }





// // 메세지 번호 불러오는것 'id' => [2,7] //2번 5번 메세지를 불러옴 
// $messages_Messages = $MadelineProto->channels->getMessages(['channel' => '@koreasigna', 'id' => [2010, 2152], ]);
// print_r($messages_Messages);
// $messages_Messages = $MadelineProto->messages->getHistory(['peer' => InputPeer, 'offset_id' => int, 'offset_date' => int, 'add_offset' => int, 'limit' => int, 'max_id' => int, 'min_id' => int, 'hash' => [int, int], ]);


// $messages_Messages = $MadelineProto->messages->getHistory(['peer' => 'https://t.me/korinside',  'offset_date' => 0 ,'offset_id' => 1, 'add_offset' => 1, 'max_id' => 100, 'min_id' =>1, 'limit' => 10 ]);
// print_r($messages_Messages);


//메세지 번호 불러오는것 'id' => [2,7] //2번 5번 메세지를 불러옴 
// $messages_Messages = $MadelineProto->channels->getMessages(['channel' => '@korinside', 'id' => [2, 7], ]);
// print_r($messages_Messages);


//메세지 보내기 (개인및 그룹 모두 잘 작동함) 
/*
$me = $MadelineProto->get_self();
\danog\MadelineProto\Logger::log($me);
if (!$me['bot']) {
    $MadelineProto->messages->sendMessage(['peer' => '@symdh_bot', 'message' => "Hi!\nThanks for creating MadelineProto! <3"]);
    $MadelineProto->channels->joinChannel(['channel' => '@korinside']);
    $MadelineProto->messages->sendMessage(['peer' => 'https://t.me/korinside', 'message' => 'Testing MadelineProto!']);
}
echo 'OK, done!'.PHP_EOL;

*/

///pear 정리 // peer의 형식은 3가지임 @---, join 링크, array형식, 채널 id
	//1. array 형식//
	// $peer = array(
	// 	'_' => "peerChannel",
	// 	'channel_id' => "1395359084"
	// );
	// 그외//
	// if (!$me['bot']) {
	//     $MadelineProto->messages->sendMessage(['peer' => '@symdh_bot', 'message' => "Hi!\nThanks for creating MadelineProto! <3"]);
	//     $MadelineProto->channels->joinChannel(['channel' => '@korinside']);
	//     $MadelineProto->messages->sendMessage(['peer' => 'https://t.me/korinside', 'message' => 'Testing MadelineProto!']);
	// }
	//$MadelineProto->messages->sendMessage(['peer' => '12412312', 'message' => "Hi $bot_api_id! Testing MadelineProto broadcasting!"]);

} else if ($_GET['mode'] == 2) { //코인 등록 삭제

echo " <div id = 'added_list' style = 'color:red;'>등록된 코인 </div>";
foreach ($load_list as $key => $value) {
	echo "<div style = 'height : 25px; margin-bottom : 5px;'>
					<div style = 'width: 100px; float:left; text-align:right; padding-right:50px;'> {$value['title']} </div>
					<div id = '{$value['num']}' style = 'width: 300px; float:left; text-align:right; padding-right:50px;'>{$value['coin']}</div>

					<div style='margin-right: 100px; width:200px; float: left'>
						<form action = '/telegram/update/{$value{'num'}}' method='post' onsubmit = 'ajax_list(this); return false;'>
							<input type='hidden' name='num' value='{$value['num']}' />
							<input type='hidden' name='".$this->security->get_csrf_token_name()."' value='".$this->security->get_csrf_hash()."' />
							<input style='margin-right: 10px; width:100px; float: left' type='input' name='coin' />
							<button> 업데이트 </button>
						</form>
					</div>

					<div style='margin-right: 10px; width:150px; float: left'>
						<form action = '/telegram/delete/{$value{'num'}}' method='post' onsubmit = 'ajax_list(this); return false;'>
							<input type='hidden' name='num' value='{$value['num']}' />
							<input type='hidden' name='".$this->security->get_csrf_token_name()."' value='".$this->security->get_csrf_hash()."' />
							<button> 삭제하기</button>
						</form>
					</div>
				</div>
				";
}

echo " <div id = 'added_list' style = 'color:red;'>새로 등록할 코인 </div>";
echo "
<div style='margin-right: 10px; width:150px; float: left'>
	<form action = '/' method='post' onsubmit = \"$(this).attr('action', '/telegram/add/' + $(this).children('input[name=coin]').val() ) ; return true;\">
		코인 번역 <input type='input' name = 'title'/>
		<br>
		코인 영자 (영자만 쓸것) <input type='input' name = 'coin'/>
		<input type='hidden' name='".$this->security->get_csrf_token_name()."' value='".$this->security->get_csrf_hash()."' />
		<button> 등록하기 (ajax 하기 복잡해서 효용성없어서 새로고침됨</button>
	</form>
</div>


";




} else if ($_GET['mode'] == 3)  { //채널 등록 ㄱㄱ
	echo " <div id = 'added_list' style = 'color:red;'>등록된 채널 </div>";
	foreach ($load_list as $key => $value) {
		echo "<div style = 'height : 25px; margin-bottom : 5px;'>
						<div style = 'width: 100px; float:left; text-align:right; padding-right:50px;'> {$value['title']} </div>
						
						<div style='margin-right: 10px; width:150px; float: left'>
							<form action = '/telegram/delete/{$value{'num'}}' method='post' onsubmit = 'ajax_list(this); return false;'>
								<input type='hidden' name='num' value='{$value['num']}' />
								<input type='hidden' name='".$this->security->get_csrf_token_name()."' value='".$this->security->get_csrf_hash()."' />
								<button> 삭제하기</button>
							</form>
						</div>
					</div>
					";
	}

	echo " <div id = 'added_list' style = 'color:red;'>새로 등록할 코인 </div>";
	echo "
	<div style='margin-right: 10px; width:150px; float: left'>
		<form action = '/' method='post' onsubmit = \"$(this).attr('action', '/telegram/add/' + $(this).children('input[name=chat]').val() ) ; return true;\">
			채널 등록 (구분 가능한 문자로만 쓸것) <input type='input' name = 'chat'/>
			<input type='hidden' name='".$this->security->get_csrf_token_name()."' value='".$this->security->get_csrf_hash()."' />
			<button> 등록하기 (ajax 하기 복잡해서 효용성없어서 새로고침됨</button>
		</form>
	</div>


	";



	
} else if ($_GET['mode'] == 5)  { //리스트를 바탕으로 글을 가져온다.
	//(결과 확인)

	$current_time = strtotime('now');
	foreach ($load_list as $key => $value) {
		$past_time = $current_time - $value['time'];
		$time = (($past_time/60/60/24)%7)."일 ".(($past_time/60/60)%24)."시간".(($past_time/60)%60)."분".($past_time%60)."초 전";

		echo "<div style = 'width:120px; height:30px; float:left'>".substr($value['title'],0,15)."</div>";
		echo "<div style = 'width:120px; height:30px; float:left'>#".$value['chat']."</div>";
		echo "<div style = 'width:120px; height:30px; float:left'>".$value['coin']."</div>";
		echo "<div style = 'width:80px; height:30px; float:left'>".$value['position']."</div>";
		echo "<div style = 'width:200px;  height:30px; float:left'>".$time."</div>";
		echo "<div style = 'width:1000px; float:left;  '>".$value['content']."</div>";
		echo "<div style = 'clear:both;'> </div>";
	}

} else if ($_GET['mode'] == 6)  {  //채널 없는거 가져오기

	$current_time = strtotime('now');
	foreach ($load_list as $key => $value) {
		$past_time = $current_time - $value['time'];
		$time = (($past_time/60/60/24)%7)."일 ".(($past_time/60/60)%24)."시간".(($past_time/60)%60)."분".($past_time%60)."초 전";

		echo "<div style = 'width:120px; height:30px; float:left'>".substr($value['title'],0,15)."</div>";
		echo "<div style = 'width:120px; height:30px; float:left'>#".$value['chat']."</div>";
		echo "<div style = 'width:120px; height:30px; float:left'>".$value['coin']."</div>";
		echo "<div style = 'width:80px; height:30px; float:left'>".$value['position']."</div>";
		echo "<div style = 'width:200px;  height:30px; float:left'>".$time."</div>";
		echo "<div style = 'width:1000px; float:left;  '>".$value['content']."</div>";
		echo "<div style = 'clear:both;'> </div>";
	} 

} else if ($_GET['mode'] == 7)  { // 채널은 있고 코인 없는거 가져오기
	$current_time = strtotime('now');
	foreach ($load_list as $key => $value) {
		$past_time = $current_time - $value['time'];
		$time = (($past_time/60/60/24)%7)."일 ".(($past_time/60/60)%24)."시간".(($past_time/60)%60)."분".($past_time%60)."초 전";

		echo "<div style = 'width:120px; height:30px; float:left'>".substr($value['title'],0,15)."</div>";
		echo "<div style = 'width:120px; height:30px; float:left'>#".$value['chat']."</div>";
		echo "<div style = 'width:120px; height:30px; float:left'>".$value['coin']."</div>";
		echo "<div style = 'width:80px; height:30px; float:left'>".$value['position']."</div>";
		echo "<div style = 'width:200px;  height:30px; float:left'>".$time."</div>";
		echo "<div style = 'width:1000px; float:left;  '>".$value['content']."</div>";
		echo "<div style = 'clear:both;'> </div>";
	} 

} else if ($_GET['mode'] == 8)  { // 채널, 코인은 있고 포지션 없는거 가져오기
	$current_time = strtotime('now');
	foreach ($load_list as $key => $value) {
		$past_time = $current_time - $value['time'];
		$time = (($past_time/60/60/24)%7)."일 ".(($past_time/60/60)%24)."시간".(($past_time/60)%60)."분".($past_time%60)."초 전";

		echo "<div style = 'width:120px; height:30px; float:left'>".substr($value['title'],0,15)."</div>";
		echo "<div style = 'width:120px; height:30px; float:left'>#".$value['chat']."</div>";
		echo "<div style = 'width:120px; height:30px; float:left'>".$value['coin']."</div>";
		echo "<div style = 'width:80px; height:30px; float:left'>".$value['position']."</div>";
		echo "<div style = 'width:200px;  height:30px; float:left'>".$time."</div>";
		echo "<div style = 'width:1000px; float:left;  '>".$value['content']."</div>";
		echo "<div style = 'clear:both;'> </div>";
	} 

}




















































































































	// $url = 'https://web.telegram.org/#/im?p=@symdh_bot';
	// $ch = curl_init(); 
	// curl_setopt($ch, CURLOPT_URL, $url); 	// set url 

	// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
 // 	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); 
 // 	curl_setopt($ch, CURLOPT_HEADER, 0); 
 // 	curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); 
 // 	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
 // 	curl_setopt($ch, CURLOPT_TIMEOUT, 5000);

 // 	$output = curl_exec($ch); 
 // 	print_r($output);
 // 	curl_close($ch); 



 	// preg_match_all("/entry-thumb\">(.*?)<\/div>/s", $output, $matches_1);
 	// 	//처음공지만 뽑아내면됨 
 	// preg_match_all("/archives\/(\d{5,})/s", $matches_1[1][0], $matches_2);
 	
 	// if(isset($matches_2[1][0])) {
 	// 	print_r($matches_1[1][0]);
 	// }
 	
 	// if(isset($matches_2[1][0]) && $matches_2[1][0] != 36744 )  {
 		// echo "<audio id='music' controls autoplay loop>
			// 		<source src = '/static/sound/sound1.mp3' type = 'audio/mpeg' />
			// 		</audio>";
 	// 	echo "<div id = 'blink' style = 'font-size : 30; color:red;'> <br>새로운 공지 뜸!!!<br> </div>";
 	// }

 	// $current_time = "마지막 새로고침: ".date('H시 i분');
 	// echo "<div>{$current_time}</div>";

?>


