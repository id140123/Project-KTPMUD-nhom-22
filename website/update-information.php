<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) {
    header("Location: signin.php");
    exit;
}

$servername = "localhost";
$username = "root";
$password = "minhduc2245";
$dbname = "website";

$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8");
if ($conn->connect_error) {
    error_log("DB connection failed: " . $conn->connect_error);
    echo "<h2 style='color:red;text-align:center;'>Hệ thống đang bảo trì. Vui lòng quay lại sau!</h2>";
    exit;
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';
$user = []; // Khởi tạo biến $user là một mảng rỗng

// Lấy thông tin hiện tại của người dùng
$stmt = $conn->prepare("SELECT Id, Name, Email, DateOfBirth FROM User WHERE Id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result(); // Lấy kết quả dưới dạng result set
$user = $result->fetch_assoc(); // Lấy tất cả thông tin vào biến $user dưới dạng mảng kết hợp
$stmt->close();

// Kiểm tra nếu không tìm thấy người dùng (session_id không hợp lệ)
if (!$user) {
    // Nếu không tìm thấy người dùng với ID trong session, đăng xuất
    header("Location: logout.php");
    exit;
}

// Gán các biến để sử dụng trong form, nếu $user có dữ liệu
$current_name = $user['Name'] ?? ''; // Sử dụng null coalescing operator để tránh cảnh báo nếu trường rỗng
$current_dob = $user['DateOfBirth'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_info'])) {
    $new_name = trim($_POST['username']);
    $new_password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $new_dob = $_POST['dob'];

    // Kiểm tra password và confirm password
    if ($new_password !== "" || $confirm_password !== "") {
        if ($new_password !== $confirm_password) {
            $error = "Mật khẩu mới và xác nhận mật khẩu không khớp!";
        }
    }

    if (!$error) {
        $fields = [];
        $params = [];
        $types = "";

        // Thêm trường Name vào update nếu có thay đổi và không rỗng
        if ($new_name !== "" && $new_name !== $user['Name']) { // Chỉ cập nhật nếu có thay đổi
            $fields[] = "Name = ?";
            $params[] = $new_name;
            $types .= "s";
        }
        // Thêm trường DateOfBirth vào update nếu có thay đổi và không rỗng
        if ($new_dob !== "" && $new_dob !== $user['DateOfBirth']) { // Chỉ cập nhật nếu có thay đổi
            $fields[] = "DateOfBirth = ?";
            $params[] = $new_dob;
            $types .= "s";
        }
        // Thêm trường Password vào update nếu có mật khẩu mới và khớp
        if ($new_password !== "" && $new_password === $confirm_password) {
            $fields[] = "Password = ?";
            $params[] = password_hash($new_password, PASSWORD_DEFAULT);
            $types .= "s";
        }

        if (count($fields) > 0) {
            $sql = "UPDATE User SET " . implode(", ", $fields) . " WHERE Id = ?";
            $params[] = $user_id;
            $types .= "s";
            $stmt = $conn->prepare($sql);

            // Tạo mảng tham chiếu
            $bind_names[] = $types;
            for ($i=0; $i<count($params);$i++) {
                $bind_names[] = &$params[$i];
            }
            call_user_func_array([$stmt, 'bind_param'], $bind_names);

            if ($stmt->execute()) {
                $success = "Cập nhật thông tin thành công!";
                if ($new_name !== "" && $new_name !== $user['Name']) $user['Name'] = $new_name;
                if ($new_dob !== "" && $new_dob !== $user['DateOfBirth']) $user['DateOfBirth'] = $new_dob;
            } else {
                $error = "Có lỗi xảy ra khi cập nhật!";
            }
            $stmt->close();
        } else {
            $error = "Bạn chưa nhập thông tin nào để cập nhật!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Update Information</title>
  <style>
    body { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; background: #ffffff; }
    .container { display: flex; }
    .left { width: 232px; background: #ffde59; padding: 0 35px 20px 35px; height: 100vh; }
    .logo img { width: 100%; display: flex; align-items: center; justify-content: center; }
    .left ul { list-style: none; padding: 0; }
    .left ul li { font-size: 14px; padding: 10px; cursor: pointer; }
    .left ul li a { color: inherit; text-decoration: none; } /* Đảm bảo link kế thừa màu và không có gạch chân */
    .left ul li.active { font-weight: bold; } /* Thêm class active cho mục đang chọn */
    .right { background: white; flex-grow: 1; padding: 20px 80px 20px 80px; }
    .righttop { display: flex; align-items: center; gap: 50px; }
    .righttop img { width: 150px; height: 150px; border-radius: 50%; }
    .rightbottom { padding: 25px; }
    .rightbottom h2 { font-size: 25px; color: black; text-align: center; margin-top: 0px; }
    .rightbottom-info { display: grid; grid-template-columns: 200px 1fr; gap: 10px 0px; align-items: center; max-width: 400px; margin: 0 auto; }
    .rightbottom-info .label { font-weight: bold; text-align: left; }
    table { width: 100%; border-collapse: collapse; margin-top: 25px; }
    th, td { border: 1px solid #e2c400; padding: 12px; text-align: center; }
    th { background: #ffde59; }
    tr:last-child { border-bottom: none;}
    input[type="text"], input[type="email"], input[type="password"], input[type="date"], input[type="number"] {
      width: 100%;
      padding: 8px 8px;
      border: none;
      outline: none;
      background: #f8f8f8;
      border-radius: 5px;
      font-size: 14px;
      box-sizing: border-box;
    }
    
    .btn-update {
      background: none;
      border: none;
      cursor: pointer;
      padding: 0;
    }
    .btn-update img { width: 25px; }
    /* Ẩn mũi tên lên/xuống cho input type="number" */
    input[type="number"]::-webkit-inner-spin-button,
    input[type="number"]::-webkit-outer-spin-button {
      -webkit-appearance: none; /* Dành cho Chrome, Safari, Opera */
      margin: 0;
    }
    input[type="number"] {
        -moz-appearance: textfield; /* Dành cho Firefox */
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="left">
      <div class="logo"><img src="imagine/HiStaff.jpg" alt="logo"></div>
      <ul>
        <li><a href="employee.php">Home</a></li>
        <li class="active"><a href="update-information.php">Update Information</a></li>
        <li><a href="attendance.php">Attendance</a></li>
        <li><a href="leave-request.php">Leave Request</a></li>
        <li><a href="settings.php">Settings</a></li>
        <li><a href="logout.php">Log Out</a></li>
      </ul>
    </div>
    <div class="right">
      <div class="righttop">
        <div style="text-align:center">
          <img src="imagine/avatar.jpg" alt="avatar">
          <br>
          <button style="margin-top:6px; cursor:pointer;">Change Avatar</button>
        </div>
        <div>
          <h2><?= htmlspecialchars($user['Name'] ?? 'Tên người dùng') ?></h2>
          <p>ID: <?= htmlspecialchars($user['Id'] ?? 'ID không xác định') ?></p>
        </div>
      </div>
      <div class="rightbottom">
        <h2>Update Information</h2>
        <form id="update-form" method="post" autocomplete="off">
            <input type="hidden" name="update_info" value="1">
            <table>
                <thead>
                    <tr>
                        <th>User Name</th>
                        <th>New Password</th>
                        <th>Confirm New Password</th> 
                        <th>Date Of Birth</th>
                        <th>Add</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><input type="text" name="username" value="<?= htmlspecialchars($current_name) ?>"></td>
                        <td><input type="password" name="password"></td>
                        <td><input type="password" name="confirm_password"></td>
                        <td><input type="date" name="dob" value="<?= htmlspecialchars($current_dob) ?>"></td>
                        <td>
                            <button type="button" onclick="confirmUpdate()" class="btn-update">
                                <img src="imagine/updatelogo.png" alt="Cập nhật" />
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </form>
      </div>
    </div>
    </div>
    <?php if ($error): ?>
        <script>alert("<?= htmlspecialchars($error) ?>");</script>
    <?php elseif ($success): ?>
        <script>alert("<?= htmlspecialchars($success) ?>");</script>
    <?php endif; ?>
    <script>
    function confirmUpdate() {
        if (confirm("Bạn có chắc chắn muốn cập nhật thông tin này không?")) {
            document.getElementById('update-form').submit();
        }
    }
    </script>
</body>
</html>