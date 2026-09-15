<?php
require_once 'config/db.php';
require_once 'mail_function.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);

    // Validate email
    if (empty($email)) {
        $message = "<div class='alert error'>Please enter your email address.</div>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "<div class='alert error'>Please enter a valid email address.</div>";
    } else {
        // Check if email exists in database
        $stmt = $pdo->prepare("SELECT id, name, email, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Generate stronger temporary password
            $tempPassword = "CCP@" . strtoupper(bin2hex(random_bytes(4)));
            $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);

            // Update password in DB
            $updateStmt = $pdo->prepare("UPDATE users SET password = ?, must_change_password = 1 WHERE email = ?");
            if ($updateStmt->execute([$hashedPassword, $email])) {

                // Email Subject
                $subject = "CCP Password Reset Request";

                // Email Body
                $body = "
                    <div style='font-family: Arial, sans-serif; border: 1px solid #e67e22; padding: 25px; border-radius: 15px; max-width: 550px; margin:auto;'>
                        <h2 style='color: #e67e22; margin-top: 0;'>Hello, {$user['name']} 👋</h2>
                        <p>Your password for the <b>Campus Canteen Pre-Order (CCP)</b> system has been reset successfully.</p>

                        <div style='background: #f8fafc; padding: 15px; border-radius: 10px; margin: 15px 0;'>
                            <p style='margin: 8px 0;'><b>Registered Email:</b> {$user['email']}</p>
                            <p style='margin: 8px 0;'><b>Temporary Password:</b> {$tempPassword}</p>
                            <p style='margin: 8px 0;'><b>Account Type:</b> " . ucfirst($user['role']) . "</p>
                        </div>

                        <p>Please login using this temporary password and change it immediately for security.</p>

                        <a href='http://localhost/demo/login.php' 
                           style='display:inline-block; background:#e67e22; color:white; padding:12px 20px; text-decoration:none; border-radius:8px; font-weight:bold; margin-top:10px;'>
                           Login Now
                        </a>

                        <hr style='border:none; border-top:1px solid #eee; margin:20px 0;'>

                        <p style='font-size: 12px; color: #636e72;'>
                            Regards,<br>
                            <b>CCP Team</b><br>
                            Campus Canteen Pre-Order System
                        </p>
                    </div>
                ";

                // Send mail
                sendMail($email, $subject, $body);
            }
        }

        // Always show same message for security
        $message = "<div class='alert success'>If this email exists in our system, a temporary password has been sent successfully.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | CCP</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Base styles */
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background: #f7f9fc;
            margin: 0; 
            display: flex; 
            flex-direction: column; 
            min-height: 100vh;
        }

        /* Replicating the header */
        .header {
            background-color: white;
            padding: 15px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
        }
        .header-logo {
            display: flex;
            align-items: center;
        }
        .header-logo .cc-part {
            font-size: 32px;
            font-weight: 800;
            color: #1e293b;
        }
        .header-logo .p-part {
            font-size: 32px;
            font-weight: 800;
            color: #e67e22;
        }
        .header-nav {
            display: flex;
            gap: 20px;
        }
        .header-nav a {
            text-decoration: none;
            color: #4b5563;
            font-weight: 600;
            font-size: 15px;
        }

        /* Main content area */
        .main-content {
            flex-grow: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        /* Card styling */
        .card { 
            background: white; 
            padding: 50px 40px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.03);
            width: 100%; 
            max-width: 440px; 
            text-align: center; 
            box-sizing: border-box;
        }

        /* Card Logo */
        .card-logo {
            display: flex;
            justify-content: center;
            margin-bottom: 25px;
        }
        .card-logo .cc-part {
            font-size: 40px;
            font-weight: 800;
            color: #1e293b;
        }
        .card-logo .p-part {
            font-size: 40px;
            font-weight: 800;
            color: #e67e22;
        }

        /* Heading */
        h2 { margin: 0 0 10px 0; color: #1e293b; font-size: 28px; font-weight: 700; }
        .subtitle { color: #64748b; font-size: 14px; margin-bottom: 30px; line-height: 1.5; }

        /* Input */
        .input-group { margin-bottom: 25px; }
        input[type="email"] { 
            width: 100%; 
            padding: 16px; 
            border-radius: 12px; 
            border: none;
            background-color: #f1f5f9;
            box-sizing: border-box;
            font-size: 16px;
            color: #1e293b;
        }
        input[type="email"]::placeholder {
            color: #94a3b8;
        }

        /* Button */
        .btn { 
            background: #e67e22;
            color: white; 
            border: none; 
            padding: 16px; 
            border-radius: 12px; 
            width: 100%; 
            font-weight: 700; 
            cursor: pointer; 
            font-size: 16px;
            transition: background 0.2s ease;
        }
        .btn:hover { background: #d35400; }

        /* Alert */
        .alert { 
            padding: 14px; 
            border-radius: 12px; 
            margin-bottom: 25px; 
            font-size: 14px; 
            font-weight: 600; 
        }
        .success { background: #dcfce7; color: #166534; }
        .error { background: #fee2e2; color: #991b1b; }

        /* Links */
        .link-section { margin-top: 30px; font-size: 14px; color: #64748b; font-weight: 600; }
        .link-section p { margin: 5px 0; }
        .link-section a { color: #e67e22; text-decoration: none; }
        .link-section a:hover { text-decoration: underline; }
    </style>
</head>
<body>

    <header class="header">
        <div class="header-logo">
            <span class="cc-part">CC</span><span class="p-part">P</span>
        </div>
        <nav class="header-nav">
            <a href="index.php">Home</a>
            <a href="join.php">Join Now</a>
        </nav>
    </header>

    <main class="main-content">
        <div class="card">
            <div class="card-logo">
                <span class="cc-part">CC</span><span class="p-part">P</span>
            </div>
            
            <h2>Forgot Password</h2>
            <p class="subtitle">Please enter your email to get a password reset link.</p>
            
            <?php echo $message; ?>

            <form method="POST">
                <div class="input-group">
                    <input type="email" name="email" placeholder="nandni@gmail.com" required>
                </div>
                <button type="submit" class="btn">Send Recovery Link</button>
            </form>
            
            <div class="link-section">
                <p>Don’t have an account? <a href="join.php">Register Now</a></p>
                <p><a href="login.php">Back to Login</a></p>
            </div>
        </div>
    </main>

</body>
</html>