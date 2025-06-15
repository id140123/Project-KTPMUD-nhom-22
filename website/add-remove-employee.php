<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 0) { // 0 là manager
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

// Xử lý thêm nhân viên
$add_error = '';
$new_emp_id = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_employee'])) {
    $name = trim($_POST['add_username']);
    $email = trim($_POST['add_email']);
    $password = $_POST['add_password'];
    $dob = $_POST['add_dob'];
    $salary_input = $_POST['add_salary']; // Lấy giá trị thô từ input

    // --- Bắt đầu xác thực đầu vào phía Server ---

    // 1. Kiểm tra các trường bắt buộc không rỗng và định dạng cơ bản
    if (empty($name)) {
        $add_error = "Tên người dùng không được để trống.";
    } elseif (empty($email)) {
        $add_error = "Email không được để trống.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) { // Kiểm tra định dạng email ngay sau khi biết nó không rỗng
        $add_error = "Địa chỉ email không hợp lệ.";
    } elseif (empty($password)) {
        $add_error = "Mật khẩu không được để trống.";
    } elseif (empty($dob)) {
        $add_error = "Ngày sinh không được để trống.";
    } elseif ($dob > date('Y-m-d')) { // Kiểm tra ngày sinh không phải là ngày trong tương lai
        $add_error = "Ngày sinh không thể là ngày trong tương lai.";
    } elseif (!isset($salary_input) || $salary_input === '') { // Kiểm tra lương có được nhập và không rỗng
        $add_error = "Lương không được để trống.";
    } elseif (!is_numeric($salary_input) || floatval($salary_input) <= 0) { // Kiểm tra lương là số và dương
        $add_error = "Lương phải là một số dương hợp lệ.";
    }

    // Chỉ tiếp tục nếu không có lỗi xác thực nào từ các bước trên
    if (empty($add_error)) {
        $salary = floatval($salary_input); // Chuyển đổi sang float sau khi đã xác thực

        // Kiểm tra email đã tồn tại chưa (chỉ khi dữ liệu đã hợp lệ về mặt định dạng)
        $stmt = $conn->prepare("SELECT Id FROM User WHERE Email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $add_error = "Email đã tồn tại trong hệ thống!";
        } else {
            // Logic thêm nhân viên vào DB
            // Tạo ID mới cho employee
            $result = $conn->query("SELECT Id FROM User WHERE Id LIKE 'EP%' ORDER BY Id DESC LIMIT 1");
            if ($row = $result->fetch_assoc()) {
                $lastId = intval(substr($row['Id'], 2));
                $newId = 'EP' . str_pad($lastId + 1, 8, '0', STR_PAD_LEFT);
            } else {
                $newId = 'EP20230001'; // ID khởi tạo nếu chưa có nhân viên nào
            }
            $conn->begin_transaction();
            try {
                // Thêm vào bảng User
                $role = 1; // 1 là employee
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO User (Id, Name, Email, Password, DateOfBirth, RoleID) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssi", $newId, $name, $email, $hashed, $dob, $role);
                $stmt->execute();

                // Thêm vào bảng Employee
                $stmt2 = $conn->prepare("INSERT INTO Employee (EmployeeId, Salary, Workdays) VALUES (?, ?, 0)");
                $stmt2->bind_param("sd", $newId, $salary);
                $stmt2->execute();

                $conn->commit();
                $new_emp_id = $newId;
            } catch (Exception $e) {
                $conn->rollback();
                $add_error = "Có lỗi xảy ra khi thêm nhân viên! Vui lòng thử lại."; // Thông báo lỗi thân thiện hơn
                error_log("Lỗi thêm nhân viên: " . $e->getMessage()); // Ghi log chi tiết để debug
            }
        }
    }
}

// Xử lý xóa nhân viên (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_employee'])) {
    $empId = $_POST['emp_id'];

    // Basic validation for empId (should not be empty or too short/long)
    if (empty($empId) || !is_string($empId) || strlen($empId) > 10) { // Example length check for 'EP20230001'
        echo "fail";
        exit;
    }

    $conn->begin_transaction();
    try {
        // Xóa các thông tin liên quan dùng prepared statement
        // Đảm bảo thứ tự xóa đúng để tránh lỗi khóa ngoại
        $stmt1 = $conn->prepare("DELETE FROM employeewelfares WHERE EmployeeId = ?");
        $stmt1->bind_param("s", $empId);
        $stmt1->execute();

        $stmt2 = $conn->prepare("DELETE FROM employeeviolations WHERE EmployeeId = ?");
        $stmt2->bind_param("s", $empId);
        $stmt2->execute();

        // Nếu có bảng liên quan khác như LeaveRequest, Attendance, etc., cần xóa ở đây
        // Ví dụ: DELETE FROM LeaveRequests WHERE EmployeeId = ?;
        // Ví dụ: DELETE FROM AttendanceRecords WHERE EmployeeId = ?;

        $stmt3 = $conn->prepare("DELETE FROM Employee WHERE EmployeeId = ?");
        $stmt3->bind_param("s", $empId);
        $stmt3->execute();

        $stmt4 = $conn->prepare("DELETE FROM User WHERE Id = ?");
        $stmt4->bind_param("s", $empId);
        $stmt4->execute();

        $conn->commit();
        echo "success";
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Lỗi xóa nhân viên: " . $e->getMessage());
        echo "fail";
    }
    exit;
}

