<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Main</title>
</head>
<body>
    
<h1> Library Management System </h1>

<?php
$servername = "localhost";
$username = "root";
$password = "";
$database = "Library_Management";

$conn = new mysqli ($servername, $username, $password,$database);


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    echo "Welcome!";
} 

?>
<br>

<p>Who are you?</p>

    <a href="user.php"><button >User</button></a>
    <a href="admin.php"><button >Admin</button></a>
    
</body>
</html>