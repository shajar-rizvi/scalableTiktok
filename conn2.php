<?php

//variables

$hostname = "tiktikapp.mysql.database.azure.com";
$username = "tiktokadmin";
$password = "password!1";
$dbname = "tiktok";

//connection

$conn = mysqli_connect($hostname, $username,$password, $dbname )
       or die("Not connected");

//query

$sql = "delete from users where username = 'sharjeel'";
 if (!mysqli_query($conn,$sql )) 
 {
    die("Error in delete query" .mysqli_error());
 } 
 echo  "Data has been deleted";     
 mysqli_close($conn)                    




?>