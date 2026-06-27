<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['sid'])) {
    header("Location: login.php");
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
            $msg = "<div style='background-color:#fce4e4; color:#cc0000; padding:0.75rem; border-radius:4px; margin-bottom:1rem;'>⚠️ Failed to upload image.</div>";
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
        $msg = "<div style='background-color:#e6f4ea; color:#137333; padding:0.75rem; border-radius:4px; margin-bottom:1rem;'>✅ Furniture Product added successfully! (ID: $fid)</div>";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $msg = "<div style='background-color:#fce4e4; color:#cc0000; padding:0.75rem; border-radius:4px; margin-bottom:1rem;'>⚠️ Failed to add product: " . $e->getMessage() . "</div>";
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
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
<nav class="navbar">
    <div class="logo"><h1><a href="dashboard.php"><i class="fas fa-tree"></i> Staff Portal</a></h1></div>
    <ul class="nav-links">
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="insert-furniture.php" class="active">Insert Furniture</a></li>
        <li><a href="insert-material.php">Insert Material</a></li>
        <li><a href="manage-orders.php">Manage Orders</a></li>
        <li><a href="generate-report.php">Generate Report</a></li>
        <li><a href="delete-furniture.php">Delete Furniture</a></li>
        <li><a href="login.php">Logout</a></li>
    </ul>
</nav>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-plus-circle"></i> Add New Furniture Product</h2>
            <p>Furniture ID will be generated automatically. Supports JPG, PNG, WEBP, GIF images.</p>
        </div>

        <?php echo $msg; ?>

        <form action="insert-furniture.php" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="fname">Furniture Name *</label>
                <input type="text" id="fname" name="fname" required placeholder="Enter furniture name">
            </div>
            <div class="form-group">
                <label for="fdesc">Description *</label>
                <textarea id="fdesc" name="fdesc" rows="3" required placeholder="Enter detailed description"></textarea>
            </div>
            <div class="form-group">
                <label for="fimage">Furniture Image</label>
                <input type="file" id="fimage" name="fimage" accept="image/*">
                <div style="font-size: 0.8rem; color: #a89f91; margin-top: 0.3rem;">
                    <i class="fas fa-info-circle"></i> Supported formats: JPG, PNG, WEBP, GIF. Max size: 5MB
                </div>
            </div>
            <div class="form-group">
                <label for="fprice">Price per Item ($) *</label>
                <input type="number" id="fprice" name="fprice" step="0.01" required placeholder="Enter price">
            </div>

            <div style="margin-top: 2rem; border-top: 2px solid var(--wood-pale); padding-top: 1.5rem;">
                <h3><i class="fas fa-cubes"></i> Materials Required</h3>
                <p style="color: var(--wood-light);">A furniture product may consist of one or multiple materials</p>
            </div>

            <div id="materialsContainer" style="margin-top: 1rem;">
                <div class="material-entry form-row">
                    <div class="form-group">
                        <label>Material</label>
                        <select name="materials[0][mid]" required class="filter-select" style="width:100%;">
                            <option value="">Select Material</option>
                            <?php foreach ($materials_list as $m): ?>
                                <option value="<?php echo $m['mid']; ?>"><?php echo htmlspecialchars($m['mname']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Quantity Required</label>
                        <input type="number" name="materials[0][pmqty]" required placeholder="Quantity" min="1">
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
        </form>
    </div>
</div>

<script>
    let materialCount = 1;
    const matsJson = <?php echo json_encode($materials_list); ?>;

    function addMaterialEntry() {
        const container = document.getElementById('materialsContainer');
        const newEntry = document.createElement('div');
        newEntry.className = 'material-entry form-row';
        newEntry.style.marginTop = '0.5rem';

        let optionsHtml = '<option value="">Select Material</option>';
        matsJson.forEach(m => {
            optionsHtml += `<option value="${m.mid}">${m.mname}</option>`;
        });

        newEntry.innerHTML = `
            <div class="form-group">
                <label>Material</label>
                <select name="materials[${materialCount}][mid]" required class="filter-select" style="width:100%;">${optionsHtml}</select>
            </div>
            <div class="form-group">
                <label>Quantity Required</label>
                <input type="number" name="materials[${materialCount}][pmqty]" required placeholder="Quantity" min="1">
            </div>
            <div class="form-group" style="display:flex; align-items:flex-end;">
                <button type="button" class="btn btn-danger" onclick="this.parentElement.parentElement.remove()">
                    <i class="fas fa-trash"></i> Remove
                </button>
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