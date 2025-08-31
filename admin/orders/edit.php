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
$repair_items = [];
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
    
    // ดึงรายการซ่อม
    $stmt = $pdo->prepare("SELECT * FROM repair_items WHERE order_id = ? ORDER BY id");
    $stmt->execute([$order_id]);
    $repair_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "ไม่สามารถโหลดข้อมูล: " . $e->getMessage();
}

// จัดการฟอร์มส่งข้อมูล
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $estimated_completion = !empty($_POST['estimated_completion']) ? $_POST['estimated_completion'] : null;
    $notes = trim($_POST['notes'] ?? '');
    
    try {
        $pdo->beginTransaction();
        
        // อัพเดทคำสั่งซ่อม
        $stmt = $pdo->prepare("
            UPDATE repair_orders 
            SET estimated_completion = ?, notes = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        $stmt->execute([$estimated_completion, $notes, $order_id]);
        
        // ลบรายการซ่อมเดิม
        $stmt = $pdo->prepare("DELETE FROM repair_items WHERE order_id = ?");
        $stmt->execute([$order_id]);
        
        // เพิ่มรายการซ่อมใหม่
        if (isset($_POST['repair_items']) && is_array($_POST['repair_items'])) {
            foreach ($_POST['repair_items'] as $item) {
                if (!empty($item['description'])) {
                    $stmt = $pdo->prepare("
                        INSERT INTO repair_items (order_id, description, cost, notes)
                        VALUES (?, ?, ?, ?)
                    ");
                    $cost = !empty($item['cost']) ? floatval($item['cost']) : 0;
                    $stmt->execute([$order_id, $item['description'], $cost, $item['notes'] ?? '']);
                }
            }
        }
        
        // คำนวณยอดรวมใหม่
        $stmt = $pdo->prepare("
            UPDATE repair_orders 
            SET total_cost = (SELECT COALESCE(SUM(cost), 0) FROM repair_items WHERE order_id = ?),
                updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        $stmt->execute([$order_id, $order_id]);
        
        $pdo->commit();
        
        header('Location: list.php?success=updated');
        exit;
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "เกิดข้อผิดพลาดในการอัพเดทข้อมูล: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขคำสั่งซ่อม - ReBikeShop</title>
    <link rel="stylesheet" href="../../css/style.css">
    <script src="../../js/script.js" defer></script>
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container">
        <h1>แก้ไขคำสั่งซ่อม #<?= htmlspecialchars($order['order_number']) ?></h1>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h2>ข้อมูลพื้นฐาน</h2>
            <p><strong>ลูกค้า:</strong> <?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></p>
            <p><strong>เบอร์โทร:</strong> <?= htmlspecialchars($order['phone']) ?></p>
            <p><strong>จักรยาน:</strong> <?= htmlspecialchars($order['brand'] . ' ' . ($order['model'] ?? '')) ?></p>
            <p><strong>สถานะ:</strong> 
                <span class="status-badge status-<?= $order['status'] ?>">
                    <?= getStatusThai($order['status']) ?>
                </span>
            </p>
            <p><strong>วันที่รับ:</strong> <?= thai_date($order['received_date']) ?></p>
        </div>
        
        <form method="POST" class="form">
            <div class="form-group">
                <label for="estimated_completion">กำหนดเสร็จโดยประมาณ</label>
                <input type="date" name="estimated_completion" id="estimated_completion" 
                       value="<?= $order['estimated_completion'] ?>">
            </div>
            
            <div class="form-group">
                <label for="notes">หมายเหตุ</label>
                <textarea name="notes" id="notes" rows="3"><?= htmlspecialchars($order['notes'] ?? '') ?></textarea>
            </div>
            
            <div class="card">
                <h2>รายการซ่อม</h2>
                <div id="repair-items">
                    <?php if (!empty($repair_items)): ?>
                        <?php foreach ($repair_items as $index => $item): ?>
                        <div class="repair-item">
                            <div class="form-group">
                                <label>รายการซ่อม</label>
                                <input type="text" name="repair_items[<?= $index ?>][description]" 
                                       value="<?= htmlspecialchars($item['description']) ?>" 
                                       placeholder="ระบุรายการซ่อม" required>
                            </div>
                            <div class="form-group">
                                <label>ค่าใช้จ่าย (บาท)</label>
                                <input type="number" name="repair_items[<?= $index ?>][cost]" 
                                       value="<?= $item['cost'] ?>" step="0.01" min="0" placeholder="0.00">
                            </div>
                            <div class="form-group">
                                <label>หมายเหตุ</label>
                                <input type="text" name="repair_items[<?= $index ?>][notes]" 
                                       value="<?= htmlspecialchars($item['notes'] ?? '') ?>" 
                                       placeholder="หมายเหตุเพิ่มเติม">
                            </div>
                            <button type="button" class="btn btn-danger btn-sm" onclick="removeItem(this)">ลบ</button>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="repair-item">
                            <div class="form-group">
                                <label>รายการซ่อม</label>
                                <input type="text" name="repair_items[0][description]" 
                                       placeholder="ระบุรายการซ่อม" required>
                            </div>
                            <div class="form-group">
                                <label>ค่าใช้จ่าย (บาท)</label>
                                <input type="number" name="repair_items[0][cost]" 
                                       step="0.01" min="0" placeholder="0.00">
                            </div>
                            <div class="form-group">
                                <label>หมายเหตุ</label>
                                <input type="text" name="repair_items[0][notes]" 
                                       placeholder="หมายเหตุเพิ่มเติม">
                            </div>
                            <button type="button" class="btn btn-danger btn-sm" onclick="removeItem(this)">ลบ</button>
                        </div>
                    <?php endif; ?>
                </div>
                
                <button type="button" class="btn btn-secondary" onclick="addRepairItem()">+ เพิ่มรายการซ่อม</button>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-success">บันทึกการเปลี่ยนแปลง</button>
                <a href="list.php" class="btn btn-secondary">ยกเลิก</a>
            </div>
        </form>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
    
    <script>
    let itemCount = <?= count($repair_items) ?: 1 ?>;
    
    function addRepairItem() {
        const container = document.getElementById('repair-items');
        const newItem = document.createElement('div');
        newItem.className = 'repair-item';
        newItem.innerHTML = `
            <div class="form-group">
                <label>รายการซ่อม</label>
                <input type="text" name="repair_items[${itemCount}][description]" 
                       placeholder="ระบุรายการซ่อม" required>
            </div>
            <div class="form-group">
                <label>ค่าใช้จ่าย (บาท)</label>
                <input type="number" name="repair_items[${itemCount}][cost]" 
                       step="0.01" min="0" placeholder="0.00">
            </div>
            <div class="form-group">
                <label>หมายเหตุ</label>
                <input type="text" name="repair_items[${itemCount}][notes]" 
                       placeholder="หมายเหตุเพิ่มเติม">
            </div>
            <button type="button" class="btn btn-danger btn-sm" onclick="removeItem(this)">ลบ</button>
        `;
        container.appendChild(newItem);
        itemCount++;
    }
    
    function removeItem(button) {
        if (document.querySelectorAll('.repair-item').length > 1) {
            button.parentElement.remove();
        } else {
            alert('ต้องมีอย่างน้อย 1 รายการซ่อม');
        }
    }
    </script>
</body>
</html>