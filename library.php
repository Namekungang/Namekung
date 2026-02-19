<?php
session_start();

$conn = new mysqli("localhost","s673190120","s673190120","s673190120");
if ($conn->connect_error) die("DB Error");

/* ================= CREATE TABLE ================= */

$conn->query("CREATE TABLE IF NOT EXISTS users(
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    full_name VARCHAR(255),
    role ENUM('user','admin','superadmin') DEFAULT 'user'
)");

$conn->query("CREATE TABLE IF NOT EXISTS books(
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_name VARCHAR(255),
    status ENUM('available','borrowed') DEFAULT 'available'
)");

$conn->query("CREATE TABLE IF NOT EXISTS borrow(
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    book_id INT,
    borrow_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    return_date DATETIME NULL
)");

/* ================= CREATE SUPERADMIN ================= */

$check = $conn->query("SELECT id FROM users WHERE role='superadmin'");
if($check->num_rows==0){
    $pass = password_hash("super123",PASSWORD_DEFAULT);
    $stmt=$conn->prepare("INSERT INTO users(username,password,full_name,role) VALUES(?,?,?,?)");
    $role="superadmin";
    $stmt->bind_param("ssss",$u,$pass,$f,$role);
    $u="superadmin"; $f="Super Admin";
    $stmt->execute();
}

/* ================= FUNCTION ROLE ================= */

function requireRole($roles){
    if(!isset($_SESSION['role']) || !in_array($_SESSION['role'],$roles)){
        die("<h2>403 Forbidden</h2>");
    }
}

$action=$_GET['action']??'login';
$message="";

/* ================= REGISTER ================= */

if($action=='register' && isset($_POST['register'])){
    $stmt=$conn->prepare("INSERT INTO users(username,password,full_name) VALUES(?,?,?)");
    $pass=password_hash($_POST['password'],PASSWORD_DEFAULT);
    $stmt->bind_param("sss",$_POST['username'],$pass,$_POST['full_name']);
    if($stmt->execute()) $message="สมัครสมาชิกสำเร็จ";
    else $message="Username ซ้ำ";
}

/* ================= LOGIN ================= */

if($action=='login' && isset($_POST['login'])){
    $stmt=$conn->prepare("SELECT * FROM users WHERE username=?");
    $stmt->bind_param("s",$_POST['username']);
    $stmt->execute();
    $res=$stmt->get_result();
    if($res->num_rows>0){
        $user=$res->fetch_assoc();
        if(password_verify($_POST['password'],$user['password'])){
            $_SESSION['user_id']=$user['id'];
            $_SESSION['username']=$user['username'];
            $_SESSION['role']=$user['role'];
            header("Location:?action=dashboard"); exit();
        }
    }
    $message="Login ไม่ถูกต้อง";
}

/* ================= LOGOUT ================= */

if($action=='logout'){
    session_destroy();
    header("Location:?"); exit();
}

/* ================= ROLE CHECK ================= */

switch($action){

    case 'borrow':
    case 'return':
    case 'mybooks':
        requireRole(['user','admin','superadmin']);
        break;

    case 'admin_books':
    case 'add_book':
    case 'delete_book':
    case 'all_borrow':
        requireRole(['admin','superadmin']);
        break;

    case 'admin_users':
    case 'change_role':
    case 'delete_user':
        requireRole(['superadmin']);
        break;
}

/* ================= BORROW ================= */

if($action=='borrow'){
    $stmt=$conn->prepare("INSERT INTO borrow(user_id,book_id) VALUES(?,?)");
    $stmt->bind_param("ii",$_SESSION['user_id'],$_GET['book']);
    $stmt->execute();
    $conn->query("UPDATE books SET status='borrowed' WHERE id=".$_GET['book']);
    header("Location:?action=mybooks"); exit();
}

/* ================= RETURN ================= */

