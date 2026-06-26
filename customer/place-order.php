<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['cid'])) {
    header("Location: login.php");
    exit();
}

require_once '../conn.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quantities = $_POST['quantity'] ?? [];
    $delivery_date = $_POST['delivery_date'] ?? '';
    $delivery_address = trim($_POST['delivery_address'] ?? '');
    $customer_id = $_SESSION['cid'];

    // Filter down to only items with a selected quantity greater than 0
    $ordered_items = [];
    foreach ($quantities as $fid => $qty) {
        $qty = intval($qty);
        if ($qty > 0) {
            $ordered_items[$fid] = $qty;
        }
    }

    if (empty($ordered_items) || empty($delivery_date) || empty($delivery_address)) {
        die("<script>alert('Error: Please select at least one furniture item and complete all form requirements.'); window.history.back();</script>");
    }

    try {
        // Start an InnoDB Transaction block to ensure system safety and balance consistency
        $pdo->beginTransaction();

        $total_amount = 0.00;
        $required_materials = [];

        // Loop through choices to figure out cumulative materials costs and calculate bill summaries
        foreach ($ordered_items as $fid => $order_qty) {
            // Get item unit pricing metrics
            $fStmt = $pdo->prepare("SELECT fprice, fname FROM Furnitures WHERE fid = :fid");
            $fStmt->execute(['fid' => $fid]);
            $item = $fStmt->fetch(PDO::FETCH_ASSOC);

            if (!$item) {
                throw new Exception("Furniture Product ID record #$fid does not exist.");
            }

            $total_amount += ($item['fprice'] * $order_qty);

            // Fetch structural recipe requirement instructions for this specific collection item
            $mStmt = $pdo->prepare("SELECT mid, pmqty FROM FurnitureMaterials WHERE fid = :fid");
            $mStmt->execute(['fid' => $fid]);
            $recipes = $mStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($recipes as $recipe) {
                $mid = $recipe['mid'];
                $total_needed = $recipe['pmqty'] * $order_qty;

                if (!isset($required_materials[$mid])) {
                    $required_materials[$mid] = 0;
                }
                $required_materials[$mid] += $total_needed;
            }
        }

        // Verify warehouse inventory quantities before proceeding with the transaction
        foreach ($required_materials as $mid => $qty_needed) {
            $invStmt = $pdo->prepare("SELECT mqty, mname FROM Materials WHERE mid = :mid FOR UPDATE");
            $invStmt->execute(['mid' => $mid]);
            $material = $invStmt->fetch(PDO::FETCH_ASSOC);

            if (!$material || $material['mqty'] < $qty_needed) {
                throw new Exception("Insufficient raw materials inventory storage stock for item: " . ($material['mname'] ?? 'Unknown Material'));
            }
        }

        // 1. Insert parent record entry into Orders table
        $orderSql = "INSERT INTO Orders (ototalamount, cid, odeliverydate, odeliveraddress, ostatus) 
                     VALUES (:total, :cid, :ddate, :daddr, 1)";
        $orderStmt = $pdo->prepare($orderSql);
        $orderStmt->execute([
            'total' => $total_amount,
            'cid'   => $customer_id,
            'ddate' => $delivery_date,
            'daddr' => $delivery_address
        ]);
        $new_oid = $pdo->lastInsertId();

        // 2. Insert rows into the OrderFurnitures mapping table and decrement warehouse inventory quantities
        foreach ($ordered_items as $fid => $order_qty) {
            $itemSql = "INSERT INTO OrderFurnitures (oid, fid, oqty) VALUES (:oid, :fid, :qty)";
            $pdo->prepare($itemSql)->execute(['oid' => $new_oid, 'fid' => $fid, 'qty' => $order_qty]);
        }

        // 3. Deduct materials from Materials table
        foreach ($required_materials as $mid => $qty_needed) {
            $deductSql = "UPDATE Materials SET mqty = mqty - :deduct WHERE mid = :mid";
            $pdo->prepare($deductSql)->execute(['deduct' => $qty_needed, 'mid' => $mid]);
        }

        // Everything looks perfect, save all data structural mutations safely to the disk!
        $pdo->commit();

        echo "<script>alert('Success! Your Artisanal Order #' + $new_oid + ' has been placed successfully.'); window.location.href='view-orders.php';</script>";
        exit();

    } catch (Exception $e) {
        // Something went wrong, roll back any broken operations safely to keep stock calculations intact!
        $pdo->rollBack();
        echo "<script>alert('Checkout Transaction Aborted: " . addslashes($e->getMessage()) . "'); window.history.back();</script>";
        exit();
    }
}
?>