<?php
require_once '../../includes/db_connect.php';
require_once '../../includes/functions.php';

// ตรวจสอบการล็อกอิน
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

// ตรวจสอบว่ามี ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: list.php?error=missing_id');
    exit;
}

$order_id = intval($_GET['id']);
$order = [];
$error = '';

// ดึงข้อมูลคำสั่งซ่อม
try {
    $stmt = $pdo->prepare("
        SELECT ro.*, c.first_name, c.last_name, c.phone, b.brand, b.model 
        FROM repair_orders ro 
        JOIN customers c ON ro.customer_id = c.id 
        JOIN bikes b ON ro.bike_id = b.id 
        WHERE ro.id = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        header('Location: list.php?error=order_not_found');
        exit;
    }
    
} catch (PDOException $e) {
    $error = "ไม่สามารถโหลดข้อมูล: " . $e->getMessage();
}

// จัดการฟอร์มส่งข้อมูล
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_status = $_POST['status'];
    $status_notes = trim($_POST['status_notes'] ?? '');
    
    try {
        $pdo->beginTransaction();
        
        // อัพเดทสถานะคำสั่งซ่อม
        $stmt = $pdo->prepare("
            UPDATE repair_orders 
            SET status = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        $stmt->execute([$new_status, $order_id]);
        
        // บันทึกประวัติสถานะ
        $stmt = $pdo->prepare("
            INSERT INTO status_updates (order_id, status, notes)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$order_id, $new_status, $status_notes]);
        
        $pdo->commit();
        
        header('Location: list.php?success=status_updated');
        exit;
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "เกิดข้อผิดพลาดในการอัพเดทสถานะ: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>อัพเดทสถานะคำสั่งซ่อม - ReBikeShop</title>
    <link rel="stylesheet" href="../../css/style.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container">
        <h1>อัพเดทสถานะคำสั่งซ่อม</h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h2>ข้อมูลคำสั่งซ่อม</h2>
            <p><strong>เลขที่:</strong> <?= htmlspecialchars($order['order_number']) ?></p>
            <p><strong>ลูกค้า:</strong> <?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></p>
            <p><strong>เบอร์โทร:</strong> <?= htmlspecialchars($order['phone']) ?></p>
            <p><strong>จักรยาน:</strong> <?= htmlspecialchars($order['brand'] . ' ' . ($order['model'] ?? '')) ?></p>
            <p><strong>สถานะปัจจุบัน:</strong> 
                <span class="status-badge status-<?= $order['status'] ?>">
                    <?= getStatusThai($order['status']) ?>
                </span>
            </p>
        </div>
        
        <form method="POST" class="form">
            <div class="form-group">
                <label for="status">สถานะใหม่ *</label>
                <select name="status" id="status" required>
                    <option value="received" <?= $order['status'] == 'received' ? 'selected' : '' ?>>รับงานแล้ว</option>
                    <option value="diagnosing" <?= $order['status'] == 'diagnosing' ? 'selected' : '' ?>>กำลังตรวจสอบ</option>
                    <option value="repairing" <?= $order['status'] == 'repairing' ? 'selected' : '' ?>>กำลังซ่อม</option>
                    <option value="waiting_parts" <?= $order['status'] == 'waiting_parts' ? 'selected' : '' ?>>รออะไหล่</option>
                    <option value="completed" <?= $order['status'] == 'completed' ? 'selected' : '' ?>>ซ่อมเสร็จแล้ว</option>
                    <option value="delivered" <?= $order['status'] == 'delivered' ? 'selected' : '' ?>>ส่งมอบแล้ว</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="status_notes">หมายเหตุสถานะ</label>
                <textarea name="status_notes" id="status_notes" rows="3" 
                          placeholder="บันทึกข้อมูลเพิ่มเติมเกี่ยวกับสถานะนี้"></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-success">อัพเดทสถานะ</button>
                <a href="list.php" class="btn btn-secondary">ยกเลิก</a>
            </div>
        </form>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>