if($action=='return'){
    $stmt=$conn->prepare("SELECT * FROM borrow WHERE id=? AND user_id=?");
    $stmt->bind_param("ii",$_GET['id'],$_SESSION['user_id']);
    $stmt->execute();
    if($stmt->get_result()->num_rows==0) die("ไม่มีสิทธิ์");

    $conn->query("UPDATE borrow SET return_date=NOW() WHERE id=".$_GET['id']);
    $conn->query("UPDATE books SET status='available' 
                  WHERE id=(SELECT book_id FROM borrow WHERE id=".$_GET['id'].")");
    header("Location:?action=mybooks"); exit();
}

/* ================= ADMIN BOOK ================= */

if($action=='add_book' && isset($_POST['add'])){
    $stmt=$conn->prepare("INSERT INTO books(book_name) VALUES(?)");
    $stmt->bind_param("s",$_POST['book']);
    $stmt->execute();
}

if($action=='delete_book'){
    $conn->query("DELETE FROM books WHERE id=".$_GET['id']);
    header("Location:?action=admin_books"); exit();
}

/* ================= SUPERADMIN USER ================= */

if($action=='change_role'){
    if($_GET['id']==$_SESSION['user_id']) die("ห้ามแก้ตัวเอง");
    $role=$_GET['role'];
    if(!in_array($role,['user','admin','superadmin'])) die("ผิดพลาด");
    $stmt=$conn->prepare("UPDATE users SET role=? WHERE id=?");
    $stmt->bind_param("si",$role,$_GET['id']);
    $stmt->execute();
    header("Location:?action=admin_users"); exit();
}

if($action=='delete_user'){
    if($_GET['id']==$_SESSION['user_id']) die("ห้ามลบตัวเอง");
    $conn->query("DELETE FROM users WHERE id=".$_GET['id']);
    header("Location:?action=admin_users"); exit();
}
?>
<!DOCTYPE html>
<html>
<head>
<title>ระบบยืมคืนหนังสือ</title>
<style>
body{font-family:Arial;background:#f4f6f9;margin:0}
.container{width:1000px;margin:auto;background:white;padding:30px;margin-top:30px;border-radius:10px}
.menu a{padding:8px 12px;background:#4e73df;color:white;text-decoration:none;border-radius:5px;margin-right:5px}
table{width:100%;border-collapse:collapse}
th,td{border:1px solid #ddd;padding:8px;text-align:center}
button{padding:8px;background:#4e73df;color:white;border:none}
</style>
</head>
<body>
<div class="container">

<?php if($action=='register'){ ?>

<h2>สมัครสมาชิก</h2>
<form method="post">
<input name="full_name" placeholder="ชื่อ-สกุล" required><br><br>
<input name="username" placeholder="Username" required><br><br>
<input type="password" name="password" placeholder="Password" required><br><br>
<button name="register">สมัครสมาชิก</button>
</form>
<?=$message?>
<a href="?">Login</a>

<?php } elseif($action=='dashboard' && isset($_SESSION['user_id'])){ ?>

<h2>Dashboard (<?=$_SESSION['role']?>)</h2>
<div class="menu">
<a href="?action=mybooks">รายการของฉัน</a>
<a href="?action=borrow_page">ยืมหนังสือ</a>

<?php if($_SESSION['role']=='admin'||$_SESSION['role']=='superadmin'){ ?>
<a href="?action=admin_books">จัดการหนังสือ</a>
<a href="?action=all_borrow">รายการยืมทั้งหมด</a>
<?php } ?>

<?php if($_SESSION['role']=='superadmin'){ ?>
<a href="?action=admin_users">จัดการสมาชิก</a>
<?php } ?>

<a href="?action=logout">Logout</a>
</div>

<?php } elseif($action=='borrow_page'){ ?>

<h3>เลือกหนังสือ</h3>
<table>
<tr><th>ชื่อหนังสือ</th><th>สถานะ</th><th></th></tr>
<?php
$r=$conn->query("SELECT * FROM books");
while($b=$r->fetch_assoc()){
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

<?php } elseif($action=='mybooks'){ ?>

<h3>รายการของฉัน</h3>
<table>
<tr><th>หนังสือ</th><th>วันที่ยืม</th><th>คืน</th></tr>
<?php
$stmt=$conn->prepare("SELECT borrow.*,books.book_name 
FROM borrow JOIN books ON borrow.book_id=books.id
WHERE user_id=?");
$stmt->bind_param("i",$_SESSION['user_id']);
$stmt->execute();
$res=$stmt->get_result();
while($row=$res->fetch_assoc()){
echo "<tr>";
echo "<td>".$row['book_name']."</td>";
echo "<td>".$row['borrow_date']."</td>";
echo "<td>";
if(!$row['return_date'])
echo "<a href='?action=return&id=".$row['id']."'>คืน</a>";
else echo "คืนแล้ว";
echo "</td></tr>";
}
?>
</table>

<?php } elseif($action=='admin_books'){ ?>

<h3>จัดการหนังสือ</h3>
<form method="post" action="?action=add_book">
<input name="book" placeholder="ชื่อหนังสือ">
<button name="add">เพิ่ม</button>
</form>
<table>
<tr><th>ชื่อหนังสือ</th><th></th></tr>
<?php
$r=$conn->query("SELECT * FROM books");
while($b=$r->fetch_assoc()){
echo "<tr><td>".$b['book_name']."</td>
<td><a href='?action=delete_book&id=".$b['id']."'>ลบ</a></td></tr>";
}
?>
</table>

<?php } elseif($action=='admin_users'){ ?>

<h3>จัดการสมาชิก</h3>
<table>
<tr><th>Username</th><th>Role</th><th>จัดการ</th></tr>
<?php
$r=$conn->query("SELECT * FROM users");
while($u=$r->fetch_assoc()){
echo "<tr>";
echo "<td>".$u['username']."</td>";
echo "<td>".$u['role']."</td>";
echo "<td>";
if($u['id']!=$_SESSION['user_id']){
echo "<a href='?action=change_role&id=".$u['id']."&role=user'>User</a> ";
echo "<a href='?action=change_role&id=".$u['id']."&role=admin'>Admin</a> ";
echo "<a href='?action=delete_user&id=".$u['id']."'>ลบ</a>";
}
echo "</td></tr>";
}
?>
</table>

<?php } else { ?>

<h2>Login</h2>
<form method="post">
<input name="username" placeholder="Username"><br><br>
<input type="password" name="password" placeholder="Password"><br><br>
<button name="login">Login</button>
</form>
<?=$message?>
<br><a href="?action=register">สมัครสมาชิก</a>

<?php } ?>

</div>
</body>
</html>
