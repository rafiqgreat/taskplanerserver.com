<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <!--  This file has been downloaded from https://bootdey.com  -->
    <!--  All snippets are MIT license https://bootdey.com/license -->
    <title>Task Planner</title>
    <link rel="icon" href="icon2.gif" type="image/gif" sizes="16x16">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
    <link href="https://netdna.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" rel="stylesheet">
   
    <link rel="stylesheet" href="build/css/intlTelInput.css">
  <link rel="stylesheet" href="build/css/demo.css">
   <link href="assets/css/styles.css" rel="stylesheet">
   <script language="javascript" type="text/javascript">
    function setCookie(key, value, expiry) {
        var expires = new Date();
        expires.setTime(expires.getTime() + (expiry * 24 * 60 * 60 * 1000));
        document.cookie = key + '=' + value + ';expires=' + expires.toUTCString();
    }

    function getCookie(key) {
        var keyValue = document.cookie.match('(^|;) ?' + key + '=([^;]*)(;|$)');
        return keyValue ? keyValue[2] : null;
    }

    function eraseCookie(key) {
        var keyValue = getCookie(key);
        setCookie(key, keyValue, '-1');
    }
   </script>
</head>
<body>
<link rel="stylesheet" href="//cdn.materialdesignicons.com/3.7.95/css/materialdesignicons.min.css">

<div class="container-fluid h-100">
    <div class="content-wrapper h-100">
        <div class="row h-100">
            <div class="col-md-12">
                <div class="row h-100">
                    <div class="col-md-6 mx-auto my-auto align-self-center">
                        <!-- form card login -->
                        <div class="card rounded-0">
                            <div class="card-header">
                                <h3 class="mb-0">Login</h3>
                            </div>
                            <div class="card-body">
                                <!-- Jahangir +923314948707-->
                                <form id="loginForm" class="form" role="form" autocomplete="off" id="formLogin" novalidate="" method="POST">
                                    <div class="form-group">
                                        <label>Full Name</label>
                                        <input type="text" class="form-control form-control-lg rounded-0" id="full_name" required="" value="">
                                    </div>

                                    <div class="form-group">
                                        <label>Mobile Number (0301 234 5678)</label>
                                       <br>
                                  <!-- <input type="text" class="form-control form-control-lg rounded-0" id="mobile_number" required="" value="">-->
                                         <input width="100%" class="form-control form-control-lg rounded-0" id="mobile_number" required="" value="+1" name="phone" type="tel">
                                    </div>

                                    <button type="button" class="btn btn-success btn-lg float-right" id="btnLogin">Login</button>
                                </form>
                            </div>
                            <!--/card-block-->
                        </div>
                        <!-- /form card login -->
                    </div>
                </div>
                <!--/row-->
            </div>
            <!--/col-->
        </div>
        <!--/row-->
    </div>
</div>



  <script src="build/js/intlTelInput.js"></script>
  <script>
    var input = document.querySelector("#mobile_number");
    window.intlTelInput(input, {
      // allowDropdown: false,
      autoHideDialCode: false,
      // autoPlaceholder: "off",
      // dropdownContainer: document.body,
      // excludeCountries: ["us"],
      // formatOnDisplay: false,
      // geoIpLookup: function(callback) {
      //   $.get("http://ipinfo.io", function() {}, "jsonp").always(function(resp) {
      //     var countryCode = (resp && resp.country) ? resp.country : "";
      //     callback(countryCode);
      //   });
      // },
      // hiddenInput: "full_number",
      // initialCountry: "auto",
      // localizedCountries: { 'de': 'Deutschland' },
      // nationalMode: false,
      // onlyCountries: ['us', 'gb', 'ch', 'ca', 'do'],
      // placeholderNumberType: "MOBILE",
      // preferredCountries: ['cn', 'jp'],
      // separateDialCode: true,
      utilsScript: "build/js/utils.js",
    });
	
  </script>

<script src="https://netdna.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>
<script type="text/javascript">

var mobile = getCookie('mobile');
var full = getCookie('full');

$('#mobile_number').val(mobile);
 $('#full_name').val(full);

    $('#btnLogin').click(function(e) {
		var num = $('#mobile_number').val();
		num = num.replace(' ','');
		num = num.replace(')','');
		num = num.replace('(','');
		num = num.replace('-','');				
		var fuln = $('#full_name').val();
		if(fuln.length<3) { alert('FULL NAME Invalid!');  $('#full_name').focus(); return false;}
        $.ajax({
            url: '<?php echo $url; ?>api2/auth/newlogin',
            data: {
                USER_NAME : num,
                FULL_NAME : fuln,
                SESSION : 1
            },
            type: 'POST',
            dataType: 'json',
            cache: false,
            success: function (data) {
                if (data.STATUS === 'SUCCESS') {
					
				// Set a cookie
setCookie('mobile', $('#mobile_number').val(),'30'); 
setCookie('full',$('#full_name').val(),'30'); 

                    location.reload();
                } else {
                    alert("There was an error");
                }
            }
        })
    })
	
</script>
</body>
</html>