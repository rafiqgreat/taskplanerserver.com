<?php
	$localdt = "<script type='text/javascript'> var date = new Date(); var dateStr2 =  date.getFullYear() + '-' + ('00' + (date.getMonth() + 1)).slice(-2) + '-' +  ('00' + date.getDate()).slice(-2) + ' ' +   ('00' + date.getHours()).slice(-2) + ':' + ('00' + date.getMinutes()).slice(-2) + ':' + ('00' + date.getSeconds()).slice(-2); document.write(dateStr2); </script>"; 
echo $localdt;
?>
