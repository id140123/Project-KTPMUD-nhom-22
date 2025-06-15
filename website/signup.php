<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

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

function showMessage($msg, $success = false) {
    echo "<div style='margin:40px auto;max-width:400px;padding:20px;border-radius:8px;background:" . ($success ? "#d4edda" : "#f8d7da") . ";color:" . ($success ? "#155724" : "#721c24") . ";font-size:18px;text-align:center;'>";
    echo $msg;
    echo "<br><a href='signup.php'>Quay lại đăng ký</a>";
    echo "</div>";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirmpassword = $_POST['confirmpassword'];
    $dateofbirth = $_POST['dateofbirth'];
    $role = intval($_POST['role']);

    // Kiểm tra dữ liệu
    if (!$username || !$email || !$password || !$confirmpassword || !$dateofbirth || ($role !== 0 && $role !== 1)) {
        showMessage("Vui lòng nhập đầy đủ thông tin!");
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        showMessage("Email không hợp lệ!");
        exit;
    }
    if ($password !== $confirmpassword) {
        showMessage("Mật khẩu xác nhận không khớp!");
        exit;
    }

    // Kiểm tra email đã tồn tại chưa (prepared statement)
    $stmt = $conn->prepare("SELECT 1 FROM User WHERE Email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        showMessage("Email đã được đăng ký!");
        $stmt->close();
        $conn->close();
        exit;
    }
    $stmt->close();

    // Sinh mã ID mới
    if ($role == 1) { // Employee
        $prefix = "EP2023";
        $sql_max = "SELECT Id FROM User WHERE Id LIKE '{$prefix}%'";
        $result = $conn->query($sql_max);
        $max_num = 0;
        while ($row = $result->fetch_assoc()) {
            $num = intval(substr($row['Id'], strlen($prefix)));
            if ($num > $max_num) $max_num = $num;
        }
        $new_num = str_pad($max_num + 1, 4, "0", STR_PAD_LEFT);
        $new_id = $prefix . $new_num;
    } else { // Manager
        $prefix = "MN2023";
        $sql_max = "SELECT Id FROM User WHERE Id LIKE '{$prefix}%'";
        $result = $conn->query($sql_max);
        $max_num = 9000;
        while ($row = $result->fetch_assoc()) {
            $num = intval(substr($row['Id'], strlen($prefix)));
            if ($num > $max_num) $max_num = $num;
        }
        $new_num = str_pad($max_num + 1, 4, "0", STR_PAD_LEFT);
        $new_id = $prefix . $new_num;
    }

    // Bắt đầu transaction
    $conn->begin_transaction();

    try {
        // Mã hóa mật khẩu
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Thêm vào bảng User (prepared statement)
        $stmt = $conn->prepare("INSERT INTO User (Id, Name, Email, Password, RoleId, DateOfBirth) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssis", $new_id, $username, $email, $password_hash, $role, $dateofbirth);
        if (!$stmt->execute()) {
            throw new Exception("Lỗi khi thêm User: " . $stmt->error);
        }
        $stmt->close();

        if ($role == 1) {
            // Thêm vào bảng Employee
            $stmt = $conn->prepare("INSERT INTO Employee (EmployeeId, Salary, Workdays) VALUES (?, 0, 0)");
            $stmt->bind_param("s", $new_id);
            if (!$stmt->execute()) {
                throw new Exception("Lỗi khi thêm Employee: " . $stmt->error);
            }
            $stmt->close();
        } else {
            // Thêm vào bảng Manager
            $stmt = $conn->prepare("INSERT INTO Manager (ManagerId) VALUES (?)");
            $stmt->bind_param("s", $new_id);
            if (!$stmt->execute()) {
                throw new Exception("Lỗi khi thêm Manager: " . $stmt->error);
            }
            $stmt->close();
        }

        $conn->commit();
        echo "<script>alert('Đăng ký thành công! Mã tài khoản: $new_id'); window.location='signin.php';</script>";
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        showMessage("Đăng ký thất bại: " . $e->getMessage());
    }

    $conn->close();
} 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sign Up</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background: #CCFFCC; display: flex; justify-content: center; align-items: center; }
        .container { margin-top: 10px; margin-bottom: 10px; display: flex; width: 500px; background: #ffde59; border-radius: 12px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1); overflow: hidden; }
        .formbg { width: 100%; background: #ffde59; margin-top: 0px; margin-bottom: 20px; margin-left: 50px; margin-right: 50px; }
        .logo img { width: 200px; height: auto; display: block; margin: 0 auto; }
        form { display: flex; flex-direction: column; }
        label { font-size: 14px; margin-bottom: 6px; color: #000000; }
        input { padding: 10px; margin-bottom: 15px; border: 1px solid #000000; border-radius: 5px; }
        button { margin-top: 10px; background: #7ed957; color: white; padding: 14px; border: none; border-radius: 5px; font-size: 14px; cursor: pointer; }
        select#role { width: 100%; padding: 10px; margin-bottom: 20px; border: 1px solid #000000; border-radius: 5px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="formbg">
            <div class="logo"> <img src="imagine/HiStaff.jpg" alt="ảnh logo" /></div>
            <form id="signupForm" method="post" action="signup.php">
                <label for="username">User name</label>
                <input type="text" id="username" name="username" placeholder="vuminhduc" required />

                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="vuminhduc@gmail.com" required />

                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="*********" required />

                <label for="confirmpassword"> Confirm password</label>
                <input type="password" id="confirmpassword" name="confirmpassword" placeholder="*********" required />

                <label for="dateofbirth">Date of birth</label>
                <input type="date" id="dateofbirth" name="dateofbirth" required />
        
                <label for="role">Role</label>
                <select id="role" name="role" required>
                    <option value="" disabled selected hidden>-- Select role --</option>
                    <option value="0">Manager</option>
                    <option value="1">Employee</option>
                </select>

                <button type="submit">Sign up</button>
            </form>
        </div>
    </div>
</body>
</html>