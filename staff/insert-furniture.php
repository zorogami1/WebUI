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

// Fetch materials for dropdown
try {
    $materials_list = $pdo->query("SELECT mid, mname, munit FROM Materials ORDER BY mname ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $materials_list = [];
}

// ===== FETCH EXISTING FURNITURE FOR "ADD STOCK" =====
$existing_furniture = [];
try {
    $existing_furniture = $pdo->query("SELECT fid, fname FROM Furnitures ORDER BY fname ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $existing_furniture = [];
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action_type = isset($_POST['action_type']) ? $_POST['action_type'] : 'new';

    if ($action_type === 'existing') {
        // ===== ADD STOCK TO EXISTING FURNITURE =====
        $fid = intval($_POST['existing_furniture']);
        $additional_qty = intval($_POST['additional_qty']);

        if ($fid <= 0 || $additional_qty <= 0) {
            $msg = "<div class='alert alert-danger'>⚠️ Please select a furniture and enter a valid quantity.</div>";
        } else {
            try {
                // Check if furniture exists
                $check_stmt = $pdo->prepare("SELECT fname FROM Furnitures WHERE fid = ?");
                $check_stmt->execute([$fid]);
                $furniture = $check_stmt->fetch(PDO::FETCH_ASSOC);

                if (!$furniture) {
                    $msg = "<div class='alert alert-danger'>⚠️ Furniture not found.</div>";
                } else {
                    // Get materials needed for this furniture
                    $mat_stmt = $pdo->prepare("SELECT mid, pmqty FROM FurnitureMaterials WHERE fid = ?");
                    $mat_stmt->execute([$fid]);
                    $materials = $mat_stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (empty($materials)) {
                        $msg = "<div class='alert alert-warning'>⚠️ This furniture has no materials defined. Please add materials first.</div>";
                    } else {
                        // Start transaction
                        $pdo->beginTransaction();

                        // Update each material stock
                        foreach ($materials as $mat) {
                            $stock_to_add = $mat['pmqty'] * $additional_qty;
                            $update_stmt = $pdo->prepare("UPDATE Materials SET mqty = mqty + ? WHERE mid = ?");
                            $update_stmt->execute([$stock_to_add, $mat['mid']]);
                        }

                        $pdo->commit();
                        $msg = "<div class='alert alert-success'>✅ Added stock for $additional_qty unit(s) of '{$furniture['fname']}' successfully!</div>";
                    }
                }
            } catch (PDOException $e) {
                $pdo->rollBack();
                $msg = "<div class='alert alert-danger'>⚠️ Failed to add stock: " . $e->getMessage() . "</div>";
            }
        }
    } else {
        // ===== ADD NEW FURNITURE (DOES NOT INCREASE MATERIAL STOCK) =====
        $fname = trim($_POST['fname']);
        $fdesc = trim($_POST['fdesc']);
        $fprice = floatval($_POST['fprice']);
        $fimage = "";

        // Handle image upload
        if (isset($_FILES['fimage']) && $_FILES['fimage']['error'] === UPLOAD_ERR_OK) {
            $target_dir = "../uploads/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $filename = time() . "_" . preg_replace('/[^a-zA-Z0-9]/', '_', $_FILES["fimage"]["name"]);
            $target_file = $target_dir . $filename;

            if (move_uploaded_file($_FILES["fimage"]["tmp_name"], $target_file)) {
                $fimage = "uploads/" . $filename;
            } else {
                $msg = "<div class='alert alert-danger'>⚠️ Failed to upload image.</div>";
            }
        }

        try {
            $pdo->beginTransaction();

            // Insert furniture
            $stmt = $pdo->prepare("INSERT INTO Furnitures (fname, fdesc, fprice, fimage) VALUES (:fname, :fdesc, :fprice, :fimage)");
            $stmt->execute([
                    'fname' => $fname,
                    'fdesc' => $fdesc,
                    'fprice' => $fprice,
                    'fimage' => $fimage
            ]);
            $fid = $pdo->lastInsertId();

            // Insert material mappings (these define what materials are needed)
            // THIS DOES NOT INCREASE MATERIAL STOCK - just defines requirements
            if (isset($_POST['materials']) && is_array($_POST['materials'])) {
                $stmt_mat = $pdo->prepare("INSERT INTO FurnitureMaterials (fid, mid, pmqty) VALUES (:fid, :mid, :pmqty)");
                foreach ($_POST['materials'] as $mat) {
                    if (!empty($mat['mid']) && !empty($mat['pmqty'])) {
                        $stmt_mat->execute([
                                'fid' => $fid,
                                'mid' => intval($mat['mid']),
                                'pmqty' => intval($mat['pmqty'])
                        ]);
                    }
                }
            }

            $pdo->commit();
            $msg = "<div class='alert alert-success'>✅ Furniture Product added successfully! (ID: $fid)</div>";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $msg = "<div class='alert alert-danger'>⚠️ Failed to add product: " . $e->getMessage() . "</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insert Furniture - Premium Living</title>
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
            max-width: 800px;
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
            margin-bottom: 0.4rem;
            font-size: 0.85rem;
        }

        .form-group label i {
            color: var(--accent-gold);
            margin-right: 0.4rem;
            width: 1.2rem;
        }

        .form-group .required {
            color: #e74c3c;
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

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
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
            margin-top: 0.2rem;
        }

        .form-group input[type="file"] {
            padding: 0.5rem;
            border: 1.5px dashed var(--input-border);
            background: var(--cream);
            cursor: pointer;
        }

        .form-group input[type="file"]:hover {
            border-color: var(--accent-gold);
        }

        /* ===== MATERIAL SECTION ===== */
        .material-section {
            margin-top: 2rem;
            border-top: 2px solid var(--input-border);
            padding-top: 1.5rem;
        }

        .material-section .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 0.3rem;
        }

        .material-section .section-header h3 {
            font-size: 1.1rem;
            color: var(--wood-dark);
            font-family: 'Playfair Display', serif;
        }

        .material-section .section-header h3 i {
            color: var(--accent-gold);
            margin-right: 0.5rem;
        }

        .material-section .section-sub {
            color: var(--gray-wood);
            font-size: 0.85rem;
            margin-bottom: 1rem;
        }

        /* ===== MATERIAL ENTRY ===== */
        .material-entry {
            background: var(--cream);
            padding: 1rem;
            border-radius: 0.8rem;
            margin-bottom: 0.5rem;
            border: 1px solid var(--input-border);
            display: flex;
            gap: 1rem;
            align-items: flex-end;
            flex-wrap: wrap;
        }

        .material-entry .form-group {
            flex: 1;
            min-width: 150px;
            margin-bottom: 0;
        }

        .material-entry .form-group label {
            margin-bottom: 0.2rem;
            font-size: 0.75rem;
        }

        .material-entry .form-group select,
        .material-entry .form-group input {
            padding: 0.5rem 0.8rem;
            font-size: 0.85rem;
        }

        .material-entry .remove-btn {
            background: #fde8e8;
            color: #cc0000;
            border: none;
            padding: 0.4rem 0.8rem;
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 0.8rem;
            transition: all 0.3s;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            height: 40px;
            display: flex;
            align-items: center;
            gap: 0.3rem;
            flex-shrink: 0;
        }

        .material-entry .remove-btn:hover {
            background: #fce4e4;
            transform: translateY(-2px);
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
            background: var(--gray-wood);
            color: white;
        }

        .btn-secondary:hover {
            background: var(--wood-light);
            transform: translateY(-2px);
        }

        .btn-success {
            background: #2a9d8f;
            color: white;
        }

        .btn-success:hover {
            background: #21867a;
            transform: translateY(-2px);
        }

        .btn-sm {
            padding: 0.4rem 1rem;
            font-size: 0.8rem;
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

            .card-header {
                padding: 1rem;
            }

            .card-header h2 {
                font-size: 1.1rem;
            }

            .card-body {
                padding: 1.5rem;
            }

            .material-entry {
                flex-direction: column;
                align-items: stretch;
            }

            .material-entry .form-group {
                min-width: unset;
            }

            .material-entry .remove-btn {
                justify-content: center;
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
        <li><a href="insert-furniture.php" class="active"><i class="fas fa-plus-circle"></i> Insert Furniture</a></li>
        <li><a href="insert-material.php"><i class="fas fa-warehouse"></i> Insert Material</a></li>
        <li><a href="manage-orders.php"><i class="fas fa-clipboard-list"></i> Manage Orders</a></li>
        <li><a href="generate-report.php"><i class="fas fa-file-alt"></i> Generate Report</a></li>
        <li><a href="delete-furniture.php"><i class="fas fa-trash"></i> Delete Furniture</a></li>
        <li><a href="../index.php?action=logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</nav>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-plus-circle"></i> Manage Furniture Products</h2>
            <p>Add new furniture or add stock to existing furniture</p>
        </div>

        <?php echo $msg; ?>

        <div class="card-body">
            <!-- ===== TAB SWITCHER ===== -->
            <div class="tab-switcher">
                <button class="tab-btn active" onclick="switchTab('new')">
                    <i class="fas fa-plus-circle"></i> New Furniture
                </button>
                <button class="tab-btn" onclick="switchTab('existing')">
                    <i class="fas fa-edit"></i> Add Stock
                </button>
            </div>

            <!-- ===== TAB 1: ADD NEW FURNITURE ===== -->
            <div id="tab-new" class="tab-content active">
                <form action="insert-furniture.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action_type" value="new">

                    <div class="form-group">
                        <label for="fname"><i class="fas fa-tag"></i> Furniture Name <span class="required">*</span></label>
                        <input type="text" id="fname" name="fname" required placeholder="Enter furniture name">
                    </div>

                    <div class="form-group">
                        <label for="fdesc"><i class="fas fa-align-left"></i> Description <span class="required">*</span></label>
                        <textarea id="fdesc" name="fdesc" rows="3" required placeholder="Enter detailed description"></textarea>
                    </div>

                    <div class="form-group">
                        <label for="fimage"><i class="fas fa-image"></i> Furniture Image</label>
                        <input type="file" id="fimage" name="fimage" accept="image/*">
                        <div class="helper-text">
                            <i class="fas fa-info-circle"></i> Supported formats: JPG, PNG, WEBP, GIF. Max size: 5MB
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="fprice"><i class="fas fa-dollar-sign"></i> Price per Item ($) <span class="required">*</span></label>
                        <input type="number" id="fprice" name="fprice" step="0.01" required placeholder="Enter price">
                    </div>

                    <!-- Materials Section -->
                    <div class="material-section">
                        <div class="section-header">
                            <h3><i class="fas fa-cubes"></i> Materials Required</h3>
                        </div>
                        <p class="section-sub">Define what materials are needed to make this furniture</p>

                        <div id="materialsContainer">
                            <div class="material-entry">
                                <div class="form-group">
                                    <label><i class="fas fa-box"></i> Material <span class="required">*</span></label>
                                    <select name="materials[0][mid]" required>
                                        <option value="">Select Material</option>
                                        <?php foreach ($materials_list as $m): ?>
                                            <option value="<?php echo $m['mid']; ?>"><?php echo htmlspecialchars($m['mname']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label><i class="fas fa-sort-numeric-up"></i> Quantity Required <span class="required">*</span></label>
                                    <input type="number" name="materials[0][pmqty]" required placeholder="Qty" min="1">
                                </div>
                                <button type="button" class="remove-btn" onclick="this.closest('.material-entry').remove()" style="display:none;">
                                    <i class="fas fa-trash"></i> Remove
                                </button>
                            </div>
                        </div>

                        <div style="margin-top: 1rem; display: flex; gap: 0.8rem; flex-wrap: wrap;">
                            <button type="button" class="btn btn-secondary btn-sm" onclick="addMaterialEntry()">
                                <i class="fas fa-plus"></i> Add Another Material
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Create Furniture Product
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- ===== TAB 2: ADD STOCK TO EXISTING FURNITURE ===== -->
            <div id="tab-existing" class="tab-content">
                <form action="insert-furniture.php" method="POST">
                    <input type="hidden" name="action_type" value="existing">

                    <div class="form-group">
                        <label for="existing_furniture"><i class="fas fa-chair"></i> Select Furniture <span class="required">*</span></label>
                        <select id="existing_furniture" name="existing_furniture" required>
                            <option value="">-- Select Furniture --</option>
                            <?php foreach ($existing_furniture as $f): ?>
                                <option value="<?php echo $f['fid']; ?>"><?php echo htmlspecialchars($f['fname']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="helper-text">
                            <i class="fas fa-info-circle"></i> Select the furniture you want to add stock for
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="additional_qty"><i class="fas fa-sort-numeric-up"></i> Quantity to Add <span class="required">*</span></label>
                        <input type="number" id="additional_qty" name="additional_qty" required placeholder="Enter quantity to add to stock" min="1">
                        <div class="helper-text">
                            <i class="fas fa-info-circle"></i> This will increase the material stock for this furniture
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success" style="width:100%; margin-top: 0.5rem;">
                        <i class="fas fa-plus"></i> Add Stock
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    let materialCount = 1;
    const matsJson = <?php echo json_encode($materials_list); ?>;

    // ===== TAB SWITCHING =====
    function switchTab(tab) {
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));

        if (tab === 'new') {
            document.querySelector('.tab-btn:first-child').classList.add('active');
            document.getElementById('tab-new').classList.add('active');
        } else {
            document.querySelector('.tab-btn:last-child').classList.add('active');
            document.getElementById('tab-existing').classList.add('active');
        }
    }

    // ===== ADD MATERIAL ENTRY =====
    function addMaterialEntry() {
        const container = document.getElementById('materialsContainer');
        const entries = container.querySelectorAll('.material-entry');

        entries.forEach(entry => {
            const removeBtn = entry.querySelector('.remove-btn');
            if (removeBtn) removeBtn.style.display = 'flex';
        });

        const newEntry = document.createElement('div');
        newEntry.className = 'material-entry';

        let optionsHtml = '<option value="">Select Material</option>';
        matsJson.forEach(m => {
            optionsHtml += `<option value="${m.mid}">${m.mname}</option>`;
        });

        newEntry.innerHTML = `
            <div class="form-group">
                <label><i class="fas fa-box"></i> Material <span class="required">*</span></label>
                <select name="materials[${materialCount}][mid]" required>${optionsHtml}</select>
            </div>
            <div class="form-group">
                <label><i class="fas fa-sort-numeric-up"></i> Quantity Required <span class="required">*</span></label>
                <input type="number" name="materials[${materialCount}][pmqty]" required placeholder="Qty" min="1">
            </div>
            <button type="button" class="remove-btn" onclick="this.closest('.material-entry').remove()">
                <i class="fas fa-trash"></i> Remove
            </button>
        `;
        container.appendChild(newEntry);
        materialCount++;
    }
</script>

<footer>
    <p>&copy; 2026 Premium Living Furniture Co. Ltd. All rights reserved.</p>
</footer>
</body>
</html>