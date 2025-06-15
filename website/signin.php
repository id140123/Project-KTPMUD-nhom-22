<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "minhduc2245";
$dbname = "website";

$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8");

if ($conn->connect_error) {
    error_log("Kết nối thất bại: " . $conn->connect_error);
    echo "<div style='color:red;text-align:center;margin-top:40px;'>Hệ thống đang bảo trì, vui lòng quay lại sau!</div>";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Kiểm tra dữ liệu đầu vào
    if (empty($email) || empty($password)) {
        echo "<script>alert('Vui lòng nhập đầy đủ email và mật khẩu!'); window.location='signin.php';</script>";
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<script>alert('Email không hợp lệ!'); window.location='signin.php';</script>";
        exit;
    }

    // Lấy user từ DB
    $stmt = $conn->prepare("SELECT Id, Name, Email, Password, RoleId, DateOfBirth FROM User WHERE Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows == 1) {
        $stmt->bind_result($id, $name, $email_db, $hashed_password, $role, $dob);
        $stmt->fetch();
        if (password_verify($password, $hashed_password)) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $id;
            $_SESSION['name'] = $name;
            $_SESSION['email'] = $email_db;
            $_SESSION['role'] = $role;
            $_SESSION['dob'] = $dob;
            if ($role == 0) {
                header("Location: manager.php");
            } else {
                header("Location: employee.php");
            }
            exit;
        } else {
            echo "<script>alert('Email hoặc mật khẩu không đúng!'); window.location='signin.php';</script>";
        }
    } else {
        echo "<script>alert('Email hoặc mật khẩu không đúng!'); window.location='signin.php';</script>";
    }
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sign In</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
    body { background: #CCFFCC; display: flex; justify-content: center; align-items: center; height: 100vh; }
    .container { margin-top: 10px; margin-bottom: 10px; display: flex; width: 800px; background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); overflow: hidden; }
    .left { width: 50%; background: #FFFFFF; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; }
    .left img { width: 100%; max-width: 400px; margin-bottom: 10px; }
    .left h2 { font-size: 30px; margin-bottom: 10px; color: #7ed957; }
    .left p { font-size: 14px; color: #000000; }
    .right { width: 50%; background: #ffde59; padding: 0px 50px 20px 50px; }
    .logo img { width: 100%; display: flex; align-items: center; justify-content: center; }
    form { display: flex; flex-direction: column; }
    label { font-size: 14px; margin-bottom: 6px; color: #000000; }
    input { padding: 10px; margin-bottom: 20px; border: 1px solid #000000; border-radius: 5px; }
    .forgot { text-align: right; font-size: 14px; margin-top: -14px; margin-bottom: 20px; }
    .forgot a { color: #000000; text-decoration: none; }
    button { background: #7ed957; color: white; padding: 14px; border: none; border-radius: 5px; font-size: 14px; cursor: pointer; }
    .divider { text-align: center; margin: 12px 0; color: #000000; }
    .google-btn { background: white; color: #000000; border: 1px solid #000000; padding: 14px; display: flex; align-items: center; justify-content: center; border-radius: 5px; cursor: pointer; margin-bottom: 15px; font-size: 14px; }
    .google-btn img { width: 20px; margin-right: 8px; }
    .signup { text-align: center; font-size: 14px; }
    .signup a { color: #7ed957; text-decoration: none; font-weight: 600; }
  </style>
</head>
<body>
  <div class="container">
    <div class="left">
      <img src="imagine/bglogin.jpeg" alt="ảnh login" />
      <h2>HiStaff</h2>
      <p>Biến công việc trở nên hiệu quả, linh hoạt</p>
    </div>
    <div class="right">
      <div class="logo"> <img src="imagine/HiStaff.jpg" alt="ảnh logo" /></div>
      <form method="post" action="signin.php">
        <label for="email">Email</label>
        <input type="text" id="email" name="email" placeholder="vuminhduc@gmail.com" />
        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="*********" />
        <div class="forgot"><a href="#">Forgot password?</a></div>
        <button type="submit">Sign in</button>
      </form>
      <div class="divider">or</div>
      <div class="google-btn">
        <img src="imagine/logogoogle.webp" alt="Google logo" />
        Sign in with Google
      </div>
      <div class="signup">Are you new? <a href="signup.php">Create an Account</a></div>
    </div>
  </div>
</body>
</html>