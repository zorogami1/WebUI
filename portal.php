<?php
// Clear any existing sessions
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Destroy any existing session to start fresh
session_destroy();
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Living - Portal Selection</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #2d1f16 0%, #4a3222 50%, #3e2a21 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image:
                    radial-gradient(circle at 20% 80%, rgba(212, 163, 115, 0.05) 0%, transparent 50%),
                    radial-gradient(circle at 80% 20%, rgba(212, 163, 115, 0.08) 0%, transparent 50%);
            pointer-events: none;
        }

        .portal-container {
            max-width: 1100px;
            width: 100%;
            position: relative;
            z-index: 1;
        }

        .brand-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .brand-header .logo-icon {
            font-size: 3.5rem;
            color: #d4a373;
            margin-bottom: 0.5rem;
            display: block;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .brand-header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.8rem;
            font-weight: 700;
            color: #fdf8f0;
            letter-spacing: 0.02em;
        }

        .brand-header h1 span {
            color: #d4a373;
        }

        .brand-header p {
            color: rgba(255, 255, 255, 0.6);
            font-size: 1.1rem;
            margin-top: 0.5rem;
            letter-spacing: 0.05em;
        }

        .brand-header .divider {
            width: 80px;
            height: 3px;
            background: linear-gradient(90deg, transparent, #d4a373, transparent);
            margin: 1rem auto;
            border-radius: 2px;
        }

        .portal-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 2.5rem;
            margin-top: 2rem;
        }

        .portal-card {
            border-radius: 1.5rem;
            padding: 2.5rem 2rem;
            text-align: center;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .portal-card:hover {
            transform: translateY(-8px) scale(1.01);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .portal-card:active {
            transform: scale(0.98);
        }

        /* Staff Card */
        .portal-card.staff {
            background: linear-gradient(145deg, rgba(62, 42, 33, 0.95), rgba(92, 61, 46, 0.95));
            border-color: rgba(212, 163, 115, 0.2);
        }

        .portal-card.staff:hover {
            border-color: #d4a373;
            box-shadow: 0 20px 60px rgba(212, 163, 115, 0.15);
        }

        .portal-card.staff .card-icon {
            background: rgba(212, 163, 115, 0.15);
            color: #d4a373;
        }

        .portal-card.staff .card-title {
            color: #fdf8f0;
        }

        .portal-card.staff .card-desc {
            color: rgba(255, 255, 255, 0.6);
        }

        .portal-card.staff .card-features {
            color: rgba(255, 255, 255, 0.7);
        }

        .portal-card.staff .card-features i {
            color: #d4a373;
        }

        .portal-card.staff .btn-login {
            background: #d4a373;
            color: #3e2a21;
        }

        .portal-card.staff .btn-login:hover {
            background: #e6ccb2;
            transform: translateY(-2px);
        }

        /* Customer Card */
        .portal-card.customer {
            background: linear-gradient(145deg, rgba(253, 248, 240, 0.98), rgba(245, 239, 230, 0.98));
            border-color: rgba(139, 94, 60, 0.15);
        }

        .portal-card.customer:hover {
            border-color: #8b5e3c;
            box-shadow: 0 20px 60px rgba(139, 94, 60, 0.2);
        }

        .portal-card.customer .card-icon {
            background: rgba(139, 94, 60, 0.1);
            color: #8b5e3c;
        }

        .portal-card.customer .card-title {
            color: #3e2a21;
        }

        .portal-card.customer .card-desc {
            color: rgba(62, 42, 33, 0.6);
        }

        .portal-card.customer .card-features {
            color: rgba(62, 42, 33, 0.7);
        }

        .portal-card.customer .card-features i {
            color: #8b5e3c;
        }

        .portal-card.customer .btn-login {
            background: #8b5e3c;
            color: #fdf8f0;
        }

        .portal-card.customer .btn-login:hover {
            background: #5c3d2e;
            transform: translateY(-2px);
        }

        .card-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.2rem;
            font-size: 2rem;
            transition: all 0.3s;
        }

        .portal-card:hover .card-icon {
            transform: scale(1.1) rotate(-5deg);
        }

        .card-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .card-desc {
            font-size: 0.95rem;
            margin-bottom: 1.5rem;
        }

        .card-features {
            list-style: none;
            padding: 0;
            margin-bottom: 1.8rem;
            text-align: left;
            font-size: 0.9rem;
        }

        .card-features li {
            padding: 0.4rem 0;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .card-features li i {
            width: 20px;
            font-size: 0.9rem;
        }

        .btn-login {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.8rem 2.5rem;
            border-radius: 2rem;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            text-decoration: none;
            width: 100%;
            justify-content: center;
        }

        .btn-login i {
            font-size: 1.1rem;
        }

        .portal-footer {
            text-align: center;
            margin-top: 3rem;
            color: rgba(255, 255, 255, 0.3);
            font-size: 0.85rem;
        }

        .portal-footer span {
            color: rgba(255, 255, 255, 0.5);
        }

        @media (max-width: 768px) {
            .brand-header h1 {
                font-size: 2rem;
            }
            .brand-header .logo-icon {
                font-size: 2.5rem;
            }
            .portal-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            .portal-card {
                padding: 2rem 1.5rem;
            }
            body {
                padding: 1rem;
            }
        }

        @media (max-width: 480px) {
            .brand-header h1 {
                font-size: 1.5rem;
            }
            .card-title {
                font-size: 1.4rem;
            }
            .portal-card {
                padding: 1.5rem 1rem;
            }
        }
    </style>
</head>
<body>

<div class="portal-container">
    <div class="brand-header">
        <i class="fas fa-tree logo-icon"></i>
        <h1>Premium <span>Living</span></h1>
        <div class="divider"></div>
        <p>Artisanal Furniture • Since 2004</p>
        <p style="font-size: 0.9rem; margin-top: 0.3rem; color: rgba(255,255,255,0.4);">
            <i class="fas fa-shield-alt"></i> Secure Portal Access
        </p>
    </div>

    <div class="portal-grid">
        <!-- Customer Portal -->
        <a href="customer/login.php" class="portal-card customer">
            <div class="card-icon">
                <i class="fas fa-user-circle"></i>
            </div>
            <h2 class="card-title">Customer Portal</h2>
            <p class="card-desc">Browse, order, and track your furniture</p>
            <ul class="card-features">
                <li><i class="fas fa-shopping-cart"></i> Browse furniture collection</li>
                <li><i class="fas fa-clipboard-list"></i> Place and track orders</li>
                <li><i class="fas fa-user-edit"></i> Manage your profile</li>
                <li><i class="fas fa-history"></i> View order history</li>
            </ul>
            <button class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Sign In as Customer
            </button>
        </a>

        <!-- Staff Portal -->
        <a href="staff/login.php" class="portal-card staff">
            <div class="card-icon">
                <i class="fas fa-user-tie"></i>
            </div>
            <h2 class="card-title">Staff Portal</h2>
            <p class="card-desc">Manage inventory, orders, and reports</p>
            <ul class="card-features">
                <li><i class="fas fa-plus-circle"></i> Add furniture products</li>
                <li><i class="fas fa-boxes"></i> Manage materials inventory</li>
                <li><i class="fas fa-clipboard-check"></i> Process customer orders</li>
                <li><i class="fas fa-chart-bar"></i> Generate sales reports</li>
            </ul>
            <button class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Sign In as Staff
            </button>
        </a>
    </div>

    <div class="portal-footer">
        <p>
            <i class="fas fa-lock" style="margin-right: 0.3rem;"></i>
            <span>Secure login portal for Premium Living Furniture Management System</span>
        </p>
        <p style="margin-top: 0.3rem; font-size: 0.75rem;">
            &copy; <?php echo date('Y'); ?> Premium Living Furniture Co. Ltd. All rights reserved.
        </p>
    </div>
</div>

</body>
</html>