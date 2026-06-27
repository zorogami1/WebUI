<?php
// Secure session layer initialization
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// SELF-CONTAINED LOGOUT ENGINE: Intercepts action tag without needing a separate file
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $_SESSION = array(); // Clear data array

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
        );
    }
    session_destroy(); // Terminate server tracking
    header("Location: index.php"); // Clean redirect to logged-out state
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Living Furniture | Artisanal Wood Furniture</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* ===== COMPLETE STYLES ===== */
        :root {
            --wood-dark: #3e2a21;
            --wood-medium: #5c3d2e;
            --wood-light: #8b5e3c;
            --wood-bg: #f5efe6;
            --cream: #fdf8f0;
            --accent-gold: #d4a373;
            --gray-wood: #a89f91;
            --shadow-soft: 0 8px 30px rgba(0,0,0,0.08);
            --shadow-warm: 0 12px 28px rgba(62, 42, 33, 0.12);
            --radius-card: 1.25rem;
            --radius-btn: 2rem;
            --input-border: #d4c4a8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--wood-bg);
            color: var(--wood-dark);
            line-height: 1.5;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }

        /* ===== NAVBAR ===== */
        .navbar {
            background: var(--wood-dark);
            padding: 0.8rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
            border-bottom: 3px solid var(--accent-gold);
            margin-bottom: 2rem;
        }

        .logo h1 {
            font-size: 1.3rem;
            margin: 0;
        }

        .logo a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logo a i {
            color: var(--accent-gold);
            font-size: 1.2rem;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 0.4rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .nav-links a {
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            padding: 0.3rem 0.6rem;
            border-radius: 6px;
            transition: all 0.3s;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .nav-links a:hover {
            background: rgba(212, 163, 115, 0.2);
            color: var(--accent-gold);
        }

        .nav-links a.active {
            background: rgba(212, 163, 115, 0.15);
            color: var(--accent-gold);
        }

        /* ===== HERO SECTION ===== */
        .hero-section {
            background: linear-gradient(135deg, #3e2a21 0%, #5c3d2e 100%);
            border-radius: var(--radius-card);
            padding: 3rem 2rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 80%;
            height: 200%;
            background: radial-gradient(circle, rgba(212,163,115,0.15) 0%, transparent 70%);
            transform: rotate(15deg);
            pointer-events: none;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 600px;
        }

        .hero-badge {
            display: inline-block;
            background: rgba(212,163,115,0.2);
            backdrop-filter: blur(4px);
            padding: 0.4rem 1rem;
            border-radius: 2rem;
            font-size: 0.8rem;
            color: var(--accent-gold);
            margin-bottom: 1rem;
            font-weight: 500;
        }

        .hero-title {
            font-size: 3.2rem;
            font-weight: 700;
            color: white;
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .hero-title span {
            color: var(--accent-gold);
            font-family: 'Playfair Display', serif;
        }

        .hero-desc {
            color: rgba(255,255,255,0.85);
            font-size: 1.1rem;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        /* ===== BUTTONS ===== */
        .btn {
            display: inline-block;
            padding: 0.6rem 1.5rem;
            border: none;
            border-radius: var(--radius-btn);
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            text-align: center;
            font-family: 'Inter', sans-serif;
        }

        .btn-primary {
            background: var(--accent-gold);
            color: var(--wood-dark);
        }

        .btn-primary:hover {
            background: #c49363;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(212, 163, 115, 0.4);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
        }

        /* ===== FEATURE GRID ===== */
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 2rem;
            margin: 3rem 0;
        }

        .feature-card {
            background: var(--cream);
            padding: 1.8rem;
            border-radius: var(--radius-card);
            text-align: center;
            transition: all 0.3s;
            border: 1px solid rgba(139,94,60,0.1);
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-warm);
            border-color: var(--accent-gold);
        }

        .feature-icon {
            font-size: 2.8rem;
            color: var(--wood-light);
            margin-bottom: 1rem;
        }

        .feature-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--wood-dark);
            margin-bottom: 0.5rem;
        }

        .feature-card p {
            color: var(--wood-light);
            font-size: 0.9rem;
        }

        /* ===== SECTION HEADER ===== */
        .section-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .section-header h2 {
            font-size: 2rem;
            color: var(--wood-dark);
            margin-bottom: 0.5rem;
        }

        .section-header h2 i {
            color: var(--accent-gold);
            margin-right: 0.5rem;
        }

        .section-header p {
            color: var(--wood-light);
            max-width: 600px;
            margin: 0 auto;
        }

        /* ===== PRODUCT CARDS ===== */
        .featured-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .product-card {
            background: white;
            border-radius: var(--radius-card);
            overflow: hidden;
            box-shadow: var(--shadow-soft);
            transition: all 0.3s ease;
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-warm);
        }

        .product-image {
            background: linear-gradient(145deg, #e8dccc, #d4c4a8);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 220px;
            overflow: hidden;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-image .fallback-icon {
            font-size: 4rem;
            color: var(--wood-medium);
            opacity: 0.6;
        }

        .product-info {
            padding: 1.5rem;
        }

        .product-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--wood-dark);
            margin-bottom: 0.3rem;
        }

        .product-price {
            color: var(--accent-gold);
            font-size: 1.4rem;
            font-weight: 700;
            margin: 0.3rem 0;
        }

        .product-desc {
            color: var(--wood-light);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .product-card .btn {
            width: 100%;
        }

        /* ===== TESTIMONIALS ===== */
        .testimonial-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .testimonial-card {
            background: var(--cream);
            padding: 1.8rem;
            border-radius: var(--radius-card);
            text-align: center;
            border: 1px solid rgba(139,94,60,0.1);
            transition: all 0.3s;
        }

        .testimonial-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-warm);
        }

        .testimonial-card .fa-star {
            margin: 0 1px;
        }

        .testimonial-text {
            font-style: italic;
            color: var(--wood-dark);
            margin: 1rem 0;
            line-height: 1.6;
        }

        .testimonial-author {
            font-weight: 700;
            color: var(--wood-light);
        }

        /* ===== FOOTER ===== */
        footer {
            background: var(--wood-dark);
            color: rgba(255,255,255,0.7);
            text-align: center;
            padding: 1.5rem;
            margin-top: 2rem;
            border-top: 3px solid var(--accent-gold);
        }

        footer i {
            color: var(--accent-gold);
            margin-right: 0.5rem;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                text-align: center;
                padding: 0.8rem 1rem;
            }

            .nav-links {
                justify-content: center;
                gap: 0.3rem;
            }

            .nav-links a {
                font-size: 0.75rem;
                padding: 0.2rem 0.5rem;
            }

            .hero-title {
                font-size: 2.2rem;
            }

            .hero-section {
                padding: 2rem 1.5rem;
            }

            .feature-grid {
                gap: 1rem;
            }

            .featured-grid {
                grid-template-columns: 1fr;
                max-width: 400px;
                margin-left: auto;
                margin-right: auto;
            }

            .testimonial-grid {
                grid-template-columns: 1fr;
                max-width: 400px;
                margin-left: auto;
                margin-right: auto;
            }

            .hero-buttons {
                flex-direction: column;
                align-items: stretch;
            }

            .hero-buttons .btn {
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0 1rem;
            }

            .hero-title {
                font-size: 1.8rem;
            }

            .hero-desc {
                font-size: 1rem;
            }

            .section-header h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
