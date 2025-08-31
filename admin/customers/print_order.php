<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// ตรวจสอบสิทธิ์ admin เท่านั้น
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    die("คุณไม่มีสิทธิ์เข้าถึงหน้านี้");
}

$order = null;
$status_updates = [];
$repair_items = [];

if (isset($_GET['order_number'])) {
    $order_number = trim($_GET['order_number']);
    
    try {
        $stmt = $pdo->prepare("
            SELECT ro.*, c.first_name, c.last_name, c.phone, c.email, c.address, c.district, c.province, c.zip_code, c.notes as customer_notes,
                   b.brand, b.model, b.color, b.serial_number, b.purchase_date, bt.name as bike_type, bt.description as bike_type_desc
            FROM repair_orders ro
            JOIN customers c ON ro.customer_id = c.id
            JOIN bikes b ON ro.bike_id = b.id
            LEFT JOIN bike_types bt ON b.bike_type_id = bt.id
            WHERE ro.order_number = :order_number
        ");
        $stmt->bindParam(':order_number', $order_number, PDO::PARAM_STR);
        $stmt->execute();
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($order) {
            $stmt_updates = $pdo->prepare("
                SELECT su.*, ro.order_number
                FROM status_updates su
                JOIN repair_orders ro ON su.order_id = ro.id
                WHERE su.order_id = :order_id
                ORDER BY su.update_time DESC
            ");
            $stmt_updates->bindParam(':order_id', $order['id'], PDO::PARAM_INT);
            $stmt_updates->execute();
            $status_updates = $stmt_updates->fetchAll(PDO::FETCH_ASSOC);

            $stmt_items = $pdo->prepare("
                SELECT ri.*, p.name as part_name
                FROM repair_items ri
                LEFT JOIN parts p ON ri.part_id = p.id
                WHERE ri.order_id = :order_id
            ");
            $stmt_items->bindParam(':order_id', $order['id'], PDO::PARAM_INT);
            $stmt_items->execute();
            $repair_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        die("เกิดข้อผิดพลาด: " . $e->getMessage());
    }
}

if (!$order) {
    die("ไม่พบคำสั่งซ่อมสำหรับเลขที่ออเดอร์นี้");
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>พิมพ์คำสั่งซ่อม #<?php echo htmlspecialchars($order['order_number']); ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20mm; }
        .header { text-align: center; margin-bottom: 20mm; }
        .header h1 { margin: 0; }
        .section { margin-bottom: 10mm; }
        .section h2 { border-bottom: 1px solid #000; padding-bottom: 5px; }
        .details p { margin: 5px 0; }
        .list { list-style: none; padding: 0; }
        .list li { margin: 5px 0; }
        @media print {
            .no-print { display: none; }
            body { margin: 0; } /* Reset margin for print */
        }
    </style>
</head>
<body onload="window.print()">
    <div class="header">
        <h1>RebikeShop - คำสั่งซ่อม</h1>
        <p>เลขที่ออเดอร์: <?php echo htmlspecialchars($order['order_number']); ?></p>
        <p>วันที่พิมพ์: <?php echo date('d/m/Y H:i', strtotime('now')); ?> (เวลาไทย)</p>
    </div>

    <div class="section">
        <h2>ข้อมูลลูกค้า</h2>
        <div class="details">
            <p><strong>ชื่อ:</strong> <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></p>
            <p><strong>เบอร์โทร:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
            <p><strong>อีเมล:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
            <p><strong>ที่อยู่:</strong> <?php echo htmlspecialchars($order['address'] . ', ' . $order['district'] . ', ' . $order['province'] . ' ' . $order['zip_code']); ?></p>
            <p><strong>หมายเหตุ:</strong> <?php echo htmlspecialchars($order['customer_notes'] ?: '-'); ?></p>
        </div>
    </div>

    <div class="section">
        <h2>ข้อมูลจักรยาน</h2>
        <div class="details">
            <p><strong>ยี่ห้อ:</strong> <?php echo htmlspecialchars($order['brand']); ?></p>
            <p><strong>รุ่น:</strong> <?php echo htmlspecialchars($order['model']); ?></p>
            <p><strong>สี:</strong> <?php echo htmlspecialchars($order['color']); ?></p>
            <p><strong>หมายเลขซีเรียล:</strong> <?php echo htmlspecialchars($order['serial_number']); ?></p>
            <p><strong>วันที่ซื้อ:</strong> <?php echo $order['purchase_date'] ? date('d/m/Y', strtotime($order['purchase_date'])) : '-'; ?></p>
            <p><strong>ประเภท:</strong> <?php echo htmlspecialchars($order['bike_type'] ?: '-'); ?></p>
            <p><strong>คำอธิบายประเภท:</strong> <?php echo htmlspecialchars($order['bike_type_desc'] ?: '-'); ?></p>
        </div>
    </div>

    <div class="section">
        <h2>รายการซ่อม</h2>
        <?php if (count($repair_items) > 0): ?>
            <ul class="list">
                <?php foreach ($repair_items as $item): ?>
                    <li>
                        <?php echo htmlspecialchars($item['part_name'] ?: 'ไม่มีชื่อชิ้นส่วน'); ?> - 
                        จำนวน: <?php echo $item['quantity']; ?> - 
                        ราคา: <?php echo number_format($item['price'], 2); ?> บาท
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="text-muted">ไม่มีรายการซ่อม</p>
        <?php endif; ?>
    </div>

    <div class="section">
        <h2>ประวัติสถานะ</h2>
        <?php if (count($status_updates) > 0): ?>
            <ul class="list">
                <?php foreach ($status_updates as $update): ?>
                    <li>
                        <strong><?php echo date('d/m/Y H:i', strtotime($update['update_time'])); ?></strong>: 
                        <?php echo htmlspecialchars(getStatusThai($update['status'])); ?>
                        <?php if ($update['notes']): ?>
                            - <em><?php echo htmlspecialchars($update['notes']); ?></em>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="text-muted">ไม่มีประวัติสถานะ</p>
        <?php endif; ?>
    </div>

    <div class="no-print">
        <p>ปิดหน้านี้หลังจากพิมพ์เสร็จสิ้น</p>
    </div>
</body>
</html>