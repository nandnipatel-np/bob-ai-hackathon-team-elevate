<?php
/**
 * CCP - OTP Verification for Customer Registration
 * Path: verify_otp.php
 */

require_once 'config/db.php';
require_once 'mail_function.php';
session_start();

$error = '';
$success = '';

// If registration session not found, send back to register
if (
    !isset($_SESSION['registration_pending']) ||
    !isset($_SESSION['otp']) ||
    !isset($_SESSION['reg_customer_id']) ||
    !isset($_SESSION['reg_name']) ||
    !isset($_SESSION['reg_email']) ||
    !isset($_SESSION['reg_phone']) ||
    !isset($_SESSION['reg_password'])
) {
    header("Location: register.php");
    exit();
}

// Optional OTP expiry (10 minutes)
$otp_expired = false;
if (isset($_SESSION['otp_created_at']) && (time() - $_SESSION['otp_created_at']) > 600) {
    $otp_expired = true;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $entered_otp = trim((string)($_POST['otp'] ?? ''));

    if ($otp_expired) {
        $error = "OTP has expired. Please register again.";
    } elseif (empty($entered_otp)) {
        $error = "Please enter the OTP.";
    } elseif (!preg_match('/^[0-9]{6}$/', $entered_otp)) {
        $error = "Please enter a valid 6-digit OTP.";
    } elseif ($entered_otp !== (string)$_SESSION['otp']) {
        $error = "Invalid OTP. Please try again.";
    } else {
        try {
            // OTP correct -> insert into database
            // IMPORTANT: Keep customer_id as STRING (e.g., 23BCA070)
            $customer_id = strtoupper(trim((string)$_SESSION['reg_customer_id']));
            $name = trim((string)$_SESSION['reg_name']);
            $email = strtolower(trim((string)$_SESSION['reg_email']));
            $phone = trim((string)$_SESSION['reg_phone']);
            $hashed_password = (string)$_SESSION['reg_password'];

            // Extra safety validation before insert
            if (
                empty($customer_id) ||
                empty($name) ||
                empty($email) ||
                empty($phone) ||
                empty($hashed_password)
            ) {
                $error = "Registration data is missing. Please register again.";
            } else {
                // Prevent duplicate insert again before saving
                $checkStmt = $pdo->prepare("SELECT id FROM users WHERE UPPER(customer_id) = UPPER(?) OR LOWER(email) = LOWER(?) OR phone = ? LIMIT 1");
                $checkStmt->execute([$customer_id, $email, $phone]);

                if ($checkStmt->fetch()) {
                    $error = "This account is already registered. Please login.";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO users (customer_id, name, email, phone, password, role, status) VALUES (?, ?, ?, ?, ?, 'customer', 'active')");

                    if ($stmt->execute([$customer_id, $name, $email, $phone, $hashed_password])) {

                        // Send welcome email after successful verification
                        $subject = "Welcome to CCP - Registration Successful!";
                        $body = "
                            <div style='font-family: Arial, sans-serif; border: 1px solid #e67e22; padding: 25px; border-radius: 15px; max-width: 550px; margin:auto;'>
                                <h2 style='color: #e67e22; margin-top: 0;'>Welcome, $name! 🎉</h2>
                                <p>Your registration for <b>Campus Canteen Pre-Order (CCP)</b> has been verified successfully.</p>

                                <div style='background: #f8fafc; padding: 15px; border-radius: 10px; margin: 15px 0;'>
                                    <p style='margin: 8px 0;'><b>Customer ID:</b> $customer_id</p>
                                    <p style='margin: 8px 0;'><b>Registered Email:</b> $email</p>
                                </div>

                                <p>You can now login to pre-order your meals and skip the queue.</p>

                                <a href='http://localhost/demo/login.php' 
                                   style='display:inline-block; background:#e67e22; color:white; padding:12px 20px; text-decoration:none; border-radius:8px; font-weight:bold; margin-top:10px;'>
                                   Login Now
                                </a>

                                <br><br>
                                <p style='font-size: 13px; color: #636e72;'>
                                    For security reasons, your password is not included in this email.<br>
                                    Please keep your login credentials safe.
                                </p>

                                <hr style='border:none; border-top:1px solid #eee; margin:20px 0;'>

                                <p style='font-size: 12px; color: #636e72;'>
                                    Regards,<br>
                                    <b>The CCP Team</b><br>
                                    Campus Canteen Pre-Order System
                                </p>
                            </div>
                        ";

                        sendMail($email, $subject, $body);

                        // Clear session registration data
                        unset($_SESSION['registration_pending']);
                        unset($_SESSION['otp']);
                        unset($_SESSION['otp_created_at']);
                        unset($_SESSION['reg_customer_id']);
                        unset($_SESSION['reg_name']);
                        unset($_SESSION['reg_email']);
                        unset($_SESSION['reg_phone']);
                        unset($_SESSION['reg_password']);

                        $success = "Email verified successfully! Registration completed. Redirecting to login...";
                        header("refresh:3;url=login.php");
                    } else {
                        $error = "Database error. Please try again later.";
                    }
                }
            }
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}

// Resend OTP
if (isset($_GET['resend']) && $_GET['resend'] == '1') {
    $newOtp = random_int(100000, 999999);
    $_SESSION['otp'] = (string)$newOtp;
    $_SESSION['otp_created_at'] = time();

    $name = trim((string)$_SESSION['reg_name']);
    $email = strtolower(trim((string)$_SESSION['reg_email']));
    $customer_id = strtoupper(trim((string)$_SESSION['reg_customer_id']));

    $subject = "CCP Email Verification OTP - Resent";
    $body = "
        <div style='font-family: Arial, sans-serif; border: 1px solid #e67e22; padding: 25px; border-radius: 15px; max-width: 550px; margin:auto;'>
            <h2 style='color: #e67e22; margin-top: 0;'>Hello, $name 👋</h2>
            <p>Your new OTP for <b>Campus Canteen Pre-Order (CCP)</b> registration is:</p>

            <div style='background: #fff7ed; padding: 20px; border-radius: 12px; margin: 20px 0; text-align: center;'>
                <h1 style='margin: 0; color: #e67e22; letter-spacing: 6px;'>$newOtp</h1>
            </div>

            <div style='background: #f8fafc; padding: 15px; border-radius: 10px; margin: 15px 0;'>
                <p style='margin: 8px 0;'><b>Customer ID:</b> $customer_id</p>
                <p style='margin: 8px 0;'><b>Email:</b> $email</p>
            </div>

            <p style='font-size: 13px; color: #636e72;'>Please use this OTP to complete your registration.</p>

            <hr style='border:none; border-top:1px solid #eee; margin:20px 0;'>

            <p style='font-size: 12px; color: #636e72;'>
                Regards,<br>
                <b>The CCP Team</b><br>
                Campus Canteen Pre-Order System
            </p>
        </div>
    ";

    if (sendMail($email, $subject, $body)) {
        $success = "A new OTP has been sent to your email.";
    } else {
        $error = "Failed to resend OTP. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP | CCP</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #e67e22;
            --secondary: #1a1a1a;
            --bg: #f8fafc;
            --white: #ffffff;
            --text-main: #2d3436;
            --text-muted: #636e72;
        }

        body {
            background-color: var(--bg);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--text-main);
        }

        .navbar { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: 20px 8%; 
            background: var(--white); 
            box-shadow: 0 4px 20px rgba(0,0,0,0.03); 
            position: sticky; 
            top: 0; 
            z-index: 1000; 
        }

        .logo { font-size: 1.8rem; font-weight: 800; color: var(--secondary); text-decoration: none; }
        .logo span { color: var(--primary); }
        
        nav a { text-decoration: none; color: var(--text-muted); margin-left: 30px; font-weight: 700; font-size: 14px; }

        .main-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 60px 20px;
        }

        .otp-card {
            background: var(--white);
            padding: 50px 40px;
            border-radius: 24px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.05);
            width: 100%;
            max-width: 420px;
            text-align: center;
            border: 1px solid #f1f5f9;
        }

        .card-logo { font-size: 32px; font-weight: 800; margin-bottom: 10px; color: var(--secondary); }
        .card-logo span { color: var(--primary); }

        h3 { font-size: 24px; font-weight: 800; margin-top: 0; margin-bottom: 8px; color: var(--secondary); }

        .msg {
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .error-msg {
            background: #fee2e2;
            color: #ef4444;
            border: 1px solid #fecaca;
        }

        .success-msg {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        input {
            width: 100%;
            padding: 14px;
            margin: 12px 0;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-family: inherit;
            font-size: 16px;
            text-align: center;
            letter-spacing: 4px;
            font-weight: 700;
            outline: none;
            transition: 0.2s;
            box-sizing: border-box;
        }

        input:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(230, 126, 34, 0.1); }

        .btn-verify {
            width: 100%;
            padding: 15px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 15px;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 10px;
        }

        .btn-verify:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(230, 126, 34, 0.2); background: #d35400; }

        .footer-links { margin-top: 20px; font-size: 14px; color: var(--text-muted); font-weight: 600; }
        .footer-links a { color: var(--primary); text-decoration: none; font-weight: 700; }

        .footer {
            text-align: center;
            padding: 20px;
            color: var(--text-muted);
            font-size: 13px;
            font-weight: 600;
        }
    </style>
</head>
<body>

    <header class="navbar">
        <a href="index.php" class="logo">CC<span>P</span></a>
        <nav>
            <a href="index.php">Home</a>
            <a href="register.php">Back</a>
        </nav>
    </header>

    <main class="main-container">
        <div class="otp-card">
            <div class="card-logo">CC<span>P</span></div>
            <h3>Verify OTP</h3>
            <p style="color: var(--text-muted); margin-bottom: 25px; font-size: 14px;">
                Enter the 6-digit OTP sent to your email
            </p>

            <?php if ($error): ?> <div class="msg error-msg"><?php echo htmlspecialchars($error); ?></div> <?php endif; ?>
            <?php if ($success): ?> <div class="msg success-msg"><?php echo htmlspecialchars($success); ?></div> <?php endif; ?>

            <form method="POST">
                <input type="text" name="otp" maxlength="6" placeholder="Enter OTP" pattern="[0-9]{6}" inputmode="numeric" required>
                <button type="submit" class="btn-verify">Verify & Complete Registration</button>
            </form>

            <div class="footer-links">
                Didn’t receive OTP? <a href="verify_otp.php?resend=1">Resend OTP</a>
            </div>
        </div>
    </main>

    <footer class="footer">
        &copy; <?php echo date('Y'); ?> CCP - Campus Canteen Pre-Order. All rights reserved.
    </footer>

</body>
</html>