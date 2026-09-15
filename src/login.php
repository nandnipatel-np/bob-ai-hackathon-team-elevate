<?php
require_once 'config/db.php';
session_start();

$error = '';

// Catch redirect errors from other pages
if (isset($_GET['error']) && $_GET['error'] === 'account_disabled') {
    $error = "Your account has been disabled or deleted by an administrator.";
} elseif (isset($_GET['error']) && $_GET['error'] === 'unauthorized') {
    $error = "You are not authorized to access that page.";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim(filter_var($_POST['email'], FILTER_SANITIZE_EMAIL));
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $storedPassword = $user['password'] ?? '';

                // Support BOTH:
                // 1) hashed passwords (new users)
                // 2) plain passwords (old admin/superadmin)
                $isValidPassword = false;

                if (!empty($storedPassword)) {
                    if (password_verify($password, $storedPassword)) {
                        $isValidPassword = true;
                    } elseif ($password === $storedPassword) {
                        $isValidPassword = true;

                        // Optional auto-upgrade plain password to hash
                        $newHash = password_hash($password, PASSWORD_DEFAULT);
                        $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                        $updateStmt->execute([$newHash, $user['id']]);
                    }
                }

                if ($isValidPassword) {

                    // FIX: Check if user is active securely.
                    // Handles spaces, boolean '1', and empty '' defaults from the database.
                    $userStatus = isset($user['status']) ? strtolower(trim((string)$user['status'])) : 'active';

                    if (!in_array($userStatus, ['active', '1', ''])) {
                        $error = "Your account has been deactivated. Please contact support.";
                    } else {

                        // Secure session
                        session_regenerate_id(true);

                        // Store session
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['name'] = $user['name'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['customer_id'] = $user['customer_id'] ?? null;

                        if ($user['role'] === 'admin') {
                            $_SESSION['canteen_id'] = $user['canteen_id'] ?? null;
                        }

                        // Force password change after reset
                        if (isset($user['must_change_password']) && $user['must_change_password'] == 1) {
                            header("Location: change_password.php");
                            exit();
                        }

                        // Role-based redirect
                        if ($user['role'] === 'admin') {
                            header("Location: admin/dashboard.php");
                        } elseif ($user['role'] === 'superadmin') {
                            header("Location: superadmin/dashboard.php");
                        } else {
                            header("Location: customer/dashboard.php");
                        }
                        exit();
                    }

                } else {
                    $error = "Invalid email or password.";
                }
            } else {
                $error = "Invalid email or password.";
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
    <title>Login | CCP</title>
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

        .logo { font-size: 1.8rem; font-weight: 800; color: var(--secondary); text-decoration: none; letter-spacing: 0px; }
        .logo span { color: var(--primary); }
        
        nav a { text-decoration: none; color: var(--text-muted); margin-left: 30px; font-weight: 700; font-size: 14px; transition: 0.3s; }
        nav a:hover { color: var(--primary); }

        .main-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 60px 20px;
        }

        .login-card {
            background: var(--white);
            padding: 50px 40px;
            border-radius: 24px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.05);
            width: 100%;
            max-width: 420px;
            text-align: center;
            border: 1px solid #f1f5f9;
        }

        .card-logo { font-size: 32px; font-weight: 800; margin-bottom: 10px; letter-spacing: 0px; color: var(--secondary); }
        .card-logo span { color: var(--primary); }

        h3 { font-size: 24px; font-weight: 800; margin-top: 0; margin-bottom: 8px; color: var(--secondary); }
        
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

        input:focus { border-color: var(--primary); box-shadow: 0 0 0 4px rgba(230, 126, 34, 0.1); }

        .btn-login {
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

        .btn-login:hover { transform: translateY(-2px); box-shadow: 0 10px 20px rgba(230, 126, 34, 0.2); background: #d35400; }

        .footer-links { margin-top: 25px; font-size: 14px; color: var(--text-muted); font-weight: 600; }
        .footer-links a { color: var(--primary); text-decoration: none; font-weight: 700; }
        .footer-links a:hover { text-decoration: underline; }

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
            <a href="register.php">Join Now</a>
        </nav>
    </header>

    <main class="main-container">
        <div class="login-card">
            <div class="card-logo">CC<span>P</span></div>
            <h3>Welcome Back</h3>
            <p style="color: var(--text-muted); margin-bottom: 30px; font-size: 14px; font-weight: 500;">Please log in to manage your canteen orders</p>

            <?php if ($error): ?>
                <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <input type="email" name="email" placeholder="Email Address" required>
                <input type="password" name="password" placeholder="Password" required>
                
                <button type="submit" class="btn-login">Login to Dashboard</button>

                <div class="footer-links">
                    Don't have an account? <a href="register.php">Register Now</a><br>
                    <div style="margin-top: 10px;">
                        <a href="forgot_password.php">Forgot Password?</a>
                    </div>
                </div>
            </form>
        </div>
    </main>

    <footer class="footer">
        &copy; <?php echo date('Y'); ?> CCP - Campus Canteen Pre-Order. All rights reserved.
    </footer>

</body>
</html>