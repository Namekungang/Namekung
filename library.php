# ระบบการยืมคืนหนังสือ (ไฟล์เดียว พร้อมภาพประกอบ)

นำโค้ดด้านล่างนี้ไปวางแทนไฟล์เดิมทั้งหมด

```php
<?php
session_start();

$conn = new mysqli("localhost","s673190120","s673190120","s673190120");
if ($conn->connect_error) {
    die("เชื่อมต่อฐานข้อมูลไม่ได้: " . $conn->connect_error);
}

// ================= สร้างตารางอัตโนมัติ =================
$conn->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    full_name VARCHAR(255),
    role ENUM('user','admin') DEFAULT 'user'
)");

// สร้าง admin เริ่มต้น
$checkAdmin = $conn->query("SELECT id FROM users WHERE role='admin' LIMIT 1");
if ($checkAdmin->num_rows == 0) {
    $adminPass = password_hash("admin123", PASSWORD_DEFAULT);
    $conn->query("INSERT INTO users(username,password,full_name,role)
                  VALUES('admin','$adminPass','Administrator','admin')");
}

$conn->query("CREATE TABLE IF NOT EXISTS books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_name VARCHAR(255),
    status ENUM('available','borrowed') DEFAULT 'available'
)");

$conn->query("CREATE TABLE IF NOT EXISTS borrow (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    book_id INT,
    borrow_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    return_date DATETIME NULL
)");

$checkBook = $conn->query("SELECT id FROM books LIMIT 1");
if ($checkBook->num_rows == 0) {
    $conn->query("INSERT INTO books (book_name) VALUES
        ('PHP Programming'),
        ('MySQL Database'),
        ('Web Development'),
        ('Data Structure')");
}

$action = $_GET['action'] ?? 'login';
$message = "";

// ================= REGISTER =================
if ($action == 'register' && isset($_POST['register'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $fullname = $_POST['full_name'];

    $check = $conn->query("SELECT id FROM users WHERE username='$username'");
    if ($check->num_rows > 0) {
        $message = "Username นี้มีอยู่แล้ว";
    } else {
        $conn->query("INSERT INTO users(username,password,full_name)
                      VALUES('$username','$password','$fullname')");
        $message = "สมัครสมาชิกสำเร็จ";
    }
}

// ================= LOGIN =================
if ($action == 'login' && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $result = $conn->query("SELECT * FROM users WHERE username='$username'");
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header("Location: ?action=dashboard"); exit();
        } else {
            $message = "รหัสผ่านไม่ถูกต้อง";
        }
    } else {
        $message = "ไม่พบ Username";
    }
}

// ================= BORROW =================
if ($action == 'borrow' && isset($_GET['book']) && isset($_SESSION['user_id'])) {
    $book_id = $_GET['book'];
    $user_id = $_SESSION['user_id'];

    $conn->query("INSERT INTO borrow(user_id,book_id) VALUES('$user_id','$book_id')");
    $conn->query("UPDATE books SET status='borrowed' WHERE id='$book_id'");
    header("Location: ?action=mybooks"); exit();
}

// ================= RETURN =================
if ($action == 'return' && isset($_GET['borrow_id'])) {
    $borrow_id = $_GET['borrow_id'];
    $conn->query("UPDATE borrow SET return_date=NOW() WHERE id='$borrow_id'");
    $conn->query("UPDATE books SET status='available' 
                  WHERE id=(SELECT book_id FROM borrow WHERE id='$borrow_id')");
    header("Location: ?action=mybooks"); exit();
}

// ================= ADD BOOK (ADMIN) =================
if ($action == 'addbook' && isset($_POST['addbook']) && $_SESSION['role']=='admin') {
    $book_name = $_POST['book_name'];
    $conn->query("INSERT INTO books(book_name) VALUES('$book_name')");
    header("Location: ?action=admin"); exit();
}

// ================= LOGOUT =================
if ($action == 'logout') {
    session_destroy();
    header("Location: ?"); exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>ระบบการยืมคืนหนังสือ</title>
<style>
body{
    font-family: Arial;
    margin:0;
    background: linear-gradient(to right,#141e30,#243b55);
}
.header{
    text-align:center;
    padding:20px;
    color:white;
}
.header img{
    width:120px;
}
.container{
    width:900px;
    margin:auto;
    background:white;
    margin-top:20px;
    padding:30px;
    border-radius:15px;
    box-shadow:0 8px 25px rgba(0,0,0,0.3);
}
h1{ text-align:center; }
.menu a{
    padding:10px 15px;
    background:#243b55;
    color:white;
    text-decoration:none;
    margin-right:5px;
    border-radius:5px;
}
.menu a:hover{
    background:#141e30;
}
.menu{ margin-bottom:20px; }
input,button{
    padding:12px;
    margin:8px 0;
    width:100%;
    border-radius:5px;
    border:1px solid #ccc;
}
button{
    background:#243b55;
    color:white;
    border:none;
    cursor:pointer;
}
button:hover{
    background:#141e30;
}
table{
    width:100%;
    border-collapse:collapse;
    margin-top:15px;
}
table,th,td{
    border:1px solid #ddd;
}
th{
    background:#243b55;
    color:white;
}
th,td{
    padding:10px;
    text-align:center;
}
.message{ color:red; text-align:center; }
.footer{
    text-align:center;
    color:white;
    margin-top:20px;
    padding:10px;
}
</style>
</head>
<body>

<div class="header">
    <img src="lb2.jpg>
    <h2>ระบบการยืมคืนหนังสือออนไลน์</h2>
</div>

<div class="container">

<?php if ($action == 'register') { ?>
<h1>สมัครสมาชิก</h1>
<form method="post">
<input type="text" name="full_name" placeholder="ชื่อ-สกุล" required>
<input type="text" name="username" placeholder="Username" required>
<input type="password" name="password" placeholder="Password" required>
<button name="register">สมัครสมาชิก</button>
</form>
<div class="message"><?php echo $message; ?></div>
<a href="?">ไปหน้า Login</a>

<?php } elseif ($action == 'dashboard' && isset($_SESSION['user_id'])) { ?>
<h1>📚 Dashboard</h1>
<div class="menu">
<a href="?action=borrow_page">ยืมหนังสือ</a>
<a href="?action=mybooks">รายการที่ยืม</a>
<?php if($_SESSION['role']=='admin'){ ?>
<a href="?action=admin">จัดการระบบ</a>
<?php } ?>
<a href="?action=logout">Logout</a>
</div>
<p>ยินดีต้อนรับ <b><?php echo $_SESSION['username']; ?></b></p>

<?php } elseif ($action == 'borrow_page') { ?>
<h1>เลือกหนังสือเพื่อยืม</h1>
<div class="menu">
<a href="?action=dashboard">กลับหน้า Dashboard</a>
</div>
<table>
<tr><th>ชื่อหนังสือ</th><th>สถานะ</th><th>จัดการ</th></tr>
<?php
$books = $conn->query("SELECT * FROM books");
while($b = $books->fetch_assoc()){
echo "<tr>";
echo "<td>📖 ".$b['book_name']."</td>";
echo "<td>".$b['status']."</td>";
if($b['status']=="available"){
echo "<td><a href='?action=borrow&book=".$b['id']."'>ยืม</a></td>";
}else{
echo "<td>-</td>";
}
echo "</tr>";
}
?>
</table>

<?php } elseif ($action == 'mybooks') { ?>
<h1>รายการที่ยืม</h1>
<img src="lb2.jpg" style="width:100%; max-height:250px; object-fit:cover; border-radius:15px; margin-bottom:20px;">
<div class="menu">
<a href="?action=dashboard">กลับหน้า Dashboard</a>
</div>
<table>
<tr><th>ชื่อหนังสือ</th><th>วันที่ยืม</th><th>วันที่คืน</th><th>จัดการ</th></tr>
<?php
$user_id = $_SESSION['user_id'];
$sql = "SELECT borrow.*,books.book_name 
        FROM borrow 
        JOIN books ON borrow.book_id=books.id
        WHERE borrow.user_id='$user_id'";
$result = $conn->query($sql);
while($row=$result->fetch_assoc()){
echo "<tr>";
echo "<td>📘 ".$row['book_name']."</td>";
echo "<td>".$row['borrow_date']."</td>";
echo "<td>".($row['return_date'] ?? '-')."</td>";
if(!$row['return_date']){
echo "<td><a href='?action=return&borrow_id=".$row['id']."'>คืน</a></td>";
}else{
echo "<td>-</td>";
}
echo "</tr>";
}
?>
</table>

<?php } elseif ($action == 'admin' && isset($_SESSION['role']) && $_SESSION['role']=='admin') { ?>

<h1>⚙️ หน้าผู้ดูแลระบบ (Admin)</h1>
<div class="menu">
<a href="?action=dashboard">กลับหน้า Dashboard</a>
</div>

<h3>👥 จัดการสมาชิก</h3>
<table>
<tr><th>ID</th><th>Username</th><th>ชื่อ</th><th>Role</th></tr>
<?php
$users = $conn->query("SELECT * FROM users");
while($u=$users->fetch_assoc()){
 echo "<tr>";
 echo "<td>{$u['id']}</td>";
 echo "<td>{$u['username']}</td>";
 echo "<td>{$u['full_name']}</td>";
 echo "<td>{$u['role']}</td>";
 echo "</tr>";
}
?>
</table>

<h3 style='margin-top:30px;'>📚 จัดการหนังสือ</h3>
<form method="post" action="?action=addbook">
<input type="text" name="book_name" placeholder="ชื่อหนังสือใหม่" required>
<button name="addbook">เพิ่มหนังสือ</button>
</form>

<table>
<tr><th>ID</th><th>ชื่อหนังสือ</th><th>สถานะ</th></tr>
<?php
$books = $conn->query("SELECT * FROM books");
while($b=$books->fetch_assoc()){
 echo "<tr>";
 echo "<td>{$b['id']}</td>";
 echo "<td>{$b['book_name']}</td>";
 echo "<td>{$b['status']}</td>";
 echo "</tr>";
}
?>
</table>

<?php } else { ?>
<h1>เข้าสู่ระบบ</h1>
<img src="lb.jpg" style="width:100%; max-height:250px; object-fit:cover; border-radius:15px; margin-bottom:20px;">
<form method="post">
<input type="text" name="username" placeholder="Username" required>
<input type="password" name="password" placeholder="Password" required>
<button name="login">Login</button>
</form>
<div class="message"><?php echo $message; ?></div>
<a href="?action=register">สมัครสมาชิก</a>

<?php } ?>

</div>

<div class="footer">
    © 2026 ระบบการยืมคืนหนังสือ
</div>

</body>
</html>
```
