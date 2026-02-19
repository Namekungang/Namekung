<?php
include "db.php";

if(isset($_GET['borrow_id'])){
    $borrow_id = $_GET['borrow_id'];

    $conn->query("UPDATE borrowings
                  SET status='returned', return_date=CURDATE()
                  WHERE borrow_id=$borrow_id");

    $result = $conn->query("SELECT book_id FROM borrowings WHERE borrow_id=$borrow_id");
    $row = $result->fetch_assoc();
    $book_id = $row['book_id'];

    $conn->query("UPDATE books SET available_quantity=available_quantity+1
                  WHERE book_id=$book_id");

    header("Location: mobi1/dashboard.php");
    exit();
}
?>