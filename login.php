<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include("DBconnect.php");

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $login_input = trim($_POST['login_id'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($login_input !== '' && $password !== '') {
        $clean_input = mysqli_real_escape_string($conn, $login_input);
        $clean_pass = mysqli_real_escape_string($conn, $password);

        // Accept C5/c5 or E123/e123 as well as a plain numeric ID.
        $numeric_id_text = preg_replace('/^[cCeE]/', '', $login_input);
        $numeric_id = ctype_digit($numeric_id_text) ? (int)$numeric_id_text : 0;

        $cust_sql = "SELECT * FROM customer
                     WHERE (Customer_ID = $numeric_id OR Customer_ID = '$clean_input' OR Email = '$clean_input')
                       AND Password = '$clean_pass'
                     LIMIT 1";
        $cust_result = mysqli_query($conn, $cust_sql);

        $emp_sql = "SELECT * FROM employee
                    WHERE (Employee_ID = $numeric_id OR Employee_ID = '$clean_input' OR Username = '$clean_input')
                      AND Password = '$clean_pass'
                    LIMIT 1";
        $emp_result = mysqli_query($conn, $emp_sql);

        if ($cust_result && mysqli_num_rows($cust_result) > 0) {
            $user = mysqli_fetch_assoc($cust_result);
            $customer_id = (int)($user['Customer_ID'] ?? 0);

            // Clear any previous employee session data.
            unset($_SESSION['employee_id'], $_SESSION['Employee_id']);

            $_SESSION['user_id'] = 'C' . $customer_id;
            $_SESSION['customer_id'] = $customer_id;
            $_SESSION['user_name'] = $user['Customer_name'] ?? 'Customer';
            $_SESSION['role'] = 'customer';

            $_SESSION['points'] = (int)($user['points'] ?? $user['Loyalty_points'] ?? 0);

            echo "<script>
                localStorage.setItem('plant_hub_user', JSON.stringify({
                    user_id: " . json_encode($_SESSION['user_id']) . ",
                    user_name: " . json_encode($_SESSION['user_name']) . ",
                    role: " . json_encode($_SESSION['role']) . ",
                    login_time: new Date().toISOString()
                }));
                window.location.href = 'index.php';
            </script>";
            exit;
        }

        if ($emp_result && mysqli_num_rows($emp_result) > 0) {
            $user = mysqli_fetch_assoc($emp_result);
            $employee_id = (int)($user['Employee_ID'] ?? 0);

            // Clear any previous customer session data.
            unset($_SESSION['customer_id'], $_SESSION['Customer_id'], $_SESSION['Customer_ID']);

            $_SESSION['user_id'] = 'E' . $employee_id;
            $_SESSION['employee_id'] = $employee_id;
            $_SESSION['user_name'] = $user['Employee_name'] ?? 'Employee';
            $_SESSION['role'] = 'employee';

            echo "<script>
                localStorage.setItem('plant_hub_user', JSON.stringify({
                    user_id: " . json_encode($_SESSION['user_id']) . ",
                    user_name: " . json_encode($_SESSION['user_name']) . ",
                    role: " . json_encode($_SESSION['role']) . ",
                    login_time: new Date().toISOString()
                }));
                window.location.href = 'index.php';
            </script>";
            exit;
        }

        $error_message = "Invalid Login ID / Email or Password.";
    } else {
        $error_message = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Plant Hub</title>
    <style>
        .login-wrapper{display:flex;justify-content:center;align-items:center;padding:50px 20px;min-height:70vh}
        .login-card{width:100%;max-width:400px;background:#fff;padding:30px;border-radius:12px;box-shadow:0 4px 15px rgba(0,0,0,.1);border:1px solid #e1e8ed}
        .login-card h2{text-align:center;color:#2c3e50;margin-top:0;margin-bottom:20px}
        .form-group{margin-bottom:15px}.form-group label{display:block;margin-bottom:5px;font-weight:bold;color:#333}
        .form-group input{width:100%;padding:10px;box-sizing:border-box;border:1px solid #ccc;border-radius:6px;font-size:14px}
        .btn-submit{width:100%;background:#27ae60;color:#fff;padding:12px;border:0;border-radius:6px;font-size:16px;font-weight:bold;cursor:pointer;margin-top:10px}
        .btn-submit:hover{background:#219150}.alert-error{background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;padding:10px;margin-bottom:15px;border-radius:6px;text-align:center;font-size:14px}
        .signup-link{text-align:center;margin-top:15px;display:block;color:#2980b9;text-decoration:none}.signup-link:hover{text-decoration:underline}
    </style>
</head>
<body>
<?php if (file_exists("header.php")) include("header.php"); ?>
<div class="login-wrapper">
    <div class="login-card">
        <h2>Account Login</h2>
        <?php if ($error_message !== ''): ?><div class="alert-error"><?php echo htmlspecialchars($error_message); ?></div><?php endif; ?>
        <form action="login.php" method="POST">
            <div class="form-group">
                <label for="login_id">User ID or Email / Username</label>
                <input type="text" id="login_id" name="login_id" required placeholder="e.g. E24101143, C5, or Email">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Enter password">
            </div>
            <button type="submit" class="btn-submit">Login</button>
        </form>
        <a href="signup.php" class="signup-link">Don't have an account? Sign up here</a>
    </div>
</div>
<?php if (file_exists("footer.php")) include("footer.php"); ?>
</body>
</html>