// Lấy danh sách nhân viên hiện có
$employees = [];
$sql = "SELECT u.Id, u.Name, u.Email FROM User u WHERE u.RoleId = 1";
$res = $conn->query($sql);
while ($row = $res->fetch_assoc()) {
    $employees[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Add/Remove Employee</title>
  <style>
    body { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; background: #ffffff; }
    .container { display: flex; }
    .left { width: 200px; background: #ffde59; padding: 0 35px 20px 35px; height: 100vh;}
    .logo img { width: 100%; display: flex; align-items: center; justify-content: center; }
    .left ul { list-style: none; padding: 0; }
    .left ul li { font-size: 14px; padding: 10px; cursor: pointer; }
    .right { flex: 1; padding: 0px 40px 10px 40px; }
    h2 { text-align: center; margin-top: 30px; }
    table { width: 100%; border-collapse: collapse; margin-top: 30px; }
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
    .btn-add, .btn-remove { background: none; border: none; cursor: pointer; padding: 0; }
    .btn-add img, .btn-remove img { width: 25px; }
    /* Đã bỏ .error-msg và .success-msg vì sẽ dùng alert() */
    /* .error-msg { color: red; text-align: center; margin-bottom: 10px;} */
    /* .success-msg { color: green; text-align: center; margin-bottom: 10px;} */
    /* Ẩn mũi tên lên/xuống cho input type="number" */
    input[type="number"]::-webkit-inner-spin-button, input[type="number"]::-webkit-outer-spin-button {
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
      <div class="logo"><img src="imagine/HiStaff.jpg" alt="logo" /></div>
      <ul>
        <li><a href="manager.php" style="color:inherit;text-decoration:none;">Home</a></li>
        <li><a href="employee-list.php" style="color:inherit;text-decoration:none;">Employee List</a></li>
        <li style="font-weight:bold;"><a href="add-remove-employee.php" style="color:inherit;text-decoration:none;">Add/Remove Employee</a></li>
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
      <div class="right-top">
        <h2 style="text-align:center;">Add Employee</h2>
        <form id="add-employee-form" method="post" autocomplete="off">
          <input type="hidden" name="add_employee" value="1">
          <table>
            <thead>
              <tr>
                <th>User Name</th>
                <th>Email</th>
                <th>Password</th>
                <th>Date of Birth</th>
                <th>Salary</th>
                <th>Add</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><input type="text" name="add_username" value="<?= htmlspecialchars($_POST['add_username'] ?? '') ?>" required style="width: 100%;"></td>
                <td><input type="email" name="add_email" value="<?= htmlspecialchars($_POST['add_email'] ?? '') ?>" required style="width: 100%;"></td>
                <td><input type="password" name="add_password" required style="width: 100%;"></td>
                <td><input type="date" name="add_dob" value="<?= htmlspecialchars($_POST['add_dob'] ?? '') ?>" required style="width: 100%;"></td>
                <td><input type="number" step="any" name="add_salary" value="<?= htmlspecialchars($_POST['add_salary'] ?? '') ?>" required style="width: 100%;"></td>
                <td>
                  <button type="submit" class="btn-add">
                    <img src="imagine/addlogo.png" alt="Thêm" />
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </form>
      </div>
      <div class="right-bottom">
        <h2 style="text-align:center;">Remove Employee</h2>
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>User Name</th>
              <th>Email</th>
              <th>Remove</th>
            </tr>
          </thead>
          <tbody id="employee-list">
            <?php foreach ($employees as $emp): ?>
              <tr id="row-<?= htmlspecialchars($emp['Id']) ?>">
                <td><?= htmlspecialchars($emp['Id']) ?></td>
                <td><?= htmlspecialchars($emp['Name']) ?></td>
                <td><?= htmlspecialchars($emp['Email']) ?></td>
                <td>
                  <button class="btn-remove" onclick="removeEmployee('<?= htmlspecialchars($emp['Id']) ?>', '<?= htmlspecialchars($emp['Name']) ?>')">
                    <img src="imagine/removelogo.png" alt="Xoá" />
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <script>
    function removeEmployee(empId, empName) {
      if (confirm('Bạn có chắc muốn xoá nhân viên với id: "' + empId + '" và toàn bộ dữ liệu liên quan không?')) {
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "add-remove-employee.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function() {
          if (xhr.readyState === 4 && xhr.status === 200) {
            if (xhr.responseText.trim() === "success") {
              var row = document.getElementById('row-' + empId);
              if (row) row.remove();
              alert("Xoá nhân viên thành công!");
            } else {
              alert("Có lỗi xảy ra khi xoá!");
            }
          }
        };
        xhr.send("remove_employee=1&emp_id=" + encodeURIComponent(empId));
      }
    }
  </script>
  <?php if ($new_emp_id): ?>
  <script>
      window.onload = function() {
          alert("Đăng ký nhân viên thành công!\nEmployee ID: <?= $new_emp_id ?>");
          // Sau khi hiển thị alert, chuyển hướng để xóa POST data và tải lại danh sách
          window.location.href = "add-remove-employee.php";
      };
  </script>
  <?php endif; ?>
  <?php if ($add_error && empty($new_emp_id)): // Chỉ hiển thị lỗi nếu không có ID nhân viên mới (tức là không phải lỗi đã được xử lý bằng chuyển hướng) ?>
  <script>
      document.addEventListener('DOMContentLoaded', function() {
          alert("<?= htmlspecialchars($add_error) ?>"); // Hiển thị lỗi qua alert
      });
  </script>
  <?php endif; ?>
</body>
</html>