<?php
session_start();

$conn = new mysqli("localhost","s673190120","s673190120","s673190120");
if ($conn->connect_error) {
    die("เชื่อมต่อฐานข้อมูลไม่ได้: " . $conn->connect_error);
}

/* ================= CREATE TABLE ================= */

$conn->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    full_name VARCHAR(255),
    role ENUM('admin','user') DEFAULT 'user'
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

/* ================= CREATE ADMIN AUTO ================= */

$checkAdmin = $conn->query("SELECT id FROM users WHERE username='admin'");
if ($checkAdmin->num_rows == 0) {
    $adminPass = password_hash("admin", PASSWORD_DEFAULT);
    $conn->query("INSERT INTO users(username,password,full_name,role)
                  VALUES('admin','$adminPass','Administrator','admin')");
}

/* ================= SAMPLE BOOK ================= */

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

/* ================= REGISTER ================= */

if ($action == 'register' && isset($_POST['register'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $fullname = $_POST['full_name'];

    $check = $conn->query("SELECT id FROM users WHERE username='$username'");
    if ($check->num_rows > 0) {
        $message = "Username นี้มีอยู่แล้ว";
    } else {
        $conn->query("INSERT INTO users(username,password,full_name,role)
                      VALUES('$username','$password','$fullname','user')");
        $message = "สมัครสมาชิกสำเร็จ";
    }
}

/* ================= LOGIN ================= */

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

/* ================= ADD BOOK ================= */

if ($action == 'add_book' && isset($_POST['add_book']) && $_SESSION['role']=='admin') {
    $book = $_POST['book_name'];
    $conn->query("INSERT INTO books(book_name) VALUES('$book')");
    header("Location: ?action=manage_books"); exit();
}

/* ================= BORROW ================= */

if ($action == 'borrow' && isset($_GET['book']) && $_SESSION['role']=='user') {
    $book_id = $_GET['book'];
    $user_id = $_SESSION['user_id'];

    $conn->query("INSERT INTO borrow(user_id,book_id) VALUES('$user_id','$book_id')");
    $conn->query("UPDATE books SET status='borrowed' WHERE id='$book_id'");
    header("Location: ?action=mybooks"); exit();
}

/* ================= RETURN ================= */

if ($action == 'return') {
    $borrow_id = $_GET['borrow_id'];
    $conn->query("UPDATE borrow SET return_date=NOW() WHERE id='$borrow_id'");
    $conn->query("UPDATE books SET status='available'
                  WHERE id=(SELECT book_id FROM borrow WHERE id='$borrow_id')");
    header("Location: ?action=mybooks"); exit();
}

/* ================= LOGOUT ================= */

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
body{font-family:Arial;background:linear-gradient(to right,#4e73df,#1cc88a);margin:0;}
.container{width:900px;margin:auto;background:white;margin-top:30px;padding:30px;border-radius:10px;}
.menu a{padding:10px;background:#4e73df;color:white;text-decoration:none;margin-right:5px;border-radius:5px;}
input,button{padding:10px;margin:5px 0;width:100%;}
button{background:#4e73df;color:white;border:none;}
table{width:100%;border-collapse:collapse;}
table,th,td{border:1px solid #ddd;}
th,td{padding:10px;text-align:center;}
.message{color:red;}
.info-box{background:#f8f9fc;padding:10px;border-radius:8px;margin-bottom:15px;}
</style>
</head>
<body>
<div class="container">

<?php if ($action == 'dashboard' && isset($_SESSION['user_id'])) { ?>

<h2>ยินดีต้อนรับ <?php echo $_SESSION['username']; ?> (<?php echo $_SESSION['role']; ?>)</h2>

<div class="menu">
<?php if($_SESSION['role']=='admin'){ ?>
<a href="?action=members">ดูสมาชิก</a>
<a href="?action=manage_books">จัดการหนังสือ</a>
<?php } else { ?>
<a href="?action=borrow_page">ยืมหนังสือ</a>
<a href="?action=mybooks">รายการที่ยืม</a>
<?php } ?>
<a href="?action=logout">Logout</a>
</div>

<?php } elseif ($action == 'manual') { ?>

<h2>📖 คู่มือการใช้งาน</h2>

<h3>👨‍💼 สำหรับ Admin</h3>
<ul>
<li>เข้าสู่ระบบด้วย Username: <b>admin</b></li>
<li>Password: <b>admin</b></li>
<li>ดูรายชื่อสมาชิก</li>
<li>เพิ่มหนังสือเข้าระบบ</li>
</ul>

<h3>👤 สำหรับ User</h3>
<ul>
<li>สมัครสมาชิกก่อนเข้าใช้งาน</li>
<li>ยืมหนังสือที่สถานะ available</li>
<li>คืนหนังสือได้ที่หน้า "รายการที่ยืม"</li>
</ul>

<a href="?">กลับหน้า Login</a>

<?php } elseif ($action == 'register') { ?>

<h2>สมัครสมาชิก</h2>
<form method="post">
<input type="text" name="full_name" placeholder="ชื่อ-สกุล" required>
<input type="text" name="username" placeholder="Username" required>
<input type="password" name="password" placeholder="Password" required>
<button name="register">สมัครสมาชิก</button>
</form>
<div class="message"><?php echo $message; ?></div>
<a href="?">กลับหน้า Login</a>

<?php } else { ?>

<h2>Login</h2>

<div class="info-box">
<b>บัญชีผู้ดูแลระบบ</b><br>
Username: <b>admin</b><br>
Password: <b>admin</b>
</div>

<form method="post">
<input type="text" name="username" required>
<input type="password" name="password" required>
<button name="login">Login</button>
</form>

<div class="message"><?php echo $message; ?></div>

<a href="?action=register">สมัครสมาชิก</a> |
<a href="?action=manual">คู่มือการใช้งาน</a>

<?php } ?>

</div>
</body>
</html>