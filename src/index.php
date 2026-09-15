<?php
session_start();
require_once 'config/db.php';

// Fetch ALL canteens for the landing page
$stmt = $pdo->query("SELECT * FROM canteens ORDER BY name ASC");
$canteens = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CCP | Campus Canteen Pre-Order</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { 
            --primary: #e67e22; 
            --secondary: #1a1a1a; 
            --text-main: #2d3436; 
            --text-muted: #636e72; 
            --white: #ffffff; 
            --bg-light: #f8fafc; 
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Plus Jakarta Sans', sans-serif; }
        html { scroll-behavior: smooth; }
        body { display: flex; flex-direction: column; min-height: 100vh; }

        /* Navbar */
        .navbar { 
            display: flex; justify-content: space-between; align-items: center; 
            padding: 20px 8%; background: rgba(255, 255, 255, 0.95); 
            backdrop-filter: blur(10px); box-shadow: 0 4px 20px rgba(0,0,0,0.03); 
            position: sticky; top: 0; z-index: 1000; 
        }
        .logo { font-size: 1.8rem; font-weight: 800; color: var(--secondary); text-decoration: none; }
        .logo span { color: var(--primary); }
        nav a { text-decoration: none; color: var(--text-muted); margin-left: 30px; font-weight: 700; font-size: 14px; transition: 0.3s; }
        nav a:hover { color: var(--primary); }
        .btn-cta { background: var(--primary); color: white !important; padding: 12px 28px; border-radius: 14px; transition: 0.3s; text-decoration: none; font-weight: 700; }

        /* Hero Section */
        .hero { 
            height: 85vh; background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.7)), 
            url('https://images.unsplash.com/photo-1543353071-873f17a7a088?auto=format&fit=crop&w=1600&q=80') center/cover no-repeat; 
            display: flex; align-items: center; justify-content: center; text-align: center; color: white; 
        }
        .hero-content h1 { font-size: 4.5rem; font-weight: 800; margin-bottom: 20px; line-height: 1.1; }
        .hero-content p { font-size: 1.4rem; margin-bottom: 40px; font-weight: 500; }

        /* Canteens Section */
        .canteens-container { padding: 100px 8%; background: var(--bg-light); }
        .section-title { text-align: center; margin-bottom: 60px; }
        .section-title h2 { font-size: 2.8rem; font-weight: 800; color: var(--secondary); }
        .canteen-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 40px; }
        .canteen-card { background: var(--white); border-radius: 28px; overflow: hidden; box-shadow: 0 15px 35px rgba(0,0,0,0.03); border: 1px solid #f1f5f9; transition: 0.3s; }
        .canteen-card:hover { transform: translateY(-10px); }
        .card-img { height: 240px; overflow: hidden; }
        .card-img img { width: 100%; height: 100%; object-fit: cover; }
        .card-info { padding: 35px; }
        .card-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 25px; border-top: 1px solid #f1f5f9; }
        .btn-view { background: var(--secondary); color: white; padding: 14px 24px; text-decoration: none; border-radius: 14px; font-weight: 700; transition: 0.3s; }

        /* Contact Section */
        .contact-section { padding: 100px 8%; background: var(--white); }
        .contact-container { display: grid; grid-template-columns: 1fr 1fr; gap: 80px; align-items: center; }
        .contact-info h2 { font-size: 2.5rem; font-weight: 800; color: var(--secondary); margin-bottom: 20px; }
        .contact-info p { color: var(--text-muted); line-height: 1.8; margin-bottom: 30px; font-weight: 500; }
        .contact-item { display: flex; align-items: center; margin-bottom: 20px; }
        .contact-icon { width: 50px; height: 50px; background: #fff7ed; color: var(--primary); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-right: 20px; font-size: 20px; }
        .contact-form { background: var(--bg-light); padding: 40px; border-radius: 24px; border: 1px solid #f1f5f9; }
        .contact-form input, .contact-form textarea { width: 100%; padding: 15px; margin-bottom: 15px; border: 1px solid #e2e8f0; border-radius: 12px; font-size: 14px; }
        .contact-form textarea { height: 120px; resize: none; }
        .btn-send { width: 100%; background: var(--secondary); color: white; border: none; padding: 16px; border-radius: 12px; font-weight: 700; cursor: pointer; transition: 0.3s; }
        .btn-send:hover { background: var(--primary); }

        /* Footer - Spacious & Rebranded */
        .footer { background: var(--secondary); color: white; padding: 120px 8% 40px; } /* Increased top padding */
        .footer-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 80px; margin-bottom: 100px; } /* Large gap and bottom margin */
        
        .footer-brand .logo { color: white; margin-bottom: 30px; display: block; font-size: 2.2rem; }
        .footer-brand p { color: #94a3b8; line-height: 2; font-size: 15px; max-width: 350px; }
        
        /* Quick Links Spacing Fix */
        .footer-links h4 { font-size: 18px; font-weight: 700; margin-bottom: 45px; color: var(--white); text-transform: uppercase; letter-spacing: 1.5px; } /* Increased margin below heading */
        .footer-links ul { list-style: none; }
        .footer-links ul li { margin-bottom: 22px; } /* Extra vertical space between link items */
        .footer-links ul li a { color: #94a3b8; text-decoration: none; font-size: 15px; transition: 0.3s; font-weight: 600; display: inline-block; }
        .footer-links ul li a:hover { color: var(--primary); transform: translateX(10px); } /* Slide-in effect on hover */
        
        .footer-bottom { border-top: 1px solid rgba(255,255,255,0.08); padding-top: 50px; text-align: center; } /* Spacing for bottom bar */
        .footer-bottom p { color: #64748b; font-size: 14px; font-weight: 600; }
    </style>
</head>
<body>

    <header class="navbar">
        <a href="index.php" class="logo">CC<span>P</span></a>
        <nav>
            <a href="index.php">Home</a>
            <a href="#canteens">Canteens</a>
            <a href="#contact">Contact</a>
            <?php if(isset($_SESSION['user_id'])): ?>
                <?php 
                    $dashboard_link = ($_SESSION['role'] == 'admin') ? 'admin/dashboard.php' : (($_SESSION['role'] == 'superadmin') ? 'superadmin/dashboard.php' : 'customer/dashboard.php');
                ?>
                <a href="<?php echo $dashboard_link; ?>">Dashboard</a>
                <a href="logout.php" class="btn-cta" style="background:#ef4444;">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="register.php" class="btn-cta">Join Now</a>
            <?php endif; ?>
        </nav>
    </header>

    <section class="hero">
        <div class="hero-content">
            <h1>Skip the<br>Canteen Queue</h1>
            <p>Order from your favorite campus spots at Charusat University.</p>
            <a href="#canteens" class="btn-cta" style="padding: 20px 50px; font-size: 1.1rem; font-weight: 800;">Get Started</a>
        </div>
    </section>

    <section id="canteens" class="canteens-container">
        <div class="section-title">
            <h2>Campus Canteens</h2>
            <p>Select a partner canteen to browse their menu</p>
        </div>
        <div class="canteen-grid">
            <?php foreach($canteens as $c): ?>
                <div class="canteen-card">
                    <div class="card-img">
                        <?php 
                            // CHECK FOR THE UPLOADED PROFILE IMAGE
                            if (!empty($c['image'])) {
                                $clean_path = 'assets/images/canteens/' . $c['image'];
                            } else {
                                $clean_path = 'assets/images/default.jpg';
                            }
                        ?>
                        <img src="<?php echo htmlspecialchars($clean_path); ?>" alt="<?php echo htmlspecialchars($c['name']); ?>" onerror="this.src='assets/images/default.jpg'">
                    </div>
                    <div class="card-info">
                        <span class="location">📍 <?php echo htmlspecialchars($c['location']); ?></span>
                        <h3><?php echo htmlspecialchars($c['name']); ?></h3>
                        <div class="card-footer">
                            <span style="color:#27ae60; font-weight:700; font-size:13px;">● Open Now</span>
                            <a href="customer/menu.php?canteen_id=<?php echo $c['id']; ?>" class="btn-view">Browse Menu</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section id="contact" class="contact-section">
        <div class="contact-container">
            <div class="contact-info">
                <h2>Get in Touch</h2>
                <p>Have a question or want to partner with us? Our team at Charusat is here to help you.</p>
                <div class="contact-item"><div class="contact-icon">📍</div><div><h4>Location</h4><p>Charusat University, Changa</p></div></div>
                <div class="contact-item"><div class="contact-icon">📧</div><div><h4>Email Us</h4><p>support@ccp.com</p></div></div>
            </div>
            <div class="contact-form">
                <form action="contact_process.php" method="POST">
                    <input type="text" name="name" placeholder="Your Name" required>
                    <input type="email" name="email" placeholder="Your Email" required>
                    <textarea name="message" placeholder="How can we help you?" required></textarea>
                    <button type="submit" class="btn-send">Send Message</button>
                </form>
            </div>
        </div>
    </section>

    <footer class="footer">
        <div class="footer-grid">
            <div class="footer-brand">
                <a href="#" class="logo">CC<span>P</span></a>
                <p>Revolutionizing the campus dining experience. Pre-order your meals, skip the long queues, and save time.</p>
            </div>
            
            <div class="footer-links">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="#canteens">Browse Canteens</a></li>
                    <li><a href="login.php">Partner Login</a></li>
                    <li><a href="register.php">Create Account</a></li>
                </ul>
            </div>

            <div class="footer-links">
                <h4>Contact & Support</h4>
                <ul>
                    <li><a href="#">📍 Charusat University, Changa</a></li>
                    <li><a href="mailto:support@ccp.com">📧 support@ccp.com</a></li>
                    <li><a href="#">📞 +91 81600 19020</a></li>
                    <li><a href="#">⚖️ Privacy Policy</a></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> CCP - Campus Canteen Pre-Order. Designed for Charusat Campus.</p>
        </div>
    </footer>

</body>
</html>