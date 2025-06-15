function isValidEmail(email) {
      const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      return re.test(email);
    }

function signin(event) {
    event.preventDefault();
    var email = document.getElementById("email").value;
    var password = document.getElementById("password").value;
    
    if (email === "" || password === "") {
        alert("Please enter email and password.");
        return;
    }

    var user = localStorage.getItem(email);
    if (!user) {
        alert("No user found. Please sign up first.");
        return;
    }
    
    var data = JSON.parse(user);
    if (data.email === email && data.password === password) {
        // Lưu user hiện tại để trang khác lấy thông tin
        localStorage.setItem("currentUser", email);
        alert("Login successful!");
        if (data.role === "manager") {
            window.location.href = "manager.html";
        } else {
            window.location.href = "employee.html";
        }
    } 
    else {
        alert("Invalid email or password.");
    }
}

function signup(event) {
    event.preventDefault();
    var username = document.getElementById("username").value.trim();
    var email = document.getElementById("email").value.trim();
    var password = document.getElementById("password").value;
    var confirmpassword = document.getElementById("confirmpassword").value;
    var dateofbirth = document.getElementById("dateofbirth").value;
    var role = document.getElementById("role").value;

    // Kiểm tra các trường bắt buộc
    if (!username || !email || !password || !confirmpassword || !dateofbirth || !role) {
        alert("Please enter complete information.");
        return;
    }

    // Kiểm tra định dạng email
    if (!isValidEmail(email)) {
        alert("Invalid email.");
        return;
    }

    // Kiểm tra xem email đã tồn tại chưa
    if (localStorage.getItem(email)) {
        alert("Email is already registered. Please use another email.");
        return;
    }
    
    // Kiểm tra xác nhận mật khẩu
    if (password !== confirmpassword) {
        alert("Password and Confirm password do not match!");
        return;
    }

    // Tạo ID tự động
    var employeeIdKey = "lastEmployeeId";
    var managerIdKey = "lastManagerId";
    var newId = "";
    if (role === "employee") {
        let lastId = parseInt(localStorage.getItem(employeeIdKey)) || 20230000;
        lastId++;
        localStorage.setItem(employeeIdKey, lastId); // cập nhật ID mới nhất
        newId = "EP" + lastId;
        // Thông tin người dùng
        var user = {
            id: newId,
            username: username,
            email: email,
            password: password,
            dateofbirth: dateofbirth,
            role: role,
            salary: 0,
            welfares: 0,
            violations: 0,
            welfare: [],
            violation: []
        };
    } 
    else if (role === "manager") {
        let lastId = parseInt(localStorage.getItem(managerIdKey)) || 20239000;
        lastId++;
        localStorage.setItem(managerIdKey, lastId); // cập nhật ID mới nhất
        newId = "MN" + lastId;
        // Thông tin người dùng
        var user = {
            id: newId,
            username: username,
            email: email,
            password: password,
            dateofbirth: dateofbirth,
            role: role,
        };
    }

    // Lưu vào localStorage
    localStorage.setItem(email, JSON.stringify(user));
    alert("User signed up successfully! ID: " + newId);
    window.location.href = "signin.html";
}
