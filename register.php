<?php
include "db.php";

if(isset($_POST['register'])){
    $name = $_POST['name'];
    $email = $_POST['email'];
    $student = $_POST['student'];
    $pass = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $conn->query("INSERT INTO members(full_name,email,student_code,password)
                  VALUES('$name','$email','$student','$pass')");

    header("Location: mobi1/");
    exit();
}
?>
