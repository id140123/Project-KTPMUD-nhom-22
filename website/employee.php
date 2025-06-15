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
    error_log("Kết nối thất bại: " . $conn->connect_error);
    echo "<div style='color:red;text-align:center;margin-top:40px;'>Hệ thống đang bảo trì, vui lòng quay lại sau!</div>";
    exit;
}

$user_id = $_SESSION['user_id'];

// Lấy thông tin user & employee
$sql = "SELECT u.Id, u.Name, u.Email, u.DateOfBirth, e.Salary, e.Workdays
        FROM User u
        LEFT JOIN Employee e ON u.Id = e.EmployeeId
        WHERE u.Id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    session_unset();
    session_destroy();
    header("Location: signin.php");
    exit;
}

// Lấy danh sách phúc lợi (sắp xếp theo số trong WelfareID)
$welfare_list = [];
$total_bonus = 0.0;
$sqlWelfare = "SELECT w.WelfareID, w.WelfareName, w.Bonus
    FROM employeewelfares ew
    JOIN welfare w ON ew.WelfareID = w.WelfareID
    WHERE ew.EmployeeId = ?
    ORDER BY CAST(SUBSTRING(w.WelfareID, 3) AS UNSIGNED) ASC";
$stmtWelfare = $conn->prepare($sqlWelfare);
$stmtWelfare->bind_param("s", $user_id);
$stmtWelfare->execute();
$resWelfare = $stmtWelfare->get_result();
while ($row = $resWelfare->fetch_assoc()) {
    $welfare_list[] = htmlspecialchars($row['WelfareName']);
    $total_bonus += floatval($row['Bonus']);
}
$stmtWelfare->close();
$welfares_count = count($welfare_list);

// Lấy danh sách vi phạm (sắp xếp theo số trong ViolationID)
$violation_list = [];
$total_fine = 0.0;
$sqlViolation = "SELECT v.ViolationID, v.ViolationName, v.Fine
    FROM employeeviolations ev
    JOIN violation v ON ev.ViolationID = v.ViolationID
    WHERE ev.EmployeeId = ?
    ORDER BY CAST(SUBSTRING(v.ViolationID, 3) AS UNSIGNED) ASC";
$stmtViolation = $conn->prepare($sqlViolation);
$stmtViolation->bind_param("s", $user_id);
$stmtViolation->execute();
$resViolation = $stmtViolation->get_result();
while ($row = $resViolation->fetch_assoc()) {
    $violation_list[] = htmlspecialchars($row['ViolationName']);
    $total_fine += floatval($row['Fine']);
}
$stmtViolation->close();
$violations_count = count($violation_list);

// Tính lại salary (float)
$salary = floatval($user['Salary'] ?? 0) + $total_bonus - $total_fine;
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Employee</title>
  <style>
    body { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; background: #ffffff; }
    .container { display: flex; }
    .left { width: 200px; background: #ffde59; padding: 0 35px 20px 35px; }
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
      <div class="logo"><img src="imagine/HiStaff.jpg" alt="logo"></div>
      <ul>
        <li style="font-weight:bold;"><a href="employee.php" style="color:inherit;text-decoration:none;">Home</a></li>
        <li><a href="update-information.php" style="color:inherit;text-decoration:none;">Update Information</a></li>
        <li>Attendance</li>
        <li>Leave Request</li>
        <li>Settings</li>
        <li><a href="logout.php" style="color:inherit;text-decoration:none;">Log Out</a></li>
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
          <h2><?= htmlspecialchars($user['Name']) ?></h2>
          <p>ID: <?= htmlspecialchars($user['Id']) ?></p>
        </div>
      </div>
      <div class="rightbottom">
        <h2>Information</h2>
        <div class="rightbottom-info">
          <div class="label">User Name:</div><div><?= htmlspecialchars($user['Name']) ?></div>
          <div class="label">Email:</div><div><?= htmlspecialchars($user['Email']) ?></div>
          <div class="label">Date of birth:</div><div><?= htmlspecialchars($user['DateOfBirth']) ?></div>
          <div class="label">Role:</div><div>Employee</div>
          <div class="label">Salary:</div><div><?= number_format($salary, 2) ?> triệu</div>
          <div class="label">Welfares:</div><div><?= $welfares_count ?></div>
          <div class="label">Welfare List:</div>
          <div>
            <?= $welfares_count > 0 ? implode("<br>", $welfare_list) : "Không có phúc lợi nào" ?>
          </div>
          <div class="label">Violations:</div><div><?= $violations_count ?></div>
          <div class="label">Violation List:</div>
          <div>
            <?= $violations_count > 0 ? implode("<br>", $violation_list) : "Không có lỗi nào" ?>
          </div>
          <div class="label">Workdays:</div><div><?= $user['Workdays'] ?? 0 ?></div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>