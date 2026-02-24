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

            if ($selectedRole == "admin") {
                if ($user['role'] != "admin") {
                    $message = "ไม่มีสิทธิ์เข้าแบบ Admin";
                } else {
                    $_SESSION['user_id']=$user['id'];
                    $_SESSION['full_name']=$user['full_name'];
                    $_SESSION['role']=$user['role'];
                    header("Location:?action=dashboard"); exit();
                }
            } else {
                $_SESSION['user_id']=$user['id'];
                $_SESSION['full_name']=$user['full_name'];
                $_SESSION['role']="user";
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
    header("Location:?"); exit();
}
?>