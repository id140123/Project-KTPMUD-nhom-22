<?php
session_start();

// Thiết lập báo lỗi để dễ dàng gỡ lỗi trong quá trình phát triển
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Kiểm tra session và vai trò (role 0 cho Manager)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 0) {
    header("Location: signin.php"); // Chuyển hướng về trang đăng nhập nếu chưa đăng nhập hoặc không phải Manager
    exit;
}

// Thông tin kết nối cơ sở dữ liệu
$servername = "localhost";
$username = "root";
$password = "minhduc2245";
$dbname = "website";

// Tạo kết nối
$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8"); // Đặt charset để hỗ trợ tiếng Việt

// Kiểm tra lỗi kết nối
if ($conn->connect_error) {
    error_log("Kết nối thất bại: " . $conn->connect_error); // Ghi lỗi vào log server
    echo "<div style='color:red;text-align:center;margin-top:40px;'>Hệ thống đang bảo trì, vui lòng quay lại sau!</div>";
    exit;
}

// Lấy thông tin user_id từ session
$user_id = $_SESSION['user_id'];

// --- BẮT ĐẦU PHẦN ĐỒNG BỘ HÓA SESSION VỚI DATABASE ---
// Truy vấn database để lấy thông tin người dùng mới nhất
$stmt = $conn->prepare("SELECT Name, Email, DateOfBirth FROM User WHERE Id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$db_user_info = $result->fetch_assoc();
$stmt->close();

// Kiểm tra xem có tìm thấy thông tin người dùng trong DB không
if ($db_user_info) {
    // Cập nhật session với thông tin mới nhất từ DB
    $_SESSION['name'] = $db_user_info['Name'];
    $_SESSION['email'] = $db_user_info['Email'];
    $_SESSION['dob'] = $db_user_info['DateOfBirth'];
    // Nếu có thêm trường nào khác trong session cần cập nhật, hãy thêm vào đây
} else {
    // Nếu không tìm thấy người dùng trong DB (có thể đã bị xóa hoặc lỗi),
    // hủy bỏ session và chuyển hướng về trang đăng nhập
    session_unset();
    session_destroy();
    header("Location: signin.php");
    exit;
}
// --- KẾT THÚC PHẦN ĐỒNG BỘ HÓA SESSION VỚI DATABASE ---

// Đóng kết nối cơ sở dữ liệu sau khi hoàn tất các thao tác DB
$conn->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Manager</title>
  <style>
    body { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; background: #ffffff; }
    .container { display: flex; }
    .left { width: 200px; background: #ffde59; height:100vh;padding: 0 35px 20px 35px; }
    .logo img { width: 100%; display: flex; align-items: center; justify-content: center; }
    .left ul { list-style: none; padding: 0; }
    .left ul li { font-size: 14px; padding: 10px; cursor: pointer; }
    .right { background: white; flex-grow: 1; padding: 20px 80px 20px 80px; }
    .righttop { display: flex; align-items: center; gap: 50px; }
    .righttop img { width: 150px; height: 150px; border-radius: 50%; }
    .rightbottom { padding: 25px; }
    .rightbottom h2 { font-size: 25px; color: black; text-align: center; }
    .rightbottom-info { display: grid; grid-template-columns: 200px 1fr; gap: 10px 0px; align-items: center; max-width: 400px; margin: 0 auto; }
    .rightbottom-info .label { font-weight: bold; text-align: left; }
  </style>
</head>
<body>
  <div class="container">
    <div class="left">
      <div class="logo"> <img src="imagine/HiStaff.jpg" alt="ảnh logo" /></div>
      <ul>
        <li style="font-weight:bold;"><a href="manager.php" style="color:inherit;text-decoration:none;">Home</li>
        <li><a href="employee-list.php" style="color:inherit;text-decoration:none;">Employee List</a></li>
        <li><a href="add-remove-employee.php" style="color:inherit;text-decoration:none;">Add/Remove Employee</a></li>
        <li>Update Salary/Benefits</li>
        <li>Update Violation</li>
        <li>Update Working Day</li>
        <li>Evaluate Performance</li>
        <li>Approve Request</li>
        <li>Change Information</li>
        <li>Settings</li>
        <li><a href="logout.php" style="color:inherit;text-decoration:none;">Log Out</a></li>
      </ul>
    </div>
    <div class="right">
      <div class="righttop">
        <div style="text-align: center;">
          <img src="imagine/avatar.jpg" alt="avatar" />
          <br />
          <button style="margin-top: 6px; border-radius: 5px; border-width: 1px; cursor: pointer;">Change Avatar</button>
        </div>
        <div>
          <h2><?php echo htmlspecialchars($_SESSION['name']); ?></h2>
          <p>ID: <span><?php echo htmlspecialchars($_SESSION['user_id']); ?></span></p>
        </div>
      </div>
      <div class="rightbottom">
        <h2>Information</h2>
        <div class="rightbottom-info">
          <div class="label">User Name:</div>
          <div><?php echo htmlspecialchars($_SESSION['name']); ?></div>
          <div class="label">Email:</div>
          <div><?php echo htmlspecialchars($_SESSION['email']); ?></div>
          <div class="label">Date of birth:</div>
          <div><?php echo htmlspecialchars($_SESSION['dob']); ?></div>
          <div class="label">Role:</div>
          <div>Manager</div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>