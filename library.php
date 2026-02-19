<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

$conn = new mysqli("localhost","s673190120","s673190120","s673190120");
if ($conn->connect_error) {
    die("เชื่อมต่อฐานข้อมูลไม่ได้: " . $conn->connect_error);
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
        $conn->query("INSERT INTO users (username,password,full_name)
                      VALUES ('$username','$password','$fullname')");
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
            header("Location: index.php?action=dashboard");
            exit();
        } else {
            $message = "รหัสผ่านไม่ถูกต้อง";
        }
    } else {
        $message = "ไม่พบ Username นี้";
    }
}

/* ================= LOGOUT ================= */
if ($action == 'logout') {
    session_destroy();
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Simple Login System</title>
</head>
<body>

<?php if ($action == 'register') { ?>

    <h2>สมัครสมาชิก</h2>
    <form method="post">
        <input type="text" name="full_name" placeholder="ชื่อ-สกุล" required><br><br>
        <input type="text" name="username" placeholder="Username" required><br><br>
        <input type="password" name="password" placeholder="Password" required><br><br>
        <button name="register">สมัครสมาชิก</button>
    </form>

    <p style="color:red;"><?php echo $message; ?></p>
    <a href="index.php">ไปหน้า Login</a>

<?php } elseif ($action == 'dashboard' && isset($_SESSION['user_id'])) { ?>

    <h2>ยินดีต้อนรับ <?php echo $_SESSION['username']; ?></h2>
    <a href="index.php?action=logout">Logout</a>

<?php } else { ?>

    <h2>เข้าสู่ระบบ</h2>
    <form method="post">
        <input type="text" name="username" placeholder="Username" required><br><br>
        <input type="password" name="password" placeholder="Password" required><br><br>
        <button name="login">Login</button>
    </form>

    <p style="color:red;"><?php echo $message; ?></p>
    <a href="index.php?action=register">สมัครสมาชิก</a>

<?php } ?>

</body>
</html>
