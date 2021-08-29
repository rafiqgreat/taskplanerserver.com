<?php
//date_default_timezone_set('Europe/London');

if (date_default_timezone_get()) {
    echo 'date_default_timezone_get: ' . date_default_timezone_get() . '<br />';
}

if (ini_get('date.timezone')) {
    echo 'date.timezone: ' . ini_get('date.timezone');
}

?>
<hr />
<?php
echo date("Y-m-d h:i:sa l")." <br />The time is " . date("h:i:sa");
?>



