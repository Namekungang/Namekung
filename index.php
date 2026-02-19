<?php
header ("Location: mobi1/");-- =============================================
include 'db.php';

$member_id = $_POST['member_id'];
$book_id   = $_POST['book_id'];
$borrow_date = date('Y-m-d');
$due_date = date('Y-m-d', strtotime('+7 days'));

// ตรวจสอบจำนวนหนังสือคงเหลือ
$check = $conn->query("SELECT available_quantity FROM books WHERE book_id = $book_id");
$row = $check->fetch_assoc();

if ($row['available_quantity'] > 0) {

    // เพิ่มข้อมูลการยืม
    $conn->query("INSERT INTO borrowings (member_id, book_id, borrow_date, due_date)
                  VALUES ($member_id, $book_id, '$borrow_date', '$due_date')");

    // ลดจำนวนหนังสือ
    $conn->query("UPDATE books
                  SET available_quantity = available_quantity - 1
                  WHERE book_id = $book_id");

    echo "ยืมหนังสือสำเร็จ";
} else {
    echo "หนังสือไม่เพียงพอ";
}
?>

-- =============================================
-- 7) ตัวอย่างไฟล์คืนหนังสือ (return.php)
-- =============================================
<?php
include 'db.php';

$borrow_id = $_POST['borrow_id'];
$return_date = date('Y-m-d');

// อัปเดตสถานะเป็น returned
$conn->query("UPDATE borrowings
              SET return_date = '$return_date',
                  status = 'returned'
              WHERE borrow_id = $borrow_id");

// ดึง book_id
$result = $conn->query("SELECT book_id FROM borrowings WHERE borrow_id = $borrow_id");
$data = $result->fetch_assoc();
$book_id = $data['book_id'];

// เพิ่มจำนวนหนังสือกลับ
$conn->query("UPDATE books
              SET available_quantity = available_quantity + 1
              WHERE book_id = $book_id");

 echo "คืนหนังสือสำเร็จ";?>