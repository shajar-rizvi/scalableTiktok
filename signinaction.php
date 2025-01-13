<?php
//variables

$hostname = "tiktikapp.mysql.database.azure.com";
$username = "tiktokadmin";
$password = "password!1";
$dbname = "tiktok";

//connection

$conn = mysqli_connect($hostnamme,$username,$password,$dbname)
        or die("not able to connecr" .mysqli_error($conn));
echo "Connected successfully";

//query

$sql = mysqli_query($conn, "select username,password from users");

//fecth


?>