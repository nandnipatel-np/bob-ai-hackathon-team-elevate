<?php
/**
 * CCP - Customer Registration with Email OTP Verification
 * Path: register.php
 */

require_once 'config/db.php';
require_once 'mail_function.php';
session_start();

$error = '';
$success = '';

// Clear old registration session only when page is opened fresh
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    unset($_SESSION['registration_pending']);
    unset($_SESSION['otp']);
    unset($_SESSION['otp_created_at']);
    unset($_SESSION['reg_customer_id']);
    unset($_SESSION['reg_name']);
    unset($_SESSION['reg_email']);
    unset($_SESSION['reg_phone']);
    unset($_SESSION['reg_password']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Always keep customer_id as STRING
    $customer_id = strtoupper(trim((string)($_POST['customer_id'] ?? '')));
    $name = trim((string)($_POST['name'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $phone = trim((string)($_POST['phone'] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $confirm_password = (string)($_POST['confirm_password'] ?? '');

    // Email split safely
    $email_parts = explode('@', $email);
    $email_username = strtoupper(trim($email_parts[0] ?? ''));
    $email_domain = strtolower(trim($email_parts[1] ?? ''));

    /**
     * VALID CUSTOMER ID RULES
     * Student IDs examples:
     * 23BCA070, 23BSIT001, 23IT123, 24EC045, 24AIML012, 23CAC001, 23MSCIT005
     *
     * Staff IDs examples:
     * 1047, 2201
     */
    $is_student_id = preg_match('/^[0-9]{2}[A-Z]{2,10}[0-9]{2,4}$/', $customer_id);
    $is_staff_id   = preg_match('/^[0-9]{3,6}$/', $customer_id);

    // --- SERVER-SIDE VALIDATION ---
    if (empty($customer_id) || empty($name) || empty($email) || empty($phone) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required.";
    }
    elseif (!$is_student_id && !$is_staff_id) {
        $error = "Customer ID format is invalid. Example: 23BCA070 or 1047.";
    }
    elseif (!preg_match("/^[a-zA-Z\s]+$/", $name)) {
        $error = "Full Name cannot contain numbers or special characters.";
    }
    elseif (!preg_match("/^[0-9]{10}$/", $phone)) {
        $error = "Please enter exactly 10 digits for the mobile number.";
    }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    }
    elseif (!preg_match("/@(charusat\.edu\.in|charusat\.ac\.in|gmail\.com)$/i", $email)) {
        $error = "Only @charusat.edu.in, @charusat.ac.in or @gmail.com addresses are allowed.";
    }
    // For Charusat email, username must match customer ID exactly
    elseif ($email_domain !== 'gmail.com' && $email_username !== $customer_id) {
        $error = "For Charusat email, use the same Customer ID before @. Example: {$customer_id}@{$email_domain}";
    }
    elseif (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/", $password)) {
        $error = "Password must be at least 8 characters, include uppercase, lowercase, number, and special character.";
    }
    elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    }
    else {
        try {
            // IMPORTANT: customer_id must remain as full string like 23BCA070
            // Never cast to int / intval

            // 1) Check Customer ID
            $stmtId = $pdo->prepare("SELECT id FROM users WHERE UPPER(customer_id) = UPPER(?) LIMIT 1");
            $stmtId->execute([$customer_id]);

            if ($stmtId->fetch()) {
                $error = "This Customer ID is already registered.";
            } else {
                // 2) Check Email
                $stmtEmail = $pdo->prepare("SELECT id FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1");
                $stmtEmail->execute([$email]);

                if ($stmtEmail->fetch()) {
                    $error = "This Email is already registered.";
                } else {
                    // 3) Check Phone
                    $stmtPhone = $pdo->prepare("SELECT id FROM users WHERE phone = ? LIMIT 1");
                    $stmtPhone->execute([$phone]);

                    if ($stmtPhone->fetch()) {
                        $error = "This Mobile Number is already registered.";
                    } else {
                        // Generate OTP
                        $otp = random_int(100000, 999999);
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                        // Store registration data in session safely
                        $_SESSION['registration_pending'] = true;
                        $_SESSION['otp'] = (string)$otp;
                        $_SESSION['otp_created_at'] = time();
                        $_SESSION['reg_customer_id'] = $customer_id; // full string like 23BCA070
                        $_SESSION['reg_name'] = $name;
                        $_SESSION['reg_email'] = $email;
                        $_SESSION['reg_phone'] = $phone;
                        $_SESSION['reg_password'] = $hashed_password;

                        // OTP Email
                        $subject = "CCP Verification OTP: $otp";
                        $body = "
                            <div style='font-family: Arial, sans-serif; border: 1px solid #e67e22; padding: 25px; border-radius: 15px; max-width: 550px; margin:auto;'>
                                <h2 style='color: #e67e22;'>Hello, {$name} 👋</h2>
                                <p>Your OTP for <b>Campus Canteen Pre-Order (CCP)</b> registration is:</p>
                                <div style='background: #fff7ed; padding: 20px; border-radius: 12px; text-align: center; margin:20px 0;'>
                                    <h1 style='margin: 0; color: #e67e22; letter-spacing: 5px;'>{$otp}</h1>
                                </div>
                                <p>This code is valid for 10 minutes.</p>
                            </div>";

                        // Send OTP
                        $mailResult = sendMail($email, $subject, $body);

                        if ($mailResult === true) {
                            header("Location: verify_otp.php");
                            exit();
                        } else {
                            // Clear only OTP session if mail fails
                            unset($_SESSION['registration_pending']);
                            unset($_SESSION['otp']);
                            unset($_SESSION['otp_created_at']);
                            unset($_SESSION['reg_customer_id']);
                            unset($_SESSION['reg_name']);
                            unset($_SESSION['reg_email']);
                            unset($_SESSION['reg_phone']);
                            unset($_SESSION['reg_password']);

                            $error = "OTP mail could not be sent. Mail Error: " . $mailResult;
                        }
                    }
                }
            }
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Registration | CCP</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #e67e22; --secondary: #1a1a1a; --bg: #f8fafc; --white: #ffffff; --text-muted: #636e72; }
        body { background-color: var(--bg); margin: 0; font-family: 'Plus Jakarta Sans', sans-serif; display: flex; flex-direction: column; min-height: 100vh; }
        .navbar { display: flex; justify-content: space-between; align-items: center; padding: 20px 8%; background: var(--white); box-shadow: 0 4px 20px rgba(0,0,0,0.03); position: sticky; top: 0; z-index: 1000; }
        .logo { font-size: 1.8rem; font-weight: 800; color: var(--secondary); text-decoration: none; }
        .logo span { color: var(--primary); }
        nav a { text-decoration: none; color: var(--text-muted); margin-left: 30px; font-weight: 700; font-size: 14px; }
        .main-container { flex: 1; display: flex; justify-content: center; align-items: center; padding: 40px 20px; }
        .register-card { background: var(--white); padding: 40px; border-radius: 24px; box-shadow: 0 20px 50px rgba(0,0,0,0.05); width: 100%; max-width: 500px; text-align: center; border: 1px solid #f1f5f9; }
        .msg { padding: 12px; border-radius: 12px; margin-bottom: 20px; font-size: 13px; font-weight: 600; }
        .error-msg { background: #fee2e2; color: #ef4444; border: 1px solid #fecaca; }
        .input-group { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; text-align: left; }
        .full-width { grid-column: span 2; }
        input { width: 100%; padding: 14px; border: 1px solid #e2e8f0; border-radius: 12px; font-size: 14px; outline: none; transition: 0.2s; box-sizing: border-box; }
        input:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(230, 126, 34, 0.1); }
        .btn-reg { width: 100%; padding: 15px; background: var(--primary); color: white; border: none; border-radius: 12px; font-weight: 700; font-size: 15px; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .btn-reg:hover { background: #d35400; transform: translateY(-2px); }
        .footer-links { margin-top: 20px; font-size: 14px; color: var(--text-muted); font-weight: 600; }
        .footer-links a { color: var(--primary); text-decoration: none; font-weight: 700; }
        .password-strength { font-size: 11px; margin-top: 5px; font-weight: 700; }
        .weak { color: #ef4444; } .medium { color: #e67e22; } .strong { color: #10b981; }
    </style>
</head>
<body>
    <header class="navbar">
        <a href="index.php" class="logo">CC<span>P</span></a>
        <nav><a href="index.php">Home</a><a href="login.php">Login</a></nav>
    </header>
    <main class="main-container">
        <div class="register-card">
            <h3>Customer Registration</h3>
            <?php if ($error): ?> <div class="msg error-msg"><?= htmlspecialchars($error); ?></div> <?php endif; ?>
            <form action="register.php" method="POST" class="input-group" id="regForm" autocomplete="off">
                <div class="field-wrapper full-width">
                    <input type="text" name="customer_id" id="sid" placeholder="Customer ID (e.g., 23BSIT001)" maxlength="16" value="<?= isset($_POST['customer_id']) ? htmlspecialchars($_POST['customer_id']) : ''; ?>" required>
                </div>
                <div class="field-wrapper">
                    <input type="text" name="name" id="fname" placeholder="Full Name" value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                </div>
                <div class="field-wrapper">
                    <input type="text" name="phone" id="phone" placeholder="Mobile (10 Digits)" value="<?= isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>" required>
                </div>
                <div class="field-wrapper full-width">
                    <input type="email" name="email" placeholder="Email (@charusat.edu.in)" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                </div>
                <div class="field-wrapper full-width">
                    <input type="password" name="password" id="pass" placeholder="Create Strong Password" required>
                    <div id="passStrength" class="password-strength"></div>
                </div>
                <div class="field-wrapper full-width">
                    <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                </div>
                <button type="submit" class="btn-reg full-width">Register Now</button>
            </form>
            <div class="footer-links">Already have an account? <a href="login.php">Login here</a></div>
        </div>
    </main>

    <script>
        document.getElementById('phone').addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length > 10) this.value = this.value.slice(0, 10);
        });

        document.getElementById('fname').addEventListener('input', function() {
            this.value = this.value.replace(/[^a-zA-Z\\s]/g, '');
        });

        document.getElementById('sid').addEventListener('input', function() {
            this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        });

        const passInput = document.getElementById('pass');
        const strengthDiv = document.getElementById('passStrength');

        passInput.addEventListener('input', function() {
            let val = this.value;
            let strength = 0;
            if (val.length >= 8) strength++;
            if (/[A-Z]/.test(val)) strength++;
            if (/[a-z]/.test(val)) strength++;
            if (/[0-9]/.test(val)) strength++;
            if (/[@$!%*?&]/.test(val)) strength++;

            if (val === "") {
                strengthDiv.innerHTML = "";
            } else if (strength < 3) {
                strengthDiv.innerHTML = "Weak";
                strengthDiv.className = "password-strength weak";
            } else if (strength < 5) {
                strengthDiv.innerHTML = "Medium";
                strengthDiv.className = "password-strength medium";
            } else {
                strengthDiv.innerHTML = "Strong ✅";
                strengthDiv.className = "password-strength strong";
            }
        });
    </script>
</body>
</html>