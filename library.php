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
    <title>ระบบการยืมคืนหนังสือ</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(to right, #4e73df, #1cc88a);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .card {
            background: white;
            padding: 30px;
            width: 350px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            text-align: center;
        }
        h1 {
            margin-bottom: 20px;
            color: #333;
        }
        input {
            width: 90%;
            padding: 10px;
            margin: 8px 0;
            border-radius: 5px;
            border: 1px solid #ccc;
        }
        button {
            width: 95%;
            padding: 10px;
            background: #4e73df;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background: #2e59d9;
        }
        a {
            text-decoration: none;
            color: #4e73df;
            font-size: 14px;
        }
        .message {
            color: red;
            margin-top: 10px;
        }
        .success {
            color: green;
        }
    </style>
</head>
<body>

<div class="card">

<?php if ($action == 'register') { ?>

    <h1>📚 สมัครสมาชิก</h1>
    <form method="post">
        <input type="text" name="full_name" placeholder="ชื่อ-สกุล" required>
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button name="register">สมัครสมาชิก</button>
    </form>

    <div class="message"><?php echo $message; ?></div>
    <br>
    <a href="index.php">ไปหน้า Login</a>

<?php } elseif ($action == 'dashboard' && isset($_SESSION['user_id'])) { ?>

    <h1>📚 ระบบการยืมคืนหนังสือ</h1>
    <p>ยินดีต้อนรับ <strong><?php echo $_SESSION['username']; ?></strong></p>
    <br>
    <a href="index.php?action=logout">Logout</a>

<?php } else { ?>

    <h1>📚 ระบบการยืมคืนหนังสือ</h1>
    <form method="post">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button name="login">Login</button>
    </form>

    <div class="message"><?php echo $message; ?></div>
    <br>
    <a href="index.php?action=register">สมัครสมาชิก</a>

<?php } ?>

</div>

</body>
</html>
