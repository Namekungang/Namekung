<?php
session_start();

$conn = new mysqli("localhost","s673190120","s673190120","s673190120");
if ($conn->connect_error) die("เชื่อมต่อฐานข้อมูลไม่ได้");

// ================= CREATE TABLES =================

$conn->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    full_name VARCHAR(255),
    role ENUM('user','admin') DEFAULT 'user'
)");

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

$conn->query("CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_name VARCHAR(100) UNIQUE,
    setting_value VARCHAR(255)
)");

// ================= DEFAULT ADMIN =================

$checkAdmin = $conn->query("SELECT id FROM users WHERE role='admin'");
if ($checkAdmin->num_rows == 0) {
    $pass = password_hash("admin", PASSWORD_DEFAULT);
    $conn->query("INSERT INTO users(username,password,full_name,role)
                  VALUES('admin','$pass','Administrator','admin')");
}

// ================= DEFAULT ADMIN KEY =================

$checkKey = $conn->query("SELECT * FROM settings WHERE setting_name='admin_key'");
if ($checkKey->num_rows == 0) {
    $hashKey = password_hash("SECRET123", PASSWORD_DEFAULT);
    $conn->query("INSERT INTO settings(setting_name,setting_value)
                  VALUES('admin_key','$hashKey')");
}

// ================= SAMPLE BOOK =================

$checkBook = $conn->query("SELECT id FROM books LIMIT 1");
if ($checkBook->num_rows == 0) {
    $conn->query("INSERT INTO books(book_name) VALUES
        ('PHP Programming'),
        ('MySQL Database'),
        ('Web Development'),
        ('Data Structure')");
}

$action = $_GET['action'] ?? 'login';
$message = "";

// ================= ROLE PROTECTION =================

if (in_array($action, ['admin_page','view_users','add_book'])) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
        die("403 Forbidden");
    }
}

// ================= REGISTER =================

if ($action == 'register' && isset($_POST['register'])) {

    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $fullname = $_POST['full_name'];
    $role = $_POST['role'];

    if ($role == 'admin') {

        $inputKey = $_POST['admin_key'];

        $getKey = $conn->query("SELECT setting_value FROM settings WHERE setting_name='admin_key'");
        $rowKey = $getKey->fetch_assoc();

        if (!password_verify($inputKey, $rowKey['setting_value'])) {
            $message = "Admin Key ไม่ถูกต้อง";
            $role = 'user';
        }
    }

    $check = $conn->query("SELECT id FROM users WHERE username='$username'");
    if ($check->num_rows > 0) {
        $message = "Username นี้มีอยู่แล้ว";
    } else {
        $conn->query("INSERT INTO users(username,password,full_name,role)
                      VALUES('$username','$password','$fullname','$role')");
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
        }
    }
    $message = "Login ไม่ถูกต้อง";
}

// ================= LOGOUT =================

if ($action == 'logout') {
    session_destroy();
    header("Location: ?"); exit();
}

// ================= BORROW =================

if ($action == 'borrow' && isset($_GET['book']) && isset($_SESSION['user_id'])) {
    $book_id = intval($_GET['book']);
    $user_id = $_SESSION['user_id'];

    $conn->query("INSERT INTO borrow(user_id,book_id) VALUES('$user_id','$book_id')");
    $conn->query("UPDATE books SET status='borrowed' WHERE id='$book_id'");
    header("Location: ?action=mybooks"); exit();
}

// ================= RETURN =================

if ($action == 'return' && isset($_GET['borrow_id'])) {
    $borrow_id = intval($_GET['borrow_id']);
    $user_id = $_SESSION['user_id'];

    $check = $conn->query("SELECT * FROM borrow 
                           WHERE id='$borrow_id' AND user_id='$user_id'");
    if ($check->num_rows == 0) die("ไม่มีสิทธิ์");

    $conn->query("UPDATE borrow SET return_date=NOW() WHERE id='$borrow_id'");
    $conn->query("UPDATE books SET status='available' 
                  WHERE id=(SELECT book_id FROM borrow WHERE id='$borrow_id')");
    header("Location: ?action=mybooks"); exit();
}

// ================= ADD BOOK =================

if ($action == 'add_book' && isset($_POST['add_book'])) {
    $book = $_POST['book_name'];
    $conn->query("INSERT INTO books(book_name) VALUES('$book')");
    header("Location: ?action=admin_page"); exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>ระบบการยืมคืนหนังสือ</title>
<style>
body{font-family:Arial;background:linear-gradient(to right,#4e73df,#1cc88a);margin:0}
.container{width:1000px;margin:auto;background:white;padding:30px;margin-top:30px;border-radius:10px}
.menu a{padding:8px 12px;background:#4e73df;color:white;text-decoration:none;border-radius:5px;margin-right:5px}
input,select,button{padding:8px;width:100%;margin:5px 0}
button{background:#4e73df;color:white;border:none}
table{width:100%;border-collapse:collapse}
th,td{border:1px solid #ddd;padding:8px;text-align:center}
.message{color:red}
</style>
</head>
<body>
<div class="container">

<?php if ($action == 'register') { ?>

<h2>สมัครสมาชิก</h2>
<form method="post">

<input name="full_name" placeholder="ชื่อ-สกุล" required>
<input name="username" placeholder="Username" required>
<input type="password" name="password" placeholder="Password" required>

<label>เลือกระดับผู้ใช้</label>
<select name="role" onchange="toggleKey(this.value)">
<option value="user">User</option>
<option value="admin">Admin</option>
</select>

<div id="keybox" style="display:none;">
<input type="password" name="admin_key" placeholder="ใส่ Admin Key">
</div>

<button name="register">สมัครสมาชิก</button>
</form>

<script>
function toggleKey(value){
document.getElementById('keybox').style.display =
value === 'admin' ? 'block' : 'none';
}
</script>

<div class="message"><?= $message ?></div>
<a href="?">กลับ Login</a>

<?php } elseif ($action == 'dashboard' && isset($_SESSION['user_id'])) { ?>

<h2>Dashboard (<?= $_SESSION['role'] ?>)</h2>
<div class="menu">
<a href="?action=borrow_page">ยืมหนังสือ</a>
<a href="?action=mybooks">รายการของฉัน</a>
<?php if($_SESSION['role']=='admin'){ ?>
<a href="?action=admin_page">เมนู Admin</a>
<?php } ?>
<a href="?action=logout">Logout</a>
</div>
<p>ยินดีต้อนรับ <?= $_SESSION['username'] ?></p>

<?php } elseif ($action == 'borrow_page') { ?>

<h2>เลือกหนังสือ</h2>
<table>
<tr><th>ชื่อหนังสือ</th><th>สถานะ</th><th></th></tr>
<?php
$books=$conn->query("SELECT * FROM books");
while($b=$books->fetch_assoc()){
echo "<tr>";
echo "<td>".$b['book_name']."</td>";
echo "<td>".$b['status']."</td>";
echo "<td>";
if($b['status']=="available")
echo "<a href='?action=borrow&book=".$b['id']."'>ยืม</a>";
echo "</td></tr>";
}
?>
</table>

<?php } elseif ($action == 'mybooks') { ?>

<h2>รายการที่ยืม</h2>
<table>
<tr><th>หนังสือ</th><th>วันที่ยืม</th><th>คืน</th></tr>
<?php
$user_id=$_SESSION['user_id'];
$result=$conn->query("SELECT borrow.*,books.book_name 
FROM borrow JOIN books ON borrow.book_id=books.id
WHERE borrow.user_id='$user_id'");
while($row=$result->fetch_assoc()){
echo "<tr>";
echo "<td>".$row['book_name']."</td>";
echo "<td>".$row['borrow_date']."</td>";
echo "<td>";
if(!$row['return_date'])
echo "<a href='?action=return&borrow_id=".$row['id']."'>คืน</a>";
else echo "คืนแล้ว";
echo "</td></tr>";
}
?>
</table>

<?php } elseif ($action == 'admin_page') { ?>

<h2>เมนู Admin</h2>
<div class="menu">
<a href="?action=view_users">ดูสมาชิก</a>
<a href="?action=dashboard">กลับ</a>
</div>

<h3>เพิ่มหนังสือ</h3>
<form method="post" action="?action=add_book">
<input name="book_name" placeholder="ชื่อหนังสือ" required>
<button name="add_book">เพิ่มหนังสือ</button>
</form>

<?php } elseif ($action == 'view_users') { ?>

<h2>รายชื่อสมาชิก</h2>
<div class="menu">
<a href="?action=admin_page">กลับ</a>
</div>
<table>
<tr><th>Username</th><th>ชื่อ</th><th>Role</th></tr>
<?php
$users=$conn->query("SELECT * FROM users");
while($u=$users->fetch_assoc()){
echo "<tr>";
echo "<td>".$u['username']."</td>";
echo "<td>".$u['full_name']."</td>";
echo "<td>".$u['role']."</td>";
echo "</tr>";
}
?>
</table>

<?php } else { ?>

<h2>ระบบการยืมคืนหนังสือ</h2>
<form method="post">
<input name="username" placeholder="Username" required>
<input type="password" name="password" placeholder="Password" required>
<button name="login">Login</button>
</form>
<div class="message"><?= $message ?></div>
<a href="?action=register">สมัครสมาชิก</a>

<?php } ?>

</div>
</body>
</html>
