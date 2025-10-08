<?php
session_start();
include("db.php");

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['email']) && isset($_POST['password'])) {
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        $query = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($query);

        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();

                if (password_verify($password, $user['password'])) {
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['user_id'] = $user['id'];

                if ($user['role'] === 'admin') {
                    header("Location: admin_dashboard.php");
                    exit();
                } else if ($user['role'] === 'staff' || $user['role'] === 'head') {
                    header("Location: staff_dashboard.php");
                    exit();
                }else {
                    $error = "Invalid email or password!";
                }
            } else {
                $error = "Invalid email or password!";
            }
            $stmt->close();
        } else {
            $error = "Database query failed.";
        }
    } else {
        $error = "Please fill in both fields.";
    }
}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SDU - Login</title>

    <style>
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap');

    body {
        font-family: 'Montserrat', sans-serif;
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        background-color: #f0f2f5;
    }

    .login-container {
        display: flex;
        width: 100%;
        max-width: 1000px;
        height: 100vh;
        max-height: 600px;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }

    .login-left {
        background-color: #1a237e;
        color: white;
        flex: 1;
        display: flex;
        justify-content: center;
        align-items: center;
        text-align: center;
        padding: 20px;
    }

    .login-left h1 {
        font-size: 3rem;
        font-weight: 700;
        margin-bottom: 10px;
        text-align: left; 
        padding-left: 20px;
    }

    .login-right {
        background-color: white;
        flex: 1;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 20px;
    }

    .login-form-box {
        width: 100%;
        max-width: 400px;
    }

    .login-form-box h2 {
        font-size: 1.8rem;
        font-weight: 700;
        color: #1a237e;
        border-bottom: 3px solid #1a237e;
        padding-bottom: 5px;
        margin-bottom: 25px; 
    }

    .form-group {
        margin-bottom: 20px; 
    }

    .form-group label {
        display: block;
        font-size: 0.8rem;
        font-weight: 700;
        color: #495057;
        margin-bottom: 5px;
        text-transform: uppercase;
    }

    .input-with-icon {
        display: flex;
        align-items: center;
        border: 1px solid #ced4da;
        border-radius: 0;
        background-color: #e9ecef;
    }

    .input-with-icon svg {
        margin: 0 10px;
        color: #6c757d;
    }

    .input-with-icon input {
        width: 100%;
        border: none;
        padding: 10px;
        background-color: white;
        outline: none;
    }

    .input-with-icon input::placeholder {
        color: #6c757d;
    }

    .input-with-icon input:focus {
        outline: 2px solid #1a237e;
        outline-offset: -2px;
    }

    .login-btn {
        width: 100%;
        padding: 12px;
        background-color: #1a237e;
        color: white;
        border: none;
        font-size: 1.1rem;
        font-weight: 700;
        cursor: pointer;
        transition: background-color 0.2s ease-in-out;
        margin-top: 10px;
    }

    .login-btn:hover {
        background-color: #141b63;
    }

    .register {
        text-align: center;
        margin-top: 20px; 
    }

    .register a {
        color: #1a237e;
        text-decoration: none;
        font-size: 0.9rem;
    }

    .register a:hover {
        text-decoration: underline;
    }

    @media (max-width: 992px) {
        .login-container {
            max-width: 800px;
        }
        .login-left h1 {
            font-size: 2rem;
        }
    }

    @media (max-width: 768px) {
        .login-container {
            flex-direction: column-reverse;
            height: auto;
            max-height: none;
            border-radius: 0;
            box-shadow: none;
        }
        .login-left, .login-right {
            min-height: 40vh;
            width: 100%;
            padding: 40px 20px;
        }
        .login-right {
            min-height: 60vh;
        }
        .login-form-box {
            max-width: 100%;
        }
    }

    @media (max-width: 480px) {
        .login-left h1 {
            font-size: 1.8rem;
        }
        .login-form-box h2 {
            font-size: 1.5rem;
        }
        .role-selection {
            flex-direction: column; 
        }
    }
    
    </style>

</head>
<body>
     <div class="login-container">
        <div class="login-left">
            <h1>SOCIAL DEVELOPMENT UNIT</h1>
        </div>

        <div class="login-right">
            <div class="login-form-box">
                <h2>Sign In</h2>

                <?php if ($error): ?>
                    <div class="message error">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form id="loginForm" method="post" action="login.php">
                    <div class="form-group">
                        <label for="email">EMAIL</label>
                        <div class="input-with-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1zm13 1v.76L8.14 9.172a.5.5 0 0 1-.284 0L1 4.76V4a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1"/>
                            </svg>
                            <input type="email" id="email" name="email" placeholder="Type your Email" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="password">PASSWORD</label>
                        <div class="input-with-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2m3 6V3a3 3 0 1 0-6 0v4a1 1 0 0 0-1 1v2a2 2 0 0 0 2 2v2a.5.5 0 0 0 1 0v-2a2 2 0 0 0 2-2V8a1 1 0 0 0-1-1M5.5 8.5a.5.5 0 0 1 1 0v2a.5.5 0 0 1-1 0z"/>
                            </svg>
                            <input type="password" id="password" name="password" placeholder="Type your password" required>
                        </div>
                    </div>
                    <button type="submit" class="login-btn">LOG IN</button>
                </form>
                <div class="register">
                    <a href="registration.php">Don't have an Account? Register</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>