<nav class="navbar">
    <div class="logo">
        <h1><a href="index.php"><i class="fas fa-tree"></i> Premium Living</a></h1>
    </div>
    <ul class="nav-links">
        <li><a href="index.php" class="active"><i class="fas fa-home"></i> Home</a></li>

        <?php if(isset($_SESSION['user_id'])): ?>
            <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'staff'): ?>
                <li><a href="staff/dashboard.php"><i class="fas fa-desktop"></i> Staff Dashboard</a></li>
            <?php else: ?>
                <li><a href="customer/dashboard.php"><i class="fas fa-user-circle"></i> My Account</a></li>
            <?php endif; ?>
            <li><a href="index.php?action=logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        <?php else: ?>
            <li><a href="register.php"><i class="fas fa-user-plus"></i> Register</a></li>
            <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Portal Login</a></li>
        <?php endif; ?>
    </ul>
</nav>

<main>
    <div class="container">
        <div class="hero-section">
            <div class="hero-content">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <span class="hero-badge"><i class="fas fa-user-check"></i> Welcome Back</span>
                    <h1 class="hero-title">Your Premium <span>Living Spaces</span><br>Await</h1>
                    <p class="hero-desc">Explore the catalog collection below or jump straight into your personalized profile dashboard system to track your orders.</p>
                    <div class="hero-buttons">
                        <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'staff'): ?>
                            <a href="staff/dashboard.php" class="btn btn-primary"><i class="fas fa-desktop"></i> Go to Staff Dashboard</a>
                        <?php else: ?>
                            <a href="customer/dashboard.php" class="btn btn-primary"><i class="fas fa-user-circle"></i> View My Account</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <span class="hero-badge"><i class="fas fa-leaf"></i> Since 2004</span>
                    <h1 class="hero-title">Handcrafted <span>Wooden Furniture</span><br>For Generations</h1>
                    <p class="hero-desc">Every piece tells a story. Sustainably sourced solid wood, traditional joinery, and timeless designs that bring warmth to your home.</p>
                    <div class="hero-buttons">
                        <a href="register.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> Join Us / Register</a>
                        <a href="login.php" class="btn btn-secondary"><i class="fas fa-sign-in-alt"></i> Member Login</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="feature-grid">
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-tree"></i></div>
                <div class="feature-title">Solid Hardwood</div>
                <p>Premium oak, walnut, and teak — sustainably harvested</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-hand-sparkles"></i></div>
                <div class="feature-title">Handcrafted Quality</div>
                <p>Traditional joinery, no particle board, heirloom durability</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-truck-fast"></i></div>
                <div class="feature-title">White Glove Delivery</div>
                <p>Free assembly & placement within 50km</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fas fa-leaf"></i></div>
                <div class="feature-title">Eco-Friendly</div>
                <p>Low-VOC finishes, carbon-neutral shipping</p>
            </div>
        </div>

        <div class="section-header">
            <h2><i class="fas fa-crown"></i> Bestsellers Collection</h2>
            <p>Our most loved pieces — crafted with passion and precision</p>
        </div>
        <div class="featured-grid">
            <div class="product-card">
                <div class="product-image">
                    <img src="images/2.png" alt="Oak Dining Chair" onerror="this.parentElement.innerHTML='<i class=\'fas fa-chair fallback-icon\'></i>'">
                </div>
                <div class="product-info">
                    <div class="product-title">Montego Oak Chair</div>
                    <div class="product-price">$450</div>
                    <div class="product-desc">Hand-carved solid oak, natural finish</div>
                    <button class="btn btn-primary" onclick="redirectToOrder()"><i class="fas fa-cart-plus"></i> Order Now</button>
                </div>
            </div>
            <div class="product-card">
                <div class="product-image">
                    <img src="images/3.png" alt="Large Dining Table" onerror="this.parentElement.innerHTML='<i class=\'fas fa-table fallback-icon\'></i>'">
                </div>
                <div class="product-info">
                    <div class="product-title">Harvest Dining Table</div>
                    <div class="product-price">$2,500</div>
                    <div class="product-desc">Seats 8, reclaimed pine top</div>
                    <button class="btn btn-primary" onclick="redirectToOrder()"><i class="fas fa-cart-plus"></i> Order Now</button>
                </div>
            </div>
            <div class="product-card">
                <div class="product-image">
                    <img src="images/1.png" alt="Fabric Sofa" onerror="this.parentElement.innerHTML='<i class=\'fas fa-couch fallback-icon\'></i>'">
                </div>
                <div class="product-info">
                    <div class="product-title">Willow 3-Seat Sofa</div>
                    <div class="product-price">$3,800</div>
                    <div class="product-desc">Linen blend, solid wood frame</div>
                    <button class="btn btn-primary" onclick="redirectToOrder()"><i class="fas fa-cart-plus"></i> Order Now</button>
                </div>
            </div>
        </div>

        <div class="section-header">
            <h2><i class="fas fa-quote-left"></i> From Our Customers</h2>
            <p>What furniture lovers say about Premium Living Furniture</p>
        </div>
        <div class="testimonial-grid">
            <div class="testimonial-card">
                <div>
                    <i class="fas fa-star" style="color: var(--accent-gold);"></i>
                    <i class="fas fa-star" style="color: var(--accent-gold);"></i>
                    <i class="fas fa-star" style="color: var(--accent-gold);"></i>
                    <i class="fas fa-star" style="color: var(--accent-gold);"></i>
                    <i class="fas fa-star" style="color: var(--accent-gold);"></i>
                </div>
                <p class="testimonial-text">"The oak dining table transformed our home. Solid, beautiful, and built to last. Delivery was seamless!"</p>
                <p class="testimonial-author">— The Johnson Family</p>
            </div>
            <div class="testimonial-card">
                <div>
                    <i class="fas fa-star" style="color: var(--accent-gold);"></i>
                    <i class="fas fa-star" style="color: var(--accent-gold);"></i>
                    <i class="fas fa-star" style="color: var(--accent-gold);"></i>
                    <i class="fas fa-star" style="color: var(--accent-gold);"></i>
                    <i class="fas fa-star" style="color: var(--accent-gold);"></i>
                </div>
                <p class="testimonial-text">"Customer service went above and beyond. The walnut bookshelf is a statement piece in our study."</p>
                <p class="testimonial-author">— Sarah M., Interior Designer</p>
            </div>
            <div class="testimonial-card">
                <div>
                    <i class="fas fa-star" style="color: var(--accent-gold);"></i>
                    <i class="fas fa-star" style="color: var(--accent-gold);"></i>
                    <i class="fas fa-star" style="color: var(--accent-gold);"></i>
                    <i class="fas fa-star" style="color: var(--accent-gold);"></i>
                    <i class="fas fa-star" style="color: var(--accent-gold);"></i>
                </div>
                <p class="testimonial-text">"Sustainable luxury! The bed frame is rock solid and the wood grain is stunning."</p>
                <p class="testimonial-author">— David K.</p>
            </div>
        </div>
    </div>
</main>

<footer>
    <p><i class="fas fa-tree"></i> 2026 Premium Living Furniture | Sustainably Crafted in Small Batches</p>
</footer>

<script>
    function redirectToOrder() {
        // Check if user is logged in
        <?php if(isset($_SESSION['user_id'])): ?>
        // If logged in, go directly to make-order.php
        window.location.href = 'customer/make-order.php';
        <?php else: ?>
        // If not logged in, save the redirect and go to login
        sessionStorage.setItem('redirectAfterLogin', 'customer/make-order.php');
        window.location.href = 'login.php';
        <?php endif; ?>
    }

    document.querySelectorAll('.btn-primary, .btn-secondary').forEach(btn => {
        btn.addEventListener('mouseenter', function() { this.style.transition = 'all 0.2s'; });
    });
</script>
</body>
</html>