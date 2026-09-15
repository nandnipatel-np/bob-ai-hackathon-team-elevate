<?php
require_once 'config/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (empty($new_password) || empty($confirm_password)) {
        $message = "Please fill in all fields.";
    } elseif (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/", $new_password)) {
        $message = "Password must be at least 8 characters and include uppercase, lowercase, number, and special character.";
    } elseif ($new_password !== $confirm_password) {
        $message = "Passwords do not match.";
    } else {
        // Get current hashed password from DB
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        if ($user && password_verify($new_password, $user['password'])) {
            $message = "New password cannot be the same as your old password.";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("UPDATE users SET password = ?, must_change_password = 0 WHERE id = ?");
            if ($stmt->execute([$hashed_password, $_SESSION['user_id']])) {

                // Redirect based on role
                if ($_SESSION['role'] === 'admin') {
                    header("Location: admin/dashboard.php");
                } elseif ($_SESSION['role'] === 'superadmin') {
                    header("Location: superadmin/dashboard.php");
                } else {
                    header("Location: customer/dashboard.php");
                }
                exit();
            } else {
                $message = "Failed to update password. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password | CCP</title>
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

        .logo { 
            font-size: 1.8rem; 
            font-weight: 800; 
            color: var(--secondary); 
            text-decoration: none; 
        }

        .logo span { color: var(--primary); }
        
        nav a { 
            text-decoration: none; 
            color: var(--text-muted); 
            margin-left: 30px; 
            font-weight: 700; 
            font-size: 14px; 
        }

        .main-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 60px 20px;
        }

        .change-card {
            background: var(--white);
            padding: 50px 40px;
            border-radius: 24px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.05);
            width: 100%;
            max-width: 420px;
            text-align: center;
            border: 1px solid #f1f5f9;
        }

        .card-logo { 
            font-size: 32px; 
            font-weight: 800; 
            margin-bottom: 10px; 
            color: var(--secondary); 
        }

        .card-logo span { color: var(--primary); }

        h3 { 
            font-size: 24px; 
            font-weight: 800; 
            margin-top: 0; 
            margin-bottom: 8px; 
            color: var(--secondary); 
        }

        .error-msg {
            background: #fee2e2;
            color: #ef4444;
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        input {
            width: 100%;
            padding: 14px;
            margin: 12px 0;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-family: inherit;
            font-size: 14px;
            outline: none;
            transition: 0.2s;
            box-sizing: border-box;
        }

        input:focus { 
            border-color: var(--primary); 
            box-shadow: 0 0 0 4px rgba(230, 126, 34, 0.1); 
        }

        .btn-change {
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

        .btn-change:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 10px 20px rgba(230, 126, 34, 0.2); 
            background: #d35400; 
        }

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
        </nav>
    </header>

    <main class="main-container">
        <div class="change-card">
            <div class="card-logo">CC<span>P</span></div>
            <h3>Change Password</h3>
            <p style="color: var(--text-muted); margin-bottom: 30px; font-size: 14px; font-weight: 500;">
                For security, please set a new password before continuing.
            </p>

            <?php if ($message): ?>
                <div class="error-msg"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="password" name="new_password" placeholder="New Password" required>
                <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
                
                <button type="submit" class="btn-change">Update Password</button>
            </form>
        </div>
    </main>

    <footer class="footer">
        &copy; <?php echo date('Y'); ?> CCP - Campus Canteen Pre-Order. All rights reserved.
    </footer>

</body>
</html>