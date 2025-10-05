<?php 

//database credentials
$serverName = 'localhost:3307';
$userName = 'root';
$password = 12345;
$dbName = 'email-schedule';

// Timezone
date_default_timezone_set('Africa/Lagos');

//creating connections
$conn = mysqli_connect($serverName, $userName, $password, $dbName);

if (!$conn) {
    die("Connection Failed: " . mysqli_connect_errno());
}

?>