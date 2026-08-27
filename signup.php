<?php
include("DBconnect.php");

$message = "";
$message_type = "";
$assigned_id = "";
$user_role = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $role     = $_POST['role'] ?? '';
    $name     = isset($_POST['name']) ? mysqli_real_escape_string($conn, $_POST['name']) : '';
    $email    = isset($_POST['email']) ? mysqli_real_escape_string($conn, $_POST['email']) : '';
    $password = isset($_POST['password']) ? mysqli_real_escape_string($conn, $_POST['password']) : '';

    if (!empty($role) && !empty($name) && !empty($email) && !empty($password)) {
        if ($role === "customer") {
            // Set points to 0 by default for new customers
            $sql = "INSERT INTO customer (Customer_name, Email, Password, points) VALUES ('$name', '$email', '$password', 0)";
            
            if (mysqli_query($conn, $sql)) {
                $new_id = mysqli_insert_id($conn);
                $assigned_id = "C" . $new_id;
                $user_role = "Customer";
                $message_type = "success";
            } else {
                $message = "Error creating account: " . mysqli_error($conn);
                $message_type = "error";
            }

        } else if ($role === "employee") {
            $sql = "INSERT INTO employee (Employee_name, Username, Password) VALUES ('$name', '$email', '$password')";
            
            if (mysqli_query($conn, $sql)) {
                $new_id = mysqli_insert_id($conn);
                $assigned_id = "E" . $new_id;
                $user_role = "Employee";
                $message_type = "success";
            } else {
                $message = "Error creating account: " . mysqli_error($conn);
                $message_type = "error";
            }
        }
    } else {
        $message = "Please fill in all required fields.";
        $message_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Plant Hub</title>
    <style>
        .signup-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
            width: 100%;
            box-sizing: border-box;
        }
        .signup-card {
            width: 100%;
            max-width: 420px;
            background: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid #e1e8ed;
            text-align: left;
        }
        .signup-card h2 {
            text-align: center;
            color: #2c3e50;
            margin-top: 0;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }
        .form-group input, 
        .form-group select {
            width: 100%;
            padding: 10px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
        }
        .btn-submit, .btn-login {
            width: 100%;
            background-color: #27ae60;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            box-sizing: border-box;
        }
        .btn-submit:hover, .btn-login:hover {
            background-color: #219150;
        }
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 6px;
            text-align: center;
            font-size: 14px;
        }
        .success-box {
            text-align: center;
            padding: 10px 0;
        }
        .success-box .icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .id-badge {
            background: #e8f5e9;
            color: #1e3c2b;
            font-size: 24px;
            font-weight: bold;
            padding: 12px;
            border-radius: 8px;
            border: 2px dashed #27ae60;
            margin: 15px 0;
            display: block;
        }
        .login-link {
            text-align: center;
            margin-top: 15px;
            display: block;
            color: #2980b9;
            text-decoration: none;
        }
        .login-link:hover { text-decoration: underline; }
    </style>
</head>
<body>

<?php 
if (file_exists("header.php")) {
    include("header.php");
}
?>

<div class="signup-wrapper">
    <div class="signup-card">
        
        <?php if ($message_type === "success"): ?>
            <div class="success-box">
                <div class="icon">🎉</div>
                <h2>Registration Complete!</h2>
                <p><?php echo $user_role; ?> account created successfully.</p>
                <p>Your unique login ID is:</p>
                <div class="id-badge"><?php echo $assigned_id; ?></div>
                <p style="font-size: 13px; color: #666; margin-bottom: 20px;">Please save this ID to log in.</p>
                <a href="login.php" class="btn-login">Proceed to Login &rarr;</a>
            </div>

        <?php else: ?>
            <h2>Create Account</h2>

            <?php if (!empty($message)): ?>
                <div class="alert-error">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form action="signup.php" method="POST">
                <div class="form-group">
                    <label for="role">Register As</label>
                    <select name="role" id="role" required>
                        <option value="customer">Customer</option>
                        <option value="employee">Employee</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" required placeholder="Enter your full name">
                </div>

                <div class="form-group">
                    <label for="email">Email / Username</label>
                    <input type="text" id="email" name="email" required placeholder="Enter email or username">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="Enter password">
                </div>

                <button type="submit" class="btn-submit">Sign Up</button>
            </form>

            <a href="login.php" class="login-link">Already have an account? Login here</a>
        <?php endif; ?>

    </div>
</div>

<?php 
if (file_exists("footer.php")) {
    include("footer.php");
}
?>

</body>
</html>