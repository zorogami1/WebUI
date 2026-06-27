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

// Define material types/categories
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

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mname = trim($_POST['mname']);
    $mtype = trim($_POST['mtype']);
    $mqty = intval($_POST['mqty']);
    $munit = trim($_POST['munit']);

    if (empty($mname) || empty($mtype) || empty($munit)) {
        $msg = "<div style='background-color:#fce4e4; color:#cc0000; padding:0.75rem; border-radius:4px; margin-bottom:1rem;'>⚠️ Please fill in all required fields.</div>";
    } else {
        try {
            // Add mtype column if it doesn't exist (run this once)
            // ALTER TABLE Materials ADD mtype VARCHAR(50) NULL AFTER mname;

            $stmt = $pdo->prepare("INSERT INTO Materials (mname, mtype, mqty, munit) VALUES (:mname, :mtype, :mqty, :munit)");
            $stmt->execute([
                    'mname' => $mname,
                    'mtype' => $mtype,
                    'mqty' => $mqty,
                    'munit' => $munit
            ]);
            $msg = "<div style='background-color:#e6f4ea; color:#137333; padding:0.75rem; border-radius:4px; margin-bottom:1rem;'>✅ Material added successfully! (ID: " . $pdo->lastInsertId() . ")</div>";
        } catch (PDOException $e) {
            $msg = "<div style='background-color:#fce4e4; color:#cc0000; padding:0.75rem; border-radius:4px; margin-bottom:1rem;'>⚠️ Failed to add material: " . $e->getMessage() . "</div>";
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
    <link rel="stylesheet" href="../css/styles.css">
    <style>
        .form-row-two {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.2rem;
        }
        @media (max-width: 768px) {
            .form-row-two {
                grid-template-columns: 1fr;
            }
        }
        .type-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 8px;
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
    </style>
</head>
<body>
<nav class="navbar">
    <div class="logo"><h1><a href="dashboard.php"><i class="fas fa-tree"></i> Staff Portal</a></h1></div>
    <ul class="nav-links">
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="insert-furniture.php">Insert Furniture</a></li>
        <li><a href="insert-material.php" class="active">Insert Material</a></li>
        <li><a href="manage-orders.php">Manage Orders</a></li>
        <li><a href="generate-report.php">Generate Report</a></li>
        <li><a href="delete-furniture.php">Delete Furniture</a></li>
        <li><a href="login.php">Logout</a></li>
    </ul>
</nav>

<div class="container">
    <div class="card" style="max-width: 650px; margin: 0 auto;">
        <div class="card-header">
            <h2><i class="fas fa-warehouse"></i> Add New Material</h2>
            <p>Material ID will be generated automatically</p>
        </div>

        <?php echo $msg; ?>

        <form action="insert-material.php" method="POST">
            <!-- Material Name -->
            <div class="form-group">
                <label for="mname">Material Name *</label>
                <input type="text" id="mname" name="mname" required placeholder="Enter material name (e.g., Oak Wood Plank)">
            </div>

            <!-- Material Type Dropdown -->
            <div class="form-group">
                <label for="mtype">Material Type / Category *</label>
                <select id="mtype" name="mtype" required class="filter-select" style="width:100%;">
                    <option value="">-- Select Material Type --</option>
                    <?php foreach ($material_types as $key => $label): ?>
                        <option value="<?php echo $key; ?>">
                            <?php echo $label; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div style="margin-top: 0.5rem; font-size: 0.8rem; color: var(--wood-light);">
                    <i class="fas fa-info-circle"></i> Select the category that best describes this material
                </div>
            </div>

            <!-- Quantity and Unit in two columns -->
            <div class="form-row-two">
                <div class="form-group">
                    <label for="mqty">Quantity *</label>
                    <input type="number" id="mqty" name="mqty" required placeholder="Enter quantity" min="0" step="0.01">
                </div>
                <div class="form-group">
                    <label for="munit">Unit of Measurement *</label>
                    <select id="munit" name="munit" required class="filter-select" style="width:100%;">
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

            <!-- Material Type Preview -->
            <div style="margin: 1rem 0; padding: 0.75rem; background: var(--wood-bg); border-radius: 8px; border: 1px solid rgba(139,94,60,0.1); display: none;" id="typePreview">
                <span style="font-weight: 600;">Selected Type:</span>
                <span id="selectedTypeDisplay" style="font-weight: 400;"></span>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%; margin-top: 0.5rem;">
                <i class="fas fa-plus-circle"></i> Add Material to Inventory
            </button>
        </form>

        <!-- Quick Reference Table -->
        <div style="margin-top: 2rem; border-top: 1px solid var(--wood-pale); padding-top: 1.5rem;">
            <h4 style="color: var(--wood-dark);"><i class="fas fa-list"></i> Common Material Types</h4>
            <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.5rem;">
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

<script>
    // Show type preview when user selects a type
    document.getElementById('mtype').addEventListener('change', function() {
        const preview = document.getElementById('typePreview');
        const display = document.getElementById('selectedTypeDisplay');
        const selectedOption = this.options[this.selectedIndex];

        if (this.value) {
            preview.style.display = 'block';
            display.textContent = selectedOption.text;
        } else {
            preview.style.display = 'none';
        }
    });
</script>

<footer>
    <p>&copy; 2026 Premium Living Furniture Co. Ltd. All rights reserved.</p>
</footer>
</body>
</html>