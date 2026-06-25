<?php
// Start a session to track if a user logs in later
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Living Furniture | Artisanal Wood Furniture</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        /* Additional layout enhancements for index only */
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
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
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
        .feature-card p {
            color: var(--wood-light);
            font-size: 0.9rem;
        }
        .feature-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--wood-dark);
            margin-bottom: 0.5rem;
        }
        .section-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        .section-header h2 {
            font-size: 2rem;
            color: var(--wood-dark);
            margin-bottom: 0.5rem;
        }
        .section-header p {
            color: var(--wood-light);
            max-width: 600px;
            margin: 0 auto;
        }
        .featured-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        .testimonial-card {
            background: var(--cream);
            padding: 1.8rem;
            border-radius: var(--radius-card);
            text-align: center;
            border: 1px solid rgba(139,94,60,0.1);
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
        .product-image {
            background: linear-gradient(145deg, #e8dccc, #d4c4a8);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 200px;
        }
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .product-image .fallback-icon {
            font-size: 4rem;
            color: var(--wood-medium);
        }
        @media (max-width: 768px) {
            .hero-title { font-size: 2.2rem; }
            .hero-section { padding: 2rem 1.5rem; }
            .feature-grid { gap: 1rem; }
        }
    </style>
</head>
<body>
<nav class="navbar">
    <div class="logo">
        <h1><a href="index.php"><i class="fas fa-tree"></i> Premium Living Furniture</a></h1>
    </div>
    <ul class="nav-links">
        <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>

        <?php if(isset($_SESSION['user_id'])): ?>
            <?php if($_SESSION['role'] === 'staff'): ?>
                <li><a href="staff/dashboard.html"><i class="fas fa-desktop"></i> Staff Dashboard</a></li>
            <?php else: ?>
                <li><a href="customer/dashboard.html"><i class="fas fa-user-circle"></i> My Account</a></li>
            <?php endif; ?>
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        <?php else: ?>
            <li><a href="customer/login.html"><i class="fas fa-user"></i> Customer Portal</a></li>
            <li><a href="staff/login.html"><i class="fas fa-briefcase"></i> Staff Portal</a></li>
        <?php endif; ?>
    </ul>
</nav>

<main>
    <div class="container">
        <div class="hero-section">
            <div class="hero-content">
                <span class="hero-badge"><i class="fas fa-leaf"></i> Since 2004</span>
                <h1 class="hero-title">Handcrafted <span>Wooden Furniture</span><br>For Generations</h1>
                <p class="hero-desc">Every piece tells a story. Sustainably sourced solid wood, traditional joinery, and timeless designs that bring warmth to your home.</p>
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <a href="staff/register.html" class="btn btn-primary" style="background: var(--accent-gold); color: var(--wood-dark);"><i class="fas fa-user-plus"></i> Join Us / Register</a>
                    <?php if(!isset($_SESSION['user_id'])): ?>
                        <a href="customer/login.html" class="btn btn-secondary" style="background: rgba(255,255,255,0.15); color: white;"><i class="fas fa-sign-in-alt"></i> Customer Login</a>
                    <?php endif; ?>
                </div>
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
                    <button class="btn btn-primary" style="width: 100%; margin-top: 0.5rem;" onclick="redirectToOrder()"><i class="fas fa-cart-plus"></i> Order Now</button>
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
                    <button class="btn btn-primary" style="width: 100%; margin-top: 0.5rem;" onclick="redirectToOrder()"><i class="fas fa-cart-plus"></i> Order Now</button>
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
                    <button class="btn btn-primary" style="width: 100%; margin-top: 0.5rem;" onclick="redirectToOrder()"><i class="fas fa-cart-plus"></i> Order Now</button>
                </div>
            </div>
        </div>

        <div class="section-header">
            <h2><i class="fas fa-quote-left"></i> From Our Customers</h2>
            <p>What furniture lovers say about Premium Living Furniture</p>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-bottom: 2rem;">
            <div class="testimonial-card">
                <i class="fas fa-star" style="color: var(--accent-gold);"></i><i class="fas fa-star" style="color: var(--accent-gold);"></i><i class="fas fa-star" style="color: var(--accent-gold);"></i><i class="fas fa-star" style="color: var(--accent-gold);"></i><i class="fas fa-star" style="color: var(--accent-gold);"></i>
                <p class="testimonial-text">"The oak dining table transformed our home. Solid, beautiful, and built to last. Delivery was seamless!"</p>
                <p class="testimonial-author">— The Johnson Family</p>
            </div>
            <div class="testimonial-card">
                <i class="fas fa-star" style="color: var(--accent-gold);"></i><i class="fas fa-star" style="color: var(--accent-gold);"></i><i class="fas fa-star" style="color: var(--accent-gold);"></i><i class="fas fa-star" style="color: var(--accent-gold);"></i><i class="fas fa-star" style="color: var(--accent-gold);"></i>
                <p class="testimonial-text">"Customer service went above and beyond. The walnut bookshelf is a statement piece in our study."</p>
                <p class="testimonial-author">— Sarah M., Interior Designer</p>
            </div>
            <div class="testimonial-card">
                <i class="fas fa-star" style="color: var(--accent-gold);"></i><i class="fas fa-star" style="color: var(--accent-gold);"></i><i class="fas fa-star" style="color: var(--accent-gold);"></i><i class="fas fa-star" style="color: var(--accent-gold);"></i><i class="fas fa-star" style="color: var(--accent-gold);"></i>
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
        // Maps nicely to customer/make-order.html found in your directory
        sessionStorage.setItem('redirectAfterLogin', 'customer/make-order.html');
        window.location.href = 'customer/login.html';
    }

    document.querySelectorAll('.btn-primary, .btn-secondary').forEach(btn => {
        btn.addEventListener('mouseenter', function() { this.style.transition = 'all 0.2s'; });
    });
</script>
</body>
</html>