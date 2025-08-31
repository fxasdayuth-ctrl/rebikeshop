<?php
// admin/orders/view.php
require_once '../../includes/db_connect.php'; // ย้อนกลับสองระดับจาก admin/orders/
require_once '../../includes/functions.php'; // รวม functions.php ที่มี getStatusThai()

// ตรวจสอบว่ามี order_id ถูกส่งมาหรือไม่
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    header('Location: ../../customer/track.php?error=missing_order_id');
    exit();
}

$order_id = intval($_GET['order_id']);

try {
    // ดึงข้อมูลคำสั่งซ่อม
    $stmt = $pdo->prepare("
        SELECT ro.*, c.first_name, c.last_name, c.phone, c.email, 
               b.brand, b.model, b.color, bt.name as bike_type
        FROM repair_orders ro
        JOIN customers c ON ro.customer_id = c.id
        JOIN bikes b ON ro.bike_id = b.id
        LEFT JOIN bike_types bt ON b.bike_type_id = bt.id
        WHERE ro.id = :order_id
    ");
    $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
    $stmt->execute();
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        header('Location: ../../customer/track.php?error=order_not_found');
        exit();
    }
    
    // ดึงรายการซ่อม
    $stmt = $pdo->prepare("
        SELECT * FROM repair_items 
        WHERE order_id = :order_id 
        ORDER BY id
    ");
    $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
    $stmt->execute();
    $repair_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ดึงประวัติสถานะ
    $stmt = $pdo->prepare("
        SELECT * FROM status_updates 
        WHERE order_id = :order_id 
        ORDER BY update_time DESC
    ");
    $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
    $stmt->execute();
    $status_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    header('Location: ../../customer/track.php?error=database_error');
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดคำสั่งซ่อม - ReBikeShop</title>
    <link rel="stylesheet" href="../../css/style.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container">
        <h1>รายละเอียดคำสั่งซ่อม</h1>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php
                $messages = [
                    'status_updated' => 'อัพเดทสถานะเรียบร้อยแล้ว'
                ];
                echo $messages[$_GET['success']] ?? 'ดำเนินการเรียบร้อย';
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <?php
                $errors = [
                    'database' => 'เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล',
                    'invalid_request' => 'คำขอไม่ถูกต้อง'
                ];
                echo $errors[$_GET['error']] ?? 'เกิดข้อผิดพลาด';
                ?>
            </div>
        <?php endif; ?>
        
        <div class="order-details">
            <div class="card">
                <h2>ข้อมูลคำสั่งซ่อม #<?php echo htmlspecialchars($order['order_number']); ?></h2>
                
                <div class="grid-container">
                    <div class="grid-item">
                        <h3>สถานะปัจจุบัน</h3>
                        <div class="status-badge status-<?php echo $order['status']; ?>">
                            <?php echo getStatusThai($order['status']); ?>
                        </div>
                        <p><strong>วันที่รับงาน:</strong> <?php echo thai_date($order['received_date']); ?></p>
                        <?php if ($order['estimated_completion']): ?>
                        <p><strong>กำหนดเสร็จโดยประมาณ:</strong> <?php echo thai_date($order['estimated_completion']); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="grid-item">
                        <h3>ข้อมูลลูกค้า</h3>
                        <p><strong>ชื่อ-นามสกุล:</strong> <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></p>
                        <p><strong>โทรศัพท์:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
                        <?php if ($order['email']): ?>
                        <p><strong>อีเมล:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="grid-item">
                        <h3>ข้อมูลจักรยาน</h3>
                        <p><strong>ประเภท:</strong> <?php echo htmlspecialchars($order['bike_type'] ?? '-'); ?></p>
                        <p><strong>ยี่ห้อ/รุ่น:</strong> <?php echo htmlspecialchars($order['brand'] . ' ' . ($order['model'] ?? '')); ?></p>
                        <p><strong>สี:</strong> <?php echo htmlspecialchars($order['color'] ?? '-'); ?></p>
                    </div>
                </div>
                
                <?php if ($order['notes']): ?>
                <div class="notes">
                    <h3>หมายเหตุ</h3>
                    <p><?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($repair_items)): ?>
            <div class="card">
                <h2>รายการซ่อม</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>รายการ</th>
                            <th>ค่าใช้จ่าย</th>
                            <th>หมายเหตุ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($repair_items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['description']); ?></td>
                            <td class="text-right"><?php echo number_format($item['cost'], 2); ?> บาท</td>
                            <td><?php echo nl2br(htmlspecialchars($item['notes'] ?? '-')); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="total-row">
                            <td colspan="3" class="text-right">
                                <strong>รวมทั้งหมด: <?php echo number_format($order['total_cost'] ?? 0, 2); ?> บาท</strong>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <h2>ประวัติสถานะ</h2>
                <div class="timeline">
                    <?php if (!empty($status_history)): ?>
                        <?php foreach ($status_history as $update): ?>
                        <div class="timeline-item">
                            <div class="timeline-date"><?php echo thai_date_time($update['update_time']); ?></div>
                            <div class="timeline-content">
                                <div class="status-badge status-<?php echo $update['status']; ?>">
                                    <?php echo getStatusThai($update['status']); ?>
                                </div>
                                <?php if ($update['notes']): ?>
                                <p><?php echo nl2br(htmlspecialchars($update['notes'])); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>ไม่มีประวัติสถานะ</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="actions">
                <a href="../../customer/track.php" class="btn btn-secondary">กลับไปหน้าติดตามคำสั่งซ่อม</a>
            </div>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>