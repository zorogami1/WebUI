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

// Handle Delete Processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_fid'])) {
    $fid = intval($_POST['delete_fid']);

    try {
        // Check if furniture has any delivered orders
        $checkQuery = "SELECT COUNT(*) as delivered_count 
                       FROM OrderFurnitures of 
                       JOIN Orders o ON of.oid = o.oid 
                       WHERE of.fid = :fid AND LOWER(o.status) = 'delivered'";
        $checkStmt = $pdo->prepare($checkQuery);
        $checkStmt->execute(['fid' => $fid]);
        $result = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($result['delivered_count'] > 0) {
            $msg = "<div style='background-color:#fce4e4; color:#cc0000; padding:0.75rem; border-radius:4px; margin-bottom:1rem;'>
                    ⚠️ Cannot delete this furniture item because it has been used in delivered orders. 
                    <br>You can only delete items that are not associated with delivered orders.
                    </div>";
        } else {
            // Proceed with deletion
            $pdo->beginTransaction();

            // Check if there are any pending orders (not delivered)
            $pendingQuery = "SELECT COUNT(*) as pending_count 
                           FROM OrderFurnitures of 
                           JOIN Orders o ON of.oid = o.oid 
                           WHERE of.fid = :fid AND LOWER(o.status) != 'delivered' AND LOWER(o.status) != 'completed'";
            $pendingStmt = $pdo->prepare($pendingQuery);
            $pendingStmt->execute(['fid' => $fid]);
            $pendingResult = $pendingStmt->fetch(PDO::FETCH_ASSOC);

            if ($pendingResult['pending_count'] > 0) {
                // Warn about pending orders
                $msg = "<div style='background-color:#fef7e0; color:#b06000; padding:0.75rem; border-radius:4px; margin-bottom:1rem;'>
                        ⚠️ This furniture item has pending orders. Deleting it will remove the product from these orders.
                        </div>";
            }

            // Clear mapping composite values before catalog purging
            $stmt1 = $pdo->prepare("DELETE FROM FurnitureMaterials WHERE fid = :fid");
            $stmt1->execute(['fid' => $fid]);

            $stmt2 = $pdo->prepare("DELETE FROM Furnitures WHERE fid = :fid");
            $stmt2->execute(['fid' => $fid]);

            $pdo->commit();
            $msg = "<div style='background-color:#e6f4ea; color:#137333; padding:0.75rem; border-radius:4px; margin-bottom:1rem;'>
                    ✅ Furniture item successfully deleted from catalog records.
                    </div>";
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        $msg = "<div style='background-color:#fce4e4; color:#cc0000; padding:0.75rem; border-radius:4px; margin-bottom:1rem;'>
                ⚠️ Deletion failed: " . $e->getMessage() . "
                </div>";
    }
}

