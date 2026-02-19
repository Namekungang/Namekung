<?php
/* =============================
   SIMPLE LIBRARY SYSTEM (ONE FILE)
   Save as: library.php
   ============================= */

// ---------- DATABASE CONFIG ----------
$host = "localhost";
$user = "root";
$pass = "";
$db   = "library_system";

$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) die("Connection failed");

// Create Database if not exists
$conn->query("CREATE DATABASE IF NOT EXISTS $db");
$conn->select_db($db);

// Create Tables
$conn->query("CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    password VARCHAR(255),
    full_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255),
    author VARCHAR(255),
    quantity INT DEFAULT 1
)");

$conn->query("CREATE TABLE IF NOT EXISTS borrowings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    book_id INT,
    borrow_date DATE,
    return_date DATE NULL,
    status ENUM('borrowed','returned') DEFAULT 'borrowed'
)");

session_start();

// ---------- ROUTER ----------
$action = $_GET['action'] ?? 'home';

// ---------- REGISTER ----------
if ($action == 'register' && isset($_POST['register'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $fullname = $_POST['full_name'];
    $conn->query("INSERT INTO users(username,password,full_name) VALUES('$username','$password','$fullname')");
    header("Location: ?action=login");
}

// ---------- LOGIN ----------
if ($action == 'login' && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $result = $conn->query("SELECT * FROM users WHERE username='$username'");
    $user = $result->fetch_assoc();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header("Location: ?action=home");
    } else {
        echo "Login Failed";
    }
}

// ---------- LOGOUT ----------
if ($action == 'logout') {
    session_destroy();
    header("Location: ?action=login");
}

// ---------- BORROW ----------
if ($action == 'borrow' && isset($_GET['id'])) {
    $book_id = $_GET['id'];
    $user_id = $_SESSION['user_id'];
    $conn->query("INSERT INTO borrowings(user_id,book_id,borrow_date) VALUES($user_id,$book_id,CURDATE())");
    $conn->query("UPDATE books SET quantity=quantity-1 WHERE id=$book_id");
    header("Location: ?action=mybooks");
}

// ---------- RETURN ----------
if ($action == 'return' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $conn->query("UPDATE borrowings SET status='returned', return_date=CURDATE() WHERE id=$id");
    $borrow = $conn->query("SELECT book_id FROM borrowings WHERE id=$id")->fetch_assoc();
    $conn->query("UPDATE books SET quantity=quantity+1 WHERE id=".$borrow['book_id']);
    header("Location: ?action=mybooks");
}

// ---------- ADD SAMPLE BOOKS (FIRST RUN) ----------
$check = $conn->query("SELECT COUNT(*) as total FROM books")->fetch_assoc();
if ($check['total'] == 0) {
    $conn->query("INSERT INTO books(title,author,quantity) VALUES
        ('Database Systems','Navathe',5),
        ('PHP Programming','John Smith',3),
        ('Web Development','David Brown',4)");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Library System</title>
</head>
<body>

<?php if (!isset($_SESSION['user_id']) && $action != 'register') { ?>

<h2>Login</h2>
<form method="post" action="?action=login">
    <input type="text" name="username" placeholder="Username" required><br>
    <input type="password" name="password" placeholder="Password" required><br>
    <button name="login">Login</button>
</form>
<a href="?action=register">Register</a>

<?php } ?>

<?php if ($action == 'register') { ?>
<h2>Register</h2>
<form method="post">
    <input type="text" name="full_name" placeholder="Full Name" required><br>
    <input type="text" name="username" placeholder="Username" required><br>
    <input type="password" name="password" placeholder="Password" required><br>
    <button name="register">Register</button>
</form>
<a href="?action=login">Back to Login</a>
<?php } ?>

<?php if (isset($_SESSION['user_id']) && $action == 'home') { ?>
<h2>Welcome <?php echo $_SESSION['username']; ?></h2>
<a href="?action=logout">Logout</a>
<h3>Book List</h3>
<?php
$books = $conn->query("SELECT * FROM books");
while ($row = $books->fetch_assoc()) {
    echo $row['title']." - ".$row['author']." (".$row['quantity'].")";
    if ($row['quantity'] > 0)
        echo " <a href='?action=borrow&id=".$row['id']."'>Borrow</a>";
    echo "<br>";
}
?>
<br>
<a href="?action=mybooks">My Borrowed Books</a>
<?php } ?>

<?php if (isset($_SESSION['user_id']) && $action == 'mybooks') { ?>
<h2>My Borrowed Books</h2>
<a href="?action=home">Back</a><br><br>
<?php
$user_id = $_SESSION['user_id'];
$result = $conn->query("SELECT borrowings.*, books.title FROM borrowings
    JOIN books ON borrowings.book_id=books.id
    WHERE user_id=$user_id AND status='borrowed'");
while ($row = $result->fetch_assoc()) {
    echo $row['title']." - Borrowed: ".$row['borrow_date'];
    echo " <a href='?action=return&id=".$row['id']."'>Return</a><br>";
}
?>
<?php } ?>

</body>
</html>
