<script src="https://code.jquery.com/jquery-1.12.4.min.js" integrity="sha256-ZosEbRLbNQzLpnKIkEdrPv7lOy9C27hHQ+Xp8a4MxAQ=" crossorigin="anonymous"></script> 


<?php

// $str = '6ㅏk 가느당';
// if(preg_match('/\dㅏk/i', $str))
// 	echo "된다";
// else 
// 	echo "안된다";

// echo str_replace(" ", "", "띄 어 쓰 끼"); 
// $str = '123';
// echo substr($str, -1);
// echo Is_numeric(substr($str, -1));
// $str = 'awdawd';

?>

<body>


<a href="/main/startcheck/dc"><button style = 'font-size: 50px'>200개 기계 분석</button></a>

<button onclick = '$("body").scrollTop($(document).height());' style = 'margin-left: 100px; font-size: 50px'>맨 아래로</button></a>



<style>
	td{
		padding-top: 10px;
		min-width: 100px;
		text-align: center;
	}
</style>


<table>
	<tr>
		<th> 넘어감 </th>
		<th> 그룹 </th>
		<th style="width:500px;"> 내용 </th>
	</tr>

<?php
	foreach ($F_content as $key => $value) {

		echo "<tr>";
		echo "<td>
						<a href='/main/passcheck/dc/{$value['num']}'><button style = 'font-size: 12px'>넘어가기</button></a>
					</td>";
		echo "<td>{$location}</td>";
		echo "<td>{$value['title']}</td>";
		echo "</tr>";
	}	

?>

</table>

<br>
<br>
<b style = 'color:red;'>((이름 추가)) 및 ((속성 삭제)) 및 ((우선 순위)) 는 직접 할것</b>
<table>
	<tr>
		<th> 이름 </th>
		<th> 속성 </th>
		<th> 우선순위 </th>
		<th> 속성 추가</th>
	</tr>

<?php
	$i = 0;
	foreach ($coin_term as $key => $value) {
		$i++;
		echo "<tr>";
		echo "<td>{$key}</td>";
		echo "<td>{$value}</td>";
		echo "<td>{$i}</td>";
		echo "<td style = 'width:500px;'>
						<form id = 'get_page' method='get' action='/main/addattribute/{$location}'>
							<input type='hidden' name='name' value='{$key}'>
							<input type='text' name = 'attribute' style = 'width:100px; height : 30px;'>
							<input type='submit' value='저장''  style='width: 100px; height:40px;''>
						</form>
					</td>";
		echo "</tr>";
	}	

?>

</table>


</body>