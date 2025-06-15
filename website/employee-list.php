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
    error_log("Kết nối thất bại: " . $conn->connect_error);
    echo "<div style='color:red;text-align:center;margin-top:40px;'>Hệ thống đang bảo trì, vui lòng quay lại sau!</div>";
    exit;
}

// 1. Lấy danh sách employee
$sql = "SELECT u.Id, u.Name, u.Email, u.DateOfBirth, e.Salary, e.Workdays
        FROM User u
        JOIN Employee e ON u.Id = e.EmployeeId";
$result = $conn->query($sql);

$employees = [];
$employeeIds = [];
while ($row = $result->fetch_assoc()) {
    $employees[$row['Id']] = $row;
    $employeeIds[] = $row['Id'];
}

// Nếu không có nhân viên thì không cần truy vấn tiếp
if (empty($employeeIds)) {
    $welfaresByEmp = [];
    $violationsByEmp = [];
    $bonusByEmp = [];
    $fineByEmp = [];
} else {
    // 2. Lấy toàn bộ phúc lợi cho tất cả nhân viên
    $in = implode(',', array_fill(0, count($employeeIds), '?'));
    $types = str_repeat('s', count($employeeIds));
    $sqlWelfare = "SELECT ew.EmployeeId, w.WelfareName, w.Bonus
                   FROM employeewelfares ew
                   JOIN welfare w ON ew.WelfareID = w.WelfareID
                   WHERE ew.EmployeeId IN ($in)
                   ORDER BY ew.EmployeeId, CAST(SUBSTRING(w.WelfareID, 3) AS UNSIGNED)";
    $stmtW = $conn->prepare($sqlWelfare);
    $stmtW->bind_param($types, ...$employeeIds);
    $stmtW->execute();
    $resW = $stmtW->get_result();
    $welfaresByEmp = [];
    $bonusByEmp = [];
    while ($w = $resW->fetch_assoc()) {
        $welfaresByEmp[$w['EmployeeId']][] = $w['WelfareName'];
        $bonusByEmp[$w['EmployeeId']][] = floatval($w['Bonus']);
    }
    $stmtW->close();

    // 3. Lấy toàn bộ vi phạm cho tất cả nhân viên
    $sqlViolation = "SELECT ev.EmployeeId, v.ViolationName, v.Fine
                     FROM employeeviolations ev
                     JOIN violation v ON ev.ViolationID = v.ViolationID
                     WHERE ev.EmployeeId IN ($in)
                     ORDER BY ev.EmployeeId, CAST(SUBSTRING(v.ViolationID, 3) AS UNSIGNED)";
    $stmtV = $conn->prepare($sqlViolation);
    $stmtV->bind_param($types, ...$employeeIds);
    $stmtV->execute();
    $resV = $stmtV->get_result();
    $violationsByEmp = [];
    $fineByEmp = [];
    while ($v = $resV->fetch_assoc()) {
        $violationsByEmp[$v['EmployeeId']][] = $v['ViolationName'];
        $fineByEmp[$v['EmployeeId']][] = floatval($v['Fine']);
    }
    $stmtV->close();
}
$conn->close();

