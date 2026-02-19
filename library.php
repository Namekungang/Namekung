$host = "localhost";
$user = "s673190120";
$pass = "s673190120";
$db   = "s673190120"; // ต้องสร้าง DB นี้ไว้ก่อน

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

session_start();

// ---------- CREATE TABLES IF NOT EXISTS ----------
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

// ---------- INSERT SAMPLE BOOKS IF EMPTY ----------
$check = $conn->query("SELECT COUNT(*) as total FROM books")->fetch_assoc();
if ($check['total'] == 0) {
    $conn->query("INSERT INTO books(title,author,quantity) VALUES
        ('Database Systems','Navathe',5),
        ('PHP Programming','John Smith',3),
        ('Web Development','David Brown',4)");
}

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
    $conn->query("UPDATE books SET quantity=quantity-1 WHERE id=$book_id AND quantity>0");
    header("Location: ?action=mybooks");
}

// ---------- RETURN ----------
</html>