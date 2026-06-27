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

// Check if PDF export is requested
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    exportPDF();
    exit();
}

// Pull real systemic sales tracking rows straight from transactions tables
try {
    $query = "SELECT o.oid, o.odate AS date, f.fname AS item, f.fimage, of.oqty AS qty, (of.oqty * f.fprice) AS total, f.fprice AS unit_price
              FROM orders o
              JOIN orderfurnitures of ON o.oid = of.oid
              JOIN furnitures f ON of.fid = f.fid
              ORDER BY o.odate DESC";
    $sales_records = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $sales_records = [];
}

function exportPDF() {
    global $pdo;

    // Fetch data for PDF
    try {
        $query = "SELECT o.oid, o.odate AS date, f.fname AS item, f.fimage, of.oqty AS qty, (of.oqty * f.fprice) AS total, f.fprice AS unit_price
                  FROM orders o
                  JOIN orderfurnitures of ON o.oid = of.oid
                  JOIN furnitures f ON of.fid = f.fid
                  ORDER BY o.odate DESC";
        $sales_records = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Error fetching data: " . $e->getMessage());
    }

    // Calculate totals
    $total_revenue = 0;
    $total_items = 0;
    $total_orders = [];
    foreach ($sales_records as $record) {
        $total_revenue += $record['total'];
        $total_items += $record['qty'];
        $total_orders[$record['oid']] = true;
    }
    $total_orders_count = count($total_orders);
    $avg_order = $total_orders_count > 0 ? $total_revenue / $total_orders_count : 0;

    // Use FPDF
    require_once('../fpdf/fpdf.php');

    // Custom PDF class to handle images
    class PDF extends FPDF {
        function CellWithImage($w, $h, $image_path, $text, $border=0, $align='C') {
            // Get the current x position
            $x = $this->GetX();
            $y = $this->GetY();

            // Check if image exists
            if (!empty($image_path) && file_exists('../' . $image_path)) {
                // Calculate image height
                $image_height = $h - 2;
                $image_width = $image_height;

                // Place image
                $this->Image('../' . $image_path, $x + 1, $y + 1, $image_width, $image_height);
                // Move cursor after image
                $this->SetX($x + $image_width + 4);
                // Output text next to image
                $this->Cell($w - $image_width - 4, $h, $text, 0, 0, $align);
            } else {
                // Output text only
                $this->Cell($w, $h, $text, 0, 0, $align);
            }
            // Reset X position
            $this->SetX($x + $w);
        }
    }

    // Create PDF
    $pdf = new PDF('P', 'mm', 'A4');
    $pdf->AddPage();
    $pdf->SetAutoPageBreak(true, 25);

    // ============================================
    // HEADER
    // ============================================
    // Decorative top line
    $pdf->SetDrawColor(212, 163, 115);
    $pdf->SetLineWidth(1.5);
    $pdf->Line(15, 15, 195, 15);

    // Company Name
    $pdf->SetY(22);
    $pdf->SetFont('Arial', 'B', 20);
    $pdf->SetTextColor(62, 42, 33);
    $pdf->Cell(190, 10, 'Premium Living Furniture', 0, 1, 'C');

    // Tagline
    $pdf->SetFont('Arial', 'I', 10);
    $pdf->SetTextColor(139, 94, 60);
    $pdf->Cell(190, 5, 'Crafting Excellence Since 2004', 0, 1, 'C');

    // Decorative line
    $pdf->SetDrawColor(139, 94, 60);
    $pdf->SetLineWidth(0.5);
    $pdf->Line(60, 39, 150, 39);

    // Report Title
    $pdf->Ln(5);
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->SetTextColor(62, 42, 33);
    $pdf->Cell(190, 10, 'Sales Analytics Report', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 10);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(190, 5, 'Comprehensive Sales Performance Summary', 0, 1, 'C');
    $pdf->Ln(10);

    // ============================================
    // EXECUTIVE SUMMARY - 4 CARDS
    // ============================================
    $pdf->SetFont('Arial', 'B', 13);
    $pdf->SetTextColor(62, 42, 33);
    $pdf->Cell(190, 8, 'Executive Summary', 0, 1, 'L');
    $pdf->Ln(3);

    // Card dimensions
    $card_width = 85;
    $card_height = 28;
    $spacing = 10;
    $start_x = 15;
    $start_y = $pdf->GetY();

    // ROW 1: Total Revenue and Total Orders
    // Card 1 - Total Revenue
    $x1 = $start_x;
    $y1 = $start_y;
    $pdf->SetFillColor(245, 239, 230);
    $pdf->SetDrawColor(212, 163, 115);
    $pdf->SetLineWidth(0.3);
    $pdf->Rect($x1, $y1, $card_width, $card_height, 'DF');

    $pdf->SetXY($x1, $y1 + 4);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetTextColor(139, 94, 60);
    $pdf->Cell($card_width, 6, 'TOTAL REVENUE', 0, 1, 'C');

    $pdf->SetXY($x1, $y1 + 13);
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->SetTextColor(62, 42, 33);
    $pdf->Cell($card_width, 10, '$' . number_format($total_revenue, 2), 0, 1, 'C');

    // Card 2 - Total Orders
    $x2 = $start_x + $card_width + $spacing;
    $y2 = $start_y;
    $pdf->SetFillColor(245, 239, 230);
    $pdf->SetDrawColor(212, 163, 115);
    $pdf->SetLineWidth(0.3);
    $pdf->Rect($x2, $y2, $card_width, $card_height, 'DF');

    $pdf->SetXY($x2, $y2 + 4);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetTextColor(139, 94, 60);
    $pdf->Cell($card_width, 6, 'TOTAL ORDERS', 0, 1, 'C');

    $pdf->SetXY($x2, $y2 + 13);
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->SetTextColor(62, 42, 33);
    $pdf->Cell($card_width, 10, $total_orders_count, 0, 1, 'C');

    // ROW 2: Items Sold and Avg Order Value
    $start_y2 = $start_y + $card_height + 8;

    // Card 3 - Items Sold
    $x3 = $start_x;
    $y3 = $start_y2;
    $pdf->SetFillColor(245, 239, 230);
    $pdf->SetDrawColor(212, 163, 115);
    $pdf->SetLineWidth(0.3);
    $pdf->Rect($x3, $y3, $card_width, $card_height, 'DF');

    $pdf->SetXY($x3, $y3 + 4);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetTextColor(139, 94, 60);
    $pdf->Cell($card_width, 6, 'ITEMS SOLD', 0, 1, 'C');

    $pdf->SetXY($x3, $y3 + 13);
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->SetTextColor(62, 42, 33);
    $pdf->Cell($card_width, 10, $total_items, 0, 1, 'C');

    // Card 4 - Avg Order Value
    $x4 = $start_x + $card_width + $spacing;
    $y4 = $start_y2;
    $pdf->SetFillColor(245, 239, 230);
    $pdf->SetDrawColor(212, 163, 115);
    $pdf->SetLineWidth(0.3);
    $pdf->Rect($x4, $y4, $card_width, $card_height, 'DF');

    $pdf->SetXY($x4, $y4 + 4);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetTextColor(139, 94, 60);
    $pdf->Cell($card_width, 6, 'AVG. ORDER VALUE', 0, 1, 'C');

    $pdf->SetXY($x4, $y4 + 13);
    $pdf->SetFont('Arial', 'B', 16);
    $pdf->SetTextColor(62, 42, 33);
    $pdf->Cell($card_width, 10, '$' . number_format($avg_order, 2), 0, 1, 'C');

    // Move Y position after the cards
    $pdf->SetY($start_y2 + $card_height + 10);

    // ============================================
    // TRANSACTION DETAILS TABLE
    // ============================================
    $pdf->SetFont('Arial', 'B', 13);
    $pdf->SetTextColor(62, 42, 33);
    $pdf->Cell(190, 8, 'Transaction Details', 0, 1, 'L');
    $pdf->Ln(2);

    // Table Header - Added Image column
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetFillColor(62, 42, 33);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetDrawColor(62, 42, 33);
    $pdf->SetLineWidth(0.3);

    $pdf->Cell(22, 9, 'Order ID', 1, 0, 'C', true);
    $pdf->Cell(27, 9, 'Date', 1, 0, 'C', true);
    $pdf->Cell(20, 9, 'Image', 1, 0, 'C', true);
    $pdf->Cell(38, 9, 'Item', 1, 0, 'C', true);
    $pdf->Cell(18, 9, 'Qty', 1, 0, 'C', true);
    $pdf->Cell(30, 9, 'Unit Price', 1, 0, 'C', true);
    $pdf->Cell(35, 9, 'Total', 1, 1, 'C', true);

    $pdf->SetFont('Arial', '', 8);
    $pdf->SetTextColor(0, 0, 0);

    // Table Rows
    if (empty($sales_records)) {
        $pdf->Cell(190, 8, 'No sales records found.', 1, 1, 'C');
    } else {
        $fill = false;
        foreach ($sales_records as $record) {
            $fill = !$fill;
            $pdf->SetFillColor($fill ? 250 : 255, $fill ? 247 : 255, $fill ? 242 : 255);

            $pdf->Cell(22, 10, '#' . $record['oid'], 1, 0, 'C', $fill);
            $pdf->Cell(27, 10, date('Y-m-d', strtotime($record['date'])), 1, 0, 'C', $fill);

            // Image cell
            $image_cell = 20;
            $image_x = $pdf->GetX();
            $image_y = $pdf->GetY();

            if (!empty($record['fimage']) && file_exists('../' . $record['fimage'])) {
                // Place image in cell
                $pdf->Image('../' . $record['fimage'], $image_x + 2, $image_y + 1, 16, 8);
                $pdf->SetX($image_x + $image_cell);
            } else {
                // If no image, show placeholder text
                $pdf->Cell($image_cell, 10, 'N/A', 1, 0, 'C', $fill);
            }

            $pdf->Cell(38, 10, htmlspecialchars(substr($record['item'], 0, 28)), 1, 0, 'L', $fill);
            $pdf->Cell(18, 10, $record['qty'], 1, 0, 'C', $fill);
            $pdf->Cell(30, 10, '$' . number_format($record['unit_price'], 2), 1, 0, 'R', $fill);
            $pdf->Cell(35, 10, '$' . number_format($record['total'], 2), 1, 1, 'R', $fill);
        }
    }

    // Grand Total
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(212, 163, 115);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(155, 9, 'GRAND TOTAL', 1, 0, 'R', true);
    $pdf->Cell(35, 9, '$' . number_format($total_revenue, 2), 1, 1, 'R', true);

    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln(5);

    // ============================================
    // TOP PERFORMING ITEMS
    // ============================================
    $pdf->SetFont('Arial', 'B', 13);
    $pdf->SetTextColor(62, 42, 33);
    $pdf->Cell(190, 8, 'Top Performing Items', 0, 1, 'L');
    $pdf->Ln(2);

    // Calculate sales by item
    $item_sales = [];
    foreach ($sales_records as $record) {
        $item_name = $record['item'];
        if (!isset($item_sales[$item_name])) {
            $item_sales[$item_name] = 0;
        }
        $item_sales[$item_name] += $record['total'];
    }

    // Sort and get top 5
    arsort($item_sales);
    $top_items = array_slice($item_sales, 0, 5);

    if (!empty($top_items)) {
        $pdf->SetFont('Arial', '', 10);
        $rank = 1;
        foreach ($top_items as $item => $amount) {
            $pdf->SetTextColor(62, 42, 33);
            $pdf->Cell(15, 7, '#' . $rank, 0, 0, 'L');
            $pdf->Cell(130, 7, htmlspecialchars(substr($item, 0, 40)), 0, 0, 'L');
            $pdf->SetTextColor(139, 94, 60);
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(45, 7, '$' . number_format($amount, 2), 0, 1, 'R');
            $pdf->SetFont('Arial', '', 10);
            $rank++;
        }
    }

    // ============================================
    // FOOTER
    // ============================================
    $pdf->Ln(5);
    $pdf->SetFont('Arial', 'I', 8);
    $pdf->SetTextColor(168, 159, 145);
    $pdf->Cell(190, 5, 'This report includes all transactions recorded in the system up to ' . date('Y-m-d'), 0, 1, 'C');
    $pdf->Cell(190, 5, 'For any questions regarding this report, please contact the management team.', 0, 1, 'C');

    // Output PDF
    $pdf->Output('D', 'Sales_Report_' . date('Y-m-d') . '.pdf');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Report - Premium Living</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
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

        /* ===== FILTER BAR ===== */
        .filter-bar {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
            margin-top: 1rem;
        }

        .filter-select {
            padding: 0.6rem 1.2rem;
            border: 1.5px solid var(--gray-wood);
            border-radius: var(--radius-btn);
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
            background: white;
            color: var(--wood-dark);
            outline: none;
            transition: all 0.3s;
        }

        .filter-select:focus {
            border-color: var(--accent-gold);
            box-shadow: 0 0 0 3px rgba(212, 163, 115, 0.15);
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

        .btn-pdf {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.5rem;
            border-radius: var(--radius-btn);
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
        }

        .btn-pdf:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
            color: white;
        }

        .btn-pdf i {
            font-size: 1.1rem;
        }

        /* ===== STATS GRID ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            padding: 1.5rem 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius-card);
            text-align: center;
            box-shadow: var(--shadow-soft);
            transition: all 0.3s;
            border-left: 5px solid var(--accent-gold);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-warm);
        }

        .stat-card .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--wood-dark);
        }

        .stat-card .stat-label {
            color: var(--wood-light);
            font-size: 0.85rem;
            font-weight: 500;
            margin-top: 0.3rem;
        }

        .stat-card .stat-label i {
            color: var(--accent-gold);
            margin-right: 0.3rem;
        }

        /* ===== CHART ===== */
        .chart-container {
            padding: 1.5rem 2rem;
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
            vertical-align: middle;
        }

        .table-container tbody tr:hover {
            background: rgba(212, 163, 115, 0.05);
        }

        .table-container tfoot td {
            font-weight: 700;
            font-size: 1rem;
            background: var(--cream);
        }

        .product-thumb {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 6px;
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

            .stats-grid {
                grid-template-columns: 1fr;
                padding: 1rem;
            }

            .card-header {
                padding: 1rem;
            }

            .card-header h2 {
                font-size: 1.1rem;
            }

            .filter-bar {
                flex-direction: column;
                align-items: stretch;
            }

            .filter-bar .btn,
            .filter-bar .btn-pdf,
            .filter-bar .filter-select {
                width: 100%;
                justify-content: center;
            }

            .chart-container {
                padding: 1rem;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0 1rem;
            }

            .stats-grid {
                gap: 1rem;
            }

            .stat-card .stat-number {
                font-size: 2rem;
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
        <li><a href="generate-report.php" class="active"><i class="fas fa-file-alt"></i> Generate Report</a></li>
        <li><a href="delete-furniture.php"><i class="fas fa-trash"></i> Delete Furniture</a></li>
        <li><a href="../index.php?action=logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
</nav>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-chart-line"></i> Sales Analytics</h2>
            <div class="filter-bar">
                <select id="rangeSelect" class="filter-select">
                    <option value="7">Last 7 Days</option>
                    <option value="30" selected>Last 30 Days</option>
                    <option value="90">Last 3 Months</option>
                    <option value="365">Last Year</option>
                    <option value="all">All Time</option>
                </select>
                <button class="btn btn-primary" onclick="refreshReport()"><i class="fas fa-sync"></i> Generate</button>
                <a href="?export=pdf" class="btn-pdf">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </a>
                <button class="btn btn-success" onclick="exportCSV()"><i class="fas fa-file-csv"></i> CSV</button>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number" id="totalSales">$0</div>
                <div class="stat-label"><i class="fas fa-dollar-sign"></i> Total Revenue</div>
            </div>
            <div class="stat-card" style="border-left-color: #2a9d8f;">
                <div class="stat-number" id="orderCount">0</div>
                <div class="stat-label"><i class="fas fa-shopping-cart"></i> Total Orders</div>
            </div>
            <div class="stat-card" style="border-left-color: #e76f51;">
                <div class="stat-number" id="itemsCount">0</div>
                <div class="stat-label"><i class="fas fa-box"></i> Items Sold</div>
            </div>
        </div>

        <div class="chart-container">
            <canvas id="revenueChart" height="250"></canvas>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-receipt"></i> Transaction Details</h2>
        </div>
        <div class="table-container">
            <table id="reportTable">
                <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Date</th>
                    <th>Image</th>
                    <th>Item</th>
                    <th>Qty</th>
                    <th>Unit Price</th>
                    <th>Total</th>
                </tr>
                </thead>
                <tbody id="reportBody"></tbody>
                <tfoot>
                <tr>
                    <td colspan="6" style="text-align:right;"><strong>Grand Total:</strong></td>
                    <td id="grandTotal">$0</td>
                </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<script>
    const salesData = <?php echo json_encode($sales_records); ?>;
    let chart;

    function refreshReport() {
        const days = document.getElementById('rangeSelect').value;
        let filtered = salesData;

        if (days !== 'all') {
            const cutoff = new Date();
            cutoff.setDate(cutoff.getDate() - parseInt(days));
            filtered = salesData.filter(d => new Date(d.date) >= cutoff);
        }

        const total = filtered.reduce((s, i) => s + parseFloat(i.total), 0);
        const orders = new Set(filtered.map(i => i.oid)).size;
        const items = filtered.reduce((s, i) => s + parseInt(i.qty), 0);

        document.getElementById('totalSales').innerHTML = `$${total.toLocaleString(undefined, {minimumFractionDigits: 2})}`;
        document.getElementById('orderCount').innerHTML = orders;
        document.getElementById('itemsCount').innerHTML = items;
        document.getElementById('grandTotal').innerHTML = `$${total.toLocaleString(undefined, {minimumFractionDigits: 2})}`;

        const tbody = document.getElementById('reportBody');
        tbody.innerHTML = '';

        filtered.forEach(i => {
            const r = tbody.insertRow();
            r.insertCell(0).innerHTML = '#' + i.oid;
            r.insertCell(1).innerText = i.date.substring(0, 10);

            // Image column
            let imageHtml = '';
            if (i.fimage && i.fimage !== '') {
                // Try to find the image
                const imagePath = '../' + i.fimage;
                imageHtml = `<img src="${imagePath}" width="40" height="40" style="border-radius:6px; object-fit:cover;" onerror="this.style.display='none'">`;
            } else {
                // Try default images
                const fid = i.oid; // This might not be accurate, we need to get fid from somewhere else
                // For now, show placeholder
                imageHtml = `<i class="fas fa-chair" style="font-size:1.5rem; color:#d4a373;"></i>`;
            }
            r.insertCell(2).innerHTML = imageHtml;
            r.insertCell(3).innerText = i.item;
            r.insertCell(4).innerText = i.qty;
            r.insertCell(5).innerHTML = `$${parseFloat(i.unit_price).toFixed(2)}`;
            r.insertCell(6).innerHTML = `<strong>$${parseFloat(i.total).toFixed(2)}</strong>`;
        });

        const daily = {};
        filtered.forEach(i => {
            const d = i.date.substring(0, 10);
            daily[d] = (daily[d] || 0) + parseFloat(i.total);
        });

        const labels = Object.keys(daily).sort();
        const values = labels.map(l => daily[l]);

        if(chart) chart.destroy();
        const ctx = document.getElementById('revenueChart').getContext('2d');
        chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: 'Daily Sales ($)',
                    data: values,
                    borderColor: '#8b5e3c',
                    backgroundColor: 'rgba(139,94,60,0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.3,
                    pointBackgroundColor: '#d4a373',
                    pointBorderColor: '#5c3d2e',
                    pointBorderWidth: 2,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        labels: {
                            font: { size: 12, weight: 'bold' },
                            color: '#3e2a21'
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    }

    function exportCSV() {
        let csv = [["Order ID","Date","Item","Qty","Unit Price","Total"]];
        document.querySelectorAll('#reportBody tr').forEach(r => {
            const cells = r.cells;
            csv.push([
                cells[0].innerText,
                cells[1].innerText,
                cells[2].innerText,
                cells[3].innerText,
                cells[4].innerText.replace('$',''),
                cells[5].innerText.replace('$','')
            ]);
        });
        const blob = new Blob([csv.map(r => r.join(',')).join('\n')], {type:'text/csv'});
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = `report_${new Date().toISOString().slice(0,10)}.csv`;
        a.click();
    }

    // Auto-refresh on load
    refreshReport();
</script>
<footer>
    <p>&copy; 2026 Premium Living | Data-Driven Craftsmanship</p>
</footer>
</body>
</html>