// 4. Tổng hợp dữ liệu cho từng nhân viên
foreach ($employees as &$emp) {
    $emp['Welfares'] = $welfaresByEmp[$emp['Id']] ?? [];
    $emp['Violations'] = $violationsByEmp[$emp['Id']] ?? [];
    $emp['welfares_count'] = count($emp['Welfares']);
    $emp['violations_count'] = count($emp['Violations']);
    $total_bonus = isset($bonusByEmp[$emp['Id']]) ? array_sum($bonusByEmp[$emp['Id']]) : 0;
    $total_fine = isset($fineByEmp[$emp['Id']]) ? array_sum($fineByEmp[$emp['Id']]) : 0;
    $emp['Salary'] = floatval($emp['Salary']) + $total_bonus - $total_fine;
}
unset($emp);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Employee List</title>
    <style>
        body { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', sans-serif; background: #ffffff; }
        .container { display: flex; }
        .left { width: 200px; background: #ffde59; padding: 0 35px 20px 35px; height: 100vh;}
        .logo img { width: 100%; display: flex; align-items: center; justify-content: center; }
        .left ul { list-style: none; padding: 0; }
        .left ul li { font-size: 14px; padding: 10px; cursor: pointer; }
        .right { flex: 1; padding: 10px 40px; }
        h2 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 30px; }
        th, td { border: 1px solid #e2c400; padding: 12px; text-align: center; }
        th { background: #ffde59; }
        tr:nth-child(even) { background: #fffbe6; }
        .btn-detail { background: #ffd700; border: none; padding: 6px 16px; border-radius: 6px; cursor: pointer; }
        /* Popup */
        .modal { display: none; position: fixed; z-index: 999; left: 0; top: 0; width: 100vw; height: 100vh; overflow: auto; background: rgba(0,0,0,0.3);}
        .modal-content { background: #fff; margin: 8% auto; padding: 30px 40px; border-radius: 12px; width: 400px; position: relative; }
        .close { color: #aaa; position: absolute; right: 18px; top: 12px; font-size: 28px; font-weight: bold; cursor: pointer; }
        .modal-content h3 { text-align: center; margin-bottom: 20px; }
        .info-grid { display: grid; grid-template-columns: 140px 1fr; gap: 10px 0; }
        .info-grid .label { font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <div class="left">
        <div class="logo"><img src="imagine/HiStaff.jpg" alt="logo"></div>
        <ul>
            <li><a href="manager.php" style="color:inherit;text-decoration:none;">Home</a></li>
            <li style="font-weight:bold;"><a href="employee-list.php" style="color:inherit;text-decoration:none;">Employee List</a></li>
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
        <h2>Employee List</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>User Name</th>
                <th>Email</th>
                <th>Date of Birth</th>
                <th>See details</th>
            </tr>
            <?php foreach ($employees as $emp): ?>
                <tr>
                    <td><?= htmlspecialchars($emp['Id']) ?></td>
                    <td><?= htmlspecialchars($emp['Name']) ?></td>
                    <td><?= htmlspecialchars($emp['Email']) ?></td>
                    <td><?= htmlspecialchars($emp['DateOfBirth']) ?></td>
                    <td>
                        <button class="btn-detail"
                            onclick='showDetail(<?= json_encode($emp, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                            See details
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>

<!-- Popup modal -->
<div id="detailModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h3>Thông tin nhân viên</h3>
        <div class="info-grid" id="modalInfo">
            <!-- Nội dung sẽ được JS đổ vào -->
        </div>
    </div>
</div>

<script>
function showDetail(emp) {
    let html = '';
    html += '<div class="label">EmployeeID:</div><div>' + emp.Id + '</div>';
    html += '<div class="label">User Name:</div><div>' + emp.Name + '</div>';
    html += '<div class="label">Email:</div><div>' + emp.Email + '</div>';
    html += '<div class="label">Date of birth:</div><div>' + emp.DateOfBirth + '</div>';
    html += '<div class="label">Salary:</div><div>' + (emp.Salary !== null ? Number(emp.Salary).toLocaleString('en-US', {minimumFractionDigits:2}) + ' triệu' : '0') + '</div>';
    html += '<div class="label">Workdays:</div><div>' + (emp.Workdays ?? 0) + '</div>';
    html += '<div class="label">Welfares:</div><div>' + emp.welfares_count + '</div>';
    html += '<div class="label">Welfare List:</div><div>' + (emp.Welfares.length > 0 ? emp.Welfares.map(w => escapeHtml(w)).join('<br>') : 'Không có phúc lợi nào') + '</div>';
    html += '<div class="label">Violations:</div><div>' + emp.violations_count + '</div>';
    html += '<div class="label">Violation List:</div><div>' + (emp.Violations.length > 0 ? emp.Violations.map(v => escapeHtml(v)).join('<br>') : 'Không có lỗi nào') + '</div>';
    document.getElementById('modalInfo').innerHTML = html;
    document.getElementById('detailModal').style.display = 'block';
}
function closeModal() {
    document.getElementById('detailModal').style.display = 'none';
}
window.onclick = function(event) {
    let modal = document.getElementById('detailModal');
    if (event.target === modal) modal.style.display = "none";
}
function escapeHtml(text) {
    return text.replace(/[&<>"']/g, function(m) {
        return ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        })[m];
    });
}
</script>
</body>
</html>