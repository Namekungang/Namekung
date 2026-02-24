<?php
session_start();

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

/* ================== SAMPLE BOOK ================== */
$result = $conn->query("SELECT id FROM books LIMIT 1");
if ($result && $result->num_rows == 0) {
    $conn->query("INSERT INTO books (book_name) VALUES
    ('PHP Programming'),
    ('MySQL Database'),
    ('Web Development')");
}

/* ================== ACTION ================== */
$action = isset($_GET['action']) ? $_GET['action'] : 'login';
$message = "";

/* ================== LOGIN PROTECTION ================== */
$allow = array('login','register');

if (!isset($_SESSION['user_id']) && !in_array($action,$allow)) {
    header("Location:?action=login");
    exit();
}

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
    $selectedRole = $_POST['role'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username=?");
    $stmt->bind_param("s",$username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($password,$user['password'])) {

            if ($selectedRole == "admin" && $user['role'] != "admin") {
                $message = "ไม่มีสิทธิ์เข้าแบบ Admin";
            } else {
                $_SESSION['user_id']=$user['id'];
                $_SESSION['full_name']=$user['full_name'];
                $_SESSION['role']=$user['role'];
                header("Location:?action=dashboard"); exit();
            }

        } else {
            $message = "รหัสผ่านไม่ถูกต้อง";
        }
    } else {
        $message = "ไม่พบ Username";
    }
}

/* ================== ADD BOOK ================== */
if ($action == 'add_book' && isset($_SESSION['role']) && $_SESSION['role']=="admin") {
    $book = $_POST['book_name'];
    $stmt = $conn->prepare("INSERT INTO books(book_name) VALUES(?)");
    $stmt->bind_param("s",$book);
    $stmt->execute();
    header("Location:?action=dashboard"); exit();
}

/* ================== BORROW ================== */
if ($action == 'borrow' && isset($_GET['book']) && isset($_SESSION['user_id'])) {

    $book_id = intval($_GET['book']);
    $user_id = $_SESSION['user_id'];

    $stmt = $conn->prepare("INSERT INTO borrow(user_id,book_id) VALUES(?,?)");
    $stmt->bind_param("ii",$user_id,$book_id);
    $stmt->execute();

    $conn->query("UPDATE books SET status='borrowed' WHERE id=$book_id");
    header("Location:?action=mybooks"); exit();
}

/* ================== RETURN ================== */
if ($action == 'return' && isset($_GET['borrow_id']) && isset($_SESSION['user_id'])) {

    $borrow_id = intval($_GET['borrow_id']);

    $conn->query("UPDATE borrow SET return_date=NOW() WHERE id=$borrow_id");
    $conn->query("UPDATE books SET status='available'
                  WHERE id=(SELECT book_id FROM borrow WHERE id=$borrow_id)");

    header("Location:?action=mybooks"); exit();
}

/* ================== LOGOUT ================== */
if ($action == 'logout') {
    session_destroy();
    header("Location:?action=login"); exit();
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
input,button,select{padding:10px;width:100%;margin:5px 0;}
button{background:#007bff;color:white;border:none;}
table{width:100%;border-collapse:collapse;margin-top:10px;}
th,td{border:1px solid #ddd;padding:10px;text-align:center;}
.message{color:red;}
</style>
</head>
<body>
<div class="container">

<?php if($action=="dashboard"){ ?>

<h1>📚 ระบบยืมคืนหนังสือ</h1>
<p><b>ชื่อ:</b> <?=htmlspecialchars($_SESSION['full_name'])?>
(<?=htmlspecialchars($_SESSION['role'])?>)</p>

<div class="menu">
<a href="?action=borrow_page">ยืมหนังสือ</a>
<a href="?action=mybooks">หนังสือของฉัน</a>
<?php if($_SESSION['role']=="admin"){ ?>
<a href="?action=manage_users">ดูสมาชิก</a>
<?php } ?>
<a href="?action=logout">Logout</a>
</div>

<?php if($_SESSION['role']=="admin"){ ?>
<hr>
<h3>เพิ่มหนังสือ</h3>
<form method="post" action="?action=add_book">
<input name="book_name" placeholder="ชื่อหนังสือ" required>
<button>เพิ่มหนังสือ</button>
</form>
<?php } ?>

<?php } elseif($action=="manage_users" && $_SESSION['role']=="admin"){ ?>

<h2>สมาชิกทั้งหมด</h2>
<a href="?action=dashboard">กลับ</a>
<table>
<tr><th>ID</th><th>Username</th><th>ชื่อ</th><th>Role</th></tr>
<?php
$res=$conn->query("SELECT * FROM users");
while($u=$res->fetch_assoc()){
echo "<tr>
<td>{$u['id']}</td>
<td>{$u['username']}</td>
<td>{$u['full_name']}</td>
<td>{$u['role']}</td>
</tr>";
}
?>
</table>

<?php } elseif($action=="borrow_page"){ ?>

<h2>เลือกหนังสือ</h2>
<a href="?action=dashboard">กลับ</a>
<table>
<tr><th>ชื่อหนังสือ</th><th>สถานะ</th><th>จัดการ</th></tr>
<?php
$books=$conn->query("SELECT * FROM books");
while($b=$books->fetch_assoc()){
echo "<tr>";
echo "<td>{$b['book_name']}</td>";
echo "<td>{$b['status']}</td>";
if($b['status']=="available"){
echo "<td><a href='?action=borrow&book={$b['id']}'>ยืม</a></td>";
}else{
echo "<td>-</td>";
}
echo "</tr>";
}
?>
</table>

<?php } elseif($action=="mybooks"){ ?>

<h2>หนังสือที่ยืม</h2>
<a href="?action=dashboard">กลับ</a>
<table>
<tr><th>หนังสือ</th><th>วันที่ยืม</th><th>วันที่คืน</th><th>จัดการ</th></tr>
<?php
$user=$_SESSION['user_id'];
$sql="SELECT borrow.*,books.book_name 
FROM borrow JOIN books ON borrow.book_id=books.id
WHERE borrow.user_id=$user";
$res=$conn->query($sql);
while($r=$res->fetch_assoc()){
echo "<tr>";
echo "<td>{$r['book_name']}</td>";
echo "<td>{$r['borrow_date']}</td>";
echo "<td>".($r['return_date']?$r['return_date']:'-')."</td>";
if(!$r['return_date']){
echo "<td><a href='?action=return&borrow_id={$r['id']}'>คืน</a></td>";
}else{
echo "<td>-</td>";
}
echo "</tr>";
}
?>
</table>

<?php } elseif($action=="register"){ ?>

<h2>สมัครสมาชิก</h2>
<form method="post">
<input name="full_name" placeholder="ชื่อ-สกุล" required>
<input name="username" placeholder="Username" required>
<input type="password" name="password" placeholder="Password" required>
<button name="register">สมัคร</button>
</form>
<div class="message"><?=$message?></div>
<a href="?action=login">กลับหน้า Login</a>

<?php } else { ?>

<h2>เข้าสู่ระบบ</h2>
<form method="post">
<input name="username" placeholder="Username" required>
<input type="password" name="password" placeholder="Password" required>
<select name="role">
<option value="user">เข้าสู่ระบบแบบ User</option>
<option value="admin">เข้าสู่ระบบแบบ Admin</option>
</select>
<button name="login">Login</button>
</form>
<div class="message"><?=$message?></div>
<a href="?action=register">สมัครสมาชิก</a>

<?php } ?>

</div>
</body>
</html>