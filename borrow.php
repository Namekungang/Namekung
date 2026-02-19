<?php
include "db.php";

if(isset($_GET['book_id'])){
    $book_id = $_GET['book_id'];
    $member_id = $_SESSION['member_id'];

    $today = date('Y-m-d');
    $due = date('Y-m-d', strtotime('+7 days'));

    $check = $conn->query("SELECT available_quantity FROM books WHERE book_id=$book_id");
    $data = $check->fetch_assoc();

    if($data['available_quantity'] > 0){
        $conn->query("INSERT INTO borrowings(member_id,book_id,borrow_date,due_date)
                      VALUES($member_id,$book_id,'$today','$due')");

        $conn->query("UPDATE books SET available_quantity=available_quantity-1
                      WHERE book_id=$book_id");
    }

    header("Location: mobi1/dashboard.php");
    exit();
}
?>