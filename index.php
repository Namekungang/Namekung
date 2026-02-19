<?php
header ("Location: mobi1/");-- =============================================
-- ระบบยืม-คืนหนังสือบนอุปกรณ์เคลื่อนที่
-- ภาษา: PHP
-- ฐานข้อมูล: MySQL (ใช้งานผ่าน phpMyAdmin)
-- =============================================

-- ======================
-- 1) สร้างฐานข้อมูล
-- ======================
CREATE DATABASE IF NOT EXISTS mobile_library_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE mobile_library_db;

-- ======================
-- 2) ตารางสมาชิก (members)
-- ======================
CREATE TABLE members (
    member_id INT AUTO_INCREMENT PRIMARY KEY,      -- Primary Key
    student_code VARCHAR(20) NOT NULL UNIQUE,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ======================
-- 3) ตารางหนังสือ (books)
-- ======================
CREATE TABLE books (
    book_id INT AUTO_INCREMENT PRIMARY KEY,        -- Primary Key
    isbn VARCHAR(20) UNIQUE,
    title VARCHAR(200) NOT NULL,
    author VARCHAR(150) NOT NULL,
    publisher VARCHAR(150),
    total_quantity INT NOT NULL DEFAULT 1,
    available_quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ======================
-- 4) ตารางการยืม (borrowings)
-- ======================
CREATE TABLE borrowings (
    borrow_id INT AUTO_INCREMENT PRIMARY KEY,      -- Primary Key
    member_id INT NOT NULL,
    book_id INT NOT NULL,
    borrow_date DATE NOT NULL,
    due_date DATE NOT NULL,
    return_date DATE NULL,
    status ENUM('borrowed','returned','overdue') DEFAULT 'borrowed',

    -- Foreign Keys
    CONSTRAINT fk_borrow_member
        FOREIGN KEY (member_id)
        REFERENCES members(member_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,

    CONSTRAINT fk_borrow_book
        FOREIGN KEY (book_id)
        REFERENCES books(book_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
);

-- =============================================
-- 5) ตัวอย่างไฟล์เชื่อมต่อฐานข้อมูล (db.php)
-- =============================================
<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "mobile_library_db";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

-- =============================================
-- 6) ตัวอย่างไฟล์ยืมหนังสือ (borrow.php)
-- =============================================
<?php
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

 echo "คืนหนังสือสำเร็จ";
?>
?>