<?php
require_once '../../includes/db_connect.php';
require_once '../../includes/functions.php';

// ตรวจสอบการล็อกอิน
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

// ตัวกรองและการค้นหา
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// สร้างคำสั่ง SQL
$sql = "SELECT ro.*, c.first_name, c.last_name, c.phone, b.brand, b.model 
        FROM repair_orders ro 
        JOIN customers c ON ro.customer_id = c.id 
        JOIN bikes b ON ro.bike_id = b.id 
        WHERE 1=1";

$params = [];

if (!empty($status_filter)) {
    $sql .= " AND ro.status = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $sql .= " AND (ro.order_number LIKE ? OR c.first_name LIKE ? OR c.last_name LIKE ? OR c.phone LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
}

$sql .= " ORDER BY ro.received_date DESC";

// ดึงข้อมูล
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการคำสั่งซ่อม - ReBikeShop</title>
    <link rel="stylesheet" href="../../css/style.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>
    
    <div class="container">
        <h1>จัดการคำสั่งซ่อม</h1>
        
        <div class="filters">
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label for="status">สถานะ:</label>
                    <select name="status" id="status">
                        <option value="">ทั้งหมด</option>
                        <option value="received" <?= $status_filter == 'received' ? 'selected' : '' ?>>รับงานแล้ว</option>
                        <option value="diagnosing" <?= $status_filter == 'diagnosing' ? 'selected' : '' ?>>กำลังตรวจสอบ</option>
                        <option value="repairing" <?= $status_filter == 'repairing' ? 'selected' : '' ?>>กำลังซ่อม</option>
                        <option value="waiting_parts" <?= $status_filter == 'waiting_parts' ? 'selected' : '' ?>>รออะไหล่</option>
                        <option value="completed" <?= $status_filter == 'completed' ? 'selected' : '' ?>>ซ่อมเสร็จแล้ว</option>
                        <option value="delivered" <?= $status_filter == 'delivered' ? 'selected' : '' ?>>ส่งมอบแล้ว</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="search">ค้นหา:</label>
                    <input type="text" name="search" id="search" value="<?= htmlspecialchars($search) ?>" 
                           placeholder="เลขที่ออเดอร์, ชื่อลูกค้า, เบอร์โทร">
                </div>
                
                <button type="submit" class="btn btn-primary">ค้นหา</button>
                <a href="list.php" class="btn btn-secondary">ล้าง</a>
            </form>
        </div>
        
        <div class="actions">
            <a href="add.php" class="btn btn-success">เพิ่มคำสั่งซ่อมใหม่</a>
        </div>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php
                $messages = [
                    'added' => 'เพิ่มคำสั่งซ่อมเรียบร้อยแล้ว',
                    'updated' => 'อัพเดทคำสั่งซ่อมเรียบร้อยแล้ว',
                    'status_updated' => 'อัพเดทสถานะเรียบร้อยแล้ว'
                ];
                echo $messages[$_GET['success']] ?? 'ดำเนินการเรียบร้อย';
                ?>
            </div>
        <?php endif; ?>
        
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>เลขที่ออเดอร์</th>
                        <th>ลูกค้า</th>
                        <th>เบอร์โทร</th>
                        <th>จักรยาน</th>
                        <th>วันที่รับ</th>
                        <th>สถานะ</th>
                        <th>ประมาณเสร็จ</th>
                        <th>รวมค่าใช้จ่าย</th>
                        <th>การดำเนินการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="9" class="text-center">ไม่พบข้อมูลคำสั่งซ่อม</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?= htmlspecialchars($order['order_number']) ?></td>
                            <td><?= htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) ?></td>
                            <td><?= htmlspecialchars($order['phone']) ?></td>
                            <td><?= htmlspecialchars($order['brand'] . ' ' . ($order['model'] ?? '')) ?></td>
                            <td><?= thai_date($order['received_date']) ?></td>
                            <td>
                                <span class="status-badge status-<?= $order['status'] ?>">
                                    <?= getStatusThai($order['status']) ?>
                                </span>
                            </td>
                            <td><?= $order['estimated_completion'] ? thai_date($order['estimated_completion']) : '-' ?></td>
                            <td class="text-right"><?= $order['total_cost'] ? number_format($order['total_cost'], 2) . ' บาท' : '-' ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="edit.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-primary">แก้ไข</a>
                                    <a href="../customer/view_order.php?order_id=<?= $order['id'] ?>" class="btn btn-sm btn-info">ดู</a>
                                    <a href="update_status.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-warning">อัพเดทสถานะ</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <?php include '../../includes/footer.php'; ?>
</body>
</html>