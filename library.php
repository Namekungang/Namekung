<?php

/* ================== CONNECT DATABASE ================== */
$conn = new mysqli("localhost","s673190120","s673190120","s673190120");
if ($conn->connect_error) { die("เชื่อมต่อฐานข้อมูลไม่ได้"); }

/* ================== CREATE TABLE ================== */
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

/* ================== CREATE ADMIN ================== */
$adminUser = "surasit";
$check = $conn->prepare("SELECT id FROM users WHERE username=?");
$check->bind_param("s",$adminUser);
$check->execute();
$check->store_result();

if ($check->num_rows == 0) {
    $pass = password_hash("1234", PASSWORD_DEFAULT);
    $fullname = "ผู้ดูแลระบบ";
    $role = "admin";
    $stmt = $conn->prepare("INSERT INTO users(username,password,full_name,role) VALUES(?,?,?,?)");
    $stmt->bind_param("ssss",$adminUser,$pass,$fullname,$role);
    $stmt->execute();
}

/* ================== ACTION ================== */
$action = isset($_GET['action']) ? $_GET['action'] : 'login';
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$role = isset($_GET['role']) ? $_GET['role'] : '';
$message = "";

/* ================== REGISTER ================== */
if ($action == 'register' && isset($_POST['register'])) {

    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $fullname = $_POST['full_name'];

    $check = $conn->prepare("SELECT id FROM users WHERE username=?");
    $check->bind_param("s",$username);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $message = "Username นี้มีอยู่แล้ว";
    } else {
        $stmt = $conn->prepare("INSERT INTO users(username,password,full_name,role) VALUES(?,?,?,'user')");
        $stmt->bind_param("sss",$username,$password,$fullname);
        $stmt->execute();
        $message = "สมัครสมาชิกสำเร็จ";
    }
}

/* ================== LOGIN ================== */
if ($action == 'login' && isset($_POST['login'])) {

    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username=?");
    $stmt->bind_param("s",$username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($password,$user['password'])) {

            header("Location:?action=dashboard&user_id=".$user['id']."&role=".$user['role']);
            exit();

        } else {
            $message = "รหัสผ่านไม่ถูกต้อง";
        }
    } else {
        $message = "ไม่พบ Username";
    }
}

/* ================== ADD BOOK (ADMIN ONLY) ================== */
if ($action == 'add_book' && $role == "admin") {

    $book = $_POST['book_name'];
    $stmt = $conn->prepare("INSERT INTO books(book_name) VALUES(?)");
    $stmt->bind_param("s",$book);
    $stmt->execute();

    header("Location:?action=dashboard&user_id=$user_id&role=$role");
    exit();
}

/* ================== BORROW ================== */
if ($action == 'borrow' && $user_id > 0) {

    $book_id = intval($_GET['book']);

    $stmt = $conn->prepare("INSERT INTO borrow(user_id,book_id) VALUES(?,?)");
    $stmt->bind_param("ii",$user_id,$book_id);
    $stmt->execute();

    $conn->query("UPDATE books SET status='borrowed' WHERE id=$book_id");

    header("Location:?action=mybooks&user_id=$user_id&role=$role");
    exit();
}

/* ================== RETURN ================== */
if ($action == 'return' && $user_id > 0) {

    $borrow_id = intval($_GET['borrow_id']);

    $conn->query("UPDATE borrow SET return_date=NOW() WHERE id=$borrow_id");
    $conn->query("UPDATE books SET status='available'
                  WHERE id=(SELECT book_id FROM borrow WHERE id=$borrow_id)");

    header("Location:?action=mybooks&user_id=$user_id&role=$role");
    exit();
}

?>
<!DOCTYPE html>
<html>
<head>
<title>ระบบยืมคืนหนังสือ</title>
<style>
body{font-family:Arial;background:#f2f2f2;}
.container{width:1000px;margin:auto;background:white;padding:30px;margin-top:30px;border-radius:10px;}
.menu a{padding:8px 12px;background:#007bff;color:white;text-decoration:none;margin-right:5px;border-radius:5px;}
input,button{padding:10px;width:100%;margin:5px 0;}
button{background:#007bff;color:white;border:none;}
table{width:100%;border-collapse:collapse;margin-top:10px;}
th,td{border:1px solid #ddd;padding:10px;text-align:center;}
.message{color:red;}
</style>
</head>
<body>
<div class="container">

<?php if($action=="dashboard" && $user_id>0){ ?>

<h1>📚 ระบบยืมคืนหนังสือ</h1>
<p>Role: <?=htmlspecialchars($role)?></p>

<div class="menu">
<a href="?action=borrow_page&user_id=<?=$user_id?>&role=<?=$role?>">ยืมหนังสือ</a>
<a href="?action=mybooks&user_id=<?=$user_id?>&role=<?=$role?>">หนังสือของฉัน</a>
<?php if($role=="admin"){ ?>
<a href="?action=manage_users&user_id=<?=$user_id?>&role=<?=$role?>">ดูสมาชิก</a>
<?php } ?>
</div>

<?php if($role=="admin"){ ?>
<hr>
<h3>เพิ่มหนังสือ</h3>
<form method="post" action="?action=add_book&user_id=<?=$user_id?>&role=<?=$role?>">
<input name="book_name" required>
<button>เพิ่มหนังสือ</button>
</form>
<?php } ?>

<?php } elseif($action=="login"){ ?>

<h2>เข้าสู่ระบบ</h2>
<form method="post">
<input name="username" required>
<input type="password" name="password" required>
<button name="login">Login</button>
</form>
<div class="message"><?=$message?></div>

<?php } ?>

</div>
</body>
</html>