// Gather catalog inventory structural details with order status information
try {
    $query = "SELECT f.fid, f.fname, f.fdesc, f.fprice, f.fimage,
              (SELECT COUNT(*) FROM OrderFurnitures of WHERE of.fid = f.fid) AS order_count,
              (SELECT COUNT(*) FROM OrderFurnitures of 
               JOIN Orders o ON of.oid = o.oid 
               WHERE of.fid = f.fid AND LOWER(o.status) = 'delivered') AS delivered_count,
              (SELECT COUNT(*) FROM OrderFurnitures of 
               JOIN Orders o ON of.oid = o.oid 
               WHERE of.fid = f.fid AND LOWER(o.status) != 'delivered' AND LOWER(o.status) != 'completed') AS pending_count
              FROM Furnitures f 
              ORDER BY f.fid ASC";
    $catalog = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $catalog = [];
    $msg = "<div style='background-color:#fce4e4; color:#cc0000; padding:0.75rem; border-radius:4px; margin-bottom:1rem;'>
            ⚠️ Error loading catalog: " . $e->getMessage() . "
            </div>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Furniture - Premium Living Furniture</title>
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
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }

        /* ===== TABLE ===== */
        .table-container {
            overflow-x: auto;
            padding: 0;
        }

        .table-container table {
            width: 100%;
            border-collapse: collapse;
        }

        .table-container thead {
            background: var(--wood-dark);
            color: white;
        }

        .table-container th {
            padding: 0.8rem 1.2rem;
            text-align: center;
            font-weight: 600;
            font-size: 0.85rem;
        }

        .table-container td {
            padding: 0.8rem 1.2rem;
            text-align: center;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            color: var(--wood-dark);
            font-size: 0.9rem;
        }

        .table-container tbody tr:hover {
            background: rgba(212, 163, 115, 0.05);
        }

        /* ===== BUTTONS ===== */
        .btn {
            display: inline-block;
            padding: 0.5rem 1.2rem;
            border: none;
            border-radius: var(--radius-btn);
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 0.85rem;
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
        }

        .btn-danger {
            background: #cc0000;
            color: white;
        }

        .btn-danger:hover {
            background: #a00000;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: var(--gray-wood);
            color: white;
        }

        .btn-secondary:hover {
            background: var(--wood-light);
            transform: translateY(-2px);
        }

        .btn-warning {
            background: #e76f51;
            color: white;
        }

        .btn-warning:hover {
            background: #d45a3a;
            transform: translateY(-2px);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn:disabled:hover {
            transform: none;
        }

        /* ===== BADGES ===== */
        .delivered-badge {
            background-color: #e6f4ea;
            color: #137333;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }

        .pending-badge {
            background-color: #fef7e0;
            color: #b06000;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-icons {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
        }

        .status-icons i {
            font-size: 1.1rem;
        }

        /* ===== MODAL ===== */
        .modal {
            display: none;
            position: fixed;
            z-index: 100;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            backdrop-filter: blur(3px);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 25px;
            border: 2px solid #d4a373;
            width: 40%;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .close {
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #999;
            transition: color 0.2s;
        }

        .close:hover {
            color: #cc0000;
        }

        /* ===== LEGEND ===== */
        .legend {
            margin-top: 1.5rem;
            padding: 1rem;
            background: var(--cream);
            border-radius: 8px;
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
        }

        .legend-color {
            display: inline-block;
            width: 16px;
            height: 16px;
            border-radius: 4px;
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

            .modal-content {
                width: 90%;
                margin: 30% auto;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0 1rem;
            }

            .status-icons {
                flex-direction: column;
                gap: 0.2rem;
            }

            .legend {
                flex-direction: column;
                gap: 0.5rem;
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
        <li><a href="insert-material.php"><i class="fas fa-warehouse"></i> Insert Material</a></li>
        <li><a href="manage-orders.php"><i class="fas fa-clipboard-list"></i> Manage Orders</a></li>
        <li><a href="generate-report.php"><i class="fas fa-file-alt"></i> Generate Report</a></li>
        <li><a href="delete-furniture.php" class="active"><i class="fas fa-trash"></i> Delete Furniture</a></li>
        <li><a href="../index.php?action=logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</nav>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-trash-alt"></i> Delete Furniture Product</h2>
            <p style="color: #e74c3c;">
                <i class="fas fa-info-circle"></i>
                <strong>Note:</strong> An item can only be deleted when it has <strong>no delivered orders</strong>.
                Items with pending or delivered orders cannot be deleted.
            </p>
        </div>

        <?php echo $msg; ?>

        <div class="table-container">
            <table>
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Furniture Name</th>
                    <th>Description</th>
                    <th>Price</th>
                    <th>Order Status</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($catalog)): ?>
                    <tr>
                        <td colspan="7" style="text-align:center; padding: 2rem; color: #999;">
                            <i class="fas fa-folder-open" style="font-size: 2rem; display: block; margin-bottom: 0.5rem;"></i>
                            No furniture products found in catalog.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($catalog as $f):
                        $can_delete = ($f['delivered_count'] == 0);
                        $has_orders = ($f['order_count'] > 0);
                        $has_pending = ($f['pending_count'] > 0);
                        ?>
                        <tr style="<?php echo !$can_delete ? 'background-color: #fce4e4;' : ($has_orders ? 'background-color: #fff8e7;' : 'background-color: #e8f5e9;'); ?>">
                            <td><strong><?php echo $f['fid']; ?></strong></td>
                            <td>
                                <?php
                                $image_shown = false;

                                // Check 1: Staff uploaded images in uploads folder
                                if (!empty($f['fimage']) && file_exists('../' . $f['fimage'])) {
                                    echo "<img src='../" . $f['fimage'] . "' width='50' height='50' style='border-radius:6px; object-fit:cover;'>";
                                    $image_shown = true;
                                }

                                // Check 2: Default PNG images in images folder
                                if (!$image_shown) {
                                    $default_path = "../images/" . $f['fid'] . ".png";
                                    if (file_exists($default_path)) {
                                        echo "<img src='$default_path' width='50' height='50' style='border-radius:6px; object-fit:cover;'>";
                                        $image_shown = true;
                                    }
                                }

                                // Check 3: Try JPG extension
                                if (!$image_shown) {
                                    $default_path = "../images/" . $f['fid'] . ".jpg";
                                    if (file_exists($default_path)) {
                                        echo "<img src='$default_path' width='50' height='50' style='border-radius:6px; object-fit:cover;'>";
                                        $image_shown = true;
                                    }
                                }

                                // Check 4: Try WEBP extension
                                if (!$image_shown) {
                                    $default_path = "../images/" . $f['fid'] . ".webp";
                                    if (file_exists($default_path)) {
                                        echo "<img src='$default_path' width='50' height='50' style='border-radius:6px; object-fit:cover;'>";
                                        $image_shown = true;
                                    }
                                }

                                // No image found - show icon
                                if (!$image_shown) {
                                    echo "<i class='fas fa-chair' style='font-size: 2rem; color: #d4a373;'></i>";
                                }
                                ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($f['fname']); ?></strong></td>
                            <td style="max-width: 150px; font-size: 0.85rem;"><?php echo htmlspecialchars(substr($f['fdesc'], 0, 40)) . (strlen($f['fdesc']) > 40 ? '...' : ''); ?></td>
                            <td><strong>$<?php echo number_format($f['fprice'], 2); ?></strong></td>
                            <td>
                                <div class="status-icons">
                                    <?php if ($f['delivered_count'] > 0): ?>
                                        <span class="delivered-badge">
                                            <i class="fas fa-check-circle"></i> Delivered (<?php echo $f['delivered_count']; ?>)
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($f['pending_count'] > 0): ?>
                                        <span class="pending-badge">
                                            <i class="fas fa-clock"></i> Pending (<?php echo $f['pending_count']; ?>)
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($f['order_count'] == 0): ?>
                                        <span style="color: #137333; font-weight: 600;">
                                            <i class="fas fa-check-circle"></i> No Orders
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php if (!$can_delete): ?>
                                    <button class="btn btn-secondary" disabled style="opacity: 0.6; cursor: not-allowed;">
                                        <i class="fas fa-ban"></i> Cannot Delete
                                    </button>
                                    <div style="font-size: 0.7rem; color: #cc0000; margin-top: 3px;">
                                        Has delivered orders
                                    </div>
                                <?php elseif ($has_orders): ?>
                                    <button class="btn btn-warning" onclick="confirmDelete(<?php echo $f['fid']; ?>, '<?php echo addslashes($f['fname']); ?>', <?php echo $f['pending_count']; ?>)">
                                        <i class="fas fa-exclamation-triangle"></i> Delete (Pending)
                                    </button>
                                    <div style="font-size: 0.7rem; color: #b06000; margin-top: 3px;">
                                        <?php echo $f['pending_count']; ?> pending order(s)
                                    </div>
                                <?php else: ?>
                                    <button class="btn btn-danger" onclick="confirmDelete(<?php echo $f['fid']; ?>, '<?php echo addslashes($f['fname']); ?>', 0)">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Legend -->
        <div class="legend">
            <div class="legend-item">
                <span class="legend-color" style="background: #e8f5e9;"></span> No orders (can delete)
            </div>
            <div class="legend-item">
                <span class="legend-color" style="background: #fff8e7;"></span> Has pending orders (delete with caution)
            </div>
            <div class="legend-item">
                <span class="legend-color" style="background: #fce4e4;"></span> Has delivered orders (cannot delete)
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeDeleteModal()">&times;</span>
        <h3 style="color: #cc0000;"><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h3>
        <p>Are you sure you want to delete this furniture product?</p>
        <p id="furnitureName" style="font-weight: bold; color: #cc0000; font-size: 1.1rem;"></p>
        <p id="pendingWarning" style="color: #b06000; font-weight: 600; display: none;">
            <i class="fas fa-clock"></i> This item has pending orders. Deleting it will remove it from these orders.
        </p>
        <form action="delete-furniture.php" method="POST" style="margin-top: 1.5rem;">
            <input type="hidden" id="delete_fid" name="delete_fid" value="">
            <div style="display: flex; gap: 1rem; justify-content: center;">
                <button type="submit" class="btn btn-danger" style="padding: 0.6rem 2rem;">
                    <i class="fas fa-trash"></i> Yes, Delete Product
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function confirmDelete(fid, name, pendingCount) {
        document.getElementById('delete_fid').value = fid;
        document.getElementById('furnitureName').innerText = name;

        const warning = document.getElementById('pendingWarning');
        if (pendingCount > 0) {
            warning.style.display = 'block';
            warning.innerHTML = '<i class="fas fa-clock"></i> This item has ' + pendingCount + ' pending order(s). Deleting it will remove it from these orders.';
        } else {
            warning.style.display = 'none';
        }

        document.getElementById('deleteModal').style.display = 'block';
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').style.display = 'none';
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target == document.getElementById('deleteModal')) {
            closeDeleteModal();
        }
    }
</script>

<footer>
    <p>&copy; 2026 Premium Living Furniture Co. Ltd. All rights reserved.</p>
</footer>
</body>
</html>