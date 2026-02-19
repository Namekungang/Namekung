<?php
include "db.php";

if(isset($_POST['login'])){
    $email = $_POST['email'];
    $password = $_POST['password'];

    $result = $conn->query("SELECT * FROM members WHERE email='$email'");
    $user = $result->fetch_assoc();

    if($user && password_verify($password,$user['password'])){
        $_SESSION['member_id'] = $user['member_id'];
        $_SESSION['name'] = $user['full_name'];
        header("Location: mobi1/dashboard.php");
        exit();
    } else {
        echo "Login Failed";
    }
}
?>