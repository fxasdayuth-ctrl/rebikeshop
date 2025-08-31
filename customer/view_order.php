<?php
// customer/view_order.php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// ตรวจสอบว่ามี order_id ถูกส่งมาหรือไม่
if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id']) || intval($_GET['order_id']) <= 0) {
    header('Location: track.php?error=invalid_order_id');
    exit();
}

$order_id = intval($_GET['order_id']);

try {
    // ดึงข้อมูลคำสั่งซ่อม
    $stmt = $pdo->prepare("
        SELECT ro.*, c.first_name, c.last_name, c.phone, c.email, c.address, c.district, c.province, c.zip_code, c.notes as customer_notes,
               b.brand, b.model, b.color, b.serial_number, b.purchase_date, bt.name as bike_type, bt.description as bike_type_desc
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
        header('Location: track.php?error=order_not_found');
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
    header('Location: track.php?error=database_error');
    exit();
}

// Function to convert status to Thai
if (!function_exists('getStatusThai')) {
    function getStatusThai($status) {
        $statuses = [
            'received' => 'รับงานแล้ว',
            'diagnosing' => 'กำลังวินิจฉัยปัญหา',
            'repairing' => 'กำลังซ่อม',
            'waiting_parts' => 'รออะไหล่',
            'completed' => 'ซ่อมเสร็จแล้ว',
            'delivered' => 'ส่งมอบแล้ว'
        ];
        return isset($statuses[$status]) ? $statuses[$status] : $status;
    }
}

// Function to convert date to Thai format
if (!function_exists('thai_date')) {
    function thai_date($date, $format = 'full') {
        if (!$date || $date === '0000-00-00 00:00:00') return '-';
        $thai_months = [
            1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
            5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
            9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม'
        ];
        $date_obj = new DateTime($date);
        $day = $date_obj->format('j');
        $month = (int)$date_obj->format('n');
        $year = $date_obj->format('Y') + 543;
        $time = $date_obj->format('H:i');

        if ($format === 'full') {
            return "$day {$thai_months[$month]} $year เวลา $time น.";
        } else {
            return "$day {$thai_months[$month]} $year";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดคำสั่งซ่อม - ReBikeShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Prompt', sans-serif;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card-header {
            border-radius: 10px 10px 0 0;
            padding: 1.5rem;
        }
        .card-body {
            padding: 2rem;
        }
        .btn-primary {
            background-color: #4a90e2;
            border-color: #4a90e2;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
        }
        .btn-primary:hover {
            background-color: #357abd;
            border-color: #357abd;
        }
        .btn-secondary {
            background-color: #6c757d;
            border-color: #6c757d;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
            border-color: #5a6268;
        }
        .badge {
            font-size: 0.9rem;
            padding: 0.5rem 1rem;
            border-radius: 12px;
        }
        .bg-soft-success { background-color: #28a745 !important; }
        .bg-soft-warning { background-color: #f1c40f !important; }
        .bg-soft-primary { background-color: #4a90e2 !important; }
        h1, h5, h6 {
            font-weight: 600;
            color: #2c3e50;
        }
        .text-muted { color: #6c757d !important; }
        .table { border-radius: 8px; overflow: hidden; }
        .table th { background-color: #e9ecef; }
        .container { max-width: 960px; }
        .timeline {
            position: relative;
            padding: 20px 0;
        }
        .timeline:before {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            width: 4px;
            background: #4a90e2;
            left: 20px;
            margin: 0;
            border-radius: 2px;
        }
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }
        .timeline-item:before {
            content: '';
            position: absolute;
            top: 8px;
            left: 17px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #fff;
            border: 2px solid #4a90e2;
        }
        .timeline-content {
            margin-left: 40px;
            padding: 10px 20px;
            background: #fff;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand fw-bold" href="../index.php">ReBikeShop</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="../index.php">หน้าแรก</a></li>
                    <li class="nav-item"><a class="nav-link active" href="track.php">ติดตามการซ่อม</a></li>
                    <li class="nav-item"><a class="nav-link" href="../admin/login.php">เข้าสู่ระบบ</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <h1 class="text-center mb-5">รายละเอียดคำสั่งซ่อม</h1>

        <?php if (isset($_GET['error'])): ?>
            <div class="row justify-content-center">
                <div class="col-md-10">
                    <div class="alert alert-danger text-center">
                        <h4 class="alert-heading">เกิดข้อผิดพลาด</h4>
                        <?php
                        $errors = [
                            'invalid_order_id' => 'เลขที่คำสั่งซ่อมไม่ถูกต้อง กรุณาตรวจสอบและลองอีกครั้ง',
                            'order_not_found' => 'ไม่พบคำสั่งซ่อมนี้ในระบบ กรุณาตรวจสอบเลขที่คำสั่งซ่อม',
                            'database_error' => 'เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล กรุณาลองใหม่ภายหลังหรือติดต่อร้านค้าที่ <a href="mailto:support@rebikeshop.com" class="alert-link">support@rebikeshop.com</a>'
                        ];
                        echo $errors[$_GET['error']] ?? 'เกิดข้อผิดพลาดไม่ทราบสาเหตุ กรุณาติดต่อร้านค้า';
                        ?>
                        <div class="mt-3">
                            <a href="track.php" class="btn btn-primary"><i class="fas fa-arrow-left me-2"></i>กลับไปหน้าติดตามการซ่อม</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Order Details -->
            <div class="row justify-content-center">
                <div class="col-md-10">
                    <!-- Order Summary Card -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-soft-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-file-invoice me-2"></i>สรุปคำสั่งซ่อม #<?php echo htmlspecialchars($order['order_number']); ?></h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-4">
                                    <h6 class="fw-bold">สถานะปัจจุบัน</h6>
                                    <span class="badge bg-<?php echo in_array($order['status'], ['completed', 'delivered']) ? 'soft-success' : 'soft-warning'; ?> p-2">
                                        <?php echo getStatusThai($order['status']); ?>
                                    </span>
                                    <p class="mt-2"><strong>วันที่รับงาน:</strong> <?php echo thai_date($order['received_date'], 'full'); ?></p>
                                    <?php if ($order['estimated_completion'] && $order['estimated_completion'] !== '0000-00-00'): ?>
                                        <p><strong>กำหนดเสร็จโดยประมาณ:</strong> <?php echo thai_date($order['estimated_completion']); ?></p>
                                    <?php endif; ?>
                                    <?php if ($order['total_cost'] !== null): ?>
                                        <p><strong>ค่าใช้จ่ายรวม:</strong> <?php echo number_format($order['total_cost'], 2); ?> บาท</p>
                                    <?php else: ?>
                                        <p><strong>ค่าใช้จ่ายรวม:</strong> รอการคำนวณ</p>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-4 mb-4">
                                    <h6 class="fw-bold">ข้อมูลลูกค้า</h6>
                                    <p><strong>ชื่อ-นามสกุล:</strong> <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></p>
                                    <p><strong>โทรศัพท์:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
                                    <?php if (!empty($order['email'])): ?>
                                        <p><strong>อีเมล:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
                                    <?php endif; ?>
                                    <p><strong>ที่อยู่:</strong> <?php echo htmlspecialchars(($order['address'] ?? '') . ' ' . ($order['district'] ?? '') . ' ' . ($order['province'] ?? '') . ' ' . ($order['zip_code'] ?? '')); ?></p>
                                    <?php if (!empty($order['customer_notes'])): ?>
                                        <p><strong>หมายเหตุลูกค้า:</strong> <?php echo nl2br(htmlspecialchars($order['customer_notes'])); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-4 mb-4">
                                    <h6 class="fw-bold">ข้อมูลจักรยาน</h6>
                                    <p><strong>ประเภท:</strong> <?php echo htmlspecialchars($order['bike_type'] ?? '-'); ?></p>
                                    <p><strong>คำอธิบายประเภท:</strong> <?php echo htmlspecialchars($order['bike_type_desc'] ?? '-'); ?></p>
                                    <p><strong>ยี่ห้อ/รุ่น:</strong> <?php echo htmlspecialchars($order['brand'] . ' ' . ($order['model'] ?? '')); ?></p>
                                    <p><strong>สี:</strong> <?php echo htmlspecialchars($order['color'] ?? '-'); ?></p>
                                    <?php if (!empty($order['serial_number'])): ?>
                                        <p><strong>หมายเลขซีเรียล:</strong> <?php echo htmlspecialchars($order['serial_number']); ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($order['purchase_date'])): ?>
                                        <p><strong>วันที่ซื้อ:</strong> <?php echo thai_date($order['purchase_date']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if (!empty($order['notes'])): ?>
                                <hr>
                                <h6 class="fw-bold">หมายเหตุคำสั่งซ่อม</h6>
                                <p class="text-muted"><?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Repair Items Card -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-soft-warning text-white">
                            <h5 class="mb-0"><i class="fas fa-tools me-2"></i>รายการซ่อมและอะไหล่</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>รายละเอียด</th>
                                            <th>ค่าใช้จ่าย (บาท)</th>
                                            <th>หมายเหตุ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($repair_items)): ?>
                                            <?php foreach ($repair_items as $item): ?>
                                                <tr>
                                                    <td><?php echo nl2br(htmlspecialchars($item['description'])); ?></td>
                                                    <td><?php echo number_format($item['cost'], 2); ?></td>
                                                    <td><?php echo nl2br(htmlspecialchars($item['notes'] ?? '-')); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td><?php echo nl2br(htmlspecialchars($order['description'] ?? 'รอรายละเอียดการซ่อม')); ?></td>
                                                <td><?php echo number_format($order['total_cost'] ?? 0.00, 2); ?></td>
                                                <td><?php echo nl2br(htmlspecialchars($order['notes'] ?? 'รอการประเมินเพิ่มเติม')); ?></td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                    <?php if (!empty($repair_items)): ?>
                                        <tfoot>
                                            <tr>
                                                <th class="text-end">รวมทั้งสิ้น:</th>
                                                <th><?php echo number_format(array_sum(array_column($repair_items, 'cost')), 2); ?> บาท</th>
                                                <th></th>
                                            </tr>
                                        </tfoot>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Status Updates Timeline -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-soft-success text-white">
                            <h5 class="mb-0"><i class="fas fa-history me-2"></i>ไทม์ไลน์สถานะการซ่อมสินค้า</h5>
                        </div>
                        <div class="card-body">
                            <div class="timeline">
                                <?php if (!empty($status_history)): ?>
                                    <?php foreach ($status_history as $update): ?>
                                        <div class="timeline-item">
                                            <div class="timeline-content">
                                                <h6 class="mb-1"><?php echo thai_date($update['update_time'], 'full'); ?></h6>
                                                <span class="badge bg-soft-primary"><?php echo getStatusThai($update['status']); ?></span>
                                                <?php if (!empty($update['notes'])): ?>
                                                    <p class="mt-2 text-muted"><?php echo nl2br(htmlspecialchars($update['notes'])); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="timeline-item">
                                        <div class="timeline-content">
                                            <h6 class="mb-1"><?php echo thai_date($order['received_date'], 'full'); ?></h6>
                                            <span class="badge bg-soft-primary"><?php echo getStatusThai($order['status']); ?></span>
                                            <p class="mt-2 text-muted">สถานะเริ่มต้นของคำสั่งซ่อม</p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Navigation Buttons -->
                    <div class="text-center">
                        <a href="track.php" class="btn btn-secondary me-2"><i class="fas fa-arrow-left me-2"></i>กลับไปหน้าติดตามการซ่อม</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <footer class="bg-dark text-white text-center py-4 mt-5">
        <p class="mb-0">© 2025 RebikeShop - All Rights Reserved.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>