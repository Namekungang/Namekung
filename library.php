<?php
$host = "localhost";
$user = "s673190120";
$pass = "s673190120";
$db   = "s673190120"; // ต้องสร้าง DB นี้ไว้ก่อน

$conn = new mysqli("localhost","root","","library_system");
if ($conn->connect_error) die("Connection failed");

if (isset($_POST['register'])) {

    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $fullname = $_POST['full_name'];

    $check = $conn->query("SELECT id FROM users WHERE username='$username'");
    if ($check->num_rows > 0) {
        echo "Username นี้มีอยู่แล้ว";
    } else {
        $conn->query("INSERT INTO users (username,password,full_name)
                      VALUES ('$username','$password','$fullname')");
        echo "สมัครสมาชิกสำเร็จ <a href='login.php'>เข้าสู่ระบบ</a>";
    }
}
session_start();
session_destroy();
header("Location: login.php");
session_start();
$conn = new mysqli("localhost","root","","library_system");
if ($conn->connect_error) die("Connection failed");

if (isset($_POST['login'])) {

    $username = $_POST['username'];
    $password = $_POST['password'];

    $result = $conn->query("SELECT * FROM users WHERE username='$username'");
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header("Location: dashboard.php");
    } else {
        echo "Username หรือ Password ไม่ถูกต้อง";
    }
}
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
}
?>

<h2>สมัครสมาชิก</h2>
<form method="post">
    <input type="text" name="full_name" placeholder="ชื่อ-สกุล" required><br><br>
    <input type="text" name="username" placeholder="Username" required><br><br>
    <input type="password" name="password" placeholder="Password" required><br><br>
    <button name="register">สมัครสมาชิก</button>
</form>

<a href="login.php">ไปหน้า Login</a>
?>
<h2>เข้าสู่ระบบ</h2>
<form method="post">
    <input type="text" name="username" placeholder="Username" required><br><br>
    <input type="password" name="password" placeholder="Password" required><br><br>
    <button name="login">Login</button>
</form>

<a href="register.php">สมัครสมาชิก</a>
<h2>ยินดีต้อนรับ <?php echo $_SESSION['username']; ?></h2>

<a href="logout.php">Logout</a>
