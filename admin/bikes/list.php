```php
<?php
session_start();

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

// ตรวจสอบว่าไฟล์ db_connect.php มีอยู่หรือไม่
if (!file_exists('../includes/db_connect.php')) {
    die("ข้อผิดพลาด: ไม่พบไฟล์ db_connect.php ในโฟลเดอร์ includes");
}
require_once '../includes/db_connect.php';

// ดึงข้อมูลจักรยานทั้งหมด
try {
    $stmt = $conn->prepare("
        SELECT b.id, b.brand, b.model, b.color, b.serial_number, b.purchase_date, 
               bt.name AS bike_type, c.first_name, c.last_name
        FROM bikes b
        LEFT JOIN bike_types bt ON b.bike_type_id = bt.id
        LEFT JOIN customers c ON b.customer_id = c.id
        ORDER BY b.created_at DESC
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    $bikes = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $e) {
    $error = 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการจักรยาน - RebikeShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .table-container {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>รายการจักรยาน</h2>
            <a href="add.php" class="btn btn-primary">เพิ่มจักรยานใหม่</a>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (empty($bikes)): ?>
            <div class="alert alert-info">ไม่มีข้อมูลจักรยาน</div>
        <?php else: ?>
            <div class="table-container">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>ยี่ห้อ</th>
                            <th>รุ่น</th>
                            <th>สี</th>
                            <th>หมายเลขซีเรียล</th>
                            <th>วันที่ซื้อ</th>
                            <th>ประเภท</th>
                            <th>เจ้าของ</th>
                            <th>การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($bikes as $index => $bike): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td><?= htmlspecialchars($bike['brand']) ?></td>
                                <td><?= htmlspecialchars($bike['model'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($bike['color'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($bike['serial_number'] ?? '-') ?></td>
                                <td><?= $bike['purchase_date'] ? date('d/m/Y', strtotime($bike['purchase_date'])) : '-' ?></td>
                                <td><?= htmlspecialchars($bike['bike_type'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($bike['first_name'] . ' ' . $bike['last_name']) ?></td>
                                <td>
                                    <a href="edit.php?id=<?= $bike['id'] ?>" class="btn btn-sm btn-warning">แก้ไข</a>
                                    <!-- ปุ่มลบสามารถเพิ่มได้ในอนาคต -->
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="mt-3">
            <a href="../dashboard.php" class="btn btn-secondary">กลับไปที่แดชบอร์ด</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
```