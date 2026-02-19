<?php
$host = "localhost";
$user = "s673190120";
$pass = "s673190120";
$db   = "s673190120"; // ต้องสร้าง DB นี้ไว้ก่อน

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed");

echo "<h2>PHP Test ระบบห้องสมุด</h2>";

/* ================================
   1️⃣ เพิ่มผู้ใช้ทดสอบ
================================ */
$conn->query("INSERT INTO users (username,password,full_name)
              VALUES ('testuser','1234','Test User')");

echo "เพิ่มผู้ใช้เรียบร้อย<br>";

/* ================================
   2️⃣ เพิ่มหนังสือทดสอบ
================================ */
$conn->query("INSERT INTO books (title,author,quantity)
              VALUES ('Test Book','Unknown',5)");

echo "เพิ่มหนังสือเรียบร้อย<br>";

/* ================================
   3️⃣ ทดสอบยืมหนังสือ
================================ */
$conn->query("INSERT INTO borrowings (user_id,book_id,borrow_date)
              VALUES (1,1,CURDATE())");

$conn->query("UPDATE books SET quantity=quantity-1 WHERE id=1");

echo "ยืมหนังสือเรียบร้อย<br>";

/* ================================
   4️⃣ ทดสอบคืนหนังสือ
================================ */
$conn->query("UPDATE borrowings
              SET status='returned', return_date=CURDATE()
              WHERE id=1");

$conn->query("UPDATE books SET quantity=quantity+1 WHERE id=1");

echo "คืนหนังสือเรียบร้อย<br>";

/* ================================
   5️⃣ แสดงหนังสือที่ยืมอยู่
================================ */
$result = $conn->query("SELECT borrowings.*, books.title 
                        FROM borrowings 
                        JOIN books ON borrowings.book_id = books.id
                        WHERE status='borrowed'");

echo "<h3>หนังสือที่ยังไม่คืน:</h3>";
while($row = $result->fetch_assoc()){
    echo $row['title']." - ".$row['borrow_date']."<br>";
}

$conn->close();
?>