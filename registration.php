<?php
session_start();
include("db.php");

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['role']) && isset($_POST['username']) && isset($_POST['email']) && isset($_POST['password'])) {
        $role = trim($_POST['role']);
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $office = isset($_POST['office']) ? trim($_POST['office']) : '';

        if (empty($role) || empty($username) || empty($email) || empty($password)) {
            $error = "All fields are required.";
        } elseif (in_array($role, ['staff','head']) && empty($office)) {
            $error = "Please select your office.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        } else {
            $query_check = "SELECT id FROM users WHERE email = ?";
            $stmt_check = $conn->prepare($query_check);
            $stmt_check->bind_param("s", $email);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check->num_rows > 0) {
                $error = "Email already registered!";
            } else {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                $query_insert = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)";
                $stmt_insert = $conn->prepare($query_insert);

                if ($stmt_insert) {
                    $stmt_insert->bind_param("ssss", $username, $email, $hashed_password, $role);
                    if ($stmt_insert->execute()) {
                        $new_user_id = $stmt_insert->insert_id;
                        if (in_array($role, ['staff','head'])) {
                            $stmt_sd = $conn->prepare("INSERT INTO staff_details (user_id, office) VALUES (?, ?)");
                            $stmt_sd->bind_param("is", $new_user_id, $office);
                            $stmt_sd->execute();
                            $stmt_sd->close();
                        }
                        $_SESSION['registration_success'] = "Registration successful! You can now sign in.";
                        header("Location: login.php");
                        exit();
                    } else {
                        $error = "Registration failed. Please try again.";
                    }
                    $stmt_insert->close();
                } else {
                    $error = "Database query failed.";
                }
            }
            $stmt_check->close();
        }
    } else {
        $error = "Please fill in all the required fields.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SDU - Register</title>
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
        background-image: url(sma-cover-1024x576.jpg);
        background-size: cover;
    }

    .registration-container {
        display: flex;
        flex-direction: row-reverse; 
        width: 100%;
        max-width: 1000px;
        height: 100vh;
        max-height: 600px;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }

    .registration-right {
        background-color: white;
        flex: 1;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 20px;
    }

    .registration-left {
        background-color: #1a237e;
        color: white;
        flex: 1;
        display: flex;
        justify-content: center;
        align-items: center;
        text-align: center;
        padding: 20px;
    }

    .registration-left h1 {
        font-size: 3rem;
        font-weight: 700;
        margin-bottom: 10px;
        text-align: left; 
        padding-left: 20px;
    }
    
    .registration-form-box {
        width: 100%;
        max-width: 400px;
    }

    .registration-form-box h2 {
        font-size: 1.8rem;
        font-weight: 700;
        color: #1a237e;
        border-bottom: 3px solid #1a237e;
        padding-bottom: 5px;
        margin-bottom: 25px;
    }

    .registration-form-box p {
        color: #6c757d;
        margin-bottom: 20px;
    }

    .role-selection {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
    }

    .role-selection input[type="radio"] {
        display: none;
    }

    .role-selection label {
        flex: 1;
        text-align: center;
        padding: 10px;
        border: 1px solid #ced4da;
        cursor: pointer;
        font-weight: bold;
        color: #495057;
        transition: background-color 0.2s ease-in-out, border-color 0.2s ease-in-out;
        font-size: 0.8rem;
    }

    .role-selection input[type="radio"]:checked + label {
        background-color: #1a237e;
        color: white;
        border-color: #1a237e;
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

    .register-btn {
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

    .register-btn:hover {
        background-color: #141b63;
    }

    .login {
        text-align: center;
        margin-top: 20px;
    }

    .login a {
        color: #1a237e;
        text-decoration: none;
        font-size: 0.9rem;
    }

    .login a:hover {
        text-decoration: underline;
    }

    .message {
        padding: 10px;
        margin-bottom: 15px;
        border-radius: 5px;
        text-align: center;
        font-weight: bold;
    }

    .error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    @media (max-width: 768px) {
        .registration-container {
            flex-direction: column-reverse;
            width: 90%;
            height: auto;
            max-width: none;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        .registration-right, .registration-left {
            min-height: auto;
            width: 100%;
            padding: 40px 20px;
        }
        .registration-form-box {
            max-width: 100%;
        }
    }

    </style> 

</head>
<body>
     <div class="registration-container">
        <div class="registration-right">
            <div class="registration-form-box">
                <h2>Create an Account</h2>
                
                <?php if ($error): ?>
                    <div class="message error">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <p>Please select your role</p>
                <form action="registration.php" method="POST">
                    <div class="role-selection">
                        <input type="radio" id="admin" name="role" value="admin" required>
                        <label for="admin">ADMIN</label>
                        <input type="radio" id="staff" name="role" value="staff">
                        <label for="staff">STAFF</label>
                        <input type="radio" id="head" name="role" value="head">
                        <label for="head">HEAD</label>
                    </div>

                    <div class="form-group">
                        <label for="username">USERNAME</label>
                        <div class="input-with-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6m2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0m4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4m-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.685 10.567 10 8 10s-3.516.685-4.168 1.332c-.678.678-.83 1.418-.832 1.664z"/>
                            </svg>
                            <input type="text" id="username" name="username" placeholder="Type your username" required>
                        </div>
                    </div>
                    <div class="form-group" id="office-group" style="display:none;">
                        <label for="office">OFFICE</label>
                        <select id="office" name="office">
                            <option value="">Select your office</option>
                            <option value="Ateneo Center for Culture & the Arts (ACCA)">Ateneo Center for Culture & the Arts (ACCA)</option>
                            <option value="Ateneo Center for Environment & Sustainability (ACES)">Ateneo Center for Environment & Sustainability (ACES)</option>
                            <option value="Ateneo Center for Leadership & Governance (ACLG)">Ateneo Center for Leadership & Governance (ACLG)</option>
                            <option value="Ateneo Peace Institute (API)">Ateneo Peace Institute (API)</option>
                            <option value="Center for Community Extension Services (CCES)">Center for Community Extension Services (CCES)</option>
                            <option value="Ateneo Learning and Teaching Excellence Center (ALTEC)">Ateneo Learning and Teaching Excellence Center (ALTEC)</option>
                        </select>
                    </div>
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
                    <button type="submit" class="register-btn">REGISTER</button>
                </form>
                <div class="login">
                    <a href="login.php">Already have an account? Sign In</a>
                </div>
            </div>
        </div>

        <div class="registration-left">
            <h1>SOCIAL DEVELOPMENT UNIT</h1>
        </div>
    </div>
<script>
    const adminRadio = document.getElementById('admin');
    const staffRadio = document.getElementById('staff');
    const headRadio = document.getElementById('head');
    const officeGroup = document.getElementById('office-group');
    function toggleOffice() {
        if (staffRadio.checked || headRadio.checked) {
            officeGroup.style.display = '';
        } else {
            officeGroup.style.display = 'none';
        }
    }
    [adminRadio, staffRadio, headRadio].forEach(r => r.addEventListener('change', toggleOffice));
    toggleOffice();
 </script>
</body>
</html>