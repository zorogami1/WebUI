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

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

        // Get file extension
        $file_ext = strtolower(pathinfo($_FILES["fimage"]["name"], PATHINFO_EXTENSION));

        // Generate unique filename with timestamp
        $filename = time() . "_" . preg_replace('/[^a-zA-Z0-9]/', '_', $_FILES["fimage"]["name"]);
        $target_file = $target_dir . $filename;

        // Move uploaded file
        if (move_uploaded_file($_FILES["fimage"]["tmp_name"], $target_file)) {
            // Store relative path in database
            $fimage = "uploads/" . $filename;
        } else {
            $msg = "<div class='alert alert-danger'>⚠️ Failed to upload image.</div>";
        }
    }

    try {
        $pdo->beginTransaction();

        // Insert furniture with image path
        $stmt = $pdo->prepare("INSERT INTO Furnitures (fname, fdesc, fprice, fimage) VALUES (:fname, :fdesc, :fprice, :fimage)");
        $stmt->execute([
                'fname' => $fname,
                'fdesc' => $fdesc,
                'fprice' => $fprice,
                'fimage' => $fimage
        ]);
        $fid = $pdo->lastInsertId();

        // Insert material mappings
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
            max-width: 900px;
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

        /* ===== FORM ROW ===== */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 1rem;
            align-items: start;
        }

        .form-row .form-group:last-child {
            display: flex;
            align-items: flex-end;
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

        .btn-danger {
            background: #cc0000;
            color: white;
        }

        .btn-danger:hover {
            background: #a00000;
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

        /* ===== MATERIAL SECTION ===== */
        .material-section {
            margin-top: 2rem;
            border-top: 2px solid var(--input-border);
            padding-top: 1.5rem;
        }

        .material-section h3 {
            font-size: 1.1rem;
            color: var(--wood-dark);
            font-family: 'Playfair Display', serif;
        }

        .material-section h3 i {
            color: var(--accent-gold);
            margin-right: 0.5rem;
        }

        .material-section p {
            color: var(--gray-wood);
            font-size: 0.85rem;
            margin-top: 0.2rem;
        }

        .material-entry {
            background: var(--cream);
            padding: 1rem;
            border-radius: 0.8rem;
            margin-bottom: 0.5rem;
            border: 1px solid var(--input-border);
        }

        .material-entry .form-row {
            align-items: center;
        }

        .material-entry .form-group {
            margin-bottom: 0;
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

            .form-row {
                grid-template-columns: 1fr;
            }

            .form-row .form-group:last-child {
                display: block;
            }

            .alert {
                margin: 1rem 1rem 0;
            }

            .material-entry .form-row {
                grid-template-columns: 1fr;
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
            <h2><i class="fas fa-plus-circle"></i> Add New Furniture Product</h2>
            <p>Furniture ID will be generated automatically. Supports JPG, PNG, WEBP, GIF images.</p>
        </div>

        <?php echo $msg; ?>

        <div class="card-body">
            <form action="insert-furniture.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="fname"><i class="fas fa-tag"></i> Furniture Name *</label>
                    <input type="text" id="fname" name="fname" required placeholder="Enter furniture name">
                </div>

                <div class="form-group">
                    <label for="fdesc"><i class="fas fa-align-left"></i> Description *</label>
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
                    <label for="fprice"><i class="fas fa-dollar-sign"></i> Price per Item ($) *</label>
                    <input type="number" id="fprice" name="fprice" step="0.01" required placeholder="Enter price">
                </div>

                <div class="material-section">
                    <h3><i class="fas fa-cubes"></i> Materials Required</h3>
                    <p>A furniture product may consist of one or multiple materials</p>

                    <div id="materialsContainer" style="margin-top: 1rem;">
                        <div class="material-entry">
                            <div class="form-row">
                                <div class="form-group">
                                    <label><i class="fas fa-box"></i> Material</label>
                                    <select name="materials[0][mid]" required class="filter-select" style="width:100%;">
                                        <option value="">Select Material</option>
                                        <?php foreach ($materials_list as $m): ?>
                                            <option value="<?php echo $m['mid']; ?>"><?php echo htmlspecialchars($m['mname']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label><i class="fas fa-sort-numeric-up"></i> Quantity Required</label>
                                    <input type="number" name="materials[0][pmqty]" required placeholder="Quantity" min="1">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div style="margin-top: 1rem; display: flex; gap: 1rem; flex-wrap: wrap;">
                        <button type="button" class="btn btn-secondary" onclick="addMaterialEntry()">
                            <i class="fas fa-plus"></i> Add Another Material
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Create Furniture Product
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let materialCount = 1;
    const matsJson = <?php echo json_encode($materials_list); ?>;

    function addMaterialEntry() {
        const container = document.getElementById('materialsContainer');
        const newEntry = document.createElement('div');
        newEntry.className = 'material-entry';
        newEntry.style.marginTop = '0.5rem';

        let optionsHtml = '<option value="">Select Material</option>';
        matsJson.forEach(m => {
            optionsHtml += `<option value="${m.mid}">${m.mname}</option>`;
        });

        newEntry.innerHTML = `
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-box"></i> Material</label>
                    <select name="materials[${materialCount}][mid]" required class="filter-select" style="width:100%;">${optionsHtml}</select>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-sort-numeric-up"></i> Quantity Required</label>
                    <input type="number" name="materials[${materialCount}][pmqty]" required placeholder="Quantity" min="1">
                </div>
                <div class="form-group" style="display:flex; align-items:flex-end; justify-content:flex-end;">
                    <button type="button" class="btn btn-danger" onclick="this.closest('.material-entry').remove()">
                        <i class="fas fa-trash"></i> Remove
                    </button>
                </div>
            </div>
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