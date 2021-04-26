<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <!--  This file has been downloaded from https://bootdey.com  -->
    <!--  All snippets are MIT license https://bootdey.com/license -->
    <title>Task Planner</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="icon2.gif" type="image/gif" sizes="16x16">
    <link href="assets/dist/filepond.css" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
    <link href="https://netdna.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">   
    <link href="https://use.fontawesome.com/releases/v5.0.6/css/all.css" rel="stylesheet">
    <link rel="stylesheet" href="//cdn.materialdesignicons.com/3.7.95/css/materialdesignicons.min.css">

<script src="assets/dist/jquery.min.js"></script>
<link rel="stylesheet" href="assets/dist/jquery.fancybox.min.css" />
<script src="assets/dist/jquery.fancybox.min.js"></script>
    
  <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.css">
  <script src="//code.jquery.com/jquery-1.12.4.js"></script>
  <script src="//code.jquery.com/ui/1.12.1/jquery-ui.js"></script>


    <style>
.menu-heading{display: inline-block;width: 100%;text-decoration: none;position: relative;}
.badge-pill{float: right;
    margin-left: 5px;color: #212529;
    background-color: #ffc107;padding-right: .6em;
    padding-left: .6em;
    border-radius: 10rem;display: inline-block;
    padding: .25em .4em;
   
    font-weight: 700;
    line-height: 1;
    text-align: center;
    white-space: nowrap;
    vertical-align: baseline;}
.menu-icons{font-size: 12px;
    line-height: 30px;
    text-align: center;
	
    border-radius: 4px;}
	
/*********************/
.toshow { 
	display:block;
   /* position: absolute; 
	margin-left:105px;*/
    background:none; 
    width: 200px; 
}

 .nav-link span:first-child {
	 width:100%;
	 }

</style>

<script type="text/javascript">
var countassignmeDD="";
var countassignotherDD="";
var countassignpersonalDD="";
var countassignccDD="";
var countershipment = 0;
var loggedMobile = '';
var names = '';
	
	 
$(document).ready( function() 
{		
// for notificaiton popup window right bottom
checknotif();
setInterval(function(){ checknotif(); }, 1800000);
function checknotif() {
	if (!Notification) {
		$('body').append('<h4 style="color:red">*Browser does not support Web Notification</h4>');
		return;
	}
	if (Notification.permission !== "granted")
	{
		//console.log('No Permission');
		Notification.requestPermission();
	}
	else {
		$.ajax(
		{
			url : "<?php echo $url; ?>api2/tasks/get-popnotifications?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>",
			type: "GET",
			 dataType: 'json',
            cache: false,
			success: function(data)
			{
				 var data = JSON.parse(data.DATA);
				 //console.log(data);
				if(data.result == true){
					var data_notif = data.notif;
					console.log(data_notif);
					for (var i = data_notif.length - 1; i >= 0; i--) {
						var theurl = data_notif[i]['url'];
						var notifikasi = new Notification(data_notif[i]['title'], {
							icon: data_notif[i]['icon'],
							body: data_notif[i]['msg'],
						});
						notifikasi.onclick = function () {
							window.open(theurl); 
							//notifikasi.close();     
						};
						//setTimeout(function(){ notifikasi.close(); }, 3000000);
					};
				}else{

				}
			},
			error: function(jqXHR, textStatus, errorThrown)
			{

			}
		});	

	} };

//COUNTER - Fetch project members
$.ajax({
	url: '<?php echo $url; ?>api2/projects/fetch-member-projects?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>',
	type: 'GET',
    dataType: 'json',
    cache: false,
    success: function (data) 
	{                
    	submenuMyProjects.empty();
       	if(data.STATUS !== 'ERROR') 
		{
			var data = JSON.parse(data.DATA);
			document.getElementById("projectTaskCount").innerHTML = data.length;
        } 
		else 
		{
             document.getElementById("projectTaskCount").innerHTML = '0';
        }
	}
}); 

names = new Array();
$.ajax({
	url: '<?php echo $url; ?>api2/projects/get-registered-users',
    type: 'POST',
	contentType:'application/x-www-form-urlencoded',
    data:"STATUS=ACTIVE",		
    success: function(data) 
	{                       
       if(data.STATUS !== 'ERROR') 
	   {
		   var d = JSON.parse(data.DATA);		   				
		    for(var i = 0; i < d.length; i++)
			{
				names[i] = d[i].FULL_NAME+'('+d[i].MOBILE_NUMBER+')';
			}
	   }                  
	}
});
		
$( "#autocomplete" ).autocomplete({	
  source: names
});
$( "#AUTO_ASSIGNED_ID" ).autocomplete({	
  source: names
});
$( "#AUTO_CC" ).autocomplete({	
  source: names
});

$("#frm").on("submit", function()
{
	//console.log(this);
	$('#breadcrumb').html('Home &raquo; Searched Tasks');
	
	var rdValue = $("input[name='rd']:checked"). val();
	var keyValue = $("input[id='key']"). val();
	var url = '<?php echo $url; ?>api2/tasks/search-tasks?TASK_TITLE='+keyValue+'&USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE='+rdValue;
 	$.ajax({		
            url: url,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) 
			{
            	taskList.empty();
				taskDetail.empty();
				if(data.STATUS !== 'ERROR') 
				{
            		var data = JSON.parse(data.DATA);
					if(rdValue=='sh')
					{
						showSearchTaskListShipmentsWhite(data);
						funshowUsersShipmentsSearched(keyValue);
					}
					else
					{
						taskList.append('<div class="mail-list" style="background: grey;color:white;"><h6 style="margin:0px">Total '+parseInt(data.length)+' Searched Tasks  </h6></div>');
						//Assigned to me 
						taskList.append('<div class="mail-list" style="background-color:#f5f2ec;color:#067d1a; border:1px solid #ada6a6;"><h6 style="margin:0px">Assigned To Me Tasks (<span id="srch_1">0</span> Tasks)</h6></div>');
						var srch_1c = 0;
						for (var i = 0; i < data.length; i++) 
						{							
						if(data[i].TYPE_ORDER == 1)
							{	
							srch_1c++;
								
							var duedatae = convertDate(data[i].DUE_DATE);
							//alert(toTimestamp(duedatae));
							var openvar = "";
							var closevar = "";
							var completevar = "";
							var inprogressvar = "";
							switch(data[i].TASK_STATUS)
							{
								case "OPEN":
									openvar = " selected='selected'";
								break;
								case "CLOSED":
									closevar = " selected='selected'";
								break;
								case "COMPLETED":
									completevar = " selected='selected'";
								break;
								case "IN PROGRESS":
									inprogressvar = " selected='selected'";
								break;
							}
							var duedatae = convertDate(data[i].DUE_DATE)
							if(duedatae=="1 Jan 1970 5:0 AM"){duedatae="";}
							if(duedatae=="31 Dec 1969 6:0 PM"){duedatae="";} // new added by rafiq
							var notificationStatusYes="";
							var notificationStatusNo="";
							if(data[i].STATUS==1){notificationStatusYes = " checked"}
							else{notificationStatusNo = " checked"}
							var output = "";
							var repint = data[i].REPEAT_INTERVAL;
							var arrIntervals = repint.split(',');
							var arrayLength = arrIntervals.length;
							for (var x = 0; x < arrayLength; x++) 
							{
								switch(arrIntervals[x])
								{
									case '0': 
										output += "Mon, ";
										break;
									case '1': 
										output += "Tue, ";
										break;
									case '2': 
										output += "Wed, ";
										break;
									case '3': 
										output += "Thu, ";
										break;
									case '4': 
										output += "Fri, ";
										break;
									case '5': 
										output += "Sat, ";
										break;
									case '6': 
										output += "Sun, ";
										break;
									case '7': 
										output += "Month";
										break;
								}
							}
							if(repint == "0,1,2,3,4,5,6") output = "Every Day";
							if(output == "") output = "Never";
							var strstyleDD=' style="color:#ffd80b; font-weight:600; text-transform:capitalize" ';
							var timecheck = timeSince(new Date(duedatae));
							if (timecheck = timecheck.includes("ago"))
							{
								strstyleDD =' style="color:red; font-weight:600; text-transform:capitalize" ';
							}
							var anchortag = '<a href="javascript:changeStatusPRR(\'COMPLETED\','+data[i].TASK_ID+');" style="font-size:12px;"><img src="assets/images/mark-completed.gif" id="markascompleted'+data[i].TASK_ID+'" /></a>';								
							if(data[i].TASK_STATUS == 'COMPLETED')
							{				
								anchortag = '<a href="#" style="font-size:12px;"><img src="assets/images/completed-task.gif" id="completedtask"/></a>';
							}
							var notificationButton = '<img src="assets/images/btn-off.png" id="imgoff" onclick="changeNotificationStatusPRRT(1,' + data[i].TASK_ID + ');" />';
							if(data[i].STATUS==1){notificationButton = '<img src="assets/images/btn-on.png" id="imgon" onclick="changeNotificationStatusPRRT(0,' + data[i].TASK_ID + ');" />';}
							
							var duedatae = convertDate(data[i].DUE_DATE);
							if(duedatae=="1 Jan 1970 5:0 AM"){duedatae="";}
							if(duedatae=="31 Dec 1969 6:0 PM"){duedatae="";}
							if(duedatae=="0"){duedatae="";}
							
							if(duedatae == '') { duedatae = '<small>No Due Date</small>';} else { duedatae = timeSince(new Date(duedatae)); }
							
							
							taskList.append('<div class="mail-list taskDetailsCCtaskDD" data-task_id="' + data[i].TASK_ID + '">' +
												'<div class="content" style="width:97%;">'+
												'<div class="row"><div class="col-md-8"><p class="message_text" '+ strstyleDD +'>' + data[i].TASK_TITLE + '</p></div><div class="col-md-4" ><small>Creator: </small> <span style="color:#346fa1; font-size:12px;font-weight:bold;">'+data[i].FULL_NAME+'</span></div></div>' + 
												'<div class="row"><div class="col-md-8"><p class="message_text">'+duedatae+'</p></div><div class="col-md-4" ><small>Status: </small>' + data[i].TASK_STATUS + '</div></div>'+ 
												'<div class="row"><div class="col-md-8"><p class="message_text"><strong>Repeat: </strong> '+output+'</p></div><div class="col-md-4" ><small>Type: </small><span style="color:#346fa1; font-size:12px; font-weight:bold;">'+data[i].TYPE+'</span></div></div>'+
											'</div></div>');
						
							}
						$('#srch_1').text(srch_1c);
						}
						// my personal tasks
						taskList.append('<div class="mail-list" style="background-color:#f5f2ec;color:#067d1a; border:1px solid #ada6a6;"><h6 style="margin:0px">My Personal Tasks (<span id="srch_2">0</span> Tasks)</h6></div>');
						var srch_2c = 0;
						for (var i = 0; i < data.length; i++) 
						{							
						if(data[i].TYPE_ORDER == 2)
							{	
							srch_2c++;
								
							var duedatae = convertDate(data[i].DUE_DATE);
							//alert(toTimestamp(duedatae));
							var openvar = "";
							var closevar = "";
							var completevar = "";
							var inprogressvar = "";
							switch(data[i].TASK_STATUS)
							{
								case "OPEN":
									openvar = " selected='selected'";
								break;
								case "CLOSED":
									closevar = " selected='selected'";
								break;
								case "COMPLETED":
									completevar = " selected='selected'";
								break;
								case "IN PROGRESS":
									inprogressvar = " selected='selected'";
								break;
							}
							var duedatae = convertDate(data[i].DUE_DATE)
							if(duedatae=="1 Jan 1970 5:0 AM"){duedatae="";}
							if(duedatae=="31 Dec 1969 6:0 PM"){duedatae="";} // new added by rafiq
							var notificationStatusYes="";
							var notificationStatusNo="";
							if(data[i].STATUS==1){notificationStatusYes = " checked"}
							else{notificationStatusNo = " checked"}
							var output = "";
							var repint = data[i].REPEAT_INTERVAL;
							var arrIntervals = repint.split(',');
							var arrayLength = arrIntervals.length;
							for (var x = 0; x < arrayLength; x++) 
							{
								switch(arrIntervals[x])
								{
									case '0': 
										output += "Mon, ";
										break;
									case '1': 
										output += "Tue, ";
										break;
									case '2': 
										output += "Wed, ";
										break;
									case '3': 
										output += "Thu, ";
										break;
									case '4': 
										output += "Fri, ";
										break;
									case '5': 
										output += "Sat, ";
										break;
									case '6': 
										output += "Sun, ";
										break;
									case '7': 
										output += "Month";
										break;
								}
							}
							if(repint == "0,1,2,3,4,5,6") output = "Every Day";
							if(output == "") output = "Never";
							var strstyleDD=' style="color:#ffd80b; font-weight:600; text-transform:capitalize" ';
							var timecheck = timeSince(new Date(duedatae));
							if (timecheck = timecheck.includes("ago"))
							{
								strstyleDD =' style="color:red; font-weight:600; text-transform:capitalize" ';
							}
var anchortag = '<a href="javascript:changeStatusPersonalSearched(\'COMPLETED\','+data[i].TASK_ID+');" style="font-size:12px;"><img src="assets/images/mark-completed.gif" id="markascompleted'+data[i].TASK_ID+'" class="status-image"/></a>';
if(data[i].TASK_STATUS == 'COMPLETED')
{				
    anchortag = '<a href="#" style="font-size:12px;"><img src="assets/images/completed-task.gif" id="completedtask"/></a>';
}
							var notificationButton = '<img src="assets/images/btn-off.png" id="imgoff" onclick="changeNotificationStatusPRRT(1,' + data[i].TASK_ID + ');" />';
							if(data[i].STATUS==1){notificationButton = '<img src="assets/images/btn-on.png" id="imgon" onclick="changeNotificationStatusPRRT(0,' + data[i].TASK_ID + ');" />';}
							
							var duedatae = convertDate(data[i].DUE_DATE);
							if(duedatae=="1 Jan 1970 5:0 AM"){duedatae="";}
							if(duedatae=="31 Dec 1969 6:0 PM"){duedatae="";}
							if(duedatae=="0"){duedatae="";}
							
							if(duedatae == '') { duedatae = '<small>No Due Date</small>';} else { duedatae = timeSince(new Date(duedatae)); }
							
							
							taskList.append('<div class="mail-list taskDetailsPersonal" data-task_id="' + data[i].TASK_ID + '">' +
												'<div class="content" style="width:97%;">'+
												'<div class="row"><div class="col-md-8"><p class="message_text" '+ strstyleDD +'>' + data[i].TASK_TITLE + '</p></div><div class="col-md-4"  style="text-align:left;padding:0px; margin:0px;">'+anchortag+'</div></div>' + 
												'<div class="row"><div class="col-md-8"><p class="message_text">'+duedatae+'</p></div><div class="col-md-4" ><small>Status: </small>' + data[i].TASK_STATUS + '</div></div>'+ 
												'<div class="row"><div class="col-md-8"><p class="message_text"><strong>Repeat: </strong> '+output+'</p></div><div class="col-md-4" ><small>Type: </small><span style="color:#346fa1; font-size:12px; font-weight:bold;">'+data[i].TYPE+'</span></div></div>'+
											'</div></div>');
						
							}
						$('#srch_2').text(srch_2c);
						}
						// Assigned to other tasks
						taskList.append('<div class="mail-list" style="background-color:#f5f2ec;color:#067d1a; border:1px solid #ada6a6;"><h6 style="margin:0px">Assgined to Other Tasks (<span id="srch_3">0</span> Tasks)</h6></div>');
						var srch_3c = 0;
						for (var i = 0; i < data.length; i++) 
						{							
						if(data[i].TYPE_ORDER == 3)
							{	
							srch_3c++;
								
							var duedatae = convertDate(data[i].DUE_DATE);
							//alert(toTimestamp(duedatae));
							var openvar = "";
							var closevar = "";
							var completevar = "";
							var inprogressvar = "";
							switch(data[i].TASK_STATUS)
							{
								case "OPEN":
									openvar = " selected='selected'";
								break;
								case "CLOSED":
									closevar = " selected='selected'";
								break;
								case "COMPLETED":
									completevar = " selected='selected'";
								break;
								case "IN PROGRESS":
									inprogressvar = " selected='selected'";
								break;
							}
							var duedatae = convertDate(data[i].DUE_DATE)
							if(duedatae=="1 Jan 1970 5:0 AM"){duedatae="";}
							if(duedatae=="31 Dec 1969 6:0 PM"){duedatae="";} // new added by rafiq
							var notificationStatusYes="";
							var notificationStatusNo="";
							if(data[i].STATUS==1){notificationStatusYes = " checked"}
							else{notificationStatusNo = " checked"}
							var output = "";
							var repint = data[i].REPEAT_INTERVAL;
							var arrIntervals = repint.split(',');
							var arrayLength = arrIntervals.length;
							for (var x = 0; x < arrayLength; x++) 
							{
								switch(arrIntervals[x])
								{
									case '0': 
										output += "Mon, ";
										break;
									case '1': 
										output += "Tue, ";
										break;
									case '2': 
										output += "Wed, ";
										break;
									case '3': 
										output += "Thu, ";
										break;
									case '4': 
										output += "Fri, ";
										break;
									case '5': 
										output += "Sat, ";
										break;
									case '6': 
										output += "Sun, ";
										break;
									case '7': 
										output += "Month";
										break;
								}
							}
							if(repint == "0,1,2,3,4,5,6") output = "Every Day";
							if(output == "") output = "Never";
							var strstyleDD=' style="color:#ffd80b; font-weight:600; text-transform:capitalize" ';
							var timecheck = timeSince(new Date(duedatae));
							if (timecheck = timecheck.includes("ago"))
							{
								strstyleDD =' style="color:red; font-weight:600; text-transform:capitalize" ';
							}
							
var anchortag = '<a href="javascript:changeStatusPersonalSearched(\'COMPLETED\','+data[i].TASK_ID+');" style="font-size:12px;"><img src="assets/images/mark-completed.gif" id="markascompleted'+data[i].TASK_ID+'" class="status-image"/></a>';
if(data[i].TASK_STATUS == 'COMPLETED')
{				
    anchortag = '<a href="#" style="font-size:12px;"><img src="assets/images/completed-task.gif" id="completedtask"/></a>';
}

							
							var notificationButton = '<img src="assets/images/btn-off.png" id="imgoff" onclick="changeNotificationStatusPRRT(1,' + data[i].TASK_ID + ');" />';
							if(data[i].STATUS==1){notificationButton = '<img src="assets/images/btn-on.png" id="imgon" onclick="changeNotificationStatusPRRT(0,' + data[i].TASK_ID + ');" />';}
							
							var duedatae = convertDate(data[i].DUE_DATE);
							if(duedatae=="1 Jan 1970 5:0 AM"){duedatae="";}
							if(duedatae=="31 Dec 1969 6:0 PM"){duedatae="";}
							if(duedatae=="0"){duedatae="";}
							
							if(duedatae == '') { duedatae = '<small>No Due Date</small>';} else { duedatae = timeSince(new Date(duedatae)); }
							
							
							taskList.append('<div class="mail-list taskDetailsAssignOthers" data-task_id="' + data[i].TASK_ID + '">' +
												'<div class="content" style="width:97%;">'+
												'<div class="row"><div class="col-md-8"><p class="message_text" '+ strstyleDD +'>' + data[i].TASK_TITLE + '</p></div><div class="col-md-4" style="text-align:left; padding:0px; margin:0px;">'+anchortag+'</div></div>' + 
												'<div class="row"><div class="col-md-8"><p class="message_text">'+duedatae+'</p></div><div class="col-md-4" ><small>Status: </small>' + data[i].TASK_STATUS + '</div></div>'+ 
												'<div class="row"><div class="col-md-8"><p class="message_text"><strong>Repeat: </strong> '+output+'</p></div><div class="col-md-4" ><small>Type: </small><span style="color:#346fa1; font-size:12px; font-weight:bold;">'+data[i].TYPE+'</span></div></div>'+
											'</div></div>');
						
							}
						$('#srch_3').text(srch_3c);
						}
						
						// cc tasks
						taskList.append('<div class="mail-list" style="background-color:#f5f2ec;color:#067d1a; border:1px solid #ada6a6;"><h6 style="margin:0px">CC Tasks (<span id="srch_4">0</span> Tasks)</h6></div>');
						var srch_4c = 0;
						for (var i = 0; i < data.length; i++) 
						{							
						if(data[i].TYPE_ORDER == 4)
							{	
							srch_4c++;
								
							var duedatae = convertDate(data[i].DUE_DATE);
							//alert(toTimestamp(duedatae));
							var openvar = "";
							var closevar = "";
							var completevar = "";
							var inprogressvar = "";
							switch(data[i].TASK_STATUS)
							{
								case "OPEN":
									openvar = " selected='selected'";
								break;
								case "CLOSED":
									closevar = " selected='selected'";
								break;
								case "COMPLETED":
									completevar = " selected='selected'";
								break;
								case "IN PROGRESS":
									inprogressvar = " selected='selected'";
								break;
							}
							var duedatae = convertDate(data[i].DUE_DATE)
							if(duedatae=="1 Jan 1970 5:0 AM"){duedatae="";}
							if(duedatae=="31 Dec 1969 6:0 PM"){duedatae="";} // new added by rafiq
							var notificationStatusYes="";
							var notificationStatusNo="";
							if(data[i].STATUS==1){notificationStatusYes = " checked"}
							else{notificationStatusNo = " checked"}
							var output = "";
							var repint = data[i].REPEAT_INTERVAL;
							var arrIntervals = repint.split(',');
							var arrayLength = arrIntervals.length;
							for (var x = 0; x < arrayLength; x++) 
							{
								switch(arrIntervals[x])
								{
									case '0': 
										output += "Mon, ";
										break;
									case '1': 
										output += "Tue, ";
										break;
									case '2': 
										output += "Wed, ";
										break;
									case '3': 
										output += "Thu, ";
										break;
									case '4': 
										output += "Fri, ";
										break;
									case '5': 
										output += "Sat, ";
										break;
									case '6': 
										output += "Sun, ";
										break;
									case '7': 
										output += "Month";
										break;
								}
							}
							if(repint == "0,1,2,3,4,5,6") output = "Every Day";
							if(output == "") output = "Never";
							var strstyleDD=' style="color:#ffd80b; font-weight:600; text-transform:capitalize" ';
							var timecheck = timeSince(new Date(duedatae));
							if (timecheck = timecheck.includes("ago"))
							{
								strstyleDD =' style="color:red; font-weight:600; text-transform:capitalize" ';
							}
							var anchortag = '<a href="javascript:changeStatusPRR(\'COMPLETED\','+data[i].TASK_ID+');" style="font-size:12px;"><img src="assets/images/mark-completed.gif" id="markascompleted'+data[i].TASK_ID+'" /></a>';								
							if(data[i].TASK_STATUS == 'COMPLETED')
							{				
								anchortag = '<a href="#" style="font-size:12px;"><img src="assets/images/completed-task.gif" id="completedtask"/></a>';
							}
							var notificationButton = '<img src="assets/images/btn-off.png" id="imgoff" onclick="changeNotificationStatusPRRT(1,' + data[i].TASK_ID + ');" />';
							if(data[i].STATUS==1){notificationButton = '<img src="assets/images/btn-on.png" id="imgon" onclick="changeNotificationStatusPRRT(0,' + data[i].TASK_ID + ');" />';}
							
							var duedatae = convertDate(data[i].DUE_DATE);
							if(duedatae=="1 Jan 1970 5:0 AM"){duedatae="";}
							if(duedatae=="31 Dec 1969 6:0 PM"){duedatae="";}
							if(duedatae=="0"){duedatae="";}
							
							if(duedatae == '') { duedatae = '<small>No Due Date</small>';} else { duedatae = timeSince(new Date(duedatae)); }
							
							
							taskList.append('<div class="mail-list taskDetailsCCtaskDD" data-task_id="' + data[i].TASK_ID + '">' +
												'<div class="content" style="width:97%;">'+
												'<div class="row"><div class="col-md-8"><p class="message_text" '+ strstyleDD +'>' + data[i].TASK_TITLE + '</p></div><div class="col-md-4" ><small>Creator: </small> <span style="color:#346fa1; font-size:12px;font-weight:bold;">'+data[i].FULL_NAME+'</span></div></div>' + 
												'<div class="row"><div class="col-md-8"><p class="message_text">'+duedatae+'</p></div><div class="col-md-4" ><small>Status: </small>' + data[i].TASK_STATUS + '</div></div>'+ 
												'<div class="row"><div class="col-md-8"><p class="message_text"><strong>Repeat: </strong> '+output+'</p></div><div class="col-md-4" ><small>Type: </small><span style="color:#346fa1; font-size:12px; font-weight:bold;">'+data[i].TYPE+'</span></div></div>'+
											'</div></div>');
						
							}
						$('#srch_4').text(srch_4c);
						}
					}
				} 
				else 
				{
            		taskList.append('<div class="error">' + data.MESSAGE + '</div>')
        		}
    		}
		});
	return false;
})

var USER_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
	 $.ajax({
        url: '<?php echo $url; ?>api2/projects/get-user-mobile',
        type: 'POST',
		contentType:'application/x-www-form-urlencoded',
        data:"USER_ID="+USER_ID,		
        success: function(data) {                       
       if(data.STATUS !== 'ERROR') {
		   var d = JSON.parse(data.DATA);		   
				loggedMobile = d.MOBILE_NUMBER;
		   }                  
            }
		});	

///////////////////////////////////////
///////////// COUNTERS ////////////////
///////////////////////////////////////	 
// COUNTER - ASSIGNED ME TASKS DD
var totalcounter = 0;
	 new Promise((resolve,reject)=>{
		$.ajax({
	url : '<?php echo $url; ?>api2/tasks/count-tasks-dd?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=COUNTASSIGNMEDD&CURRENT_DATE=<?php echo date('Y-m-d'); ?>',
    type: 'GET',
    dataType: 'json',
    cache: false,
    success: function (data) 
	{                
       if(data.STATUS !== 'ERROR') 
	   {
            var data = JSON.parse(data.DATA);
			//console.log(data);
  			if(data.length == 0)
			{
				document.getElementById("assignMeTaskCountDD").innerHTML = "0";//
				resolve()
			}
			else 
			{
				if(document.getElementById("assignMeTaskCountDD"))
				{
                 document.getElementById("assignMeTaskCountDD").innerHTML = data[0].noOfTask ? data[0].noOfTask: 0;
				 totalcounter += parseInt(data[0].noOfTask);
				}
				 resolve()			
			}
        }
	} });
		
		}).then(()=>{
			new Promise((resolve,reject)=>{
				 
					// COUNTER ASSIGNED OTHER TASKS DD
					 $.ajax({
						url : '<?php echo $url; ?>api2/tasks/count-tasks-dd?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=COUNTASSIGNEDOTHERDD&CURRENT_DATE=<?php echo date('Y-m-d'); ?>',
								type: 'GET',
								dataType: 'json',
								cache: false,
								success: function (data) {                
						   if(data.STATUS !== 'ERROR') {
								var data = JSON.parse(data.DATA);
								//console.log(data);
								if(data.length == 0){
									document.getElementById("assignOtherTaskCountDD").innerHTML = "0";//				
									resolve()			
					
								}
								else {
									if(document.getElementById("assignOtherTaskCountDD"))
									{
									 document.getElementById("assignOtherTaskCountDD").innerHTML = data[0].noOfTask ? data[0].noOfTask: 0;
									 totalcounter += parseInt(data[0].noOfTask);
									}
									 resolve()			
					
									}
							} 
						   }
						  }); 
				
			}).then(()=>{
				new Promise((resolve,reject)=>{
					// COUNTER - PERSONAL TASKS DD
									$.ajax({
									 url : '<?php echo $url; ?>api2/tasks/count-tasks-dd?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=COUNTPERSONALDD&CURRENT_DATE=<?php echo date('Y-m-d'); ?>',
												type: 'GET',
												dataType: 'json',
												cache: false,
												success: function (data) {                
										   if(data.STATUS !== 'ERROR') {
												var data = JSON.parse(data.DATA);
												//console.log(data);
												if(data.length == 0){
													document.getElementById("personalTaskCountDD").innerHTML = "0";
													resolve()
												}
												else {
													if(document.getElementById("personalTaskCountDD"))
													{
													 document.getElementById("personalTaskCountDD").innerHTML = data[0].noOfTask ? data[0].noOfTask: 0;
													 totalcounter += parseInt(data[0].noOfTask);
													}

 													resolve()

													}
											} 
										   }
										  }); 
								
				}).then(()=>{
						new Promise((resolve,reject)=>{
							// COUNTER - CC TASKS DD
									 $.ajax({
									 url : '<?php echo $url; ?>api2/tasks/count-tasks-dd?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=CCTASKCOUNTDD&CURRENT_DATE=<?php echo date('Y-m-d'); ?>',
												type: 'GET',
												dataType: 'json',
												cache: false,
												success: function (data) {                
										   if(data.STATUS !== 'ERROR') {
												var data = JSON.parse(data.DATA);
												//console.log(data);
												
												if(data.length == 0){
													document.getElementById("ccTaskCountDD").innerHTML = "0";//
													resolve()
												}
												else 
												{
													if(document.getElementById("ccTaskCountDD"))
													{
													document.getElementById("ccTaskCountDD").innerHTML = data[0].noOfTask ? data[0].noOfTask: 0;
													totalcounter += parseInt(data[0].noOfTask);
													}
													resolve()
												}			
											} 
										   }
										  });
																
						}).then(()=>{								
								document.getElementById("totaltaskscountDD").innerHTML = totalcounter;
						})								

				})
			})				
		})

	  
// COUNTER - ASSIGNED TO ME TASKS NORMAL
$.ajax({
	 url : '<?php echo $url; ?>api2/tasks/count-tasks?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=ASSIGNMETASKCOUNT',
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {                
       if(data.STATUS !== 'ERROR') {
            var data = JSON.parse(data.DATA);
  			if(data.length == 0){
				document.getElementById("assignMeTaskCount").innerHTML = "0";//
			}
			else {
                 document.getElementById("assignMeTaskCount").innerHTML = data[0].noOfTask;
				}
        } 
       }
      }); 
// COUNTER - ASSINGN TO OTHERS TASKS NORMAL
$.ajax({
	 url : '<?php echo $url; ?>api2/tasks/count-tasks?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=ASSIGNOTHERTASKCOUNT',
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {                
       if(data.STATUS !== 'ERROR') {
            var data = JSON.parse(data.DATA);
  			if(data.length == 0){
				document.getElementById("assignOtherTaskCount").innerHTML = "0";//
			}
			else {
                 document.getElementById("assignOtherTaskCount").innerHTML = data[0].noOfTask;
				}
        } 
       }
      });  

		
	    
	   
	// COUNTER - POERSONAL TASKS DD
   $.ajax({
	 url : '<?php echo $url; ?>api2/tasks/count-tasks?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=PERSONALTASKCOUNT',
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {                
       if(data.STATUS !== 'ERROR') {
            var data = JSON.parse(data.DATA);
  			if(data.length == 0){
				document.getElementById("personalTaskCount").innerHTML = "0";//
			}
			else {
                 document.getElementById("personalTaskCount").innerHTML = data[0].noOfTask;
				}
        } 
       }
      }); 
	  
	  
	 		
		
	
		
		///////////////
		 $.ajax({
	 url : '<?php echo $url; ?>api2/tasks/count-tasks?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=CCTASKCOUNT',
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {                
       if(data.STATUS !== 'ERROR') {
            var data = JSON.parse(data.DATA);
  			if(data.length == 0){
				document.getElementById("ccTaskCount").innerHTML = "0";//
			}
			else {
                 document.getElementById("ccTaskCount").innerHTML = data[0].noOfTask;
				}
        } 
       }
      }); 
		///////////////
		 $.ajax({
	 url : '<?php echo $url; ?>api2/tasks/count-tasks?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=CCTASKCOUNT',
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {                
       if(data.STATUS !== 'ERROR') {
            var data = JSON.parse(data.DATA);
			//console.log(data);
  			if(data.length == 0){
				document.getElementById("ccTaskCount").innerHTML = "0";//
			}
			else {
                 document.getElementById("ccTaskCount").innerHTML = data[0].noOfTask;
				}
        } 
       }
      }); 
		///////////////
		 $.ajax({
	 url : '<?php echo $url; ?>api2/tasks/count-tasks?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=CCTASKCOUNT',
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {                
       if(data.STATUS !== 'ERROR') {
            var data = JSON.parse(data.DATA);
			//console.log(data);
  			if(data.length == 0){
				document.getElementById("ccTaskCount").innerHTML = "0";//
			}
			else {
                 document.getElementById("ccTaskCount").innerHTML = data[0].noOfTask;
				}
        } 
       }
      }); 
		///////////////
		 $.ajax({
	 url : '<?php echo $url; ?>api2/tasks/count-tasks?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=CCTASKCOUNT',
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {                
       if(data.STATUS !== 'ERROR') {
            var data = JSON.parse(data.DATA);
			//console.log(data);
  			if(data.length == 0){
				document.getElementById("ccTaskCount").innerHTML = "0";//
			}
			else {
                 document.getElementById("ccTaskCount").innerHTML = data[0].noOfTask;
				}
        } 
       }
      }); 
		///////////////
		$.ajax({
            url:  url,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
               var data = JSON.parse(data.DATA);
		 if(data[0].total == 0)
		 {
			$("#submenu1 ul li > a:contains('My Personal Tasks')").parent().remove();
		 }
		   }
        });
		//////////////// ASSIGNED TO ME TASKS		
		 $.ajax({
            url:  '<?php echo $url; ?>api2/tasks/fetch-me-tasks?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=USERSLIST&CURRENT_DATE=<?php echo date('Y-m-d'); ?>',
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data2) {                
       if(data2.STATUS !== 'ERROR') {
            var data2 = JSON.parse(data2.DATA); 			
			if(data2.length == 0){
				$("#submenu1 ul li > a:contains('Assigned To Me')").parent().remove();
			
			}
			
        }
                  
            }
        }); 
	 ////////////////////////////// ASAIGN OTEHR TASKS
	  $.ajax({
            url:  '<?php echo $url; ?>api2/tasks/fetch-others-tasks?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=USERSLIST&CURRENT_DATE=<?php echo date('Y-m-d'); ?>',
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data3) {                
       if(data3.STATUS !== 'ERROR') {
            var data3 = JSON.parse(data3.DATA);
			if(data3.length == 0){
				$("#submenu1 ul li > a:contains('Assigned To Others')").parent().remove();
			}
        }
            }
        }); 
	 /////////////////////////// CC USERS
	  $.ajax({
	 url : '<?php echo $url; ?>api2/tasks/fetch-cc-due-tasks-users?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=USERSLIST&CURRENT_DATE=<?php echo date('Y-m-d'); ?>',
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {                
       if(data.STATUS !== 'ERROR') {
            var data = JSON.parse(data.DATA);
  			if(data.length == 0){
				$("#submenu1 ul li > a:contains('CC Tasks')").parent().remove();
			}
			
        } 
                  
            }
        }); 
		
		// shipment tasks counter //		
        var url = '<?php echo $url; ?>api2/shipments/fetch-shipments?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>';
         $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {				
				if(data.STATUS !== 'ERROR') {
					var data = JSON.parse(data.DATA);
					//alert(data.length);
					document.getElementById("shipmentTaskCount").innerHTML = data.length;											
		        }     
            }
        });
		
		});
</script>
<style type="text/css">
a.link_header_menu:link, a.link_header_menu:hover, a.link_header_menu:active, a.link_header_menu:visited {
	font-family:font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,"Noto Sans",sans-serif,"Apple Color Emoji","Segoe UI Emoji","Segoe UI Symbol","Noto Color Emoji";
	font-size:0.75rem;
	padding:0px 4px 0px 6px;
	
}
div.hdr_menu{
	text-align:left;
	float:left;
	margin-left:15px;
}


 /* Dropdown Button */
.dropbtn {
  background-color: #4CAF50;
  color: white;
  padding: 5px 15px;
  font-size: 14px;
  border: none;
}

.dropbtn2 {
  background-color: #4CAF50;
  color: white;
  padding: 5px 15px;
  font-size: 14px;
  border: none;
}

/* The container <div> - needed to position the dropdown content */
.dropdown {
  position: relative;
  display: inline-block;
}

/* Dropdown Content (Hidden by Default) */
.dropdown-content {
  display: none;
  position: absolute;
  background-color: #f1f1f1;
  width: 220px;
  box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
  z-index: 1;
}

/* Links inside the dropdown */
.dropdown-content a {
  color: black;
  padding: 12px 16px;
  text-decoration: none;
  display: block;
  border:1px solid #CCC;
}

/* Change color of dropdown links on hover */
.dropdown-content a:hover {background-color: #ddd;}

/* Show the dropdown menu on hover */
.dropdown:hover .dropdown-content {display: block;}

/* Change the background color of the dropdown button when the dropdown content is shown */
.dropdown:hover .dropbtn {background-color: #3e8e41;} 
</style>
</head>
<body>
<div class="container-fluid">
    <div class="content-wrapper">
        <div class="email-wrapper wrapper">
            <div class="row align-items-stretch top-bar" style="padding-top:2px; padding-bottom:0px;">
                <div class="col col-md-12">
                    <div class="float-left" style="line-height:34px;"><b style="font-size:18px; line-height:34px;">Task Planner</b> </div> <div class="hdr_menu" style="float:left;line-height:34px;" ><a class="link_header_menu" style="float:left;" href="<?php echo $url; ?>" id="home"> <button class="dropbtn2 btn"> HOME </button> </a> 
                    <!-- menu start -->
                     <div class="dropdown" style="float:left;line-height:34px;">
  <button class="dropbtn btn">ADD <img src="assets/images/btn_add2.png" width="17"  />  </button> 
 
  <div class="dropdown-content">
    <a href="#" id="addNewProjects">New Project</a>
    <a href="#" id="addIndivisualTask">Indivisual Task</a>
    <a href="#" id="addNewPersonal">Personal Task</a>
    <a href="#" id="addNewShipment">Advance Shipment/RMA</a>
  </div>
</div> <!--- menu end -->
                    </div>
                    <div style="float:left;line-height:34px; font-family:Tahoma, Geneva, sans-serif; font-size:12px;"><form id="frm" name="frm" method="GET" ><input type="text" name="key" id="key"  placeholder="Search Any Task" class="form-control" value="" style="width:230px; margin-left:20px; padding:2px;"  /></div><div style="float:left;line-height:34px;"> <input type="radio" name="rd" style="padding:10px; margin:8px 3px 0 5px;" value="np" checked > <label style="font-family:Tahoma, Geneva, sans-serif; font-size:12px;">Non-Project </label> <input type="radio" name="rd" style="padding:10px; margin:6px 3px 0 5px;" value="pr" > <label style="font-family:Tahoma, Geneva, sans-serif; font-size:12px;">Project</label> <input type="radio" name="rd" style="padding:10px; margin:6px 3px 0 5px;" value="sh" > <label style="font-family:Tahoma, Geneva, sans-serif; font-size:12px;">Shipment</label>  <button class="dropbtn btn" type="submit" style="margin-left:5px;">Search</button></form> </div>
                    <div class="float-right" style="line-height:34px;font-family:Tahoma, Geneva, sans-serif; font-size:12px;">Welcome  <strong><?php echo $_SESSION['logged_in']['FULL_NAME']; ?></strong> | <a href="#" id="btnLogout">Logout</a></div>
                </div>
            </div>
            <div class="row" style="margin-top:48px;">
            <div class="col-12"><div style="clear:both; border-bottom:1px solid #f5de67; font-family:Tahoma, Geneva, sans-serif; font-size:12px; line-height:25px;" id="breadcrumb">Home </div></div>
            </div>
            <?php /*?><div class="row">
            	<div class="alert alert-info alert-dismissible fade show" role="alert">
                  <strong>NOTIFICATION! (TASK OVERDUE) </strong>CHECK ON THE WAY SHIPMENTS  - (11 hours ago)   &nbsp; &nbsp;  Personal Task by <strong>Ziad Minhas</strong> Notification on <span style="color:#006;">08/27/2020 09:15:44</span>
                  <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                  </button>
                </div>
            </div><?php */?>
            <div class="row mail-container align-items-stretch" style="margin-top:0px;">
                <div class="mail-sidebar d-none d-lg-block col-md-3 pt-3 bg-white">
                    <div class="menu-bar">
                        <ul class="menu-items nav flex-column flex-nowrap">                     	
                             
                            <li class="nav-item active">
                                <a class="nav-link collapsed menu-heading" href="#submenu1" data-toggle="collapse" data-target="#submenu1" id="dueTasks"><span style="float:left"> Due Today</span><span class="badge-pill" id="totaltaskscountDD"></span>
                                    
                                    <!--<span class="badge badge-pill badge-success float-right">8</span>-->
                                </a>
                                <div class="collapse" id="submenu1" aria-expanded="false">
                                    <ul class="menu-items nav flex-column flex-nowrap">
                                        <li class="nav-item">
                                       <a class="nav-link collapsed" href="#submenuMeDD" data-toggle="collapse" data-target="#submenuMeDD" id="assignMeDD"><span style="float:left"> Assigned To Me</span><span class="badge-pill" id="assignMeTaskCountDD"></span></a>
                                        <div class="collapse" id="submenuMeDD" aria-expanded="false"></div>
                                        </li>
                                        <li class="nav-item">
                                        
                                        <a class="nav-link collapsed" href="#submenu1p" data-toggle="collapse" data-target="#submenu1p" id="personalTasksDD" style="background:#93bcc7;"><span style="float:left">My Personal Tasks</span><div class="toshow"><img src="assets/images/add-ico-20.png" style="cursor:pointer; float:right;" id="addNewPersonal2" > </div><span id="personalTaskCountDD" class="badge-pill"></span></a>
                                        <div class="collapse" id="submenu1p" aria-expanded="false"></div>
                                        </li>  
                                        <li class="nav-item">
                                       <a class="nav-link collapsed" href="#submenuOtherDD" data-toggle="collapse" data-target="#submenuOtherDD" id="assignOthersDD"  style="background:#93bcc7;"><span style="float:left">Assigned To Others</span> <div class="toshow"><img src="assets/images/add-ico-20.png" style="cursor:pointer; float:right;" id="addIndivisualTask2" > </div><span id="assignOtherTaskCountDD" class="badge-pill"></span></a>
                                        <div class="collapse" id="submenuOtherDD" aria-expanded="false"></div>
                                        </li>                                              
                                       <li class="nav-item">
                                       <a class="nav-link collapsed" href="#submenuCCtaskDD" data-toggle="collapse" data-target="#submenuCCtaskDD" id="ccTasksDD"><span style="float:left">CC Tasks</span><span class="badge-pill" id="ccTaskCountDD"></span></a>
                                        <div class="collapse" id="submenuCCtaskDD" aria-expanded="false"></div>
                                       </li>
                                       
                                    </ul>
                                </div>
                            </li>                            
                             <li class="nav-item active">
                              <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#submenuMe" id="assignMe"> 																																				<span style="float:left">Assigned To Me</span><span class="badge-pill" id="assignMeTaskCount"></span></a>
                               <div class="collapse" id="submenuMe" aria-expanded="false">
                                    
                                </div>
                              </li>
                            <li class="nav-item active">
                            	
                                <!--<span class="badge badge-pill badge-success float-right">8</span>-->
                                
                              <a class="nav-link collapsed" href="#submenu1myp" data-toggle="collapse" data-target="#submenu1myp" id="myPersonalTasks"><span style="float:left">My Personal Tasks</span><div class="toshow"><img src="assets/images/add-ico-20.png" style="cursor:pointer; float:right;" id="addNewPersonal" > </div><span id="personalTaskCount" class="badge-pill"></span>     
 
</a>
  <div class="collapse" id="submenu1myp" aria-expanded="false"></div>
         <div class="collapse" id="submenu2" aria-expanded="false">
      <!--    <ul class="flex-column nav">
         <li class="nav-item"><a class="nav-link" href="#" id="addNewPersonal">Add New</a></li>
         </ul>  -->
         </div>
                            </li>
          					<li class="nav-item active">
         					<a class="nav-link collapsed" href="#submenuMyProjects" data-toggle="collapse" data-target="#submenuMyProjects" id="myProjects">
          					<span style="float:left">My Projects</span><div class="toshow"><img src="assets/images/add-ico-20.png" style="cursor:pointer; float:right;" id="addNewProjects" > </div><span id="projectTaskCount" class="badge-pill"></span>
                            <!--<span class="badge badge-pill badge-success float-right">8</span>-->
                            
                            </a>
         					<div class="collapse" id="submenuMyProjects" aria-expanded="false">
                             <ul class="flex-column nav">
                            <!-- <li class="nav-item"><a class="nav-link" href="#" id="addNewProjects">Add New</a></li> -->
                             </ul>
                             </div>
                            </li>
                            <li class="nav-item active">
                               <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#submenuOthers" id="assignOthers"> 																																				<span style="float:left">Assigned To Others</span><div class="toshow"><img src="assets/images/add-ico-20.png" style="cursor:pointer; float:right;" id="addIndivisualTask" > </div><span class="badge-pill" id="assignOtherTaskCount"></span></a>
                               <div class="collapse" id="submenuOthers" aria-expanded="false">
                                    
                                </div>
                              </li>
                            <li class="nav-item active">
                             <a class="nav-link collapsed" href="#submenuCC" data-toggle="collapse" data-target="#submenuCC" id="ccTasks">
                              <span style="float:left"> CC Tasks</span><span id="ccTaskCount" class="badge-pill"></span>
                              
                              <!--<span class="badge badge-pill badge-success float-right">8</span>-->
                             </a>
                                        <div class="collapse" id="submenuCC" aria-expanded="false"></div>
                             <div class="collapse" id="submenu4" aria-expanded="false">
                             <ul class="flex-column nav">
                             <li class="nav-item"><a class="nav-link" href="#" id="addCCTask">Add New</a></li>
                             </ul>
                             </div>         
         					</li>  
                            <li class="nav-item active">
                             <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#submenuShipmentUsers" id="shipmentTask">
                             <!-- <i class="fa fa-tachometer-alt menu-icons" style="margin-right:0px;" ></i>-->
                             <span style="float:left">Adv Shipment/RMA</span><div class="toshow"><img src="assets/images/add-ico-20.png" style="cursor:pointer; float:right;" id="addNewShipment" > </div><span id="shipmentTaskCount" class="badge-pill"></span>
                             <!--<span class="badge badge-pill badge-success float-right">8</span>-->
                             
                             </a>
                             <div class="collapse" id="submenuShipmentUsers" aria-expanded="false">
                             <ul class="flex-column nav">                             
                             <li class="nav-item"><a class="nav-link" href="#" id="addNewAdvanceShipment">Add New A.S/A.R</a></li>
                             </ul>
                             </div>
                             </li>  
                      
                       </ul>
                    </div>
                </div>                
                <div id="taskLists" class="mail-list-container col-md-4 pt-3 pb-3 col-lg-4 bg-white">

                </div>                
                <div id="taskDetails" class="mail-view d-none d-md-block col-md-7 col-lg-5 bg-white" style="margin-top:5px;">                
                No task Selected!
                    <div class="message-body" style="visibility:hidden; display:none;">
                        <div class="sender-details">
                            Task Name
                        </div>
                        <div class="message-content">
                            my task noti
                        </div>

                        <div class="sender-details">
                            Status
                        </div>
                        <div class="message-content">
                            <label class="badge badge-info">OPEN</label>
                        </div>

                        <div class="sender-details">
                            Due Date
                        </div>
                        <div class="message-content">
                            01/11/2019 01:35 AM
                        </div>

                        <div class="message-content">
                            <span>Description: </span>

                            <div class="description">
                                Add a TO-DO here
                            </div>
                        </div>
                    </div>
                </div>               
                
        </div>
    </div>
</div>


<script src="https://netdna.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
<script type="text/javascript">
    var taskList = $('#taskLists');
    var taskDetail = $('#taskDetails');
	var taskDetailProj = $('#taskDetailsProj');
var submenuOthers = $('#submenuOthers');
var submenuMe = $('#submenuMe');
var submenuMeDD = $('#submenuMeDD');
var submenuOtherDD = $('#submenuOtherDD');
var submenuCCtaskDD = $('#submenuCCtaskDD');
var submenuCC = $('#submenuCC');
var submenu1p = $('#submenu1p');
var submenu1myp = $('#submenu1myp');
var submenuMeDD2 = $('#submenuMeDD2'); 
var submenuMe2 = $('#submenuMe2'); 
var submenuOtherDD2 = $('#submenuOtherDD2'); 
var submenuOther2 = $('#submenuOther2');
var submenuCCtaskDD2 = $('#submenuCCtaskDD2');

var submenuShipmentUsers = $('#submenuShipmentUsers');
var submenuMyProjects = $('#submenuMyProjects');

    $('#btnLogout').click(function(e) {
        $.ajax({
            url: '<?php echo $url; ?>logout',
            type: 'GET',
            success: function (data) {
                if(data === 'SUCCESS')
                    window.location.href = '<?php echo $url; ?>login';
            }
        })
    });

    //Due Today
   

    $('#dueTasks').click(function(e) {
		$('#breadcrumb').html('Home &raquo; Due Today ');
		funActiveOnly('dueTasks',e);
	   		taskList.empty();
		 	taskDetail.empty();
		 
		
        activeTab($(this), 0);
	
    });

    $('#assignMeDD').click(function() {

$('#breadcrumb').html('Home &raquo; Due Today &raquo; Assigned to Me');		
	$("a#assignMeDD").removeClass("nav-link collapsed");
	$("a#assignMeDD").addClass("nav-link");
	$("a#assignMeDD").attr('aria-expanded','true');
	
	$("a#personalTasksDD").removeClass("nav-link");
	$("a#personalTasksDD").addClass("nav-link collapsed");
	$("a#personalTasksDD").attr('aria-expanded','false');
	$("div#submenu1p").removeClass("collapse show");
	$("div#submenu1p").addClass("collapse");
	$("div#submenu1p").attr('aria-expanded','false');
	$("a#ccTasksDD").removeClass("nav-link");
	$("a#ccTasksDD").addClass("nav-link collapsed");
	$("a#ccTasksDD").attr('aria-expanded','false');
	$("div#submenuCCtaskDD").removeClass("collapse show");
	$("div#submenuCCtaskDD").addClass("collapse");
	$("div#submenuCCtaskDD").attr('aria-expanded','false');
	$("a#assignOthersDD").removeClass("nav-link");
	$("a#assignOthersDD").addClass("nav-link collapsed");
	$("a#assignOthersDD").attr('aria-expanded','false');
	$("div#submenuOtherDD").removeClass("collapse show");
	$("div#submenuOtherDD").addClass("collapse");
	$("div#submenuOtherDD").attr('aria-expanded','false');

       // var url = '< ?php echo $url; ?>api2/tasks/fetch-due-tasks?USER_ID=< ?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=ME';
        //showAssignMeTasksDD(url, 1, 'tasks');
		taskList.empty();
		 taskDetail.empty();
		 showAssignMeUsersListDD();
        activeTab($(this), $('#assignMeDD'));
    });
	 function funshowSubMenuDD2(selectedUID, uname) {
		 $('#breadcrumb').html('Home &raquo; Due Today &raquo; Assigned to Me &raquo; '+uname + ' &raquo; Non Repeated Tasks');
		 
 	//if($('a#assignMe'+selectedUID).attr('aria-expanded') == 'false')
	//{
		funGetTaskAssignedMeUsersDue(selectedUID);
	//}
	//else
	//{
	//	taskList.empty();
	//	taskDetail.empty();

	//}



       // var url = '< ?php echo $url; ?>api2/tasks/fetch-due-tasks?USER_ID=< ?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=ME';
        //showAssignMeTasksDD(url, 1, 'tasks');
		taskList.empty();
		 taskDetail.empty();
		  var url = '<?php echo $url; ?>api2/tasks/fetch-due-tasks?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=MECTR&CURRENT_DATE=<?php echo date('Y-m-d'); ?>&ASSIGNED_ID='+selectedUID;
        $.ajax({
            url:  url,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
               var data = JSON.parse(data.DATA);
			   
			    //submenuMeDD2.empty();
//console.log(data);
		 if(data[0].repeated > 0 ){
			document.getElementById("submenuMeDD2"+selectedUID).innerHTML='<ul class="menu-items nav flex-column flex-nowrap"><li class="nav-item"><a class="nav-link" href="#" id="dueTaskassingedMeRep2" onclick="funGetTaskAssignedMeUsersRep('+selectedUID+');"><span style="float:left">Repeated Tasks</span><span class="badge-pill" id="">'+data[0].repeated+'</span></a></li></ul>'; 
		 } 
	
            }
        });
			   
        activeTab($(this), $('#assignMe'+selectedUID));
    }
	 function funshowSubMenu2(selectedUID, uname) {
$('#breadcrumb').html('Home &raquo; Assigned to Me &raquo; '+uname + ' &raquo; Non Repeated Tasks');		 
	//call non repeated tasks
	funGetTaskAssignedMeTaskUsersDue(selectedUID);

		
		taskList.empty();
		 taskDetail.empty();
		  var url = '<?php echo $url; ?>api2/tasks/fetch-due-tasks?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=METASKCTR&ASSIGNED_ID='+selectedUID;
        $.ajax({
            url:  url,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
               var data = JSON.parse(data.DATA);
			   document.getElementById("submenuMe2"+selectedUID).innerHTML = '';
			   var finaloutput = '';
			   
			   if(data[0].todo > 0 || data[0].repeated > 0 || data[0].nonrepeated > 0 )
			   {
				   finaloutput = '<ul class="menu-items nav flex-column flex-nowrap">';
				}		   
			   
//console.log(data);
		 if(data[0].todo > 0){
			 
			 finaloutput += '<li class="nav-item"><a class="nav-link" href="#" id="TaskassingedMeDue2" onclick="funGetTaskAssignedMeTaskUsers('+selectedUID+');"><span style="float:left">To Do</span><span class="badge-pill" id="">'+data[0].todo+'</span></a></li>';
		 }
		 
		 if(data[0].repeated > 0){
			 finaloutput += '<li class="nav-item"><a class="nav-link" href="#" id="TaskassingedMeDue2" onclick="funGetTaskAssignedMeTaskUsersRep('+selectedUID+');"><span style="float:left">Repeated Tasks</span><span class="badge-pill" id="">'+data[0].repeated+'</span></a></li>';
		 }	 
		 
		 
		  if(data[0].todo > 0 || data[0].repeated > 0 || data[0].nonrepeated > 0 )
			   {
				   finaloutput += '</ul>';
				   
				}
				
				
		 
		 document.getElementById("submenuMe2"+selectedUID).innerHTML = finaloutput;
		 	
            }
        });
			   
        activeTab($(this), $('#assignMe'+selectedUID));
    }
	
	function funshowSubMenuCCtaskDD2(selectedUID,uname) {
		
		$('#breadcrumb').html('Home &raquo; Due Today &raquo; CC Tasks &raquo; '+uname + ' &raquo; Non Repeated Tasks');
		// place here 
		funGetTaskCCUsersDDDue(selectedUID);
		
		
       // var url = '< ?php echo $url; ?>api2/tasks/fetch-due-tasks?USER_ID=< ?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=ME';
        //showAssignMeTasksDD(url, 1, 'tasks');
		taskList.empty();
		 taskDetail.empty();
		  var url = '<?php echo $url; ?>api2/tasks/fetch-cc-due-tasks-interval?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=USERSINTERVAL&CURRENT_DATE=<?php echo date('Y-m-d'); ?>';
        $.ajax({
            url:  url,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
               var data = JSON.parse(data.DATA);
			   
			    
//console.log(data);
		 if(data[0].repeated > 0 && data[0].nonrepeated > 0){
			document.getElementById("submenuCCtaskDD2"+selectedUID).innerHTML='<ul class="menu-items nav flex-column flex-nowrap"><li class="nav-item"><a class="nav-link" href="#" id="funGetTaskCCUsersDDRep2" onclick="funGetTaskCCUsersDDRep('+selectedUID+');"><span>Repeated Tasks</span><span class="badge-pill" id="">'+data[0].repeated+'</span></a></li></ul>'; 
		 } else if(data[0].repeated > 0 && data[0].nonrepeated == 0){
			 document.getElementById("submenuCCtaskDD2"+selectedUID).innerHTML='<ul class="menu-items nav flex-column flex-nowrap"><li class="nav-item"><a class="nav-link" href="#" id="funGetTaskCCUsersDDRep2" onclick="funGetTaskCCUsersDDRep('+selectedUID+');"><span>Repeated Tasks</span><span class="badge-pill" id="">'+data[0].repeated+'</span></a></li></ul>';
		 }
	
            }
        });
			   
        activeTab($(this), $('#assignMe'+selectedUID));
    }
	function funshowSubMenuCCtask2(selectedUID, uname) {
		$('#breadcrumb').html('Home &raquo; CC Tasks &raquo; '+uname + ' &raquo; Non Repeated Tasks');
       // var url = '< ?php echo $url; ?>api2/tasks/fetch-due-tasks?USER_ID=< ?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=ME';
        //showAssignMeTasksDD(url, 1, 'tasks');
		taskList.empty();
		 taskDetail.empty();
		 funGetTaskCCUsersNoRep(selectedUID);
		 
  var url = '<?php echo $url; ?>api2/tasks/fetch-cc-tasks-interval?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=USERSINTERVAL';
        $.ajax({
            url:  url,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
               var data = JSON.parse(data.DATA);
				
			   document.getElementById("submenuCCtask2"+selectedUID).innerHTML = '';
			   var finaloutput = '';
			   
			   if(data[0].todo > 0 || data[0].repeated > 0 || data[0].nonrepeated > 0 )
			   {
				   finaloutput = '<ul class="menu-items nav flex-column flex-nowrap" style="margin:2px 15px;">';
				}		   
			   
//console.log(data);
		 if(data[0].todo > 0){
			 
			 finaloutput += '<li class="nav-item"><a class="nav-link" href="#" id="TaskassingedMeDue2" onclick="funGetTaskCCUsersTodo('+selectedUID+');"><span style="float:left">Todo Tasks</span><span class="badge-pill" id="">'+data[0].todo+'</span></a></li>';
		 }
		 /*
		 if(data[0].nonrepeated > 0){
			 finaloutput += '<li class="nav-item" ><a class="nav-link" href="#" id="TaskassingedMeDue2" onclick="funGetTaskCCUsersNoRep('+selectedUID+');"><span style="float:left">Non Repeated Tasks</span><span class="badge-pill" id="">'+data[0].nonrepeated+'</span></a></li>';
		 }
		 */
		 if(data[0].repeated > 0){
			 finaloutput += '<li class="nav-item"><a class="nav-link" style="padding:7px; margin:0px;" href="#" id="TaskassingedMeDue2" onclick="funGetTaskCCUsersRep('+selectedUID+');"><span style="float:left">Repeated Tasks</span><span class="badge-pill" id="">'+data[0].repeated+'</span></a></li>';
		 }	 
		  if(data[0].todo > 0 || data[0].repeated > 0 || data[0].nonrepeated > 0 )
			   {
				   finaloutput += '</ul>';
				}
		 document.getElementById("submenuCCtask2"+selectedUID).innerHTML = finaloutput;
            }
        });
			   
        activeTab($(this), $('#assignMe'+selectedUID));
    }
	function funshowSubMenuOtherDD2(selectedUID,uname) {
		
$('#breadcrumb').html('Home &raquo; Due Today &raquo; Assigned to Other &raquo; '+uname + ' &raquo; Non Repeated Tasks');
		
		funGetTaskAssignedOtherUsersDue(selectedUID);
		
       // var url = '< ?php echo $url; ?>api2/tasks/fetch-due-tasks?USER_ID=< ?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=ME';
        //showAssignMeTasksDD(url, 1, 'tasks');
		taskList.empty();
		 taskDetail.empty();
		  var url = '<?php echo $url; ?>api2/tasks/fetch-due-tasks?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=OTHERCTR&CURRENT_DATE=<?php echo date('Y-m-d'); ?>&ASSIGNED_ID='+selectedUID;
        $.ajax({
            url:  url,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
               var data = JSON.parse(data.DATA);
			   
			    //submenuOtherDD2.empty();
//console.log(data);
		 if(data[0].repeated > 0 && data[0].nonrepeated > 0){
			document.getElementById("submenuOtherDD2"+selectedUID).innerHTML='<ul class="menu-items nav flex-column flex-nowrap" style="margin:1px 15px;"<li class="nav-item"><a class="nav-link" href="#" id="dueTaskassingedOtherRep2" onclick="funGetTaskAssignedOtherUsersRep('+selectedUID+');"><span >Repeated Tasks</span><span class="badge-pill" id="">'+data[0].repeated+'</span></a></li></ul>'; 
		 } else if(data[0].repeated > 0 && data[0].nonrepeated == 0){
			 document.getElementById("submenuOtherDD2"+selectedUID).innerHTML='<ul class="menu-items nav flex-column flex-nowrap" style="margin:1px 15px;"><li class="nav-item"><a class="nav-link" href="#" id="dueTaskassingedOtherRep2" onclick="funGetTaskAssignedOtherUsersRep('+selectedUID+');"><span >Repeated Tasks</span><span class="badge-pill" id="">'+data[0].repeated+'</span></a></li></ul>';
		 }
	
            }
        });
			   
        activeTab($(this), $('#assignMe'+selectedUID));
    }
	function funshowSubMenuOther2(selectedUID, uname) {
$('#breadcrumb').html('Home &raquo; Assigned to Other &raquo; '+uname + ' &raquo; Non Repeated Tasks');
		
       // var url = '< ?php echo $url; ?>api2/tasks/fetch-due-tasks?USER_ID=< ?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=ME';
	  funAdiveSubmentOnly(selectedUID);
	  //return false;
        //showAssignMeTasksDD(url, 1, 'tasks');
		taskList.empty();
		 taskDetail.empty();
		 
		 funGetTaskAssignedOtherUsersTaskDue(selectedUID);
		  var url = '<?php echo $url; ?>api2/tasks/fetch-due-tasks?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=OTHERTASKCTR&ASSIGNED_ID='+selectedUID;
        $.ajax({
            url:  url,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
               var data = JSON.parse(data.DATA);
			   
			    document.getElementById("submenuOthers2"+selectedUID).innerHTML = '';
			   var finaloutput = '';
			   
			   if(data[0].todo > 0 || data[0].repeated > 0 || data[0].nonrepeated > 0 )
			   {
				   finaloutput = '<ul class="menu-items nav flex-column flex-nowrap" style="margin:1px 10px 1px 20px;">';
				}		   
			   
//console.log(data);
		 if(data[0].todo > 0){
			 
			 finaloutput += '<li class="nav-item"><a class="nav-link" href="#" id="TaskassingedMeDue2" onclick="funGetTaskAssignedOtherUsersTask('+selectedUID+');"><span style="float:left">Todo Tasks</span><span class="badge-pill" id="">'+data[0].todo+'</span></a></li>';
		 }
		
		/* if(data[0].nonrepeated > 0){
			 finaloutput += '<li class="nav-item"><a class="nav-link" href="#" id="TaskassingedMeDue2" onclick="funGetTaskAssignedOtherUsersTaskDue('+selectedUID+');" ><span style="float:left">Non Repeated Tasks</span><span class="badge-pill" id="">'+data[0].nonrepeated+'</span></a></li>';
		 }
		 */
		 if(data[0].repeated > 0){
			 finaloutput += '<li class="nav-item"><a class="nav-link" href="#" id="TaskassingedMeDue2" onclick="funGetTaskAssignedOtherUsersTaskRep('+selectedUID+');"><span style="float:left">Repeated Tasks</span><span class="badge-pill" id="">'+data[0].repeated+'</span></a></li>';
		 }	
		  
		  if(data[0].todo > 0 || data[0].repeated > 0 || data[0].nonrepeated > 0 )
			   {
				   finaloutput += '</ul>';
				}
		 document.getElementById("submenuOthers2"+selectedUID).innerHTML = finaloutput;
			}
        });
			   
        activeTab($(this), $('#assignMe'+selectedUID));
    }

    $('#personalTasksDD').click(function(e) {
		$('#breadcrumb').html('Home &raquo; Due Today &raquo; My Personal Tasks');
		taskList.empty();
		 taskDetail.empty();

	

	if($('a#personalTasksDD').attr('aria-expanded') == 'false')
	{
		dueTaskPersonalDueFunction();
	}
	else
	{
		taskList.empty();
		taskDetail.empty();

	}

		 
	$("a#personalTasksDD").removeClass("nav-link collapsed");
	$("a#personalTasksDD").addClass("nav-link");
	$("a#personalTasksDD").attr('aria-expanded','true');
	$("a#assignMeDD").removeClass("nav-link");
	$("a#assignMeDD").addClass("nav-link collapsed");
	$("a#assignMeDD").attr('aria-expanded','false');
	$("div#submenuMeDD").removeClass("collapse show");
	$("div#submenuMeDD").addClass("collapse");
	$("div#submenuMeDD").attr('aria-expanded','false');
	$("a#ccTasksDD").removeClass("nav-link");
	$("a#ccTasksDD").addClass("nav-link collapsed");
	$("a#ccTasksDD").attr('aria-expanded','false');
	$("div#submenuCCtaskDD").removeClass("collapse show");
	$("div#submenuCCtaskDD").addClass("collapse");
	$("div#submenuCCtaskDD").attr('aria-expanded','false');
	$("a#assignOthersDD").removeClass("nav-link");
	$("a#assignOthersDD").addClass("nav-link collapsed");
	$("a#assignOthersDD").attr('aria-expanded','false');
	$("div#submenuOtherDD").removeClass("collapse show");
	$("div#submenuOtherDD").addClass("collapse");
	$("div#submenuOtherDD").attr('aria-expanded','false');
	
	 
        var url = '<?php echo $url; ?>api2/tasks/fetch-due-tasks?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=PERSONALCTR&CURRENT_DATE=<?php echo date('Y-m-d'); ?>';
        $.ajax({
            url:  url,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
               var data = JSON.parse(data.DATA);
  		 submenu1p.empty();

		 if(data[0].repeated > 0 && data[0].nonrepeated > 0){
			 submenu1p.append('<ul class="menu-items nav flex-column flex-nowrap" style="margin:1px 13px;"><li class="nav-item"><a class="nav-link" href="#" id="dueTaskPersonalRep" onclick="dueTaskPersonalRepFunction();"><span style="float:left">Repeated Tasks</span><span class="badge-pill" id="">'+data[0].repeated+'</span></a></li></ul>');		 
		 } else if(data[0].repeated > 0 && data[0].nonrepeated == 0){
			 submenu1p.append('<ul class="menu-items nav flex-column flex-nowrap" style="margin:1px 13px;"><li class="nav-item"><a class="nav-link" href="#" id="dueTaskPersonalRep" onclick="dueTaskPersonalRepFunction();"><span style="float:left">Repeated Tasks</span><span class="badge-pill" id="">'+data[0].repeated+'</span></a></li></ul>');
		 }
	
            }
        });
    

        activeTab($(this), $('#personalTasksDD'));
		
    });
	 $('#myPersonalTasks').click(function(e) {	
		
		$('#breadcrumb').html('Home &raquo; My Personal Tasks &raquo; Non Repeated Tasks');

		funActiveOnly('myPersonalTasks',e);
	
		 
		taskList.empty();
		 taskDetail.empty();
		 TaskPersonalNoRepFunction();
		 
        var url = '<?php echo $url; ?>api2/tasks/fetch-due-tasks?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=MYPERSONALCTR';
        $.ajax({
            url:  url,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
				
				
               var data = JSON.parse(data.DATA);
			   
			    document.getElementById("submenu1myp").innerHTML = '';
			   var finaloutput = '';
			   
			   if(data[0].todo > 0 || data[0].repeated > 0 || data[0].nonrepeated > 0 )
			   {
				   finaloutput = '<ul class="menu-items nav flex-column flex-nowrap">';
				}		   
			   
console.log(data);
		 if(data[0].todo > 0){
			 
			 finaloutput += '<li class="nav-item"><a class="nav-link" href="#" id="todoPersonalTask" onclick="TaskPersonalFunction();">Todo Tasks</a></li>';
		 }
		 /*if(data[0].nonrepeated > 0){
			 finaloutput += '<li class="nav-item"><a class="nav-link" href="#" id="noRepPersonalTask" onclick="TaskPersonalNoRepFunction();" style="background:#93bcc7;">NonRepeated Tasks</a></li>';
		 }*/
		 if(data[0].repeated > 0){
			 
			 	 if(data[0].nonrepeated > 0){
					 finaloutput += '<li class="nav-item"><a class="nav-link" href="#" id="RepPersonalTask" onclick="TaskPersonalRepFunction();">Repeated Tasks</a></li>';
					 } else {
			 	finaloutput += '<li class="nav-item"><a class="nav-link" href="#" id="RepPersonalTask" onclick="TaskPersonalRepFunction();" style="background:#93bcc7;">Repeated Tasks</a></li>';
				 }
		 }	 
		  if(data[0].todo > 0 || data[0].repeated > 0 || data[0].nonrepeated > 0 )
			   {
				   finaloutput += '</ul>';
				}
		 document.getElementById("submenu1myp").innerHTML = finaloutput;
			
            }
        });
    

        activeTab($(this), $('#myPersonalTasks'));
		
    });
	
	//dueTaskPersonalDue
	//$('#dueTaskPersonalDue').click(function(e) {
		function dueTaskPersonalDueFunction() {
			$('#breadcrumb').html('Home &raquo; Due Today &raquo; My Personal Tasks &raquo; Non Repeated Tasks');
        var url = '<?php echo $url; ?>api2/tasks/fetch-due-tasks?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=PERSONALDUE';
        showPersonalTasksDDDue(url, 1, 'tasks');
        activeTab($(this), $('#dueTaskPersonalDue'));
    }
	//dueTaskPersonalDue
	//$('#dueTaskPersonalRep').click(function(e) {
		function dueTaskPersonalRepFunction() {
			$('#breadcrumb').html('Home &raquo; Due Today &raquo; My Personal Tasks &raquo; Repeated Tasks');
        var url = '<?php echo $url; ?>api2/tasks/fetch-due-tasks?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=PERSONALREP';
        showPersonalTasksDDRep(url, 1, 'tasks');
        activeTab($(this), $('#dueTaskPersonalRep'));
    }
	function TaskPersonalRepFunction() {
		$('#breadcrumb').html('Home &raquo; My Personal Tasks &raquo; Repeated Tasks');
        var url = '<?php echo $url; ?>api2/tasks/fetch-due-tasks?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=MYPERSONALREP';
        showPersonalTasksRep(url, 1, 'tasks');
        activeTab($(this), $('#dueTaskPersonalRep'));
    }
	function TaskPersonalNoRepFunction() {
        var url = '<?php echo $url; ?>api2/tasks/fetch-due-tasks?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=PERSONALNOREP';
        showPersonalTasksNoRep(url, 1, 'tasks');
        activeTab($(this), $('#dueTaskPersonalRep'));
    }
	function TaskPersonalFunction() {
		$('#breadcrumb').html('Home &raquo; My Personal Tasks &raquo; Todo Tasks');
        var url = '<?php echo $url; ?>api2/tasks/fetch-due-tasks?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=PERSONALTODO';
        showPersonalTasks(url, 1, 'tasks');
        activeTab($(this), $('#dueTaskPersonalRep'));
    }
	
	
	
	//CC Tasks DD
    $('#ccTasksDD').click(function() {
		$('#breadcrumb').html('Home &raquo; Due Today &raquo; CC Tasks');
	$("a#ccTasksDD").removeClass("nav-link collapsed");
	$("a#ccTasksDD").addClass("nav-link");
	$("a#ccTasksDD").attr('aria-expanded','true');
	
	$("a#personalTasksDD").removeClass("nav-link");
	$("a#personalTasksDD").addClass("nav-link collapsed");
	$("a#personalTasksDD").attr('aria-expanded','false');
	$("div#submenu1p").removeClass("collapse show");
	$("div#submenu1p").addClass("collapse");
	$("div#submenu1p").attr('aria-expanded','false');
	
	$("a#assignMeDD").removeClass("nav-link");
	$("a#assignMeDD").addClass("nav-link collapsed");
	$("a#assignMeDD").attr('aria-expanded','false');
	$("div#submenuMeDD").removeClass("collapse show");
	$("div#submenuMeDD").addClass("collapse");
	$("div#submenuMeDD").attr('aria-expanded','false');
	
	$("a#assignOthersDD").removeClass("nav-link");
	$("a#assignOthersDD").addClass("nav-link collapsed");
	$("a#assignOthersDD").attr('aria-expanded','false');
	$("div#submenuOtherDD").removeClass("collapse show");
	$("div#submenuOtherDD").addClass("collapse");
	$("div#submenuOtherDD").attr('aria-expanded','false');
	
        taskList.empty();
		 taskDetail.empty();
		 showCCtaskUsersListDD();
       

        activeTab($(this), 0);
    });
	 $('#ccTasks').click(function(e) {
		 $('#breadcrumb').html('Home &raquo; CC Tasks');
		 funActiveOnly('ccTasks',e);
        taskList.empty();
		 taskDetail.empty();
		 showCCtaskUsersList();
       

        activeTab($(this), 0);
    });


   $('#assignOthersDD').click(function() {
	   $('#breadcrumb').html('Home &raquo; Due Today &raquo; Assigned to Other');
       	$("a#assignOthersDD").removeClass("nav-link collapsed");
	$("a#assignOthersDD").addClass("nav-link");
	$("a#assignOthersDD").attr('aria-expanded','true');
	
	$("a#personalTasksDD").removeClass("nav-link");
	$("a#personalTasksDD").addClass("nav-link collapsed");
	$("a#personalTasksDD").attr('aria-expanded','false');
	$("div#submenu1p").removeClass("collapse show");
	$("div#submenu1p").addClass("collapse");
	$("div#submenu1p").attr('aria-expanded','false');
	
	$("a#assignMeDD").removeClass("nav-link");
	$("a#assignMeDD").addClass("nav-link collapsed");
	$("a#assignMeDD").attr('aria-expanded','false');
	$("div#submenuMeDD").removeClass("collapse show");
	$("div#submenuMeDD").addClass("collapse");
	$("div#submenuMeDD").attr('aria-expanded','false');
	
	$("a#ccTasksDD").removeClass("nav-link");
	$("a#ccTasksDD").addClass("nav-link collapsed");
	$("a#ccTasksDD").attr('aria-expanded','false');
	$("div#submenuCCtaskDD").removeClass("collapse show");
	$("div#submenuCCtaskDD").addClass("collapse");
	$("div#submenuCCtaskDD").attr('aria-expanded','false');
		taskList.empty();
		 taskDetail.empty();
		 showAssignOthersUsersListDD();
        activeTab($(this), $('#assignOthersDD'));
    });

	
	//My Projects
    $('#myProjects').click(function(e) {	
		$('#breadcrumb').html('Home &raquo; My Projects');
	funActiveOnly('myProjects',e);
		funShowProject();		
		});
	
	function funShowProject()
	{
		 taskList.empty();
		 taskDetail.empty();
		 
	
		 $.ajax({
	url: '<?php echo $url; ?>api2/projects/fetch-member-projects?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>',
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {                
         submenuMyProjects.empty();
       if(data.STATUS !== 'ERROR') {
            var data = JSON.parse(data.DATA);
			
			if(data.length > 0) {
				
	
				
 submenuMyProjects.append('<ul class="menu-items nav flex-column flex-nowrap"  style="margin:1px;">');
           // console.log(data);	
				var bgclr = ' background:rgba(237, 242, 249, 0.77);';
			for (var i = 0; i < data.length; i++) {
				if(i%2==0) { bgclr = ' background:rgba(255, 255, 255, 0.8);'; } else { bgclr = ' background:rgba(237, 242, 249, 0.77);'; }
			if(data[i].CREATOR_ID == <?php echo $_SESSION['logged_in']['USER_ID']; ?>)
			{
				submenuMyProjects.append('<li class="nav-item" style="border:1px solid #aaa; margin:2px 6px; padding:3px;'+bgclr+' border-radius:4px; clear:both;"><a class="nav-link" style="padding:3px; font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica Neue; font-size:0.75em; color:#084557;" id="lnkShip_'+data[i].PROJECT_ID+'" onclick="funshowProjectTasks('+data[i].PROJECT_ID+',\''+data[i].PROJECT_NAME+'\',\''+data[i].CREATOR_ID+'\');" href="#" data-target="#'+data[i].PROJECT_ID+'" >'+data[i].PROJECT_NAME+'<span id="projectTaskCount" class="badge-pill" style="width:auto; font-weight:normal; float:right;margin-right: 54px;">'+data[i].FULL_NAME+'</span></a><span style="float:right;padding:4px;margin-top: -30px;"><img src="assets/images/add-ico-20.png" style="cursor:pointer;" data-pname="'+data[i].PROJECT_NAME+'" data-pid="'+data[i].PROJECT_ID+'" id="addProjectTask" ></span><span style="float:right;padding:4px;margin-top: -30px;"><img src="assets/images/btn_delete.png" data-pname="'+data[i].PROJECT_NAME+'" data-pid="'+data[i].PROJECT_ID+'" id="delProject" style="cursor:pointer;" ></span></li>');
				
			}
			else
			{
				submenuMyProjects.append('<li class="nav-item" style="border:1px solid #aaa; margin:2px 6px; padding:3px;'+bgclr+' border-radius:4px;"><a class="nav-link" style="padding:3px; font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica Neue; font-size:0.75em; color:#084557;" id="lnkShip_'+data[i].PROJECT_ID+'" onclick="funshowProjectTasks('+data[i].PROJECT_ID+',\''+data[i].PROJECT_NAME+'\');" href="#" data-target="#'+data[i].PROJECT_ID+'" >'+data[i].PROJECT_NAME+' <span id="projectTaskCount" class="badge-pill" style="width:auto; font-weight:normal; padding-right:5px;">'+data[i].FULL_NAME+'</span></a></li>');
			}
			
            }
			submenuMyProjects.append('</ul>');
			}
			if(data.length == 0){
				submenuMyProjects.empty();
			// submenuOthers.append('<ul class="flex-column nav"><li class="nav-item"><a class="nav-link" href="#" id="Others_0">NOT FOUND</a></li></ul>');
			}
			
        } else {
           // submenuMyProjects.append('<ul class="flex-column nav"><li class="nav-item"><a class="nav-link" href="#" id="Others_0">'+data.MESSAGE+'</a></li></ul>');
        }
                  
            }
        }); 
	 
	 taskList.append('<div class="error">No Project Seleted</div>')

    	
	}

	


   $(document).on('click', '.taskDetailsProj', function() {
        var PROJECT_ID = $(this).data('task_id');
		 var ASSIGNED_ID = $(this).data('assigned_id');
		
        $.ajax({
            url: '<?php echo $url; ?>api2/tasks/fetch-details?OBJECT_ID=' + PROJECT_ID + '&OBJECT_TYPE=task',
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
                if(data.STATUS === 'SUCCESS') {
					//console.log(data.DATA);
                   showProjTaskDetail(ASSIGNED_ID,data.DATA)
                }
            }
        });
    });

//showProjectSubTaskDetail
function showProjectTaskDetail(ASSIGNED_ID,PROJECT_ID)
{
	
        $.ajax({
            url: '<?php echo $url; ?>api2/tasks/fetch-details?OBJECT_ID=' + PROJECT_ID + '&OBJECT_TYPE=task',
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
                if(data.STATUS === 'SUCCESS') {
					//console.log(data.DATA);
                   showProjTaskDetail(ASSIGNED_ID,data.DATA)
                }
            }
        });
    
}
function showProjectSubTaskDetail(ASSIGNED_ID,PROJECT_ID)
{
	
        $.ajax({
            url: '<?php echo $url; ?>api2/tasks/fetch-details?OBJECT_ID=' + PROJECT_ID + '&OBJECT_TYPE=task',
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
                if(data.STATUS === 'SUCCESS') {
					//console.log(data.DATA);
                   showProjSubTaskDetail(ASSIGNED_ID,data.DATA)
                }
            }
        });
    
}

 $(document).on('click', '#btn_editTaskProjStatus', function() { 
var  TASK_ID = this.value;
var USER_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
$.ajax({
            url: '<?php echo $url; ?>api2/tasks/fetch-details?OBJECT_ID=' + TASK_ID + '&OBJECT_TYPE=task',
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
                if(data.STATUS === 'SUCCESS') {
                    showEditTaskStatusDetail(data.DATA)
                }
            }
        });

  });
  //showEditSubTaskDetailAll
  function showEditTaskDetailAll(d) {
	  
	 var data = JSON.parse(d);
	// alert(data.TASK_STATUS);
		d1 = new Date(convertDate(data.DUE_DATE));
		var dayr = ("0" + d1.getDate()).slice(-2);
		var monr = ("0" + (d1.getMonth()+1)).slice(-2);
		var fullyear = d1.getFullYear();
		
		
		finaldate = [fullyear,monr,dayr].join('-');
		var d = new Date(); // for now
           var hrs = d1.getHours(); // => 9
		   var mits = d1.getMinutes(); // =>  30
	       var secs = d1.getSeconds(); // => 51
		    if(finaldate=="1970-01-01"){finaldate="";}
		   
		   
		   hrs = ('0' + hrs).slice(-2);
		   mits = ('0' + mits).slice(-2);
		   
		   finaltime = [hrs,mits,].join(':');
		
		
		
		var output = "";
		 var openvar = "";
		  var closevar = "";
		   var completevar = "";
		    var inprogressvar = "";
		 switch(data.TASK_STATUS)
		 {
			 case "OPEN":
			 		openvar = " selected='selected'";
			 	break;
				 case "CLOSED":
			 		closevar = " selected='selected'";
			 	break;
				 case "COMPLETED":
			 		completevar = " selected='selected'";
			 	break;
				 case "IN PROGRESS":
			 		inprogressvar = " selected='selected'";
			 	break;
		 }

var imgsOutput = "";
		var imgesArr = data.IMAGES;
		if(Array.isArray(imgesArr)){
		for(var aa=0;aa<imgesArr.length;aa++)
		{
			//alert('< ?php echo $url; ?>');
			imgsOutput += '<a data-fancybox="gallery"  href="<?php echo $url; ?>'+imgesArr[aa]['TASK_IMAGE']+'" data-title="Photo" ><img src="<?php echo $url; ?>'+imgesArr[aa]['TASK_IMAGE']+'" class="img-fluid" width="150"></a>&nbsp; ';
		}
		}
		else
		imgsOutput = "";
//if(output == "") output += "Never";
		
		taskDetail.empty();
		
	taskDetail.append('<div class="message-body"><div class="container-fluid" id="xyz2"><form  id="frm_editProjectTaskAll" name="frm_editProjectTaskAll" enctype="multipart/form-data" method="post" onsubmit="return false;">' +			
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><h2>Edit Project Task Detail</h2></div></div>' +
           '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="validationCustom01">Task Name</label><input type="text" class="form-control" id="validationCustom01" name=validationCustom01" placeholder="Task name" value="'+data.TASK_TITLE+'" ><div class="valid-feedback">Looks good!</div></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-6"><label for="validationCustom02">Due Date </label><input type="date" class="form-control" id="validationCustom02" name="validationCustom02" value="' + finaldate + '" ><div class="valid-feedback">Looks good!</div></div><div class="col-md-6"><label for="txttime">Due Time</label><input type="time" class="form-control" id="txttime" name="txttime" value="' + finaltime + '" ></div></div>' +		   
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="validationCustom03">Description</label><textarea class="form-control" id="validationCustom03" name="validationCustom03" >' + data.TASK_DESCRIPTION + '</textarea><div class="invalid-feedback">Please provide description.</div></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-6"><label for="TASK_STATUS">Task Status</label> <select class="custom-select browser-default" id="TASK_STATUS" name="TASK_STATUS"><option value="OPEN" ' + openvar + ' >OPEN</option><option value="CLOSED" ' + closevar + '>CLOSED</option><option value="COMPLETED" ' + completevar + '>COMPLETED</option><option value="IN PROGRESS" ' + inprogressvar + '>IN PROGRESS</option></select></div><div class="col-md-6"><label for="Creator">Assigned To</label><input type="text" class="form-control" id="Creator" placeholder="Assigned" value="' + data.FULL_NAME + '" readonly></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12">'+imgsOutput+'</div></div>' +
		    '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="TASK_IMAGES">Pictures</label> <br /><input type="file" name="TASK_IMAGES[]" multiple ></div></div>' +
		   '<div class="row" style="margin-top:25px;"><div class="col-md-6" style="text-align:center;"><button class="btn btn-primary" type="button" style="width:200px;" id="btn_updateTaskbx" name="btn_updateTaskbx" onclick="changeProjectTaskAll(\'IN PROGRESS\','+data.TASK_ID+');" value="'+data.TASK_ID+'" >Update Task </button></div><div class="col-md-6" style="text-align:center;"><button class="btn btn-info" type="button" style="width:200px;" id="btn_CancelTask" name="btn_CancelTask" onclick="showProjectTaskDetail('+data.ASSIGNED_ID+','+data.TASK_ID+')" >Cancel Update</button></div></div>' +		   
            '</div></form></div>'
        );
		
	
  }
   function showEditSubTaskDetailAll(d) {
	  
	 var data = JSON.parse(d);
	// alert(data.TASK_STATUS);
		d1 = new Date(convertDate(data.DUE_DATE));
		var dayr = ("0" + d1.getDate()).slice(-2);
		var monr = ("0" + (d1.getMonth()+1)).slice(-2);
		var fullyear = d1.getFullYear();
		
		
		finaldate = [fullyear,monr,dayr].join('-');
		var d = new Date(); // for now
           var hrs = d1.getHours(); // => 9
		   var mits = d1.getMinutes(); // =>  30
	       var secs = d1.getSeconds(); // => 51
		    if(finaldate=="1970-01-01"){finaldate="";}
		   
		   
		   hrs = ('0' + hrs).slice(-2);
		   mits = ('0' + mits).slice(-2);
		   
		   finaltime = [hrs,mits,].join(':');
		
		
		
		var output = "";
		 var openvar = "";
		  var closevar = "";
		   var completevar = "";
		    var inprogressvar = "";
		 switch(data.TASK_STATUS)
		 {
			 case "OPEN":
			 		openvar = " selected='selected'";
			 	break;
				 case "CLOSED":
			 		closevar = " selected='selected'";
			 	break;
				 case "COMPLETED":
			 		completevar = " selected='selected'";
			 	break;
				 case "IN PROGRESS":
			 		inprogressvar = " selected='selected'";
			 	break;
		 }

//if(output == "") output += "Never";
		
		taskDetail.empty();
		
	taskDetail.append('<div class="message-body"><div class="container-fluid" id="xyz2"><form  id="frm_editProjectTaskAll" name="frm_editProjectTaskAll" enctype="multipart/form-data" method="post" onsubmit="return false;">' +			
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><h2>Edit Project Sub Task Detail</h2></div></div>' +
           '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="validationCustom01">Task Name</label><input type="text" class="form-control" id="validationCustom01" name=validationCustom01" placeholder="Task name" value="'+data.TASK_TITLE+'" ><div class="valid-feedback">Looks good!</div></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-6"><label for="validationCustom02">Due Date </label><input type="date" class="form-control" id="validationCustom02" name="validationCustom02" value="' + finaldate + '" ><div class="valid-feedback">Looks good!</div></div><div class="col-md-6"><label for="txttime">Due Time</label><input type="time" class="form-control" id="txttime" name="txttime" value="' + finaltime + '" ></div></div>' +		   
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="validationCustom03">Description</label><textarea class="form-control" id="validationCustom03" name="validationCustom03" >' + data.TASK_DESCRIPTION + '</textarea><div class="invalid-feedback">Please provide description.</div></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-6"><label for="TASK_STATUS">Task Status</label> <select class="custom-select browser-default" id="TASK_STATUS" name="TASK_STATUS"><option value="OPEN" ' + openvar + ' >OPEN</option><option value="CLOSED" ' + closevar + '>CLOSED</option><option value="COMPLETED" ' + completevar + '>COMPLETED</option><option value="IN PROGRESS" ' + inprogressvar + '>IN PROGRESS</option></select></div><div class="col-md-6"><label for="Creator">Assigned To</label><input type="text" class="form-control" id="Creator" placeholder="Assigned" value="' + data.FULL_NAME + '" readonly></div></div>' +
		   '<div class="row" style="margin-top:25px;"><div class="col-md-6" style="text-align:center;"><button class="btn btn-primary" type="button" style="width:200px;" id="btn_updateSubTaskbx" name="btn_updateSubTaskbx" onclick="changeProjectSubTaskAll(\'IN PROGRESS\','+data.TASK_ID+');" value="'+data.TASK_ID+'" >Update Task </button></div><div class="col-md-6" style="text-align:center;"><button class="btn btn-info" type="button" style="width:200px;" id="btn_CancelTask" name="btn_CancelTask" onclick="showProjectSubTaskDetail('+data.ASSIGNED_ID+','+data.TASK_ID+')" >Cancel Update</button></div></div>' +		   
            '</div></form></div>'
        );
		
	
  }
 function showEditTaskStatusDetail(d) {
	 var data = JSON.parse(d);
	// alert(data.TASK_STATUS);
		d1 = new Date(convertDate(data.DUE_DATE));
		var dayr = ("0" + d1.getDate()).slice(-2);
		var monr = ("0" + (d1.getMonth()+1)).slice(-2);
		var fullyear = d1.getFullYear();
		
		
		finaldate = [fullyear,monr,dayr].join('-');
		var d = new Date(); // for now
           var hrs = d1.getHours(); // => 9
		   var mits = d1.getMinutes(); // =>  30
	       var secs = d1.getSeconds(); // => 51
		    if(finaldate=="1970-01-01"){finaldate="";}
		   
		   
		   hrs = ('0' + hrs).slice(-2);
		   mits = ('0' + mits).slice(-2);
		   
		   finaltime = [hrs,mits,].join(':');
		
		
		
		var output = "";
		 var openvar = "";
		  var closevar = "";
		   var completevar = "";
		    var inprogressvar = "";
		 switch(data.TASK_STATUS)
		 {
			 case "OPEN":
			 		openvar = " selected='selected'";
			 	break;
				 case "CLOSED":
			 		closevar = " selected='selected'";
			 	break;
				 case "COMPLETED":
			 		completevar = " selected='selected'";
			 	break;
				 case "IN PROGRESS":
			 		inprogressvar = " selected='selected'";
			 	break;
		 }

//if(output == "") output += "Never";
		
		taskDetail.empty();
		
	taskDetail.append('<div class="message-body"><div class="container-fluid" id="xyz2"><form  id="frm_editStatusProjectTask" name="frm_editStatusProjectTask" enctype="multipart/form-data" method="post" onsubmit="return false;">' +			
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><h2>Edit Status Project Task</h2></div></div>' +
           '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="validationCustom01">Task Name</label><input type="text" class="form-control" id="validationCustom01" name=validationCustom01" placeholder="Task name" value="'+data.TASK_TITLE+'" readonly><div class="valid-feedback">Looks good!</div></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-6"><label for="validationCustom02">Due Date </label><input type="date" class="form-control" id="validationCustom02" name="validationCustom02" value="' + finaldate + '" readonly><div class="valid-feedback">Looks good!</div></div><div class="col-md-6"><label for="txttime">Due Time</label><input type="time" class="form-control" id="txttime" name="txttime" value="' + finaltime + '" readonly></div></div>' +		   
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="validationCustom03">Description</label><textarea class="form-control" id="validationCustom03" name="validationCustom03" readonly >' + data.TASK_DESCRIPTION + '</textarea><div class="invalid-feedback">Please provide description.</div></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-6"><label for="TASK_STATUS">Task Status</label> <select class="custom-select browser-default" id="TASK_STATUS" name="TASK_STATUS"><option value="OPEN" ' + openvar + ' >OPEN</option><option value="CLOSED" ' + closevar + '>CLOSED</option><option value="COMPLETED" ' + completevar + '>COMPLETED</option><option value="IN PROGRESS" ' + inprogressvar + '>IN PROGRESS</option></select></div><div class="col-md-6"><label for="Creator">Assigned To</label><input type="text" class="form-control" id="Creator" placeholder="Assigned" value="' + data.FULL_NAME + '" readonly></div></div>' +
		   '<div class="row" style="margin-top:25px;"><div class="col-md-6" style="text-align:center;"><button class="btn btn-primary" type="button" style="width:200px;" id="btn_updateTaskx" name="btn_updateTaskx" onclick="changeStatusProjectTask(\'IN PROGRESS\','+data.TASK_ID+');" value="'+data.TASK_ID+'" >Update Task Status</button></div><div class="col-md-6" style="text-align:center;"><button class="btn btn-info" type="button" style="width:200px;" id="btn_CancelTask" name="btn_CancelTask" onclick="showProjectTaskDetail('+data.ASSIGNED_ID+','+data.TASK_ID+')" >Cancel Update</button></div></div>' +		   
            '</div></form></div>'
        );
	
    }
	//showProjSubTaskDetail(aid,data.DATA)
  function showProjTaskDetail(aid,d) {
	
        taskDetail.empty();
		//console.log(d);
        var data = JSON.parse(d);
		 console.log(data);
		 var output = "";
		var duedatae = convertDate(data.DUE_DATE)
				 if(duedatae=="1 Jan 1970 5:0 AM"){duedatae="";}
				 if(duedatae=="31 Dec 1969 6:0 PM"){duedatae="";} // new added by rafiq
		var createdatae = convertDate(data.CREATED_DATE);
		
				 if(createdatae=="1 Jan 1970 5:0 AM"){createdatae="";}				 
				 if(createdatae=="31 Dec 1969 6:0 PM"){createdatae="";} // new added by rafiq

		// console.log(data.IMAGES.length);
		var imgsOutput = "";
		var imgesArr = data.IMAGES;
		if(Array.isArray(imgesArr)){
		for(var aa=0;aa<imgesArr.length;aa++)
		{
			//alert('< ?php echo $url; ?>');
			imgsOutput += '<a data-fancybox="gallery"  href="<?php echo $url; ?>'+imgesArr[aa]['TASK_IMAGE']+'" data-title="Photo" ><img src="<?php echo $url; ?>'+imgesArr[aa]['TASK_IMAGE']+'" class="img-fluid" width="150"></a>&nbsp; ';
		}
		}
		else
		imgsOutput = "";
		// return false;
$.ajax({
	url: '<?php echo $url; ?>api2/projects/get-project-detail?PROJECT_ID='+data.PROJECT_ID,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (datax) {                
       if(datax.STATUS !== 'ERROR') {
            var datax = JSON.parse(datax.DATA);
			console.log('project details=>',datax);
			//alert(data.xFULL_NAME+'=aa');
			
			var btn_edit = '';
			var logged_userId = <?php echo $_SESSION['logged_in']['USER_ID']; ?>;
			if(datax.CREATOR_ID == logged_userId)
			{
				//btn_edit = '<div class="col-md-4" style="text-align:center;"><button class="btn btn-primary" style="width:200px;" id="btn_editTaskProj" value="' + data.TASK_ID + '" >Edit Task</button></div><div class="col-md-4" style="text-align:center;"><button class="btn btn-danger" style="width:200px;" id="btn_delTask"  value="' + data.TASK_ID + '" >Delete Task</button></div><div class="col-md-4" style="text-align:center;"></div>';
				btn_edit = '<div class="col-md-4" style="text-align:center;"><button class="btn btn-primary" style="width:200px;" id="btn_editTaskProj" value="' + data.TASK_ID + '" >Edit Task</button></div><div class="col-md-4" style="text-align:center;"><button class="btn btn-danger" style="width:200px;" id="btn_delTask"  value="' + data.TASK_ID + '" >Delete Task</button></div><div class="col-md-4" style="text-align:center;"><button class="btn btn-primary" style="width:200px;" id="addProjectSubTask" data-pid="'+datax.PROJECT_ID+'" data-pname="'+datax.PROJECT_NAME+'" value="' + data.TASK_ID + '" >Add SubTask</button></div>';
			}
			else if(aid == logged_userId)
			{
				btn_edit = '<div class="col-md-6" style="text-align:center;"><button class="btn btn-primary" style="width:200px;" id="btn_editTaskProjStatus" value="' + data.TASK_ID + '" >Edit Task Status</button></div><div class="col-md-6" style="text-align:center;"></div>';
			}
			else
			{
				btn_edit = '<div class="col-md-12" style="text-align:center;"></div>';
			}
			
			taskDetail.append('<div class="message-body">' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="txt_task_name">Project Name</label><input type="text" class="form-control" id="txt_task_name" placeholder="Task Name" value="'+datax.PROJECT_NAME+'" readonly="readonly" ></div></div>' +
           '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="txt_task_name">Task Name</label><input type="text" class="form-control" id="txt_task_name" placeholder="Task Name" value="'+data.TASK_TITLE+'" readonly="readonly" ></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="txt_task_desc">Description</label><textarea class="form-control" id="txt_task_desc" readonly="readonly">'+data.TASK_DESCRIPTION+'</textarea></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-4"><label for="txt_task_due">Due on</label><input type="text" class="form-control" id="txt_task_due" placeholder="" value="'+duedatae+'" readonly="readonly" ></div><div class="col-md-4"><label for="txt_task_status">Status</label><input type="text" class="form-control" id="txt_task_status" placeholder="Task Status" value="'+data.TASK_STATUS+'" readonly="readonly" ></div><div class="col-md-4"><label for="txt_task_due">Assigned To</label><input type="text" class="form-control" id="txt_task_due" placeholder="" value="'+data.FULL_NAME+'" readonly="readonly" ></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="txt_task_desc">Pictures</label></div></div>'+ 
		   '<div class="row" style="margin-top:0px 10px;"><div class="col-md-12"><div style="border:1px solid #ccc; width:100%;">' + imgsOutput + '</div></div></div>' +
		   '<div class="row" style="margin-top:25px;">'+btn_edit+'</div>' +
            '</div>'
        );
			
	   }
			}
});
			
					
		
		/*
        taskDetail.append('<div class="message-body">' +
            '<div class="sender-details">Project Task Name</div>' +
            '<div class="message-content">' + data.TASK_TITLE + '</div>' +
            '<div class="sender-details">Status</div>' +
            '<div class="message-content">' +
            '<label class="badge badge-info">' + data.TASK_STATUS + '</label>' +
            '</div>' +	
		   
		   '<div class="sender-details">Due Date</div>' +
            '<div class="message-content">' + duedatae + '</div>' +
			
			
            '<div class="message-content"><span>Description: </span>' +
            '<div class="description">' + data.TASK_DESCRIPTION + '</div>' +
            '<div class="message-content"><span>Photos: </span>' +
            '<div class="message-content">' + imgsOutput + '</div>' +
            '</div>' +
			 '<div class="message-content"><span> </span>' +
            '</div>' + '<span><button class="btn btn-primary btn-sm" id="btn_editTaskProj" type="button"  value="' + data.TASK_ID + '" >Edit Task</button></span> &nbsp; <span><button class="btn btn-primary btn-sm" id="btn_delTask" type="button"  value="' + data.TASK_ID + '" >Delete Task</button></span>' + 
            '</div>');
			*/
			
			
			
			 $.ajax({
            url: '<?php echo $url; ?>api2/projects/fetch-sub-tasks?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TASK_ID='+data.TASK_ID,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (datax) {
				 
				   if(datax.STATUS !== 'ERROR') {
						var datay = JSON.parse(datax.DATA);
			   taskDetail.append('<div class="mail-list" style="background: grey;color:white; padding:5px; margin-top:20px;"><h6 style="margin:0px">Sub Tasks List</h6></div>');
			   			datay = datay.DATA;
						 console.log('SubTask Detail:',datay);		
						 var tasktype = 'TODO';						
						for (var z = 0; z < datay.length; z++) {
							
							if(datay[z].DUE_DATE=="0" || datay[z].DUE_DATE == "")
							{
								tasktype = 'TODO';
							} 
							else 
							{ 
								tasktype = 'Due:'+convertDate(datay[z].DUE_DATE);
							}
							taskDetail.append('<div class="row" onclick="funShowSubTaskDetail('+datay[z].ASSIGNED_ID+','+ datay[z].TASK_ID+');" style="cursor:pointer"><div  style="border:1px solid grey; padding:5px; width:100%; clear:both; height:70px; margin:2px 15px"><div style="clear:both;"><div style="float:left; margin:2px;"><strong>TITLE:</strong> &nbsp; ' + datay[z].TASK_TITLE + '</div><div style="float:right; margin:2px;"><strong>STATUS:</strong> &nbsp; '+datay[z].TASK_STATUS+'</div></div><div style="clear:both;"><div style="float:left;"><strong>Assigned:</strong> &nbsp; '+ datay[z].FULL_NAME+'</div><div style="float:right; margin:2px;"><strong>TYPE:</strong> &nbsp; ' +tasktype + '</div></div></div></div>');
						}
						
					} 
							
            }
			
        });
    }
	
	 function showProjSubTaskDetail(aid,d) {
	
        taskDetail.empty();
		//console.log(d);
        var data = JSON.parse(d);
		 console.log(data);
		 var output = "";
		var duedatae = convertDate(data.DUE_DATE)
				 if(duedatae=="1 Jan 1970 5:0 AM"){duedatae="";}
				 if(duedatae=="31 Dec 1969 6:0 PM"){duedatae="";} // new added by rafiq
		var createdatae = convertDate(data.CREATED_DATE);
		
				 if(createdatae=="1 Jan 1970 5:0 AM"){createdatae="";}				 
				 if(createdatae=="31 Dec 1969 6:0 PM"){createdatae="";} // new added by rafiq

		// console.log(data.IMAGES.length);
		var imgsOutput = "";
		var imgesArr = data.IMAGES;
		if(Array.isArray(imgesArr)){
		for(var aa=0;aa<imgesArr.length;aa++)
		{
			//alert('< ?php echo $url; ?>');
			imgsOutput += '<a data-fancybox="gallery"  href="<?php echo $url; ?>'+imgesArr[aa]['TASK_IMAGE']+'" data-title="Photo" ><img src="<?php echo $url; ?>'+imgesArr[aa]['TASK_IMAGE']+'" class="img-fluid" width="150"></a>&nbsp; ';
		}
		}
		else
		imgsOutput = "";
		// return false;
$.ajax({
	url: '<?php echo $url; ?>api2/projects/get-project-detail?PROJECT_ID='+data.PROJECT_ID,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (datax) {                
       if(datax.STATUS !== 'ERROR') {
            var datax = JSON.parse(datax.DATA);
			console.log('project details=>',datax);
			//alert(data.xFULL_NAME+'=aa');
			
			var btn_edit = '';
			var logged_userId = <?php echo $_SESSION['logged_in']['USER_ID']; ?>;
			if(datax.CREATOR_ID == logged_userId)
			{
				//btn_edit = '<div class="col-md-4" style="text-align:center;"><button class="btn btn-primary" style="width:200px;" id="btn_editTaskProj" value="' + data.TASK_ID + '" >Edit Task</button></div><div class="col-md-4" style="text-align:center;"><button class="btn btn-danger" style="width:200px;" id="btn_delTask"  value="' + data.TASK_ID + '" >Delete Task</button></div><div class="col-md-4" style="text-align:center;"></div>';
				btn_edit = '<div class="col-md-6" style="text-align:center;"><button class="btn btn-primary" style="width:200px;" id="btn_editSubTaskProj" value="' + data.TASK_ID + '" >Edit Task</button></div><div class="col-md-6" style="text-align:center;"><button class="btn btn-danger" style="width:200px;" id="btn_delTask"  value="' + data.TASK_ID + '" >Delete Sub Task</button></div></div>';
			}
			else if(aid == logged_userId)
			{
				btn_edit = '<div class="col-md-6" style="text-align:center;"><button class="btn btn-primary" style="width:200px;" id="btn_editTaskProjStatus" value="' + data.TASK_ID + '" >Edit Task Status</button></div><div class="col-md-6" style="text-align:center;"></div>';
			}
			else
			{
				btn_edit = '<div class="col-md-12" style="text-align:center;"></div>';
			}
			
			taskDetail.append('<div class="message-body">' +
			'<div class="row" style="margin-top:15px;"><div class="col-md-12"><h2>Project Sub Task Detail</h2></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="txt_task_name">Project Name</label><input type="text" class="form-control" id="txt_task_name" placeholder="Task Name" value="'+datax.PROJECT_NAME+'" readonly="readonly" ></div></div>' +
           '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="txt_task_name">Task Name</label><input type="text" class="form-control" id="txt_task_name" placeholder="Task Name" value="'+data.TASK_TITLE+'" readonly="readonly" ></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="txt_task_desc">Description</label><textarea class="form-control" id="txt_task_desc" readonly="readonly">'+data.TASK_DESCRIPTION+'</textarea></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-4"><label for="txt_task_due">Due on</label><input type="text" class="form-control" id="txt_task_due" placeholder="" value="'+duedatae+'" readonly="readonly" ></div><div class="col-md-4"><label for="txt_task_status">Status</label><input type="text" class="form-control" id="txt_task_status" placeholder="Task Status" value="'+data.TASK_STATUS+'" readonly="readonly" ></div><div class="col-md-4"><label for="txt_task_due">Assigned To</label><input type="text" class="form-control" id="txt_task_due" placeholder="" value="'+data.FULL_NAME+'" readonly="readonly" ></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="txt_task_desc">Pictures</label></div></div>'+ 
		   '<div class="row" style="margin-top:0px 10px;"><div class="col-md-12"><div style="border:1px solid #ccc; width:100%;">' + imgsOutput + '</div></div></div>' +
		   '<div class="row" style="margin-top:25px;">'+btn_edit+'</div>' +
            '</div>'
        );
			
	   }
			}
});
			
					
		
		/*
        taskDetail.append('<div class="message-body">' +
            '<div class="sender-details">Project Task Name</div>' +
            '<div class="message-content">' + data.TASK_TITLE + '</div>' +
            '<div class="sender-details">Status</div>' +
            '<div class="message-content">' +
            '<label class="badge badge-info">' + data.TASK_STATUS + '</label>' +
            '</div>' +	
		   
		   '<div class="sender-details">Due Date</div>' +
            '<div class="message-content">' + duedatae + '</div>' +
			
			
            '<div class="message-content"><span>Description: </span>' +
            '<div class="description">' + data.TASK_DESCRIPTION + '</div>' +
            '<div class="message-content"><span>Photos: </span>' +
            '<div class="message-content">' + imgsOutput + '</div>' +
            '</div>' +
			 '<div class="message-content"><span> </span>' +
            '</div>' + '<span><button class="btn btn-primary btn-sm" id="btn_editTaskProj" type="button"  value="' + data.TASK_ID + '" >Edit Task</button></span> &nbsp; <span><button class="btn btn-primary btn-sm" id="btn_delTask" type="button"  value="' + data.TASK_ID + '" >Delete Task</button></span>' + 
            '</div>');
			*/
			
			
			
			 $.ajax({
            url: '<?php echo $url; ?>api2/projects/fetch-sub-tasks?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TASK_ID='+data.TASK_ID,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (datax) {
				 
				   if(datax.STATUS !== 'ERROR') {
						var datay = JSON.parse(datax.DATA);
			   taskDetail.append('<div class="mail-list" style="background: grey;color:white; padding:5px; margin-top:20px;"><h6 style="margin:0px">Sub Tasks List</h6></div>');
			   			datay = datay.DATA;
						 console.log('SubTask Detail:',datay);		
						 var tasktype = 'TODO';						
						for (var z = 0; z < datay.length; z++) {
							
							if(datay[z].DUE_DATE=="0" || datay[z].DUE_DATE == "")
							{
								tasktype = 'TODO';
							} 
							else 
							{ 
								tasktype = 'Due:'+convertDate(datay[z].DUE_DATE);
							}
							taskDetail.append('<div class="row" onclick="funShowSubTaskDetail('+datay[z].ASSIGNED_ID+','+ datay[z].TASK_ID+');" style="cursor:pointer"><div  style="border:1px solid grey; padding:5px; width:100%; clear:both; height:70px; margin:2px 15px"><div style="clear:both;"><div style="float:left; margin:2px;"><strong>TITLE:</strong> &nbsp; ' + datay[z].TASK_TITLE + '</div><div style="float:right; margin:2px;"><strong>STATUS:</strong> &nbsp; '+datay[z].TASK_STATUS+'</div></div><div style="clear:both;"><div style="float:left;"><strong>Assigned:</strong> &nbsp; '+ datay[z].FULL_NAME+'</div><div style="float:right; margin:2px;"><strong>TYPE:</strong> &nbsp; ' +tasktype + '</div></div></div></div>');
						}
						
					} 
							
            }
			
        });
    }
	
	function funShowSubTaskDetail(aid,sTask_ID)
	{
		 $.ajax({
            url: '<?php echo $url; ?>api2/tasks/fetch-details?OBJECT_ID=' + sTask_ID + '&OBJECT_TYPE=task',
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
                if(data.STATUS === 'SUCCESS') {
					//console.log(data.DATA);
                   showProjSubTaskDetail(aid,data.DATA)
                }
            }
        });
	}
    //Assigned to Other	
    $('#assignOthers').click(function(e) {
		$('#breadcrumb').html('Home &raquo; Assigned to Others');
		funActiveOnly('assignOthers',e);
        var url = '<?php echo $url; ?>api2/tasks/fetch-others-tasks?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=ASSIGNEDOTHERS';
		taskList.empty();
		 taskDetail.empty();
		showAssignOthersUsersList();
        //showAssignOthersNew(url, 1, 'tasks');
		

        activeTab($(this), $('#assignOthers'));
    });
	
	 //Assigned to Me	
    $('#assignMe').click(function(e) {
		$('#breadcrumb').html('Home &raquo; Assigned to Me');
		funActiveOnly('assignMe',e);
		taskList.empty();
		 taskDetail.empty();
		showAssignMeUsersList();
        //showAssignMeNew(url, 1, 'tasks');
		

        activeTab($(this), $('#assignMe'));
    });
	
	function funGetTaskAssignedOtherUsers(pid) {
        var url = '<?php echo $url; ?>api2/tasks/fetch-others-tasks?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=USERASSIGNEDOTHERS&ASSIGNED_ID='+pid;
        showAssignOthersNew(url, 1, 'tasks');

        activeTab($(this), $('#assignOthers'));
    }
	
	function funGetTaskAssignedMeUsersDue(pid) {
        var url = '<?php echo $url; ?>api2/tasks/fetch-me-tasks?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=USERASSIGNEDME&CURRENT_DATE=<?php echo date('Y-m-d'); ?>&CREATOR_ID='+pid;
        showAssignMeNewDue(url, 1, 'tasks');

        activeTab($(this), $('#assignMe'));
    }
	function funGetTaskAssignedMeUsersRep(pid) {
		
		 $.ajax({
	url: '<?php echo $url; ?>api2/tasks/fetch-member-details?USER_ID='+pid,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {                
       if(data.STATUS !== 'ERROR') {
            var data = JSON.parse(data.DATA);
			//console.log(data);
			//alert(data.FULL_NAME+'=aa');			

	$('#breadcrumb').html('Home &raquo; Assigned to Me &raquo; '+data.FULL_NAME + ' &raquo; Repeated Tasks');

  
        }
            }
        }); 
        var url = '<?php echo $url; ?>api2/tasks/fetch-me-tasks?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=USERASSIGNEDME&CURRENT_DATE=<?php echo date('Y-m-d'); ?>&CREATOR_ID='+pid;
        showAssignMeNewRep(url, 1, 'tasks');

        activeTab($(this), $('#assignMe'));
    }
	
	function funGetTaskAssignedMeTaskUsers(pid) {
		 $.ajax({
	url: '<?php echo $url; ?>api2/tasks/fetch-member-details?USER_ID='+pid,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {                
       if(data.STATUS !== 'ERROR') {
            var data = JSON.parse(data.DATA);
			//console.log(data);
			//alert(data.FULL_NAME+'=aa');			

	$('#breadcrumb').html('Home &raquo; Assigned to Me &raquo; '+data.FULL_NAME + ' &raquo; Todo Tasks');

  
        }
            }
        }); 
        var url = '<?php echo $url; ?>api2/tasks/fetch-me-tasks?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=USERASSIGNEDME&CREATOR_ID='+pid;
        showAssignMeNewTask(url, 1, 'tasks');

        activeTab($(this), $('#assignMe'));
    }
	function funGetTaskAssignedMeTaskUsersDue(pid) {
        var url = '<?php echo $url; ?>api2/tasks/fetch-me-tasks?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=USERASSIGNEDME&CREATOR_ID='+pid;
        showAssignMeNewTaskDue(url, 1, 'tasks');

        activeTab($(this), $('#assignMe'));
    }
	function funGetTaskAssignedMeTaskUsersRep(pid) {
		 $.ajax({
	url: '<?php echo $url; ?>api2/tasks/fetch-member-details?USER_ID='+pid,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {                
       if(data.STATUS !== 'ERROR') {
            var data = JSON.parse(data.DATA);
			//console.log(data);
			//alert(data.FULL_NAME+'=aa');			

	$('#breadcrumb').html('Home &raquo; Assigned to Other &raquo; '+data.FULL_NAME + ' &raquo; Repeated Tasks');

  
        }
            }
        }); 
        var url = '<?php echo $url; ?>api2/tasks/fetch-me-tasks?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=USERASSIGNEDME&CREATOR_ID='+pid;
        showAssignMeNewRep(url, 1, 'tasks');

        activeTab($(this), $('#assignMe'));
    }
		function funGetTaskAssignedOtherUsersDue(pid) {
        var url = '<?php echo $url; ?>api2/tasks/fetch-others-tasks-dd?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=USERASSIGNEDOTHERS&CURRENT_DATE=<?php echo date('Y-m-d'); ?>&ASSIGNED_ID='+pid;
        showAssignOtherNewDue(url, 1, 'tasks');

        activeTab($(this), $('#assignMe'));
    }
	function funGetTaskAssignedOtherUsersRep(pid) {
		
		 $.ajax({
	url: '<?php echo $url; ?>api2/tasks/fetch-member-details?USER_ID='+pid,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {                
       if(data.STATUS !== 'ERROR') {
            var data = JSON.parse(data.DATA);
			//console.log(data);
			//alert(data.FULL_NAME+'=aa');			

	$('#breadcrumb').html('Home &raquo; Due Today &raquo; Assigned to Other &raquo; '+data.FULL_NAME + ' &raquo; Repeated Tasks');

  
        }
            }
        }); 
		

		
        var url = '<?php echo $url; ?>api2/tasks/fetch-others-tasks-dd?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=USERASSIGNEDOTHERS&CURRENT_DATE=<?php echo date('Y-m-d'); ?>&ASSIGNED_ID='+pid;
        showAssignOtherNewRep(url, 1, 'tasks');

        activeTab($(this), $('#assignMe'));
    }
	function funGetTaskAssignedOtherUsersTaskRep(pid) {
		 $.ajax({
	url: '<?php echo $url; ?>api2/tasks/fetch-member-details?USER_ID='+pid,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {                
       if(data.STATUS !== 'ERROR') {
            var data = JSON.parse(data.DATA);
			//console.log(data);
			//alert(data.FULL_NAME+'=aa');			

	$('#breadcrumb').html('Home &raquo; Assigned to Other &raquo; '+data.FULL_NAME + ' &raquo; Repeated Tasks');

  
        }
            }
        }); 
		
        var url = '<?php echo $url; ?>api2/tasks/fetch-others-tasks?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=USERASSIGNEDOTHERS&ASSIGNED_ID='+pid;
        showAssignOtherNewTaskRep(url, 1, 'tasks');

        activeTab($(this), $('#assignMe'));
    }
	function funGetTaskAssignedOtherUsersTask(pid) {
		 $.ajax({
	url: '<?php echo $url; ?>api2/tasks/fetch-member-details?USER_ID='+pid,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {                
       if(data.STATUS !== 'ERROR') {
            var data = JSON.parse(data.DATA);
			//console.log(data);
			//alert(data.FULL_NAME+'=aa');			

	$('#breadcrumb').html('Home &raquo; Assigned to Other &raquo; '+data.FULL_NAME + ' &raquo; Todo Tasks');

  
        }
            }
        }); 
		
		
        var url = '<?php echo $url; ?>api2/tasks/fetch-others-tasks?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=USERASSIGNEDOTHERS&ASSIGNED_ID='+pid;
        showAssignOtherNewTask(url, 1, 'tasks');

        activeTab($(this), $('#assignMe'));
    }
	function funGetTaskAssignedOtherUsersTaskDue(pid) {
        var url = '<?php echo $url; ?>api2/tasks/fetch-others-tasks?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=USERASSIGNEDOTHERS&ASSIGNED_ID='+pid;
        showAssignOtherNewTaskDue(url, 1, 'tasks');

        activeTab($(this), $('#assignMe'));
    }
	
	function funGetTaskCCUsersDDDue(pid) {
        var url = 'api2/tasks/fetch-only-cc-tasks-dd?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=CCUSERDUE&CURRENT_DATE=<?php echo date('Y-m-d'); ?>&CREATOR_ID='+pid;
        showCCtaskDDNewDue(url, 1, 'tasks');

        activeTab($(this), $('#assignMe'));
    }
	function funGetTaskCCUsersDDRep(pid) {
		
		 $.ajax({
	url: '<?php echo $url; ?>api2/tasks/fetch-member-details?USER_ID='+pid,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {                
       if(data.STATUS !== 'ERROR') {
            var data = JSON.parse(data.DATA);
			//console.log(data);
			//alert(data.FULL_NAME+'=aa');			

	$('#breadcrumb').html('Home &raquo; Due Today &raquo; CC Tasks &raquo; '+data.FULL_NAME + ' &raquo; Repeated Tasks');

  
        }
            }
        }); 
		
		
		
        var url = '<?php echo $url; ?>api2/tasks/fetch-only-cc-tasks-dd?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=CCUSERREP&CURRENT_DATE=<?php echo date('Y-m-d'); ?>&CREATOR_ID='+pid;
        showCCtaskDDNewRep(url, 1, 'tasks');

        activeTab($(this), $('#assignMe'));
    }
	function funGetTaskCCUsersTodo(pid) {
		
		 $.ajax({
	url: '<?php echo $url; ?>api2/tasks/fetch-member-details?USER_ID='+pid,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {                
       if(data.STATUS !== 'ERROR') {
            var data = JSON.parse(data.DATA);
			//console.log(data);
			//alert(data.FULL_NAME+'=aa');			

	$('#breadcrumb').html('Home &raquo; CC Tasks &raquo; '+data.FULL_NAME + ' &raquo; Todo Tasks');

  
        }
            }
        }); 
		
        var url = '<?php echo $url; ?>api2/tasks/fetch-cc-tasks-only?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=CCTASKTODO&CREATOR_ID='+pid;
        showCCtaskNewTodo(url, 1, 'tasks');

        activeTab($(this), $('#assignMe'));
    }
	function funGetTaskCCUsersNoRep(pid) {
        var url = '<?php echo $url; ?>api2/tasks/fetch-cc-tasks-only?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=CCTASKNOREP&CREATOR_ID='+pid;
        showCCtaskNewNoRep(url, 1, 'tasks');

        activeTab($(this), $('#assignMe'));
    }
	function funGetTaskCCUsersRep(pid) {
		 $.ajax({
	url: '<?php echo $url; ?>api2/tasks/fetch-member-details?USER_ID='+pid,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {                
       if(data.STATUS !== 'ERROR') {
            var data = JSON.parse(data.DATA);
			//console.log(data);
			//alert(data.FULL_NAME+'=aa');			

	$('#breadcrumb').html('Home &raquo; CC Tasks &raquo; '+data.FULL_NAME + ' &raquo; Repeated Tasks');

  
        }
            }
        }); 
		
        var url = '<?php echo $url; ?>api2/tasks/fetch-cc-tasks-only?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=CCTASKREP&CREATOR_ID='+pid;
        showCCtaskNewRep(url, 1, 'tasks');

        activeTab($(this), $('#assignMe'));
    }
	
 function showAssignOthersUsersList()
	 {
		 $.ajax({
            url:  '<?php echo $url; ?>api2/tasks/fetch-others-tasks?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=USERSLIST',
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {                
         submenuOthers.empty();
       if(data.STATUS !== 'ERROR') {
            var data = JSON.parse(data.DATA);
 submenuOthers.append('<ul class="menu-items nav flex-column flex-nowrap"  style="margin:1px;">');
           // console.log(data);	
				var bgclr = ' background:rgba(237, 242, 249, 0.77);';
			for (var i = 0; i < data.length; i++) {
				
				if(i%2==0) { bgclr = ' background:rgba(255, 255, 255, 0.8);'; } else { bgclr = ' background:rgba(237, 242, 249, 0.77);'; }
			submenuOthers.append('<li class="nav-item" style="border:1px solid #aaa; margin:2px 6px; padding:3px; '+bgclr+' border-radius:4px;"><a class="nav-link" style="padding:3px; font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica Neue; font-size:0.85em; color:#084557;" href="#submenuOthers2'+data[i].USER_ID+'" data-toggle="collapse" data-target="#submenuOthers2'+data[i].USER_ID+'" id="assignOthers2'+data[i].USER_ID+'" onclick="funshowSubMenuOther2('+data[i].USER_ID+',\''+data[i].FULL_NAME+'\');" >'+data[i].FULL_NAME+'<img src="assets/images/add-ico-20.png" style="cursor:pointer; float:right;" id="addIndivisualTask44" uname="'+data[i].FULL_NAME+'" uid="'+data[i].USER_ID+'"   title="Add Indivisual Task" ></a>'+
			'<div class="collapse" id="submenuOthers2'+data[i].USER_ID+'" aria-expanded="false" style=""></div></li>');
            }
			
			submenuOthers.append('</ul>');
			
			
			if(data.length == 0){
				submenuOthers.empty();
			// submenuOthers.append('<ul class="flex-column nav"><li class="nav-item"><a class="nav-link" href="#" id="Others_0">NOT FOUND</a></li></ul>');
			}
			
        } else {
            submenuOthers.append('<ul class="flex-column nav"><li class="nav-item"><a class="nav-link" href="#" id="Others_0">'+data.MESSAGE+'</a></li></ul>');
        }
                  
            }
        }); 
	 }
 function showAssignOthersUsersListDD()
	 {
		 $.ajax({
            url:  '<?php echo $url; ?>api2/tasks/fetch-others-tasks-dd?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=USERSLIST&CURRENT_DATE=<?php echo date('Y-m-d'); ?>',
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {                
         submenuOtherDD.empty();
       if(data.STATUS !== 'ERROR') {
            var data = JSON.parse(data.DATA);
			
 submenuOtherDD.append('<ul class="flex-column nav">');
           // console.log(data);	
				
			for (var i = 0; i < data.length; i++) {
			submenuOtherDD.append('<li class="nav-item" style="margin:1px 15px;"><a class="nav-link collapsed" href="#submenuOtherDD2'+data[i].USER_ID+'" data-toggle="collapse" data-target="#submenuOtherDD2'+data[i].USER_ID+'" onclick="funshowSubMenuOtherDD2('+data[i].USER_ID+',\''+data[i].FULL_NAME+'\');" >'+data[i].FULL_NAME+' <img src="assets/images/add-ico-20.png" style="cursor:pointer; float:right;" id="addIndivisualTask3Due" uname="'+data[i].FULL_NAME+'" uid="'+data[i].USER_ID+'"   title="Add Indivisual Task" ></a><div class="collapse" id="submenuOtherDD2'+data[i].USER_ID+'" aria-expanded="false"></div></li>');
            }
			
			submenuOtherDD.append('</ul>');
			
			
			if(data.length == 0){
				submenuOtherDD.empty();
			// submenuOthers.append('<ul class="flex-column nav"><li class="nav-item"><a class="nav-link" href="#" id="Others_0">NOT FOUND</a></li></ul>');
			}
			
        } else {
            submenuOtherDD.append('<ul class="flex-column nav"><li class="nav-item"><a class="nav-link" href="#" id="Others_0">'+data.MESSAGE+'</a></li></ul>');
        }
                  
            }
        }); 
	 }
	 	
function showAssignMeUsersListDD()
	 {
		 $.ajax({
            url:  '<?php echo $url; ?>api2/tasks/fetch-me-tasks?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=USERSLIST&CURRENT_DATE=<?php echo date('Y-m-d'); ?>',
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {                
         submenuMeDD.empty();
       if(data.STATUS !== 'ERROR') {
            var data = JSON.parse(data.DATA);
 submenuMeDD.append('<ul class="flex-column nav">');
           // console.log(data);	
				
			for (var i = 0; i < data.length; i++) {
			submenuMeDD.append('<li class="nav-item"><a class="nav-link collapsed" id="assignMe'+data[i].USER_ID+'"  onclick="funshowSubMenuDD2('+data[i].USER_ID+',\''+data[i].FULL_NAME+'\')" href="#submenuMeDD2'+data[i].USER_ID+'" data-toggle="collapse" data-target="#submenuMeDD2'+data[i].USER_ID+'" >'+data[i].FULL_NAME+'</a>'+
			'<div class="collapse" id="submenuMeDD2'+data[i].USER_ID+'" aria-expanded="false"></div></li>');
            }
			
			submenuMeDD.append('</ul>');
			
			
			if(data.length == 0){
				submenuMeDD.empty();
			// submenuOthers.append('<ul class="flex-column nav"><li class="nav-item"><a class="nav-link" href="#" id="Others_0">NOT FOUND</a></li></ul>');
			}
			
        } else {
            submenuMeDD.append('<ul class="flex-column nav"><li class="nav-item"><a class="nav-link" href="#" id="Others_0">'+data.MESSAGE+'</a></li></ul>');
        }
                  
            }
        }); 
	 }
function showCCtaskUsersListDD()
	 {
		 $.ajax({
	 url : '<?php echo $url; ?>api2/tasks/fetch-cc-due-tasks-users?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=USERSLIST&CURRENT_DATE=<?php echo date('Y-m-d'); ?>',
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {                
         submenuCCtaskDD.empty();
       if(data.STATUS !== 'ERROR') {
            var data = JSON.parse(data.DATA);
 submenuCCtaskDD.append('<ul class="flex-column nav">');
           // console.log(data);	
				
			for (var i = 0; i < data.length; i++) {
			submenuCCtaskDD.append('<li class="nav-item" style="margin:1px 15px;"><a class="nav-link collapsed" id="assignMe'+data[i].USER_ID+'" onclick="funshowSubMenuCCtaskDD2('+data[i].USER_ID+',\''+data[i].FULL_NAME+'\')" href="#submenuCCtaskDD2'+data[i].USER_ID+'" data-toggle="collapse" data-target="#submenuCCtaskDD2'+data[i].USER_ID+'" >'+data[i].FULL_NAME+'</a>'+
			'<div class="collapse" id="submenuCCtaskDD2'+data[i].USER_ID+'" aria-expanded="false"></div></li>');
            }
			submenuCCtaskDD.append('</ul>');
			if(data.length == 0){
				submenuCCtaskDD.empty();
			// submenuOthers.append('<ul class="flex-column nav"><li class="nav-item"><a class="nav-link" href="#" id="Others_0">NOT FOUND</a></li></ul>');
			}
			
        } else {
            submenuCCtaskDD.append('<ul class="flex-column nav"><li class="nav-item"><a class="nav-link" href="#" id="Others_0">'+data.MESSAGE+'</a></li></ul>');
        }
                  
            }
        }); 
	 }

function funshowProjectTasks(PROJECT_ID,PROJECT_NAME,CREATOR_ID)
{	
$('#breadcrumb').html('Home &raquo; My Projects &raquo; '+PROJECT_NAME);	 

        $.ajax({
            url: '<?php echo $url; ?>api2/projects/fetch-tasks?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&PROJECT_ID='+PROJECT_ID,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (datax) {
				 
					taskList.empty();
				   if(datax.STATUS !== 'ERROR') {
						var data = JSON.parse(datax.DATA);
						if(CREATOR_ID == <?php echo $_SESSION['logged_in']['USER_ID']; ?>)
						{
			   taskList.append('<div class="mail-list" style="background: grey;color:white; clear:both;"><div class="row" style="width:100%; margin:0px; padding:0px;"><div class="col-8"><h6 style="margin:0px; float:left; padding:5px 0px;">Projects Tasks List</h6></div><div class="col-4"><span style="float:right;"><img src="assets/images/add-member.png" style="padding:0px; margin:0px;cursor:pointer;" title="Add Project Member" id="addMemberProject" data-pname="'+PROJECT_NAME+'" data-pid="'+PROJECT_ID+'" /></span></div></div></div>');
						}
						else
						{
			    taskList.append('<div class="mail-list" style="background: grey;color:white;"><h6 style="margin:0px">Projects Tasks List</h6></div>');
						}
				
						 console.log(data);		
						 var tasktype = 'TODO';
						 
						 
						 data.sort(function(a, b){
    var x = a.TASK_STATUS.toLowerCase();
    var y = b.TASK_STATUS.toLowerCase();
    if (x > y) {return -1;}
    if (x < y) {return 1;}
    return 0;
  });
						 
						 var clrgreen = "";
						for (var i = 0; i < data.length; i++) {
							if(data[i].TASK_STATUS == 'COMPLETED')  clrgreen = 'style="color:green;"';
								if(data[i].DUE_DATE=="0" || data[i].DUE_DATE == ""){tasktype = 'TODO';} else { tasktype = 'Date:'+convertDate(data[i].DUE_DATE);}
								
							taskList.append('<div class="mail-list taskDetailsProj" data-assigned_id="' + data[i].ASSIGNED_ID + '" data-task_id="' + data[i].TASK_ID + '">' +
								'<div class="content">' +
								'<p class="message_text" '+clrgreen+'>' + data[i].TASK_TITLE + '</p>' +
								'<p class="message_text" style="font-size:12px; color:#666;">Assigned: '+ data[i].FULL_NAME+'</p>' +
								'<p class="message_text">' +tasktype + '</p>' +
								'</div><div class="message_text" style="width:15%;float:right;text-align:right;color:#000; font-size:12px;">Status:<br />'+data[i].TASK_STATUS+'</div>' +
								'</div>');
						}
						
					} else {
						taskList.empty();
						if(CREATOR_ID == <?php echo $_SESSION['logged_in']['USER_ID']; ?>)
						{
			   taskList.append('<div class="mail-list" style="background: grey;color:white; clear:both;"><div class="row" style="width:100%; margin:0px; padding:0px;"><div class="col-8"><h6 style="margin:0px; float:left; padding:5px 0px;">Projects Tasks List</h6></div><div class="col-4"><span style="float:right;"><img src="assets/images/add-member.png" style="padding:0px; margin:0px;cursor:pointer;" title="Add Project Member" id="addMemberProject"  data-pname="'+PROJECT_NAME+'" data-pid="'+PROJECT_ID+'" /></span></div></div></div>');
						}
						else
						{
			    taskList.append('<div class="mail-list" style="background: grey;color:white;"><h6 style="margin:0px">Projects Tasks List</h6></div>');
						}
						taskList.append('<div class="error">No Project Task Found!</div>')
					}
							
            }
			
        });
		taskDetail.empty();
		taskDetail.append('<div class="error">No Project Task Seleted!</div>')
 
	}
function timeConverter(UNIX_timestamp){
  var a = new Date(UNIX_timestamp * 1000);
  var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
  var year = a.getFullYear();
  var month = months[a.getMonth()];
  var mon = a.getMonth()+1;
  var date = a.getDate();
  var hour = a.getHours();
  var min = a.getMinutes();
  var sec = a.getSeconds();
  var time = mon + '/' + date + '/' + year + ' ' + hour + ':' + min + ':' + sec ;
  return time;
  //01/25/20 05:00:00 AM
}


function funshowUsersShipments(uid)
{	

var seleteduser = "";
		 $.ajax({
	url: '<?php echo $url; ?>api2/tasks/fetch-member-details?USER_ID='+uid,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {                
       if(data.STATUS !== 'ERROR') {
            var data = JSON.parse(data.DATA);
			//console.log(data);
			//alert(data.FULL_NAME+'=aa');
			seleteduser = data.FULL_NAME;	

	$('#breadcrumb').html('Home &raquo; Adv Shipment/RMA &raquo; '+seleteduser);

  
        }
            }
        }); 
		
		
	 taskList.empty();
	 taskDetail.empty();

	/////////////////////////////////////////////
	var url = '<?php echo $url; ?>api2/shipments/fetch-shipment-group?CREATOR_ID='+uid+'&USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>';
	 $.ajax({
		url: url,
		type: 'GET',
		dataType: 'json',
		cache: false,
		success: function (data) {
			taskList.empty();
			taskDetail.empty();
			if(data.STATUS !== 'ERROR') {
				var data = JSON.parse(data.DATA);
				showTaskListShipmentsRed(data);
				showTaskListShipmentsGreen(data);
				
	} else {
		taskList.append('<div class="error">' + data.MESSAGE + '</div>')
	}

		}
	});
	activeTab($(this), 0);

}

function funshowUsersShipmentsSearched(keyValue)
{
	
	
	 taskDetail.empty();

	/////////////////////////////////////////////
	var url = '<?php echo $url; ?>api2/shipments/fetch-shipment-group-search?TASK_TITLE='+keyValue+'&USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>';
	 $.ajax({
		url: url,
		type: 'GET',
		dataType: 'json',
		cache: false,
		success: function (data) {
			taskDetail.empty();
			if(data.STATUS !== 'ERROR') {
				var data = JSON.parse(data.DATA);
				showTaskListShipmentsRedSearched(data);
				showTaskListShipmentsGreenSearched(data);
				
	} else {
		taskList.append('<div class="error">' + data.MESSAGE + '</div>')
	}

		}
	});
	activeTab($(this), 0);

}

function showCCtaskUsersList()
	 {
		 $.ajax({
	 url : '<?php echo $url; ?>api2/tasks/fetch-cc-tasks-users?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=USERSLIST',
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {                
         submenuCC.empty();
       if(data.STATUS !== 'ERROR') {
            var data = JSON.parse(data.DATA);
 submenuCC.append('<ul class="menu-items nav flex-column flex-nowrap"  style="margin:1px;">');
           // console.log(data);	
				var bgclr = ' background:rgba(237, 242, 249, 0.77);';
			for (var i = 0; i < data.length; i++) {
				if(i%2==0) { bgclr = ' background:rgba(255, 255, 255, 0.8);'; } else { bgclr = ' background:rgba(237, 242, 249, 0.77);'; }
			submenuCC.append('<li class="nav-item" style="border:1px solid #aaa; margin:2px 10px; padding:3px; '+bgclr+' border-radius:4px;"><a class="nav-link" style="padding:3px; font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica Neue; font-size:0.85em; color:#084557;" id="assignCCTask2'+data[i].USER_ID+'" onclick="funshowSubMenuCCtask2('+data[i].USER_ID+',\''+data[i].FULL_NAME+'\')" href="#submenuCCtask2'+data[i].USER_ID+'" data-toggle="collapse" data-target="#submenuCCtask2'+data[i].USER_ID+'" >'+data[i].FULL_NAME+' <span id="projectTaskCount" class="badge-pill" style="width:auto; font-weight:normal;">-</span></a></li>'+
			'<div class="collapse" id="submenuCCtask2'+data[i].USER_ID+'" aria-expanded="false"></div></li>');
            }
			submenuCC.append('</ul>');
			if(data.length == 0){
				submenuCC.empty();
			// submenuOthers.append('<ul class="flex-column nav"><li class="nav-item"><a class="nav-link" href="#" id="Others_0">NOT FOUND</a></li></ul>');
			}
			
        } else {
            submenuCC.append('<ul class="flex-column nav"><li class="nav-item"><a class="nav-link" href="#" id="Others_0">'+data.MESSAGE+'</a></li></ul>');
        }
                  
            }
        }); 
	 }	 
	 		
function showAssignMeUsersList()
	 {
		 $.ajax({
            url:  '<?php echo $url; ?>api2/tasks/fetch-me-tasks?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=USERSLIST',
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {                
         submenuMe.empty();
       if(data.STATUS !== 'ERROR') {
            var data = JSON.parse(data.DATA);
 submenuMe.append('<ul class="menu-items nav flex-column flex-nowrap"  style="margin:1px;">');
          //  console.log(data);	
				var bgclr = ' background:rgba(237, 242, 249, 0.77);';
			for (var i = 0; i < data.length; i++) {
				if(i%2==0) { bgclr = ' background:rgba(255, 255, 255, 0.8);'; } else { bgclr = ' background:rgba(237, 242, 249, 0.77);'; }
			submenuMe.append('<li class="nav-item" style="border:1px solid #aaa; margin:2px 6px; padding:3px; '+bgclr+' border-radius:4px;"><a class="nav-link collapsed" id="assignMe2'+data[i].USER_ID+'" style="padding:3px; font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica Neue; font-size:0.85em; color:#084557;" href="#submenuMe2'+data[i].USER_ID+'" data-toggle="collapse" data-target="#submenuMe2'+data[i].USER_ID+' " onclick="funshowSubMenu2('+data[i].USER_ID+',\''+data[i].FULL_NAME+'\')"> '+data[i].FULL_NAME+'</a><div class="collapse" id="submenuMe2'+data[i].USER_ID+'" aria-expanded="false"></div></li>');
            }//aliraza
			submenuMe.append('</ul>');
			
			if(data.length == 0){
				submenuMe.empty();
			// submenuOthers.append('<ul class="flex-column nav"><li class="nav-item"><a class="nav-link" href="#" id="Others_0">NOT FOUND</a></li></ul>');
			}
			
        } else {
            submenuMe.append('<ul class="flex-column nav"><li class="nav-item"><a class="nav-link" href="#" id="Others_0">'+data.MESSAGE+'</a></li></ul>');
        }
                  
            }
        }); 
	 }
	 	


   <?php /*  //CC Tasks
   /* $('#ccTasks').click(function() {
        var url = '< ?php echo $url; ?>api2/tasks/fetch-only-cc-tasks?USER_ID=< ?php echo $_SESSION['logged_in']['USER_ID']; ?>';
        showCCTasks(url, 0, 'tasks');

        activeTab($(this), 0);
    });*/ ?>


    //Shipment Tasks
    $('#shipmentTask').click(function(e) {
       $('#breadcrumb').html('Home &raquo; Adv Shipment/RMA ');
		funActiveOnly('shipmentTask',e);
		 taskList.empty();
		 //taskDetail.empty();
	
		 $.ajax({
	 url : '<?php echo $url; ?>api2/shipments/fetch-shipment-users?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>',
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {                
         submenuShipmentUsers.empty();
       if(data.STATUS !== 'ERROR') {
            var data = JSON.parse(data.DATA);
			
			if(data.length > 0) {
 submenuShipmentUsers.append('<ul class="menu-items nav flex-column flex-nowrap"  style="margin:1px 10px;">');
           // console.log(data);	
				var bgclr = ' background:rgba(237, 242, 249, 0.77);';
			for (var i = 0; i < data.length; i++) {
				if(i%2==0) { bgclr = ' background:rgba(255, 255, 255, 0.8);'; } else { bgclr = ' background:rgba(237, 242, 249, 0.77);'; }
			submenuShipmentUsers.append('<li class="nav-item" style="border:1px solid #aaa; margin:2px 10px; padding:3px; '+bgclr+' border-radius:4px;"><a class="nav-link" style="padding:3px; font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica Neue; font-size:0.75em; color:#084557;" id="lnkShip_'+data[i].CREATOR_ID+'" onclick="funshowUsersShipments('+data[i].CREATOR_ID+')" href="#" data-toggle="collapse" data-target="#'+data[i].CREATOR_ID+'" >'+data[i].FULL_NAME+' <span id="projectTaskCount" class="badge-pill" style="width:auto; font-weight:normal;">'+data[i].total+'</span></a></li>');
            }
			submenuShipmentUsers.append('</ul>');
			}
			if(data.length == 0){
				submenuShipmentUsers.empty();
			// submenuOthers.append('<ul class="flex-column nav"><li class="nav-item"><a class="nav-link" href="#" id="Others_0">NOT FOUND</a></li></ul>');
			}
			
        } else {
            submenuShipmentUsers.append('<ul class="flex-column nav"><li class="nav-item"><a class="nav-link" href="#" id="Others_0">'+data.MESSAGE+'</a></li></ul>');
        }
                  
            }
        }); 
	 
		/////////////////////////////////////////////		 
         $.ajax({
            url: '<?php echo $url; ?>api2/shipments/fetch-shipments?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>',
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
				taskList.empty();
				//taskDetail.empty();
				if(data.STATUS !== 'ERROR') {
					var data = JSON.parse(data.DATA);
					document.getElementById("shipmentTaskCount").innerHTML = data.length;	
					// show pending and approved shiment_status 
					showTaskListShipmentsWhite(data);
					// show groups i.e. user groups list
					//showUsersGroupsShimpments();
					
        } else {
            taskList.append('<div class="error">' + data.MESSAGE + '</div>')
        }
    
            }
        });
        activeTab($(this), 0);
    });
function showSearchTaskListShipmentsWhite(d)
{
	var ctrd = 0;
	taskList.append('<div class="mail-list" style="background: grey;color:white;"><h6 style="margin:0px">Total <span id="totalsearched">.'+parseInt(d.length)+'</span> Searched Advance Shipment/RMA (PENDING/APPROVED/REJECTED)</h6></div>');
	
	for (var i = 0; i < d.length; i++) {
		
		var advanceRMA="";
		var advanceShipmentRMA=d[i].SHIPMENT_CATEGORY;
		if(advanceShipmentRMA=="SHIPMENT"){advanceRMA="A.S";}
		if(advanceShipmentRMA=="RMA"){advanceRMA="A.R";}
//		if(d[i].SHIPMENT_STATUS=="PENDING" || d[i].SHIPMENT_STATUS=="APPROVED" || d[i].SHIPMENT_STATUS=="REJECTED"){
			if((d[i].SHIPMENT_STATUS=="PENDING" || d[i].SHIPMENT_STATUS=="APPROVED" || d[i].SHIPMENT_STATUS=="REJECTED"))
			{
			ctrd++;
				taskList.append('<div class="mail-list shipmentDetails" data-task_id="' + d[i].SHIPMENT_ID + '"><div class="content" style="width:97%;">' +
				'<div class="row"  style="padding:0px; margin:0px;"><div class="col-md-12" style="padding:0px; margin:0px;"><p class="message_text">' + advanceRMA + ' By ' + d[i].FULL_NAME+'</p></div></div>' +
				'<div class="row"><div class="col-md-8"><p class="message_text">' + d[i].CUSTOMER_NAME + '</p></div><div class="col-md-4"  style="text-align:right;">' + convertDate(d[i].CREATED_DATE) + '</div></div>' + 
				'<div class="row"><div class="col-md-8"><p class="message_text">INVOICE# ' + d[i].INVOICE_NUMBER + '</p></div><div class="col-md-4"  style="text-align:right;">PRICE $ '+ d[i].SHIPMENT_TITLE +'</div></div>' + 
				'<div class="row"><div class="col-md-8"><p class="message_text">Shipping Status: ' + d[i].SHIPMENT_STATUS + '</p></div><div class="col-md-4"  style="text-align:right;">Final Status:'+ d[i].FINAL_STATUS +'</div></div>' +
				'</div></div>');
		   }			   	   
	}
	$('#totalsearched').text(ctrd);//<span id="totalsearched">
}

function showTaskListShipmentsWhite(d)
{
	var ctrd = 0;
	taskList.append('<div class="mail-list" style="background: grey;color:white;"><h6 style="margin:0px">Advance Shipment/RMA</h6></div>');
	
	<?php /*?>if(d.length == 0) {
		taskList.append('<div class="mail-list" ><h6 style="color:red;">No Shipment/RMA Found Approved with Final Status Pending!</h6></div>');
	}<?php */?>
	
	
	for (var i = 0; i < d.length; i++) {
		
		var advanceRMA="";
		var advanceShipmentRMA=d[i].SHIPMENT_CATEGORY;
		if(advanceShipmentRMA=="SHIPMENT"){advanceRMA="A.S";}
		if(advanceShipmentRMA=="RMA"){advanceRMA="A.R";}
		if(d[i].SHIPMENT_STATUS=="PENDING" || d[i].SHIPMENT_STATUS=="APPROVED" || d[i].SHIPMENT_STATUS=="REJECTED"){
			ctrd++;
				taskList.append('<div class="mail-list shipmentDetails" data-task_id="' + d[i].SHIPMENT_ID + '">' +
				'<div class="content">' +
				'<p class="message_text">' + advanceRMA + ' By ' + d[i].FULL_NAME+'</p>' +
				'<p class="message_text">' + d[i].CUSTOMER_NAME + '</p>' +
				'<p class="message_text">INVOICE# ' + d[i].INVOICE_NUMBER + '  PRICE $ '+ d[i].SHIPMENT_TITLE +'</p>' +
				'<p class="message_text">' + convertDate(d[i].CREATED_DATE) + '</p>' +
				'</div>' +
				'</div>');
		}
	}
	if(ctrd == 0) {
		taskList.append('<div class="mail-list" ><h6 style="color:red;">No Shipment/RMA Found Approved with Final Status Pending!</h6></div>');
	}
}
function showTaskListShipmentsRed(dy)
{
	var ctrdy = 0;
	//console.log(dy);
	taskList.append('<div class="mail-list" style="background:#ad0000;color:white;"><h6 style="margin:0px">Advance Shipment/RMA - PENDING</h6></div>');
<?php /*?>	if(dy.length == 0) {
		taskList.append('<div class="mail-list" ><h6 style="color:red;">No Shipment/RMA Found With Final Status Pending!</h6></div>');
	}
<?php */?>
	for (var i = 0; i < dy.length; i++) {		
		var advanceRMA="";
		var advanceShipmentRMA=dy[i].SHIPMENT_CATEGORY;
		if(advanceShipmentRMA=="SHIPMENT"){advanceRMA="A.S";}
		if(advanceShipmentRMA=="RMA"){advanceRMA="A.R";}
		if(dy[i].FINAL_STATUS=="PENDING" ){
			ctrdy++;
				taskList.append('<div class="mail-list shipmentDetails" data-task_id="' + dy[i].SHIPMENT_ID + '">' +
				'<div class="content">' +
				'<p class="message_text" style="color:#ad0000;">' + advanceRMA + ' By ' + dy[i].FULL_NAME+'</p>' +
				'<p class="message_text" style="color:#ad0000;">' + dy[i].CUSTOMER_NAME + '</p>' +
				'<p class="message_text" style="color:#ad0000;">INVOICE# ' + dy[i].INVOICE_NUMBER + '  PRICE $ '+ dy[i].SHIPMENT_TITLE +'</p>' +
				'<p class="message_text" style="color:#ad0000;">' + convertDate(dy[i].CREATED_DATE) + '</p>' +
				'</div><div class="message_text" style="width:15%;float:right;text-align:right;color:#ad0000;">'+dy[i].DAYS+'</div>' +
				'</div>');
		}
	}
	if(ctrdy == 0) {
		taskList.append('<div class="mail-list" ><h6 style="color:red;">No Shipment/RMA Found With Final Status Pending!</h6></div>');
	}
}
function showTaskListShipmentsGreen(dx)
{
	var ctrdx = 0;
	//console.log(dx);
	taskList.append('<div class="mail-list" style="background:#038912;color:white;"><h6 style="margin:0px">Advance Shipment/RMA - RECEIVED</h6></div>');
	
	for (var i = 0; i < dx.length; i++) {		
		var advanceRMA="";
		var advanceShipmentRMA=dx[i].SHIPMENT_CATEGORY;
		if(advanceShipmentRMA=="SHIPMENT"){advanceRMA="A.S";}
		if(advanceShipmentRMA=="RMA"){advanceRMA="A.R";}
		if(dx[i].FINAL_STATUS=="RECEIVED" ){
			ctrdx++;
				taskList.append('<div class="mail-list shipmentDetails" data-task_id="' + dx[i].SHIPMENT_ID + '">' +
				'<div class="content">' +
				'<p class="message_text" style="color:#038912;">' + advanceRMA + ' By ' + dx[i].FULL_NAME+'</p>' +
				'<p class="message_text" style="color:#038912;">' + dx[i].CUSTOMER_NAME + '</p>' +
				'<p class="message_text" style="color:#038912;">INVOICE# ' + dx[i].INVOICE_NUMBER + '  PRICE $ '+ dx[i].SHIPMENT_TITLE +'</p>' +
				'<p class="message_text" style="color:#038912;">' + convertDate(dx[i].CREATED_DATE) + '</p>' +
				'</div><div class="message_text" style="width:15%;float:right;text-align:right;color:#038912;">'+dx[i].DAYS+'</div>' +
				'</div>');
		}
	}
	if(ctrdx == 0) {
		taskList.append('<div class="mail-list" ><h6 style="color:green;">No Shipment/RMA Found With Final Status Received!</h6></div>');
	}
}

function showTaskListShipmentsRedSearched(dy)
{
	var ctrdy = 0;
	//console.log(dy);
	taskList.append('<div class="mail-list" style="background:#ad0000;color:white;"><h6 style="margin:0px">Searched <span id="totalsearchedy"> 0 </SPAN> Advance Shipment/RMA - FINAL STATUS PENDING</h6></div>');
<?php /*?>	if(dy.length == 0) {
		taskList.append('<div class="mail-list" ><h6 style="color:red;">No Shipment/RMA Found With Final Status Pending!</h6></div>');
	}
<?php */?>
	for (var i = 0; i < dy.length; i++) {		
		var advanceRMA="";
		var advanceShipmentRMA=dy[i].SHIPMENT_CATEGORY;
		if(advanceShipmentRMA=="SHIPMENT"){advanceRMA="A.S";}
		if(advanceShipmentRMA=="RMA"){advanceRMA="A.R";}
		if(dy[i].FINAL_STATUS=="PENDING" ){
			ctrdy++;
				taskList.append('<div class="mail-list shipmentDetails" data-task_id="' + dy[i].SHIPMENT_ID + '">' +
				'<div class="content">' +
				'<p class="message_text" style="color:#ad0000;">' + advanceRMA + ' By ' + dy[i].FULL_NAME+'</p>' +
				'<p class="message_text" style="color:#ad0000;">' + dy[i].CUSTOMER_NAME + '</p>' +
				'<p class="message_text" style="color:#ad0000;">INVOICE# ' + dy[i].INVOICE_NUMBER + '  PRICE $ '+ dy[i].SHIPMENT_TITLE +'</p>' +
				'<p class="message_text" style="color:#ad0000;">' + convertDate(dy[i].CREATED_DATE) + '</p>' +
				'</div><div class="message_text" style="width:15%;float:right;text-align:right;color:#ad0000;">'+dy[i].DAYS+'</div>' +
				'</div>');
		}
	}
	$('#totalsearchedy').text(ctrdy);//<span id="totalsearchedy">
	
}
function showTaskListShipmentsGreenSearched(dx)
{
	var ctrdx = 0;
	//console.log(dx);
	taskList.append('<div class="mail-list" style="background:#038912;color:white;"><h6 style="margin:0px">Searched <span id="totalsearchedx">0</span> Advance Shipment/RMA - RECEIVED</h6></div>');
	
	for (var i = 0; i < dx.length; i++) {		
		var advanceRMA="";
		var advanceShipmentRMA=dx[i].SHIPMENT_CATEGORY;
		if(advanceShipmentRMA=="SHIPMENT"){advanceRMA="A.S";}
		if(advanceShipmentRMA=="RMA"){advanceRMA="A.R";}
		if(dx[i].FINAL_STATUS=="RECEIVED" ){
			ctrdx++;
				taskList.append('<div class="mail-list shipmentDetails" data-task_id="' + dx[i].SHIPMENT_ID + '">' +
				'<div class="content">' +
				'<p class="message_text" style="color:#038912;">' + advanceRMA + ' By ' + dx[i].FULL_NAME+'</p>' +
				'<p class="message_text" style="color:#038912;">' + dx[i].CUSTOMER_NAME + '</p>' +
				'<p class="message_text" style="color:#038912;">INVOICE# ' + dx[i].INVOICE_NUMBER + '  PRICE $ '+ dx[i].SHIPMENT_TITLE +'</p>' +
				'<p class="message_text" style="color:#038912;">' + convertDate(dx[i].CREATED_DATE) + '</p>' +
				'</div><div class="message_text" style="width:15%;float:right;text-align:right;color:#038912;">'+dx[i].DAYS+'</div>' +
				'</div>');
		}
	}
	$('#totalsearchedx').text(ctrdx);
}

   $(document).on('click', '.taskDetailsPersonal', function() {
        var id = $(this).data('task_id');

        $.ajax({
            url: '<?php echo $url; ?>api2/tasks/fetch-details?OBJECT_ID=' + id + '&OBJECT_TYPE=task',
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
                if(data.STATUS === 'SUCCESS') {
                    showTaskDetailPersonal(data.DATA)
                }
            }
        });
    });	

 $(document).on('click', '.taskDetailsAssignOthers', function() {
        var id = $(this).data('task_id');

        $.ajax({
            url: '<?php echo $url; ?>api2/tasks/fetch-details?OBJECT_ID=' + id + '&OBJECT_TYPE=task',
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
                if(data.STATUS === 'SUCCESS') {
                    showTaskDetailAssignOthers(data.DATA)
                }
            }
        });
    });
		
    $(document).on('click', '.taskDetails', function() {
        var id = $(this).data('task_id');

        $.ajax({
            url: '<?php echo $url; ?>api2/tasks/fetch-details?OBJECT_ID=' + id + '&OBJECT_TYPE=task',
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
                if(data.STATUS === 'SUCCESS') {
                    showTaskDetail(data.DATA)
                }
            }
        });
    });
	
		$(document).on('click', '.taskDetailsAMe', function() {
        var id = $(this).data('task_id');

        $.ajax({
            url: '<?php echo $url; ?>api2/tasks/fetch-details?OBJECT_ID=' + id + '&OBJECT_TYPE=task',
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
                if(data.STATUS === 'SUCCESS') {
                    showTaskDetailAMe(data.DATA)
                }
            }
        });
    });
	$(document).on('click', '.taskDetailsCCtaskDD', function() {
        var id = $(this).data('task_id');

        $.ajax({
            url: '<?php echo $url; ?>api2/tasks/fetch-details?OBJECT_ID=' + id + '&OBJECT_TYPE=task',
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
                if(data.STATUS === 'SUCCESS') {
                    showTaskDetailDDCCtask(data.DATA)
                }
            }
        });
    });
	$(document).on('click', '.taskDetailsDDassignMe', function() {
        var id = $(this).data('task_id');

        $.ajax({
            url: '<?php echo $url; ?>api2/tasks/fetch-details?OBJECT_ID=' + id + '&OBJECT_TYPE=task',
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
                if(data.STATUS === 'SUCCESS') {
                    showTaskDetailDDassignMe(data.DATA)
                }
            }
        });
    });
	$(document).on('click', '.taskDetailsDDPersonal', function() {
        var id = $(this).data('task_id');

        $.ajax({
            url: '<?php echo $url; ?>api2/tasks/fetch-details?OBJECT_ID=' + id + '&OBJECT_TYPE=task',
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
                if(data.STATUS === 'SUCCESS') {
                    showTaskDetailDDPersonal(data.DATA)
                }
            }
        });
    });
	/* rafiq create new personal task # form  */
	$(document).on('click', '#addNewPersonal', function() {
		addNewPersonalTask()
     });

	/* rafiq create new personal task # form  */
	$(document).on('click', '#addNewPersonal2', function() {		
		addNewPersonalTask2()
     });

	$(document).on('click', '#delProject', function() {
		var PROJECT_NAME = $(this).data('pname');
		var PROJECT_ID = $(this).data('pid');
		if(confirm('Are you sure, you want to delete Complete Project of "'+PROJECT_NAME+'" of Project ID:'+PROJECT_ID+'?'))
		{
			$.ajax({
        url: '<?php echo $url; ?>api2/projects/delete',
        type: 'POST',
		contentType:'application/x-www-form-urlencoded',
        data:"PROJECT_ID="+PROJECT_ID,
		error: function(err) {
            alert(err.statusText);
        },
        success: function(data) {
				if(data.STATUS !== 'ERROR') 
				{
					taskList.empty();					
					taskList.append('<div class="error">Project: ['+PROJECT_NAME+'] deleted successfully! </div>')
					taskDetail.empty();			   		
					//taskDetail.append('<div class="error">Error in Adding New Project!</div>')
					//alert('lnkShip_'+PROJECT_ID);					
					$('a#lnkShip_'+PROJECT_ID).closest('li').remove();
					window.scrollTo(0, 0);
				} 
				else 
				{
					taskList.empty();					
					taskList.append('<div class="error">Error in Deletion of Project: ['+PROJECT_NAME+']! Please contact Adminsitrator! </div>')
					taskDetail.empty();	
				}
                  
            }
		});
		}
		
	

     });

$(document).on('click', '#addMemberProject', function() {
		//alert($(this).data('pid'));
		/////////////// start personal task start
	//taskList.append('<div class="content"  style="padding:10px;"><p class="message_text" style="color:green;" >Add New Indivisual Task which includes creation new task, assign to and cc tasks to TaskPlanner Users.</p></div>');			//
	taskDetail.empty();
	taskDetail.append('<div class="message-body"><div class="container-fluid" id="xyz2"><form  id="frm_newProjectMember" name="frm_newProjectMember" enctype="multipart/form-data" method="post" onsubmit="return false;">' +			
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><h2>Add Project Member</h2></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="project">Project Name</label><input type="text" class="form-control" id="projectName" name=projectName" value="'+$(this).data('pname')+'" readonly><input type="hidden" name="PROJECT_IDX" id="PROJECT_IDX" value="'+$(this).data('pid')+'" /></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="AUTO_ASSIGNED_ID">Select Member</label><input type="text" class="form-control" id="AUTO_ASSIGNED_ID" name="AUTO_ASSIGNED_ID" value="" ><div class="valid-feedback">Looks good!</div></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="Creator">Added By</label><input type="text" class="form-control" id="Creator" placeholder="Creator" value="Ziad Minhas " readonly></div></div>' +
		   '<div class="row" style="margin-top:25px;"><div class="col-md-12" style="text-align:center;"><button class="btn btn-primary" style="width:200px;" type="submit" id="btn_submitProjectMember" name="btn_submitProjectMember" >Add Project Member</button></div></div>' +		   
            '</div></form></div>'
        );
		
  $( "#AUTO_ASSIGNED_ID" ).autocomplete({	
  source: names
});

   $( "#AUTO_CC" ).autocomplete({	
  source: names
});


  
window.addEventListener("click", function(event) {
  var checkboxes = document.getElementsByName('funnel[]'),
    selectall = document.getElementById('selectall');
  for (var i = 0; i < checkboxes.length; i++) {
    checkboxes[i].addEventListener('change', function() {
      //Conver to array
      var inputList = Array.prototype.slice.call(checkboxes);

      //Set checked  property of selectall input
      selectall.checked = inputList.every(function(c) {
        return c.checked;
      });
    });
  }
  if(selectall){
  selectall.addEventListener('change', function() {
    for (var i = 0; i < checkboxes.length; i++) {
      checkboxes[i].checked = selectall.checked;
    }
  });
  }
});
   

		////////////////// end personal task ends

	
     });
	 
$(document).on('click', '#addProjectTask', function() {
		//alert($(this).data('pid'));
		/////////////// start personal task start
	//taskList.append('<div class="content"  style="padding:10px;"><p class="message_text" style="color:green;" >Add New Indivisual Task which includes creation new task, assign to and cc tasks to TaskPlanner Users.</p></div>');			//
	taskDetail.empty();
	taskDetail.append('<div class="message-body"><div class="container-fluid" id="xyz2"><form  id="frm_newProjectTask" name="frm_newProjectTask" enctype="multipart/form-data" method="post" onsubmit="return false;">' +			
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><h2>Add Project Task</h2></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="project">Project Name</label><input type="text" class="form-control" id="projectName" name=projectName" value="'+$(this).data('pname')+'" readonly><input type="hidden" name="PROJECT_IDX" id="PROJECT_IDX" value="'+$(this).data('pid')+'" /></div></div>' +
           '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="validationCustom01">Task Name</label><input type="text" class="form-control" id="validationCustom01" name=validationCustom01" placeholder="Task name" value="" required><div class="valid-feedback">Looks good!</div></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="AUTO_ASSIGNED_ID">Assigned To</label><input type="text" class="form-control" id="AUTO_ASSIGNED_ID" name="AUTO_ASSIGNED_ID" value="" ><div class="valid-feedback">Looks good!</div></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-6"><label for="validationCustom02">Due Date </label><input type="date" class="form-control" id="validationCustom02" name="validationCustom02" value="" ><div class="valid-feedback">Looks good!</div></div><div class="col-md-6"><label for="txttime">Due Time</label><input type="time" class="form-control" id="txttime" name="txttime" value="" ></div></div>' +		   
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="validationCustom03">Description</label><textarea class="form-control" id="validationCustom03" name="validationCustom03" ></textarea><div class="invalid-feedback">Please provide description.</div></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-6"><label for="TASK_IMAGES">Pictures</label> <br /><input type="file" name="TASK_IMAGES[]" multiple ></div><div class="col-md-6"><label for="Creator">By</label><input type="text" class="form-control" id="Creator" placeholder="Creator" value="Ziad Minhas " readonly></div></div>' +
		   '<div class="row" style="margin-top:25px;"><div class="col-md-12" style="text-align:center;"><button class="btn btn-primary" style="width:200px;" type="submit" id="btn_submitProjectTask" name="btn_submitProjectTask" >Add Project Task</button></div></div>' +		   
            '</div></form></div>'
        );
		
  $( "#AUTO_ASSIGNED_ID" ).autocomplete({	
  source: names
});

   $( "#AUTO_CC" ).autocomplete({	
  source: names
});


  
window.addEventListener("click", function(event) {
  var checkboxes = document.getElementsByName('funnel[]'),
    selectall = document.getElementById('selectall');
  for (var i = 0; i < checkboxes.length; i++) {
    checkboxes[i].addEventListener('change', function() {
      //Conver to array
      var inputList = Array.prototype.slice.call(checkboxes);

      //Set checked  property of selectall input
      selectall.checked = inputList.every(function(c) {
        return c.checked;
      });
    });
  }
  if(selectall){
  selectall.addEventListener('change', function() {
    for (var i = 0; i < checkboxes.length; i++) {
      checkboxes[i].checked = selectall.checked;
    }
  });
  }
});
   

		////////////////// end personal task ends

	
     });
$(document).on('click', '#addProjectSubTask', function() {
		//alert($(this).data('pid')+' '+$(this).data('pname'));
		//alert(this.value);
		/////////////// start personal task start
	//taskList.append('<div class="content"  style="padding:10px;"><p class="message_text" style="color:green;" >Add New Indivisual Task which includes creation new task, assign to and cc tasks to TaskPlanner Users.</p></div>');			//
	taskDetail.empty();
	taskDetail.append('<div class="message-body"><div class="container-fluid" id="xyz2"><form  id="frm_newProjectSubTask" name="frm_newProjectSubTask" enctype="multipart/form-data" method="post" onsubmit="return false;">' +			
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><h2>Add Sub Task</h2></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-6"><label for="project">Project Name</label><input type="text" class="form-control" id="projectName" name=projectName" value="'+$(this).data('pname')+'" readonly><input type="hidden" name="PROJECT_IDX" id="PROJECT_IDX" value="'+$(this).data('pid')+'" /></div><div class="col-md-6"><label for="project">Parent Task ID</label><input type="text" class="form-control" id="pid" name=pid" value="'+this.value+'" readonly><input type="hidden" name="TASK_IDX" id="TASK_IDX" value="'+this.value+'" /></div></div>' +
           '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="validationCustom01">Sub Task Name</label><input type="text" class="form-control" id="validationCustom01" name=validationCustom01" placeholder="Task name" value="" required><div class="valid-feedback">Looks good!</div></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="AUTO_ASSIGNED_ID">Assigned To</label><input type="text" class="form-control" id="AUTO_ASSIGNED_ID" name="AUTO_ASSIGNED_ID" value="" ><div class="valid-feedback">Looks good!</div></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-6"><label for="validationCustom02">Due Date </label><input type="date" class="form-control" id="validationCustom02" name="validationCustom02" value="" ><div class="valid-feedback">Looks good!</div></div><div class="col-md-6"><label for="txttime">Due Time</label><input type="time" class="form-control" id="txttime" name="txttime" value="" ></div></div>' +		   
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="validationCustom03">Description</label><textarea class="form-control" id="validationCustom03" name="validationCustom03" ></textarea><div class="invalid-feedback">Please provide description.</div></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-6"><label for="TASK_IMAGES">Pictures</label> <br /><input type="file" name="TASK_IMAGES[]" multiple ></div><div class="col-md-6"><label for="Creator">By</label><input type="text" class="form-control" id="Creator" placeholder="Creator" value="<?php echo $_SESSION['logged_in']['FULL_NAME']; ?>" readonly></div></div>' +
		   '<div class="row" style="margin-top:25px;"><div class="col-md-12" style="text-align:center;"><button class="btn btn-primary" style="width:200px;" type="submit" id="btn_submitProjectSubTask" name="btn_submitProjectSubTask" >Add Sub Task</button></div></div>' +		   
            '</div></form></div>'
        );
		
  $( "#AUTO_ASSIGNED_ID" ).autocomplete({	
  source: names
});

   $( "#AUTO_CC" ).autocomplete({	
  source: names
});

	
     });

	/* rafiq create new personal task # form  */ //addNewProjects
	$(document).on('click', '#addIndivisualTask', function() {
		/////////////// start personal task start
	taskList.append('<div class="content"  style="padding:10px;"><p class="message_text" style="color:green;" >Add New Indivisual Task which includes creation new task, assign to and cc tasks to TaskPlanner Users.</p></div>');			//
	taskDetail.empty();
	taskDetail.append('<div class="message-body"><div class="container-fluid" id="xyz2"><form  id="frm_newIndivisualTask" name="frm_newIndivisualTask" enctype="multipart/form-data" method="post" onsubmit="return false;">' +			
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><h2>Add New Indivisual Task <button class="btn btn-primary btn-sm" id="btnp2" type="button" onclick="funSetCurDateTime();" >Set Priority</button></h2></div></div>' +
           '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="validationCustom01">Task Name</label><input type="text" class="form-control" id="validationCustom01" name=validationCustom01" placeholder="Task name" value="" required><div class="valid-feedback">Looks good!</div></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-6"><label for="AUTO_ASSIGNED_ID">Assigned To</label><input type="text" class="form-control" id="AUTO_ASSIGNED_ID" name="AUTO_ASSIGNED_ID" value="" ><div class="valid-feedback">Looks good!</div></div><div class="col-md-6"><label for="AUTO_CC">CC Member</label><input type="text" class="form-control" id="AUTO_CC" name="AUTO_CC" value="" ></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-6"><label for="validationCustom02">Due Date </label><input type="date" class="form-control" id="validationCustom02" name="validationCustom02" value="" ><div class="valid-feedback">Looks good!</div></div><div class="col-md-6"><label for="txttime">Due Time</label><input type="time" class="form-control" id="txttime" name="txttime" value="" ></div></div>' +		   
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="validationCustom03">Description</label><textarea class="form-control" id="validationCustom03" name="validationCustom03" ></textarea><div class="invalid-feedback">Please provide description.</div></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="validationCustom03">Description</label><textarea class="form-control" id="validationCustom03" name="validationCustom03" ></textarea><div class="invalid-feedback">Please provide description.</div></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-6"><label for="TASK_IMAGES">Pictures</label> <br /><input type="file" name="TASK_IMAGES[]" multiple ></div><div class="col-md-6"><label for="Creator">Creator</label><input type="text" class="form-control" id="Creator" placeholder="Creator" value="<?php echo $_SESSION['logged_in']['FULL_NAME']; ?>" readonly></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="repeat">Repeat Task</label></div></div>' +
		   '<div class="row" style="margin-top:5px;"><div class="col-md-12"><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input" id="selectall" value="0,1,2,3,4,5,6"><label class="custom-control-label" for="selectall">Every Day</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyMonday" value="0"><label class="custom-control-label" for="everyMonday">Every Monday</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;" style="width:200px; float:left;"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyTuesday" value="1"><label class="custom-control-label" for="everyTuesday">Every Tuesday</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyWednesday" value="2"><label class="custom-control-label" for="everyWednesday">Every Wednesday</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyThursday" value="3"><label class="custom-control-label" for="everyThursday">Every Thursday</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyFriday" value="4"><label class="custom-control-label" for="everyFriday">Every Friday</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input" name="funnel[]" id="everySaturday" value="5"><label class="custom-control-label" for="everySaturday">Every Saturday</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input" name="funnel[]" id="everySunday" value="6"><label class="custom-control-label" for="everySunday">Every Sunday</label></div></div></div>'+ 
		   		   '<div class="row" style="margin-top:10px;"><div class="col-md-12"><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input" name="funnel[]"  id="everyMonth" value="7"><label class="custom-control-label" for="everyMonth">Every Month</label></div></div></div>'+
		   '<div class="row" style="margin-top:25px;"><div class="col-md-12" style="text-align:center;"><button class="btn btn-primary" style="width:200px;" type="submit" id="btn_submitIndivisualTask" name="btn_submitIndivisualTask" >Add Indivisual Task</button></div></div>' +		   
            '</div></form></div>'
        );
		
  $( "#AUTO_ASSIGNED_ID" ).autocomplete({	
  source: names
});

   $( "#AUTO_CC" ).autocomplete({	
  source: names
});


  
window.addEventListener("click", function(event) {
  var checkboxes = document.getElementsByName('funnel[]'),
    selectall = document.getElementById('selectall');
  for (var i = 0; i < checkboxes.length; i++) {
    checkboxes[i].addEventListener('change', function() {
      //Conver to array
      var inputList = Array.prototype.slice.call(checkboxes);

      //Set checked  property of selectall input
      selectall.checked = inputList.every(function(c) {
        return c.checked;
      });
    });
  }
  if(selectall){
  selectall.addEventListener('change', function() {
    for (var i = 0; i < checkboxes.length; i++) {
      checkboxes[i].checked = selectall.checked;
    }
  });
  }
});
   

		////////////////// end personal task ends

	
     });
 $(document).on('click', '#addIndivisualTask44', function() {
		/////////////// start personal task start
//console.log(this);
//alert($(this).attr('uid')+'='+$(this).attr('uname'));

var seleteduser = "";
		 $.ajax({
	url: '<?php echo $url; ?>api2/tasks/fetch-member-details?USER_ID='+$(this).attr('uid'),
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {                
       if(data.STATUS !== 'ERROR') {
            var data = JSON.parse(data.DATA);
			//console.log(data);
			//alert(data.FULL_NAME+'=aa');
			seleteduser = data.FULL_NAME+'('+data.MOBILE_NUMBER+')';
		taskDetail.empty();
	taskDetail.append('<div class="message-body"><div class="container-fluid" id="xyz2"><form  id="frm_newIndivisualTask" name="frm_newIndivisualTask" enctype="multipart/form-data" method="post" onsubmit="return false;">' +			
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><h2>Add New Indivisual Task <button class="btn btn-primary btn-sm" id="btnp2" type="button" onclick="funSetCurDateTime();" >Set Priority</button></h2></div></div>' +
           '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="validationCustom01">Task Name</label><input type="text" class="form-control" id="validationCustom01" name=validationCustom01" placeholder="Task name" value="" required><div class="valid-feedback">Looks good!</div></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-6"><label for="AUTO_ASSIGNED_ID">Assigned To</label><input type="text" class="form-control" id="AUTO_ASSIGNED_ID" name="AUTO_ASSIGNED_ID" value="'+seleteduser+'" ><div class="valid-feedback">Looks good!</div></div><div class="col-md-6"><label for="AUTO_CC">CC Member</label><input type="text" class="form-control" id="AUTO_CC" name="AUTO_CC" value="" ></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-6"><label for="validationCustom02">Due Date </label><input type="date" class="form-control" id="validationCustom02" name="validationCustom02" value="" ><div class="valid-feedback">Looks good!</div></div><div class="col-md-6"><label for="txttime">Due Time</label><input type="time" class="form-control" id="txttime" name="txttime" value="" ></div></div>' +		   
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="validationCustom03">Description</label><textarea class="form-control" id="validationCustom03" name="validationCustom03" ></textarea><div class="invalid-feedback">Please provide description.</div></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-6"><label for="TASK_IMAGES">Pictures</label> <br /><input type="file" name="TASK_IMAGES[]" multiple ></div><div class="col-md-6"><label for="Creator">Creator</label><input type="text" class="form-control" id="Creator" placeholder="Creator" value="<?php echo $_SESSION['logged_in']['FULL_NAME']; ?>" readonly></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="repeat">Repeat Task</label></div></div>' +
		   '<div class="row" style="margin-top:5px;"><div class="col-md-12"><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input" id="selectall" value="0,1,2,3,4,5,6"><label class="custom-control-label" for="selectall">Every Day</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyMonday" value="0"><label class="custom-control-label" for="everyMonday">Every Monday</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;" style="width:200px; float:left;"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyTuesday" value="1"><label class="custom-control-label" for="everyTuesday">Every Tuesday</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyWednesday" value="2"><label class="custom-control-label" for="everyWednesday">Every Wednesday</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyThursday" value="3"><label class="custom-control-label" for="everyThursday">Every Thursday</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyFriday" value="4"><label class="custom-control-label" for="everyFriday">Every Friday</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input" name="funnel[]" id="everySaturday" value="5"><label class="custom-control-label" for="everySaturday">Every Saturday</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input" name="funnel[]" id="everySunday" value="6"><label class="custom-control-label" for="everySunday">Every Sunday</label></div></div></div>'+ 
		   		   '<div class="row" style="margin-top:10px;"><div class="col-md-12"><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input" name="funnel[]"  id="everyMonth" value="7"><label class="custom-control-label" for="everyMonth">Every Month</label></div></div></div>'+
		   '<div class="row" style="margin-top:25px;"><div class="col-md-12" style="text-align:center;"><button class="btn btn-primary" type="submit" style="width:200px;" id="btn_submitIndivisualTask" name="btn_submitIndivisualTask" >Add Indivisual Task</button></div></div>' +		   
            '</div></form></div>'
        );
		
		/*
		
  taskDetail.append('<div class="container-fluid" id="xyz2"><h2>Add New Indivisual Task</h2><form  id="frm_newIndivisualTask" name="frm_newIndivisualTask" enctype="multipart/form-data" method="post" onsubmit="return false;">');

 taskDetail.append(' <div id="abc2"><div class="form-row"><div class="col-md-4 mb-3"><label for="Creator">Creator</label><input type="text" class="form-control" id="Creator" placeholder="Creator" value="< ?php echo $_SESSION['logged_in']['FULL_NAME']; ?>" readonly><div class="valid-feedback">Looks good!</div></div><div class="col-md-4 mb-3"><label for="AUTO_ASSIGNED_ID">Assigned To</label><input type="text" class="form-control" id="AUTO_ASSIGNED_ID" name="AUTO_ASSIGNED_ID" value="'+seleteduser+'" ><div class="valid-feedback">Looks good!</div></div><div class="col-md-4 mb-3"><label for="AUTO_CC">CC Member</label><input type="text" class="form-control" id="AUTO_CC" name="AUTO_CC" value="" ></div></div></div>');
  
  taskDetail.append('<div class="form-row">'+
  '<div class="col-md-5 mb-3"><label for="validationCustom01">Task Name</label><input type="text" class="form-control" id="validationCustom01" name=validationCustom01" placeholder="Task name" value="" required><div class="valid-feedback">Looks good!</div>'+
  	'</div>'+	
	'<div class="col-md-4 mb-3"><label for="validationCustom02">Due Date </label><input type="date" class="form-control" id="validationCustom02" name="validationCustom02" value="< ?php echo date("Y-m-d"); ?>" ><div class="valid-feedback">Looks good!</div>'+
	'</div>'+
	'<div class="col-md-3 mb-3"><label for="txttime">Due Time</label><input type="time" class="form-control" id="txttime" name="txttime" value="< ?php echo '00:00';?>" ></div>'+
	'</div>'+
  '</div>'+  
'<div class="form-row">' +  
  '<div class="col-md-12 mb-3"><label for="validationCustom03">Description</label><textarea type="text" class="form-control" id="validationCustom03" name="validationCustom03" placeholder="Add a TO-DO here" ></textarea><div class="invalid-feedback">Please provide description.</div>'+
	'</div>'+
'</div>'+
  '<div class="form-row"><div class="col-md-12 mb-3">'+
 '<input type="file" name="TASK_IMAGES[]" multiple >'+
 '</div></div><div class="form-row"><div class="col-md-2"> </div>'+
  '<div class="col-md-3"><div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input" id="selectall" value="0,1,2,3,4,5,6">'+
  '<label class="custom-control-label" for="selectall">Every Day</label></div></div>'+
 '<div class="col-md-3"><div class="custom-control custom-checkbox">'+
 '<input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyMonday" value="0">'+
' <label class="custom-control-label" for="everyMonday">Every Monday</label></div></div>'+
  '<div class="col-md-3"><div class="custom-control custom-checkbox">'+
  '<input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyTuesday" value="1">'+
 ' <label class="custom-control-label" for="everyTuesday">Every Tuesday</label>'+
'</div></div><div class="col-md-1"> </div></div><div class="form-row"><div class="col-md-2"> </div><div class="col-md-3"> '+
'<div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyWednesday" value="2">'+
'<label class="custom-control-label" for="everyWednesday">Every Wednesday</label>'+
  '</div></div><div class="col-md-3"> '+
'<div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyThursday" value="3">'+
'<label class="custom-control-label" for="everyThursday">Every Thursday</label>'+
  '</div></div><div class="col-md-3"><div class="custom-control custom-checkbox">'+
    '<input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyFriday" value="4">'+
    '<label class="custom-control-label" for="everyFriday">Every Friday</label></div></div><div class="col-md-1"> </div></div>'+
  '<div class="form-row"><div class="col-md-2 mb-3"> </div><div class="col-md-3 mb-3"><div class="custom-control custom-checkbox">'+
   '<input type="checkbox" class="custom-control-input" name="funnel[]" id="everySaturday" value="5">'+
    '<label class="custom-control-label" for="everySaturday">Every Saturday</label></div></div><div class="col-md-3 mb-3"> '+
'<div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input" name="funnel[]" id="everySunday" value="6">'+
    '<label class="custom-control-label" for="everySunday">Every Sunday</label>'+
  '</div></div><div class="col-md-3 mb-3"><div class="custom-control custom-checkbox">'+
     '<input type="checkbox" class="custom-control-input" name="funnel[]"  id="everyMonth" value="7">'+
   ' <label class="custom-control-label" for="everyMonth">Every Month</label>'+
  '</div></div><div class="col-md-1 mb-3"> </div>'+
  '</div>'+  
  '<div class="form-row"><div class="col-md-2 mb-3"> </div><div class="col-md-8 mb-3"><div class="form-group">'+
  '<button class="btn btn-primary btn-sm" id="btn_submitIndivisualTask" name="btn_submitIndivisualTask" type="submit" >Add Personal Task</button></div></div><div class="col-md-2 mb-3"> </div></div></form></div>');
  */
  
   $( "#AUTO_ASSIGNED_ID" ).autocomplete({	 source: names });

   $( "#AUTO_CC" ).autocomplete({	
  source: names });


  
window.addEventListener("click", function(event) {
  var checkboxes = document.getElementsByName('funnel[]'),
    selectall = document.getElementById('selectall');
  for (var i = 0; i < checkboxes.length; i++) {
    checkboxes[i].addEventListener('change', function() {
      //Conver to array
      var inputList = Array.prototype.slice.call(checkboxes);

      //Set checked  property of selectall input
      selectall.checked = inputList.every(function(c) {
        return c.checked;
      });
    });
  }
  if(selectall){
  selectall.addEventListener('change', function() {
    for (var i = 0; i < checkboxes.length; i++) {
      checkboxes[i].checked = selectall.checked;
    }
  });
  }
});
        }
            }
        }); 
		
		
				

   

		////////////////// end personal task ends

	
     });	 
	 $(document).on('click', '#addIndivisualTask3Due', function() {
		/////////////// start personal task start
//console.log(this);
//alert($(this).attr('uid')+'='+$(this).attr('uname'));

var seleteduser = "";
		 $.ajax({
	url: '<?php echo $url; ?>api2/tasks/fetch-member-details?USER_ID='+$(this).attr('uid'),
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {                
       if(data.STATUS !== 'ERROR') {
            var data = JSON.parse(data.DATA);
			//console.log(data);
			//alert(data.FULL_NAME+'=aa');
			seleteduser = data.FULL_NAME+'('+data.MOBILE_NUMBER+')';
		taskDetail.empty();
	taskDetail.append('<div class="message-body"><div class="container-fluid" id="xyz2"><form  id="frm_newIndivisualTask" name="frm_newIndivisualTask" enctype="multipart/form-data" method="post" onsubmit="return false;">' +			
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><h2>Add New Indivisual Task</h2></div></div>' +
           '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="validationCustom01">Task Name</label><input type="text" class="form-control" id="validationCustom01" name=validationCustom01" placeholder="Task name" value="" required><div class="valid-feedback">Looks good!</div></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-6"><label for="AUTO_ASSIGNED_ID">Assigned To</label><input type="text" class="form-control" id="AUTO_ASSIGNED_ID" name="AUTO_ASSIGNED_ID" value="'+seleteduser+'" ><div class="valid-feedback">Looks good!</div></div><div class="col-md-6"><label for="AUTO_CC">CC Member</label><input type="text" class="form-control" id="AUTO_CC" name="AUTO_CC" value="" ></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-6"><label for="validationCustom02">Due Date </label><input type="date" class="form-control" id="validationCustom02" name="validationCustom02" value="<?php echo date("Y-m-d"); ?>" ><div class="valid-feedback">Looks good!</div></div><div class="col-md-6"><label for="txttime">Due Time</label><input type="time" class="form-control" id="txttime" name="txttime" value="<?php echo '00:00';?>" ></div></div>' +		   
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="validationCustom03">Description</label><textarea class="form-control" id="validationCustom03" name="validationCustom03" ></textarea><div class="invalid-feedback">Please provide description.</div></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-6"><label for="TASK_IMAGES">Pictures</label> <br /><input type="file" name="TASK_IMAGES[]" multiple ></div><div class="col-md-6"><label for="Creator">Creator</label><input type="text" class="form-control" id="Creator" placeholder="Creator" value="<?php echo $_SESSION['logged_in']['FULL_NAME']; ?>" readonly></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="repeat">Repeat Task</label></div></div>' +
		   '<div class="row" style="margin-top:5px;"><div class="col-md-12"><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input" id="selectall" value="0,1,2,3,4,5,6"><label class="custom-control-label" for="selectall">Every Day</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyMonday" value="0"><label class="custom-control-label" for="everyMonday">Every Monday</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;" style="width:200px; float:left;"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyTuesday" value="1"><label class="custom-control-label" for="everyTuesday">Every Tuesday</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyWednesday" value="2"><label class="custom-control-label" for="everyWednesday">Every Wednesday</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyThursday" value="3"><label class="custom-control-label" for="everyThursday">Every Thursday</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyFriday" value="4"><label class="custom-control-label" for="everyFriday">Every Friday</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input" name="funnel[]" id="everySaturday" value="5"><label class="custom-control-label" for="everySaturday">Every Saturday</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input" name="funnel[]" id="everySunday" value="6"><label class="custom-control-label" for="everySunday">Every Sunday</label></div></div></div>'+ 
		   		   '<div class="row" style="margin-top:10px;"><div class="col-md-12"><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input" name="funnel[]"  id="everyMonth" value="7"><label class="custom-control-label" for="everyMonth">Every Month</label></div></div></div>'+
		   '<div class="row" style="margin-top:25px;"><div class="col-md-12" style="text-align:center;"><button class="btn btn-primary" type="submit" style="width:200px;" id="btn_submitIndivisualTask" name="btn_submitIndivisualTask" >Add Indivisual Task</button></div></div>' +		   
            '</div></form></div>'
        );
		
		/*
		
  taskDetail.append('<div class="container-fluid" id="xyz2"><h2>Add New Indivisual Task</h2><form  id="frm_newIndivisualTask" name="frm_newIndivisualTask" enctype="multipart/form-data" method="post" onsubmit="return false;">');

 taskDetail.append(' <div id="abc2"><div class="form-row"><div class="col-md-4 mb-3"><label for="Creator">Creator</label><input type="text" class="form-control" id="Creator" placeholder="Creator" value="< ?php echo $_SESSION['logged_in']['FULL_NAME']; ?>" readonly><div class="valid-feedback">Looks good!</div></div><div class="col-md-4 mb-3"><label for="AUTO_ASSIGNED_ID">Assigned To</label><input type="text" class="form-control" id="AUTO_ASSIGNED_ID" name="AUTO_ASSIGNED_ID" value="'+seleteduser+'" ><div class="valid-feedback">Looks good!</div></div><div class="col-md-4 mb-3"><label for="AUTO_CC">CC Member</label><input type="text" class="form-control" id="AUTO_CC" name="AUTO_CC" value="" ></div></div></div>');
  
  taskDetail.append('<div class="form-row">'+
  '<div class="col-md-5 mb-3"><label for="validationCustom01">Task Name</label><input type="text" class="form-control" id="validationCustom01" name=validationCustom01" placeholder="Task name" value="" required><div class="valid-feedback">Looks good!</div>'+
  	'</div>'+	
	'<div class="col-md-4 mb-3"><label for="validationCustom02">Due Date </label><input type="date" class="form-control" id="validationCustom02" name="validationCustom02" value="< ?php echo date("Y-m-d"); ?>" ><div class="valid-feedback">Looks good!</div>'+
	'</div>'+
	'<div class="col-md-3 mb-3"><label for="txttime">Due Time</label><input type="time" class="form-control" id="txttime" name="txttime" value="< ?php echo '00:00';?>" ></div>'+
	'</div>'+
  '</div>'+  
'<div class="form-row">' +  
  '<div class="col-md-12 mb-3"><label for="validationCustom03">Description</label><textarea type="text" class="form-control" id="validationCustom03" name="validationCustom03" placeholder="Add a TO-DO here" ></textarea><div class="invalid-feedback">Please provide description.</div>'+
	'</div>'+
'</div>'+
  '<div class="form-row"><div class="col-md-12 mb-3">'+
 '<input type="file" name="TASK_IMAGES[]" multiple >'+
 '</div></div><div class="form-row"><div class="col-md-2"> </div>'+
  '<div class="col-md-3"><div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input" id="selectall" value="0,1,2,3,4,5,6">'+
  '<label class="custom-control-label" for="selectall">Every Day</label></div></div>'+
 '<div class="col-md-3"><div class="custom-control custom-checkbox">'+
 '<input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyMonday" value="0">'+
' <label class="custom-control-label" for="everyMonday">Every Monday</label></div></div>'+
  '<div class="col-md-3"><div class="custom-control custom-checkbox">'+
  '<input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyTuesday" value="1">'+
 ' <label class="custom-control-label" for="everyTuesday">Every Tuesday</label>'+
'</div></div><div class="col-md-1"> </div></div><div class="form-row"><div class="col-md-2"> </div><div class="col-md-3"> '+
'<div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyWednesday" value="2">'+
'<label class="custom-control-label" for="everyWednesday">Every Wednesday</label>'+
  '</div></div><div class="col-md-3"> '+
'<div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyThursday" value="3">'+
'<label class="custom-control-label" for="everyThursday">Every Thursday</label>'+
  '</div></div><div class="col-md-3"><div class="custom-control custom-checkbox">'+
    '<input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyFriday" value="4">'+
    '<label class="custom-control-label" for="everyFriday">Every Friday</label></div></div><div class="col-md-1"> </div></div>'+
  '<div class="form-row"><div class="col-md-2 mb-3"> </div><div class="col-md-3 mb-3"><div class="custom-control custom-checkbox">'+
   '<input type="checkbox" class="custom-control-input" name="funnel[]" id="everySaturday" value="5">'+
    '<label class="custom-control-label" for="everySaturday">Every Saturday</label></div></div><div class="col-md-3 mb-3"> '+
'<div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input" name="funnel[]" id="everySunday" value="6">'+
    '<label class="custom-control-label" for="everySunday">Every Sunday</label>'+
  '</div></div><div class="col-md-3 mb-3"><div class="custom-control custom-checkbox">'+
     '<input type="checkbox" class="custom-control-input" name="funnel[]"  id="everyMonth" value="7">'+
   ' <label class="custom-control-label" for="everyMonth">Every Month</label>'+
  '</div></div><div class="col-md-1 mb-3"> </div>'+
  '</div>'+  
  '<div class="form-row"><div class="col-md-2 mb-3"> </div><div class="col-md-8 mb-3"><div class="form-group">'+
  '<button class="btn btn-primary btn-sm" id="btn_submitIndivisualTask" name="btn_submitIndivisualTask" type="submit" >Add Personal Task</button></div></div><div class="col-md-2 mb-3"> </div></div></form></div>');
  */
  
   $( "#AUTO_ASSIGNED_ID" ).autocomplete({	 source: names });

   $( "#AUTO_CC" ).autocomplete({	
  source: names });


  
window.addEventListener("click", function(event) {
  var checkboxes = document.getElementsByName('funnel[]'),
    selectall = document.getElementById('selectall');
  for (var i = 0; i < checkboxes.length; i++) {
    checkboxes[i].addEventListener('change', function() {
      //Conver to array
      var inputList = Array.prototype.slice.call(checkboxes);

      //Set checked  property of selectall input
      selectall.checked = inputList.every(function(c) {
        return c.checked;
      });
    });
  }
  if(selectall){
  selectall.addEventListener('change', function() {
    for (var i = 0; i < checkboxes.length; i++) {
      checkboxes[i].checked = selectall.checked;
    }
  });
  }
});
        }
            }
        }); 
		
		
				

   

		////////////////// end personal task ends

	
     });
 $(document).on('click', '#addIndivisualTask2', function() {
		/////////////// start personal task start
			
		taskList.append('<div class="content"  style="padding:10px;"><p class="message_text" style="color:green;" >Add New Indivisual Task which includes creation new task with Due Today Date and Time, assign to and cc tasks to TaskPlanner Users.</p></div>');
			taskDetail.empty();
	taskDetail.append('<div class="message-body"><div class="container-fluid" id="xyz2"><form  id="frm_newIndivisualTask" name="frm_newIndivisualTask" enctype="multipart/form-data" method="post" onsubmit="return false;">' +			
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><h2>Add New Indivisual Task </h2></div></div>' +
           '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="validationCustom01">Task Name</label><input type="text" class="form-control" id="validationCustom01" name=validationCustom01" placeholder="Task name" value="" required><div class="valid-feedback">Looks good!</div></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-6"><label for="AUTO_ASSIGNED_ID">Assigned To</label><input type="text" class="form-control" id="AUTO_ASSIGNED_ID" name="AUTO_ASSIGNED_ID" value="" ><div class="valid-feedback">Looks good!</div></div><div class="col-md-6"><label for="AUTO_CC">CC Member</label><input type="text" class="form-control" id="AUTO_CC" name="AUTO_CC" value="" ></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-6"><label for="validationCustom02">Due Date </label><input type="date" class="form-control" id="validationCustom02" name="validationCustom02" value="<?php echo date("Y-m-d"); ?>" ><div class="valid-feedback">Looks good!</div></div><div class="col-md-6"><label for="txttime">Due Time</label><input type="time" class="form-control" id="txttime" name="txttime" value="<?php echo '00:00';?>" ></div></div>' +		   
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="validationCustom03">Description</label><textarea class="form-control" id="validationCustom03" name="validationCustom03" ></textarea><div class="invalid-feedback">Please provide description.</div></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-6"><label for="TASK_IMAGES">Pictures</label> <br /><input type="file" name="TASK_IMAGES[]" multiple ></div><div class="col-md-6"><label for="Creator">Creator</label><input type="text" class="form-control" id="Creator" placeholder="Creator" value="<?php echo $_SESSION['logged_in']['FULL_NAME']; ?>" readonly></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="repeat">Repeat Task</label></div></div>' +
		   '<div class="row" style="margin-top:5px;"><div class="col-md-12"><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input" id="selectall" value="0,1,2,3,4,5,6"><label class="custom-control-label" for="selectall">Every Day</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyMonday" value="0"><label class="custom-control-label" for="everyMonday">Every Monday</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;" style="width:200px; float:left;"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyTuesday" value="1"><label class="custom-control-label" for="everyTuesday">Every Tuesday</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyWednesday" value="2"><label class="custom-control-label" for="everyWednesday">Every Wednesday</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyThursday" value="3"><label class="custom-control-label" for="everyThursday">Every Thursday</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyFriday" value="4"><label class="custom-control-label" for="everyFriday">Every Friday</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input" name="funnel[]" id="everySaturday" value="5"><label class="custom-control-label" for="everySaturday">Every Saturday</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input" name="funnel[]" id="everySunday" value="6"><label class="custom-control-label" for="everySunday">Every Sunday</label></div></div></div>'+ 
		   		   '<div class="row" style="margin-top:10px;"><div class="col-md-12"><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input" name="funnel[]"  id="everyMonth" value="7"><label class="custom-control-label" for="everyMonth">Every Month</label></div></div></div>'+
		   '<div class="row" style="margin-top:25px;"><div class="col-md-12" style="text-align:center;"><button class="btn btn-primary" style="width:200px;" type="submit" id="btn_submitIndivisualTask" name="btn_submitIndivisualTask" >Add Indivisual Task</button></div></div>' +		   
            '</div></form></div>'
        );
		
		/*
		
	
		
  taskDetail.append('<div class="container-fluid" id="xyz2"><h2>Add New Indivisual Task</h2><form  id="frm_newIndivisualTask" name="frm_newIndivisualTask" enctype="multipart/form-data" method="post" onsubmit="return false;">');

 taskDetail.append(' <div id="abc2"><div class="form-row"><div class="col-md-4 mb-3"><label for="Creator">Creator</label><input type="text" class="form-control" id="Creator" placeholder="Creator" value="< ?php echo $_SESSION['logged_in']['FULL_NAME']; ?>" readonly><div class="valid-feedback">Looks good!</div></div><div class="col-md-4 mb-3"><label for="AUTO_ASSIGNED_ID">Assigned To</label><input type="text" class="form-control" id="AUTO_ASSIGNED_ID" name="AUTO_ASSIGNED_ID" value="" ><div class="valid-feedback">Looks good!</div></div><div class="col-md-4 mb-3"><label for="AUTO_CC">CC Member</label><input type="text" class="form-control" id="AUTO_CC" name="AUTO_CC" value="" ></div></div></div>');
  
  taskDetail.append('<div class="form-row">'+
  '<div class="col-md-5 mb-3"><label for="validationCustom01">Task Name</label><input type="text" class="form-control" id="validationCustom01" name=validationCustom01" placeholder="Task name" value="" required><div class="valid-feedback">Looks good!</div>'+
  	'</div>'+	
	'<div class="col-md-4 mb-3"><label for="validationCustom02">Due Date </label><input type="date" class="form-control" id="validationCustom02" name="validationCustom02" value="< ?php echo date("Y-m-d"); ?>" ><div class="valid-feedback">Looks good!</div>'+
	'</div>'+
	'<div class="col-md-3 mb-3"><label for="txttime">Due Time</label><input type="time" class="form-control" id="txttime" name="txttime" value="< ?php echo '00:00';?>" ></div>'+
	'</div>'+
  '</div>'+  
'<div class="form-row">' +  
  '<div class="col-md-12 mb-3"><label for="validationCustom03">Description</label><textarea type="text" class="form-control" id="validationCustom03" name="validationCustom03" placeholder="Add a TO-DO here" ></textarea><div class="invalid-feedback">Please provide description.</div>'+
	'</div>'+
'</div>'+
  '<div class="form-row"><div class="col-md-12 mb-3">'+
 '<input type="file" name="TASK_IMAGES[]" multiple >'+
 '</div></div><div class="form-row"><div class="col-md-2"> </div>'+
  '<div class="col-md-3"><div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input" id="selectall" value="0,1,2,3,4,5,6">'+
  '<label class="custom-control-label" for="selectall">Every Day</label></div></div>'+
 '<div class="col-md-3"><div class="custom-control custom-checkbox">'+
 '<input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyMonday" value="0">'+
' <label class="custom-control-label" for="everyMonday">Every Monday</label></div></div>'+
  '<div class="col-md-3"><div class="custom-control custom-checkbox">'+
  '<input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyTuesday" value="1">'+
 ' <label class="custom-control-label" for="everyTuesday">Every Tuesday</label>'+
'</div></div><div class="col-md-1"> </div></div><div class="form-row"><div class="col-md-2"> </div><div class="col-md-3"> '+
'<div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyWednesday" value="2">'+
'<label class="custom-control-label" for="everyWednesday">Every Wednesday</label>'+
  '</div></div><div class="col-md-3"> '+
'<div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyThursday" value="3">'+
'<label class="custom-control-label" for="everyThursday">Every Thursday</label>'+
  '</div></div><div class="col-md-3"><div class="custom-control custom-checkbox">'+
    '<input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyFriday" value="4">'+
    '<label class="custom-control-label" for="everyFriday">Every Friday</label></div></div><div class="col-md-1"> </div></div>'+
  '<div class="form-row"><div class="col-md-2 mb-3"> </div><div class="col-md-3 mb-3"><div class="custom-control custom-checkbox">'+
   '<input type="checkbox" class="custom-control-input" name="funnel[]" id="everySaturday" value="5">'+
    '<label class="custom-control-label" for="everySaturday">Every Saturday</label></div></div><div class="col-md-3 mb-3"> '+
'<div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input" name="funnel[]" id="everySunday" value="6">'+
    '<label class="custom-control-label" for="everySunday">Every Sunday</label>'+
  '</div></div><div class="col-md-3 mb-3"><div class="custom-control custom-checkbox">'+
     '<input type="checkbox" class="custom-control-input" name="funnel[]"  id="everyMonth" value="7">'+
   ' <label class="custom-control-label" for="everyMonth">Every Month</label>'+
  '</div></div><div class="col-md-1 mb-3"> </div>'+
  '</div>'+  
  '<div class="form-row"><div class="col-md-2 mb-3"> </div><div class="col-md-8 mb-3"><div class="form-group">'+
  '<button class="btn btn-primary btn-sm" id="btn_submitIndivisualTask" name="btn_submitIndivisualTask" type="submit" >Add Personal Task</button></div></div><div class="col-md-2 mb-3"> </div></div></form></div>');
  
  */
   $( "#AUTO_ASSIGNED_ID" ).autocomplete({	
  source: names
});

   $( "#AUTO_CC" ).autocomplete({	
  source: names
});


  
window.addEventListener("click", function(event) {
  var checkboxes = document.getElementsByName('funnel[]'),
    selectall = document.getElementById('selectall');
  for (var i = 0; i < checkboxes.length; i++) {
    checkboxes[i].addEventListener('change', function() {
      //Conver to array
      var inputList = Array.prototype.slice.call(checkboxes);

      //Set checked  property of selectall input
      selectall.checked = inputList.every(function(c) {
        return c.checked;
      });
    });
  }
  if(selectall){
  selectall.addEventListener('change', function() {
    for (var i = 0; i < checkboxes.length; i++) {
      checkboxes[i].checked = selectall.checked;
    }
  });
  }
});
   

		////////////////// end personal task ends

	
     });
//	//TASK_TITLE, CREATOR_ID, PROJECT_ID, PARENT_TASK_ID, ASSIGNED_ID, CREATED_DATE, DUE_DATE, CC
$(document).on('click', '#btn_submitIndivisualTask', function() {
	var REPEAT_INTERVAL = $.map($('input[name="funnel[]"]:checked'), function(c){return c.value; });
	var  TASK_TITLE = $('#validationCustom01').val();
	var  TASK_DESCRIPTION = $('#validationCustom03').val();
	//2019-01-11 14:43:00
	var dat = $('#validationCustom02').val(); //document.frm_newIndivisualTask.validationCustom02.value;
	var tim = $('#txttime').val();//document.frm_newIndivisualTask.txttime.value;
	var  DUE_DATE_DT = "";
	var DUE_DATE = "";
	if(dat != "")
	{
		DUE_DATE_DT = dat+' '+tim+':00';
		if(tim == "") {  DUE_DATE_DT = dat+' 00:00:00'; }
		DUE_DATE = toTimestamp(DUE_DATE_DT);
	}
	//alert('DUE_DATE:'+DUE_DATE+', DUE_DATE_DT:'+DUE_DATE_DT);
	var CREATOR_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
	if(TASK_TITLE == "") { alert('Task Title Missing!'); return false;}
	
	var  AUTO_ASSIGNED_ID = $('#AUTO_ASSIGNED_ID').val();
	var matches = [];
	if(AUTO_ASSIGNED_ID)
	{
		AUTO_ASSIGNED_ID.replace(/\((.*?)\)/g, function(_, match){
  		matches.push(match);
		});
	}
	var  AUTO_CC= $('#AUTO_CC').val();
	var matches2 = [];
	if(AUTO_CC)
	{
		AUTO_CC.replace(/\((.*?)\)/g, function(_, match){
  		matches2.push(match);
		});
	}
	if(matches == "")
	{
		alert('Assigned To Field Missing!'); return false;
	}
	var form = $("#frm_newIndivisualTask");
	console.log(form);
	var formData = new FormData(form[0]);
	formData.append('TASK_TITLE',TASK_TITLE);
	formData.append('TASK_DESCRIPTION',TASK_DESCRIPTION);
	formData.append('DUE_DATE',DUE_DATE);
	formData.append('DUE_DATE_DT',DUE_DATE_DT);
	formData.append('REPEAT_INTERVAL',REPEAT_INTERVAL);
	formData.append('CREATOR_ID',CREATOR_ID);
	formData.append('ASSIGNED_ID',matches);
	formData.append('CC',matches2);
	formData.append('PROJECT_ID','');
	formData.append('PARENT_TASK_ID','');
	$.ajax({
       url: '<?php echo $url; ?>api2/tasks/create-task',
	    type: 'POST',
		data: formData,
		dataType: 'json',
		contentType:false,
		cache: false,
		processData:false,
		error: function(err) {
            alert(err.statusText);
        },
        success: function(data) {
				taskList.empty();
				taskDetail.empty();			
				taskDetail.append('<div class="success">Task Added Successfully!<p></p></div>');	
		}
		});
  });

$(document).on('click', '#btn_submitProjectTask', function() {
	var REPEAT_INTERVAL = ""; //$.map($('input[name="funnel[]"]:checked'), function(c){return c.value; });
	var  TASK_TITLE = $('#validationCustom01').val();
	var  TASK_DESCRIPTION = $('#validationCustom03').val();
	var  PROJECT_IDX = $('#PROJECT_IDX').val();
	var  projectName = $('#projectName').val();
	
	//projectName
	//alert(PROJECT_ID);
	//2019-01-11 14:43:00
	var dat = $('#validationCustom02').val(); //document.frm_newIndivisualTask.validationCustom02.value;
	if(dat == "")
	{
		alert('Due Date Missing!');
		return false;
	}
	
	var tim = $('#txttime').val();//document.frm_newIndivisualTask.txttime.value;
	var  DUE_DATE_DT = "";
	var DUE_DATE = "";
	if(dat != "")
	{
		DUE_DATE_DT = dat+' '+tim+':00';
		if(tim == "") {  DUE_DATE_DT = dat+' 00:00:00'; }
		DUE_DATE = toTimestamp(DUE_DATE_DT);
	}
	//alert('DUE_DATE:'+DUE_DATE+', DUE_DATE_DT:'+DUE_DATE_DT);
	var CREATOR_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
	if(TASK_TITLE == "") { alert('Task Title Missing!'); return false;}
	
	var  AUTO_ASSIGNED_ID = $('#AUTO_ASSIGNED_ID').val();
	var matches = [];
	if(AUTO_ASSIGNED_ID)
	{
		AUTO_ASSIGNED_ID.replace(/\((.*?)\)/g, function(_, match){
  		matches.push(match);
		});
	}
	if(matches == "")
	{
		alert('Assigned To Field Missing!'); return false;
	}
	var form = $("#frm_newProjectTask");
	console.log(form);
	var formData = new FormData(form[0]);
	formData.append('TASK_TITLE',TASK_TITLE);
	formData.append('TASK_DESCRIPTION',TASK_DESCRIPTION);
	formData.append('DUE_DATE',DUE_DATE);
	formData.append('DUE_DATE_DT',DUE_DATE_DT);
	formData.append('CREATOR_ID',CREATOR_ID);
	formData.append('ASSIGNED_ID',matches);
	formData.append('PROJECT_ID',PROJECT_IDX);
	formData.append('REPEAT_INTERVAL',REPEAT_INTERVAL);
	//formData.append('PARENT_TASK_ID','(NULL)');
	formData.append('CC','');
	$.ajax({
       url: '<?php echo $url; ?>api2/tasks/create-ptask',
	    type: 'POST',
		data: formData,
		dataType: 'json',
		contentType:false,
		cache: false,
		processData:false,
		error: function(err) {
            alert(err.statusText);
        },
        success: function(data) {
				taskList.empty();
				taskDetail.empty();			
				funshowProjectTasks(PROJECT_IDX,projectName,CREATOR_ID);
				taskDetail.append('<div class="success">Project Task Added Successfully!<p></p></div>');
		}
		});
  });
  
  $(document).on('click', '#btn_submitProjectMember', function() {
	var  PROJECT_IDX = $('#PROJECT_IDX').val();
	var  projectName = $('#projectName').val();
	var CREATOR_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;

	var  AUTO_ASSIGNED_ID = $('#AUTO_ASSIGNED_ID').val();
	var matches = [];
	if(AUTO_ASSIGNED_ID)
	{
		AUTO_ASSIGNED_ID.replace(/\((.*?)\)/g, function(_, match){
  		matches.push(match);
		});
	}
	if(matches == "")
	{
		alert('Member Field Missing!'); return false;
	}
	var form = $("#frm_newProjectMember");
	console.log(form);
	var formData = new FormData(form[0]);
	formData.append('USER_ID',CREATOR_ID);
	formData.append('USER_EMAIL',matches);
	formData.append('PROJECT_ID',PROJECT_IDX);
	formData.append('PROJECT_ROLE','MEMBER');
	//PROJECT_ROLE
	$.ajax({
       url: '<?php echo $url; ?>api2/projects/members/add',
	    type: 'POST',
		data: formData,
		dataType: 'json',
		contentType:false,
		cache: false,
		processData:false,
		error: function(err) {
            alert(err.statusText);
        },
        success: function(data) {
				taskList.empty();
				taskDetail.empty();			
				funshowProjectTasks(PROJECT_IDX,projectName,CREATOR_ID);
				taskDetail.empty();
				if(data.DATA.includes('already'))
				{
					taskDetail.append('<div class="error">Member Already Exist in Project!<p></p></div>');
				}
				else
				{
					taskDetail.append('<div class="success">Member addded successfully in the Project!<p></p></div>');
				}
		}
		});
  });
  
  $(document).on('click', '#btn_submitProjectSubTask', function() {
	var REPEAT_INTERVAL = ""; //$.map($('input[name="funnel[]"]:checked'), function(c){return c.value; });
	var  TASK_TITLE = $('#validationCustom01').val();
	var  TASK_DESCRIPTION = $('#validationCustom03').val();
	var  PROJECT_IDX = $('#PROJECT_IDX').val();
	var  projectName = $('#projectName').val();
	
	//projectName
	//alert(PROJECT_ID);
	//2019-01-11 14:43:00
	var dat = $('#validationCustom02').val(); //document.frm_newIndivisualTask.validationCustom02.value;
	var tim = $('#txttime').val();//document.frm_newIndivisualTask.txttime.value;
	var  DUE_DATE_DT = "";
	var DUE_DATE = "";
	if(dat != "")
	{
		DUE_DATE_DT = dat+' '+tim+':00';
		if(tim == "") {  DUE_DATE_DT = dat+' 00:00:00'; }
		DUE_DATE = toTimestamp(DUE_DATE_DT);
	}
	//alert('DUE_DATE:'+DUE_DATE+', DUE_DATE_DT:'+DUE_DATE_DT);
	var CREATOR_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
	if(TASK_TITLE == "") { alert('Task Title Missing!'); return false;}
	
	var  AUTO_ASSIGNED_ID = $('#AUTO_ASSIGNED_ID').val();
	var matches = [];
	if(AUTO_ASSIGNED_ID)
	{
		AUTO_ASSIGNED_ID.replace(/\((.*?)\)/g, function(_, match){
  		matches.push(match);
		});
	}
	if(matches == "")
	{
		alert('Assigned To Field Missing!'); return false;
	}
	var form = $("#frm_newProjectSubTask");
	console.log(form);
	var formData = new FormData(form[0]);
	formData.append('TASK_TITLE',TASK_TITLE);
	formData.append('TASK_DESCRIPTION',TASK_DESCRIPTION);
	formData.append('DUE_DATE',DUE_DATE);
	formData.append('DUE_DATE_DT',DUE_DATE_DT);
	formData.append('CREATOR_ID',CREATOR_ID);
	formData.append('ASSIGNED_ID',matches);
	formData.append('PROJECT_ID',PROJECT_IDX);
	formData.append('REPEAT_INTERVAL',REPEAT_INTERVAL);
	formData.append('PARENT_TASK_ID',document.frm_newProjectSubTask.TASK_IDX.value);
	formData.append('CC','');
	$.ajax({
       url: '<?php echo $url; ?>api2/tasks/create-ptask',
	    type: 'POST',
		data: formData,
		dataType: 'json',
		contentType:false,
		cache: false,
		processData:false,
		error: function(err) {
            alert(err.statusText);
        },
        success: function(data) {
				taskList.empty();
				taskDetail.empty();			
				funshowProjectTasks(PROJECT_IDX,projectName,CREATOR_ID);
				taskDetail.append('<div class="success">Project Sub Task Added Successfully!<p></p></div>');
		}
		});
  });

	 
	$(document).on('click', '#addNewShipment', function() {
		addNewAdvance()
     });
	 
	 $(document).on('click', '#addNewProjects', function() {
		addNewProject()
     });
	 $(document).on('click', '#addCCTask', function() {
		addNewIndividualTask()
     });
	
/*
var form = $("#frm_newPersonalTask");
console.log(form);
var formData = new FormData(form[0]);
formData.append('TASK_ID',TASK_ID);
formData.append('TASK_TITLE',TASK_TITLE);
formData.append('TASK_DESCRIPTION',TASK_DESCRIPTION);
formData.append('DUE_DATE',DUE_DATE);
formData.append('DUE_DATE_DT',DUE_DATE_DT);
formData.append('REPEAT_INTERVAL',REPEAT_INTERVAL);
formData.append('CREATOR_ID',CREATOR_ID);
formData.append('ASSIGNED_ID',ASSIGNED_ID);
 
$.ajax({
       url: "<?php echo $url; ?>api2/tasks/create/personal",
        type: 'POST',
		data: formData,
		dataType: 'json',
		contentType:false,
		cache: false,
		processData:false,
*/

 $(document).on('click', '#btn_updateTask', function() {   
var TASK_ID  = this.value; 
var REPEAT_INTERVAL = $.map($('input[name="funnel[]"]:checked'), function(c){return c.value; });
var  TASK_TITLE = document.frm_editPersonalTask.validationCustom01.value;
//alert(TASK_TITLE);
var  TASK_DESCRIPTION = document.frm_editPersonalTask.validationCustom03.value;

//2019-01-11 14:43:00
var dat = document.frm_editPersonalTask.validationCustom02.value;
var tim = document.frm_editPersonalTask.txttime.value;
var  DUE_DATE_DT = dat+' '+tim+':00';
var DUE_DATE = toTimestamp(DUE_DATE_DT);
//alert('DUE_DATE:'+DUE_DATE+', DUE_DATE_DT:'+DUE_DATE_DT);

var CREATOR_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
var ASSIGNED_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;

var form = $("#frm_editPersonalTask");
var formData = new FormData(form[0]);
formData.append('TASK_ID',TASK_ID);
formData.append('TASK_TITLE',TASK_TITLE);
formData.append('TASK_DESCRIPTION',TASK_DESCRIPTION);
formData.append('DUE_DATE',DUE_DATE);
formData.append('DUE_DATE_DT',DUE_DATE_DT);
formData.append('REPEAT_INTERVAL',REPEAT_INTERVAL);
formData.append('CREATOR_ID',CREATOR_ID);
formData.append('ASSIGNED_ID',ASSIGNED_ID);

$.ajax({
       url: '<?php echo $url; ?>api2/tasks/update',
	    type: 'POST',
		data: formData,
		dataType: 'json',
		contentType:false,
		cache: false,
		processData:false,
		error: function(err) {
            alert(err.statusText);
        },
        success: function(data) {
					
		//alert(data.MESSAGE); //updated successfully // rafiq
		showTaskDetailBack(TASK_ID);
			
		}
		});
		
  });
 //btn_updateOtherTask 
   $(document).on('click', '#btn_updateOtherTask', function() {  
   
var TASK_ID  = this.value; 
var REPEAT_INTERVAL = $.map($('input[name="funnel[]"]:checked'), function(c){return c.value; });
var  TASK_TITLE = document.frm_editPersonalOTask.validationCustom01.value;
//alert(TASK_TITLE);
var  TASK_DESCRIPTION = document.frm_editPersonalOTask.validationCustom03.value;

//2019-01-11 14:43:00
var dat = document.frm_editPersonalOTask.validationCustom02.value;
var tim = document.frm_editPersonalOTask.txttime.value;
var  DUE_DATE_DT = dat+' '+tim+':00';
var DUE_DATE = toTimestamp(DUE_DATE_DT);
//alert('DUE_DATE:'+DUE_DATE+', DUE_DATE_DT:'+DUE_DATE_DT);

var CREATOR_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
var  ASSIGNED_ID = document.frm_editPersonalOTask.ASSIGNED_ID.value;
var  CC = document.frm_editPersonalOTask.CC.value;

var form = $("#frm_editPersonalOTask");
var formData = new FormData(form[0]);
formData.append('TASK_ID',TASK_ID);
formData.append('TASK_TITLE',TASK_TITLE);
formData.append('TASK_DESCRIPTION',TASK_DESCRIPTION);
formData.append('DUE_DATE',DUE_DATE);
formData.append('DUE_DATE_DT',DUE_DATE_DT);
formData.append('REPEAT_INTERVAL',REPEAT_INTERVAL);
formData.append('CREATOR_ID',CREATOR_ID);
formData.append('ASSIGNED_ID',ASSIGNED_ID);
formData.append('CC',CC);

$.ajax({
       url: '<?php echo $url; ?>api2/tasks/update',
	    type: 'POST',
		data: formData,
		dataType: 'json',
		contentType:false,
		cache: false,
		processData:false,
		error: function(err) {
            alert(err.statusText);
        },
        success: function(data) {
					
		//alert(data.MESSAGE); //updated successfully // rafiq
		showTaskDetailBack(TASK_ID);
			
		}
		});
		
  });
  
 $(document).on('click', '#btn_submitPersonalTask', function() {  
var REPEAT_INTERVAL = $.map($('input[name="funnel[]"]:checked'), function(c){return c.value; });
var  TASK_TITLE = document.forms.item(1).validationCustom01.value;
var  TASK_DESCRIPTION = document.forms.item(1).validationCustom03.value;
//2019-01-11 14:43:00
var dat = document.forms.item(1).validationCustom02.value;
var tim = document.forms.item(1).txttime.value;

var  DUE_DATE_DT = dat+' '+tim+':00';
if(tim == "")
  DUE_DATE_DT = dat+' 00:00:00';


var DUE_DATE = toTimestamp(DUE_DATE_DT);

//alert(REPEAT_INTERVAL);
var CREATOR_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
var ASSIGNED_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;

if(TASK_TITLE == "") { alert('Task Title Missing!'); return false;}




var form = $("#frm_newPersonalTask");
console.log(form);
var formData = new FormData(form[0]);
formData.append('TASK_TITLE',TASK_TITLE);
formData.append('TASK_DESCRIPTION',TASK_DESCRIPTION);
formData.append('DUE_DATE',DUE_DATE);
formData.append('DUE_DATE_DT',DUE_DATE_DT);
formData.append('REPEAT_INTERVAL',REPEAT_INTERVAL);
formData.append('CREATOR_ID',CREATOR_ID);
formData.append('ASSIGNED_ID',ASSIGNED_ID);
 console.log('checking dates : due date->'+DUE_DATE+' == '+DUE_DATE_DT);
	
$.ajax({
       url: "<?php echo $url; ?>api2/tasks/create/personal",
        type: 'POST',
		data: formData,
		dataType: 'json',
		contentType:false,
		cache: false,
		processData:false,
		error: function(err) {
           alert(err.statusText);
        },
        success: function(data) {
					
				//alert(data.MESSAGE);
				
				//var finalMSG = data.MESSAGE;
				taskDetail.empty();			
				taskDetail.append('<p class="message_text" style="color:green;"> Personal Task Successfully Addded!.</p>');	
				
				

				
//var url = '< ?php echo $url; ?>api2/tasks/fetch-personal-tasks?USER_ID=< ?php echo $_SESSION['logged_in']['USER_ID']; ?>';
//showTasks(url, 1, 'tasks');
//activeTab($(this), 0);
		
		
		 
		
		 
		taskList.empty();
		 taskDetail.empty();
        var url = '<?php echo $url; ?>api2/tasks/fetch-due-tasks?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>&TYPE=MYPERSONALCTR';
        $.ajax({
            url:  url,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
				
				
               var data = JSON.parse(data.DATA);
			   
			    document.getElementById("submenu1myp").innerHTML = '';
			   var finaloutput = '';
			   
			   if(data[0].todo > 0 || data[0].repeated > 0 || data[0].nonrepeated > 0 )
			   {
				   finaloutput = '<ul class="menu-items nav flex-column flex-nowrap">';
				}		   
			   
//console.log(data);
		 if(data[0].todo > 0){
			 
			 finaloutput += '<li class="nav-item"><a class="nav-link" href="#" id="todoPersonalTask" onclick="TaskPersonalFunction();">Todo Tasks</a></li>';
		 }
		 if(data[0].nonrepeated > 0){
			 finaloutput += '<li class="nav-item"><a class="nav-link" href="#" id="noRepPersonalTask" onclick="TaskPersonalNoRepFunction();">NonRepeated Tasks</a></li>';
		 }
		 if(data[0].repeated > 0){
			 finaloutput += '<li class="nav-item"><a class="nav-link" href="#" id="RepPersonalTask" onclick="TaskPersonalRepFunction();">Repeated Tasks</a></li>';
		 }	 
		  if(data[0].todo > 0 || data[0].repeated > 0 || data[0].nonrepeated > 0 )
			   {
				   finaloutput += '</ul>';
				}
		 document.getElementById("submenu1myp").innerHTML = finaloutput;
			
           
		    taskList.append('<div class="mail-list taskDetails" ><div class="content"><p class="message_text" style="color:green;" > <p class="message_text" style="color:green;"> Personal Task Successfully Addded!.</p></p></div></div>');
		   
		    }
        });
    
	

        activeTab($(this), $('#myPersonalTasks'));
		
    	
			
		}
		});
  });
	 
 $(document).on('click', '#btn_submitAdvShipTask', function(e) {  
// $("#frm_newAdvance").on('submit', function(e){
   e.preventDefault();
  
//  alert('a');
// $("#frm_newAdvance").submit(); // Submit the form
	 
var  SHIPMENT_TITLE = document.forms.item(1).SHIPMENT_TITLE.value;
var  SHIPMENT_DESCRIPTION = document.forms.item(1).SHIPMENT_DESCRIPTION.value;
var  SHIPMENT_CATEGORY = document.forms.item(1).SHIPMENT_CATEGORY.value;
var  CUSTOMER_NAME = document.forms.item(1).CUSTOMER_NAME.value;
var  INVOICE_NUMBER = document.forms.item(1).INVOICE_NUMBER.value;
var CREATOR_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
var CREATED_DATE = Math.round(new Date().getTime()/1000);
var USER_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
if(CUSTOMER_NAME == "") { alert('Customer Name Missing!'); return false;}
if(INVOICE_NUMBER == "") { alert('Invoice Number Missing!'); return false;}

/*
        data:"SHIPMENT_TITLE="+SHIPMENT_TITLE+"&SHIPMENT_DESCRIPTION="+SHIPMENT_DESCRIPTION+"&SHIPMENT_CATEGORY="+SHIPMENT_CATEGORY+"&CUSTOMER_NAME="+CUSTOMER_NAME+"&INVOICE_NUMBER="+INVOICE_NUMBER+"&CREATOR_ID="+CREATOR_ID+"&CREATED_DATE="+CREATED_DATE+"&USER_ID="+USER_ID,

*/

var form = $("#frm_newAdvance");
var formData = new FormData(form[0]);
 formData.append('CREATOR_ID',CREATOR_ID);
 formData.append('CREATED_DATE',CREATED_DATE);
 formData.append('USER_ID',USER_ID);
 
$.ajax({
       url: "<?php echo $url; ?>api2/shipments/create?SHIPMENT_TITLE="+SHIPMENT_TITLE+"&SHIPMENT_DESCRIPTION="+SHIPMENT_DESCRIPTION+"&SHIPMENT_CATEGORY="+SHIPMENT_CATEGORY+"&CUSTOMER_NAME="+CUSTOMER_NAME+"&INVOICE_NUMBER="+INVOICE_NUMBER+"&CREATOR_ID="+CREATOR_ID+"&CREATED_DATE="+CREATED_DATE+"&USER_ID="+USER_ID,
        	type: 'POST',
			data: formData,
            dataType: 'json',
            contentType: false,
            cache: false,
            processData:false,
			error: function(err) {
            alert(err.statusText);
        },
        success: function(data) {
			
		 taskList.empty();
		 taskDetail.empty();
		 
	
		 $.ajax({
	 url : '<?php echo $url; ?>api2/shipments/fetch-shipment-users?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>',
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {                
         submenuShipmentUsers.empty();
       if(data.STATUS !== 'ERROR') {
            var data = JSON.parse(data.DATA);
			
			if(data.length > 0) {
 submenuShipmentUsers.append('<ul class="flex-column nav">');
           // console.log(data);	
				
			for (var i = 0; i < data.length; i++) {
			submenuShipmentUsers.append('<li class="nav-item"><a class="nav-link collapsed menu-heading" id="lnkShip_'+data[i].CREATOR_ID+'" onclick="funshowUsersShipments('+data[i].CREATOR_ID+')" href="#" data-toggle="collapse" data-target="#'+data[i].CREATOR_ID+'" ><span style="float:left; font-size:0.75rem;">'+data[i].FULL_NAME+'</span><span class="badge-pill">'+data[i].total+'</span></a>');
            }
			submenuShipmentUsers.append('</ul>');
			
					
			
			
			}
			if(data.length == 0){
				submenuShipmentUsers.empty();
			// submenuOthers.append('<ul class="flex-column nav"><li class="nav-item"><a class="nav-link" href="#" id="Others_0">NOT FOUND</a></li></ul>');
			}
			
        } else {
            submenuShipmentUsers.append('<ul class="flex-column nav"><li class="nav-item"><a class="nav-link" href="#" id="Others_0">'+data.MESSAGE+'</a></li></ul>');
        }
                  
            }
        }); 
	 
		/////////////////////////////////////////////		 
         $.ajax({
            url: '<?php echo $url; ?>api2/shipments/fetch-shipments?USER_ID=<?php echo $_SESSION['logged_in']['USER_ID']; ?>',
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
				taskList.empty();
				taskDetail.empty();
				if(data.STATUS !== 'ERROR') {
					var data = JSON.parse(data.DATA);
					document.getElementById("shipmentTaskCount").innerHTML = data.length;	
					// show pending and approved shiment_status 
					showTaskListShipmentsWhite(data);					
					// show groups i.e. user groups list
					//showUsersGroupsShimpments();
					
					
			$('div#taskDetails div.message-body').attr("style","visibility:hidden; display:none");
		$('div#taskDetails').append("New Shipment/RMA Added Successfully!");	
		

        } else {
            taskList.append('<div class="error">' + data.MESSAGE + '</div>')
        }
    
            }
        });
    
			
			}
		});
  });
  
 $(document).on('click', '#btn_delTask', function() {   
var  TASK_ID = this.value;
var USER_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
if(confirm("Are you sure to delete TaskID : "+TASK_ID))
{
	$.ajax({
       url: '<?php echo $url; ?>api2/tasks/delete',
        type: 'POST',
		contentType:'application/x-www-form-urlencoded',
        data:"TASK_ID="+TASK_ID+"&USER_ID="+USER_ID,
		error: function(err) {
            alert(err.statusText);
        },
        success: function(data) {
					
				//alert(data.MESSAGE);
				//<div class="mail-list taskDetails" data-task_id="2164">
				//$('.videodata[data-id*='+ data + ']').text(title) ;
				if($("div.taskDetailsAMe[data-task_id*="+ TASK_ID + "]"))
				{//taskDetailsAMe
					$("div.taskDetailsAMe[data-task_id*="+ TASK_ID + "]").remove();
				}
				if($("div.taskDetails[data-task_id*="+ TASK_ID + "]"))
				{//taskDetails
					$("div.taskDetails[data-task_id*="+ TASK_ID + "]").remove();
				}
				if($("div.taskDetailsCCtaskDD[data-task_id*="+ TASK_ID + "]"))
				{//taskDetailsCCtaskDD
					$("div.taskDetailsCCtaskDD[data-task_id*="+ TASK_ID + "]").remove();
				}
				//taskDetailsProj
				if($("div.taskDetailsProj[data-task_id*="+ TASK_ID + "]"))
				{//taskDetailsCCtaskDD
					$("div.taskDetailsProj[data-task_id*="+ TASK_ID + "]").remove();
				}
				if($("div.taskDetailsPersonal[data-task_id*="+ TASK_ID + "]"))
				{//taskDetailsCCtaskDD
					$("div.taskDetailsPersonal[data-task_id*="+ TASK_ID + "]").remove();
				}
				if($("div.taskDetailsAssignOthers[data-task_id*="+ TASK_ID + "]"))
				{//taskDetailsCCtaskDD
					$("div.taskDetailsAssignOthers[data-task_id*="+ TASK_ID + "]").remove();
				}
				


//var url = '< ?php echo $url; ?>api2/tasks/fetch-personal-tasks?USER_ID=< ?php echo $_SESSION['logged_in']['USER_ID']; ?>';
//showTasks(url, 1, 'tasks');
//activeTab($(this), 0);
		
		$('div#taskDetails div.message-body').attr("style","visibility:hidden; display:none");
		$('div#taskDetails').append("Selected Task Deleted Successfully!");	
		}
		});
}
else
	return false;
  });
//funShowSubTaskDetail(45,2748);
$(document).on('click', '#btn_editTaskProj', function() { 
var  TASK_ID = this.value;
var USER_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
$.ajax({
            url: '<?php echo $url; ?>api2/tasks/fetch-details?OBJECT_ID=' + TASK_ID + '&OBJECT_TYPE=task',
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
                if(data.STATUS === 'SUCCESS') {
                    showEditTaskDetailAll(data.DATA)
                }
            }
        });

  });
 
 $(document).on('click', '#btn_editSubTaskProj', function() { 
var  TASK_ID = this.value;
var USER_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
$.ajax({
            url: '<?php echo $url; ?>api2/tasks/fetch-details?OBJECT_ID=' + TASK_ID + '&OBJECT_TYPE=task',
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
                if(data.STATUS === 'SUCCESS') {
                    showEditSubTaskDetailAll(data.DATA)
                }
            }
        });

  });
  
 
 $(document).on('click', '#btn_editTask', function() { 
var  TASK_ID = this.value;
//alert('Edit Functionality is Pending.... Please wait'+TASK_ID);
//return false;
var USER_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
$.ajax({
            url: '<?php echo $url; ?>api2/tasks/fetch-details?OBJECT_ID=' + TASK_ID + '&OBJECT_TYPE=task',
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
                if(data.STATUS === 'SUCCESS') {
                    showEditTaskDetail(data.DATA)
                }
            }
        });

  });

$(document).on('click', '#btn_editTaskOther', function() { 
var  TASK_ID = this.value;
//alert('Edit Functionality is Pending.... Please wait'+TASK_ID);
//return false;
var USER_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
$.ajax({
            url: '<?php echo $url; ?>api2/tasks/fetch-details?OBJECT_ID=' + TASK_ID + '&OBJECT_TYPE=task',
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
                if(data.STATUS === 'SUCCESS') {
                    showEditTaskDetailOther(data.DATA)
                }
            }
        });

  });
/*
function verify(frmid)
{
	var form = $('#'+frmid);
	alert($(form));
	 $.ajax({
       url: '< ?php echo $url; ?>api2/tasks/create/personal',
        type: 'POST',
		dataType: 'json',
			processData: false,
			contentType: 'application/x-www-form-urlencoded',
            cache: false,
      data: $(form).serialize(),
        success: function () {
           //  if(data.STATUS === 'SUCCESS') {
                   // showShipmentDetail(data.DATA)
				   alert('Ajax success return rafiq');
               // }
        },
        error: function () {
            console.log('it failed!');
        }
    });
	return false;
}
*/
/*
$('form.frm_newPersonalTask').submit(function (e) {
    // prevent the page from submitting like normal
    e.preventDefault(); 

    $.ajax({
       url: '< ?php echo $url; ?>api2/tasks/create/personal',
        type: 'POST',
        data: $(this).serialize(),
        success: function () {
             if(data.STATUS === 'SUCCESS') {
                   // showShipmentDetail(data.DATA)
				   alert('Ajax success return rafiq');
                }
        },
        error: function () {
            console.log('it failed!');
        }
    });
	 return false;
});
*/
/*
 $(document).on('click', '#btn_submitPersonalTask', function() {       
		var myform = document.getElementById("frm_newPersonalTask");
   		 var fd = new FormData(myform );
        $.ajax({
            url: '< ?php echo $url; ?>api2/tasks/create/personal',
            type: 'POST',
            dataType: 'json',
			data: fd,
			processData: false,
			contentType: false,
            cache: false,
            success: function (data) {
                if(data.STATUS === 'SUCCESS') {
                   // showShipmentDetail(data.DATA)
				   alert('Ajax success return rafiq');
                }
            }
        });
    });
	
	
*/
function toTimestamp(strDate){
   var datum = Date.parse(strDate);
   return datum/1000;
}
    $(document).on('click', '.shipmentDetails', function() {
        var id = $(this).data('task_id');

        $.ajax({
            url: '<?php echo $url; ?>api2/shipments/fetch-details?OBJECT_ID=' + id + '&OBJECT_TYPE=task',
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
                if(data.STATUS === 'SUCCESS') {
                   		taskDetail.empty();
						var data = JSON.parse(data.DATA);	
					
					console.log(data);	
					
						var imgsOutput = "";
						if(data.images){
		var imgesArr = data.images;
		for(var aa=0;aa<imgesArr.length;aa++)
		{
			//alert('< ?php echo $url; ?>');
			imgsOutput += '<a data-fancybox="gallery" href="<?php echo $url; ?>'+imgesArr[aa]['SHIPMENT_IMAGE']+'" data-title="Photo" ><img src="<?php echo $url; ?>'+imgesArr[aa]['SHIPMENT_IMAGE']+'" class="img-fluid" width="150"></a>&nbsp; ';
			
		}}
		
		var strbuttons = '';
		if(data.SHIPMENT_STATUS == 'PENDING' || data.SHIPMENT_STATUS == 'REJECTED')
		{
			<?php if(isset($_SESSION['logged_in']['SUPER_ADMIN'])&&$_SESSION['logged_in']['SUPER_ADMIN']==1) { ?>
			strbuttons = '&nbsp;<span><button class="btn btn-primary btn-sm" id="btn_acceptShipment" type="button"  value="' + data.SHIPMENT_ID + '" >Accept Shipment</button></span>&nbsp;<span><button class="btn btn-primary btn-sm" id="btn_rejectShipment" type="button"  value="' + data.SHIPMENT_ID + '" >Reject Shipment</button></span>';
			<?php } ?>
		}
		var strshipbuttions = '';
		if(data.SHIPMENT_STATUS == 'APPROVED')
		{
			
			<?php if(isset($_SESSION['logged_in']['IS_SHIPPER'])&&$_SESSION['logged_in']['IS_SHIPPER']==1) { ?>
			strshipbuttions = '&nbsp;<span><button class="btn btn-primary btn-sm" id="btn_changedShipped" type="button"  value="' + data.SHIPMENT_ID + '" >Mark as Shipped</button></span>';
			<?php } ?>
		}
		var USER_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
		
		var strpaymentrec = '';
		if(data.SHIPMENT_STATUS == 'SHIPPED' && data.FINAL_STATUS == 'PENDING' && data.CREATOR_ID == USER_ID)
		{
			strpaymentrec = '&nbsp;<span><button class="btn btn-primary btn-sm" id="btn_finalReceived" type="button"  value="' + data.SHIPMENT_ID + '" >';
			
			if(data.SHIPMENT_CATEGORY == 'SHIPMENT')
			{
				strpaymentrec += 'Item Received';
			}
			else
			{
				strpaymentrec += 'Payment Received';
			}
			
			strpaymentrec +='</button></span>';
 			
		}
		
		var createdate = convertDate(data.CREATED_DATE)
				 if(createdate=="1 Jan 1970 5:0 AM"){createdate="";}
				 if(createdate=="31 Dec 1969 6:0 PM"){createdate="";} // new added by rafiq
				 
				 
		taskDetail.append('<div class="message-body">' +
			'<div class="row" style="margin-top:15px;"><div class="col-md-6"><label for="txt_customer_name">Customer Name</label><input type="text" class="form-control" id="txt_customer_name" placeholder="Customer Name" value="' + data.CUSTOMER_NAME + '" readonly="readonly" ></div><div class="col-md-6"><label for="txt_invoice">Invoice No.</label><input type="text" class="form-control" id="txt_invoice" placeholder="Invoice" value="'+data.INVOICE_NUMBER+'" readonly="readonly" ></div></div>' +
			'<div class="row" style="margin-top:15px;"><div class="col-md-6"><label for="txt_ship_category">Shipment Type</label><input type="text" class="form-control" id="txt_ship_category" placeholder="Shipment Category" value="' + data.SHIPMENT_CATEGORY + '" readonly="readonly" ></div><div class="col-md-6"><label for="txt_price">Price</label><input type="text" class="form-control" id="txt_price" placeholder="Price" value="'+data.SHIPMENT_TITLE+'" readonly="readonly" ></div></div>' +			
			'<div class="row" style="margin-top:15px;"><div class="col-md-6"><label for="txt_created_by">Creator</label><input type="text" class="form-control" id="txt_created_by" placeholder="Creator" value="'+data.FULL_NAME+'" readonly="readonly" ></div><div class="col-md-6"><label for="txt_task_created">Created on</label><input type="text" class="form-control" id="txt_created" placeholder="Created" value="'+createdate+'" readonly="readonly" ></div></div>' +
			'<div class="row" style="margin-top:15px;"><div class="col-md-6"><label for="txt_ship_status">Shipment Status</label><input type="text" class="form-control" id="txt_ship_status" placeholder="Shipment Status" value="' + data.SHIPMENT_STATUS + '" readonly="readonly" ></div><div class="col-md-6"><label for="txt_final_status">Final Status</label><input type="text" class="form-control" id="txt_final_status" placeholder="Final Status" value="'+data.FINAL_STATUS+'" readonly="readonly" ></div></div>' +
           '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="txt_desc">Description</label><input type="text" class="form-control" id="txt_desc" placeholder="Description" value="'+data.SHIPMENT_DESCRIPTION+'" readonly="readonly" ></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="txt_task_desc">Pictures</label></div></div>'+ 
		   '<div class="row" style="margin-top:0px 10px;"><div class="col-md-12"><div style="border:1px solid #ccc; width:100%;">' + imgsOutput + '</div></div></div>' +
			'<div class="row" style="margin-top:25px;"><div class="col-md-12" style="text-align:center;">'+strbuttons+''+strshipbuttions+''+strpaymentrec+'&nbsp;<span><button class="btn btn-danger btn-sm" id="btn_delShipment" type="button"  value="' + data.SHIPMENT_ID + '" >Delete Shipment</button></span></div></div>' +		   
            '</div>'
        );
		/*
					
						taskDetail.append('<div class="message-body">' +							
							'<div class="row">' +
			'<div class="col-md-6">' +
			'<div class="row">' +
            '<div class="sender-details col-md-12">Customer Name</div>' +
			'<div class="col-md-7" ><div class="message-content">' + data.CUSTOMER_NAME + '</div></div><div class="col-md-5"></div></div>'+
           '</div><div class="col-md-6"><div class="sender-details col-md-12">Final Status(Payment)</div>'+
		   '<div class="message-content">' + data.FINAL_STATUS + '</div></div></div>' +
							'<div class="row">' +
			'<div class="col-md-6">' +
			'<div class="row">' +
            '<div class="sender-details col-md-12">Shipment Status</div>' +
			'<div class="col-md-7" ><div class="message-content">' + data.SHIPMENT_STATUS + '</div></div><div class="col-md-5"></div></div>'+
           '</div><div class="col-md-6"><div class="sender-details col-md-12">Category</div>'+
		   '<div class="message-content">' + data.SHIPMENT_CATEGORY + '</div></div></div>' +
		   '<div class="row">' +
			'<div class="col-md-6">' +
			'<div class="row">' +
            '<div class="sender-details col-md-12">Invoice Number</div>' +
			'<div class="col-md-7" ><div class="message-content">' + data.INVOICE_NUMBER + '</div></div><div class="col-md-5"></div></div>'+
           '</div><div class="col-md-6"><div class="sender-details col-md-12">Shipment Price</div>'+
		   '<div class="message-content">' + data.SHIPMENT_TITLE + '</div></div></div>' +
		   '<div class="row">' +
			'<div class="col-md-6">' +
			'<div class="row">' +
            '<div class="sender-details col-md-12">Create Date</div>' +
			'<div class="col-md-7" ><div class="message-content">' + data.CREATED_DATE + '</div></div><div class="col-md-5"></div></div>'+
           '</div><div class="col-md-6"><div class="sender-details col-md-12">Created By </div>'+
		   '<div class="message-content">' + data.FULL_NAME + '</div></div></div>' +
							'<div class="sender-details">Photos</div>' +
							'<div class="message-content">'+ imgsOutput +'</div>' +													
							'<div class="message-content"><span>Description: </span>' +
							'<div class="description">' + data.SHIPMENT_DESCRIPTION + '</div>' +
							'<div class="message-content"><span>&nbsp; </span>' +							
							'<div class="message-content">'+strbuttons+''+strshipbuttions+''+strpaymentrec+'&nbsp;<span><button class="btn btn-primary btn-sm" id="btn_delShipment" type="button"  value="' + data.SHIPMENT_ID + '" >Delete Shipment</button></span></div>' +
							'</div>' +
							'</div>'
						);
		*/
						//<span><button class="btn btn-primary btn-sm" id="btn_delTask" type="button"  value="' + data.TASK_ID + '" >Delete Task</button></span>
                }
            }
        });
    });

 $(document).on('click', '#btn_finalReceived', function() {    //OBJECT_ID, SHIPMENT_STATUS, UPDATED_DATE, 
var  OBJECT_ID = this.value;
var SHIPMENT_STATUS = 'RECEIVED';
var UPDATED_DATE = '';
var USER_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
 $.ajax({
       url: '<?php echo $url; ?>api2/shipments/update-final-status',
        type: 'GET',
		contentType:'application/x-www-form-urlencoded',
        data:"OBJECT_ID="+OBJECT_ID+"&SHIPMENT_STATUS="+SHIPMENT_STATUS+"&UPDATED_DATE="+UPDATED_DATE+"&USER_ID="+USER_ID,
		error: function(err) { //UPDATED_DATE
            alert(err.statusText);
        },
        success: function(data) {
					
				//alert(data.MESSAGE);
				
				funshowUsersShipments(USER_ID);
				
				
		$('div#taskDetails div.message-body').attr("style","visibility:hidden; display:none");
		$('div#taskDetails').append("Selected Shipment/RMA Final Item/Payment Received Successfully!");		
		
		
		}
		});

  });
  
   $(document).on('click', '#btn_changedShipped', function() {   
var  OBJECT_ID = this.value;
var SHIPMENT_STATUS = 'SHIPPED';
var UPDATED_DATE = '';
var USER_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
var SHIPMENT_NUMBER = prompt("Please Provide Shipment Number", "");
  if (SHIPMENT_NUMBER != null) {
	$.ajax({
       url: '<?php echo $url; ?>api2/shipments/update-status',
        type: 'GET',
		contentType:'application/x-www-form-urlencoded',
        data:"OBJECT_ID="+OBJECT_ID+"&SHIPMENT_STATUS="+SHIPMENT_STATUS+"&UPDATED_DATE="+UPDATED_DATE+"&SHIPMENT_NUMBER="+SHIPMENT_NUMBER+"&USER_ID="+USER_ID,
		error: function(err) { //UPDATED_DATE
            alert(err.statusText);
        },
        success: function(data) {
					
				//alert(data.MESSAGE);
				$("div.shipmentDetails[data-task_id*="+ OBJECT_ID + "]").remove();
		$('div#taskDetails div.message-body').attr("style","visibility:hidden; display:none");
		$('div#taskDetails').append("Selected Shipment status SHIPPED Successfully!");	
		
		//$('#shipmentTaskCount').innerHTML((parseInt($('#shipmentTaskCount').innerHTML())-1));
		
		
		}
		});
  }

  });

 $(document).on('click', '#btn_delShipment', function() {   
var  SHIPMENT_ID = this.value;
var USER_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
if(confirm("Are you sure to delete this Shipment : "+SHIPMENT_ID))
{
	$.ajax({
       url: '<?php echo $url; ?>api2/shipments/delete',
        type: 'POST',
		contentType:'application/x-www-form-urlencoded',
        data:"SHIPMENT_ID="+SHIPMENT_ID+"&USER_ID="+USER_ID,
		error: function(err) {
            alert(err.statusText);
        },
        success: function(data) {
					
				//alert(data.MESSAGE);
		$("div.shipmentDetails[data-task_id*="+ SHIPMENT_ID + "]").remove();
		$('div#taskDetails div.message-body').attr("style","visibility:hidden; display:none");
		$('div#taskDetails').append("Selected Shipment Deleted Successfully!");	
		
		//$('#shipmentTaskCount').innerHTML((parseInt($('#shipmentTaskCount').innerHTML())-1));
		
		
		}
		});
}
else
	return false;
  });
 $(document).on('click', '#btn_acceptShipment', function() {   
var  OBJECT_ID = this.value;
var SHIPMENT_STATUS = 'APPROVED';
var UPDATED_DATE = '1';
var USER_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
if(confirm("Are you sure to Accept this Shipment : "+OBJECT_ID))
{
	$.ajax({
       url: '<?php echo $url; ?>api2/shipments/update-status',
        type: 'GET',
		contentType:'application/x-www-form-urlencoded',
        data:"OBJECT_ID="+OBJECT_ID+"&SHIPMENT_STATUS="+SHIPMENT_STATUS+"&UPDATED_DATE="+UPDATED_DATE+"&USER_ID="+USER_ID,
		error: function(err) { //UPDATED_DATE
            alert(err.statusText);
        },
        success: function(data) {
					
				//alert(data.MESSAGE);
				//$("div.shipmentDetails[data-task_id*="+ OBJECT_ID + "]").remove();
		$('div#taskDetails div.message-body').attr("style","visibility:hidden; display:none");
		$('div#taskDetails').append("Selected Shipment Approved Successfully!");	
		
		//$('#shipmentTaskCount').innerHTML((parseInt($('#shipmentTaskCount').innerHTML())-1));
		
		
		}
		});
}
else
	return false;
  });
  $(document).on('click', '#btn_rejectShipment', function() {   
var  OBJECT_ID = this.value;
var SHIPMENT_STATUS = 'REJECTED';
var UPDATED_DATE = '1';
var USER_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
if(confirm("Are you sure to Accept this Shipment : "+OBJECT_ID))
{
	$.ajax({
       url: '<?php echo $url; ?>api2/shipments/update-status',
        type: 'GET',
		contentType:'application/x-www-form-urlencoded',
        data:"OBJECT_ID="+OBJECT_ID+"&SHIPMENT_STATUS="+SHIPMENT_STATUS+"&UPDATED_DATE="+UPDATED_DATE+"&USER_ID="+USER_ID,
		error: function(err) { //UPDATED_DATE
            alert(err.statusText);
        },
        success: function(data) {
					
				//alert(data.MESSAGE);
				//$("div.shipmentDetails[data-task_id*="+ OBJECT_ID + "]").remove();
		$('div#taskDetails div.message-body').attr("style","visibility:hidden; display:none");
		$('div#taskDetails').append("Selected Shipment Rejected Successfully!");	
		
		//$('#shipmentTaskCount').innerHTML((parseInt($('#shipmentTaskCount').innerHTML())-1));
		
		
		}
		});
}
else
	return false;
  });
  
function showDDTasks(u, c, t) {
        var url = (c === 1) ? '&CURRENT_DATE=<?php echo date('Y-m-d'); ?>' : '';

        $.ajax({
            url: u + url,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
                if(t === 'tasks')
                    createDDTaskList(data);
                else
                    createShipmentList(data)
            }
        });
    }
	
	function showOnlyPersonalTasks(u, c, t) {
        var url = (c === 1) ? '&CURRENT_DATE=<?php echo date('Y-m-d'); ?>' : '';

        $.ajax({
            url: u + url,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
                if(t === 'tasks')
                    createOnlyPersonalTaskList(data);
                else
                    createShipmentList(data)
            }
        });
    }
	
	function showAssignMeTasks(u, c, t) {
        var url = (c === 1) ? '&CURRENT_DATE=<?php echo date('Y-m-d'); ?>' : '';

        $.ajax({
            url: u + url,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
                if(t === 'tasks')
                    createAssignMeTaskList(data);
                else
                    createShipmentList(data)
            }
        });
    }
	
	
	 function showAssignOthersDD(u, c, t) {
        var url = (c === 1) ? '&CURRENT_DATE=<?php echo date('Y-m-d'); ?>' : '';

        $.ajax({
            url: u + url,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
                if(t === 'tasks')
                    createAssignOthersDD(data);
                else
                    createShipmentList(data)
            }
        });
    }
	
	 function showAssignOthersNew(u, c, t) {       

        $.ajax({
            url: u,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
                if(t === 'tasks')
                    createAssignOthersNew(data);               
            }
        });
    }

	 function showAssignMeNewDue(u, c, t) {       

        $.ajax({
            url: u,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
                if(t === 'tasks')
                    createAssignMeNewDue(data);               
            }
        });
    }
	 function showAssignMeNewRep(u, c, t) {       

        $.ajax({
            url: u,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
                if(t === 'tasks')
                    createAssignMeNewRep(data);               
            }
        });
    }
	 function showAssignMeNewTask(u, c, t) {       

        $.ajax({
            url: u,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
                if(t === 'tasks')
                    createAssignMeNewTask(data);               
            }
        });
    }
	 function showAssignMeNewTaskDue(u, c, t) {       

        $.ajax({
            url: u,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
                if(t === 'tasks')
                    createAssignMeNewTaskDue(data);               
            }
        });
    }
	function showAssignOtherNewDue(u, c, t) {       

        $.ajax({
            url: u,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
                if(t === 'tasks')
                    createAssignOtherNewDue(data);               
            }
        });
    }
	 function showAssignOtherNewRep(u, c, t) {       

        $.ajax({
            url: u,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
                if(t === 'tasks')
                    createAssignOtherNewRep(data);               
            }
        });
    }
	function showAssignOtherNewTaskRep(u, c, t) {       

        $.ajax({
            url: u,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
                if(t === 'tasks')
                    createAssignOtherNewTaskRep(data);               
            }
        });
    }
	function showAssignOtherNewTask(u, c, t) {       

        $.ajax({
            url: u,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
                if(t === 'tasks')
                    createAssignOtherNewTask(data);               
            }
        });
    }
	function showAssignOtherNewTaskDue(u, c, t) {       

        $.ajax({
            url: u,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
                if(t === 'tasks')
                    createAssignOtherNewTaskDue(data);               
            }
        });
    }
	 function showCCtaskDDNewDue(u, c, t) {       

        $.ajax({
            url: u,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
                if(t === 'tasks')
                    createCCtaskDDNewDue(data);               
            }
        });
    }
	 function showCCtaskDDNewRep(u, c, t) {       

        $.ajax({
            url: u,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
                if(t === 'tasks')
                    createCCtaskDDNewRep(data);               
            }
        });
    }
	function showCCtaskNewTodo(u, c, t) {       

        $.ajax({
            url: u,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
                if(t === 'tasks')
                    createCCtaskNewTodo(data);               
            }
        });
    }
	function showCCtaskNewNoRep(u, c, t) {       

        $.ajax({
            url: u,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
                if(t === 'tasks')
                    createCCtaskNewNoRep(data);               
            }
        });
    }
	function showCCtaskNewRep(u, c, t) {       

        $.ajax({
            url: u,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
                if(t === 'tasks')
                    createCCtaskNewRep(data);               
            }
        });
    }


	
	 function showTasks(u, c, t) {
        var url = (c === 1) ? '&CURRENT_DATE=<?php echo date('Y-m-d'); ?>' : '';

        $.ajax({
            url: u + url,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
                if(t === 'tasks')
                    createTaskList(data);
                else
                    createShipmentList(data)
            }
        });
    }
	
    function showAssignMeTasksDD(u, c, t) {
        var url = (c === 1) ? '&CURRENT_DATE=<?php echo date('Y-m-d'); ?>' : '';

        $.ajax({
            url: u + url,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
                if(t === 'tasks')
                    createAssignMeTasksDD(data);
                else
                    createShipmentList(data)
            }
        });
    }
	function showAssignOthersTasks(u, c, t) {
        var url = (c === 1) ? '&CURRENT_DATE=<?php echo date('Y-m-d'); ?>' : '';

        $.ajax({
            url: u + url,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
                if(t === 'tasks')
                    createAssignOthersTaskList(data);
                else
                    createShipmentList(data)
            }
        });
    }
	function showPersonalTasksDDDue(u, c, t) {
        var url = (c === 1) ? '&CURRENT_DATE=<?php echo date('Y-m-d'); ?>' : '';

        $.ajax({
            url: u + url,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
                if(t === 'tasks')
                    createPersonalTaskListDDDue(data);               
            }
        });
    }
	function showPersonalTasksDDRep(u, c, t) {
        var url = (c === 1) ? '&CURRENT_DATE=<?php echo date('Y-m-d'); ?>' : '';

        $.ajax({
            url: u + url,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
                if(t === 'tasks')
                    createPersonalTaskListDDRep(data);
               
            }
        });
    }
	function showPersonalTasksRep(u, c, t) {
              $.ajax({
            url: u,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
                if(t === 'tasks')
                    createPersonalTaskListRep(data);
               
            }
        });
    }
	function showPersonalTasksNoRep(u, c, t) {
       

        $.ajax({
            url: u,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
                if(t === 'tasks')
                    createPersonalTaskListNoRep(data);
               
            }
        });
    }
	function showPersonalTasks(u, c, t) {
       

        $.ajax({
            url: u,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
                if(t === 'tasks')
                    createPersonalTaskList(data);
               
            }
        });
    }
	function showCCTasksDD(u, c, t) {
        var url = (c === 1) ? '&CURRENT_DATE=<?php echo date('Y-m-d'); ?>' : '';

        $.ajax({
            url: u + url,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
                if(t === 'tasks')
                    createCCTasksDD(data);
                else
                    createShipmentList(data)
            }
        });
    }
	function showCCTasks(u, c, t) {
        var url = (c === 1) ? '&CURRENT_DATE=<?php echo date('Y-m-d'); ?>' : '';

        $.ajax({
            url: u + url,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
                if(t === 'tasks')
                    createCCTaskList(data);
                else
                    createShipmentList(data)
            }
        });
    }
	function showProjectTasks(u, c, t) {
        var url = (c === 1) ? '&CURRENT_DATE=<?php echo date('Y-m-d'); ?>' : '';

        $.ajax({
            url: u + url,
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
                if(t === 'tasks')
                    createTaskList(data);
                else
                    createShipmentList(data)
            }
        });
    }
function timeSince(date) {

  var seconds = Math.floor((new Date() - date) / 1000);

  var interval = Math.floor(seconds / 31536000);

  if (interval > 1) {
	  if(interval>5){   return ''; }
	  else { return interval + " years ago"; }
  }
  interval = Math.floor(seconds / 2592000);
  if (interval > 1) {
    return interval + " months ago";
  }
  interval = Math.floor(seconds / 86400);
  if (interval > 1) {
    return interval + " days ago";
  }
  interval = Math.floor(seconds / 3600);
  if (interval > 1) {
	  if(interval > 24)
	  	{ return "1 days ago"; }
		else
    	{ return interval + " hours ago"; }
  }
  interval = Math.floor(seconds / 60);
  if (interval > 1) {
    if(interval > 59 && interval < 121)
	{
		return  " 1 hour ago";
	}
	else if(interval > 120 && interval < 181)
	{
		return  " 2 hours ago";
	}
	else
	return interval + " minutes";
  }
  
  var lefttime = Math.floor(seconds) * (-1);
  if((lefttime/3600) > 1)
  {	
  	return Math.floor(lefttime/3600)+ " Hours left";
  }
  else
  {
	 return Math.floor(lefttime/60)+ " Minutes left"; 
  }
}

function toTimestamp(strDate){
   var datum = Date.parse(strDate);
   return datum/1000;
}

  function createDDTaskList(d) {
        taskList.empty();
       if(d.STATUS !== 'ERROR') {
            var data = JSON.parse(d.DATA);
   taskList.append('<div class="mail-list" style="background: grey;color:white;"><h6 style="margin:0px">Due Today Tasks</h6></div>');
            // console.log(data);		
			for (var i = 0; i < data.length; i++) {
			 var duedatae = convertDate(data[i].DUE_DATE);
 				 if(duedatae=="1 Jan 1970 5:0 AM"){duedatae="";}
				 if(duedatae=="31 Dec 1969 6:0 PM"){duedatae="";} // new added by rafiq

		//alert(toTimestamp(duedatae));
	 
//console.log(timeSince(new Date(duedatae)));
		    var strstyleDD=' style="color:#ffd80b; font-weight:600; text-transform:capitalize" ';
			var timecheck = timeSince(new Date(duedatae));
			if (timecheck = timecheck.includes("ago"))
			{
				strstyleDD =' style="color:red; font-weight:600; text-transform:capitalize" ';
			}
			
                taskList.append('<div class="mail-list taskDetails" data-task_id="' + data[i].TASK_ID + '">' +
                    '<div class="content">' +
                    '<p class="message_text" '+ strstyleDD +'>' + data[i].TASK_TITLE + '</p>' +
					'<p class="message_text">'+timeSince(new Date(duedatae))+'</p>' +
                    '</div>' +
                    '</div>');
            }
			
        } else {
            taskList.append('<div class="error">' + d.MESSAGE + '</div>')
        }
    }
    function createPersonalTaskListDDDue(d) {
        taskList.empty();
       if(d.STATUS !== 'ERROR') {
            var data = JSON.parse(d.DATA);
   taskList.append('<div class="mail-list" style="background: grey;color:white;"><h6 style="margin:0px">Due Today Non Repeated Personal Tasks</h6></div>');
            // console.log(data);		
			for (var i = 0; i < data.length; i++) {
			 var duedatae = convertDate(data[i].DUE_DATE);
			  if(duedatae=="1 Jan 1970 5:0 AM"){duedatae="";}
				 if(duedatae=="31 Dec 1969 6:0 PM"){duedatae="";} // new added by rafiq
		//alert(toTimestamp(duedatae));
	 var openvar = "";
		  var closevar = "";
		   var completevar = "";
		    var inprogressvar = "";
 switch(data[i].TASK_STATUS)
		 {
			 case "OPEN":
			 		openvar = " selected='selected'";
			 	break;
				 case "CLOSED":
			 		closevar = " selected='selected'";
			 	break;
				 case "COMPLETED":
			 		completevar = " selected='selected'";
			 	break;
				 case "IN PROGRESS":
			 		inprogressvar = " selected='selected'";
			 	break;
		 }
		 var notificationStatusYes="";
		 var notificationStatusNo="";
		 //alert('h '+ data.TASK_NOTIFICATION_STATUS);
		 if(data[i].STATUS==1){notificationStatusYes = " checked"}
		 else{notificationStatusNo = " checked"}
//console.log(timeSince(new Date(duedatae)));
		    var strstyleDD=' style="color:#ffd80b; font-weight:600; text-transform:capitalize" ';
			var timecheck = timeSince(new Date(duedatae));
			if (timecheck = timecheck.includes("ago"))
			{
				strstyleDD =' style="color:red; font-weight:600; text-transform:capitalize" ';
			}
/*			
var anchortag = '<a href="javascript:changeStatusDPNR(\'COMPLETED\','+data[i].TASK_ID+');" style="font-size:12px;">Mark as Complete</a>';								
if(data[i].TASK_STATUS == 'COMPLETED')
{				
    anchortag = '<a href="#" style="font-size:12px;">Completed</a>';
}		
*/
var anchortag = '<a href="javascript:changeStatusDD(\'COMPLETED\','+data[i].TASK_ID+');" style="font-size:12px;"><img src="assets/images/mark-completed.gif" id="markascompleted'+data[i].TASK_ID+'" /></a>';								
if(data[i].TASK_STATUS == 'COMPLETED')
{				
    anchortag = '<a href="#" style="font-size:12px;"><img src="assets/images/completed-task.gif" id="completedtask"/></a>';
}

var notificationButton = '<img src="assets/images/btn-off.png" id="imgoff" onclick="changeNotificationStatusDMNR(1,' + data[i].TASK_ID + ');" />';
if(data[i].STATUS==1){notificationButton = '<img src="assets/images/btn-on.png" id="imgon" onclick="changeNotificationStatusDMNR(0,' + data[i].TASK_ID + ');" />';}


	
			/*
			 taskList.append('<div class="mail-list taskDetails" data-task_id="' + data[i].TASK_ID + '">' +
                     '<div class="content" style="width:97%;">' +
                    '<p class="message_text" '+ strstyleDD +'>' + data[i].TASK_TITLE + '</p>' +
					'<p class="message_text">'+timeSince(new Date(duedatae))+'</p>' +
					'<div class="row"><div class="col-md-6" style="padding:0px"><select class="custom-select browser-default" required="" id="TASK_STATUS" name="TASK_STATUS" onchange="changeStatusDD(this.value,' + data[i].TASK_ID + ');"><option value="OPEN" ' + openvar + ' >OPEN</option><option value="CLOSED" ' + closevar + '>CLOSED</option><option value="COMPLETED" ' + completevar + '>COMPLETED</option><option value="IN PROGRESS" ' + inprogressvar + '>IN PROGRESS</option></select></div>'+
					'<div class="col-md-3">'+
			'<div class="message-content" style="padding-top:6px; color: #46de46;font-weight:600" ><input onchange="changeNotificationStatus(this.value,' + data[i].TASK_ID + ');" class="message-content" type="radio" name="TASK_NOTIFICATION_STATUS_' + data[i].TASK_ID + '" value="1" ' + notificationStatusYes + '> Yes <input onchange="changeNotificationStatus(this.value,' + data[i].TASK_ID + ');" class="message-content" style="margin-left:6px" type="radio" name="TASK_NOTIFICATION_STATUS_' + data[i].TASK_ID + '" value="0" ' + notificationStatusNo + '> No </div>'+
			'</div>'+
			'<div class="col-md-3" style="padding-left:10px;">'+anchortag+'</div>' +
						'</div>'+			
			'</div></div></div>');	
		
		
                taskList.append('<div class="mail-list taskDetails" data-task_id="' + data[i].TASK_ID + '">' +
                     '<div class="content" style="width:97%;">' +
                    '<p class="message_text" '+ strstyleDD +'>' + data[i].TASK_TITLE + '</p>' +
					'<p class="message_text">'+timeSince(new Date(duedatae))+'</p>' +
					'<div class="row"><div class="col-md-6" style="padding:0px"><select class="custom-select browser-default" required="" id="TASK_STATUS" name="TASK_STATUS" onchange="changeStatusDD(this.value,' + data[i].TASK_ID + ');"><option value="OPEN" ' + openvar + ' >OPEN</option><option value="CLOSED" ' + closevar + '>CLOSED</option><option value="COMPLETED" ' + completevar + '>COMPLETED</option><option value="IN PROGRESS" ' + inprogressvar + '>IN PROGRESS</option></select></div>'+
					'<div class="col-md-3">'+
			'<div class="message-content" style="padding-top:6px; color: #46de46;font-weight:600" ><input onchange="changeNotificationStatus(this.value,' + data[i].TASK_ID + ');" class="message-content" type="radio" name="TASK_NOTIFICATION_STATUS_' + data[i].TASK_ID + '" value="1" ' + notificationStatusYes + '> Yes <input onchange="changeNotificationStatus(this.value,' + data[i].TASK_ID + ');" class="message-content" style="margin-left:6px" type="radio" name="TASK_NOTIFICATION_STATUS_' + data[i].TASK_ID + '" value="0" ' + notificationStatusNo + '> No </div>'+
			'</div>'+
			'<div class="col-md-3" style="padding-left:10px;">'+anchortag+'</div>' +
						'</div>'+			
			'</div></div></div>');	
		*/
		
		 taskList.append('<div class="mail-list taskDetailsPersonal" data-task_id="' + data[i].TASK_ID + '">' +
                    '<div class="content" style="width:97%;">'+
						'<div class="row"><div class="col-md-8"><p class="message_text" '+ strstyleDD +'>' + data[i].TASK_TITLE + '</p></div><div class="col-md-4" style="text-align:right; padding:0px; margin:0px;">'+anchortag+'</div></div>' + 
                    	'<div class="row"><div class="col-md-9"><p class="message_text">'+timeSince(new Date(duedatae))+'</p></div><div class="col-md-3"  id="due_personl_rep" style="text-align:right;">'+notificationButton+'</div></div>'+ 					
                   		'<div class="row" style="visibility:hidden; display:none;"><div class="col-md-1"></div><div class="col-md-10" style="padding:0px"><select class="custom-select browser-default" required="" id="TASK_STATUS" name="TASK_STATUS" onchange="changeStatusDD(this.value,' + data[i].TASK_ID + ');"><option value="OPEN" ' + openvar + ' >OPEN</option><option value="CLOSED" ' + closevar + '>CLOSED</option><option value="COMPLETED" ' + completevar + '>COMPLETED</option><option value="IN PROGRESS" ' + inprogressvar + '>IN PROGRESS</option></select></div><div class="col-md-1"></div></div>'+
					'</div></div>');	
					
					
			
			
            }
			
        } else {
            taskList.append('<div class="error">' + d.MESSAGE + '</div>')
        }
    }
	function createPersonalTaskListDDRep(d) {
        taskList.empty();
       if(d.STATUS !== 'ERROR') {
            var data = JSON.parse(d.DATA);
   taskList.append('<div class="mail-list" style="background: grey;color:white;"><h6 style="margin:0px">Due Today Repeated Personal Tasks</h6></div>');
             console.log(data);		
			for (var i = 0; i < data.length; i++) {
			 var duedatae = convertDate(data[i].DUE_DATE);
			 if(duedatae=="1 Jan 1970 5:0 AM"){duedatae="";}
				 if(duedatae=="31 Dec 1969 6:0 PM"){duedatae="";} // new added by rafiq
		//alert(toTimestamp(duedatae));
		
		 var openvar = "";
		  var closevar = "";
		   var completevar = "";
		    var inprogressvar = "";
 switch(data[i].TASK_STATUS)
		 {
			 case "OPEN":
			 		openvar = " selected='selected'";
			 	break;
				 case "CLOSED":
			 		closevar = " selected='selected'";
			 	break;
				 case "COMPLETED":
			 		completevar = " selected='selected'";
			 	break;
				 case "IN PROGRESS":
			 		inprogressvar = " selected='selected'";
			 	break;
		 }

		 var output = "";
		 var repint = data[i].REPEAT_INTERVAL;
		 //if(output == "") output += "Never";
		 var arrIntervals = repint.split(',');
		 var arrayLength = arrIntervals.length;
for (var x = 0; x < arrayLength; x++) {
	switch(arrIntervals[x])
	{
		case '0': 
			output += "Mon, ";
			break;
		case '1': 
			output += "Tue, ";
			break;
		case '2': 
			output += "Wed, ";
			break;
		case '3': 
			output += "Thu, ";
			break;
		case '4': 
			output += "Fri, ";
			break;
		case '5': 
			output += "Sat, ";
			break;
		case '6': 
			output += "Sun, ";
			break;
		case '7': 
			output += "Month";
			break;
	}
   // console.log();
    //Do something
}

if(repint == "0,1,2,3,4,5,6") output = "Every Day";
if(output == "") output = "Never";

	 
//console.log(timeSince(new Date(duedatae)));
		    var strstyleDD=' style="color:#ffd80b; font-weight:600; text-transform:capitalize" ';
			var timecheck = timeSince(new Date(duedatae));
			if (timecheck = timecheck.includes("ago"))
			{
				strstyleDD =' style="color:red; font-weight:600; text-transform:capitalize" ';
			}
			
var anchortag = '<a href="javascript:changeStatusDD(\'COMPLETED\','+data[i].TASK_ID+');" style="font-size:12px;"><img src="assets/images/mark-completed.gif" id="markascompleted'+data[i].TASK_ID+'" /></a>';								
if(data[i].TASK_STATUS == 'COMPLETED')
{				
    anchortag = '<a href="#" style="font-size:12px;"><img src="assets/images/completed-task.gif" id="completedtask"/></a>';
}

var notificationButton = '<img src="assets/images/btn-off.png" id="imgoff" onclick="changeNotificationStatus(1,' + data[i].TASK_ID + ');" />';
if(data[i].STATUS==1){notificationButton = '<img src="assets/images/btn-on.png" id="imgon" onclick="changeNotificationStatus(0,' + data[i].TASK_ID + ');" />';}
			
			
                taskList.append('<div class="mail-list taskDetailsPersonal" data-task_id="' + data[i].TASK_ID + '">' +
                    '<div class="content" style="width:97%;">'+
						'<div class="row"><div class="col-md-8"><p class="message_text" '+ strstyleDD +'>' + data[i].TASK_TITLE + '</p></div><div class="col-md-4"  style="text-align:right;padding:0px; margin:0px;">'+anchortag+'</div></div>' + 
                    	'<div class="row"><div class="col-md-9"><p class="message_text">'+timeSince(new Date(duedatae))+'</p></div><div class="col-md-3"  id="due_personl_rep" style="text-align:right;">'+notificationButton+'</div></div>'+ 
						'<div class="row"><div class="col-md-12"><p class="message_text"><strong>Repeat: </strong> '+output+'</p></div></div>'+ 
                   		'<div class="row" style="visibility:hidden; display:none;"><div class="col-md-1"></div><div class="col-md-10" style="padding:0px"><select class="custom-select browser-default" required="" id="TASK_STATUS" name="TASK_STATUS" onchange="changeStatusDD(this.value,' + data[i].TASK_ID + ');"><option value="OPEN" ' + openvar + ' >OPEN</option><option value="CLOSED" ' + closevar + '>CLOSED</option><option value="COMPLETED" ' + completevar + '>COMPLETED</option><option value="IN PROGRESS" ' + inprogressvar + '>IN PROGRESS</option></select></div><div class="col-md-1"></div></div>'+
					'</div></div>');	
					/*
					
                taskList.append('<div class="mail-list taskDetails" data-task_id="' + data[i].TASK_ID + '">' +
                    '<div class="content" style="width:97%;">' +
                    '<p class="message_text" '+ strstyleDD +'>' + data[i].TASK_TITLE + '</p>' +
					'<p class="message_text">'+timeSince(new Date(duedatae))+'</p>' +
                   '<div class="row"><div class="col-md-6" style="padding:0px"><select class="custom-select browser-default" required="" id="TASK_STATUS" name="TASK_STATUS" onchange="changeStatusDD(this.value,' + data[i].TASK_ID + ');"><option value="OPEN" ' + openvar + ' >OPEN</option><option value="CLOSED" ' + closevar + '>CLOSED</option><option value="COMPLETED" ' + completevar + '>COMPLETED</option><option value="IN PROGRESS" ' + inprogressvar + '>IN PROGRESS</option></select></div>'+
				   '<div class="col-md-3">'+
			'<div class="message-content" style="padding-top:6px; color: #46de46;font-weight:600" ><input onchange="changeNotificationStatus(this.value,' + data[i].TASK_ID + ');" class="message-content" type="radio" name="TASK_NOTIFICATION_STATUS_' + data[i].TASK_ID + '" value="1" ' + notificationStatusYes + '> Yes <input onchange="changeNotificationStatus(this.value,' + data[i].TASK_ID + ');" class="message-content" style="margin-left:6px" type="radio" name="TASK_NOTIFICATION_STATUS_' + data[i].TASK_ID + '" value="0" ' + notificationStatusNo + '> No </div>'+
			'</div>'+
			'<div class="col-md-3" style="padding-left:10px;">'+anchortag+'</div>' +
						'</div>'+			
			'</div></div></div>');	
					*/		
            }
			
        } else {
            taskList.append('<div class="error">' + d.MESSAGE + '</div>')
        }
    }
	function createPersonalTaskListRep(d) {
        taskList.empty();
		taskDetail.empty();
       if(d.STATUS !== 'ERROR') {
            var data = JSON.parse(d.DATA);
   taskList.append('<div class="mail-list" style="background: grey;color:white;"><h6 style="margin:0px">Repeated Personal Tasks</h6></div>');
            // console.log(data);		
			for (var i = 0; i < data.length; i++) {
			 var duedatae = convertDate(data[i].DUE_DATE);
		//alert(toTimestamp(duedatae));
		
		 var openvar = "";
		  var closevar = "";
		   var completevar = "";
		    var inprogressvar = "";
 switch(data[i].TASK_STATUS)
		 {
			 case "OPEN":
			 		openvar = " selected='selected'";
			 	break;
				 case "CLOSED":
			 		closevar = " selected='selected'";
			 	break;
				 case "COMPLETED":
			 		completevar = " selected='selected'";
			 	break;
				 case "IN PROGRESS":
			 		inprogressvar = " selected='selected'";
			 	break;
		 }
		 
		 
                 var duedatae = convertDate(data[i].DUE_DATE)
				 if(duedatae=="1 Jan 1970 5:0 AM"){duedatae="";}
				 if(duedatae=="31 Dec 1969 6:0 PM"){duedatae="";} // new added by rafiq

		 
		 var notificationStatusYes="";
		 var notificationStatusNo="";
		 //alert('h '+ data.TASK_NOTIFICATION_STATUS);
		 if(data[i].STATUS==1){notificationStatusYes = " checked"}
		 else{notificationStatusNo = " checked"}
	 
//console.log(timeSince(new Date(duedatae)));
		 var output = "";
		 var repint = data[i].REPEAT_INTERVAL;
		 //if(output == "") output += "Never";
		 var arrIntervals = repint.split(',');
		 var arrayLength = arrIntervals.length;
for (var x = 0; x < arrayLength; x++) {
	switch(arrIntervals[x])
	{
		case '0': 
			output += "Mon, ";
			break;
		case '1': 
			output += "Tue, ";
			break;
		case '2': 
			output += "Wed, ";
			break;
		case '3': 
			output += "Thu, ";
			break;
		case '4': 
			output += "Fri, ";
			break;
		case '5': 
			output += "Sat, ";
			break;
		case '6': 
			output += "Sun, ";
			break;
		case '7': 
			output += "Month";
			break;
	}
   // console.log();
    //Do something
}

if(repint == "0,1,2,3,4,5,6") output = "Every Day";
if(output == "") output = "Never";

		    var strstyleDD=' style="color:#ffd80b; font-weight:600; text-transform:capitalize" ';
			var timecheck = timeSince(new Date(duedatae));
			if (timecheck = timecheck.includes("ago"))
			{
				strstyleDD =' style="color:red; font-weight:600; text-transform:capitalize" ';
			}
			
			

var anchortag = '<a href="javascript:changeStatusPRR(\'COMPLETED\','+data[i].TASK_ID+');" style="font-size:12px;"><img src="assets/images/mark-completed.gif" id="markascompleted'+data[i].TASK_ID+'" /></a>';								
if(data[i].TASK_STATUS == 'COMPLETED')
{				
    anchortag = '<a href="#" style="font-size:12px;"><img src="assets/images/completed-task.gif" id="completedtask"/></a>';
}

var notificationButton = '<img src="assets/images/btn-off.png" id="imgoff" onclick="changeNotificationStatusPRRT(1,' + data[i].TASK_ID + ');" />';
if(data[i].STATUS==1){notificationButton = '<img src="assets/images/btn-on.png" id="imgon" onclick="changeNotificationStatusPRRT(0,' + data[i].TASK_ID + ');" />';}

/*
var anchortag = '<a href="javascript:changeStatusPRR(\'COMPLETED\','+data[i].TASK_ID+');" style="font-size:12px;">Mark as Complete</a>';								
if(data[i].TASK_STATUS == 'COMPLETED')
{				
    anchortag = '<a href="#" style="font-size:12px;">Completed</a>';
}
	*/		
               /*
			    taskList.append('<div class="mail-list taskDetails" data-task_id="' + data[i].TASK_ID + '">' +
                    '<div class="content" style="width:97%;">' +
                    '<p class="message_text" '+ strstyleDD +'>' + data[i].TASK_TITLE + '</p>' +
					'<p class="message_text">'+duedatae+'</p>' +
                   '<div class="row">'+
				   '<div class="col-md-6" style="padding:0px"><select class="custom-select browser-default" required="" id="TASK_STATUS" name="TASK_STATUS" onchange="changeStatus(this.value,' + data[i].TASK_ID + ');"><option value="OPEN" ' + openvar + ' >OPEN</option><option value="CLOSED" ' + closevar + '>CLOSED</option><option value="COMPLETED" ' + completevar + '>COMPLETED</option><option value="IN PROGRESS" ' + inprogressvar + '>IN PROGRESS</option></select></div>'+
				   '<div class="col-md-3">'+
			'<div class="message-content" style="padding-top:6px; color: #46de46;font-weight:600" ><input onchange="changeNotificationStatus(this.value,' + data[i].TASK_ID + ');" class="message-content" type="radio" name="TASK_NOTIFICATION_STATUS_' + data[i].TASK_ID + '" value="1" ' + notificationStatusYes + '> Yes <input onchange="changeNotificationStatus(this.value,' + data[i].TASK_ID + ');" class="message-content" style="margin-left:6px" type="radio" name="TASK_NOTIFICATION_STATUS_' + data[i].TASK_ID + '" value="0" ' + notificationStatusNo + '> No </div>'+
			'</div>'+
			'<div class="col-md-3" style="padding-left:10px;">'+anchortag+'</div>' +
						'</div>'+			
			'</div></div></div>');
			*/
			  taskList.append('<div class="mail-list taskDetailsPersonal" data-task_id="' + data[i].TASK_ID + '">' +
                    '<div class="content" style="width:97%;">'+
						'<div class="row"><div class="col-md-8"><p class="message_text" '+ strstyleDD +'>' + data[i].TASK_TITLE + '</p></div><div class="col-md-4"  id="due_personl_rep" style="text-align:right;">'+anchortag+'</div></div>' + 
                    	'<div class="row"><div class="col-md-9"><p class="message_text">'+timeSince(new Date(duedatae))+'</p></div><div class="col-md-3"  id="due_personl_rep" style="text-align:right;">'+notificationButton+'</div></div>'+ 
                   		'<div class="row" style="margin-top:5px;"><div class="col-md-7"><p class="message_text"><strong>Repeat: </strong> '+output+'</p></div><div class="col-md-2"><strong>Status:</strong></div><div class="col-md-3" style="padding:0px"><select class="custom-select browser-default" required="" id="TASK_STATUS" name="TASK_STATUS" onchange="changeStatusPRR(this.value,' + data[i].TASK_ID + ');"><option value="OPEN" ' + openvar + ' >OPEN</option><option value="CLOSED" ' + closevar + '>CLOSED</option><option value="COMPLETED" ' + completevar + '>COMPLETED</option><option value="IN PROGRESS" ' + inprogressvar + '>IN PROGRESS</option></select></div></div>'+
					'</div></div>');
			
            }
			
        } else {
            taskList.append('<div class="error">' + d.MESSAGE + '</div>')
        }
    }
	function createPersonalTaskListNoRep(d) {
        taskList.empty();
       if(d.STATUS !== 'ERROR') {
            var data = JSON.parse(d.DATA);
   taskList.append('<div class="mail-list" style="background: grey;color:white;"><h6 style="margin:0px">Non Repeated Personal Tasks</h6></div>');
            // console.log(data);		
			for (var i = 0; i < data.length; i++) {
			 var duedatae = convertDate(data[i].DUE_DATE);
			  if(duedatae=="1 Jan 1970 5:0 AM"){duedatae="";}
				 if(duedatae=="31 Dec 1969 6:0 PM"){duedatae="";} // new added by rafiq
		//alert(toTimestamp(duedatae));
		
		 var openvar = "";
		  var closevar = "";
		   var completevar = "";
		    var inprogressvar = "";
 switch(data[i].TASK_STATUS)
		 {
			 case "OPEN":
			 		openvar = " selected='selected'";
			 	break;
				 case "CLOSED":
			 		closevar = " selected='selected'";
			 	break;
				 case "COMPLETED":
			 		completevar = " selected='selected'";
			 	break;
				 case "IN PROGRESS":
			 		inprogressvar = " selected='selected'";
			 	break;
		 }
		 var notificationStatusYes="";
		 var notificationStatusNo="";
		 //alert('h '+ data.TASK_NOTIFICATION_STATUS);
		 if(data[i].STATUS==1){notificationStatusYes = " checked"}
		 else{notificationStatusNo = " checked"}
	 
//console.log(timeSince(new Date(duedatae)));
		    var strstyleDD=' style="color:#ffd80b; font-weight:600; text-transform:capitalize" ';
			var timecheck = timeSince(new Date(duedatae));
			if (timecheck = timecheck.includes("ago"))
			{
				strstyleDD =' style="color:red; font-weight:600; text-transform:capitalize" ';
			}
/*			
			var anchortag = '<a href="javascript:changeStatusPNR(\'COMPLETED\','+data[i].TASK_ID+');" style="font-size:12px;">Mark as Complete</a>';								
			if(data[i].TASK_STATUS == 'COMPLETED')
			{				
				anchortag = '<a href="#" style="font-size:12px;">Completed</a>';
			}
*/
var anchortag = '<a href="javascript:changeStatusPNR(\'COMPLETED\','+data[i].TASK_ID+');" style="font-size:12px;"><img src="assets/images/mark-completed.gif" id="markascompleted'+data[i].TASK_ID+'" /></a>';								
if(data[i].TASK_STATUS == 'COMPLETED')
{				
    anchortag = '<a href="#" style="font-size:12px;"><img src="assets/images/completed-task.gif" id="completedtask"/></a>';
}

var notificationButton = '<img src="assets/images/btn-off.png" id="imgoff" onclick="changeNotificationStatusPNRT(1,' + data[i].TASK_ID + ');" />';
if(data[i].STATUS==1){notificationButton = '<img src="assets/images/btn-on.png" id="imgon" onclick="changeNotificationStatusPNRT(0,' + data[i].TASK_ID + ');" />';}


			
			/*
                taskList.append('<div class="mail-list taskDetails" data-task_id="' + data[i].TASK_ID + '">' +
                    '<div class="content">' +
                    '<p class="message_text" '+ strstyleDD +'>' + data[i].TASK_TITLE + '</p>' +
					'<p class="message_text">'+duedatae+'</p>' +
                   '<div class="row">'+
				   '<div class="col-md-6" style="padding:0px"><select class="custom-select browser-default" required="" id="TASK_STATUS" name="TASK_STATUS" onchange="changeStatus(this.value,' + data[i].TASK_ID + ');"><option value="OPEN" ' + openvar + ' >OPEN</option><option value="CLOSED" ' + closevar + '>CLOSED</option><option value="COMPLETED" ' + completevar + '>COMPLETED</option><option value="IN PROGRESS" ' + inprogressvar + '>IN PROGRESS</option></select></div>'+
				   '<div class="col-md-3">'+
			'<div class="message-content" style="padding-top:6px; color: #46de46;font-weight:600" ><input onchange="changeNotificationStatus(this.value,' + data[i].TASK_ID + ');" class="message-content" type="radio" name="TASK_NOTIFICATION_STATUS_' + data[i].TASK_ID + '" value="1" ' + notificationStatusYes + '> Yes <input onchange="changeNotificationStatus(this.value,' + data[i].TASK_ID + ');" class="message-content" style="margin-left:6px" type="radio" name="TASK_NOTIFICATION_STATUS_' + data[i].TASK_ID + '" value="0" ' + notificationStatusNo + '> No </div>'+
			'</div>'+
			'<div class="col-md-3" style="padding-left:10px;">'+anchortag+'</div>' +
						'</div>'+
			'</div></div></div>');
			
			*/
			 taskList.append('<div class="mail-list taskDetailsPersonal" data-task_id="' + data[i].TASK_ID + '">' +
                    '<div class="content" style="width:97%;">'+
						'<div class="row"><div class="col-md-8"><p class="message_text" '+ strstyleDD +'>' + data[i].TASK_TITLE + '</p></div><div class="col-md-4"  id="due_personl_rep" style="text-align:right;">'+anchortag+'</div></div>' + 
                    	'<div class="row"><div class="col-md-9"><p class="message_text">'+timeSince(new Date(duedatae))+'</p></div><div class="col-md-3"  id="due_personl_rep" style="text-align:right;">'+notificationButton+'</div></div>'+ 					
                   		'<div class="row" style="margin-top:5px;"><div class="col-md-8" style="text-align:right;"><strong>Status:</strong></div><div class="col-md-4" style="padding:0px"><select class="custom-select browser-default" required="" id="TASK_STATUS" name="TASK_STATUS" onchange="changeStatusPNR(this.value,' + data[i].TASK_ID + ');"><option value="OPEN" ' + openvar + ' >OPEN</option><option value="CLOSED" ' + closevar + '>CLOSED</option><option value="COMPLETED" ' + completevar + '>COMPLETED</option><option value="IN PROGRESS" ' + inprogressvar + '>IN PROGRESS</option></select></div></div>'+
					'</div></div>');	
            }
			
        } else {
            taskList.append('<div class="error">' + d.MESSAGE + '</div>')
        }
    }
	function createPersonalTaskList(d) {
        taskList.empty();
		 taskDetail.empty();
       if(d.STATUS !== 'ERROR') {
            var data = JSON.parse(d.DATA);
   taskList.append('<div class="mail-list" style="background: grey;color:white;"><h6 style="margin:0px">To Do Personal Tasks</h6></div>');
            // console.log(data);		
			for (var i = 0; i < data.length; i++) {
			 var duedatae = convertDate(data[i].DUE_DATE);
			 if(duedatae=="1 Jan 1970 5:0 AM"){duedatae="";}
				 if(duedatae=="31 Dec 1969 6:0 PM"){duedatae="";} // new added by rafiq
		//alert(toTimestamp(duedatae));
		
		 var openvar = "";
		  var closevar = "";
		   var completevar = "";
		    var inprogressvar = "";
 switch(data[i].TASK_STATUS)
		 {
			 case "OPEN":
			 		openvar = " selected='selected'";
			 	break;
				 case "CLOSED":
			 		closevar = " selected='selected'";
			 	break;
				 case "COMPLETED":
			 		completevar = " selected='selected'";
			 	break;
				 case "IN PROGRESS":
			 		inprogressvar = " selected='selected'";
			 	break;
		 }
		 var output = "";
		 var repint = data[i].REPEAT_INTERVAL;
		 //if(output == "") output += "Never";
		 var arrIntervals = repint.split(',');
		 var arrayLength = arrIntervals.length;
for (var x = 0; x < arrayLength; x++) {
	switch(arrIntervals[x])
	{
		case '0': 
			output2 += "Mon, ";
			break;
		case '1': 
			output2 += "Tue, ";
			break;
		case '2': 
			output2 += "Wed, ";
			break;
		case '3': 
			output2 += "Thu, ";
			break;
		case '4': 
			output2 += "Fri, ";
			break;
		case '5': 
			output2 += "Sat, ";
			break;
		case '6': 
			output2 += "Sun, ";
			break;
		case '7': 
			output2 += "Month";
			break;
	}
   // console.log();
    //Do something
}

if(repint == "0,1,2,3,4,5,6") output = "Every Day";
if(output == "") output = "Never";
		 var notificationStatusYes="";
		 var notificationStatusNo="";
		 //alert('h '+ data.TASK_NOTIFICATION_STATUS);
		 if(data[i].STATUS==1){notificationStatusYes = " checked"}
		 else{notificationStatusNo = " checked"}
	 
//console.log(timeSince(new Date(duedatae)));
		    var strstyleDD=' style="color:#ffd80b; font-weight:600; text-transform:capitalize" ';
			var timecheck = timeSince(new Date(duedatae));
			if (timecheck = timecheck.includes("ago"))
			{
				strstyleDD =' style="color:red; font-weight:600; text-transform:capitalize" ';
			}
var anchortag = '<a href="javascript:changeStatusPersonalTDT(\'COMPLETED\','+data[i].TASK_ID+');" style="font-size:12px;"><img src="assets/images/mark-completed.gif" id="markascompleted'+data[i].TASK_ID+'" /></a>';								
if(data[i].TASK_STATUS == 'COMPLETED')
{				
    anchortag = '<a href="#" style="font-size:12px;"><img src="assets/images/completed-task.gif" id="completedtask"/></a>';
}

var notificationButton = '<img src="assets/images/btn-off.png" id="imgoff" onclick="changeNotificationStatusPersonalTDT(1,' + data[i].TASK_ID + ');" />';
if(data[i].STATUS==1){notificationButton = '<img src="assets/images/btn-on.png" id="imgon" onclick="changeNotificationStatusPersonalTDT(0,' + data[i].TASK_ID + ');" />';}


			/*
			var anchortag = '<a href="javascript:changeStatusPTD(\'COMPLETED\','+data[i].TASK_ID+');" style="font-size:12px;">Mark as Complete</a>';								
			if(data[i].TASK_STATUS == 'COMPLETED')
			{				
				anchortag = '<a href="#" style="font-size:12px;">Completed</a>';
			}
			
										
taskList.append('<div class="mail-list taskDetails" data-task_id="' + data[i].TASK_ID + '">' +
	       			'<div class="content" style="width:97%;">' +
						'<p class="message_text" style="color:black;">' + data[i].TASK_TITLE + '</p>' +
						'<p class="message_text" style="float:left">Repeat : </p>'+
						'<p class="message_text"> '+output+'</p>' +
						'<div class="row">'+
							'<div class="col-md-6" style="padding:0px;">'+
								'<select class="custom-select browser-default" required="" id="TASK_STATUS" name="TASK_STATUS" onchange="changeStatus(this.value,' + data[i].TASK_ID + ');"><option value="OPEN" ' + openvar + ' >OPEN</option><option value="CLOSED" ' + closevar + '>CLOSED</option><option value="COMPLETED" ' + completevar + '>COMPLETED</option><option value="IN PROGRESS" ' + inprogressvar + '>IN PROGRESS</option></select>'+
							'</div>'+
							'<div class="col-md-3">'+
								'<div class="message-content" style="padding-top:6px; color: #46de46;font-weight:600" >'+
									'<input onchange="changeNotificationStatus(this.value,' + data[i].TASK_ID + ');" class="message-content" type="radio" name="TASK_NOTIFICATION_STATUS_' + data[i].TASK_ID + '" value="1" ' + notificationStatusYes + '> Yes <input onchange="changeNotificationStatus(this.value,' + data[i].TASK_ID + ');" class="message-content" style="margin-left:6px" type="radio" name="TASK_NOTIFICATION_STATUS_' + data[i].TASK_ID + '" value="0" ' + notificationStatusNo + '> No '+
								'</div>'+
							'</div>'+
							'<div class="col-md-3" style="padding-left:10px;">'+anchortag+'</div>' +
						'</div>'+
					'</div>'+
				'</div>');
		*/
		/*
		 taskList.append('<div class="mail-list taskDetailsPersonal" data-task_id="' + data[i].TASK_ID + '">' +
                    '<div class="content" style="width:97%;">'+
						'<div class="row"><div class="col-md-8"><p class="message_text" '+ strstyleDD +'>' + data[i].TASK_TITLE + '</p></div><div class="col-md-4"  id="due_personl_rep" style="text-align:right;">'+anchortag+'</div></div>' + 
                    	'<div class="row" style="padding-top:5px;"><div class="col-md-2" style="vertical-align:middle;"><strong>Status:</strong></div><div class="col-md-4" style="padding:0px"><select class="custom-select browser-default" required="" id="TASK_STATUS" name="TASK_STATUS" onchange="changeStatusPTD(this.value,' + data[i].TASK_ID + ');"><option value="OPEN" ' + openvar + ' >OPEN</option><option value="CLOSED" ' + closevar + '>CLOSED</option><option value="COMPLETED" ' + completevar + '>COMPLETED</option><option value="IN PROGRESS" ' + inprogressvar + '>IN PROGRESS</option></select></div><div class="col-md-6"  id="due_personl_rep" style="text-align:right;">'+notificationButton+'</div></div>'+ 					
                   		'<div class="row" style="margin-top:5px;"></div>'+
					'</div></div>');
			*/		
					taskList.append('<div class="mail-list taskDetailsPersonal" data-task_id="' + data[i].TASK_ID + '">' +
                    '<div class="content" style="width:97%;">'+
						'<div class="row"><div class="col-md-8"><p class="message_text" '+ strstyleDD +'>' + data[i].TASK_TITLE + '</p></div><div class="col-md-4"  id="due_personl_rep" style="text-align:right;">'+anchortag+'</div></div>' + 
                    	'<div class="row"><div class="col-md-9"><p class="message_text">&nbsp;</p></div><div class="col-md-3"  id="due_personl_rep" style="text-align:right;">'+notificationButton+'</div></div>'+ 
                   		'<div class="row" style="margin-top:5px;"><div class="col-md-7">&nbsp;</div><div class="col-md-2"><strong>Status:</strong></div><div class="col-md-3" style="padding:0px"><select class="custom-select browser-default" required="" id="TASK_STATUS" name="TASK_STATUS" onchange="changeStatusPTD(this.value,' + data[i].TASK_ID + ');"><option value="OPEN" ' + openvar + ' >OPEN</option><option value="CLOSED" ' + closevar + '>CLOSED</option><option value="COMPLETED" ' + completevar + '>COMPLETED</option><option value="IN PROGRESS" ' + inprogressvar + '>IN PROGRESS</option></select></div></div>'+
					'</div></div>');


            }
			
        } else {
            taskList.append('<div class="error">' + d.MESSAGE + '</div>')
        }
    }
	
	 function createCCtaskDDNewDue(d) {
        taskList.empty();
       if(d.STATUS !== 'ERROR') {
            var data = JSON.parse(d.DATA);
   taskList.append('<div class="mail-list" style="background: grey;color:white;"><h6 style="margin:0px">Due Today Non Repeated CC Tasks</h6></div>');
            // console.log(data);		
			for (var i = 0; i < data.length; i++) {
			 var duedatae = convertDate(data[i].DUE_DATE);
			 if(duedatae=="1 Jan 1970 5:0 AM"){duedatae="";}
				 if(duedatae=="31 Dec 1969 6:0 PM"){duedatae="";} // new added by rafiq
		//alert(toTimestamp(duedatae));
	 
//console.log(timeSince(new Date(duedatae)));
		    var strstyleDD=' style="color:#ffd80b; font-weight:600; text-transform:capitalize" ';
			var timecheck = timeSince(new Date(duedatae));
			if (timecheck = timecheck.includes("ago"))
			{
				strstyleDD =' style="color:red; font-weight:600; text-transform:capitalize" ';
			}
			var openvar = "";
		  var closevar = "";
		   var completevar = "";
		    var inprogressvar = "";
 switch(data[i].TASK_STATUS)
		 {
			 case "OPEN":
			 		openvar = " selected='selected'";
			 	break;
				 case "CLOSED":
			 		closevar = " selected='selected'";
			 	break;
				 case "COMPLETED":
			 		completevar = " selected='selected'";
			 	break;
				 case "IN PROGRESS":
			 		inprogressvar = " selected='selected'";
			 	break;
		 }
		 var notificationStatusYes="";
		 var notificationStatusNo="";
		 //alert('h '+ data.TASK_NOTIFICATION_STATUS);
		 if(data[i].STATUS==1){notificationStatusYes = " checked"}
		 else{notificationStatusNo = " checked"}
			
			/*
                taskList.append('<div class="mail-list taskDetailsCCtaskDD" data-task_id="' + data[i].TASK_ID + '">' +
                    '<div class="content">' +
                    '<p class="message_text" '+ strstyleDD +'>' + data[i].TASK_TITLE + '</p>' +
					'<p class="message_text">'+timeSince(new Date(duedatae))+'</p>' +
					'<div class="row"><div class="col-md-6" style="padding:0px"><select class="custom-select browser-default" required="" id="TASK_STATUS" name="TASK_STATUS" onchange="changeStatus(this.value,' + data[i].TASK_ID + ');"><option value="OPEN" ' + openvar + ' >OPEN</option><option value="CLOSED" ' + closevar + '>CLOSED</option><option value="COMPLETED" ' + completevar + '>COMPLETED</option><option value="IN PROGRESS" ' + inprogressvar + '>IN PROGRESS</option></select></div><div class="col-md-6">'+
			'<div class="message-content" style="padding-top:6px; color: #46de46;font-weight:600" ><input onchange="changeNotificationStatus(this.value,' + data[i].TASK_ID + ');" class="message-content" type="radio" name="TASK_NOTIFICATION_STATUS_' + data[i].TASK_ID + '" value="1" ' + notificationStatusYes + '> Yes <input onchange="changeNotificationStatus(this.value,' + data[i].TASK_ID + ');" class="message-content" style="margin-left:6px" type="radio" name="TASK_NOTIFICATION_STATUS_' + data[i].TASK_ID + '" value="0" ' + notificationStatusNo + '> No </div>'+
			'</div></div></div>' +
                    '</div>');
				*/	
					
					  taskList.append('<div class="mail-list taskDetailsCCtaskDD" data-task_id="' + data[i].TASK_ID + '">' +
                    '<div class="content" style="width:97%;">'+
						'<div class="row"><div class="col-md-12"><p class="message_text" '+ strstyleDD +'>' + data[i].TASK_TITLE + '</p></div></div>' + 
                    	'<div class="row"><div class="col-md-6"><p class="message_text">'+timeSince(new Date(duedatae))+'</p></div><div class="col-md-6" style="text-align:right;"><p class="message_text"><strong>Status: </strong> '+data[i].TASK_STATUS+'</p></div></div>'+ 
					'</div></div>');
					
					
            }
			
        } else {
            taskList.append('<div class="error">' + d.MESSAGE + '</div>')
        }
    }
	function createCCtaskDDNewRep(d) {
        taskList.empty();
       if(d.STATUS !== 'ERROR') {
            var data = JSON.parse(d.DATA);
   taskList.append('<div class="mail-list" style="background: grey;color:white;"><h6 style="margin:0px">Due Today Repeat Interval CC Tasks</h6></div>');
            // console.log(data);		
			for (var i = 0; i < data.length; i++) {
			 var duedatae = convertDate(data[i].DUE_DATE);
			 if(duedatae=="1 Jan 1970 5:0 AM"){duedatae="";}
				 if(duedatae=="31 Dec 1969 6:0 PM"){duedatae="";} // new added by rafiq
		//alert(toTimestamp(duedatae));
	 
//console.log(timeSince(new Date(duedatae)));
		    var strstyleDD=' style="color:#ffd80b; font-weight:600; text-transform:capitalize" ';
			var timecheck = timeSince(new Date(duedatae));
			if (timecheck = timecheck.includes("ago"))
			{
				strstyleDD =' style="color:red; font-weight:600; text-transform:capitalize" ';
			}
			var openvar = "";
		  var closevar = "";
		   var completevar = "";
		    var inprogressvar = "";
 switch(data[i].TASK_STATUS)
		 {
			 case "OPEN":
			 		openvar = " selected='selected'";
			 	break;
				 case "CLOSED":
			 		closevar = " selected='selected'";
			 	break;
				 case "COMPLETED":
			 		completevar = " selected='selected'";
			 	break;
				 case "IN PROGRESS":
			 		inprogressvar = " selected='selected'";
			 	break;
		 }
		 var notificationStatusYes="";
		 var notificationStatusNo="";
		 //alert('h '+ data.TASK_NOTIFICATION_STATUS);
		 if(data[i].STATUS==1){notificationStatusYes = " checked"}
		 else{notificationStatusNo = " checked"}
		 
                taskList.append('<div class="mail-list taskDetailsCCtaskDD" data-task_id="' + data[i].TASK_ID + '">' +
                    '<div class="content" style="width:97%;">'+
						'<div class="row"><div class="col-md-12"><p class="message_text" '+ strstyleDD +'>' + data[i].TASK_TITLE + '</p></div></div>' + 
                    	'<div class="row"><div class="col-md-6"><p class="message_text">'+timeSince(new Date(duedatae))+'</p></div><div class="col-md-6" style="text-align:right;"><p class="message_text"><strong>Status: </strong> '+data[i].TASK_STATUS+'</p></div></div>'+ 
					'</div></div>');	
		 
            }
			
        } else {
            taskList.append('<div class="error">' + d.MESSAGE + '</div>')
        }
    }
	function createCCtaskNewTodo(d) {
        taskList.empty();
       if(d.STATUS !== 'ERROR') {
            var data = JSON.parse(d.DATA);
   taskList.append('<div class="mail-list" style="background: grey;color:white;"><h6 style="margin:0px">To Do Interval CC Tasks</h6></div>');
            // console.log(data);		
			for (var i = 0; i < data.length; i++) {
			 var duedatae = convertDate(data[i].DUE_DATE);
			 if(duedatae=="1 Jan 1970 5:0 AM"){duedatae="";}
				 if(duedatae=="31 Dec 1969 6:0 PM"){duedatae="";} // new added by rafiq
		//alert(toTimestamp(duedatae));
	 
//console.log(timeSince(new Date(duedatae)));
		    var strstyleDD=' style="color:#ffd80b; font-weight:600; text-transform:capitalize" ';
			var timecheck = timeSince(new Date(duedatae));
			if (timecheck = timecheck.includes("ago"))
			{
				strstyleDD =' style="color:red; font-weight:600; text-transform:capitalize" ';
			}
			var openvar = "";
		  var closevar = "";
		   var completevar = "";
		    var inprogressvar = "";
 switch(data[i].TASK_STATUS)
		 {
			 case "OPEN":
			 		openvar = " selected='selected'";
			 	break;
				 case "CLOSED":
			 		closevar = " selected='selected'";
			 	break;
				 case "COMPLETED":
			 		completevar = " selected='selected'";
			 	break;
				 case "IN PROGRESS":
			 		inprogressvar = " selected='selected'";
			 	break;
		 }
		 var notificationStatusYes="";
		 var notificationStatusNo="";
		 //alert('h '+ data.TASK_NOTIFICATION_STATUS);
		 if(data[i].STATUS==1){notificationStatusYes = " checked"}
		 else{notificationStatusNo = " checked"}
               
			   /*
			    taskList.append('<div class="mail-list taskDetailsCCtaskDD" data-task_id="' + data[i].TASK_ID + '">' +
                    '<div class="content">' +
                    '<p class="message_text" '+ strstyleDD +'>' + data[i].TASK_TITLE + '</p>' +
					'<p class="message_text">'+timeSince(new Date(duedatae))+'</p>' +
                    '<div class="row"><div class="col-md-6" style="padding:0px"><select class="custom-select browser-default" required="" id="TASK_STATUS" name="TASK_STATUS" onchange="changeStatus(this.value,' + data[i].TASK_ID + ');"><option value="OPEN" ' + openvar + ' >OPEN</option><option value="CLOSED" ' + closevar + '>CLOSED</option><option value="COMPLETED" ' + completevar + '>COMPLETED</option><option value="IN PROGRESS" ' + inprogressvar + '>IN PROGRESS</option></select></div><div class="col-md-6">'+
			'<div class="message-content" style="padding-top:6px; color: #46de46;font-weight:600" ><input onchange="changeNotificationStatus(this.value,' + data[i].TASK_ID + ');" class="message-content" type="radio" name="TASK_NOTIFICATION_STATUS_' + data[i].TASK_ID + '" value="1" ' + notificationStatusYes + '> Yes <input onchange="changeNotificationStatus(this.value,' + data[i].TASK_ID + ');" class="message-content" style="margin-left:6px" type="radio" name="TASK_NOTIFICATION_STATUS_' + data[i].TASK_ID + '" value="0" ' + notificationStatusNo + '> No </div>'+
			'</div></div></div>' +
                    '</div>');
					*/
					
					 taskList.append('<div class="mail-list taskDetailsCCtaskDD" data-task_id="' + data[i].TASK_ID + '">' +
                    '<div class="content" style="width:97%;">'+
						'<div class="row"><div class="col-md-12"><p class="message_text" '+ strstyleDD +'>' + data[i].TASK_TITLE + '</p></div></div>' + 
                    	'<div class="row"><div class="col-md-6"><p class="message_text">'+timeSince(new Date(duedatae))+'</p></div><div class="col-md-6" style="text-align:right;"><p class="message_text"><strong>Status: </strong> '+data[i].TASK_STATUS+'</p></div></div>'+ 
					'</div></div>');
            }
			
        } else {
            taskList.append('<div class="error">' + d.MESSAGE + '</div>')
        }
    }
	function createCCtaskNewNoRep(d) {
        taskList.empty();
       if(d.STATUS !== 'ERROR') {
            var data = JSON.parse(d.DATA);
   taskList.append('<div class="mail-list" style="background: grey;color:white;"><h6 style="margin:0px">Non Repeat Interval CC Tasks</h6></div>');
            // console.log(data);		
			for (var i = 0; i < data.length; i++) {
			 var duedatae = convertDate(data[i].DUE_DATE);
		//alert(toTimestamp(duedatae));
	 
//console.log(timeSince(new Date(duedatae)));
		    var strstyleDD=' style="color:#ffd80b; font-weight:600; text-transform:capitalize" ';
			var timecheck = timeSince(new Date(duedatae));
			if (timecheck = timecheck.includes("ago"))
			{
				strstyleDD =' style="color:red; font-weight:600; text-transform:capitalize" ';
			}
			var openvar = "";
		  var closevar = "";
		   var completevar = "";
		    var inprogressvar = "";
 switch(data[i].TASK_STATUS)
		 {
			 case "OPEN":
			 		openvar = " selected='selected'";
			 	break;
				 case "CLOSED":
			 		closevar = " selected='selected'";
			 	break;
				 case "COMPLETED":
			 		completevar = " selected='selected'";
			 	break;
				 case "IN PROGRESS":
			 		inprogressvar = " selected='selected'";
			 	break;
		 }
		 var notificationStatusYes="";
		 var notificationStatusNo="";
		 //alert('h '+ data.TASK_NOTIFICATION_STATUS);
		 if(data[i].STATUS==1){notificationStatusYes = " checked"}
		 else{notificationStatusNo = " checked"}
              
			  /*  taskList.append('<div class="mail-list taskDetailsCCtaskDD" data-task_id="' + data[i].TASK_ID + '">' +
                    '<div class="content">' +
                    '<p class="message_text" '+ strstyleDD +'>' + data[i].TASK_TITLE + '</p>' +
					'<p class="message_text">'+timeSince(new Date(duedatae))+'</p>' +
                    '<div class="row"><div class="col-md-6" style="padding:0px"><select class="custom-select browser-default" required="" id="TASK_STATUS" name="TASK_STATUS" onchange="changeStatus(this.value,' + data[i].TASK_ID + ');"><option value="OPEN" ' + openvar + ' >OPEN</option><option value="CLOSED" ' + closevar + '>CLOSED</option><option value="COMPLETED" ' + completevar + '>COMPLETED</option><option value="IN PROGRESS" ' + inprogressvar + '>IN PROGRESS</option></select></div><div class="col-md-6">'+
			'<div class="message-content" style="padding-top:6px; color: #46de46;font-weight:600" ><input onchange="changeNotificationStatus(this.value,' + data[i].TASK_ID + ');" class="message-content" type="radio" name="TASK_NOTIFICATION_STATUS_' + data[i].TASK_ID + '" value="1" ' + notificationStatusYes + '> Yes <input onchange="changeNotificationStatus(this.value,' + data[i].TASK_ID + ');" class="message-content" style="margin-left:6px" type="radio" name="TASK_NOTIFICATION_STATUS_' + data[i].TASK_ID + '" value="0" ' + notificationStatusNo + '> No </div>'+
			'</div></div></div>' +
                    '</div>');
					*/
					
					 taskList.append('<div class="mail-list taskDetailsCCtaskDD" data-task_id="' + data[i].TASK_ID + '">' +
                    '<div class="content" style="width:97%;">'+
						'<div class="row"><div class="col-md-12"><p class="message_text" '+ strstyleDD +'>' + data[i].TASK_TITLE + '</p></div></div>' + 
                    	'<div class="row"><div class="col-md-6"><p class="message_text">'+timeSince(new Date(duedatae))+'</p></div><div class="col-md-6" style="text-align:right;"><p class="message_text"><strong>Status: </strong> '+data[i].TASK_STATUS+'</p></div></div>'+ 
					'</div></div>');
					
            }
			
        } else {
            taskList.append('<div class="error">' + d.MESSAGE + '</div>')
        }
    }
	function createCCtaskNewRep(d) {
        taskList.empty();
       if(d.STATUS !== 'ERROR') {
            var data = JSON.parse(d.DATA);
   taskList.append('<div class="mail-list" style="background: grey;color:white;"><h6 style="margin:0px">Repeat Interval CC Tasks</h6></div>');
            // console.log(data);		
			for (var i = 0; i < data.length; i++) {
			 var duedatae = convertDate(data[i].DUE_DATE);
			 if(duedatae=="1 Jan 1970 5:0 AM"){duedatae="";}
				 if(duedatae=="31 Dec 1969 6:0 PM"){duedatae="";} // new added by rafiq
		//alert(toTimestamp(duedatae));
	 
//console.log(timeSince(new Date(duedatae)));
		    var strstyleDD=' style="color:#ffd80b; font-weight:600; text-transform:capitalize" ';
			var timecheck = timeSince(new Date(duedatae));
			if (timecheck = timecheck.includes("ago"))
			{
				strstyleDD =' style="color:red; font-weight:600; text-transform:capitalize" ';
			}
			var openvar = "";
		  var closevar = "";
		   var completevar = "";
		    var inprogressvar = "";
 switch(data[i].TASK_STATUS)
		 {
			 case "OPEN":
			 		openvar = " selected='selected'";
			 	break;
				 case "CLOSED":
			 		closevar = " selected='selected'";
			 	break;
				 case "COMPLETED":
			 		completevar = " selected='selected'";
			 	break;
				 case "IN PROGRESS":
			 		inprogressvar = " selected='selected'";
			 	break;
		 }
		 		 var output = "";
		 var repint = data[i].REPEAT_INTERVAL;
		 //if(output == "") output += "Never";
		 var arrIntervals = repint.split(',');
		 var arrayLength = arrIntervals.length;
for (var x = 0; x < arrayLength; x++) {
	switch(arrIntervals[x])
	{
		case '0': 
			output += "Mon, ";
			break;
		case '1': 
			output += "Tue, ";
			break;
		case '2': 
			output += "Wed, ";
			break;
		case '3': 
			output += "Thu, ";
			break;
		case '4': 
			output += "Fri, ";
			break;
		case '5': 
			output += "Sat, ";
			break;
		case '6': 
			output += "Sun, ";
			break;
		case '7': 
			output += "Month";
			break;
	}
   // console.log();
    //Do something
}

if(repint == "0,1,2,3,4,5,6") output = "Every Day";
if(output == "") output = "Never";

		 var notificationStatusYes="";
		 var notificationStatusNo="";
		 //alert('h '+ data.TASK_NOTIFICATION_STATUS);
		 if(data[i].STATUS==1){notificationStatusYes = " checked"}
		 else{notificationStatusNo = " checked"}
               /*
			    taskList.append('<div class="mail-list taskDetailsCCtaskDD" data-task_id="' + data[i].TASK_ID + '">' +
                    '<div class="content">' +
                    '<p class="message_text" '+ strstyleDD +'>' + data[i].TASK_TITLE + '</p>' +
					'<p class="message_text">'+timeSince(new Date(duedatae))+'</p>' +
                    '<div class="row"><div class="col-md-6" style="padding:0px"><select class="custom-select browser-default" required="" id="TASK_STATUS" name="TASK_STATUS" onchange="changeStatus(this.value,' + data[i].TASK_ID + ');"><option value="OPEN" ' + openvar + ' >OPEN</option><option value="CLOSED" ' + closevar + '>CLOSED</option><option value="COMPLETED" ' + completevar + '>COMPLETED</option><option value="IN PROGRESS" ' + inprogressvar + '>IN PROGRESS</option></select></div><div class="col-md-6">'+
			'<div class="message-content" style="padding-top:6px; color: #46de46;font-weight:600" ><input onchange="changeNotificationStatus(this.value,' + data[i].TASK_ID + ');" class="message-content" type="radio" name="TASK_NOTIFICATION_STATUS_' + data[i].TASK_ID + '" value="1" ' + notificationStatusYes + '> Yes <input onchange="changeNotificationStatus(this.value,' + data[i].TASK_ID + ');" class="message-content" style="margin-left:6px" type="radio" name="TASK_NOTIFICATION_STATUS_' + data[i].TASK_ID + '" value="0" ' + notificationStatusNo + '> No </div>'+
			'</div></div></div>' +
                    '</div>');
					*/
					 taskList.append('<div class="mail-list taskDetailsCCtaskDD" data-task_id="' + data[i].TASK_ID + '">' +
                    '<div class="content" style="width:97%;">'+
						'<div class="row"><div class="col-md-12"><p class="message_text" '+ strstyleDD +'>' + data[i].TASK_TITLE + '</p></div></div>' + 
                    	'<div class="row"><div class="col-md-6"><p class="message_text">'+timeSince(new Date(duedatae))+'</p></div><div class="col-md-6" style="text-align:right;"><p class="message_text"><strong>Status: </strong> '+data[i].TASK_STATUS+'</p></div></div>'+ 
						'<div class="row"><div class="col-md-12"><p class="message_text"><strong>Repeat: </strong> '+output+'</p></div></div>'+ 
					'</div></div>');
					
					
            }
			
        } else {
            taskList.append('<div class="error">' + d.MESSAGE + '</div>')
        }
    }
	function createAssignMeTasksDD(d) {
        taskList.empty();
       if(d.STATUS !== 'ERROR') {
            var data = JSON.parse(d.DATA);
   taskList.append('<div class="mail-list" style="background: grey;color:white;"><h6 style="margin:0px">Due Today Assign Me Tasks</h6></div>');
            // console.log(data);		
			for (var i = 0; i < data.length; i++) {
			 var duedatae = convertDate(data[i].DUE_DATE);
		//alert(toTimestamp(duedatae));
	 
//console.log(timeSince(new Date(duedatae)));
		    var strstyleDD=' style="color:#ffd80b; font-weight:600; text-transform:capitalize" ';
			var timecheck = timeSince(new Date(duedatae));
			if (timecheck = timecheck.includes("ago"))
			{
				strstyleDD =' style="color:red; font-weight:600; text-transform:capitalize" ';
			}
			
                taskList.append('<div class="mail-list taskDetailsDDassignMe" data-task_id="' + data[i].TASK_ID + '">' +
                    '<div class="content">' +
                    '<p class="message_text" '+ strstyleDD +'>' + data[i].TASK_TITLE + '</p>' +
					'<p class="message_text">'+timeSince(new Date(duedatae))+'</p>' +
					'<p class="message_text">By: '+data[i].FULL_NAME+'</p>' +
                    '</div>' +
                    '</div>');
            }
			
        } else {
            taskList.append('<div class="error">' + d.MESSAGE + '</div>')
        }
    }
	 function createAssignOthersDD(d) {
        taskList.empty();
       if(d.STATUS !== 'ERROR') {
            var data = JSON.parse(d.DATA);
   taskList.append('<div class="mail-list" style="background: grey;color:white;"><h6 style="margin:0px">Due Today Assign Others Tasks</h6></div>');
            // console.log(data);		
			for (var i = 0; i < data.length; i++) {
			 var duedatae = convertDate(data[i].DUE_DATE);
			  if(duedatae=="1 Jan 1970 5:0 AM"){duedatae="";}
				 if(duedatae=="31 Dec 1969 6:0 PM"){duedatae="";} // new added by rafiq
		//alert(toTimestamp(duedatae));
	 
//console.log(timeSince(new Date(duedatae)));
		    var strstyleDD=' style="color:#ffd80b; font-weight:600; text-transform:capitalize" ';
			var timecheck = timeSince(new Date(duedatae));
			if (timecheck = timecheck.includes("ago"))
			{
				strstyleDD =' style="color:red; font-weight:600; text-transform:capitalize" ';
			}
			
                taskList.append('<div class="mail-list taskDetails" data-task_id="' + data[i].TASK_ID + '">' +
                    '<div class="content">' +
                    '<p class="message_text" '+ strstyleDD +'>' + data[i].TASK_TITLE + '</p>' +
					'<p class="message_text">'+timeSince(new Date(duedatae))+'</p>' +
					'<p class="message_text">To: '+data[i].FULL_NAME+'</p>' +
                    '</div>' +
                    '</div>');
            }
			
        } else {
            taskList.append('<div class="error">' + d.MESSAGE + '</div>')
        }
    }
	
	 function createAssignOthersNew(d) {
        taskList.empty();
        if(d.STATUS !== 'ERROR') {
            var data = JSON.parse(d.DATA);
   taskList.append('<div class="mail-list" style="background: grey;color:white;"><h6 style="margin:0px">Assigned to Others Tasks</h6></div>');
            // console.log(data);
			for (var i = 0; i < data.length; i++) {
				taskListRepeat="";
		        taskListNoRepeat="";
				
				var output = "";
		 var repint = data[i].REPEAT_INTERVAL;
		 //if(output == "") output += "Never";
		 var arrIntervals = repint.split(',');
		 var arrayLength = arrIntervals.length;
for (var x = 0; x < arrayLength; x++) {
	switch(arrIntervals[x])
	{
		case '0': 
			output += "Mon, ";
			break;
		case '1': 
			output += "Tue, ";
			break;
		case '2': 
			output += "Wed, ";
			break;
		case '3': 
			output += "Thu, ";
			break;
		case '4': 
			output += "Fri, ";
			break;
		case '5': 
			output += "Sat, ";
			break;
		case '6': 
			output += "Sun, ";
			break;
		case '7': 
			output += "Month";
			break;
	}
   // console.log();
    //Do something
}

if(repint == "0,1,2,3,4,5,6") output = "Every Day";
if(output == "") output = "Never";

                 var duedatae = convertDate(data[i].DUE_DATE)
				 if(duedatae=="1 Jan 1970 5:0 AM"){duedatae="";}
				 if(duedatae=="31 Dec 1969 6:0 PM"){duedatae="";} // new added by rafiq
				 var currentdate = new Date();
var numberOfDaysToAdd = 2;
currentdate.setDate(currentdate.getDate() + numberOfDaysToAdd); 
var date1 = new Date(currentdate);
var date2 = new Date(duedatae);
				//var strstyle=' style="color:red; text-transform:capitalize" ';
var strstyle='';
if(date1 - date2 < 0 ){strstyle=' style="color:black; font-weight:600; text-transform:capitalize" ';}	
if(date1 - date2 > 0 ){strstyle=' style="color:red; font-weight:600; text-transform:capitalize" '; 
//alert((date1 - date2)+'=='+currentdate+'-'+duedatae);
//alert(datediff(date1, date2)+" = "+date2+"-"+duedatae);

}
if(date1 - date2 > 0 && ((datediff(date1, date2) == -1)||(datediff(date1, date2) == -2)) ){strstyle=' style="color:#ffd80b; font-weight:600; text-transform:capitalize" ';}
if (data[i].TASK_STATUS=="COMPLETED")
{strstyle=' style="color:#2cc62c; font-weight:600; text-transform:capitalize" ';} //green
if(output == "" || duedatae == ""){strstyle=' style="color:black; font-weight:600; text-transform:capitalize" ';}

			 //alert(duedatae);
			if(repint==""){
				//alert('taskListNoRepeat');
                taskListNoRepeat+='<div class="mail-list taskDetails" data-task_id="' + data[i].TASK_ID + '">' +
                    '<div class="content">' +
                    '<p class="message_text" '+strstyle+'>' + data[i].TASK_TITLE + '</p>' +
					'<p class="message_text" style="font-size:12px">' + output + '</p>' +
					'<p class="message_text" style="font-size:12px">' + duedatae + '</p>' +
                    '</div>' +
                    '</div>';
				taskList.append(taskListNoRepeat);
			}
            }
			 taskList.append('<div class="mail-list" style="background: grey;color:white;"><h6 style="margin:0px">Repeat Interval Tasks</h6></div>');
			for (var i = 0; i < data.length; i++) {
				taskListRepeat="";
		        taskListNoRepeat="";
				
				var duedatae = convertDate(data[i].DUE_DATE)
				 if(duedatae=="1 Jan 1970 5:0 AM"){duedatae="";}
				 if(duedatae=="31 Dec 1969 6:0 PM"){duedatae="";} // new added by rafiq
				 var currentdate = new Date();
var numberOfDaysToAdd = 2;
currentdate.setDate(currentdate.getDate() + numberOfDaysToAdd); 
var date1 = new Date(currentdate);
var date2 = new Date(duedatae);
				//var strstyle=' style="color:red; text-transform:capitalize" ';
var strstyle='';
if(date1 - date2 < 0 ){strstyle=' style="color:black; font-weight:600; text-transform:capitalize" ';}	
if(date1 - date2 > 0 ){strstyle=' style="color:red; font-weight:600; text-transform:capitalize" '; 
//alert((date1 - date2)+'=='+currentdate+'-'+duedatae);
//alert(datediff(date1, date2)+" = "+date2+"-"+duedatae);

}
if(date1 - date2 > 0 && ((datediff(date1, date2) == -1)||(datediff(date1, date2) == -2)) ){strstyle=' style="color:#ffd80b; font-weight:600; text-transform:capitalize" ';}
if (data[i].TASK_STATUS=="COMPLETED")
{strstyle=' style="color:#2cc62c; font-weight:600; text-transform:capitalize" ';} //green
if(output == "" || duedatae == ""){strstyle=' style="color:black; font-weight:600; text-transform:capitalize" ';}				
			var output2 = "";
		 var repint = data[i].REPEAT_INTERVAL;
		 //if(output == "") output = "Never";
		 var arrIntervals = repint.split(',');
		 var arrayLength = arrIntervals.length;
for (var x = 0; x < arrayLength; x++) {
	switch(arrIntervals[x])
	{
		case '0': 
			output2 += "Mon, ";
			break;
		case '1': 
			output2 += "Tue, ";
			break;
		case '2': 
			output2 += "Wed, ";
			break;
		case '3': 
			output2 += "Thu, ";
			break;
		case '4': 
			output2 += "Fri, ";
			break;
		case '5': 
			output2 += "Sat, ";
			break;
		case '6': 
			output2 += "Sun, ";
			break;
		case '7': 
			output2 += "Month";
			break;
	}
   // console.log();
    //Do something
}

if(repint == "0,1,2,3,4,5,6") output2 = "Every Day";	
if(output2 == "") output2 = "Never";				
				
			if(!repint==""){
				//alert('taskListNoRepeat');
                taskListNoRepeat+='<div class="mail-list taskDetails" data-task_id="' + data[i].TASK_ID + '">' +
                    '<div class="content">' +
                    '<p class="message_text" '+strstyle+'>' + data[i].TASK_TITLE + '</p>' +
					'<p class="message_text" style="font-size:12px">' + output2 + '</p>' +
					'<p class="message_text" style="font-size:12px">' + convertDate(data[i].DUE_DATE) + '</p>' +
                    '</div>' +
                    '</div>';
				taskList.append(taskListNoRepeat);
			}
			
            }
			
        } else {
            taskList.append('<div class="error">' + d.MESSAGE + '</div>')
        }
    }
	
	 function createAssignOtherNewDue(d) {
        taskList.empty();
        if(d.STATUS !== 'ERROR') {
            var data = JSON.parse(d.DATA);
   taskList.append('<div class="mail-list" style="background: grey;color:white;"><h6 style="margin:0px">Due Today Non Repeated Assigned to Others Tasks</h6></div>');
             console.log(data);
			for (var i = 0; i < data.length; i++) {
				taskListRepeat="";
		        taskListNoRepeat="";
				
				var output = "";
				var output2 = "";
		 var repint = data[i].REPEAT_INTERVAL;
		 //if(output == "") output += "Never";
		 var arrIntervals = repint.split(',');
		 var arrayLength = arrIntervals.length;
for (var x = 0; x < arrayLength; x++) {
	switch(arrIntervals[x])
	{
		case '0': 
			output2 += "Mon, ";
			break;
		case '1': 
			output2 += "Tue, ";
			break;
		case '2': 
			output2 += "Wed, ";
			break;
		case '3': 
			output2 += "Thu, ";
			break;
		case '4': 
			output2 += "Fri, ";
			break;
		case '5': 
			output2 += "Sat, ";
			break;
		case '6': 
			output2 += "Sun, ";
			break;
		case '7': 
			output2 += "Month";
			break;
	}
   // console.log();
    //Do something
}

if(repint == "0,1,2,3,4,5,6") output = "Every Day";
if(output == "") output = "Never";

                 var duedatae = convertDate(data[i].DUE_DATE)
				 if(duedatae=="1 Jan 1970 5:0 AM"){duedatae="";}
				 if(duedatae=="31 Dec 1969 6:0 PM"){duedatae="";} // new added by rafiq
				 var currentdate = new Date();
var numberOfDaysToAdd = 2;
currentdate.setDate(currentdate.getDate() + numberOfDaysToAdd); 
var date1 = new Date(currentdate);
var date2 = new Date(duedatae);
				//var strstyle=' style="color:red; text-transform:capitalize" ';
var strstyle='';
if(date1 - date2 < 0 ){strstyle=' style="color:black; font-weight:600; text-transform:capitalize" ';}	
if(date1 - date2 > 0 ){strstyle=' style="color:red; font-weight:600; text-transform:capitalize" '; 
//alert((date1 - date2)+'=='+currentdate+'-'+duedatae);
//alert(datediff(date1, date2)+" = "+date2+"-"+duedatae);

}
if(date1 - date2 > 0 && ((datediff(date1, date2) == -1)||(datediff(date1, date2) == -2)) ){strstyle=' style="color:#ffd80b; font-weight:600; text-transform:capitalize" ';}
if (data[i].TASK_STATUS=="COMPLETED")
{strstyle=' style="color:#2cc62c; font-weight:600; text-transform:capitalize" ';} //green
if(output == "" || duedatae == ""){strstyle=' style="color:black; font-weight:600; text-transform:capitalize" ';}
//aa
var anchortag = '<a href="javascript:changeStatusDD(\'COMPLETED\','+data[i].TASK_ID+');" style="font-size:12px;"><img src="assets/images/mark-completed.gif" id="markascompleted'+data[i].TASK_ID+'" /></a>';								
if(data[i].TASK_STATUS == 'COMPLETED')
{				
    anchortag = '<a href="#" style="font-size:12px;"><img src="assets/images/completed-task.gif" id="completedtask"/></a>';
}


			 //alert(duedatae);
			if(repint==""){
				//alert('taskListNoRepeat');
                taskListNoRepeat+='<div class="mail-list taskDetailsAssignOthers" data-task_id="' + data[i].TASK_ID + '">' +
                    '<div class="content">' +
					'<div class="row"><div class="col-md-8"><p class="message_text" '+ strstyle +'>' + data[i].TASK_TITLE + '</p></div><div class="col-md-4"  style="text-align:right;padding:0px; margin:0px;">'+anchortag+'</div></div>' + 
					'<div class="row"><div class="col-md-12"><p class="message_text" style="font-size:12px">' + output + '</p></div></div>' +
					'<div class="row"><div class="col-md-4"><p class="message_text" style="font-size:12px">' + duedatae + '</p></div><div class="col-md-4"><span style="font-size:12px; color:#3b0edb">Send/View Messages(0)</span></div><div class="col-md-4" style="text-align:right;"><p class="message_text" style="font-size:12px"><strong>Status</strong>: ' + data[i].TASK_STATUS + '</p></div></div>' +
					'</div></div>';
				taskList.append(taskListNoRepeat);
					
			}
            }
			
			
        } else {
            taskList.append('<div class="error">' + d.MESSAGE + '</div>')
        }
    }
function createAssignOtherNewTask(d) 
{
	taskList.empty();
    if(d.STATUS !== 'ERROR') 
	{
    	var data = JSON.parse(d.DATA);
		taskList.append('<div class="mail-list" style="background: grey;color:white;"><h6 style="margin:0px">To Do Assigned to Others Tasks</h6></div>');
		for (var i = 0; i < data.length; i++) 
		{
			var taskListRepeat="";
		    var taskListNoRepeat="";
			var output = "";
			var output2 = "";
		 	var repint = data[i].REPEAT_INTERVAL;
		 	var arrIntervals = repint.split(',');
		 	var arrayLength = arrIntervals.length;
			for (var x = 0; x < arrayLength; x++) 
			{
				switch(arrIntervals[x])
				{
					case '0': 
						output2 += "Mon, ";
						break;
					case '1': 
						output2 += "Tue, ";
						break;
					case '2': 
						output2 += "Wed, ";
						break;
					case '3': 
						output2 += "Thu, ";
						break;
					case '4': 
						output2 += "Fri, ";
						break;
					case '5': 
						output2 += "Sat, ";
						break;
					case '6': 
						output2 += "Sun, ";
						break;
					case '7': 
						output2 += "Month";
						break;
				}
			}			
			if(repint == "0,1,2,3,4,5,6") { output = "Every Day"; }
			if(output == "") { output = "Never"; }
			var duedatae = convertDate(data[i].DUE_DATE);
			if(duedatae=="1 Jan 1970 5:0 AM"){duedatae="";}
			if(duedatae=="31 Dec 1969 6:0 PM"){duedatae="";} // new added by rafiq
			var currentdate = new Date();
			var numberOfDaysToAdd = 2;
			currentdate.setDate(currentdate.getDate() + numberOfDaysToAdd); 
			var date1 = new Date(currentdate);
			var date2 = new Date(duedatae);
			var strstyle='';
			if(date1 - date2 < 0 ){strstyle=' style="color:black; font-weight:600; text-transform:capitalize" ';}	
			if(date1 - date2 > 0 ){strstyle=' style="color:red; font-weight:600; text-transform:capitalize" '; }
			if(date1 - date2 > 0 && ((datediff(date1, date2) == -1)||(datediff(date1, date2) == -2)) ){strstyle=' style="color:#ffd80b; font-weight:600; text-transform:capitalize" ';}
			if (data[i].TASK_STATUS=="COMPLETED")
			{
				strstyle=' style="color:#2cc62c; font-weight:600; text-transform:capitalize" ';
			} //green
			if(output == "" || duedatae == ""){strstyle=' style="color:black; font-weight:600; text-transform:capitalize" ';}
			console.log('repint:'+repint+'  and  duedatae:'+duedatae);
			
			var anchortag = '<a href="javascript:changeStatusAssignOthersTD(\'COMPLETED\','+data[i].TASK_ID+',' + data[i].ASSIGNED_ID + ');" style="font-size:12px;"><img src="assets/images/mark-completed.gif" id="markascompleted'+data[i].TASK_ID+'" /></a>';								
if(data[i].TASK_STATUS == 'COMPLETED')
{				
    anchortag = '<a href="#" style="font-size:12px;"><img src="assets/images/completed-task.gif" id="completedtask"/></a>';
}
			
			if(repint=="" && duedatae=="")
			{
				/*
				taskListNoRepeat +='<div class="mail-list taskDetailsAssignOthers" data-task_id="' + data[i].TASK_ID + '">' +
                    '<div class="content">' +
                    '<p class="message_text" '+strstyle+'>' + data[i].TASK_TITLE + '</p>' +
					'<p class="message_text" style="font-size:12px">' + output + '</p>' +
					'<p class="message_text" style="font-size:12px">' + duedatae + '</p>' +
					'</div>' +
                    '</div>';
					
					//alert(taskListNoRepeat);
				taskList.append(taskListNoRepeat);
				*/
				 taskListNoRepeat+='<div class="mail-list taskDetailsAssignOthers" data-task_id="' + data[i].TASK_ID + '">' +
                    '<div class="content">' +
					'<div class="row"><div class="col-md-8"><p class="message_text" '+ strstyle +'>' + data[i].TASK_TITLE + '</p></div><div class="col-md-4"  style="text-align:right;padding:0px; margin:0px;">'+anchortag+'</div></div>' + 
					'<div class="row"><div class="col-md-12"><p class="message_text" style="font-size:12px">' + output + '</p></div></div>' +
					'<div class="row"><div class="col-md-6"><p class="message_text" style="font-size:12px">' + duedatae + '</p></div><div class="col-md-6" style="text-align:right;"><p class="message_text" style="font-size:12px"><strong>Status</strong>: ' + data[i].TASK_STATUS + '</p></div></div>' +
					'</div></div>';
				taskList.append(taskListNoRepeat);

			}
		}
	} 
	else 
	{
          taskList.append('<div class="error">' + d.MESSAGE + '</div>')
    }
}
function createAssignOtherNewTaskDue(d) {
        taskList.empty();
        if(d.STATUS !== 'ERROR') {
            var data = JSON.parse(d.DATA);
   taskList.append('<div class="mail-list" style="background: grey;color:white;"><h6 style="margin:0px">Non Repeated Assigned to Others Tasks</h6></div>');
             console.log(data);
			for (var i = 0; i < data.length; i++) {
				taskListRepeat="";
		        taskListNoRepeat="";
				
				var output = "";
				var output2 = "";
		 var repint = data[i].REPEAT_INTERVAL;
		 //if(output == "") output += "Never";
		 var arrIntervals = repint.split(',');
		 var arrayLength = arrIntervals.length;
for (var x = 0; x < arrayLength; x++) {
	switch(arrIntervals[x])
	{
		case '0': 
			output2 += "Mon, ";
			break;
		case '1': 
			output2 += "Tue, ";
			break;
		case '2': 
			output2 += "Wed, ";
			break;
		case '3': 
			output2 += "Thu, ";
			break;
		case '4': 
			output2 += "Fri, ";
			break;
		case '5': 
			output2 += "Sat, ";
			break;
		case '6': 
			output2 += "Sun, ";
			break;
		case '7': 
			output2 += "Month";
			break;
	}
   // console.log();
    //Do something
}

if(repint == "0,1,2,3,4,5,6") output = "Every Day";
if(output == "") output = "Never";

                 var duedatae = convertDate(data[i].DUE_DATE)
				 if(duedatae=="1 Jan 1970 5:0 AM"){duedatae="";}
				 if(duedatae=="31 Dec 1969 6:0 PM"){duedatae="";} // new added by rafiq
				 var currentdate = new Date();
var numberOfDaysToAdd = 2;
currentdate.setDate(currentdate.getDate() + numberOfDaysToAdd); 
var date1 = new Date(currentdate);
var date2 = new Date(duedatae);
				//var strstyle=' style="color:red; text-transform:capitalize" ';
var strstyle='';
if(date1 - date2 < 0 ){strstyle=' style="color:black; font-weight:600; text-transform:capitalize" ';}	
if(date1 - date2 > 0 ){strstyle=' style="color:red; font-weight:600; text-transform:capitalize" '; 
//alert((date1 - date2)+'=='+currentdate+'-'+duedatae);
//alert(datediff(date1, date2)+" = "+date2+"-"+duedatae);

}
if(date1 - date2 > 0 && ((datediff(date1, date2) == -1)||(datediff(date1, date2) == -2)) ){strstyle=' style="color:#ffd80b; font-weight:600; text-transform:capitalize" ';}
if (data[i].TASK_STATUS=="COMPLETED")
{strstyle=' style="color:#2cc62c; font-weight:600; text-transform:capitalize" ';} //green
if(output == "" || duedatae == ""){strstyle=' style="color:black; font-weight:600; text-transform:capitalize" ';}
var anchortag = '<a href="javascript:changeStatusAssignOthersNR(\'COMPLETED\','+data[i].TASK_ID+',' + data[i].ASSIGNED_ID + ');" style="font-size:12px;"><img src="assets/images/mark-completed.gif" id="markascompleted'+data[i].TASK_ID+'" /></a>';								
if(data[i].TASK_STATUS == 'COMPLETED')
{				
    anchortag = '<a href="#" style="font-size:12px;"><img src="assets/images/completed-task.gif" id="completedtask"/></a>';
}
//console.log(data[i]);
			 //alert(duedatae);
			if(repint=="" && duedatae !=0){
				//alert('taskListNoRepeat');
				/*
                taskListNoRepeat+='<div class="mail-list taskDetailsAssignOthers" data-task_id="' + data[i].TASK_ID + '">' +
                    '<div class="content">' +
                    '<p class="message_text" '+strstyle+'>' + data[i].TASK_TITLE + '</p>' +
					'<p class="message_text" style="font-size:12px">' + output + '</p>' +
					'<p class="message_text" style="font-size:12px">' + duedatae + '</p>' +
					'</div>' +
                    '</div>';
				taskList.append(taskListNoRepeat);
				*/
				
				 taskListNoRepeat+='<div class="mail-list taskDetailsAssignOthers" data-task_id="' + data[i].TASK_ID + '">' +
                    '<div class="content">' +
					'<div class="row"><div class="col-md-8"><p class="message_text" '+ strstyle +'>' + data[i].TASK_TITLE + '</p></div><div class="col-md-4"  style="text-align:right;padding:0px; margin:0px;">'+anchortag+'</div></div>' + 
					'<div class="row"><div class="col-md-12"><p class="message_text" style="font-size:12px">' + output + '</p></div></div>' +
					'<div class="row"><div class="col-md-6"><p class="message_text" style="font-size:12px">' + duedatae + '</p></div><div class="col-md-6" style="text-align:right;"><p class="message_text" style="font-size:12px"><strong>Status</strong>: ' + data[i].TASK_STATUS + '</p></div></div>' +
					'</div></div>';
				taskList.append(taskListNoRepeat);
				
			}
            }
			
			
        } else {
            taskList.append('<div class="error">' + d.MESSAGE + '</div>')
        }
    }
	 function createAssignOtherNewRep(d) {
        taskList.empty();
        if(d.STATUS !== 'ERROR') {
            var data = JSON.parse(d.DATA);
   
            // console.log(data);
			for (var i = 0; i < data.length; i++) {
				taskListRepeat="";
		        taskListNoRepeat="";
				
				var output = "";
		 var repint = data[i].REPEAT_INTERVAL;
		 //if(output == "") output += "Never";
		 var arrIntervals = repint.split(',');
		 var arrayLength = arrIntervals.length;
for (var x = 0; x < arrayLength; x++) {
	switch(arrIntervals[x])
	{
		case '0': 
			output2 += "Mon, ";
			break;
		case '1': 
			output2 += "Tue, ";
			break;
		case '2': 
			output2 += "Wed, ";
			break;
		case '3': 
			output2 += "Thu, ";
			break;
		case '4': 
			output2 += "Fri, ";
			break;
		case '5': 
			output2 += "Sat, ";
			break;
		case '6': 
			output2 += "Sun, ";
			break;
		case '7': 
			output2 += "Month";
			break;
	}
   // console.log();
    //Do something
}

		
if(repint == "0,1,2,3,4,5,6") output = "Every Day";
if(output == "") output = "Never";

                 var duedatae = convertDate(data[i].DUE_DATE)
				 if(duedatae=="1 Jan 1970 5:0 AM"){duedatae="";}
				 if(duedatae=="31 Dec 1969 6:0 PM"){duedatae="";} // new added by rafiq
				 var currentdate = new Date();
var numberOfDaysToAdd = 2;
currentdate.setDate(currentdate.getDate() + numberOfDaysToAdd); 
var date1 = new Date(currentdate);
var date2 = new Date(duedatae);
				//var strstyle=' style="color:red; text-transform:capitalize" ';
var strstyle='';
if(date1 - date2 < 0 ){strstyle=' style="color:black; font-weight:600; text-transform:capitalize" ';}	
if(date1 - date2 > 0 ){strstyle=' style="color:red; font-weight:600; text-transform:capitalize" '; 
//alert((date1 - date2)+'=='+currentdate+'-'+duedatae);
//alert(datediff(date1, date2)+" = "+date2+"-"+duedatae);

}
if(date1 - date2 > 0 && ((datediff(date1, date2) == -1)||(datediff(date1, date2) == -2)) ){strstyle=' style="color:#ffd80b; font-weight:600; text-transform:capitalize" ';}
if (data[i].TASK_STATUS=="COMPLETED")
{strstyle=' style="color:#2cc62c; font-weight:600; text-transform:capitalize" ';} //green
if(output == "" || duedatae == ""){strstyle=' style="color:black; font-weight:600; text-transform:capitalize" ';}

			 //alert(duedatae);
			
            }
			 taskList.append('<div class="mail-list" style="background: grey;color:white;"><h6 style="margin:0px">Repeat Interval Assign Other Tasks</h6></div>');
			for (var i = 0; i < data.length; i++) {
				taskListRepeat="";
		        taskListNoRepeat="";
				
				var duedatae = convertDate(data[i].DUE_DATE)
				 if(duedatae=="1 Jan 1970 5:0 AM"){duedatae="";}
				 if(duedatae=="31 Dec 1969 6:0 PM"){duedatae="";} // new added by rafiq
				 var currentdate = new Date();
var numberOfDaysToAdd = 2;
currentdate.setDate(currentdate.getDate() + numberOfDaysToAdd); 
var date1 = new Date(currentdate);
var date2 = new Date(duedatae);
				//var strstyle=' style="color:red; text-transform:capitalize" ';
var strstyle='';
if(date1 - date2 < 0 ){strstyle=' style="color:black; font-weight:600; text-transform:capitalize" ';}	
if(date1 - date2 > 0 ){strstyle=' style="color:red; font-weight:600; text-transform:capitalize" '; 
//alert((date1 - date2)+'=='+currentdate+'-'+duedatae);
//alert(datediff(date1, date2)+" = "+date2+"-"+duedatae);

}
if(date1 - date2 > 0 && ((datediff(date1, date2) == -1)||(datediff(date1, date2) == -2)) ){strstyle=' style="color:#ffd80b; font-weight:600; text-transform:capitalize" ';}
if (data[i].TASK_STATUS=="COMPLETED")
{strstyle=' style="color:#2cc62c; font-weight:600; text-transform:capitalize" ';} //green
if(output == "" || duedatae == ""){strstyle=' style="color:black; font-weight:600; text-transform:capitalize" ';}				
			var output2 = "";
		 var repint = data[i].REPEAT_INTERVAL;
		 //if(output == "") output = "Never";
		 var arrIntervals = repint.split(',');
		 var arrayLength = arrIntervals.length;
for (var x = 0; x < arrayLength; x++) {
	switch(arrIntervals[x])
	{
		case '0': 
			output2 += "Mon, ";
			break;
		case '1': 
			output2 += "Tue, ";
			break;
		case '2': 
			output2 += "Wed, ";
			break;
		case '3': 
			output2 += "Thu, ";
			break;
		case '4': 
			output2 += "Fri, ";
			break;
		case '5': 
			output2 += "Sat, ";
			break;
		case '6': 
			output2 += "Sun, ";
			break;
		case '7': 
			output2 += "Month";
			break;
	}
   // console.log();
    //Do something
}

if(repint == "0,1,2,3,4,5,6") output2 = "Every Day";	
if(output2 == "") output2 = "Never";				
var anchortag = '<a href="javascript:changeStatusDD(\'COMPLETED\','+data[i].TASK_ID+');" style="font-size:12px;"><img src="assets/images/mark-completed.gif" id="markascompleted'+data[i].TASK_ID+'" /></a>';								
if(data[i].TASK_STATUS == 'COMPLETED')
{				
    anchortag = '<a href="#" style="font-size:12px;"><img src="assets/images/completed-task.gif" id="completedtask"/></a>';
}
				
			if(!repint==""){
				//alert('taskListNoRepeat');
               
			   /* taskListNoRepeat+='<div class="mail-list taskDetailsAssignOthers" data-task_id="' + data[i].TASK_ID + '">' +
                    '<div class="content">' +
                    '<p class="message_text" '+strstyle+'>' + data[i].TASK_TITLE + '</p>' +
					'<p class="message_text" style="font-size:12px">' + output2 + '</p>' +
					'<p class="message_text" style="font-size:12px">' + convertDate(data[i].DUE_DATE) + '</p>' +
                   '</div>' +
                    '</div>';
				taskList.append(taskListNoRepeat);
				*/
				
				 taskListNoRepeat+='<div class="mail-list taskDetailsAssignOthers" data-task_id="' + data[i].TASK_ID + '">' +
                    '<div class="content">' +
					'<div class="row"><div class="col-md-8"><p class="message_text" '+ strstyle +'>' + data[i].TASK_TITLE + '</p></div><div class="col-md-4"  style="text-align:right;padding:0px; margin:0px;">'+anchortag+'</div></div>' + 
					'<div class="row"><div class="col-md-12"><p class="message_text" style="font-size:12px">' + output2 + '</p></div></div>' +
					'<div class="row"><div class="col-md-6"><p class="message_text" style="font-size:12px">' + duedatae + '</p></div><div class="col-md-6" style="text-align:right;"><p class="message_text" style="font-size:12px"><strong>Status</strong>: ' + data[i].TASK_STATUS + '</p></div></div>' +
					'</div></div>';
				taskList.append(taskListNoRepeat);
				
				
			}
			
            }
			
        } else {
            taskList.append('<div class="error">' + d.MESSAGE + '</div>')
        }
    }
	function createAssignOtherNewTaskRep(d) {
        taskList.empty();
        if(d.STATUS !== 'ERROR') {
            var data = JSON.parse(d.DATA);
   
            // console.log(data);
			for (var i = 0; i < data.length; i++) {
				taskListRepeat="";
		        taskListNoRepeat="";
				
				var output = "";
		 var repint = data[i].REPEAT_INTERVAL;
		 //if(output == "") output += "Never";
		 var arrIntervals = repint.split(',');
		 var arrayLength = arrIntervals.length;
for (var x = 0; x < arrayLength; x++) {
	switch(arrIntervals[x])
	{
		case '0': 
			output2 += "Mon, ";
			break;
		case '1': 
			output2 += "Tue, ";
			break;
		case '2': 
			output2 += "Wed, ";
			break;
		case '3': 
			output2 += "Thu, ";
			break;
		case '4': 
			output2 += "Fri, ";
			break;
		case '5': 
			output2 += "Sat, ";
			break;
		case '6': 
			output2 += "Sun, ";
			break;
		case '7': 
			output2 += "Month";
			break;
	}
   // console.log();
    //Do something
}

		
if(repint == "0,1,2,3,4,5,6") output = "Every Day";
if(output == "") output = "Never";

                 var duedatae = convertDate(data[i].DUE_DATE)
				 if(duedatae=="1 Jan 1970 5:0 AM"){duedatae="";}
				 if(duedatae=="31 Dec 1969 6:0 PM"){duedatae="";} // new added by rafiq
				 var currentdate = new Date();
var numberOfDaysToAdd = 2;
currentdate.setDate(currentdate.getDate() + numberOfDaysToAdd); 
var date1 = new Date(currentdate);
var date2 = new Date(duedatae);
				//var strstyle=' style="color:red; text-transform:capitalize" ';
var strstyle='';
if(date1 - date2 < 0 ){strstyle=' style="color:black; font-weight:600; text-transform:capitalize" ';}	
if(date1 - date2 > 0 ){strstyle=' style="color:red; font-weight:600; text-transform:capitalize" '; 
//alert((date1 - date2)+'=='+currentdate+'-'+duedatae);
//alert(datediff(date1, date2)+" = "+date2+"-"+duedatae);

}
if(date1 - date2 > 0 && ((datediff(date1, date2) == -1)||(datediff(date1, date2) == -2)) ){strstyle=' style="color:#ffd80b; font-weight:600; text-transform:capitalize" ';}
if (data[i].TASK_STATUS=="COMPLETED")
{strstyle=' style="color:#2cc62c; font-weight:600; text-transform:capitalize" ';} //green
if(output == "" || duedatae == ""){strstyle=' style="color:black; font-weight:600; text-transform:capitalize" ';}

			 //alert(duedatae);
			
            }
			 taskList.append('<div class="mail-list" style="background: grey;color:white;"><h6 style="margin:0px">Repeat Interval Assign Other Tasks</h6></div>');
			for (var i = 0; i < data.length; i++) {
				taskListRepeat="";
		        taskListNoRepeat="";
				
				var duedatae = convertDate(data[i].DUE_DATE)
				 if(duedatae=="1 Jan 1970 5:0 AM"){duedatae="";}
				 if(duedatae=="31 Dec 1969 6:0 PM"){duedatae="";} // new added by rafiq
				 var currentdate = new Date();
var numberOfDaysToAdd = 2;
currentdate.setDate(currentdate.getDate() + numberOfDaysToAdd); 
var date1 = new Date(currentdate);
var date2 = new Date(duedatae);
				//var strstyle=' style="color:red; text-transform:capitalize" ';
var strstyle='';
if(date1 - date2 < 0 ){strstyle=' style="color:black; font-weight:600; text-transform:capitalize" ';}	
if(date1 - date2 > 0 ){strstyle=' style="color:red; font-weight:600; text-transform:capitalize" '; 
//alert((date1 - date2)+'=='+currentdate+'-'+duedatae);
//alert(datediff(date1, date2)+" = "+date2+"-"+duedatae);

}
if(date1 - date2 > 0 && ((datediff(date1, date2) == -1)||(datediff(date1, date2) == -2)) ){strstyle=' style="color:#ffd80b; font-weight:600; text-transform:capitalize" ';}
if (data[i].TASK_STATUS=="COMPLETED")
{strstyle=' style="color:#2cc62c; font-weight:600; text-transform:capitalize" ';} //green
if(output == "" || duedatae == ""){strstyle=' style="color:black; font-weight:600; text-transform:capitalize" ';}				
			var output2 = "";
		 var repint = data[i].REPEAT_INTERVAL;
		 //if(output == "") output = "Never";
		 var arrIntervals = repint.split(',');
		 var arrayLength = arrIntervals.length;
for (var x = 0; x < arrayLength; x++) {
	switch(arrIntervals[x])
	{
		case '0': 
			output2 += "Mon, ";
			break;
		case '1': 
			output2 += "Tue, ";
			break;
		case '2': 
			output2 += "Wed, ";
			break;
		case '3': 
			output2 += "Thu, ";
			break;
		case '4': 
			output2 += "Fri, ";
			break;
		case '5': 
			output2 += "Sat, ";
			break;
		case '6': 
			output2 += "Sun, ";
			break;
		case '7': 
			output2 += "Month";
			break;
	}
   // console.log();
    //Do something
}

if(repint == "0,1,2,3,4,5,6") output2 = "Every Day";	
if(output2 == "") output2 = "Never";				
	
	var anchortag = '<a href="javascript:changeStatusAssignOthersRR(\'COMPLETED\','+data[i].TASK_ID+',' + data[i].ASSIGNED_ID + ');" style="font-size:12px;"><img src="assets/images/mark-completed.gif" id="markascompleted'+data[i].TASK_ID+'" /></a>';								
if(data[i].TASK_STATUS == 'COMPLETED')
{				
    anchortag = '<a href="#" style="font-size:12px;"><img src="assets/images/completed-task.gif" id="completedtask"/></a>';
}			
			if(!repint==""){
				//alert('taskListNoRepeat');
             
			 /*   taskListNoRepeat+='<div class="mail-list taskDetailsAssignOthers" data-task_id="' + data[i].TASK_ID + '">' +
                    '<div class="content">' +
                    '<p class="message_text" '+strstyle+'>' + data[i].TASK_TITLE + '</p>' +
					'<p class="message_text" style="font-size:12px">' + output2 + '</p>' +
					'<p class="message_text" style="font-size:12px">' + convertDate(data[i].DUE_DATE) + '</p>' +
                   '</div>' +
                    '</div>';
				taskList.append(taskListNoRepeat);
				*/
				 taskListNoRepeat+='<div class="mail-list taskDetailsAssignOthers" data-task_id="' + data[i].TASK_ID + '">' +
                    '<div class="content">' +
					'<div class="row"><div class="col-md-8"><p class="message_text" '+ strstyle +'>' + data[i].TASK_TITLE + '</p></div><div class="col-md-4"  style="text-align:right;padding:0px; margin:0px;">'+anchortag+'</div></div>' + 
					'<div class="row"><div class="col-md-12"><p class="message_text" style="font-size:12px">' + output2 + '</p></div></div>' +
					'<div class="row"><div class="col-md-6"><p class="message_text" style="font-size:12px">' + duedatae + '</p></div><div class="col-md-6" style="text-align:right;"><p class="message_text" style="font-size:12px"><strong>Status</strong>: ' + data[i].TASK_STATUS + '</p></div></div>' +
					'</div></div>';
				taskList.append(taskListNoRepeat);


			}
			
            }
			
        } else {
            taskList.append('<div class="error">' + d.MESSAGE + '</div>')
        }
    }

	 function createAssignMeNewDue(d) {
        taskList.empty();
        if(d.STATUS !== 'ERROR') {
            var data = JSON.parse(d.DATA);
			
   taskList.append('<div class="mail-list" style="background: grey;color:white;"><h6 style="margin:0px">Due Today Non Repeated Assigned to Me Tasks</h6></div>');
            console.log(data);
			for (var i = 0; i < data.length; i++) {
				taskListRepeat="";
		        taskListNoRepeat="";
				
				var output = "";
				var output2= "";
		 var repint = data[i].REPEAT_INTERVAL;
		 //if(output == "") output += "Never";
		 var arrIntervals = repint.split(',');
		 var arrayLength = arrIntervals.length;
for (var x = 0; x < arrayLength; x++) {
	switch(arrIntervals[x])
	{
		case '0': 
			output2 += "Mon, ";
			break;
		case '1': 
			output2 += "Tue, ";
			break;
		case '2': 
			output2 += "Wed, ";
			break;
		case '3': 
			output2 += "Thu, ";
			break;
		case '4': 
			output2 += "Fri, ";
			break;
		case '5': 
			output2 += "Sat, ";
			break;
		case '6': 
			output2 += "Sun, ";
			break;
		case '7': 
			output2 += "Month";
			break;
	}
   // console.log();
    //Do something
}

if(repint == "0,1,2,3,4,5,6") output = "Every Day";
if(output == "") output = "Never";

                 var duedatae = convertDate(data[i].DUE_DATE)
				 if(duedatae=="1 Jan 1970 5:0 AM"){duedatae="";}
				 if(duedatae=="31 Dec 1969 6:0 PM"){duedatae="";} // new added by rafiq
				 var currentdate = new Date();
var numberOfDaysToAdd = 2;
currentdate.setDate(currentdate.getDate() + numberOfDaysToAdd); 
var date1 = new Date(currentdate);
var date2 = new Date(duedatae);
				//var strstyle=' style="color:red; text-transform:capitalize" ';

          var openvar = "";
		  var closevar = "";
		   var completevar = "";
		    var inprogressvar = "";
 switch(data[i].TASK_STATUS)
		 {
			 case "OPEN":
			 		openvar = " selected='selected'";
			 	break;
				 case "CLOSED":
			 		closevar = " selected='selected'";
			 	break;
				 case "COMPLETED":
			 		completevar = " selected='selected'";
			 	break;
				 case "IN PROGRESS":
			 		inprogressvar = " selected='selected'";
			 	break;
		 }
		 var notificationStatusYes="";
		 var notificationStatusNo="";
		 //alert('h '+ data.TASK_NOTIFICATION_STATUS);
		 if(data[i].STATUS==1){notificationStatusYes = " checked"}
		 else{notificationStatusNo = " checked"}

var strstyle='';
if(date1 - date2 < 0 ){strstyle=' style="color:black; font-weight:600; text-transform:capitalize" ';}	
if(date1 - date2 > 0 ){strstyle=' style="color:red; font-weight:600; text-transform:capitalize" '; 
//alert((date1 - date2)+'=='+currentdate+'-'+duedatae);
//alert(datediff(date1, date2)+" = "+date2+"-"+duedatae);

}
if(date1 - date2 > 0 && ((datediff(date1, date2) == -1)||(datediff(date1, date2) == -2)) ){strstyle=' style="color:#ffd80b; font-weight:600; text-transform:capitalize" ';}
if (data[i].TASK_STATUS=="COMPLETED")
{strstyle=' style="color:#2cc62c; font-weight:600; text-transform:capitalize" ';} //green
if(output == "" || duedatae == ""){strstyle=' style="color:black; font-weight:600; text-transform:capitalize" ';}


var anchortag = '<a href="javascript:changeStatusDD(\'COMPLETED\','+data[i].TASK_ID+');" style="font-size:12px;"><img src="assets/images/mark-completed.gif" id="markascompleted'+data[i].TASK_ID+'" /></a>';								
if(data[i].TASK_STATUS == 'COMPLETED')
{				
    anchortag = '<a href="#" style="font-size:12px;"><img src="assets/images/completed-task.gif" id="completedtask"/></a>';
}

var notificationButton = '<img src="assets/images/btn-off.png" id="imgoff" onclick="changeNotificationStatusDueAsMe('+data[i].CREATOR_ID+',1,' + data[i].TASK_ID + ');" />';
if(data[i].STATUS==1){notificationButton = '<img src="assets/images/btn-on.png" id="imgon" onclick="changeNotificationStatusDueAsMe('+data[i].CREATOR_ID+',0,' + data[i].TASK_ID + ');" />';}


			 //alert(duedatae);
			if(repint==""){
				//alert('taskListNoRepeat');
              
			  /*
			    taskListNoRepeat+='<div class="mail-list taskDetailsAMe" data-task_id="' + data[i].TASK_ID + '">' +
                    '<div class="content">' +
               '<p class="message_text" '+strstyle+'>' + data[i].TASK_TITLE + '</p>' +
			'<div class="row"><div class="col-md-6" style="" ><p class="message_text" style="font-size:12px">' + output + '</p></div>' +
					'<div class="col-md-6" style="padding:0px"><p class="message_text" style="font-size:12px">' + duedatae + '</p></div></div>' +
			'<div class="row"><div class="col-md-6" style="padding:0px"><select class="custom-select browser-default" required="" id="TASK_STATUS" name="TASK_STATUS" onchange="changeStatusDD(this.value,' + data[i].TASK_ID + ');"><option value="OPEN" ' + openvar + ' >OPEN</option><option value="CLOSED" ' + closevar + '>CLOSED</option><option value="COMPLETED" ' + completevar + '>COMPLETED</option><option value="IN PROGRESS" ' + inprogressvar + '>IN PROGRESS</option></select></div><div class="col-md-6">'+
			'<div class="message-content" style="padding-top:6px; color: #46de46;font-weight:600" ><input onchange="changeNotificationStatus(this.value,' + data[i].TASK_ID + ');" class="message-content" type="radio" name="TASK_NOTIFICATION_STATUS_' + data[i].TASK_ID + '" value="1" ' + notificationStatusYes + '> Yes <input onchange="changeNotificationStatus(this.value,' + data[i].TASK_ID + ');" class="message-content" style="margin-left:6px" type="radio" name="TASK_NOTIFICATION_STATUS_' + data[i].TASK_ID + '" value="0" ' + notificationStatusNo + '> No </div>'+
			'</div></div></div></div>';
				taskList.append(taskListNoRepeat);
				-----
				 taskListNoRepeat+='<div class="mail-list taskDetailsAme" data-task_id="' + data[i].TASK_ID + '">' +
                    '<div class="content">' +
               '<p class="message_text" '+strstyle+'>' + data[i].TASK_TITLE + '</p>' +
			'<div class="row"><div class="col-md-6" style="" ><p class="message_text" style="font-size:12px">' + output + '</p></div>' +
					'<div class="col-md-6" style="padding:0px"><p class="message_text" style="font-size:12px">' + duedatae + '</p></div></div>' +
			'<div class="row"><div class="col-md-6" style="padding:0px"><select class="custom-select browser-default" required="" id="TASK_STATUS" name="TASK_STATUS" onchange="changeStatusDD(this.value,' + data[i].TASK_ID + ');"><option value="OPEN" ' + openvar + ' >OPEN</option><option value="CLOSED" ' + closevar + '>CLOSED</option><option value="COMPLETED" ' + completevar + '>COMPLETED</option><option value="IN PROGRESS" ' + inprogressvar + '>IN PROGRESS</option></select></div><div class="col-md-6">'+
			'<div class="message-content" style="padding-top:6px; color: #46de46;font-weight:600" ><input onchange="changeNotificationStatus(this.value,' + data[i].TASK_ID + ');" class="message-content" type="radio" name="TASK_NOTIFICATION_STATUS_' + data[i].TASK_ID + '" value="1" ' + notificationStatusYes + '> Yes <input onchange="changeNotificationStatus(this.value,' + data[i].TASK_ID + ');" class="message-content" style="margin-left:6px" type="radio" name="TASK_NOTIFICATION_STATUS_' + data[i].TASK_ID + '" value="0" ' + notificationStatusNo + '> No </div>'+
			'</div></div></div></div>';
				taskList.append(taskListNoRepeat);
				*/
				 taskList.append('<div class="mail-list taskDetailsAMe" data-task_id="' + data[i].TASK_ID + '">' +
                    '<div class="content" style="width:97%;">'+
						'<div class="row"><div class="col-md-8"><p class="message_text" '+ strstyle +'>' + data[i].TASK_TITLE + '</p></div><div class="col-md-4"  id="due_personl_rep" style="text-align:right;">'+anchortag+'</div></div>' + 
                    	'<div class="row"><div class="col-md-9"><p class="message_text">'+timeSince(new Date(duedatae))+'</p></div><div class="col-md-3"  id="due_personl_rep" style="text-align:right;">'+notificationButton+'</div></div>'+ 
						'<div class="row"><div class="col-md-12"><p class="message_text"><strong>Repeat: </strong> '+output+'</p></div></div>'+ 
                   		'<div class="row" style="visibility:hidden; display:none;"><div class="col-md-1"></div><div class="col-md-10" style="padding:0px"><select class="custom-select browser-default" required="" id="TASK_STATUS" name="TASK_STATUS" onchange="changeStatusDD(this.value,' + data[i].TASK_ID + ');"><option value="OPEN" ' + openvar + ' >OPEN</option><option value="CLOSED" ' + closevar + '>CLOSED</option><option value="COMPLETED" ' + completevar + '>COMPLETED</option><option value="IN PROGRESS" ' + inprogressvar + '>IN PROGRESS</option></select></div><div class="col-md-1"></div></div>'+
					'</div></div>');	
					
					
			}
            }
			 
			
        } else {
            taskList.append('<div class="error">' + d.MESSAGE + '</div>')
        }
    }
	 function createAssignMeNewRep(d) {
        taskList.empty();
		 taskDetail.empty();
        if(d.STATUS !== 'ERROR') {
            var data = JSON.parse(d.DATA);
   
            // console.log(data);
			for (var i = 0; i < data.length; i++) {
				taskListRepeat="";
		        taskListNoRepeat="";
				
				var output = "";
		 var repint = data[i].REPEAT_INTERVAL;
		 //if(output == "") output += "Never";
		 var arrIntervals = repint.split(',');
		 var arrayLength = arrIntervals.length;


if(repint == "0,1,2,3,4,5,6") output = "Every Day";
if(output == "") output = "Never";

                 var duedatae = convertDate(data[i].DUE_DATE)
				 if(duedatae=="1 Jan 1970 5:0 AM"){duedatae="";}
				 if(duedatae=="31 Dec 1969 6:0 PM"){duedatae="";} // new added by rafiq
				 var currentdate = new Date();
var numberOfDaysToAdd = 2;
currentdate.setDate(currentdate.getDate() + numberOfDaysToAdd); 
var date1 = new Date(currentdate);
var date2 = new Date(duedatae);
				//var strstyle=' style="color:red; text-transform:capitalize" ';

          
				
				
var strstyle='';
if(date1 - date2 < 0 ){strstyle=' style="color:black; font-weight:600; text-transform:capitalize" ';}	
if(date1 - date2 > 0 ){strstyle=' style="color:red; font-weight:600; text-transform:capitalize" '; 
//alert((date1 - date2)+'=='+currentdate+'-'+duedatae);
//alert(datediff(date1, date2)+" = "+date2+"-"+duedatae);

}
if(date1 - date2 > 0 && ((datediff(date1, date2) == -1)||(datediff(date1, date2) == -2)) ){strstyle=' style="color:#ffd80b; font-weight:600; text-transform:capitalize" ';}
if (data[i].TASK_STATUS=="COMPLETED")
{strstyle=' style="color:#2cc62c; font-weight:600; text-transform:capitalize" ';} //green
if(output == "" || duedatae == ""){strstyle=' style="color:black; font-weight:600; text-transform:capitalize" ';}

			 //alert(duedatae);
		
            }
			 taskList.append('<div class="mail-list" style="background: grey;color:white;"><h6 style="margin:0px">Repeat Interval Assigned Me Tasks</h6></div>');
			for (var i = 0; i < data.length; i++) {
				taskListRepeat="";
		        taskListNoRepeat="";
				
				var duedatae = convertDate(data[i].DUE_DATE)
				 if(duedatae=="1 Jan 1970 5:0 AM"){duedatae="";}
				 if(duedatae=="31 Dec 1969 6:0 PM"){duedatae="";} // new added by rafiq
				 var currentdate = new Date();
var numberOfDaysToAdd = 2;
currentdate.setDate(currentdate.getDate() + numberOfDaysToAdd); 
var date1 = new Date(currentdate);
var date2 = new Date(duedatae);
				//var strstyle=' style="color:red; text-transform:capitalize" ';
var strstyle='';

 var openvar = "";
		  var closevar = "";
		   var completevar = "";
		    var inprogressvar = "";
 switch(data[i].TASK_STATUS)
		 {
			 case "OPEN":
			 		openvar = " selected='selected'";
			 	break;
				 case "CLOSED":
			 		closevar = " selected='selected'";
			 	break;
				 case "COMPLETED":
			 		completevar = " selected='selected'";
			 	break;
				 case "IN PROGRESS":
			 		inprogressvar = " selected='selected'";
			 	break;
		 }
		 var notificationStatusYes="";
		 var notificationStatusNo="";
		 //alert('h '+ data.TASK_NOTIFICATION_STATUS);
		 if(data[i].STATUS==1){notificationStatusYes = " checked"}
		 else{notificationStatusNo = " checked"}

if(date1 - date2 < 0 ){strstyle=' style="color:black; font-weight:600; text-transform:capitalize" ';}	
if(date1 - date2 > 0 ){strstyle=' style="color:red; font-weight:600; text-transform:capitalize" '; 
//alert((date1 - date2)+'=='+currentdate+'-'+duedatae);
//alert(datediff(date1, date2)+" = "+date2+"-"+duedatae);

}
if(date1 - date2 > 0 && ((datediff(date1, date2) == -1)||(datediff(date1, date2) == -2)) ){strstyle=' style="color:#ffd80b; font-weight:600; text-transform:capitalize" ';}
if (data[i].TASK_STATUS=="COMPLETED")
{strstyle=' style="color:#2cc62c; font-weight:600; text-transform:capitalize" ';} //green
if(output == "" || duedatae == ""){strstyle=' style="color:black; font-weight:600; text-transform:capitalize" ';}				
			var output2 = "";
		 var repint = data[i].REPEAT_INTERVAL;
		 //if(output == "") output = "Never";
		 var arrIntervals = repint.split(',');
		 var arrayLength = arrIntervals.length;
for (var x = 0; x < arrayLength; x++) {
	switch(arrIntervals[x])
	{
		case '0': 
			output2 += "Mon, ";
			break;
		case '1': 
			output2 += "Tue, ";
			break;
		case '2': 
			output2 += "Wed, ";
			break;
		case '3': 
			output2 += "Thu, ";
			break;
		case '4': 
			output2 += "Fri, ";
			break;
		case '5': 
			output2 += "Sat, ";
			break;
		case '6': 
			output2 += "Sun, ";
			break;
		case '7': 
			output2 += "Month";
			break;
	}
   // console.log();
    //Do something
}

if(repint == "0,1,2,3,4,5,6") output2 = "Every Day";	
if(output2 == "") output2 = "Never";				
				
			if(!repint==""){
				//alert('taskListNoRepeat');
				

var anchortag = '<a href="javascript:changeStatusAMRT(\'COMPLETED\','+data[i].TASK_ID+',' + data[i].CREATOR_ID + ');" style="font-size:12px;"><img src="assets/images/mark-completed.gif" id="markascompleted'+data[i].TASK_ID+'" /></a>';								
if(data[i].TASK_STATUS == 'COMPLETED')
{				
    anchortag = '<a href="#" style="font-size:12px;"><img src="assets/images/completed-task.gif" id="completedtask"/></a>';
}

var notificationButton = '<img src="assets/images/btn-off.png" id="imgoff" onclick="changeNotificationStatusAMRT(' + data[i].CREATOR_ID + ',1,' + data[i].TASK_ID + ');" />';
if(data[i].STATUS==1){notificationButton = '<img src="assets/images/btn-on.png" id="imgon" onclick="changeNotificationStatusAMRT(' + data[i].CREATOR_ID + ',0,' + data[i].TASK_ID + ');" />';}


	/*		
	  taskListNoRepeat+='<div class="mail-list taskDetailsAMe" data-task_id="' + data[i].TASK_ID + '">' +
                  '<div class="content" style="width:97%;">' +
                    '<p class="message_text" '+strstyle+'>' + data[i].TASK_TITLE + '</p>' +
			'<div class="row"><div class="col-md-6" ><p class="message_text" style="font-size:12px">' + output2 + '</p></div>' +
					'<div class="col-md-6" style="padding:0px"><p class="message_text" style="font-size:12px">' + duedatae + '</p></div></div>' +
			'<div class="row"><div class="col-md-6" style="padding:0px"><select class="custom-select browser-default" required="" id="TASK_STATUS" name="TASK_STATUS" onchange="changeStatus(this.value,' + data[i].TASK_ID + ');"><option value="OPEN" ' + openvar + ' >OPEN</option><option value="CLOSED" ' + closevar + '>CLOSED</option><option value="COMPLETED" ' + completevar + '>COMPLETED</option><option value="IN PROGRESS" ' + inprogressvar + '>IN PROGRESS</option></select></div>'+
			'<div class="col-md-3">'+
			'<div class="message-content" style="padding-top:6px;color: #46de46; font-weight:600" ><input onchange="changeNotificationStatus(this.value,' + data[i].TASK_ID + ');" class="message-content" type="radio" name="TASK_NOTIFICATION_STATUS_' + data[i].TASK_ID + '" value="1" ' + notificationStatusYes + '> Yes <input onchange="changeNotificationStatus(this.value,' + data[i].TASK_ID + ');" class="message-content" style="margin-left:6px" type="radio" name="TASK_NOTIFICATION_STATUS_' + data[i].TASK_ID + '" value="0" ' + notificationStatusNo + '> No </div>'+
			'</div>'+
			'<div class="col-md-3" style="padding-left:10px;">'+anchortag+'</div>' +
						'</div>'+			
			'</div></div></div>';			
			
				taskList.append(taskListNoRepeat);
				-------	
                taskListNoRepeat+='<div class="mail-list taskDetailsAMe" data-task_id="' + data[i].TASK_ID + '">' +
                  '<div class="content" style="width:97%;">' +
                    '<p class="message_text" '+strstyle+'>' + data[i].TASK_TITLE + '</p>' +
			'<div class="row"><div class="col-md-6" ><p class="message_text" style="font-size:12px">' + output2 + '</p></div>' +
					'<div class="col-md-6" style="padding:0px"><p class="message_text" style="font-size:12px">' + duedatae + '</p></div></div>' +
			'<div class="row"><div class="col-md-6" style="padding:0px"><select class="custom-select browser-default" required="" id="TASK_STATUS" name="TASK_STATUS" onchange="changeStatus(this.value,' + data[i].TASK_ID + ');"><option value="OPEN" ' + openvar + ' >OPEN</option><option value="CLOSED" ' + closevar + '>CLOSED</option><option value="COMPLETED" ' + completevar + '>COMPLETED</option><option value="IN PROGRESS" ' + inprogressvar + '>IN PROGRESS</option></select></div>'+
			'<div class="col-md-3">'+
			'<div class="message-content" style="padding-top:6px;color: #46de46; font-weight:600" ><input onchange="changeNotificationStatus(this.value,' + data[i].TASK_ID + ');" class="message-content" type="radio" name="TASK_NOTIFICATION_STATUS_' + data[i].TASK_ID + '" value="1" ' + notificationStatusYes + '> Yes <input onchange="changeNotificationStatus(this.value,' + data[i].TASK_ID + ');" class="message-content" style="margin-left:6px" type="radio" name="TASK_NOTIFICATION_STATUS_' + data[i].TASK_ID + '" value="0" ' + notificationStatusNo + '> No </div>'+
			'</div>'+
			'<div class="col-md-3" style="padding-left:10px;">'+anchortag+'</div>' +
						'</div>'+			
			'</div></div></div>';			
		*/	
			
			 taskList.append('<div class="mail-list taskDetailsAMe" data-task_id="' + data[i].TASK_ID + '">' +
                    '<div class="content" style="width:97%;">'+
						'<div class="row"><div class="col-md-8"><p class="message_text" '+ strstyle +'>' + data[i].TASK_TITLE + '</p></div><div class="col-md-4"  id="due_personl_rep" style="text-align:right;">'+anchortag+'</div></div>' + 
                    	'<div class="row"><div class="col-md-9"><p class="message_text">'+timeSince(new Date(duedatae))+'</p></div><div class="col-md-3"  id="due_personl_rep" style="text-align:right;">'+notificationButton+'</div></div>'+ 
						'<div class="row"><div class="col-md-12"><p class="message_text"><strong>Repeat: </strong> '+output2+'</p></div></div>'+ 
                   		'<div class="row" style="visibility:hidden; display:none;"><div class="col-md-1"></div><div class="col-md-10" style="padding:0px"><select class="custom-select browser-default" required="" id="TASK_STATUS" name="TASK_STATUS" onchange="changeStatusDD(this.value,' + data[i].TASK_ID + ');"><option value="OPEN" ' + openvar + ' >OPEN</option><option value="CLOSED" ' + closevar + '>CLOSED</option><option value="COMPLETED" ' + completevar + '>COMPLETED</option><option value="IN PROGRESS" ' + inprogressvar + '>IN PROGRESS</option></select></div><div class="col-md-1"></div></div>'+
					'</div></div>');
					
					
					
				//taskList.append(taskListNoRepeat);
			}
			
            }
			
        } else {
            taskList.append('<div class="error">' + d.MESSAGE + '</div>')
        }
    }
	function createAssignMeNewTask(d) {
        taskList.empty();
		 taskDetail.empty();
        if(d.STATUS !== 'ERROR') {
            var data = JSON.parse(d.DATA);
			
   taskList.append('<div class="mail-list" style="background: grey;color:white;"><h6 style="margin:0px">To Do Assigned to Me Tasks</h6></div>');
            // console.log(data);
			for (var i = 0; i < data.length; i++) {
				taskListRepeat="";
		        taskListNoRepeat="";
				
				var output = "";
				var output2= "";
		 var repint = data[i].REPEAT_INTERVAL;
		 //if(output == "") output += "Never";
		 var arrIntervals = repint.split(',');
		 var arrayLength = arrIntervals.length;
for (var x = 0; x < arrayLength; x++) {
	switch(arrIntervals[x])
	{
		case '0': 
			output2 += "Mon, ";
			break;
		case '1': 
			output2 += "Tue, ";
			break;
		case '2': 
			output2 += "Wed, ";
			break;
		case '3': 
			output2 += "Thu, ";
			break;
		case '4': 
			output2 += "Fri, ";
			break;
		case '5': 
			output2 += "Sat, ";
			break;
		case '6': 
			output2 += "Sun, ";
			break;
		case '7': 
			output2 += "Month";
			break;
	}
   // console.log();
    //Do something
}

if(repint == "0,1,2,3,4,5,6") output = "Every Day";
if(output == "") output = "Never";

                 var duedatae = convertDate(data[i].DUE_DATE)
				 if(duedatae=="1 Jan 1970 5:0 AM"){duedatae="";}
				 if(duedatae=="31 Dec 1969 6:0 PM"){duedatae="";} // new added by rafiq
				 var currentdate = new Date();
var numberOfDaysToAdd = 2;
currentdate.setDate(currentdate.getDate() + numberOfDaysToAdd); 
var date1 = new Date(currentdate);
var date2 = new Date(duedatae);
				//var strstyle=' style="color:red; text-transform:capitalize" ';

          var openvar = "";
		  var closevar = "";
		   var completevar = "";
		    var inprogressvar = "";
 switch(data[i].TASK_STATUS)
		 {
			 case "OPEN":
			 		openvar = " selected='selected'";
			 	break;
				 case "CLOSED":
			 		closevar = " selected='selected'";
			 	break;
				 case "COMPLETED":
			 		completevar = " selected='selected'";
			 	break;
				 case "IN PROGRESS":
			 		inprogressvar = " selected='selected'";
			 	break;
		 }
		 var notificationStatusYes="";
		 var notificationStatusNo="";
		 //alert('h '+ data.TASK_NOTIFICATION_STATUS);
		 if(data[i].STATUS==1){notificationStatusYes = " checked"}
		 else{notificationStatusNo = " checked"}

var strstyle='';
if(date1 - date2 < 0 ){strstyle=' style="color:black; font-weight:600; text-transform:capitalize" ';}	
if(date1 - date2 > 0 ){strstyle=' style="color:red; font-weight:600; text-transform:capitalize" '; 
//alert((date1 - date2)+'=='+currentdate+'-'+duedatae);
//alert(datediff(date1, date2)+" = "+date2+"-"+duedatae);

}
if(date1 - date2 > 0 && ((datediff(date1, date2) == -1)||(datediff(date1, date2) == -2)) ){strstyle=' style="color:#ffd80b; font-weight:600; text-transform:capitalize" ';}
if (data[i].TASK_STATUS=="COMPLETED")
{strstyle=' style="color:#2cc62c; font-weight:600; text-transform:capitalize" ';} //green
if(output == "" || duedatae == ""){strstyle=' style="color:black; font-weight:600; text-transform:capitalize" ';}

			 //alert(duedatae);
			if(repint=="" && duedatae==0){
var anchortag = '<a href="javascript:changeStatusAMTDT(\'COMPLETED\','+data[i].TASK_ID+','+data[i].CREATOR_ID+');" style="font-size:12px;"><img src="assets/images/mark-completed.gif" id="markascompleted'+data[i].TASK_ID+'" /></a>';								
if(data[i].TASK_STATUS == 'COMPLETED')
{				
    anchortag = '<a href="#" style="font-size:12px;"><img src="assets/images/completed-task.gif" id="completedtask"/></a>';
}

var notificationButton = '<img src="assets/images/btn-off.png" id="imgoff" onclick="changeNotificationStatusAMTDT('+data[i].CREATOR_ID+',1,' + data[i].TASK_ID + ');" />';
if(data[i].STATUS==1){notificationButton = '<img src="assets/images/btn-on.png" id="imgon" onclick="changeNotificationStatusAMTDT('+data[i].CREATOR_ID+',0,' + data[i].TASK_ID + ');" />';}

/*				
var anchortag = '<a href="javascript:changeStatusAMD(\'COMPLETED\','+data[i].TASK_ID+','+data[i].CREATOR_ID+');" style="font-size:12px;">Mark as Complete</a>';								
if(data[i].TASK_STATUS == 'COMPLETED')
{				
    anchortag = '<a href="#" style="font-size:12px;">Completed</a>';
}
*/			
 				taskList.append('<div class="mail-list taskDetailsAMe" data-task_id="' + data[i].TASK_ID + '">' +
                    '<div class="content" style="width:97%;">'+
						'<div class="row"><div class="col-md-8"><p class="message_text" '+ strstyle +'>' + data[i].TASK_TITLE + '</p></div><div class="col-md-4"  style="text-align:right;">'+anchortag+'</div></div>' + 
                    	'<div class="row"><div class="col-md-9"><p class="message_text">&nbsp;</p></div><div class="col-md-3"  id="due_personl_rep" style="text-align:right;">'+notificationButton+'</div></div>'+ 					
					'</div></div>');

/*	
				taskListNoRepeat+='<div class="mail-list taskDetails" data-task_id="' + data[i].TASK_ID + '">' +
                    '<div class="content" style="width:97%;">' +
               '<p class="message_text" '+strstyle+'>' + data[i].TASK_TITLE + '</p>' +
			'<div class="row"><div class="col-md-6" style="" ><p class="message_text" style="font-size:12px">' + output + '</p></div>' +
					'<div class="col-md-6" style="padding:0px"><p class="message_text" style="font-size:12px">' + duedatae + '</p></div></div>' +
			'<div class="row"><div class="col-md-6" style="padding:0px"><select class="custom-select browser-default" required="" id="TASK_STATUS" name="TASK_STATUS" onchange="changeStatus(this.value,' + data[i].TASK_ID + ');"><option value="OPEN" ' + openvar + ' >OPEN</option><option value="CLOSED" ' + closevar + '>CLOSED</option><option value="COMPLETED" ' + completevar + '>COMPLETED</option><option value="IN PROGRESS" ' + inprogressvar + '>IN PROGRESS</option></select></div>'+
			'<div class="col-md-3">'+
			'<div class="message-content" style="padding-top:6px; color: #46de46;font-weight:600" ><input onchange="changeNotificationStatus(this.value,' + data[i].TASK_ID + ');" class="message-content" type="radio" name="TASK_NOTIFICATION_STATUS_' + data[i].TASK_ID + '" value="1" ' + notificationStatusYes + '> Yes <input onchange="changeNotificationStatus(this.value,' + data[i].TASK_ID + ');" class="message-content" style="margin-left:6px" type="radio" name="TASK_NOTIFICATION_STATUS_' + data[i].TASK_ID + '" value="0" ' + notificationStatusNo + '> No </div>'+
			'</div>'+
			'<div class="col-md-3" style="padding-left:10px;">'+anchortag+'</div>' +
						'</div>'+			
			'</div></div></div>';
			
				taskList.append(taskListNoRepeat);
				*/
			}
            }
			 
			
        } else {
            taskList.append('<div class="error">' + d.MESSAGE + '</div>')
        }
    }
	function createAssignMeNewTaskDue(d) {
        taskList.empty();
        if(d.STATUS !== 'ERROR') {
            var data = JSON.parse(d.DATA);
			
   taskList.append('<div class="mail-list" style="background: grey;color:white;"><h6 style="margin:0px">Non Repeated Assigned to Me Tasks</h6></div>');
            // console.log(data);
			for (var i = 0; i < data.length; i++) {
				taskListRepeat="";
		        taskListNoRepeat="";
				
				var output = "";
				var output2= "";
		 var repint = data[i].REPEAT_INTERVAL;
		 //if(output == "") output += "Never";
		 var arrIntervals = repint.split(',');
		 var arrayLength = arrIntervals.length;
for (var x = 0; x < arrayLength; x++) {
	switch(arrIntervals[x])
	{
		case '0': 
			output2 += "Mon, ";
			break;
		case '1': 
			output2 += "Tue, ";
			break;
		case '2': 
			output2 += "Wed, ";
			break;
		case '3': 
			output2 += "Thu, ";
			break;
		case '4': 
			output2 += "Fri, ";
			break;
		case '5': 
			output2 += "Sat, ";
			break;
		case '6': 
			output2 += "Sun, ";
			break;
		case '7': 
			output2 += "Month";
			break;
	}
   // console.log();
    //Do something
}

if(repint == "0,1,2,3,4,5,6") output = "Every Day";
if(output == "") output = "Never";

                 var duedatae = convertDate(data[i].DUE_DATE)
				 if(duedatae=="1 Jan 1970 5:0 AM"){duedatae="";}
				 if(duedatae=="31 Dec 1969 6:0 PM"){duedatae="";} // new added by rafiq
				 var currentdate = new Date();
var numberOfDaysToAdd = 2;
currentdate.setDate(currentdate.getDate() + numberOfDaysToAdd); 
var date1 = new Date(currentdate);
var date2 = new Date(duedatae);
				//var strstyle=' style="color:red; text-transform:capitalize" ';

          var openvar = "";
		  var closevar = "";
		   var completevar = "";
		    var inprogressvar = "";
 switch(data[i].TASK_STATUS)
		 {
			 case "OPEN":
			 		openvar = " selected='selected'";
			 	break;
				 case "CLOSED":
			 		closevar = " selected='selected'";
			 	break;
				 case "COMPLETED":
			 		completevar = " selected='selected'";
			 	break;
				 case "IN PROGRESS":
			 		inprogressvar = " selected='selected'";
			 	break;
		 }
		 var notificationStatusYes="";
		 var notificationStatusNo="";
		 //alert('h '+ data.TASK_NOTIFICATION_STATUS);
		 if(data[i].STATUS==1){notificationStatusYes = " checked"}
		 else{notificationStatusNo = " checked"}

var strstyle='';
if(date1 - date2 < 0 ){strstyle=' style="color:black; font-weight:600; text-transform:capitalize" ';}	
if(date1 - date2 > 0 ){strstyle=' style="color:red; font-weight:600; text-transform:capitalize" '; 
//alert((date1 - date2)+'=='+currentdate+'-'+duedatae);
//alert(datediff(date1, date2)+" = "+date2+"-"+duedatae);

}
if(date1 - date2 > 0 && ((datediff(date1, date2) == -1)||(datediff(date1, date2) == -2)) ){strstyle=' style="color:#ffd80b; font-weight:600; text-transform:capitalize" ';}
if (data[i].TASK_STATUS=="COMPLETED")
{strstyle=' style="color:#2cc62c; font-weight:600; text-transform:capitalize" ';} //green
if(output == "" || duedatae == ""){strstyle=' style="color:black; font-weight:600; text-transform:capitalize" ';}

			 //alert(duedatae);
			if(repint=="" && duedatae != "" ){
				//alert('taskListNoRepeat');
				
var anchortag = '<a href="javascript:changeStatusAMDNRT(\'COMPLETED\','+data[i].TASK_ID+','+data[i].CREATOR_ID+');" style="font-size:12px;"><img src="assets/images/mark-completed.gif" id="markascompleted'+data[i].TASK_ID+'" /></a>';								
if(data[i].TASK_STATUS == 'COMPLETED')
{				
    anchortag = '<a href="#" style="font-size:12px;"><img src="assets/images/completed-task.gif" id="completedtask"/></a>';
}

var notificationButton = '<img src="assets/images/btn-off.png" id="imgoff" onclick="changeNotificationStatusAMNRT('+data[i].CREATOR_ID+',1,' + data[i].TASK_ID + ');" />';
if(data[i].STATUS==1){notificationButton = '<img src="assets/images/btn-on.png" id="imgon" onclick="changeNotificationStatusAMNRT('+data[i].CREATOR_ID+',0,' + data[i].TASK_ID + ');" />';}

				
				/*
var anchortag = '<a href="javascript:changeStatusAMDNR(\'COMPLETED\','+data[i].TASK_ID+','+data[i].CREATOR_ID+');" style="font-size:12px;">Mark as Complete</a>';								
if(data[i].TASK_STATUS == 'COMPLETED')
{				
    anchortag = '<a href="#" style="font-size:12px;">Completed</a>';
}				

				
                taskListNoRepeat+='<div class="mail-list taskDetails" data-task_id="' + data[i].TASK_ID + '">' +
                   '<div class="content" style="width:97%;">' +
               '<p class="message_text" '+strstyle+'>' + data[i].TASK_TITLE + '</p>' +
			'<div class="row"><div class="col-md-6" style="" ><p class="message_text" style="font-size:12px">' + output + '</p></div>' +
					'<div class="col-md-6" style="padding:0px"><p class="message_text" style="font-size:12px">' + duedatae + '</p></div></div>' +
			'<div class="row"><div class="col-md-6" style="padding:0px"><select class="custom-select browser-default" required="" id="TASK_STATUS" name="TASK_STATUS" onchange="changeStatus(this.value,' + data[i].TASK_ID + ');"><option value="OPEN" ' + openvar + ' >OPEN</option><option value="CLOSED" ' + closevar + '>CLOSED</option><option value="COMPLETED" ' + completevar + '>COMPLETED</option><option value="IN PROGRESS" ' + inprogressvar + '>IN PROGRESS</option></select></div>'+
			'<div class="col-md-3">'+
			'<div class="message-content" style="padding-top:6px; color: #46de46;font-weight:600" ><input onchange="changeNotificationStatus(this.value,' + data[i].TASK_ID + ');" class="message-content" type="radio" name="TASK_NOTIFICATION_STATUS_' + data[i].TASK_ID + '" value="1" ' + notificationStatusYes + '> Yes <input onchange="changeNotificationStatus(this.value,' + data[i].TASK_ID + ');" class="message-content" style="margin-left:6px" type="radio" name="TASK_NOTIFICATION_STATUS_' + data[i].TASK_ID + '" value="0" ' + notificationStatusNo + '> No </div>'+
			'</div>'+
			'<div class="col-md-3" style="padding-left:10px;">'+anchortag+'</div>' +
						'</div>'+			
			'</div></div></div>';

				taskList.append(taskListNoRepeat);
				
				*/
				 taskList.append('<div class="mail-list taskDetailsAMe" data-task_id="' + data[i].TASK_ID + '">' +
                    '<div class="content" style="width:97%;">'+
						'<div class="row"><div class="col-md-8"><p class="message_text" '+ strstyle +'>' + data[i].TASK_TITLE + '</p></div><div class="col-md-4"  id="due_personl_rep" style="text-align:right;">'+anchortag+'</div></div>' + 
                    	'<div class="row"><div class="col-md-9"><p class="message_text">'+timeSince(new Date(duedatae))+'</p></div><div class="col-md-3"  id="due_personl_rep" style="text-align:right;">'+notificationButton+'</div></div>'+ 					
					'</div></div>');
			}
            }
			 
			
        } else {
            taskList.append('<div class="error">' + d.MESSAGE + '</div>')
        }
    }
	
	function datediff(first, second) {
    // Take the difference between the dates and divide by milliseconds per day.
    // Round to nearest whole number to deal with DST.
    return Math.round((second-first)/(1000*60*60*24));
}
	 function createOnlyPersonalTaskList(d) {
        taskList.empty();
        if(d.STATUS !== 'ERROR') {
            var data = JSON.parse(d.DATA);
   taskList.append('<div class="mail-list" style="background: grey;color:white;"><h6 style="margin:0px">My Personal Tasks</h6></div>');
            // console.log(data);
			for (var i = 0; i < data.length; i++) {
				taskListRepeat="";
		        taskListNoRepeat="";
				
				var output = "";
		 var repint = data[i].REPEAT_INTERVAL;
		 //if(output == "") output += "Never";
		 var arrIntervals = repint.split(',');
		 var arrayLength = arrIntervals.length;
for (var x = 0; x < arrayLength; x++) {
	switch(arrIntervals[x])
	{
		case '0': 
			output2 += "Mon, ";
			break;
		case '1': 
			output2 += "Tue, ";
			break;
		case '2': 
			output2 += "Wed, ";
			break;
		case '3': 
			output2 += "Thu, ";
			break;
		case '4': 
			output2 += "Fri, ";
			break;
		case '5': 
			output2 += "Sat, ";
			break;
		case '6': 
			output2 += "Sun, ";
			break;
		case '7': 
			output2 += "Month";
			break;
	}
   // console.log();
    //Do something
}

if(repint == "0,1,2,3,4,5,6") output = "Every Day";
if(output == "") output = "Never";

                 var duedatae = convertDate(data[i].DUE_DATE)
				 if(duedatae=="1 Jan 1970 5:0 AM"){duedatae="";}
				 if(duedatae=="31 Dec 1969 6:0 PM"){duedatae="";} // new added by rafiq
				 var currentdate = new Date();
var numberOfDaysToAdd = 2;
currentdate.setDate(currentdate.getDate() + numberOfDaysToAdd); 
var date1 = new Date(currentdate);
var date2 = new Date(duedatae);
				//var strstyle=' style="color:red; text-transform:capitalize" ';
var strstyle='';
if(date1 - date2 < 0 ){strstyle=' style="color:black; font-weight:600; text-transform:capitalize" ';}	
if(date1 - date2 > 0 ){strstyle=' style="color:red; font-weight:600; text-transform:capitalize" '; 
//alert((date1 - date2)+'=='+currentdate+'-'+duedatae);
//alert(datediff(date1, date2)+" = "+date2+"-"+duedatae);

}
if(date1 - date2 > 0 && ((datediff(date1, date2) == -1)||(datediff(date1, date2) == -2)) ){strstyle=' style="color:#ffd80b; font-weight:600; text-transform:capitalize" ';}
if (data[i].TASK_STATUS=="COMPLETED")
{strstyle=' style="color:#2cc62c; font-weight:600; text-transform:capitalize" ';} //green
if(output == "" || duedatae == ""){strstyle=' style="color:black; font-weight:600; text-transform:capitalize" ';}

			 //alert(duedatae);
			if(repint==""){
				//alert('taskListNoRepeat');
                taskListNoRepeat+='<div class="mail-list taskDetails" data-task_id="' + data[i].TASK_ID + '">' +
                    '<div class="content">' +
                    '<p class="message_text" '+strstyle+'>' + data[i].TASK_TITLE + '</p>' +
					'<p class="message_text" style="font-size:12px">' + output + '</p>' +
					'<p class="message_text" style="font-size:12px">' + duedatae + '</p>' +
                    '</div>' +
                    '</div>';
				taskList.append(taskListNoRepeat);
			}
            }
			 taskList.append('<div class="mail-list" style="background: grey;color:white;"><h6 style="margin:0px">Repeat Interval Tasks</h6></div>');
			for (var i = 0; i < data.length; i++) {
				taskListRepeat="";
		        taskListNoRepeat="";
				
				var duedatae = convertDate(data[i].DUE_DATE)
				 if(duedatae=="1 Jan 1970 5:0 AM"){duedatae="";}
				 if(duedatae=="31 Dec 1969 6:0 PM"){duedatae="";} // new added by rafiq
				 var currentdate = new Date();
var numberOfDaysToAdd = 2;
currentdate.setDate(currentdate.getDate() + numberOfDaysToAdd); 
var date1 = new Date(currentdate);
var date2 = new Date(duedatae);
				//var strstyle=' style="color:red; text-transform:capitalize" ';
var strstyle='';
if(date1 - date2 < 0 ){strstyle=' style="color:black; font-weight:600; text-transform:capitalize" ';}	
if(date1 - date2 > 0 ){strstyle=' style="color:red; font-weight:600; text-transform:capitalize" '; 
//alert((date1 - date2)+'=='+currentdate+'-'+duedatae);
//alert(datediff(date1, date2)+" = "+date2+"-"+duedatae);

}
if(date1 - date2 > 0 && ((datediff(date1, date2) == -1)||(datediff(date1, date2) == -2)) ){strstyle=' style="color:#ffd80b; font-weight:600; text-transform:capitalize" ';}
if (data[i].TASK_STATUS=="COMPLETED")
{strstyle=' style="color:#2cc62c; font-weight:600; text-transform:capitalize" ';} //green
if(output == "" || duedatae == ""){strstyle=' style="color:black; font-weight:600; text-transform:capitalize" ';}				
			var output2 = "";
		 var repint = data[i].REPEAT_INTERVAL;
		 //if(output == "") output = "Never";
		 var arrIntervals = repint.split(',');
		 var arrayLength = arrIntervals.length;
for (var x = 0; x < arrayLength; x++) {
	switch(arrIntervals[x])
	{
		case '0': 
			output2 += "Mon, ";
			break;
		case '1': 
			output2 += "Tue, ";
			break;
		case '2': 
			output2 += "Wed, ";
			break;
		case '3': 
			output2 += "Thu, ";
			break;
		case '4': 
			output2 += "Fri, ";
			break;
		case '5': 
			output2 += "Sat, ";
			break;
		case '6': 
			output2 += "Sun, ";
			break;
		case '7': 
			output2 += "Month";
			break;
	}
   // console.log();
    //Do something
}

if(repint == "0,1,2,3,4,5,6") output2 = "Every Day";	
if(output2 == "") output2 = "Never";				
				
			if(!repint==""){
				//alert('taskListNoRepeat');
                taskListNoRepeat+='<div class="mail-list taskDetails" data-task_id="' + data[i].TASK_ID + '">' +
                    '<div class="content">' +
                    '<p class="message_text" '+strstyle+'>' + data[i].TASK_TITLE + '</p>' +
					'<p class="message_text" style="font-size:12px">' + output2 + '</p>' +
					'<p class="message_text" style="font-size:12px">' + convertDate(data[i].DUE_DATE) + '</p>' +
                    '</div>' +
                    '</div>';
				taskList.append(taskListNoRepeat);
			}
			
            }
			
        } else {
            taskList.append('<div class="error">' + d.MESSAGE + '</div>')
        }
    }
    function createAssignMeTaskList(d) {
        taskList.empty();
        if(d.STATUS !== 'ERROR') {
            var data = JSON.parse(d.DATA);
   taskList.append('<div class="mail-list" style="background: grey;color:white;"><h6 style="margin:0px">Assign To Me Tasks</h6></div>');
           //  console.log(data);
			for (var i = 0; i < data.length; i++) {
				taskListRepeat="";
		        taskListNoRepeat="";
	
				
				var output = "";
		 var repint = data[i].REPEAT_INTERVAL;
		 //if(output == "") output += "Never";
		 var arrIntervals = repint.split(',');
		 var arrayLength = arrIntervals.length;
for (var x = 0; x < arrayLength; x++) {
	switch(arrIntervals[x])
	{
		case '0': 
			output += "Every Monday, ";
			break;
		case '1': 
			output += "Every Tuesday, ";
			break;
		case '2': 
			output += "Every Wednesday, ";
			break;
		case '3': 
			output += "Every Thursday, ";
			break;
		case '4': 
			output += "Every Friday, ";
			break;
		case '5': 
			output += "Every Saturday, ";
			break;
		case '6': 
			output += "Every Sunday, ";
			break;
		case '7': 
			output += "Every Month";
			break;
	}
   // console.log();
    //Do something
}

if(repint == "0,1,2,3,4,5,6") output = "Every Day";
if(output == "") output = "Never";

				 
				 var duedatae = data[i].DUE_DATE;
				 //alert(duedatae);
			if(repint==""){
				//alert('taskListNoRepeat');
                taskListNoRepeat+='<div class="mail-list taskDetails" data-task_id="' + data[i].TASK_ID + '">' +
                    '<div class="content">' +
                    '<p class="message_text" style="color:red; text-transform:capitalize">' + data[i].TASK_TITLE + '</p>' +
					'<p class="message_text" style="font-size:12px">' + output + '</p>' +
					'<p class="message_text" style="font-size:12px">' + convertDate(data[i].DUE_DATE) + '</p>' +
                    '</div>' +
                    '</div>';
				taskList.append(taskListNoRepeat);
			}
            }
			 taskList.append('<div class="mail-list" style="background: grey;color:white;"><h6 style="margin:0px">Repeat Interval Tasks</h6></div>');
			for (var i = 0; i < data.length; i++) {
				taskListRepeat="";
		        taskListNoRepeat="";
				
			var output2 = "";
		 var repint = data[i].REPEAT_INTERVAL;
		 //if(output == "") output = "Never";
		 var arrIntervals = repint.split(',');
		 var arrayLength = arrIntervals.length;
for (var x = 0; x < arrayLength; x++) {
	switch(arrIntervals[x])
	{
		case '0': 
			output2 += "Every Monday, ";
			break;
		case '1': 
			output2 += "Every Tuesday, ";
			break;
		case '2': 
			output2 += "Every Wednesday, ";
			break;
		case '3': 
			output2 += "Every Thursday, ";
			break;
		case '4': 
			output2 += "Every Friday, ";
			break;
		case '5': 
			output2 += "Every Saturday, ";
			break;
		case '6': 
			output2 += "Every Sunday, ";
			break;
		case '7': 
			output2 += "Every Month";
			break;
	}
   // console.log();
    //Do something
}

if(repint == "0,1,2,3,4,5,6") output2 = "Every Day";	
if(output2 == "") output2 = "Never";				
				
			if(!repint==""){
				//alert('taskListNoRepeat');
                taskListNoRepeat+='<div class="mail-list taskDetails" data-task_id="' + data[i].TASK_ID + '">' +
                    '<div class="content">' +
                    '<p class="message_text" style="color:red;text-transform:capitalize">' + data[i].TASK_TITLE + '</p>' +
					'<p class="message_text" style="font-size:12px">' + output2 + '</p>' +
					'<p class="message_text" style="font-size:12px">' + convertDate(data[i].DUE_DATE) + '</p>' +
                    '</div>' +
                    '</div>';
				taskList.append(taskListNoRepeat);
			}
			
            }
			
        } else {
            taskList.append('<div class="error">' + d.MESSAGE + '</div>')
        }
    }
	
	
	
function createTaskList(d) {
        taskList.empty();
			

        if(d.STATUS !== 'ERROR') {
            var data = JSON.parse(d.DATA);
   taskList.append('<div class="mail-list" style="background: grey;color:white;"><h6 style="margin:0px">CC Tasks</h6></div>');
            // console.log(data);
			for (var i = 0; i < data.length; i++) {
				taskListRepeat="";
		        taskListNoRepeat="";
				 var repint = data[i].REPEAT_INTERVAL;
				 var duedatae = data[i].DUE_DATE;
				 //alert(duedatae);
				//alert('taskListNoRepeat');
                taskListNoRepeat+='<div class="mail-list taskDetails" data-task_id="' + data[i].TASK_ID + '">' +
                    '<div class="content">' +
                    '<p class="message_text">' + data[i].TASK_TITLE + '</p>' +
                    '</div>' +
                    '</div>';
				taskList.append(taskListNoRepeat);
			
            }
			
        } else {
            taskList.append('<div class="error">' + d.MESSAGE + '</div>')
        }
    }
	function createAssignOthersTaskList(d) {
        taskList.empty();
			

        if(d.STATUS !== 'ERROR') {
            var data = JSON.parse(d.DATA);
   taskList.append('<div class="mail-list" style="background: grey;color:white;"><h6 style="margin:0px">CC Tasks</h6></div>');
          //   console.log(data);
			for (var i = 0; i < data.length; i++) {
				taskListRepeat="";
		        taskListNoRepeat="";
				 var repint = data[i].REPEAT_INTERVAL;
				 var duedatae = data[i].DUE_DATE;
				 //alert(duedatae);
				//alert('taskListNoRepeat');
                taskListNoRepeat+='<div class="mail-list taskDetails" data-task_id="' + data[i].TASK_ID + '">' +
                    '<div class="content">' +
                    '<p class="message_text">' + data[i].TASK_TITLE + '</p>' +
                    '</div>' +
                    '</div>';
				taskList.append(taskListNoRepeat);
			
            }
			
        } else {
            taskList.append('<div class="error">' + d.MESSAGE + '</div>')
        }
    }
	function createCCTaskList(d) {
        taskList.empty();
			

        if(d.STATUS !== 'ERROR') {
            var data = JSON.parse(d.DATA);
   taskList.append('<div class="mail-list" style="background: grey;color:white;"><h6 style="margin:0px">CC Tasks</h6></div>');
            // console.log(data);
			for (var i = 0; i < data.length; i++) {
				taskListRepeat="";
		        taskListNoRepeat="";
				 var output2 = "";
		 var repint = data[i].REPEAT_INTERVAL;
		 //if(output == "") output = "Never";
		 var arrIntervals = repint.split(',');
		 var arrayLength = arrIntervals.length;
for (var x = 0; x < arrayLength; x++) {
	switch(arrIntervals[x])
	{
		case '0': 
			output2 += "Mon, ";
			break;
		case '1': 
			output2 += "Tue, ";
			break;
		case '2': 
			output2 += "Wed, ";
			break;
		case '3': 
			output2 += "Thu, ";
			break;
		case '4': 
			output2 += "Fri, ";
			break;
		case '5': 
			output2 += "Sat, ";
			break;
		case '6': 
			output2 += "Sun, ";
			break;
		case '7': 
			output2 += "Month";
			break;
	}
   // console.log();
    //Do something
}
if(repint == "0,1,2,3,4,5,6") output2 = "Every Day";	
if(output2 == "") output2 = "Never";
				 var duedatae = data[i].DUE_DATE;
				 //alert(duedatae);
				//alert('taskListNoRepeat');
                taskListNoRepeat+='<div class="mail-list taskDetails" data-task_id="' + data[i].TASK_ID + '">' +
                    '<div class="content">' +
                    '<p class="message_text">' + data[i].TASK_TITLE + '</p>' +
					'<p class="message_text" style="font-size:12px">' + output2 + '</p>' +
					'<p class="message_text" style="font-size:12px">' + convertDate(data[i].DUE_DATE) + '</p>' +
					'<p class="message_text" style="font-size:12px">' + data[i].CREATOR_ID + '</p>' +
					'<p class="message_text" style="font-size:12px">' + data[i].ASSIGNED_ID + '</p>' +
                    '</div>' +
                    '</div>';
				taskList.append(taskListNoRepeat);
			
            }
			
        } else {
            taskList.append('<div class="error">' + d.MESSAGE + '</div>')
        }
    }
	
	function showTaskDetailBackOther(p)
	{
	 var id = p;

        $.ajax({
            url: '<?php echo $url; ?>api2/tasks/fetch-details?OBJECT_ID=' + id + '&OBJECT_TYPE=task',
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
                if(data.STATUS === 'SUCCESS') {
					showTaskDetailAssignOthers(data.DATA);
                }
            }
        });	
	}
	

	function showTaskDetailBack(p)
	{
	 var id = p;

        $.ajax({
            url: '<?php echo $url; ?>api2/tasks/fetch-details?OBJECT_ID=' + id + '&OBJECT_TYPE=task',
            type: 'GET',
            dataType: 'json',
            cache: false,
            success: function (data) {
                if(data.STATUS === 'SUCCESS') {
                    showTaskDetail(data.DATA)
                }
            }
        });	
	}

 function showTaskDetailPersonal(d) {
	
        taskDetail.empty();
		//console.log(d);
        var data = JSON.parse(d);
		// console.log(data);
		 var output = "";
		 var repint = data.REPEAT_INTERVAL;
		 var arrIntervals = repint.split(',');
		 var arrayLength = arrIntervals.length;
for (var i = 0; i < arrayLength; i++) {
	switch(arrIntervals[i])
	{
		case '0': 
			output += "Every Monday, ";
			break;
		case '1': 
			output += "Every Tuesday, ";
			break;
		case '2': 
			output += "Every Wednesday, ";
			break;
		case '3': 
			output += "Every Thursday, ";
			break;
		case '4': 
			output += "Every Friday, ";
			break;
		case '5': 
			output += "Every Saturday, ";
			break;
		case '6': 
			output += "Every Sunday, ";
			break;
		case '7': 
			output += "Every Month";
			break;
	}
   // console.log();
    //Do something
}
if(output == "") output += "Never";
if(repint == "0,1,2,3,4,5,6") output = "Every Day";
var duedatae = convertDate(data.DUE_DATE)
				 if(duedatae=="1 Jan 1970 5:0 AM"){duedatae="";}
				 if(duedatae=="31 Dec 1969 6:0 PM"){duedatae="";} // new added by rafiq
var createdatae = convertDate(data.CREATED_DATE)
				 if(createdatae=="1 Jan 1970 5:0 AM"){createdatae="";}				 
				 if(createdatae=="31 Dec 1969 6:0 PM"){createdatae="";} // new added by rafiq

		// console.log(data.IMAGES.length);
		var imgsOutput = "";
		var imgesArr = data.IMAGES;
		for(var aa=0;aa<imgesArr.length;aa++)
		{
			//alert('< ?php echo $url; ?>');
			imgsOutput += '<a data-fancybox="gallery" href="<?php echo $url; ?>'+imgesArr[aa]['TASK_IMAGE']+'"  data-title="Photo" ><img src="<?php echo $url; ?>'+imgesArr[aa]['TASK_IMAGE']+'" class="img-fluid" width="150"></a>&nbsp; ';
		}
		// return false;
		/*
        taskDetail.append('<div class="message-body">' +
            '<div class="sender-details">Task Name</div>' +
            '<div class="message-content">' + data.TASK_TITLE + '</div>' +
            '<div class="sender-details">Status</div>' +
            '<div class="message-content">' +
            '<label class="badge badge-info">' + data.TASK_STATUS + '</label>' +
            '</div>' +
            '<div class="row">' +
			'<div class="col-md-6">' +
			'<div class="row">' +
            '<div class="sender-details col-md-12">Due Date</div>' +
			'<div class="col-md-7" ><div class="message-content">' + duedatae + '</div></div><div class="col-md-5"></div></div>'+
           '</div><div class="col-md-6"><div class="sender-details col-md-12">Created Date</div>'+
		   '<div class="message-content">' + createdatae + '</div></div></div>' +
			'<div class="sender-details">Repeat</div>' +
            '<div class="message-content">' + output + '</div>' +
            '<div class="message-content"><span>Description: </span>' +
            '<div class="description">' + data.TASK_DESCRIPTION + '</div>' +
            '<div class="message-content"><span>Photos: </span>' +
            '<div class="message-content">' + imgsOutput + '</div>' +
            '</div>' +
			 '<div class="message-content"><span> </span>' +
            '</div>' + '<span><button class="btn btn-primary btn-sm" id="btn_editTask" type="button"  value="' + data.TASK_ID + '" >Edit Task</button></span> &nbsp; <span><button class="btn btn-primary btn-sm" id="btn_delTask" type="button"  value="' + data.TASK_ID + '" >Delete Task</button></span>' + 
            '</div>'
        );
		*/
		 taskDetail.append('<div class="message-body">' +
           '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="txt_task_name">Task Name</label><input type="text" class="form-control" id="txt_task_name" placeholder="Task name" value="'+data.TASK_TITLE+'" readonly="readonly" ></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="txt_task_desc">Description</label><textarea class="form-control" id="txt_task_desc" readonly="readonly">'+data.TASK_DESCRIPTION+'</textarea></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-6"><label for="txt_task_status">Status</label><input type="text" class="form-control" id="txt_task_status" placeholder="Task Status" value="'+data.TASK_STATUS+'" readonly="readonly" ></div><div class="col-md-6"><label for="txt_task_repeat">Repeat</label><input type="text" class="form-control" id="txt_task_repeat" placeholder="Task Repeat" value="'+output+'" readonly="readonly" ></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-6"><label for="txt_task_status">Created on</label><input type="text" class="form-control" id="txt_task_created" placeholder="Task Created" value="'+createdatae+'" readonly="readonly" ></div><div class="col-md-6"><label for="txt_task_due">Due on</label><input type="text" class="form-control" id="txt_task_due" placeholder="Task Due On" value="'+duedatae+'" readonly="readonly" ></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="txt_task_desc">Pictures</label></div></div>'+ 
		   '<div class="row" style="margin-top:0px 10px;"><div class="col-md-12"><div style="border:1px solid #ccc; width:100%;">' + imgsOutput + '</div></div></div>' +
		   '<div class="row" style="margin-top:25px;"><div class="col-md-6" style="text-align:center;"><button class="btn btn-primary" style="width:200px;" id="btn_editTask" value="' + data.TASK_ID + '" >Edit Task</button></div><div class="col-md-6" style="text-align:center;"><button class="btn btn-danger" style="width:200px;" id="btn_delTask"  value="' + data.TASK_ID + '" >Delete Task</button></div></div>' +
            '</div>'
        );
    }
    function showTaskDetail(d) {
	
        taskDetail.empty();
		//console.log(d);
        var data = JSON.parse(d);
		// console.log(data);
		 var output = "";
		 var repint = data.REPEAT_INTERVAL;
		 var arrIntervals = repint.split(',');
		 var arrayLength = arrIntervals.length;
for (var i = 0; i < arrayLength; i++) {
	switch(arrIntervals[i])
	{
		case '0': 
			output += "Every Monday, ";
			break;
		case '1': 
			output += "Every Tuesday, ";
			break;
		case '2': 
			output += "Every Wednesday, ";
			break;
		case '3': 
			output += "Every Thursday, ";
			break;
		case '4': 
			output += "Every Friday, ";
			break;
		case '5': 
			output += "Every Saturday, ";
			break;
		case '6': 
			output += "Every Sunday, ";
			break;
		case '7': 
			output += "Every Month";
			break;
	}
   // console.log();
    //Do something
}
if(output == "") output += "Never";
if(repint == "0,1,2,3,4,5,6") output = "Every Day";
var duedatae = convertDate(data.DUE_DATE)
				 if(duedatae=="1 Jan 1970 5:0 AM"){duedatae="";}
				 if(duedatae=="31 Dec 1969 6:0 PM"){duedatae="";} // new added by rafiq
var createdatae = convertDate(data.CREATED_DATE)
				 if(createdatae=="1 Jan 1970 5:0 AM"){createdatae="";}				 
				 if(createdatae=="31 Dec 1969 6:0 PM"){createdatae="";} // new added by rafiq

		// console.log(data.IMAGES.length);
		var imgsOutput = "";
		var imgesArr = data.IMAGES;
		for(var aa=0;aa<imgesArr.length;aa++)
		{
			//alert('< ?php echo $url; ?>');
			imgsOutput += '<a data-fancybox="gallery" href="<?php echo $url; ?>'+imgesArr[aa]['TASK_IMAGE']+'"  data-title="Photo" ><img src="<?php echo $url; ?>'+imgesArr[aa]['TASK_IMAGE']+'" class="img-fluid" width="150"></a>&nbsp; ';
		}
		// return false;
		/*
        taskDetail.append('<div class="message-body">' +
            '<div class="sender-details">Task Name</div>' +
            '<div class="message-content">' + data.TASK_TITLE + '</div>' +
            '<div class="sender-details">Status</div>' +
            '<div class="message-content">' +
            '<label class="badge badge-info">' + data.TASK_STATUS + '</label>' +
            '</div>' +
            '<div class="row">' +
			'<div class="col-md-6">' +
			'<div class="row">' +
            '<div class="sender-details col-md-12">Due Date</div>' +
			'<div class="col-md-7" ><div class="message-content">' + duedatae + '</div></div><div class="col-md-5"></div></div>'+
           '</div><div class="col-md-6"><div class="sender-details col-md-12">Created Date</div>'+
		   '<div class="message-content">' + createdatae + '</div></div></div>' +
			'<div class="sender-details">Repeat</div>' +
            '<div class="message-content">' + output + '</div>' +
            '<div class="message-content"><span>Description: </span>' +
            '<div class="description">' + data.TASK_DESCRIPTION + '</div>' +
            '<div class="message-content"><span>Photos: </span>' +
            '<div class="message-content">' + imgsOutput + '</div>' +
            '</div>' +
			 '<div class="message-content"><span> </span>' +
            '</div>' + '<span><button class="btn btn-primary btn-sm" id="btn_editTask" type="button"  value="' + data.TASK_ID + '" >Edit Task</button></span> &nbsp; <span><button class="btn btn-primary btn-sm" id="btn_delTask" type="button"  value="' + data.TASK_ID + '" >Delete Task</button></span>' + 
            '</div>'
        );
		*/
		 taskDetail.append('<div class="message-body">' +
           '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="txt_task_name">Task Name</label><input type="text" class="form-control" id="txt_task_name" placeholder="Task name" value="'+data.TASK_TITLE+'" readonly="readonly" ></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="txt_task_desc">Description</label><textarea class="form-control" id="txt_task_desc" readonly="readonly">'+data.TASK_DESCRIPTION+'</textarea></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-6"><label for="txt_task_status">Status</label><input type="text" class="form-control" id="txt_task_status" placeholder="Task Status" value="'+data.TASK_STATUS+'" readonly="readonly" ></div><div class="col-md-6"><label for="txt_task_repeat">Repeat</label><input type="text" class="form-control" id="txt_task_repeat" placeholder="Task Repeat" value="'+output+'" readonly="readonly" ></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-6"><label for="txt_task_status">Created on</label><input type="text" class="form-control" id="txt_task_created" placeholder="Task Created" value="'+createdatae+'" readonly="readonly" ></div><div class="col-md-6"><label for="txt_task_due">Due on</label><input type="text" class="form-control" id="txt_task_due" placeholder="Task Due On" value="'+duedatae+'" readonly="readonly" ></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="txt_task_desc">Pictures</label></div></div>'+ 
		   '<div class="row" style="margin-top:0px 10px;"><div class="col-md-12"><div style="border:1px solid #ccc; width:100%;">' + imgsOutput + '</div></div></div>' +
		   '<div class="row" style="margin-top:25px;"><div class="col-md-6" style="text-align:center;"><button class="btn btn-primary" style="width:200px;" id="btn_editTask" value="' + data.TASK_ID + '" >Edit Task</button></div><div class="col-md-6" style="text-align:center;"><button class="btn btn-danger" style="width:200px;" id="btn_delTask"  value="' + data.TASK_ID + '" >Delete Task</button></div></div>' +
            '</div>'
        );
    }
	 function showTaskDetailAssignOthers(d) {
	
        taskDetail.empty();
		//console.log(d);
        var data = JSON.parse(d);
		// console.log(data);
		 var output = "";
		 var repint = data.REPEAT_INTERVAL;
		 var arrIntervals = repint.split(',');
		 var arrayLength = arrIntervals.length;
for (var i = 0; i < arrayLength; i++) {
	switch(arrIntervals[i])
	{
		case '0': 
			output += "Every Monday, ";
			break;
		case '1': 
			output += "Every Tuesday, ";
			break;
		case '2': 
			output += "Every Wednesday, ";
			break;
		case '3': 
			output += "Every Thursday, ";
			break;
		case '4': 
			output += "Every Friday, ";
			break;
		case '5': 
			output += "Every Saturday, ";
			break;
		case '6': 
			output += "Every Sunday, ";
			break;
		case '7': 
			output += "Every Month";
			break;
	}
   // console.log();
    //Do something
}
if(output == "") output += "Never";
if(repint == "0,1,2,3,4,5,6") output = "Every Day";
var duedatae = convertDate(data.DUE_DATE)
				 if(duedatae=="1 Jan 1970 5:0 AM"){duedatae="";}
				 if(duedatae=="31 Dec 1969 6:0 PM"){duedatae="";} // new added by rafiq
var createdatae = convertDate(data.CREATED_DATE)
				 if(createdatae=="1 Jan 1970 5:0 AM"){createdatae="";}				 
				 if(createdatae=="31 Dec 1969 6:0 PM"){createdatae="";} // new added by rafiq

		// console.log(data.IMAGES.length);
		var imgsOutput = "";
		var imgesArr = data.IMAGES;
		imgsOutput += "<table><tr>";
		for(var aa=0;aa<imgesArr.length;aa++)
		{
			//alert('< ?php echo $url; ?>');
			imgsOutput += '<td style="vertical-align:bottom"><table><tr><td><a data-fancybox="gallery" href="<?php echo $url; ?>'+imgesArr[aa]['TASK_IMAGE']+'"  data-title="Photo" ><img src="<?php echo $url; ?>'+imgesArr[aa]['TASK_IMAGE']+'" class="img-fluid" width="150"></a></td></tr></table></td>';
			// delete image //imgsOutput += '<td style="vertical-align:bottom"><table><tr><td><a data-fancybox="gallery" href="< ?php echo $url; ?>'+imgesArr[aa]['TASK_IMAGE']+'"  data-title="Photo" ><img src="< ?php echo $url; ?>'+imgesArr[aa]['TASK_IMAGE']+'" class="img-fluid" width="150"></a></td></tr><tr><td style="text-align:center;"><input type="button" value="Delete Image" class="btn btn-danger btn-sm" /></td></tr></table></td>';
		}
		imgsOutput += "</tr></table>";
		// return false;
		/*
        taskDetail.append('<div class="message-body">' +
            '<div class="sender-details">Task Name</div>' +
            '<div class="message-content">' + data.TASK_TITLE + '</div>' +
            '<div class="sender-details">Status</div>' +
            '<div class="message-content">' +
            '<label class="badge badge-info">' + data.TASK_STATUS + '</label>' +
            '</div>' +
            '<div class="row">' +
			'<div class="col-md-6">' +
			'<div class="row">' +
            '<div class="sender-details col-md-12">Due Date</div>' +
			'<div class="col-md-7" ><div class="message-content">' + duedatae + '</div></div><div class="col-md-5"></div></div>'+
           '</div><div class="col-md-6"><div class="sender-details col-md-12">Created Date</div>'+
		   '<div class="message-content">' + createdatae + '</div></div></div>' +
			'<div class="sender-details">Repeat</div>' +
            '<div class="message-content">' + output + '</div>' +
            '<div class="message-content"><span>Description: </span>' +
            '<div class="description">' + data.TASK_DESCRIPTION + '</div>' +
            '<div class="message-content"><span>Photos: </span>' +
            '<div class="message-content">' + imgsOutput + '</div>' +
            '</div>' +
			 '<div class="message-content"><span> </span>' +
            '</div>' + '<span><button class="btn btn-primary btn-sm" id="btn_editTask" type="button"  value="' + data.TASK_ID + '" >Edit Task</button></span> &nbsp; <span><button class="btn btn-primary btn-sm" id="btn_delTask" type="button"  value="' + data.TASK_ID + '" >Delete Task</button></span>' + 
            '</div>'
        );
		*/
		 taskDetail.append('<div class="message-body">' +
           '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="txt_task_name">Task Name</label><input type="text" class="form-control" id="txt_task_name" placeholder="Task name" value="'+data.TASK_TITLE+'" readonly="readonly" ></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="txt_task_desc">Description</label><textarea class="form-control" id="txt_task_desc" readonly="readonly">'+data.TASK_DESCRIPTION+'</textarea></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-6"><label for="txt_task_status">Status</label><input type="text" class="form-control" id="txt_task_status" placeholder="Task Status" value="'+data.TASK_STATUS+'" readonly="readonly" ></div><div class="col-md-6"><label for="txt_task_repeat">Repeat</label><input type="text" class="form-control" id="txt_task_repeat" placeholder="Task Repeat" value="'+output+'" readonly="readonly" ></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-6"><label for="txt_task_status">Created on</label><input type="text" class="form-control" id="txt_task_created" placeholder="Task Created" value="'+createdatae+'" readonly="readonly" ></div><div class="col-md-6"><label for="txt_task_due">Due on</label><input type="text" class="form-control" id="txt_task_due" placeholder="Task Due On" value="'+duedatae+'" readonly="readonly" ></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="txt_task_desc">Pictures</label></div></div>'+ 
		   '<div class="row" style="margin-top:0px 10px;"><div class="col-md-12"><div style="border:1px solid #ccc; width:100%;">' + imgsOutput + '</div></div></div>' +
		   '<div class="row" style="margin-top:25px;"><div class="col-md-6" style="text-align:center;"><button class="btn btn-primary" style="width:200px;" id="btn_editTaskOther" name="btn_editTaskOther" value="' + data.TASK_ID + '" >Edit Task</button></div><div class="col-md-6" style="text-align:center;"><button class="btn btn-danger" style="width:200px;" id="btn_delTask"  value="' + data.TASK_ID + '" >Delete Task</button></div></div>' +
            '</div>'
        );
    }
	
	 function showTaskDetailAMe(d) {
	
        taskDetail.empty();
		//console.log(d);
        var data = JSON.parse(d);
		// console.log(data);
		 var output = "";
		 var repint = data.REPEAT_INTERVAL;
		 var arrIntervals = repint.split(',');
		 var arrayLength = arrIntervals.length;
for (var i = 0; i < arrayLength; i++) {
	switch(arrIntervals[i])
	{
		case '0': 
			output += "Every Monday, ";
			break;
		case '1': 
			output += "Every Tuesday, ";
			break;
		case '2': 
			output += "Every Wednesday, ";
			break;
		case '3': 
			output += "Every Thursday, ";
			break;
		case '4': 
			output += "Every Friday, ";
			break;
		case '5': 
			output += "Every Saturday, ";
			break;
		case '6': 
			output += "Every Sunday, ";
			break;
		case '7': 
			output += "Every Month";
			break;
	}
   // console.log();
    //Do something
}
if(output == "") output += "Never";
if(repint == "0,1,2,3,4,5,6") output = "Every Day";
var duedatae = convertDate(data.DUE_DATE)
				 if(duedatae=="1 Jan 1970 5:0 AM"){duedatae="";}
				 if(duedatae=="31 Dec 1969 6:0 PM"){duedatae="";} // new added by rafiq
var createdatae = convertDate(data.CREATED_DATE)
				 if(createdatae=="1 Jan 1970 5:0 AM"){createdatae="";}	
				 if(createdatae=="31 Dec 1969 6:0 PM"){createdatae="";} // new added by rafiq			 

		// console.log(data.IMAGES.length);
		var imgsOutput = "";
		var imgesArr = data.IMAGES;
		for(var aa=0;aa<imgesArr.length;aa++)
		{
			//alert('< ?php echo $url; ?>');
			imgsOutput += '<a data-fancybox="gallery" href="<?php echo $url; ?>'+imgesArr[aa]['TASK_IMAGE']+'" data-title="Photo" ><img src="<?php echo $url; ?>'+imgesArr[aa]['TASK_IMAGE']+'" class="img-fluid" width="150"></a>&nbsp; ';
		}
		// return false;
        /*
		taskDetail.append('<div class="message-body">' +
            '<div class="sender-details">Task Name</div>' +
            '<div class="message-content">' + data.TASK_TITLE + '</div>' +
            '<div class="sender-details">Status</div>' +
            '<div class="message-content">' +
            '<label class="badge badge-info">' + data.TASK_STATUS + '</label>' +
            '</div>' +
            '<div class="row">' +
			'<div class="col-md-6">' +
			'<div class="row">' +
            '<div class="sender-details col-md-12">Due Date</div>' +
			'<div class="col-md-7" ><div class="message-content">' + duedatae + '</div></div><div class="col-md-5"></div></div>'+
           '</div><div class="col-md-6"><div class="sender-details col-md-12">Created Date</div>'+
		   '<div class="message-content">' + createdatae + '</div></div></div>' +
			'<div class="sender-details">Repeat</div>' +
            '<div class="message-content">' + output + '</div>' +
            '<div class="message-content"><span>Description: </span>' +
            '<div class="description">' + data.TASK_DESCRIPTION + '</div>' +
            '<div class="message-content"><span>Photos: </span>' +
            '<div class="message-content">' + imgsOutput + '</div>' +
            '</div>' +
			 '<div class="message-content"><span> </span>' +
            '</div>' + '<span></span>' + 
            '</div>'
        );
		*/
		 taskDetail.append('<div class="message-body">' +
           '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="txt_task_name">Task Name</label><input type="text" class="form-control" id="txt_task_name" placeholder="Task name" value="'+data.TASK_TITLE+'" readonly="readonly" ></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="txt_task_desc">Description</label><textarea class="form-control" id="txt_task_desc" readonly="readonly">'+data.TASK_DESCRIPTION+'</textarea></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-6"><label for="txt_task_status">Status</label><input type="text" class="form-control" id="txt_task_status" placeholder="Task Status" value="'+data.TASK_STATUS+'" readonly="readonly" ></div><div class="col-md-6"><label for="txt_task_repeat">Repeat</label><input type="text" class="form-control" id="txt_task_repeat" placeholder="Task Repeat" value="'+output+'" readonly="readonly" ></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-6"><label for="txt_task_status">Created on</label><input type="text" class="form-control" id="txt_task_created" placeholder="Task Created" value="'+createdatae+'" readonly="readonly" ></div><div class="col-md-6"><label for="txt_task_due">Due on</label><input type="text" class="form-control" id="txt_task_due" placeholder="Task Due On" value="'+duedatae+'" readonly="readonly" ></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="txt_task_desc">Pictures</label></div></div>'+ 
		   '<div class="row" style="margin-top:0px 10px;"><div class="col-md-12"><div style="border:1px solid #ccc; width:100%;">' + imgsOutput + '</div></div></div>' +
		   '<div class="row" style="margin-top:25px;"><div class="col-md-12" style="text-align:center;"><button class="btn btn-danger" style="width:200px;" id="btn_delTask"  value="' + data.TASK_ID + '" >Delete Task</button></div></div>' +
            '</div>'
        );
		
		
		
    }
	
	
	function showTaskDetailDDassignMe(d) {
        taskDetail.empty();
		//console.log(d);
        var data = JSON.parse(d);
		 //console.log(data);
		 var output = "";
		 var repint = data.REPEAT_INTERVAL;
		 var arrIntervals = repint.split(',');
		 var arrayLength = arrIntervals.length;
for (var i = 0; i < arrayLength; i++) {
	switch(arrIntervals[i])
	{
		case '0': 
			output += "Every Monday, ";
			break;
		case '1': 
			output += "Every Tuesday, ";
			break;
		case '2': 
			output += "Every Wednesday, ";
			break;
		case '3': 
			output += "Every Thursday, ";
			break;
		case '4': 
			output += "Every Friday, ";
			break;
		case '5': 
			output += "Every Saturday, ";
			break;
		case '6': 
			output += "Every Sunday, ";
			break;
		case '7': 
			output += "Every Month";
			break;
	}
   // console.log();
    //Do something
}
if(output == "") output += "Never";
if(repint == "0,1,2,3,4,5,6") output = "Every Day";


var imgsOutput = "";
		var imgesArr = data.IMAGES;
		for(var aa=0;aa<imgesArr.length;aa++)
		{
			//alert('< ?php echo $url; ?>');
			imgsOutput += '<a data-fancybox="gallery" href="<?php echo $url; ?>'+imgesArr[aa]['TASK_IMAGE']+'"  data-title="Photo" ><img src="<?php echo $url; ?>'+imgesArr[aa]['TASK_IMAGE']+'" class="img-fluid" width="150"></a>&nbsp; ';
		}
		
var duedatae = convertDate(data.CREATED_DATE);
				 if(duedatae=="1 Jan 1970 5:0 AM"){duedatae="";}
				 if(duedatae=="31 Dec 1969 6:0 PM"){duedatae="";} // new added by rafiq
var createdatae = convertDate(data.DUE_DATE);
				 if(createdatae=="1 Jan 1970 5:0 AM"){createdatae="";}
				 if(createdatae=="31 Dec 1969 6:0 PM"){createdatae="";} // new added by rafiq
		 var openvar = "";
		  var closevar = "";
		   var completevar = "";
		    var inprogressvar = "";
 switch(data.TASK_STATUS)
		 {
			 case "OPEN":
			 		openvar = " selected='selected'";
			 	break;
				 case "CLOSED":
			 		closevar = " selected='selected'";
			 	break;
				 case "COMPLETED":
			 		completevar = " selected='selected'";
			 	break;
				 case "IN PROGRESS":
			 		inprogressvar = " selected='selected'";
			 	break;
		 }
		 var notificationStatusYes="";
		 var notificationStatusNo="";
		 //alert('h '+ data.TASK_NOTIFICATION_STATUS);
		 if(data.TASK_NOTIFICATION_STATUS==1){notificationStatusYes = " checked"}
		 else{notificationStatusNo = " checked"}
		// console.log(arrIntervals);
		// return false;
        taskDetail.append('<div class="message-body">' +
            '<div class="sender-details">Task Name</div>' +
            '<div class="message-content">' + data.TASK_TITLE + '</div>' +
			'<div class="row">' +
			'<div class="col-md-6">' +
			'<div class="row">' +
            '<div class="sender-details col-md-12">Status</div>' +
			'<div class="form-group message-content col-md-7" ><select class="custom-select browser-default" required="" id="TASK_STATUS" name="TASK_STATUS" onchange="changeStatus(this.value,' + data.TASK_ID + ');"><option value="OPEN" ' + openvar + ' >OPEN</option><option value="CLOSED" ' + closevar + '>CLOSED</option><option value="COMPLETED" ' + completevar + '>COMPLETED</option><option value="IN PROGRESS" ' + inprogressvar + '>IN PROGRESS</option></select></div><div class="col-md-5"></div></div>'+
           '</div><div class="col-md-6" style="padding:0px"><div class="sender-details col-md-12">Send Notification</div>'+
		   '<div class="message-content"><input onchange="changeNotificationStatus(this.value,' + data.TASK_ID + ');" class="message-content" type="radio" name="TASK_NOTIFICATION_STATUS" value="1" ' + notificationStatusYes + '> Yes <input onchange="changeNotificationStatus(this.value,' + data.TASK_ID + ');" class="message-content" style="margin-left:20px" type="radio" name="TASK_NOTIFICATION_STATUS" value="0" ' + notificationStatusNo + '> No </div></div></div>' +
		   '<div class="row">' +
			'<div class="col-md-6">' +
			'<div class="row">' +
            '<div class="sender-details col-md-12">Due Date</div>' +
			'<div class="col-md-7" ><div class="message-content">' + duedatae + '</div></div><div class="col-md-5"></div></div>'+
           '</div><div class="col-md-6"><div class="sender-details col-md-12">Created Date</div>'+
		   '<div class="message-content">' + createdatae + '</div></div></div>' +
			'<div class="sender-details">Repeat</div>' +
            '<div class="message-content">' + output + '</div>' +
            '<div class="message-content"><span>Description: </span>' +
            '<div class="description">' + data.TASK_DESCRIPTION + '</div>' +
            '<div class="message-content"><span>Photos: </span>' +
            '<div class="message-content">' + imgsOutput + '</div>' +

            '</div>' +
			 '<div class="message-content"><span> </span>' +
            '</div>'+ 


            '</div>'
        );
    }
	
	function showTaskDetailDDCCtask(d) {
        taskDetail.empty();
		//console.log(d);
        var data = JSON.parse(d);
		// console.log(data);
		 var output = "";
		 var repint = data.REPEAT_INTERVAL;
		 var arrIntervals = repint.split(',');
		 var arrayLength = arrIntervals.length;
for (var i = 0; i < arrayLength; i++) {
	switch(arrIntervals[i])
	{
		case '0': 
			output += "Every Monday, ";
			break;
		case '1': 
			output += "Every Tuesday, ";
			break;
		case '2': 
			output += "Every Wednesday, ";
			break;
		case '3': 
			output += "Every Thursday, ";
			break;
		case '4': 
			output += "Every Friday, ";
			break;
		case '5': 
			output += "Every Saturday, ";
			break;
		case '6': 
			output += "Every Sunday, ";
			break;
		case '7': 
			output += "Every Month";
			break;
	}
   // console.log();
    //Do something
}
if(output == "") output += "Never";
if(repint == "0,1,2,3,4,5,6") output = "Every Day";
var duedatae = convertDate(data.DUE_DATE)
				 if(duedatae=="1 Jan 1970 5:0 AM"){duedatae="";}
				 if(duedatae=="31 Dec 1969 6:0 PM"){duedatae="";} // new added by rafiq
var createdatae = convertDate(data.CREATED_DATE);
				 if(createdatae=="1 Jan 1970 5:0 AM"){createdatae="";}
				 if(createdatae=="31 Dec 1969 6:0 PM"){createdatae="";} // new added by rafiq
		 var openvar = "";
		  var closevar = "";
		   var completevar = "";
		    var inprogressvar = "";
 switch(data.TASK_STATUS)
		 {
			 case "OPEN":
			 		openvar = " selected='selected'";
			 	break;
				 case "CLOSED":
			 		closevar = " selected='selected'";
			 	break;
				 case "COMPLETED":
			 		completevar = " selected='selected'";
			 	break;
				 case "IN PROGRESS":
			 		inprogressvar = " selected='selected'";
			 	break;
		 }
		 var notificationStatusYes="";
		 var notificationStatusNo="";
		 //alert('h '+ data.TASK_NOTIFICATION_STATUS);
		 if(data.TASK_NOTIFICATION_STATUS==1){notificationStatusYes = " checked"}
		 else{notificationStatusNo = " checked"}
		// console.log(arrIntervals);
		// return false;
		
		var imgsOutput = "";
		var imgesArr = data.IMAGES;
		for(var aa=0;aa<imgesArr.length;aa++)
		{
			//alert('< ?php echo $url; ?>');
			imgsOutput += '<a data-fancybox="gallery" href="<?php echo $url; ?>'+imgesArr[aa]['TASK_IMAGE']+'" data-title="Photo" ><img src="<?php echo $url; ?>'+imgesArr[aa]['TASK_IMAGE']+'" class="img-fluid" width="150"></a>&nbsp; ';
		}
		
		 taskDetail.append('<div class="message-body">' +
           '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="txt_task_name">Task Name</label><input type="text" class="form-control" id="txt_task_name" placeholder="Task name" value="'+data.TASK_TITLE+'" readonly="readonly" ></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="txt_task_desc">Description</label><textarea class="form-control" id="txt_task_desc" readonly="readonly">'+data.TASK_DESCRIPTION+'</textarea></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-6"><label for="txt_task_status">Status</label><input type="text" class="form-control" id="txt_task_status" placeholder="Task Status" value="'+data.TASK_STATUS+'" readonly="readonly" ></div><div class="col-md-6"><label for="txt_task_repeat">Repeat</label><input type="text" class="form-control" id="txt_task_repeat" placeholder="Task Repeat" value="'+output+'" readonly="readonly" ></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-6"><label for="txt_task_status">Created on</label><input type="text" class="form-control" id="txt_task_created" placeholder="Task Created" value="'+createdatae+'" readonly="readonly" ></div><div class="col-md-6"><label for="txt_task_due">Due on</label><input type="text" class="form-control" id="txt_task_due" placeholder="Task Due On" value="'+duedatae+'" readonly="readonly" ></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="txt_task_desc">Pictures</label></div></div>'+ 
		   '<div class="row" style="margin-top:0px 10px;"><div class="col-md-12"><div style="border:1px solid #ccc; width:100%;">' + imgsOutput + '</div></div></div>' +
		   '<div class="row" style="margin-top:25px;"><div class="col-md-12" style="text-align:center;"><button class="btn btn-danger" style="width:200px;" id="btn_delTask"  value="' + data.TASK_ID + '" >Delete Task</button></div></div>' +
            '</div>'
        );
		/*
        taskDetail.append('<div class="message-body">' +
            '<div class="sender-details">Task Name</div>' +
            '<div class="message-content">' + data.TASK_TITLE + '</div>' +
			'<div class="row">' +
            '<div class="sender-details col-md-12">Status</div>' +
			'<div class="form-group message-content col-md-3" ><select class="custom-select browser-default" required="" id="TASK_STATUS" name="TASK_STATUS" onchange="changeStatus(this.value,' + data.TASK_ID + ');"><option value="OPEN" ' + openvar + ' >OPEN</option><option value="CLOSED" ' + closevar + '>CLOSED</option><option value="COMPLETED" ' + completevar + '>COMPLETED</option><option value="IN PROGRESS" ' + inprogressvar + '>IN PROGRESS</option></select></div><div class="col-md-9"></div>'+
           '<div class="col-md-6">'+
		   '</div></div>' +
		   '<div class="row">' +
			'<div class="col-md-6">' +
			'<div class="row">' +
            '<div class="sender-details col-md-12">Due Date</div>' +
			'<div class="col-md-7" ><div class="message-content">' + duedatae + '</div></div><div class="col-md-5"></div></div>'+
           '</div><div class="col-md-6"><div class="sender-details col-md-12">Created Date</div>'+
		   '<div class="message-content">' + createdatae + '</div></div></div>' +
			'<div class="sender-details">Repeat</div>' +
            '<div class="message-content">' + output + '</div>' +
            '<div class="message-content"><span>Description: </span>' +
            '<div class="description">' + data.TASK_DESCRIPTION + '</div>' +
			'<div class="message-content"><span>Photos: </span>' +
            '<div class="message-content">' + imgsOutput + '</div>' +
            '</div>' +
			 '<div class="message-content"><span> </span>' +
            '</div>'+ 

            '</div>'
        );
		*/
    }
	
<?php /*?>	
	function showTaskDetailDDPersonal(d) {
	
        taskDetail.empty();
		//console.log(d);
        var data = JSON.parse(d);
		// console.log(data);
		 var output = "";
		 var repint = data.REPEAT_INTERVAL;
		 var arrIntervals = repint.split(',');
		 var arrayLength = arrIntervals.length;
for (var i = 0; i < arrayLength; i++) {
	switch(arrIntervals[i])
	{
		case '0': 
			output += "Every Monday, ";
			break;
		case '1': 
			output += "Every Tuesday, ";
			break;
		case '2': 
			output += "Every Wednesday, ";
			break;
		case '3': 
			output += "Every Thursday, ";
			break;
		case '4': 
			output += "Every Friday, ";
			break;
		case '5': 
			output += "Every Saturday, ";
			break;
		case '6': 
			output += "Every Sunday, ";
			break;
		case '7': 
			output += "Every Month";
			break;
	}
   // console.log();
    //Do something
}
if(output == "") output += "Never";
if(repint == "0,1,2,3,4,5,6") output = "Every Day";
var duedatae = convertDate(data.DUE_DATE)
var createdatae = convertDate(data.CREATED_DATE)
				 if(duedatae=="1 Jan 1970 5:0 AM"){duedatae="";}
				 if(duedatae=="31 Dec 1969 6:0 PM"){duedatae="";} // new added by rafiq
		 var openvar = "";
		  var closevar = "";
		   var completevar = "";
		    var inprogressvar = "";
 switch(data.TASK_STATUS)
		 {
			 case "OPEN":
			 		openvar = " selected='selected'";
			 	break;
				 case "CLOSED":
			 		closevar = " selected='selected'";
			 	break;
				 case "COMPLETED":
			 		completevar = " selected='selected'";
			 	break;
				 case "IN PROGRESS":
			 		inprogressvar = " selected='selected'";
			 	break;
		 }
		 var notificationStatusYes="";
		 var notificationStatusNo="";
		 //alert('h '+ data.TASK_NOTIFICATION_STATUS);
		 if(data.TASK_NOTIFICATION_STATUS==1){notificationStatusYes = " checked"}
		 else{notificationStatusNo = " checked"}
		// console.log(arrIntervals);
		// return false;
		
		var imgsOutput = "";
		var imgesArr = data.IMAGES;
		for(var aa=0;aa<imgesArr.length;aa++)
		{
			//alert('< ?php echo $url; ?>');
			imgsOutput += '<a href="<?php echo $url; ?>'+imgesArr[aa]['TASK_IMAGE']+'" data-toggle="lightbox" data-title="Photo" ><img src="<?php echo $url; ?>'+imgesArr[aa]['TASK_IMAGE']+'" class="img-fluid" width="150"></a>&nbsp; ';
		}
        taskDetail.append('<div class="message-body">' +
            '<div class="sender-details">Task Name</div>' +
            '<div class="message-content">' + data.TASK_TITLE + '</div>' +
			'<div class="row">' +
			'<div class="col-md-6">' +
			'<div class="row">' +
            '<div class="sender-details col-md-12">Status</div>' +
			'<div class="form-group message-content col-md-7" ><select class="custom-select browser-default" required="" id="TASK_STATUS" name="TASK_STATUS" onchange="changeStatus(this.value,' + data.TASK_ID + ');"><option value="OPEN" ' + openvar + ' >OPEN</option><option value="CLOSED" ' + closevar + '>CLOSED</option><option value="COMPLETED" ' + completevar + '>COMPLETED</option><option value="IN PROGRESS" ' + inprogressvar + '>IN PROGRESS</option></select></div><div class="col-md-5"></div></div>'+
           '</div><div class="col-md-6"><div class="sender-details col-md-12">Send Notification</div>'+
		   '<div class="message-content"><input onchange="changeNotificationStatus(this.value,' + data.TASK_ID + ');" class="message-content" type="radio" name="TASK_NOTIFICATION_STATUS" value="1" ' + notificationStatusYes + '> Yes <input onchange="changeNotificationStatus(this.value,' + data.TASK_ID + ');" class="message-content" style="margin-left:20px" type="radio" name="TASK_NOTIFICATION_STATUS" value="0" ' + notificationStatusNo + '> No </div></div></div>' +
		   '<div class="row">' +
			'<div class="col-md-6">' +
			'<div class="row">' +
            '<div class="sender-details col-md-12">Due Date</div>' +
			'<div class="col-md-7" ><div class="message-content">' + duedatae + '</div></div><div class="col-md-5"></div></div>'+
           '</div><div class="col-md-6"><div class="sender-details col-md-12">Created Date</div>'+
		   '<div class="message-content">' + createdatae + '</div></div></div>' +
			'<div class="sender-details">Repeat</div>' +
            '<div class="message-content">' + output + '</div>' +
            '<div class="message-content"><span>Description: </span>' +
            '<div class="description">' + data.TASK_DESCRIPTION + '</div>' +
			'<div class="message-content"><span>Photos: </span>' +
            '<div class="message-content">' + imgsOutput + '</div>' +
            '</div>' +
			 '<div class="message-content"><span> </span>' +
            '</div>' + '<span><button class="btn btn-primary btn-sm" id="btn_editTask" type="button"  value="' + data.TASK_ID + '" >Edit Task</button></span> &nbsp; <span><button class="btn btn-primary btn-sm" id="btn_delTask" type="button"  value="' + data.TASK_ID + '" >Delete Task</button></span>' + 
            '</div>'
        );
    }
	
<?php */?>	
//changeNotificationStatusPNRT

function changeNotificationStatusPNRT(p,s)
	{
		//alert(p +' '+ s);
var TASK_ID  = s;
var USER_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
var TASK_STATUS = p;
//alert('line 313: staus'+TASK_STATUS);	
$.ajax({
       url: '<?php echo $url; ?>api2/tasks/notification-status',
        type: 'POST',
		contentType:'application/x-www-form-urlencoded',
        data:"TASK_ID="+TASK_ID+"&USER_ID="+USER_ID+"&STATUS="+TASK_STATUS,
		error: function(err) {
            alert(err.statusText);
        },
        success: function(data) {
			//alert(data.MESSAGE);  // change mage here notification status by rafiq     
		TaskPersonalNoRepFunction();				
		}
		});

	
	}
//changeNotificationStatusPRRT

function changeNotificationStatusPRRT(p,s)
	{
		//alert(p +' '+ s);
var TASK_ID  = s;
var USER_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
var TASK_STATUS = p;
//alert('line 313: staus'+TASK_STATUS);	
$.ajax({
       url: '<?php echo $url; ?>api2/tasks/notification-status',
        type: 'POST',
		contentType:'application/x-www-form-urlencoded',
        data:"TASK_ID="+TASK_ID+"&USER_ID="+USER_ID+"&STATUS="+TASK_STATUS,
		error: function(err) {
            alert(err.statusText);
        },
        success: function(data) {
			//alert(data.MESSAGE);  // change mage here notification status by rafiq     
		TaskPersonalRepFunction();				
		}
		});

	
	}



function changeNotificationStatusPersonalTDT(p,s)
	{
		//alert(p +' '+ s);
var TASK_ID  = s;
var USER_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
var TASK_STATUS = p;
//alert('line 313: staus'+TASK_STATUS);	
$.ajax({
       url: '<?php echo $url; ?>api2/tasks/notification-status',
        type: 'POST',
		contentType:'application/x-www-form-urlencoded',
        data:"TASK_ID="+TASK_ID+"&USER_ID="+USER_ID+"&STATUS="+TASK_STATUS,
		error: function(err) {
            alert(err.statusText);
        },
        success: function(data) {
			//alert(data.MESSAGE);  // change mage here notification status by rafiq     
		TaskPersonalFunction();				
		}
		});

	
	}

function changeNotificationStatus(p,s)
	{
		//alert(p +' '+ s);
var TASK_ID  = s;
var USER_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
var TASK_STATUS = p;
//alert('line 313: staus'+TASK_STATUS);	
$.ajax({
       url: '<?php echo $url; ?>api2/tasks/notification-status',
        type: 'POST',
		contentType:'application/x-www-form-urlencoded',
        data:"TASK_ID="+TASK_ID+"&USER_ID="+USER_ID+"&STATUS="+TASK_STATUS,
		error: function(err) {
            alert(err.statusText);
        },
        success: function(data) {
			//alert(data.MESSAGE);  // change mage here notification status by rafiq     
		dueTaskPersonalRepFunction();				
		}
		});

	
	}

	function changeNotificationStatusAMRT(cid,p,s)
	{
		//alert(p +' '+ s);
var TASK_ID  = s;
var USER_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
var TASK_STATUS = p;
//alert('line 313: staus'+TASK_STATUS);	
$.ajax({
       url: '<?php echo $url; ?>api2/tasks/notification-status',
        type: 'POST',
		contentType:'application/x-www-form-urlencoded',
        data:"TASK_ID="+TASK_ID+"&USER_ID="+USER_ID+"&STATUS="+TASK_STATUS,
		error: function(err) {
            alert(err.statusText);
        },
        success: function(data) {
			//alert(data.MESSAGE);  // change mage here notification status by rafiq     
		funGetTaskAssignedMeTaskUsersRep(cid);	
		}
		});

	
	}
		
function changeNotificationStatusAMNRT(cid,p,s)
	{
		//alert(p +' '+ s);
var TASK_ID  = s;
var USER_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
var TASK_STATUS = p;
//alert('line 313: staus'+TASK_STATUS);	
$.ajax({
       url: '<?php echo $url; ?>api2/tasks/notification-status',
        type: 'POST',
		contentType:'application/x-www-form-urlencoded',
        data:"TASK_ID="+TASK_ID+"&USER_ID="+USER_ID+"&STATUS="+TASK_STATUS,
		error: function(err) {
            alert(err.statusText);
        },
        success: function(data) {
			//alert(data.MESSAGE);  // change mage here notification status by rafiq     
		funGetTaskAssignedMeTaskUsersDue(cid);	
		}
		});

	
	}
		
function changeNotificationStatusAMTDT(cid,p,s)
	{
		//alert(p +' '+ s);
var TASK_ID  = s;
var USER_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
var TASK_STATUS = p;
//alert('line 313: staus'+TASK_STATUS);	
$.ajax({
       url: '<?php echo $url; ?>api2/tasks/notification-status',
        type: 'POST',
		contentType:'application/x-www-form-urlencoded',
        data:"TASK_ID="+TASK_ID+"&USER_ID="+USER_ID+"&STATUS="+TASK_STATUS,
		error: function(err) {
            alert(err.statusText);
        },
        success: function(data) {
			//alert(data.MESSAGE);  // change mage here notification status by rafiq     
		funGetTaskAssignedMeTaskUsers(cid);	
		}
		});

	
	}
	
function changeNotificationStatusDueAsMe(cid,p,s)
	{
		//alert(p +' '+ s);
var TASK_ID  = s;
var USER_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
var TASK_STATUS = p;
//alert('line 313: staus'+TASK_STATUS);	
$.ajax({
       url: '<?php echo $url; ?>api2/tasks/notification-status',
        type: 'POST',
		contentType:'application/x-www-form-urlencoded',
        data:"TASK_ID="+TASK_ID+"&USER_ID="+USER_ID+"&STATUS="+TASK_STATUS,
		error: function(err) {
            alert(err.statusText);
        },
        success: function(data) {
			//alert(data.MESSAGE);  // change mage here notification status by rafiq     
		funGetTaskAssignedMeUsersDue(cid);	
		}
		});

	
	}
	
	
	
	
	function changeNotificationStatusDMNR(p,s)
	{
		//alert(p +' '+ s);
var TASK_ID  = s;
var USER_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
var TASK_STATUS = p;
//alert('line 313: staus'+TASK_STATUS);	
$.ajax({
       url: '<?php echo $url; ?>api2/tasks/notification-status',
        type: 'POST',
		contentType:'application/x-www-form-urlencoded',
        data:"TASK_ID="+TASK_ID+"&USER_ID="+USER_ID+"&STATUS="+TASK_STATUS,
		error: function(err) {
            alert(err.statusText);
        },
        success: function(data) {
			//alert(data.MESSAGE);  // change mage here notification status by rafiq     
		dueTaskPersonalDueFunction();
		}
		});

	
	}

function changeStatusProjectTask(p,s)
	{
var TASK_ID  = s;
var TASK_STATUS = document.frm_editStatusProjectTask.TASK_STATUS.value;
//alert('line 313'+TASK_STATUS);	
$.ajax({
       url: '<?php echo $url; ?>api2/tasks/update-status',
        type: 'POST',
		contentType:'application/x-www-form-urlencoded',
        data:"TASK_ID="+TASK_ID+"&STATUS="+TASK_STATUS,
		error: function(err) {
            alert(err.statusText);
        },
        success: function(data) {
			//alert('Status Successfully Changed!');	 // wait for it		
			//TaskPersonalNoRepFunction();
			//taskDetail.empty();
			showProjectTaskDetail(<?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:"0"; ?>,TASK_ID);
		}
		});

	}
	//changeProjectTaskAll
	function changeProjectTaskAll(p,s)
	{
var TASK_ID  = s;
var TASK_TITLE = document.frm_editProjectTaskAll.validationCustom01.value;
var TASK_DESCRIPTION = document.frm_editProjectTaskAll.validationCustom03.value;
var TASK_STATUS = document.frm_editProjectTaskAll.TASK_STATUS.value;
	var dat = $('#validationCustom02').val(); //document.frm_newIndivisualTask.validationCustom02.value;
	var tim = $('#txttime').val();//document.frm_newIndivisualTask.txttime.value;
	var  DUE_DATE_DT = "";
	var DUE_DATE = "";
	if(dat != "")
	{
		DUE_DATE_DT = dat+' '+tim+':00';
		if(tim == "") {  DUE_DATE_DT = dat+' 00:00:00'; }
		DUE_DATE = toTimestamp(DUE_DATE_DT);
	}
if(TASK_TITLE == "") { alert('Task Title Missing!'); return false;}

//alert('line 313'+TASK_STATUS);	
$.ajax({
       url: '<?php echo $url; ?>api2/tasks/update-project-task',
        type: 'POST',
		contentType:'application/x-www-form-urlencoded',
        data:"TASK_ID="+TASK_ID+"&STATUS="+TASK_STATUS+"&DUE_DATE="+DUE_DATE+"&DUE_DATE_DT="+DUE_DATE_DT+"&TASK_TITLE="+TASK_TITLE+"&TASK_DESCRIPTION="+TASK_DESCRIPTION,
		error: function(err) {
            alert(err.statusText);
        },
        success: function(data) {
			//alert('Status Successfully Changed!');	 // wait for it		
			//TaskPersonalNoRepFunction();
			//taskDetail.empty();
			showProjectTaskDetail(<?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:"0"; ?>,TASK_ID);
		}
		});

	}

function changeProjectSubTaskAll(p,s)
	{
var TASK_ID  = s;
var TASK_TITLE = document.frm_editProjectTaskAll.validationCustom01.value;
var TASK_DESCRIPTION = document.frm_editProjectTaskAll.validationCustom03.value;
var TASK_STATUS = document.frm_editProjectTaskAll.TASK_STATUS.value;
	var dat = $('#validationCustom02').val(); //document.frm_newIndivisualTask.validationCustom02.value;
	var tim = $('#txttime').val();//document.frm_newIndivisualTask.txttime.value;
	var  DUE_DATE_DT = "";
	var DUE_DATE = "";
	if(dat != "")
	{
		DUE_DATE_DT = dat+' '+tim+':00';
		if(tim == "") {  DUE_DATE_DT = dat+' 00:00:00'; }
		DUE_DATE = toTimestamp(DUE_DATE_DT);
	}
if(TASK_TITLE == "") { alert('Task Title Missing!'); return false;}

//alert('line 313'+TASK_STATUS);	
$.ajax({
       url: '<?php echo $url; ?>api2/tasks/update-project-task',
        type: 'POST',
		contentType:'application/x-www-form-urlencoded',
        data:"TASK_ID="+TASK_ID+"&STATUS="+TASK_STATUS+"&DUE_DATE="+DUE_DATE+"&DUE_DATE_DT="+DUE_DATE_DT+"&TASK_TITLE="+TASK_TITLE+"&TASK_DESCRIPTION="+TASK_DESCRIPTION,
		error: function(err) {
            alert(err.statusText);
        },
        success: function(data) {
			//alert('Status Successfully Changed!');	 // wait for it		
			//TaskPersonalNoRepFunction();
			//taskDetail.empty();
			showProjectSubTaskDetail(<?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:"0"; ?>,TASK_ID);
		}
		});

	}

	//
	function changeStatusDPRR(p,s)
	{
		//alert(p);
var TASK_ID  = s;
var CREATOR_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
var ASSIGNED_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
var TASK_STATUS = p;
//alert('line 313'+TASK_STATUS);	
$.ajax({
       url: '<?php echo $url; ?>api2/tasks/update-status',
        type: 'POST',
		contentType:'application/x-www-form-urlencoded',
        data:"TASK_ID="+TASK_ID+"&CREATOR_ID="+CREATOR_ID+"&ASSIGNED_ID="+ASSIGNED_ID+"&STATUS="+TASK_STATUS,
		error: function(err) {
            alert(err.statusText);
        },
        success: function(data) {
			//alert(data.MESSAGE);
			if(p=='COMPLETED' || p=='CLOSED')
           {
			   	//taskList.empty();
				taskDetail.empty();
				$("div.taskDetails[data-task_id*="+ TASK_ID + "]").remove();
				$('div#taskDetails div.message-body').attr("style","visibility:hidden; display:none");
		$('div#taskDetails').append(" <br /><p>Selected Task Status Updated Successfully!</p>");	
		
			if($("div.taskDetailsAMe[data-task_id*="+ TASK_ID + "]"))
				{//taskDetailsAMe
					$("div.taskDetailsAMe[data-task_id*="+ TASK_ID + "]").remove();
				}
				if($("div.taskDetails[data-task_id*="+ TASK_ID + "]"))
				{//taskDetails
					$("div.taskDetails[data-task_id*="+ TASK_ID + "]").remove();
				}
				if($("div.taskDetailsCCtaskDD[data-task_id*="+ TASK_ID + "]"))
				{//taskDetailsCCtaskDD
					$("div.taskDetailsCCtaskDD[data-task_id*="+ TASK_ID + "]").remove();
				}
				//taskDetailsProj
				if($("div.taskDetailsProj[data-task_id*="+ TASK_ID + "]"))
				{//taskDetailsCCtaskDD
					$("div.taskDetailsProj[data-task_id*="+ TASK_ID + "]").remove();
				}
				if($("div.taskDetailsPersonal[data-task_id*="+ TASK_ID + "]"))
				{//taskDetailsCCtaskDD
					$("div.taskDetailsPersonal[data-task_id*="+ TASK_ID + "]").remove();
				}
				if($("div.taskDetailsAssignOthers[data-task_id*="+ TASK_ID + "]"))
				{//taskDetailsCCtaskDD
					$("div.taskDetailsAssignOthers[data-task_id*="+ TASK_ID + "]").remove();
				}
        		//activeTab($(this), 0);
			}
			else
			{
				// new code here
				taskDetail.empty();
				dueTaskPersonalRepFunction();
			}
		}
		});

	}

	//
	function changeStatusDPNR(p,s)
	{
		//alert(p);
var TASK_ID  = s;
var CREATOR_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
var ASSIGNED_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
var TASK_STATUS = p;
//alert('line 313'+TASK_STATUS);	
$.ajax({
       url: '<?php echo $url; ?>api2/tasks/update-status',
        type: 'POST',
		contentType:'application/x-www-form-urlencoded',
        data:"TASK_ID="+TASK_ID+"&CREATOR_ID="+CREATOR_ID+"&ASSIGNED_ID="+ASSIGNED_ID+"&STATUS="+TASK_STATUS,
		error: function(err) {
            alert(err.statusText);
        },
        success: function(data) {
			//alert(data.MESSAGE);
			if(p=='COMPLETED' || p=='CLOSED')
           {
			   	//taskList.empty();
				taskDetail.empty();
				$("div.taskDetails[data-task_id*="+ TASK_ID + "]").remove();
				$('div#taskDetails div.message-body').attr("style","visibility:hidden; display:none");
		$('div#taskDetails').append(" <br /><p>Selected Task Status Updated Successfully!</p>");	
		if($("div.taskDetailsAMe[data-task_id*="+ TASK_ID + "]"))
				{//taskDetailsAMe
					$("div.taskDetailsAMe[data-task_id*="+ TASK_ID + "]").remove();
				}
				if($("div.taskDetails[data-task_id*="+ TASK_ID + "]"))
				{//taskDetails
					$("div.taskDetails[data-task_id*="+ TASK_ID + "]").remove();
				}
				if($("div.taskDetailsCCtaskDD[data-task_id*="+ TASK_ID + "]"))
				{//taskDetailsCCtaskDD
					$("div.taskDetailsCCtaskDD[data-task_id*="+ TASK_ID + "]").remove();
				}
				//taskDetailsProj
				if($("div.taskDetailsProj[data-task_id*="+ TASK_ID + "]"))
				{//taskDetailsCCtaskDD
					$("div.taskDetailsProj[data-task_id*="+ TASK_ID + "]").remove();
				}
				if($("div.taskDetailsPersonal[data-task_id*="+ TASK_ID + "]"))
				{//taskDetailsCCtaskDD
					$("div.taskDetailsPersonal[data-task_id*="+ TASK_ID + "]").remove();
				}
				if($("div.taskDetailsAssignOthers[data-task_id*="+ TASK_ID + "]"))
				{//taskDetailsCCtaskDD
					$("div.taskDetailsAssignOthers[data-task_id*="+ TASK_ID + "]").remove();
				}
        		//activeTab($(this), 0);
				dueTaskPersonalDueFunction();
			}
			else
			{
				// new code here
				taskDetail.empty();
				dueTaskPersonalDueFunction();
			}
		}
		});

	}


	function changeStatusDD(p,s)
	{
		//alert(p);
var TASK_ID  = s;
var CREATOR_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
var ASSIGNED_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
var TASK_STATUS = p;
//alert('line 313'+TASK_STATUS);	
$.ajax({
       url: '<?php echo $url; ?>api2/tasks/update-status',
        type: 'POST',
		contentType:'application/x-www-form-urlencoded',
        data:"TASK_ID="+TASK_ID+"&CREATOR_ID="+CREATOR_ID+"&ASSIGNED_ID="+ASSIGNED_ID+"&STATUS="+TASK_STATUS,
		error: function(err) {
            alert(err.statusText);
        },
        success: function(data) {
			//alert(data.MESSAGE);
			if(p=='COMPLETED' || p=='CLOSED')
           {
			   	//taskList.empty();
				taskDetail.empty();
				$("div.taskDetails[data-task_id*="+ TASK_ID + "]").remove();
				$('div#taskDetails div.message-body').attr("style","visibility:hidden; display:none");
		$('div#taskDetails').append(" <br /><p>Selected Task Status Updated Successfully!</p>");	
		if($("div.taskDetailsAMe[data-task_id*="+ TASK_ID + "]"))
				{//taskDetailsAMe
					$("div.taskDetailsAMe[data-task_id*="+ TASK_ID + "]").remove();
				}
				if($("div.taskDetails[data-task_id*="+ TASK_ID + "]"))
				{//taskDetails
					$("div.taskDetails[data-task_id*="+ TASK_ID + "]").remove();
				}
				if($("div.taskDetailsCCtaskDD[data-task_id*="+ TASK_ID + "]"))
				{//taskDetailsCCtaskDD
					$("div.taskDetailsCCtaskDD[data-task_id*="+ TASK_ID + "]").remove();
				}
				//taskDetailsProj
				if($("div.taskDetailsProj[data-task_id*="+ TASK_ID + "]"))
				{//taskDetailsCCtaskDD
					$("div.taskDetailsProj[data-task_id*="+ TASK_ID + "]").remove();
				}
				if($("div.taskDetailsPersonal[data-task_id*="+ TASK_ID + "]"))
				{//taskDetailsCCtaskDD
					$("div.taskDetailsPersonal[data-task_id*="+ TASK_ID + "]").remove();
				}
				if($("div.taskDetailsAssignOthers[data-task_id*="+ TASK_ID + "]"))
				{//taskDetailsCCtaskDD
					$("div.taskDetailsAssignOthers[data-task_id*="+ TASK_ID + "]").remove();
				}
        		//activeTab($(this), 0);
			}
		}
		});

	}
	function changeStatusAMRT(p,s, userid)
	{
		//alert(p);
var TASK_ID  = s;
var CREATOR_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
var ASSIGNED_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
var TASK_STATUS = p;
//alert('line 313'+TASK_STATUS);	
$.ajax({
       url: '<?php echo $url; ?>api2/tasks/update-status',
        type: 'POST',
		contentType:'application/x-www-form-urlencoded',
        data:"TASK_ID="+TASK_ID+"&CREATOR_ID="+CREATOR_ID+"&ASSIGNED_ID="+ASSIGNED_ID+"&STATUS="+TASK_STATUS,
		error: function(err) {
            alert(err.statusText);
        },
        success: function(data) {
			//alert('Status Successfully Changed!');	 // wait for it rafiq		
			funGetTaskAssignedMeTaskUsersRep(userid);
			taskDetail.empty();
		}
		});

	}

	function changeStatusAMTDT(p,s, userid)
	{
		//alert(p);
var TASK_ID  = s;
var CREATOR_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
var ASSIGNED_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
var TASK_STATUS = p;
//alert('line 313'+TASK_STATUS);	
$.ajax({
       url: '<?php echo $url; ?>api2/tasks/update-status',
        type: 'POST',
		contentType:'application/x-www-form-urlencoded',
        data:"TASK_ID="+TASK_ID+"&CREATOR_ID="+CREATOR_ID+"&ASSIGNED_ID="+ASSIGNED_ID+"&STATUS="+TASK_STATUS,
		error: function(err) {
            alert(err.statusText);
        },
        success: function(data) {
			//alert('Status Successfully Changed!');	 // wait for it rafiq		
			funGetTaskAssignedMeTaskUsers(userid);
			taskDetail.empty();
		}
		});

	}
	function changeStatusAMD(p,s, userid)
	{
		//alert(p);
var TASK_ID  = s;
var CREATOR_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
var ASSIGNED_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
var TASK_STATUS = p;
//alert('line 313'+TASK_STATUS);	
$.ajax({
       url: '<?php echo $url; ?>api2/tasks/update-status',
        type: 'POST',
		contentType:'application/x-www-form-urlencoded',
        data:"TASK_ID="+TASK_ID+"&CREATOR_ID="+CREATOR_ID+"&ASSIGNED_ID="+ASSIGNED_ID+"&STATUS="+TASK_STATUS,
		error: function(err) {
            alert(err.statusText);
        },
        success: function(data) {
			//alert('Status Successfully Changed!');	 // wait for it rafiq		
			funGetTaskAssignedMeTaskUsers(userid);
			taskDetail.empty();
		}
		});

	}

	function changeStatusAMDNR(p,s, userid)
	{
		//alert(p);
var TASK_ID  = s;
var CREATOR_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
var ASSIGNED_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
var TASK_STATUS = p;
//alert('line 313'+TASK_STATUS);	
$.ajax({
       url: '<?php echo $url; ?>api2/tasks/update-status',
        type: 'POST',
		contentType:'application/x-www-form-urlencoded',
        data:"TASK_ID="+TASK_ID+"&CREATOR_ID="+CREATOR_ID+"&ASSIGNED_ID="+ASSIGNED_ID+"&STATUS="+TASK_STATUS,
		error: function(err) {
            alert(err.statusText);
        },
        success: function(data) {
			//alert('Status Successfully Changed!');	 // wait for it rafiq		
			funGetTaskAssignedMeTaskUsersDue(userid);
			taskDetail.empty();
		}
		});

	}

	function changeStatusAMDNRT(p,s, userid)
	{
		//alert(p);
var TASK_ID  = s;
var CREATOR_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
var ASSIGNED_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
var TASK_STATUS = p;
//alert('line 313'+TASK_STATUS);	
$.ajax({
       url: '<?php echo $url; ?>api2/tasks/update-status',
        type: 'POST',
		contentType:'application/x-www-form-urlencoded',
        data:"TASK_ID="+TASK_ID+"&CREATOR_ID="+CREATOR_ID+"&ASSIGNED_ID="+ASSIGNED_ID+"&STATUS="+TASK_STATUS,
		error: function(err) {
            alert(err.statusText);
        },
        success: function(data) {
			//alert('Status Successfully Changed!');	 // wait for it rafiq		
			funGetTaskAssignedMeTaskUsersDue(userid);
			taskDetail.empty();
		}
		});

	}


	function changeStatusAMDRR(p,s, userid)
	{
		//alert(p);
var TASK_ID  = s;
var CREATOR_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
var ASSIGNED_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
var TASK_STATUS = p;
//alert('line 313'+TASK_STATUS);	
$.ajax({
       url: '<?php echo $url; ?>api2/tasks/update-status',
        type: 'POST',
		contentType:'application/x-www-form-urlencoded',
        data:"TASK_ID="+TASK_ID+"&CREATOR_ID="+CREATOR_ID+"&ASSIGNED_ID="+ASSIGNED_ID+"&STATUS="+TASK_STATUS,
		error: function(err) {
            alert(err.statusText);
        },
        success: function(data) {
			//alert('Status Successfully Changed!');	 // wait for it rafiq		
			funGetTaskAssignedMeTaskUsersRep(userid);
			taskDetail.empty();
		}
		});

	}
	function changeStatusPersonalTDT(p,s)
	{
		//alert(p);
var TASK_ID  = s;
var CREATOR_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
var ASSIGNED_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
var TASK_STATUS = p;
//alert('line 313'+TASK_STATUS);	
$.ajax({
       url: '<?php echo $url; ?>api2/tasks/update-status',
        type: 'POST',
		contentType:'application/x-www-form-urlencoded',
        data:"TASK_ID="+TASK_ID+"&CREATOR_ID="+CREATOR_ID+"&ASSIGNED_ID="+ASSIGNED_ID+"&STATUS="+TASK_STATUS,
		error: function(err) {
            alert(err.statusText);
        },
        success: function(data) {
			//alert('Status Successfully Changed!');	 // wait for it rafiq		
			TaskPersonalFunction();
			taskDetail.empty();
		}
		});

	}

	function changeStatusPersonalSearched(p,s)
	{
		//alert(p);
var TASK_ID  = s;
var CREATOR_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
var ASSIGNED_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
var TASK_STATUS = p;
//alert('line 313'+TASK_STATUS);	
$.ajax({
       url: '<?php echo $url; ?>api2/tasks/update-status',
        type: 'POST',
		contentType:'application/x-www-form-urlencoded',
        data:"TASK_ID="+TASK_ID+"&CREATOR_ID="+CREATOR_ID+"&ASSIGNED_ID="+ASSIGNED_ID+"&STATUS="+TASK_STATUS,
		error: function(err) {
            alert(err.statusText);
        },
        success: function(data) {
			//markascompleted2164
			//alert($('#markascompleted'+s).attr('src'));
			if($('#markascompleted'+s).attr('src') == 'assets/images/mark-completed.gif')
			{
				$('#markascompleted'+s).attr('src','assets/images/completed-task.gif');
			}
			//alert('Status Successfully Changed!');	 // wait for it rafiq		
			//TaskPersonalFunction(); rafiqrafiq
			taskDetail.empty();
		}
		});

	}


	function changeStatus(p,s)
	{
		//alert(p);
var TASK_ID  = s;
var CREATOR_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
var ASSIGNED_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
var TASK_STATUS = p;
//alert('line 313'+TASK_STATUS);	
$.ajax({
       url: '<?php echo $url; ?>api2/tasks/update-status',
        type: 'POST',
		contentType:'application/x-www-form-urlencoded',
        data:"TASK_ID="+TASK_ID+"&CREATOR_ID="+CREATOR_ID+"&ASSIGNED_ID="+ASSIGNED_ID+"&STATUS="+TASK_STATUS,
		error: function(err) {
            alert(err.statusText);
        },
        success: function(data) {
			alert('Status Successfully Changed!');	 // wait for it		
		}
		});

	}

	function changeStatusPTD(p,s)
	{
		//alert(p);
var TASK_ID  = s;
var CREATOR_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
var ASSIGNED_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
var TASK_STATUS = p;
//alert('line 313'+TASK_STATUS);	
$.ajax({
       url: '<?php echo $url; ?>api2/tasks/update-status',
        type: 'POST',
		contentType:'application/x-www-form-urlencoded',
        data:"TASK_ID="+TASK_ID+"&CREATOR_ID="+CREATOR_ID+"&ASSIGNED_ID="+ASSIGNED_ID+"&STATUS="+TASK_STATUS,
		error: function(err) {
            alert(err.statusText);
        },
        success: function(data) {
			//alert('Status Successfully Changed!');	 // wait for it		
			TaskPersonalFunction();
			taskDetail.empty();
		}
		});

	}

	
	function changeStatusAssignOthersNR(p,s, userid)
	{
		//alert(p);
var TASK_ID  = s;
var CREATOR_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
var ASSIGNED_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
var TASK_STATUS = p;
//alert('line 313'+TASK_STATUS);	
$.ajax({
       url: '<?php echo $url; ?>api2/tasks/update-status',
        type: 'POST',
		contentType:'application/x-www-form-urlencoded',
        data:"TASK_ID="+TASK_ID+"&CREATOR_ID="+CREATOR_ID+"&ASSIGNED_ID="+ASSIGNED_ID+"&STATUS="+TASK_STATUS,
		error: function(err) {
            alert(err.statusText);
        },
        success: function(data) {
			//alert('Status Successfully Changed!');	 // wait for it rafiq		
			funGetTaskAssignedOtherUsersTaskDue(userid);
			taskDetail.empty();
		}
		});

	}
	function changeStatusAssignOthersRR(p,s, userid)
	{
		//alert(p);
var TASK_ID  = s;
var CREATOR_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
var ASSIGNED_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
var TASK_STATUS = p;
//alert('line 313'+TASK_STATUS);	
$.ajax({
       url: '<?php echo $url; ?>api2/tasks/update-status',
        type: 'POST',
		contentType:'application/x-www-form-urlencoded',
        data:"TASK_ID="+TASK_ID+"&CREATOR_ID="+CREATOR_ID+"&ASSIGNED_ID="+ASSIGNED_ID+"&STATUS="+TASK_STATUS,
		error: function(err) {
            alert(err.statusText);
        },
        success: function(data) {
			//alert('Status Successfully Changed!');	 // wait for it rafiq		
			funGetTaskAssignedOtherUsersTaskRep(userid);
			taskDetail.empty();
		}
		});

	}
	function changeStatusAssignOthersTD(p,s, userid)
	{
		//alert(p);
var TASK_ID  = s;
var CREATOR_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
var ASSIGNED_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
var TASK_STATUS = p;
//alert('line 313'+TASK_STATUS);	
$.ajax({
       url: '<?php echo $url; ?>api2/tasks/update-status',
        type: 'POST',
		contentType:'application/x-www-form-urlencoded',
        data:"TASK_ID="+TASK_ID+"&CREATOR_ID="+CREATOR_ID+"&ASSIGNED_ID="+ASSIGNED_ID+"&STATUS="+TASK_STATUS,
		error: function(err) {
            alert(err.statusText);
        },
        success: function(data) {
			//alert('Status Successfully Changed!');	 // wait for it rafiq		
			funGetTaskAssignedOtherUsersTask(userid);
			taskDetail.empty();
		}
		});

	}
	
	function changeStatusPNR(p,s)
	{
		//alert(p);
var TASK_ID  = s;
var CREATOR_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
var ASSIGNED_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
var TASK_STATUS = p;
//alert('line 313'+TASK_STATUS);	
$.ajax({
       url: '<?php echo $url; ?>api2/tasks/update-status',
        type: 'POST',
		contentType:'application/x-www-form-urlencoded',
        data:"TASK_ID="+TASK_ID+"&CREATOR_ID="+CREATOR_ID+"&ASSIGNED_ID="+ASSIGNED_ID+"&STATUS="+TASK_STATUS,
		error: function(err) {
            alert(err.statusText);
        },
        success: function(data) {
			//alert('Status Successfully Changed!');	 // wait for it		
			TaskPersonalNoRepFunction();
			taskDetail.empty();
		}
		});

	}

	function changeStatusPRR(p,s)
	{
		//alert(p);
var TASK_ID  = s;
var CREATOR_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
var ASSIGNED_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
var TASK_STATUS = p;
//alert('line 313'+TASK_STATUS);	
$.ajax({
       url: '<?php echo $url; ?>api2/tasks/update-status',
        type: 'POST',
		contentType:'application/x-www-form-urlencoded',
        data:"TASK_ID="+TASK_ID+"&CREATOR_ID="+CREATOR_ID+"&ASSIGNED_ID="+ASSIGNED_ID+"&STATUS="+TASK_STATUS,
		error: function(err) {
            alert(err.statusText);
        },
        success: function(data) {
			//alert('Status Successfully Changed!');	 // wait for it		
			TaskPersonalRepFunction();
			taskDetail.empty();
		}
		});

	}
	 function showEditTaskDetailOther(d) {
	 var data = JSON.parse(d);
	// alert(data.TASK_STATUS);
		d1 = new Date(convertDate(data.DUE_DATE)); //CREATED_DATE
		var dayr = ("0" + d1.getDate()).slice(-2);
		var monr = ("0" + (d1.getMonth()+1)).slice(-2);
		var fullyear = d1.getFullYear();
		
		
		finaldate = [fullyear,monr,dayr].join('-');
		var d = new Date(); // for now
           var hrs = d1.getHours(); // => 9
		   var mits = d1.getMinutes(); // =>  30
	       var secs = d1.getSeconds(); // => 51
		    if(finaldate=="1970-01-01"){finaldate="";}
		   
		   var createdatae = convertDate(data.CREATED_DATE);
		
				 if(createdatae=="1 Jan 1970 5:0 AM"){createdatae="";}				 
				 if(createdatae=="31 Dec 1969 6:0 PM"){createdatae="";} // new added by rafiq
		   
		   hrs = ('0' + hrs).slice(-2);
		   mits = ('0' + mits).slice(-2);
		   
		   finaltime = [hrs,mits,].join(':');
		
		
		
		var output = "";
		 var repint = data.REPEAT_INTERVAL;
		 var arrIntervals = repint.split(',');
		 var arrayLength = arrIntervals.length;
		 var mondaycheck = "";
		 var tuesdaycheck = "";
		 var wednesdaycheck = "";
		 var thursdaycheck = "";
		 var fridaycheck = "";
		 var saturdaycheck = "";
		 var sundaycheck = "";
		 var monthcheck = "";
		 var everydaycheck = "";
		 var openvar = "";
		  var closevar = "";
		   var completevar = "";
		    var inprogressvar = "";
		 switch(data.TASK_STATUS)
		 {
			 case "OPEN":
			 		openvar = " selected='selected'";
			 	break;
				 case "CLOSED":
			 		closevar = " selected='selected'";
			 	break;
				 case "COMPLETED":
			 		completevar = " selected='selected'";
			 	break;
				 case "IN PROGRESS":
			 		inprogressvar = " selected='selected'";
			 	break;
		 }
for (var i = 0; i < arrayLength; i++) {
	switch(arrIntervals[i])
	{
		case '0': 
		   //document.getElementById("everyMonday").checked = true;
			mondaycheck = " checked='checked'";
			break;
		case '1': 
			tuesdaycheck = " checked='checked'";
			break;
		case '2': 
			wednesdaycheck = " checked='checked'";
			break;
		case '3': 
			thursdaycheck = " checked='checked'";
			break;
		case '4': 
			fridaycheck = " checked='checked'";
			break;
		case '5': 
			saturdaycheck = " checked='checked'";
			break;
		case '6': 
			sundaycheck = " checked='checked'";
			break;
		case '7': 
			monthcheck = " checked='checked'";
			break;
	}
// console.log(data);
 //return;
    //Do something
}
//if(output == "") output += "Never";
if(repint == "0,1,2,3,4,5,6"){ everydaycheck = " checked='checked'";}
var cc_data = '';

if (data.CC != null) {
cc_data = data.CC;
}
		
		taskDetail.empty();
	taskDetail.append('<div class="message-body"><div class="container-fluid" id="xyz2"><form  id="frm_editPersonalOTask" name="frm_editPersonalOTask" enctype="multipart/form-data" method="post" onsubmit="return false;"><input type="hidden" id="ASSIGNED_ID" name="ASSIGNED_ID" value="'+data.ASSIGNED_ID+'" /><input type="hidden" id="CC" name="CC" value="'+cc_data+'" />' +			
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><h2>Edit Assign to Other Task <button class="btn btn-primary btn-sm" id="btnp2" type="button" onclick="funSetCurDateTime();" >Set Priority</button></h2></div></div>' +
           '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="validationCustom01">Task Name</label><input type="text" class="form-control" id="validationCustom01" name=validationCustom01" placeholder="Task name" value="'+data.TASK_TITLE+'" required><div class="valid-feedback">Looks good!</div></div></div>' +
   		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="validationCustom03">Description</label><textarea class="form-control" id="validationCustom03" name="validationCustom03" >' + data.TASK_DESCRIPTION + '</textarea><div class="invalid-feedback">Please provide description.</div></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-6"><label for="validationCustom02">Due Date </label><input type="date" class="form-control" id="validationCustom02" name="validationCustom02" value="' + finaldate + '" ><div class="valid-feedback">Looks good!</div></div><div class="col-md-6"><label for="txttime">Due Time</label><input type="time" class="form-control" id="txttime" name="txttime" value="' + finaltime + '" ></div></div>' +		   
		   '<div class="row" style="margin-top:15px;"><div class="col-md-6"><label for="txt_task_status">Created on</label><input type="text" class="form-control" id="txt_task_created" placeholder="" value="'+createdatae+'" readonly="readonly" ><div class="valid-feedback">Looks good!</div></div><div class="col-md-6"><label for="statusNow">Status</label><select class="custom-select browser-default" required="" id="TASK_STATUS" name="TASK_STATUS" ><option value="">Select</option><option value="OPEN" '+openvar+'>OPEN</option><option value="CLOSED" '+closevar+'>CLOSED</option><option value="COMPLETED" '+completevar+'>COMPLETED</option><option value="IN PROGRESS" '+inprogressvar+'>IN PROGRESS</option></select></div></div>' +		   
		   '<div class="row" style="margin-top:15px;"><div class="col-md-6"><label for="TASK_IMAGES">Pictures</label> <br /><input type="file" name="TASK_IMAGES[]" multiple ></div><div class="col-md-6"><label for="Creator">Creator</label><input type="text" class="form-control" id="Creator" placeholder="Creator" value="<?php echo $_SESSION['logged_in']['FULL_NAME']; ?>" readonly></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="repeat">Repeat Task</label></div></div>' +
		   '<div class="row" style="margin-top:5px;"><div class="col-md-12"><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input" id="selectall" value="0,1,2,3,4,5,6" '+everydaycheck+'><label class="custom-control-label" for="selectall">Every Day</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyMonday" value="0" '+mondaycheck+'><label class="custom-control-label" for="everyMonday">Every Monday</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;" style="width:200px; float:left;"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyTuesday" value="1" '+tuesdaycheck+'><label class="custom-control-label" for="everyTuesday">Every Tuesday</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyWednesday" value="2" '+wednesdaycheck+'><label class="custom-control-label" for="everyWednesday">Every Wednesday</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyThursday" value="3" '+thursdaycheck+'><label class="custom-control-label" for="everyThursday">Every Thursday</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyFriday" value="4" '+fridaycheck+'><label class="custom-control-label" for="everyFriday">Every Friday</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input" name="funnel[]" id="everySaturday" value="5" '+saturdaycheck+'><label class="custom-control-label" for="everySaturday">Every Saturday</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input" name="funnel[]" id="everySunday" value="6" '+sundaycheck+'><label class="custom-control-label" for="everySunday">Every Sunday</label></div></div></div>'+ 
		   		   '<div class="row" style="margin-top:10px;"><div class="col-md-12"><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input" name="funnel[]"  id="everyMonth" value="7" '+monthcheck+'><label class="custom-control-label" for="everyMonth">Every Month</label></div></div></div>'+
		   '<div class="row" style="margin-top:25px;"><div class="col-md-6" style="text-align:center;"><button class="btn btn-primary" type="submit" style="width:200px;" id="btn_updateOtherTask" name="btn_updateOtherTask" value="'+data.TASK_ID+'" >Update Assign Other Task</button></div><div class="col-md-6" style="text-align:center;"><button class="btn btn-info" type="button" style="width:200px;" id="btn_CancelTask" name="btn_CancelTask" onclick="showTaskDetailBackOther('+data.TASK_ID+');" >Cancel Update</button></div></div>' +		   
            '</div></form></div>'
        );
		
		/*		
		
		//console.log(finaldate);
		taskDetail.empty();
  taskDetail.append('<div class="container-fluid"><h2>Edit Personal Task</h2><form  id="frm_editPersonalTask" name="frm_editPersonalTask" enctype="multipart/form-data" method="post" onsubmit="return false;">'+
  '<div class="form-row"><div class="col-md-6 mb-3">'+
 '<label for="validationCustom01">Task Name</label><input type="text" class="form-control" id="validationCustom01" placeholder="Task name" value="'+data.TASK_TITLE+'" required>'+
 '<div class="valid-feedback">Looks good!</div></div><div class="col-md-6 mb-3">'+
 '<div class="form-group"><label for="statusNow">Status</label><select class="custom-select browser-default" required="" id="TASK_STATUS" name="TASK_STATUS" disabled><option value="">Select</option><option value="OPEN" '+openvar+'>OPEN</option><option value="CLOSED" '+closevar+'>CLOSED</option><option value="COMPLETED" '+completevar+'>COMPLETED</option><option value="IN PROGRESS" '+inprogressvar+'>IN PROGRESS</option></select></div></div></div>'+
 '<div class="form-row"><div class="col-md-6 mb-3"><label for="validationCustom02">Due Date </label>'+
'<input type="date" class="form-control" id="validationCustom02" value="' + finaldate + '" required>'+
      '<div class="valid-feedback">Looks good!</div></div><div class="col-md-6 mb-3"><label for="validationCustom02">Select Time</label><input type="time" class="form-control" id="txttime" placeholder="Select  Time" value="' + finaltime + '" required> </div></div>'+
  '<div class="form-row"><div class="col-md-12 mb-3">'+
  '<label for="validationCustom03">Description</label>'+
  '<textarea type="text" class="form-control" id="validationCustom03" placeholder="Add a TO-DO here" required>' + data.TASK_DESCRIPTION + '</textarea>'+
  '<div class="invalid-feedback">Please provide description.</div></div>'+
  '</div><div class="form-row"><div class="col-md-12 mb-3">'+
 '<input type="file" class="filepond" name="filepond" multiple data-allow-reorder="true" data-max-file-size="3MB" data-max-files="10">'+
 '</div></div><div class="form-row"><div class="col-md-2"> </div>'+
  '<div class="col-md-3"><div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input" id="selectall" value="0,1,2,3,4,5,6" '+everydaycheck+'>'+
  '<label class="custom-control-label" for="selectall">Every Day</label></div></div>'+
 '<div class="col-md-3"><div class="custom-control custom-checkbox">'+
 '<input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyMonday" value="0" '+mondaycheck+'>'+
' <label class="custom-control-label" for="everyMonday">Every Monday</label></div></div>'+
  '<div class="col-md-3"><div class="custom-control custom-checkbox">'+
  '<input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyTuesday" value="1" '+tuesdaycheck+'>'+
 ' <label class="custom-control-label" for="everyTuesday">Every Tuesday</label>'+
'</div></div><div class="col-md-1"> </div></div><div class="form-row"><div class="col-md-2"> </div><div class="col-md-3"> '+
'<div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyWednesday" value="2" '+wednesdaycheck+'>'+
'<label class="custom-control-label" for="everyWednesday">Every Wednesday</label>'+
  '</div></div><div class="col-md-3"> '+
'<div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyThursday" value="3" '+thursdaycheck+'>'+
'<label class="custom-control-label" for="everyThursday">Every Thursday</label>'+
  '</div></div><div class="col-md-3"><div class="custom-control custom-checkbox">'+
    '<input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyFriday" value="4" '+fridaycheck+'>'+
    '<label class="custom-control-label" for="everyFriday">Every Friday</label></div></div><div class="col-md-1"> </div></div>'+
  '<div class="form-row"><div class="col-md-2 mb-3"> </div><div class="col-md-3 mb-3"><div class="custom-control custom-checkbox">'+
   '<input type="checkbox" class="custom-control-input" name="funnel[]" id="everySaturday" value="5" '+saturdaycheck+'>'+
    '<label class="custom-control-label" for="everySaturday">Every Saturday</label></div></div><div class="col-md-3 mb-3"> '+
'<div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input" name="funnel[]" id="everySunday" value="6" '+sundaycheck+'>'+
    '<label class="custom-control-label" for="everySunday">Every Sunday</label>'+
  '</div></div><div class="col-md-3 mb-3"><div class="custom-control custom-checkbox">'+
     '<input type="checkbox" class="custom-control-input" name="funnel[]"  id="everyMonth" value="7" '+monthcheck+'>'+
   ' <label class="custom-control-label" for="everyMonth">Every Month</label>'+
  '</div></div><div class="col-md-1 mb-3"> </div>'+
  '</div><div class="form-row"><div class="col-md-2 mb-3"> </div><div class="col-md-8 mb-3"><div class="form-group">'+
  '<button class="btn btn-primary btn-sm" id="btn_updateTask" type="submit" value="'+data.TASK_ID+'" >Update Task</button> &nbsp; <button class="btn btn-primary btn-sm" id="btn_CancelTask" type="button" onclick="showTaskDetailBack('+data.TASK_ID+');" >Cancel</button></div></div><div class="col-md-2 mb-3"> </div></div></form></div>');
 */
  
 // $('[readonly]').prop( "disabled", true );
  
window.addEventListener("click", function(event) {
  var checkboxes = document.getElementsByName('funnel[]'),
    selectall = document.getElementById('selectall');
  for (var i = 0; i < checkboxes.length; i++) {
    checkboxes[i].addEventListener('change', function() {
      //Conver to array
      var inputList = Array.prototype.slice.call(checkboxes);

      //Set checked  property of selectall input
      selectall.checked = inputList.every(function(c) {
        return c.checked;
      });
    });
  }
  if(selectall){
  selectall.addEventListener('change', function() {
    for (var i = 0; i < checkboxes.length; i++) {
      checkboxes[i].checked = selectall.checked;
    }
  });
  }
});
    
  $("#validationCustom02").val(finaldate);

   
    
	
    }
	
	 function showEditTaskDetail(d) {
	 var data = JSON.parse(d);
	// alert(data.TASK_STATUS);
		d1 = new Date(convertDate(data.DUE_DATE));
		var dayr = ("0" + d1.getDate()).slice(-2);
		var monr = ("0" + (d1.getMonth()+1)).slice(-2);
		var fullyear = d1.getFullYear();
		
		
		finaldate = [fullyear,monr,dayr].join('-');
		var d = new Date(); // for now
           var hrs = d1.getHours(); // => 9
		   var mits = d1.getMinutes(); // =>  30
	       var secs = d1.getSeconds(); // => 51
		    if(finaldate=="1970-01-01"){finaldate="";}
		   
		   
		   hrs = ('0' + hrs).slice(-2);
		   mits = ('0' + mits).slice(-2);
		   
		   finaltime = [hrs,mits,].join(':');
		
		
		
		var output = "";
		 var repint = data.REPEAT_INTERVAL;
		 var arrIntervals = repint.split(',');
		 var arrayLength = arrIntervals.length;
		 var mondaycheck = "";
		 var tuesdaycheck = "";
		 var wednesdaycheck = "";
		 var thursdaycheck = "";
		 var fridaycheck = "";
		 var saturdaycheck = "";
		 var sundaycheck = "";
		 var monthcheck = "";
		 var everydaycheck = "";
		 var openvar = "";
		  var closevar = "";
		   var completevar = "";
		    var inprogressvar = "";
		 switch(data.TASK_STATUS)
		 {
			 case "OPEN":
			 		openvar = " selected='selected'";
			 	break;
				 case "CLOSED":
			 		closevar = " selected='selected'";
			 	break;
				 case "COMPLETED":
			 		completevar = " selected='selected'";
			 	break;
				 case "IN PROGRESS":
			 		inprogressvar = " selected='selected'";
			 	break;
		 }
for (var i = 0; i < arrayLength; i++) {
	switch(arrIntervals[i])
	{
		case '0': 
		   //document.getElementById("everyMonday").checked = true;
			mondaycheck = " checked='checked'";
			break;
		case '1': 
			tuesdaycheck = " checked='checked'";
			break;
		case '2': 
			wednesdaycheck = " checked='checked'";
			break;
		case '3': 
			thursdaycheck = " checked='checked'";
			break;
		case '4': 
			fridaycheck = " checked='checked'";
			break;
		case '5': 
			saturdaycheck = " checked='checked'";
			break;
		case '6': 
			sundaycheck = " checked='checked'";
			break;
		case '7': 
			monthcheck = " checked='checked'";
			break;
	}
   // console.log();
    //Do something
}
//if(output == "") output += "Never";
if(repint == "0,1,2,3,4,5,6"){ everydaycheck = " checked='checked'";}
	
		
		taskDetail.empty();
	taskDetail.append('<div class="message-body"><div class="container-fluid" id="xyz2"><form  id="frm_editPersonalTask" name="frm_editPersonalTask" enctype="multipart/form-data" method="post" onsubmit="return false;">' +			
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><h2>Edit Personal Task <button class="btn btn-primary btn-sm" id="btnp2" type="button" onclick="funSetCurDateTime();" >Set Priority</button></h2></div></div>' +
           '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="validationCustom01">Task Name</label><input type="text" class="form-control" id="validationCustom01" name=validationCustom01" placeholder="Task name" value="'+data.TASK_TITLE+'" required><div class="valid-feedback">Looks good!</div></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-6"><label for="validationCustom02">Due Date </label><input type="date" class="form-control" id="validationCustom02" name="validationCustom02" value="' + finaldate + '" ><div class="valid-feedback">Looks good!</div></div><div class="col-md-6"><label for="txttime">Due Time</label><input type="time" class="form-control" id="txttime" name="txttime" value="' + finaltime + '" ></div></div>' +		   
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="validationCustom03">Description</label><textarea class="form-control" id="validationCustom03" name="validationCustom03" >' + data.TASK_DESCRIPTION + '</textarea><div class="invalid-feedback">Please provide description.</div></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-6"><label for="TASK_IMAGES">Pictures</label> <br /><input type="file" name="TASK_IMAGES[]" multiple ></div><div class="col-md-6"><label for="Creator">Creator</label><input type="text" class="form-control" id="Creator" placeholder="Creator" value="<?php echo $_SESSION['logged_in']['FULL_NAME']; ?>" readonly></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="repeat">Repeat Task</label></div></div>' +
		   '<div class="row" style="margin-top:5px;"><div class="col-md-12"><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input" id="selectall" value="0,1,2,3,4,5,6" '+everydaycheck+'><label class="custom-control-label" for="selectall">Every Day</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyMonday" value="0" '+mondaycheck+'><label class="custom-control-label" for="everyMonday">Every Monday</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;" style="width:200px; float:left;"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyTuesday" value="1" '+tuesdaycheck+'><label class="custom-control-label" for="everyTuesday">Every Tuesday</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyWednesday" value="2" '+wednesdaycheck+'><label class="custom-control-label" for="everyWednesday">Every Wednesday</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyThursday" value="3" '+thursdaycheck+'><label class="custom-control-label" for="everyThursday">Every Thursday</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyFriday" value="4" '+fridaycheck+'><label class="custom-control-label" for="everyFriday">Every Friday</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input" name="funnel[]" id="everySaturday" value="5" '+saturdaycheck+'><label class="custom-control-label" for="everySaturday">Every Saturday</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input" name="funnel[]" id="everySunday" value="6" '+sundaycheck+'><label class="custom-control-label" for="everySunday">Every Sunday</label></div></div></div>'+ 
		   		   '<div class="row" style="margin-top:10px;"><div class="col-md-12"><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input" name="funnel[]"  id="everyMonth" value="7" '+monthcheck+'><label class="custom-control-label" for="everyMonth">Every Month</label></div></div></div>'+
		   '<div class="row" style="margin-top:25px;"><div class="col-md-6" style="text-align:center;"><button class="btn btn-primary" type="submit" style="width:200px;" id="btn_updateTask" name="btn_updateTask" value="'+data.TASK_ID+'" >Update Personal Task</button></div><div class="col-md-6" style="text-align:center;"><button class="btn btn-info" type="button" style="width:200px;" id="btn_CancelTask" name="btn_CancelTask" onclick="showTaskDetailBack('+data.TASK_ID+');" >Cancel Update</button></div></div>' +		   
            '</div></form></div>'
        );
		
		/*		
		
		//console.log(finaldate);
		taskDetail.empty();
  taskDetail.append('<div class="container-fluid"><h2>Edit Personal Task</h2><form  id="frm_editPersonalTask" name="frm_editPersonalTask" enctype="multipart/form-data" method="post" onsubmit="return false;">'+
  '<div class="form-row"><div class="col-md-6 mb-3">'+
 '<label for="validationCustom01">Task Name</label><input type="text" class="form-control" id="validationCustom01" placeholder="Task name" value="'+data.TASK_TITLE+'" required>'+
 '<div class="valid-feedback">Looks good!</div></div><div class="col-md-6 mb-3">'+
 '<div class="form-group"><label for="statusNow">Status</label><select class="custom-select browser-default" required="" id="TASK_STATUS" name="TASK_STATUS" disabled><option value="">Select</option><option value="OPEN" '+openvar+'>OPEN</option><option value="CLOSED" '+closevar+'>CLOSED</option><option value="COMPLETED" '+completevar+'>COMPLETED</option><option value="IN PROGRESS" '+inprogressvar+'>IN PROGRESS</option></select></div></div></div>'+
 '<div class="form-row"><div class="col-md-6 mb-3"><label for="validationCustom02">Due Date </label>'+
'<input type="date" class="form-control" id="validationCustom02" value="' + finaldate + '" required>'+
      '<div class="valid-feedback">Looks good!</div></div><div class="col-md-6 mb-3"><label for="validationCustom02">Select Time</label><input type="time" class="form-control" id="txttime" placeholder="Select  Time" value="' + finaltime + '" required> </div></div>'+
  '<div class="form-row"><div class="col-md-12 mb-3">'+
  '<label for="validationCustom03">Description</label>'+
  '<textarea type="text" class="form-control" id="validationCustom03" placeholder="Add a TO-DO here" required>' + data.TASK_DESCRIPTION + '</textarea>'+
  '<div class="invalid-feedback">Please provide description.</div></div>'+
  '</div><div class="form-row"><div class="col-md-12 mb-3">'+
 '<input type="file" class="filepond" name="filepond" multiple data-allow-reorder="true" data-max-file-size="3MB" data-max-files="10">'+
 '</div></div><div class="form-row"><div class="col-md-2"> </div>'+
  '<div class="col-md-3"><div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input" id="selectall" value="0,1,2,3,4,5,6" '+everydaycheck+'>'+
  '<label class="custom-control-label" for="selectall">Every Day</label></div></div>'+
 '<div class="col-md-3"><div class="custom-control custom-checkbox">'+
 '<input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyMonday" value="0" '+mondaycheck+'>'+
' <label class="custom-control-label" for="everyMonday">Every Monday</label></div></div>'+
  '<div class="col-md-3"><div class="custom-control custom-checkbox">'+
  '<input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyTuesday" value="1" '+tuesdaycheck+'>'+
 ' <label class="custom-control-label" for="everyTuesday">Every Tuesday</label>'+
'</div></div><div class="col-md-1"> </div></div><div class="form-row"><div class="col-md-2"> </div><div class="col-md-3"> '+
'<div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyWednesday" value="2" '+wednesdaycheck+'>'+
'<label class="custom-control-label" for="everyWednesday">Every Wednesday</label>'+
  '</div></div><div class="col-md-3"> '+
'<div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyThursday" value="3" '+thursdaycheck+'>'+
'<label class="custom-control-label" for="everyThursday">Every Thursday</label>'+
  '</div></div><div class="col-md-3"><div class="custom-control custom-checkbox">'+
    '<input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyFriday" value="4" '+fridaycheck+'>'+
    '<label class="custom-control-label" for="everyFriday">Every Friday</label></div></div><div class="col-md-1"> </div></div>'+
  '<div class="form-row"><div class="col-md-2 mb-3"> </div><div class="col-md-3 mb-3"><div class="custom-control custom-checkbox">'+
   '<input type="checkbox" class="custom-control-input" name="funnel[]" id="everySaturday" value="5" '+saturdaycheck+'>'+
    '<label class="custom-control-label" for="everySaturday">Every Saturday</label></div></div><div class="col-md-3 mb-3"> '+
'<div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input" name="funnel[]" id="everySunday" value="6" '+sundaycheck+'>'+
    '<label class="custom-control-label" for="everySunday">Every Sunday</label>'+
  '</div></div><div class="col-md-3 mb-3"><div class="custom-control custom-checkbox">'+
     '<input type="checkbox" class="custom-control-input" name="funnel[]"  id="everyMonth" value="7" '+monthcheck+'>'+
   ' <label class="custom-control-label" for="everyMonth">Every Month</label>'+
  '</div></div><div class="col-md-1 mb-3"> </div>'+
  '</div><div class="form-row"><div class="col-md-2 mb-3"> </div><div class="col-md-8 mb-3"><div class="form-group">'+
  '<button class="btn btn-primary btn-sm" id="btn_updateTask" type="submit" value="'+data.TASK_ID+'" >Update Task</button> &nbsp; <button class="btn btn-primary btn-sm" id="btn_CancelTask" type="button" onclick="showTaskDetailBack('+data.TASK_ID+');" >Cancel</button></div></div><div class="col-md-2 mb-3"> </div></div></form></div>');
 */
  
 // $('[readonly]').prop( "disabled", true );
  
window.addEventListener("click", function(event) {
  var checkboxes = document.getElementsByName('funnel[]'),
    selectall = document.getElementById('selectall');
  for (var i = 0; i < checkboxes.length; i++) {
    checkboxes[i].addEventListener('change', function() {
      //Conver to array
      var inputList = Array.prototype.slice.call(checkboxes);

      //Set checked  property of selectall input
      selectall.checked = inputList.every(function(c) {
        return c.checked;
      });
    });
  }
  if(selectall){
  selectall.addEventListener('change', function() {
    for (var i = 0; i < checkboxes.length; i++) {
      checkboxes[i].checked = selectall.checked;
    }
  });
  }
});

    
  $("#validationCustom02").val(finaldate);

       
	
    }
	function funSetCurDateTime()
	{
		document.getElementById('validationCustom02').value = "<?php echo date("Y-m-d"); ?>";
		document.getElementById('txttime').value = "<?php echo '00:00';?>";
	}
	/* rafiq code here */
	function addNewPersonalTask() {
		taskList.empty();
		
			taskDetail.empty();
	taskDetail.append('<div class="message-body"><div class="container-fluid" id="xyz2"><form  id="frm_newPersonalTask" name="frm_newPersonalTask" enctype="multipart/form-data" method="post" onsubmit="return false;">' +			
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><h2>Add New Personal Task <button class="btn btn-primary btn-sm" id="btnp2" type="button" onclick="funSetCurDateTime();" >Set Priority</button></h2></div></div>' +
           '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="validationCustom01">Task Name</label><input type="text" class="form-control" id="validationCustom01" name=validationCustom01" placeholder="Task name" value="" required><div class="valid-feedback">Looks good!</div></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-6"><label for="validationCustom02">Due Date </label><input type="date" class="form-control" id="validationCustom02" name="validationCustom02" value="" ><div class="valid-feedback">Looks good!</div></div><div class="col-md-6"><label for="txttime">Due Time</label><input type="time" class="form-control" id="txttime" name="txttime" value="" ></div></div>' +		   
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="validationCustom03">Description</label><textarea class="form-control" id="validationCustom03" name="validationCustom03" ></textarea><div class="invalid-feedback">Please provide description.</div></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-6"><label for="TASK_IMAGES">Pictures</label> <br /><input type="file" name="TASK_IMAGES[]" multiple ></div><div class="col-md-6"><label for="Creator">Creator</label><input type="text" class="form-control" id="Creator" placeholder="Creator" value="<?php echo $_SESSION['logged_in']['FULL_NAME']; ?>" readonly></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="repeat">Repeat Task</label></div></div>' +
		   '<div class="row" style="margin-top:5px;"><div class="col-md-12"><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input" id="selectall" value="0,1,2,3,4,5,6"><label class="custom-control-label" for="selectall">Every Day</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyMonday" value="0"><label class="custom-control-label" for="everyMonday">Every Monday</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;" style="width:200px; float:left;"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyTuesday" value="1"><label class="custom-control-label" for="everyTuesday">Every Tuesday</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyWednesday" value="2"><label class="custom-control-label" for="everyWednesday">Every Wednesday</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyThursday" value="3"><label class="custom-control-label" for="everyThursday">Every Thursday</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyFriday" value="4"><label class="custom-control-label" for="everyFriday">Every Friday</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input" name="funnel[]" id="everySaturday" value="5"><label class="custom-control-label" for="everySaturday">Every Saturday</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input" name="funnel[]" id="everySunday" value="6"><label class="custom-control-label" for="everySunday">Every Sunday</label></div></div></div>'+ 
		   		   '<div class="row" style="margin-top:10px;"><div class="col-md-12"><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input" name="funnel[]"  id="everyMonth" value="7"><label class="custom-control-label" for="everyMonth">Every Month</label></div></div></div>'+
		   '<div class="row" style="margin-top:25px;"><div class="col-md-12" style="text-align:center;"><button class="btn btn-primary" type="submit" style="width:200px;" id="btn_submitPersonalTask" name="btn_submitPersonalTask" >Add Personal Task</button></div></div>' +		   
            '</div></form></div>'
        );
		/*
		taskDetail.empty();
  taskDetail.append('<div class="container-fluid"><h2>Add New Personal Task <button class="btn btn-primary btn-sm" id="btnp" type="button" onclick="funSetCurDateTime();" >Set Priority</button></h2>  <form  id="frm_newPersonalTask" name="frm_newPersonalTask" enctype="multipart/form-data" method="post" onsubmit="return false;">'+
	'<div class="form-row">'+
		'<div class="col-md-5 mb-3"><label for="validationCustom01">Task Name</label><input type="text" class="form-control" id="validationCustom01" placeholder="Task name" value="" required><div class="valid-feedback">Looks good!</div>'+
		'</div>'+		
		'<div class="col-md-4 mb-3"><label for="validationCustom02">Due Date </label><input type="date" class="form-control" id="validationCustom02" value="" ><div class="valid-feedback">Looks good!</div>'+
		'</div>'+
		'<div class="col-md-3 mb-3"><label for="validationCustom02">Due Time</label><input type="time" class="form-control" id="txttime" value="" >'+
		'</div>'+
	'</div>'+  	
	
'<div class="form-row">'+	
	'<div class="col-md-12 mb-3"><label for="validationCustom03">Description</label><textarea type="text" class="form-control" id="validationCustom03" placeholder="Add a TO-DO here"></textarea><div class="invalid-feedback">Please provide description.</div>'+
		'</div>'+
'</div>'+	
		
  '</div><div class="form-row"><div class="col-md-12 mb-3">'+
 '<input type="file" name="TASK_IMAGES[]" multiple >'+
 '</div></div><div class="form-row"><div class="col-md-2"> </div>'+
  '<div class="col-md-3"><div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input" id="selectall" value="0,1,2,3,4,5,6">'+
  '<label class="custom-control-label" for="selectall">Every Day</label></div></div>'+
 '<div class="col-md-3"><div class="custom-control custom-checkbox">'+
 '<input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyMonday" value="0">'+
' <label class="custom-control-label" for="everyMonday">Every Monday</label></div></div>'+
  '<div class="col-md-3"><div class="custom-control custom-checkbox">'+
  '<input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyTuesday" value="1">'+
 ' <label class="custom-control-label" for="everyTuesday">Every Tuesday</label>'+
'</div></div><div class="col-md-1"> </div></div><div class="form-row"><div class="col-md-2"> </div><div class="col-md-3"> '+
'<div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyWednesday" value="2">'+
'<label class="custom-control-label" for="everyWednesday">Every Wednesday</label>'+
  '</div></div><div class="col-md-3"> '+
'<div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyThursday" value="3">'+
'<label class="custom-control-label" for="everyThursday">Every Thursday</label>'+
  '</div></div><div class="col-md-3"><div class="custom-control custom-checkbox">'+
    '<input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyFriday" value="4">'+
    '<label class="custom-control-label" for="everyFriday">Every Friday</label></div></div><div class="col-md-1"> </div></div>'+
  '<div class="form-row"><div class="col-md-2 mb-3"> </div><div class="col-md-3 mb-3"><div class="custom-control custom-checkbox">'+
   '<input type="checkbox" class="custom-control-input" name="funnel[]" id="everySaturday" value="5">'+
    '<label class="custom-control-label" for="everySaturday">Every Saturday</label></div></div><div class="col-md-3 mb-3"> '+
'<div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input" name="funnel[]" id="everySunday" value="6">'+
    '<label class="custom-control-label" for="everySunday">Every Sunday</label>'+
  '</div></div><div class="col-md-3 mb-3"><div class="custom-control custom-checkbox">'+
     '<input type="checkbox" class="custom-control-input" name="funnel[]"  id="everyMonth" value="7">'+
   ' <label class="custom-control-label" for="everyMonth">Every Month</label>'+
  '</div></div><div class="col-md-1 mb-3"> </div>'+
  '</div><div class="form-row"><div class="col-md-2 mb-3"> </div><div class="col-md-8 mb-3"><div class="form-group">'+
  '<button class="btn btn-primary btn-sm" id="btn_submitPersonalTask" type="submit" >Add Personal Task</button></div></div><div class="col-md-2 mb-3"> </div></div></form></div>');
  
  
  */
  
window.addEventListener("click", function(event) {
  var checkboxes = document.getElementsByName('funnel[]'),
    selectall = document.getElementById('selectall');
  for (var i = 0; i < checkboxes.length; i++) {
    checkboxes[i].addEventListener('change', function() {
      //Conver to array
      var inputList = Array.prototype.slice.call(checkboxes);

      //Set checked  property of selectall input
      selectall.checked = inputList.every(function(c) {
        return c.checked;
      });
    });
  }
  if(selectall){
  selectall.addEventListener('change', function() {
    for (var i = 0; i < checkboxes.length; i++) {
      checkboxes[i].checked = selectall.checked;
    }
  });
  }
});
    
    }
	
		function addNewPersonalTask2() {
		taskList.empty();
		
			taskDetail.empty();
	taskDetail.append('<div class="message-body"><div class="container-fluid" id="xyz2"><form  id="frm_newPersonalTask" name="frm_newPersonalTask" enctype="multipart/form-data" method="post" onsubmit="return false;">' +			
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><h2>Add New Personal Task</h2></div></div>' +
           '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="validationCustom01">Task Name</label><input type="text" class="form-control" id="validationCustom01" name=validationCustom01" placeholder="Task name" value="" required><div class="valid-feedback">Looks good!</div></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-6"><label for="validationCustom02">Due Date </label><input type="date" class="form-control" id="validationCustom02" name="validationCustom02" value="<?php echo date("Y-m-d"); ?>" ><div class="valid-feedback">Looks good!</div></div><div class="col-md-6"><label for="txttime">Due Time</label><input type="time" class="form-control" id="txttime" name="txttime" value="<?php echo '00:00';?>" ></div></div>' +		   
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="validationCustom03">Description</label><textarea class="form-control" id="validationCustom03" name="validationCustom03" ></textarea><div class="invalid-feedback">Please provide description.</div></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-6"><label for="TASK_IMAGES">Pictures</label> <br /><input type="file" name="TASK_IMAGES[]" multiple ></div><div class="col-md-6"><label for="Creator">Creator</label><input type="text" class="form-control" id="Creator" placeholder="Creator" value="<?php echo $_SESSION['logged_in']['FULL_NAME']; ?>" readonly></div></div>' +
		   '<div class="row" style="margin-top:15px;"><div class="col-md-12"><label for="repeat">Repeat Task</label></div></div>' +
		   '<div class="row" style="margin-top:5px;"><div class="col-md-12"><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input" id="selectall" value="0,1,2,3,4,5,6"><label class="custom-control-label" for="selectall">Every Day</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyMonday" value="0"><label class="custom-control-label" for="everyMonday">Every Monday</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;" style="width:200px; float:left;"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyTuesday" value="1"><label class="custom-control-label" for="everyTuesday">Every Tuesday</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyWednesday" value="2"><label class="custom-control-label" for="everyWednesday">Every Wednesday</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyThursday" value="3"><label class="custom-control-label" for="everyThursday">Every Thursday</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyFriday" value="4"><label class="custom-control-label" for="everyFriday">Every Friday</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input" name="funnel[]" id="everySaturday" value="5"><label class="custom-control-label" for="everySaturday">Every Saturday</label></div><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input" name="funnel[]" id="everySunday" value="6"><label class="custom-control-label" for="everySunday">Every Sunday</label></div></div></div>'+ 
		   		   '<div class="row" style="margin-top:10px;"><div class="col-md-12"><div class="custom-control custom-checkbox" style="width:180px;float:left;"><input type="checkbox" class="custom-control-input" name="funnel[]"  id="everyMonth" value="7"><label class="custom-control-label" for="everyMonth">Every Month</label></div></div></div>'+
		   '<div class="row" style="margin-top:25px;"><div class="col-md-12" style="text-align:center;"><button class="btn btn-primary" type="submit" style="width:200px;" id="btn_submitPersonalTask" name="btn_submitPersonalTask" >Add Personal Task</button></div></div>' +		   
            '</div></form></div>'
        );
		/*
		taskDetail.empty();
  taskDetail.append('<div class="container-fluid"><h2>Add New Personal Task</h2><form  id="frm_newPersonalTask" name="frm_newPersonalTask" enctype="multipart/form-data" method="post" onsubmit="return false;">'+
	'<div class="form-row">'+
		'<div class="col-md-5 mb-3"><label for="validationCustom01">Task Name</label><input type="text" class="form-control" id="validationCustom01" placeholder="Task name" value="" required><div class="valid-feedback">Looks good!</div>'+
		'</div>'+		
		'<div class="col-md-4 mb-3"><label for="validationCustom02">Due Date </label><input type="date" class="form-control" id="validationCustom02" value="<?php echo date("Y-m-d"); ?>" ><div class="valid-feedback">Looks good!</div>'+
		'</div>'+
		'<div class="col-md-3 mb-3"><label for="validationCustom02">Due Time</label><input type="time" class="form-control" id="txttime" value="<?php echo '00:00';?>" >'+
		'</div>'+
	'</div>'+  	
	
'<div class="form-row">'+	
	'<div class="col-md-12 mb-3"><label for="validationCustom03">Description</label><textarea type="text" class="form-control" id="validationCustom03" placeholder="Add a TO-DO here"></textarea><div class="invalid-feedback">Please provide description.</div>'+
		'</div>'+
'</div>'+			
  '</div><div class="form-row"><div class="col-md-12 mb-3">'+
 '<input type="file" name="TASK_IMAGES[]" multiple >'+
 '</div></div><div class="form-row"><div class="col-md-2"> </div>'+
  '<div class="col-md-3"><div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input" id="selectall" value="0,1,2,3,4,5,6">'+
  '<label class="custom-control-label" for="selectall">Every Day</label></div></div>'+
 '<div class="col-md-3"><div class="custom-control custom-checkbox">'+
 '<input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyMonday" value="0">'+
' <label class="custom-control-label" for="everyMonday">Every Monday</label></div></div>'+
  '<div class="col-md-3"><div class="custom-control custom-checkbox">'+
  '<input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyTuesday" value="1">'+
 ' <label class="custom-control-label" for="everyTuesday">Every Tuesday</label>'+
'</div></div><div class="col-md-1"> </div></div><div class="form-row"><div class="col-md-2"> </div><div class="col-md-3"> '+
'<div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyWednesday" value="2">'+
'<label class="custom-control-label" for="everyWednesday">Every Wednesday</label>'+
  '</div></div><div class="col-md-3"> '+
'<div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyThursday" value="3">'+
'<label class="custom-control-label" for="everyThursday">Every Thursday</label>'+
  '</div></div><div class="col-md-3"><div class="custom-control custom-checkbox">'+
    '<input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyFriday" value="4">'+
    '<label class="custom-control-label" for="everyFriday">Every Friday</label></div></div><div class="col-md-1"> </div></div>'+
  '<div class="form-row"><div class="col-md-2 mb-3"> </div><div class="col-md-3 mb-3"><div class="custom-control custom-checkbox">'+
   '<input type="checkbox" class="custom-control-input" name="funnel[]" id="everySaturday" value="5">'+
    '<label class="custom-control-label" for="everySaturday">Every Saturday</label></div></div><div class="col-md-3 mb-3"> '+
'<div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input" name="funnel[]" id="everySunday" value="6">'+
    '<label class="custom-control-label" for="everySunday">Every Sunday</label>'+
  '</div></div><div class="col-md-3 mb-3"><div class="custom-control custom-checkbox">'+
     '<input type="checkbox" class="custom-control-input" name="funnel[]"  id="everyMonth" value="7">'+
   ' <label class="custom-control-label" for="everyMonth">Every Month</label>'+
  '</div></div><div class="col-md-1 mb-3"> </div>'+
  '</div><div class="form-row"><div class="col-md-2 mb-3"> </div><div class="col-md-8 mb-3"><div class="form-group">'+
  '<button class="btn btn-primary btn-sm" id="btn_submitPersonalTask" type="submit" >Add Personal Task</button></div></div><div class="col-md-2 mb-3"> </div></div></form></div>');
  */
  
window.addEventListener("click", function(event) {
  var checkboxes = document.getElementsByName('funnel[]'),
    selectall = document.getElementById('selectall');
  for (var i = 0; i < checkboxes.length; i++) {
    checkboxes[i].addEventListener('change', function() {
      //Conver to array
      var inputList = Array.prototype.slice.call(checkboxes);

      //Set checked  property of selectall input
      selectall.checked = inputList.every(function(c) {
        return c.checked;
      });
    });
  }
  if(selectall){
  selectall.addEventListener('change', function() {
    for (var i = 0; i < checkboxes.length; i++) {
      checkboxes[i].checked = selectall.checked;
    }
  });
  }
});
    
    }
	function addNewProject() {
		taskList.empty();
		taskDetail.empty();
 	
		
		taskList.append('<div class="content"  style="padding:10px;"><p class="message_text" style="color:green;" >Add new Project Details includes assigning members to new project and then adding new tasks to created new project.</p></div>');
		
  taskDetail.append('<div class="container-fluid" id="xyz"><h2>Add New Project</h2><form  id="frm_newProject" name="frm_newProject" enctype="multipart/form-data" method="post" onsubmit="return false;">'+  
  '<div class="form-row"><div class="col-md-12 mb-12"><label for="PROJECT_NAME">Project Name</label><input type="text" class="form-control" name="PROJECT_NAME" id="PROJECT_NAME" placeholder="Project Name" value="" required></div></div>'+ '<div class="form-row">&nbsp;</div>' +
  '<div class="form-row"><div class="col-md-12 mb-12"><label id="txtName"><?php echo $_SESSION['logged_in']['FULL_NAME']; ?></label><span class="badge-pill" style="float:none; "><label id="txtOwner" class="label-perpul">Owner</label></span></div></div>'+ '<div class="form-row">&nbsp;</div></div></div>');
  
 taskDetail.append('<div id="abc"><label for="autocomplete">Select Member</label><input type="text" class="form-control" id="autocomplete" name="autocomplete" placeholder="Select Member" ></div>');
   taskDetail.append('<div class="form-row">&nbsp;</div><div class="form-row"><div class="col-md-12 mb-12"><div class="form-group"><button class="btn btn-primary btn-sm" id="btn_submitProject" type="submit" >Add Project</button></div>'+  
  '</form></div>');
  
  $( "#autocomplete" ).autocomplete({	
  source: names
});
 
window.addEventListener("click", function(event) {
  var checkboxes = document.getElementsByName('funnel[]'),
    selectall = document.getElementById('selectall');
  for (var i = 0; i < checkboxes.length; i++) {
    checkboxes[i].addEventListener('change', function() {
      //Conver to array
      var inputList = Array.prototype.slice.call(checkboxes);

      //Set checked  property of selectall input
      selectall.checked = inputList.every(function(c) {
        return c.checked;
      });
    });
  }
  if(selectall){
  selectall.addEventListener('change', function() {
    for (var i = 0; i < checkboxes.length; i++) {
      checkboxes[i].checked = selectall.checked;
    }
  });
  }
});
    [
        {supported: 'Symbol' in window, fill: 'https://cdnjs.cloudflare.com/ajax/libs/babel-core/5.6.15/browser-polyfill.min.js'},
        {supported: 'Promise' in window, fill: 'https://cdn.jsdelivr.net/npm/promise-polyfill@8/dist/polyfill.min.js'},
        {supported: 'fetch' in window, fill: 'https://cdn.jsdelivr.net/npm/fetch-polyfill@0.8.2/fetch.min.js'},
        {supported: 'CustomEvent' in window && 'log10' in Math && 'sign' in Math &&  'assign' in Object &&  'from' in Array &&
                    ['find', 'findIndex', 'some', 'includes'].reduce(function(previous, prop) { return (prop in Array.prototype) ? previous : false; }, true), fill: 'https://unpkg.com/filepond-polyfill/dist/filepond-polyfill.js'}
    ].forEach(function(p) {
        if (p.supported) return;
        document.write('<script src="' + p.fill + '"><\/script>');
    });
    


        
        // Get a reference to the file input element
        const inputElement = document.querySelector('input[type="file"]');

        // Create the FilePond instance
        const pond = FilePond.create(inputElement, {
            allowMultiple: true,
            allowReorder: true
        });
    }
	 $(document).on('click', '#btn_submitProject', function() {  

		
var  PROJECT_NAME = document.frm_newProject.PROJECT_NAME.value;
var PROJECT_DESCRIPTION = 'Project Description here';
var  MEMBERS_LIST = $('#autocomplete').val();
var matches = [];
if(MEMBERS_LIST)
{
	MEMBERS_LIST.replace(/\((.*?)\)/g, function(_, match){
  	matches.push(match);
	});
}
//console.log(matches.join(','));
//alert(matches);
//alert(loggedMobile);
var CREATOR_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
var USER_ID = <?php echo isset($_SESSION['logged_in'])?$_SESSION['logged_in']['USER_ID']:""; ?>;
var DUE_DATE = new Date();
DUE_DATE.setFullYear(DUE_DATE.getFullYear() + 1);
DUE_DATE = toTimestamp(DUE_DATE);
if(PROJECT_NAME == "") { alert('Project Name Missing!'); return false;}

if(matches.length == 0)
	matches = loggedMobile;
else
	matches = matches + ',' + loggedMobile;

//alert(matches);
$.ajax({
        url: '<?php echo $url; ?>api2/projects/create-project',
        type: 'POST',
		contentType:'application/x-www-form-urlencoded',
        data:"PROJECT_NAME="+PROJECT_NAME+"&PROJECT_DESCRIPTION="+PROJECT_DESCRIPTION+"&CREATOR_ID="+CREATOR_ID+"&USER_ID="+USER_ID+"&DUE_DATE="+DUE_DATE+"&MEMBERS_LIST="+matches,
		error: function(err) {
            alert(err.statusText);
        },
        success: function(data) {                
         submenuShipmentUsers.empty();
       if(data.STATUS !== 'ERROR') {
			taskDetail.empty();
		taskDetail.append('<div class="success">New Project has been Added Successfully!</div>')

			
        } else {
			
						taskDetail.empty();
						taskDetail.append('<div class="error">Error in Adding New Project!</div>')


			//alert(data.MESSAGE);
            //submenuShipmentUsers.append('<ul class="flex-column nav"><li class="nav-item"><a class="nav-link" href="#" id="Others_0">'+data.MESSAGE+'</a></li></ul>');
        }
                  
            }
		});
		
  });
	
	
	
	function addNewIndividualTask() {
		taskDetail.empty();
  taskDetail.append('<div class="container-fluid"><h2>Add New CC Task</h2><form  id="frm_newCCTask" name="frm_newCCTask" enctype="multipart/form-data" method="post" onsubmit="return false;">'+
  '<div class="form-row"><div class="col-md-6 mb-3"><label for="ccTaskFor">FOR</label><input type="text" class="form-control" id="ccTaskFor" placeholder="Member" value="" required></div><div class="col-md-6 mb-3"><label for="CCTask">CC</label><input type="text" class="form-control" id="CCTask" placeholder="Member" value="" required></div><div class="col-md-4 mb-3">'+
 '<label for="ccTaskFor">FOR</label><input type="text" class="form-control" id="ccTaskName" placeholder="Task name" value="" required>'+
 '</div><div class="col-md-4 mb-3"><label for="ccDate">Select Date </label>'+
'<input type="date" class="form-control" id="ccDate" value="" required>'+
      '<div class="valid-feedback">Looks good!</div></div><div class="col-md-4 mb-3"><label for="ccTime">Select Time</label><input type="time" class="form-control" id="ccTime"  value="" required> </div></div>'+
  '<div class="form-row"><div class="col-md-12 mb-3">'+
  '<label for="ccDescription">Description</label>'+
  '<textarea type="text" class="form-control" id="ccDescription" placeholder="Add a TO-DO here" required></textarea>'+
  '<div class="invalid-feedback">Please provide description.</div></div>'+
  '</div><div class="form-row"><div class="col-md-12 mb-3">'+
 '<input type="file" class="filepond" name="filepond" multiple data-allow-reorder="true" data-max-file-size="3MB" data-max-files="10">'+
 '</div></div><div class="form-row"><div class="col-md-2"> </div>'+
  '<div class="col-md-3"><div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input" id="selectall" value="0,1,2,3,4,5,6">'+
  '<label class="custom-control-label" for="selectall">Every Day</label></div></div>'+
 '<div class="col-md-3"><div class="custom-control custom-checkbox">'+
 '<input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyMonday" value="0">'+
' <label class="custom-control-label" for="everyMonday">Every Monday</label></div></div>'+
  '<div class="col-md-3"><div class="custom-control custom-checkbox">'+
  '<input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyTuesday" value="1">'+
 ' <label class="custom-control-label" for="everyTuesday">Every Tuesday</label>'+
'</div></div><div class="col-md-1"> </div></div><div class="form-row"><div class="col-md-2"> </div><div class="col-md-3"> '+
'<div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyWednesday" value="2">'+
'<label class="custom-control-label" for="everyWednesday">Every Wednesday</label>'+
  '</div></div><div class="col-md-3"> '+
'<div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyThursday" value="3">'+
'<label class="custom-control-label" for="everyThursday">Every Thursday</label>'+
  '</div></div><div class="col-md-3"><div class="custom-control custom-checkbox">'+
    '<input type="checkbox" class="custom-control-input funnel" name="funnel[]" id="everyFriday" value="4">'+
    '<label class="custom-control-label" for="everyFriday">Every Friday</label></div></div><div class="col-md-1"> </div></div>'+
  '<div class="form-row"><div class="col-md-2 mb-3"> </div><div class="col-md-3 mb-3"><div class="custom-control custom-checkbox">'+
   '<input type="checkbox" class="custom-control-input" name="funnel[]" id="everySaturday" value="5">'+
    '<label class="custom-control-label" for="everySaturday">Every Saturday</label></div></div><div class="col-md-3 mb-3"> '+
'<div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input" name="funnel[]" id="everySunday" value="6">'+
    '<label class="custom-control-label" for="everySunday">Every Sunday</label>'+
  '</div></div><div class="col-md-3 mb-3"><div class="custom-control custom-checkbox">'+
     '<input type="checkbox" class="custom-control-input" name="funnel[]" id="everyMonth" value="7">'+
   ' <label class="custom-control-label" for="everyMonth">Every Month</label>'+
  '</div></div><div class="col-md-1 mb-3"> </div>'+
  '</div><div class="form-row"><div class="col-md-2 mb-3"> </div><div class="col-md-8 mb-3"><div class="form-group">'+
  '<button class="btn btn-primary btn-sm" id="btn_submitCCTask" type="submit" >Add CC Task</button></div></div><div class="col-md-2 mb-3"> </div></div></form></div>');
window.addEventListener("click", function(event) {
  var checkboxes = document.getElementsByName('funnel[]'),
    selectall = document.getElementById('selectall');
  for (var i = 0; i < checkboxes.length; i++) {
    checkboxes[i].addEventListener('change', function() {
      //Conver to array
      var inputList = Array.prototype.slice.call(checkboxes);

      //Set checked  property of selectall input
      selectall.checked = inputList.every(function(c) {
        return c.checked;
      });
    });
  }
  if(selectall){
  selectall.addEventListener('change', function() {
    for (var i = 0; i < checkboxes.length; i++) {
      checkboxes[i].checked = selectall.checked;
    }
  });
  }
});
    [
        {supported: 'Symbol' in window, fill: 'https://cdnjs.cloudflare.com/ajax/libs/babel-core/5.6.15/browser-polyfill.min.js'},
        {supported: 'Promise' in window, fill: 'https://cdn.jsdelivr.net/npm/promise-polyfill@8/dist/polyfill.min.js'},
        {supported: 'fetch' in window, fill: 'https://cdn.jsdelivr.net/npm/fetch-polyfill@0.8.2/fetch.min.js'},
        {supported: 'CustomEvent' in window && 'log10' in Math && 'sign' in Math &&  'assign' in Object &&  'from' in Array &&
                    ['find', 'findIndex', 'some', 'includes'].reduce(function(previous, prop) { return (prop in Array.prototype) ? previous : false; }, true), fill: 'https://unpkg.com/filepond-polyfill/dist/filepond-polyfill.js'}
    ].forEach(function(p) {
        if (p.supported) return;
        document.write('<script src="' + p.fill + '"><\/script>');
    });
    


        
        // Get a reference to the file input element
        const inputElement = document.querySelector('input[type="file"]');

        // Create the FilePond instance
        const pond = FilePond.create(inputElement, {
            allowMultiple: true,
            allowReorder: true
        });
                   

  

   
    }
	function addNewAdvance() {  //SHIPMENT_TITLE, CREATOR_ID, SHIPMENT_DESCRIPTION, SHIPMENT_CATEGORY, CUSTOMER_NAME, INVOICE_NUMBER, CREATOR_ID, CREATED_DATE,
		taskDetail.empty();
  taskDetail.append('<div class="container-fluid"><h2>Add New Advance Shipment/ RMA</h2><form  id="frm_newAdvance" name="frm_newAdvance" enctype="multipart/form-data" method="post" >'+
  '<div class="form-row"><div class="col-md-6 mb-3">'+
 '<label for="CUSTOMER_NAME">Customer Name</label><input type="text" class="form-control" id="CUSTOMER_NAME" name="CUSTOMER_NAME"  placeholder="Customer name" value="" required></div><div class="col-md-6 mb-3">'+
'<div class="form-group"><label for="SHIPMENT_CATEGORY">Category</label><select class="custom-select browser-default" id="SHIPMENT_CATEGORY" name="SHIPMENT_CATEGORY" required><option value="SHIPMENT">Advance Shipment</option><option value="RMA">Advance RMA</option></select></div>'+
      '<div class="valid-feedback">Looks good!</div></div><div class="col-md-6 mb-3"><label for="INVOICE_NUMBER">Invoice#</label><input type="text" class="form-control" id="INVOICE_NUMBER" name="INVOICE_NUMBER" placeholder="Invoice#" value="" required> </div><div class="col-md-6 mb-3"><label for="SHIPMENT_TITLE">Price $</label><input type="text" class="form-control" id="SHIPMENT_TITLE" name="SHIPMENT_TITLE" placeholder="Price $" value="" required></div></div>'+
  '<div class="form-row"><div class="col-md-12 mb-3">'+
  '<label for="SHIPMENT_DESCRIPTION">Description</label>'+
  '<textarea type="text" class="form-control" id="SHIPMENT_DESCRIPTION" name="SHIPMENT_DESCRIPTION" placeholder="Add a TO-DO here" required></textarea>'+
  '<div class="invalid-feedback">Please provide description.</div></div>'+
  '</div><div class="form-row"><div class="col-md-12 mb-3">'+
 '<input type="file" name="SHIPMENT_IMAGES[]" multiple >'+
 '</div></div>'+
   '<div class="form-row"><div class="col-md-2 mb-3"> </div><div class="col-md-8 mb-3"><div class="form-group">'+
  '<button class="btn btn-primary btn-sm" id="btn_submitAdvShipTask" type="submit" >Add Shipment Task</button></div></div><div class="col-md-2 mb-3"> </div></div></form></div>');
  
  
  
   
    }

   

    function convertDate(timestamp) {
            var a = new Date(timestamp * 1000);
            var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            var year = a.getFullYear();
            var month = months[a.getMonth()];
            var date = a.getDate();
            var hour = a.getHours();
            var min = a.getMinutes();
            var sec = a.getSeconds();
            var time = date + ' ' + month + ' ' + year + ' ' + timeTo12HrFormat(hour + ':' + min + ':' + sec) ;

            return time;
    }

    function timeTo12HrFormat(time) {
        var time_part_array = time.split(":");
        var ampm = 'AM';

        if (time_part_array[0] >= 12) {
            ampm = 'PM';
        }

        if (time_part_array[0] > 12) {
            time_part_array[0] = time_part_array[0] - 12;
        }

        formatted_time = time_part_array[0] + ':' + time_part_array[1] + ' ' + ampm;

        return formatted_time;
    }

    function activeTab(obj, parent) {

        $('.nav-item').removeClass('active');

        if(parent !== 0) {
            parent.parent().addClass('active');
        }

        obj.parent().addClass('active');
    }
	
	$(document).on('click', '[data-toggle="lightbox"]', function(event) {
                event.preventDefault();
                $(this).ekkoLightbox();
            });
		
		
function funAdiveSubmentOnly(uid)
{
	
	$('a[id^="assignOthers2"]').removeClass("nav-link");
	$('a[id^="assignOthers2"]').addClass("nav-link collapsed");
	$('a[id^="assignOthers2"]').attr('aria-expanded','false');

	$('div[id^="submenuOthers2"]').removeClass("collapse show");
	$('div[id^="submenuOthers2"]').addClass("collapse");
	$('div[id^="submenuOthers2"]').attr('aria-expanded','false');

// for current open
	$("a#assignOthers"+uid).removeClass("nav-link collapsed");
	$("a#assignOthers"+uid).addClass("nav-link");
	$("a#assignOthers"+uid).attr('aria-expanded','true');

	$("div#submenuOthers"+uid).removeClass("collapse");
	$("div#submenuOthers"+uid).addClass("collapse show");
	$("div#submenuOthers"+uid).attr('aria-expanded','false');


// for close all	
	
//	$('li a[id^="assignOthers"]').removeClass("nav-link");
//	$('li a[id^="assignOthers"]').addClass("nav-link collapsed");
//	$('li a[id^="assignOthers"]').attr('aria-expanded','false');
//
//	$('li div[id^="submenuOthers"]').removeClass("collapse show");
//	$('li div[id^="submenuOthers"]').addClass("collapse");
//	$('li div[id^="submenuOthers"]').attr('aria-expanded','false');
	

//	$('li a[id^="assignOthers"]').removeClass("nav-link");
//	$('li a[id^="assignOthers"]').addClass("nav-link collapsed");
//	$('li a[id^="assignOthers"]').attr('aria-expanded','false');
//	$('li a div[id^="assignOthers"]').removeClass("collapse show");
//	$('li a div[id^="assignOthers"]').addClass("collapse");
//	$('li a div[id^="assignOthers"]').attr('aria-expanded','false');

//	$('a[id^="assignOthers'+uid+'"]').removeClass("nav-link collapsed");
//	$('a[id^="assignOthers'+uid+'"]').addClass("nav-link");
//	$('a[id^="assignOthers'+uid+'"]').attr('aria-expanded','true');
//	$('div[id^="assignOthers'+uid+'"]').removeClass("collapse");
//	$('div[id^="assignOthers'+uid+'"]').addClass("collapse show");
//	$('div[id^="assignOthers'+uid+'"]').attr('aria-expanded','false');



	//$('div[id^="submenuOthers"]').removeClass("collapse show");
	//$('div[id^="submenuOthers"]').addClass("collapse");
	//$('div[id^="submenuOthers"]').attr('aria-expanded','false');
	
	//submenuOthers
	
	//$("#assignOthers"+uid).addClass("collapse show");
	//$("#assignOthers"+uid).attr('aria-expanded','true');	

}
			
function funActiveOnly(p,e)
{
	//console.log(e);
	
	
    $('[id^="submenu"]').collapse('hide');

	
//	$("div.inbox").collapse({toggle: false});
	//$('[id^="submenu"]').collapse({toggle: false});
	//collapse({toggle: false});

	//$("#"+p).collapse({toggle: true});
	/*	
	$("#submenu1").removeClass("collapse show");
	$("#submenu1").addClass("collapse");
	$("#submenu1").attr('aria-expanded','false');
	
	$("#submenuMe").removeClass("collapse show");
	$("#submenuMe").addClass("collapse");
	$("#submenuMe").attr('aria-expanded','false');
	$("#submenu1myp").removeClass("collapse show");
	$("#submenu1myp").addClass("collapse");
	$("#submenu1myp").attr('aria-expanded','false');
	$("#submenuMyProjects").removeClass("collapse show");
	$("#submenuMyProjects").addClass("collapse");
	$("#submenuMyProjects").attr('aria-expanded','false');
	$("#submenuOthers").removeClass("collapse show");
	$("#submenuOthers").addClass("collapse");
	$("#submenuOthers").attr('aria-expanded','false');
	$("#submenuCC").removeClass("collapse show");
	$("#submenuCC").addClass("collapse");
	$("#submenuCC").attr('aria-expanded','false');
	$("#submenuShipmentUsers").removeClass("collapse show");
	$("#submenuShipmentUsers").addClass("collapse");
	$("#submenuShipmentUsers").attr('aria-expanded','false');	

	$("#"+p).addClass("collapse show");
	$("#"+p).attr('aria-expanded','true');	
*/
}
	
	
</script>

 <script src="assets/dist/filepond.js"></script>
</body>
</html>