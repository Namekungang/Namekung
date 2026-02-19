<?php
session_start();
$conn = new mysqli("localhost","s673190120","s673190120","s673190120");
if ($conn->connect_error) { die("เชื่อมต่อฐานข้อมูลไม่ได้"); }

$action = $_GET['action'] ?? 'login';
$message = "";

/* REGISTER */
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

/* LOGIN */
if ($action == 'login' && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $result = $conn->query("SELECT * FROM users WHERE username='$username'");
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: index.php?action=dashboard"); exit();
        } else { $message = "รหัสผ่านไม่ถูกต้อง"; }
    } else { $message = "ไม่พบ Username"; }
}

/* BORROW */
if ($action == 'borrow' && isset($_GET['book'])) {
    $book_id = $_GET['book'];
    $user_id = $_SESSION['user_id'];
    $conn->query("INSERT INTO borrow(user_id,book_id) VALUES('$user_id','$book_id')");
    $conn->query("UPDATE books SET status='borrowed' WHERE id='$book_id'");
    header("Location: index.php?action=mybooks");
}

/* RETURN */
if ($action == 'return' && isset($_GET['borrow_id'])) {
    $borrow_id = $_GET['borrow_id'];
    $conn->query("UPDATE borrow SET return_date=NOW() WHERE id='$borrow_id'");
    $conn->query("UPDATE books 
                  SET status='available' 
                  WHERE id=(SELECT book_id FROM borrow WHERE id='$borrow_id')");
    header("Location: index.php?action=mybooks");
}

/* LOGOUT */
if ($action == 'logout') {
    session_destroy();
    header("Location: index.php"); exit();
}
?>

<!DOCTYPE html>
<html>
<head>
<title>ระบบการยืมคืนหนังสือ</title>
<style>
body{
    font-family: Arial;
    background: linear-gradient(to right,#4e73df,#1cc88a);
    margin:0;
}
.container{
    width:900px;
    margin:auto;
    background:white;
    margin-top:30px;
    padding:30px;
    border-radius:10px;
    box-shadow:0 5px 20px rgba(0,0,0,0.2);
}
h1{ text-align:center; }
.menu a{
    padding:10px 15px;
    background:#4e73df;
    color:white;
    text-decoration:none;
    margin-right:5px;
    border-radius:5px;
}
.menu{ margin-bottom:20px; }
input,button{
    padding:10px;
    margin:5px 0;
    width:100%;
}
button{
    background:#4e73df;
    color:white;
    border:none;
    cursor:pointer;
}
table{
    width:100%;
    border-collapse:collapse;
}
table,th,td{
    border:1px solid #ddd;
}
th,td{
    padding:10px;
    text-align:center;
}
.message{ color:red; }
</style>
</head>
<body>

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
<a href="index.php">ไปหน้า Login</a>

<?php } elseif ($action == 'dashboard' && isset($_SESSION['user_id'])) { ?>

<h1>📚 ระบบการยืมคืนหนังสือ</h1>
<div class="menu">
<a href="index.php?action=borrow_page">ยืมหนังสือ</a>
<a href="index.php?action=mybooks">รายการที่ยืม</a>
<a href="index.php?action=logout">Logout</a>
</div>
<p>ยินดีต้อนรับ <?php echo $_SESSION['username']; ?></p>

<?php } elseif ($action == 'borrow_page') { ?>

<h1>เลือกหนังสือเพื่อยืม</h1>
<div class="menu">
<a href="index.php?action=dashboard">กลับหน้า Dashboard</a>
</div>

<table>
<tr><th>ชื่อหนังสือ</th><th>สถานะ</th><th>จัดการ</th></tr>
<?php
$books = $conn->query("SELECT * FROM books");
while($b = $books->fetch_assoc()){
echo "<tr>";
echo "<td>".$b['book_name']."</td>";
echo "<td>".$b['status']."</td>";
if($b['status']=="available"){
echo "<td><a href='index.php?action=borrow&book=".$b['id']."'>ยืม</a></td>";
}else{
echo "<td>-</td>";
}
echo "</tr>";
}
?>
</table>

<?php } elseif ($action == 'mybooks') { ?>

<h1>รายการที่ยืม</h1>
<div class="menu">
<a href="index.php?action=dashboard">กลับหน้า Dashboard</a>
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
echo "<td>".$row['book_name']."</td>";
echo "<td>".$row['borrow_date']."</td>";
echo "<td>".($row['return_date'] ?? '-')."</td>";
if(!$row['return_date']){
echo "<td><a href='index.php?action=return&borrow_id=".$row['id']."'>คืน</a></td>";
}else{
echo "<td>-</td>";
}
echo "</tr>";
}
?>
</table>

<?php } else { ?>

<h1>📚 ระบบการยืมคืนหนังสือ</h1>
<form method="post">
<input type="text" name="username" placeholder="Username" required>
<input type="password" name="password" placeholder="Password" required>
<button name="login">Login</button>
</form>
<div class="message"><?php echo $message; ?></div>
<a href="index.php?action=register">สมัครสมาชิก</a>

<?php } ?>

</div>
</body>
</html>
