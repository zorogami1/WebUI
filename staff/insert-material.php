<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ===== FIXED: Check for user_id and staff role =====
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../login.php");
    exit();
}

require_once '../conn.php';
$msg = "";

// ===== FIXED: Define material types/categories =====
$material_types = [
        'Wood' => 'Wood',
        'Metal' => 'Metal',
        'Fabric' => 'Fabric',
        'Foam' => 'Foam',
        'Glass' => 'Glass',
        'Plastic' => 'Plastic',
        'Leather' => 'Leather',
        'Paint/Finish' => 'Paint/Finish',
        'Hardware' => 'Hardware (screws, hinges, etc.)',
        'Other' => 'Other'
];

// Fetch existing materials for dropdown
$existing_materials = [];
try {
    $existing_materials = $pdo->query("SELECT mid, mname, munit, mqty FROM materials ORDER BY mname ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $existing_materials = [];
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action_type = isset($_POST['action_type']) ? $_POST['action_type'] : 'new';

    if ($action_type === 'existing') {
        // ===== ADD TO EXISTING MATERIAL =====
        $mid = intval($_POST['existing_material']);
        $additional_qty = intval($_POST['additional_qty']);

        if ($mid <= 0 || $additional_qty <= 0) {
            $msg = "<div class='alert alert-danger'>⚠️ Please select a material and enter a valid quantity.</div>";
        } else {
            try {
                // Check if material exists
                $check_stmt = $pdo->prepare("SELECT mname FROM materials WHERE mid = ?");
                $check_stmt->execute([$mid]);
                $material = $check_stmt->fetch(PDO::FETCH_ASSOC);

                if ($material) {
                    // Update quantity
                    $stmt = $pdo->prepare("UPDATE materials SET mqty = mqty + ? WHERE mid = ?");
                    $stmt->execute([$additional_qty, $mid]);
                    $msg = "<div class='alert alert-success'>✅ Added $additional_qty units to '{$material['mname']}' successfully!</div>";
                } else {
                    $msg = "<div class='alert alert-danger'>⚠️ Material not found.</div>";
                }
            } catch (PDOException $e) {
                $msg = "<div class='alert alert-danger'>⚠️ Failed to update material: " . $e->getMessage() . "</div>";
            }
        }
    } else {
        // ===== ADD NEW MATERIAL =====
        $mname = trim($_POST['mname']);
        $mtype = trim($_POST['mtype']);
        $mqty = intval($_POST['mqty']);
        $munit = trim($_POST['munit']);

        if (empty($mname) || empty($mtype) || empty($munit)) {
            $msg = "<div class='alert alert-danger'>⚠️ Please fill in all required fields.</div>";
        } else {
            // Check if material already exists
            $check_stmt = $pdo->prepare("SELECT mid FROM materials WHERE mname = ?");
            $check_stmt->execute([$mname]);
            $existing = $check_stmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $msg = "<div class='alert alert-warning'>⚠️ Material '$mname' already exists. Use 'Add to Existing' option instead.</div>";
            } else {
                try {
                    $stmt = $pdo->prepare("INSERT INTO materials (mname, mtype, mqty, munit) VALUES (:mname, :mtype, :mqty, :munit)");
                    $stmt->execute([
                            'mname' => $mname,
                            'mtype' => $mtype,
                            'mqty' => $mqty,
                            'munit' => $munit
                    ]);
                    $msg = "<div class='alert alert-success'>✅ Material added successfully! (ID: " . $pdo->lastInsertId() . ")</div>";

                    // Refresh existing materials list
                    $existing_materials = $pdo->query("SELECT mid, mname, munit, mqty FROM materials ORDER BY mname ASC")->fetchAll(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    $msg = "<div class='alert alert-danger'>⚠️ Failed to add material: " . $e->getMessage() . "</div>";
                }
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
    <title>Insert Material - Premium Living</title>
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

        /* ===== CARD ===== */
        .card {
            background: white;
            border-radius: var(--radius-card);
            box-shadow: var(--shadow-soft);
            margin-bottom: 2rem;
            overflow: hidden;
            max-width: 650px;
            margin: 0 auto;
        }

        .card-header {
            padding: 1.2rem 2rem;
            border-bottom: 2px solid var(--accent-gold);
            background: var(--cream);
        }

        .card-header h2 {
            font-size: 1.3rem;
            color: var(--wood-dark);
            font-family: 'Playfair Display', serif;
            margin: 0;
        }

        .card-header h2 i {
            color: var(--accent-gold);
            margin-right: 0.5rem;
        }

        .card-header p {
            color: var(--gray-wood);
            font-size: 0.85rem;
            margin-top: 0.3rem;
        }

        .card-body {
            padding: 2rem;
        }

        /* ===== TAB SWITCHER ===== */
        .tab-switcher {
            display: flex;
            background: var(--wood-bg);
            border-radius: 0.8rem;
            padding: 0.3rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--input-border);
        }

        .tab-btn {
            flex: 1;
            padding: 0.6rem 1rem;
            border: none;
            border-radius: 0.6rem;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.85rem;
            font-family: 'Inter', sans-serif;
            background: transparent;
            color: var(--gray-wood);
            transition: all 0.3s;
        }

        .tab-btn.active {
            background: var(--accent-gold);
            color: var(--wood-dark);
            box-shadow: 0 2px 10px rgba(212, 163, 115, 0.3);
        }

        .tab-btn:hover:not(.active) {
            background: rgba(212, 163, 115, 0.1);
            color: var(--wood-dark);
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* ===== FORM ===== */
        .form-group {
            margin-bottom: 1.2rem;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: var(--wood-dark);
            margin-bottom: 0.3rem;
            font-size: 0.85rem;
        }

        .form-group label i {
            color: var(--accent-gold);
            margin-right: 0.4rem;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.7rem 1rem;
            border: 1.5px solid var(--input-border);
            border-radius: 0.8rem;
            background: #ffffff;
            color: var(--wood-dark);
            font-size: 0.9rem;
            font-family: 'Inter', sans-serif;
            outline: none;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: var(--accent-gold);
            box-shadow: 0 0 0 3px rgba(212, 163, 115, 0.15);
        }

        .form-group .helper-text {
            font-size: 0.75rem;
            color: var(--gray-wood);
            margin-top: 0.3rem;
        }

        /* ===== FORM ROW ===== */
        .form-row-two {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.2rem;
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

        .btn-success {
            background: #2a9d8f;
            color: white;
        }

        .btn-success:hover {
            background: #21867a;
            transform: translateY(-2px);
        }

        /* ===== ALERTS ===== */
        .alert {
            padding: 0.8rem 1rem;
            border-radius: 0.8rem;
            margin: 1.5rem 2rem 0;
            font-size: 0.9rem;
            text-align: center;
            font-weight: 500;
        }

        .alert-success {
            background: #e6f4ea;
            color: #2d6a4f;
            border: 1px solid #2d6a4f;
        }

        .alert-danger {
            background: #fde8e8;
            color: #9d6b53;
            border: 1px solid #9d6b53;
        }

        .alert-warning {
            background: #fef3e2;
            color: #8a5a2a;
            border: 1px solid #e9b35f;
        }

        /* ===== TYPE PREVIEW ===== */
        .type-preview {
            margin: 1rem 0;
            padding: 0.75rem;
            background: var(--wood-bg);
            border-radius: 8px;
            border: 1px solid rgba(139,94,60,0.1);
            display: none;
        }

        .type-preview.show {
            display: block;
        }

        /* ===== BADGES ===== */
        .type-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            margin: 0 4px 4px 0;
        }

        .type-wood { background: #d4a373; color: #3e2a21; }
        .type-metal { background: #b0b0b0; color: #1a1a1a; }
        .type-fabric { background: #c9b8a8; color: #3e2a21; }
        .type-foam { background: #f0e6d3; color: #3e2a21; }
        .type-glass { background: #c5d5e0; color: #1a3a4a; }
        .type-plastic { background: #d4d4d4; color: #1a1a1a; }
        .type-leather { background: #8b5e3c; color: #f5efe6; }
        .type-paint { background: #e8d5c4; color: #3e2a21; }
        .type-hardware { background: #a8a8a8; color: #1a1a1a; }
        .type-other { background: #e0d6cc; color: #3e2a21; }

        /* ===== QUICK REFERENCE ===== */
        .quick-ref {
            margin-top: 2rem;
            border-top: 1px solid var(--input-border);
            padding-top: 1.5rem;
        }

        .quick-ref h4 {
            color: var(--wood-dark);
            font-size: 0.95rem;
        }

        .quick-ref h4 i {
            color: var(--accent-gold);
            margin-right: 0.5rem;
        }

        .badge-container {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        /* ===== CURRENT STOCK DISPLAY ===== */
        .stock-display {
            padding: 0.5rem 0.8rem;
            background: var(--wood-bg);
            border-radius: 0.5rem;
            font-size: 0.8rem;
            color: var(--gray-wood);
            margin-top: 0.3rem;
        }

        .stock-display strong {
            color: var(--wood-dark);
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

            .card {
                margin: 0 0.5rem;
            }

            .card-header {
                padding: 1rem;
            }

            .card-header h2 {
                font-size: 1.1rem;
            }

            .card-body {
                padding: 1.5rem;
            }

            .form-row-two {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .alert {
                margin: 1rem 1rem 0;
            }

            .tab-switcher {
                flex-direction: column;
                gap: 0.3rem;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0 1rem;
            }

            .card-body {
                padding: 1rem;
            }

            .badge-container {
                gap: 0.3rem;
            }

            .type-badge {
                font-size: 0.7rem;
                padding: 2px 8px;
            }
        }
    </style>
</head>
<body>
<nav class="navbar">
    <div class="logo">
        <h1><a href="dashboard.php"><i class="fas fa-tree"></i> Staff Portal</a></h1>
    </div>
    <ul class="nav-links">
        <li><a href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a></li>
        <li><a href="insert-furniture.php"><i class="fas fa-plus-circle"></i> Insert Furniture</a></li>
        <li><a href="insert-material.php" class="active"><i class="fas fa-warehouse"></i> Insert Material</a></li>
        <li><a href="manage-orders.php"><i class="fas fa-clipboard-list"></i> Manage Orders</a></li>
        <li><a href="generate-report.php"><i class="fas fa-file-alt"></i> Generate Report</a></li>
        <li><a href="delete-furniture.php"><i class="fas fa-trash"></i> Delete Furniture</a></li>
        <li><a href="../index.php?action=logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</nav>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-warehouse"></i> Manage Materials</h2>
            <p>Add new material or increase quantity of existing materials</p>
        </div>

        <?php echo $msg; ?>

        <div class="card-body">
            <!-- ===== TAB SWITCHER ===== -->
            <div class="tab-switcher">
                <button class="tab-btn active" onclick="switchTab('new')">
                    <i class="fas fa-plus-circle"></i> New Material
                </button>
                <button class="tab-btn" onclick="switchTab('existing')">
                    <i class="fas fa-plus"></i> Add to Existing
                </button>
            </div>

            <!-- ===== TAB 1: ADD NEW MATERIAL ===== -->
            <div id="tab-new" class="tab-content active">
                <form action="insert-material.php" method="POST">
                    <input type="hidden" name="action_type" value="new">

                    <div class="form-group">
                        <label for="mname"><i class="fas fa-tag"></i> Material Name *</label>
                        <input type="text" id="mname" name="mname" required placeholder="Enter material name (e.g., Oak Wood Plank)">
                    </div>

                    <div class="form-group">
                        <label for="mtype"><i class="fas fa-category"></i> Material Type / Category *</label>
                        <select id="mtype" name="mtype" required style="width:100%;">
                            <option value="">-- Select Material Type --</option>
                            <?php foreach ($material_types as $key => $label): ?>
                                <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="helper-text">
                            <i class="fas fa-info-circle"></i> Select the category that best describes this material
                        </div>
                    </div>

                    <div class="form-row-two">
                        <div class="form-group">
                            <label for="mqty"><i class="fas fa-sort-numeric-up"></i> Quantity *</label>
                            <input type="number" id="mqty" name="mqty" required placeholder="Enter quantity" min="0" step="0.01">
                        </div>
                        <div class="form-group">
                            <label for="munit"><i class="fas fa-ruler"></i> Unit of Measurement *</label>
                            <select id="munit" name="munit" required style="width:100%;">
                                <option value="">-- Select Unit --</option>
                                <option value="pcs">Pieces (pcs)</option>
                                <option value="meter">Meters (m)</option>
                                <option value="centimeter">Centimeters (cm)</option>
                                <option value="kilogram">Kilograms (kg)</option>
                                <option value="gram">Grams (g)</option>
                                <option value="liter">Liters (L)</option>
                                <option value="milliliter">Milliliters (mL)</option>
                                <option value="square meter">Square Meters (m²)</option>
                                <option value="cubic meter">Cubic Meters (m³)</option>
                                <option value="roll">Rolls</option>
                                <option value="sheet">Sheets</option>
                                <option value="set">Sets</option>
                                <option value="box">Boxes</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="type-preview" id="typePreview">
                        <span style="font-weight: 600;">Selected Type:</span>
                        <span id="selectedTypeDisplay" style="font-weight: 400;"></span>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width:100%; margin-top: 0.5rem;">
                        <i class="fas fa-plus-circle"></i> Add New Material
                    </button>
                </form>
            </div>

            <!-- ===== TAB 2: ADD TO EXISTING MATERIAL ===== -->
            <div id="tab-existing" class="tab-content">
                <form action="insert-material.php" method="POST">
                    <input type="hidden" name="action_type" value="existing">

                    <div class="form-group">
                        <label for="existing_material"><i class="fas fa-box"></i> Select Material *</label>
                        <select id="existing_material" name="existing_material" required style="width:100%;" onchange="updateStockInfo()">
                            <option value="">-- Select Material --</option>
                            <?php foreach ($existing_materials as $m): ?>
                                <option value="<?php echo $m['mid']; ?>" data-mname="<?php echo htmlspecialchars($m['mname']); ?>" data-mqty="<?php echo $m['mqty']; ?>" data-munit="<?php echo $m['munit']; ?>">
                                    <?php echo htmlspecialchars($m['mname']); ?> (Current: <?php echo $m['mqty']; ?> <?php echo $m['munit']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="stock-display" id="stockDisplay">
                            <i class="fas fa-info-circle"></i> Select a material to see current stock
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="additional_qty"><i class="fas fa-plus"></i> Quantity to Add *</label>
                        <input type="number" id="additional_qty" name="additional_qty" required placeholder="Enter quantity to add" min="1">
                        <div class="helper-text">
                            <i class="fas fa-info-circle"></i> This quantity will be added to the current stock
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success" style="width:100%; margin-top: 0.5rem;">
                        <i class="fas fa-plus"></i> Add to Material
                    </button>
                </form>
            </div>

            <!-- ===== QUICK REFERENCE ===== -->
            <div class="quick-ref">
                <h4><i class="fas fa-list"></i> Common Material Types</h4>
                <div class="badge-container">
                    <span class="type-badge type-wood">🪵 Wood</span>
                    <span class="type-badge type-metal">⚙️ Metal</span>
                    <span class="type-badge type-fabric">🧵 Fabric</span>
                    <span class="type-badge type-foam">🛋️ Foam</span>
                    <span class="type-badge type-glass">🔍 Glass</span>
                    <span class="type-badge type-plastic">🧪 Plastic</span>
                    <span class="type-badge type-leather">👝 Leather</span>
                    <span class="type-badge type-paint">🎨 Paint/Finish</span>
                    <span class="type-badge type-hardware">🔩 Hardware</span>
                    <span class="type-badge type-other">📦 Other</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // ===== TAB SWITCHING =====
    function switchTab(tab) {
        // Update tab buttons
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(btn => {
            if (btn.textContent.toLowerCase().includes(tab === 'new' ? 'new material' : 'add to existing')) {
                btn.classList.add('active');
            }
        });

        // Update tab content
        document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
        document.getElementById('tab-' + tab).classList.add('active');
    }

    // ===== TYPE PREVIEW =====
    document.getElementById('mtype').addEventListener('change', function() {
        const preview = document.getElementById('typePreview');
        const display = document.getElementById('selectedTypeDisplay');
        const selectedOption = this.options[this.selectedIndex];

        if (this.value) {
            preview.classList.add('show');
            display.textContent = selectedOption.text;
        } else {
            preview.classList.remove('show');
        }
    });

    // ===== STOCK INFO DISPLAY =====
    function updateStockInfo() {
        const select = document.getElementById('existing_material');
        const display = document.getElementById('stockDisplay');
        const selectedOption = select.options[select.selectedIndex];

        if (select.value) {
            const mname = selectedOption.dataset.mname;
            const mqty = selectedOption.dataset.mqty;
            const munit = selectedOption.dataset.munit;
            display.innerHTML = `
                <i class="fas fa-box"></i>
                <strong>${mname}</strong>:
                Current stock is <strong>${mqty}</strong> ${munit}
            `;
            display.style.background = '#e6f4ea';
            display.style.color = '#2d6a4f';
        } else {
            display.innerHTML = `<i class="fas fa-info-circle"></i> Select a material to see current stock`;
            display.style.background = '';
            display.style.color = '';
        }
    }
</script>

<footer>
    <p>&copy; 2026 Premium Living Furniture Co. Ltd. All rights reserved.</p>
</footer>
</body>
</html>