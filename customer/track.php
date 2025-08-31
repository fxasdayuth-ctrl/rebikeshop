<?php
// customer/track.php
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

$order = null;
$status_updates = [];
$repair_items = [];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['order_number'])) {
    $order_number = trim($_GET['order_number']);
    
    // Validate order number format
    if (empty($order_number)) {
        $errors[] = 'กรุณากรอกเลขที่ออเดอร์';
    } elseif (!preg_match('/^RO-(202[4-9][0-1][0-9][0-3][0-9])-([0-9]{3,})$/', $order_number)) {
        $errors[] = 'รูปแบบเลขที่ออเดอร์ไม่ถูกต้อง (ต้องเป็น RO-YYYYMMDD-NNN เช่น RO-20250826-001)';
    } else {
        try {
            // Fetch repair order details with additional customer and bike info from SQL
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
            
            if (!$order) {
                $errors[] = 'ไม่พบคำสั่งซ่อมสำหรับเลขที่ออเดอร์นี้ กรุณาตรวจสอบเลขที่ออเดอร์หรือติดต่อร้านค้า';
            } else {
                // Fetch status updates (latest first)
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

                // Fetch repair items
                $stmt_items = $pdo->prepare("
                    SELECT ri.* 
                    FROM repair_items ri
                    JOIN repair_orders ro ON ri.order_id = ro.id
                    WHERE ri.order_id = :order_id
                ");
                $stmt_items->bindParam(':order_id', $order['id'], PDO::PARAM_INT);
                $stmt_items->execute();
                $repair_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

                // Calculate and update total_cost if null
                if ($order['total_cost'] === null && !empty($repair_items)) {
                    $total_cost = array_sum(array_column($repair_items, 'cost'));
                    $update_stmt = $pdo->prepare("
                        UPDATE repair_orders 
                        SET total_cost = :total_cost 
                        WHERE id = :order_id
                    ");
                    $update_stmt->bindParam(':total_cost', $total_cost, PDO::PARAM_STR);
                    $update_stmt->bindParam(':order_id', $order['id'], PDO::PARAM_INT);
                    $update_stmt->execute();
                    $order['total_cost'] = $total_cost;
                }
            }
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            $errors[] = 'เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล กรุณาลองใหม่ภายหลังหรือติดต่อร้านค้า';
        }
    }
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

// Function to get status color class
if (!function_exists('getStatusColor')) {
    function getStatusColor($status) {
        $colors = [
            'received' => 'bg-primary',
            'diagnosing' => 'bg-info',
            'repairing' => 'bg-warning',
            'waiting_parts' => 'bg-secondary',
            'completed' => 'bg-success',
            'delivered' => 'bg-dark'
        ];
        return $colors[$status] ?? 'bg-secondary';
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
    <title>ติดตามการซ่อม - ReBikeShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --info-color: #0dcaf0;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --bg-gradient: linear-gradient(135deg, #000000ff, #000000ff);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--light-color);
            color: var(--dark-color);
        }

        .navbar {
            background: var(--bg-gradient) !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            font-weight: 600;
            color: white !important;
        }

        .nav-link {
            color: rgba(255,255,255,0.8) !important;
            transition: color 0.3s;
        }

        .nav-link:hover, .nav-link.active {
            color: white !important;
        }

        .container {
            max-width: 1200px;
        }

        .card {
            border: none;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }

        .card-header {
            background: var(--bg-gradient);
            color: white;
            padding: 1.5rem;
            border-radius: 20px 20px 0 0 !important;
        }

        .btn-primary {
            background: var(--bg-gradient);
            border: none;
            border-radius: 50px;
            padding: 0.75rem 2rem;
            font-weight: 500;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .btn-primary:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(13,110,253,0.4);
        }

        .form-control {
            border-radius: 50px;
            padding: 0.75rem 1.5rem;
            border: 1px solid rgba(0,0,0,0.1);
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(13,110,253,0.25);
        }

        .badge {
            border-radius: 50px;
            padding: 0.5rem 1rem;
            font-weight: 500;
        }

        .timeline {
            position: relative;
            padding: 0;
            list-style: none;
        }

        .timeline:before {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            left: 20px;
            width: 3px;
            background: linear-gradient(to bottom, #000000ff, #000000ff);
            z-index: 1;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 30px;
        }

        .timeline-item:before {
            content: '';
            position: absolute;
            top: 10px;
            left: 15px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: white;
            border: 3px solid var(--primary-color);
            z-index: 2;
            transition: transform 0.3s;
        }

        .timeline-item:hover:before {
            transform: scale(1.2);
        }

        .timeline-content {
            margin-left: 50px;
            padding: 15px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s;
        }

        .timeline-content:hover {
            transform: translateX(5px);
        }

        .table {
            border-radius: 15px;
            overflow: hidden;
        }

        .table th {
            background: rgba(13,110,253,0.1);
            border-top: none;
        }

        .table-hover tbody tr:hover {
            background: rgba(13,110,253,0.05);
            transition: background 0.3s;
        }

        footer {
            background: var(--bg-gradient);
            color: white;
            padding: 2rem 0;
            margin-top: auto;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="../index.php">ReBikeShop</a>
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

    <div class="container my-5 flex-grow-1">
        <h1 class="text-center mb-5 fw-bold">ติดตามสถานะการซ่อมสินค้า</h1>

        <!-- Track Form -->
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-search me-2"></i>ค้นหาคำสั่งซ่อม</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <?php foreach ($errors as $error): ?>
                                    <p class="mb-0"><?php echo htmlspecialchars($error); ?></p>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <form action="track.php" method="GET" id="trackForm">
                            <div class="mb-3 position-relative">
                                <label for="order_number" class="form-label"><i class="fas fa-barcode me-2"></i>เลขที่ออเดอร์ <span class="text-danger">*</span></label>
                                <input type="text" class="form-control ps-5" id="order_number" name="order_number" value="<?php echo htmlspecialchars($_GET['order_number'] ?? ''); ?>" placeholder="เช่น RO-20250826-001" required>
                                <i class="บักสัส"></i>
                                <div class="invalid-feedback">
                                    เลขที่ออเดอร์ต้องอยู่ในรูปแบบ RO-YYYYMMDD-NNN และเป็นวันที่ที่ถูกต้อง
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-2"></i>ตรวจสอบสถานะ</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Order Details -->
        <?php if ($order): ?>
            <div class="row mt-5 justify-content-center">
                <div class="col-md-10">
                    <!-- Order Summary Card -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-file-invoice me-2"></i>สรุปคำสั่งซ่อม #<?php echo htmlspecialchars($order['order_number']); ?></h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-4">
                                    <h6 class="fw-bold mb-3">สถานะปัจจุบัน</h6>
                                    <span class="badge <?php echo getStatusColor($status_updates[0]['status'] ?? $order['status']); ?> p-2 fs-6">
                                        <?php echo getStatusThai($status_updates[0]['status'] ?? $order['status']); ?>
                                    </span>
                                    <p class="mt-3 mb-2"><strong>วันที่รับงาน:</strong> <?php echo thai_date($order['received_date'], 'full'); ?></p>
                                    <?php if ($order['estimated_completion'] && $order['estimated_completion'] !== '0000-00-00'): ?>
                                        <p class="mb-2"><strong>กำหนดเสร็จโดยประมาณ:</strong> <?php echo thai_date($order['estimated_completion']); ?></p>
                                    <?php endif; ?>
                                    <?php if ($order['total_cost'] !== null): ?>
                                        <p class="mb-2"><strong>ค่าใช้จ่ายรวม:</strong> <?php echo number_format($order['total_cost'], 2); ?> บาท</p>
                                    <?php else: ?>
                                        <p class="mb-2"><strong>ค่าใช้จ่ายรวม:</strong> รอการคำนวณ</p>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-4 mb-4">
                                    <h6 class="fw-bold mb-3">ข้อมูลลูกค้า</h6>
                                    <p class="mb-2"><strong>ชื่อ-นามสกุล:</strong> <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></p>
                                    <p class="mb-2"><strong>โทรศัพท์:</strong> <?php echo htmlspecialchars($order['phone']); ?></p>
                                    <?php if (!empty($order['email'])): ?>
                                        <p class="mb-2"><strong>อีเมล:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
                                    <?php endif; ?>
                                    <p class="mb-2"><strong>ที่อยู่:</strong> <?php echo htmlspecialchars(($order['address'] ?? '') . ' ' . ($order['district'] ?? '') . ' ' . ($order['province'] ?? '') . ' ' . ($order['zip_code'] ?? '')); ?></p>
                                    <?php if (!empty($order['customer_notes'])): ?>
                                        <p class="mb-2"><strong>หมายเหตุลูกค้า:</strong> <?php echo nl2br(htmlspecialchars($order['customer_notes'])); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-4 mb-4">
                                    <h6 class="fw-bold mb-3">ข้อมูลจักรยาน</h6>
                                    <p class="mb-2"><strong>ประเภท:</strong> <?php echo htmlspecialchars($order['bike_type'] ?? '-'); ?></p>
                                    <p class="mb-2"><strong>คำอธิบายประเภท:</strong> <?php echo htmlspecialchars($order['bike_type_desc'] ?? '-'); ?></p>
                                    <p class="mb-2"><strong>ยี่ห้อ/รุ่น:</strong> <?php echo htmlspecialchars($order['brand'] . ' ' . ($order['model'] ?? '')); ?></p>
                                    <p class="mb-2"><strong>สี:</strong> <?php echo htmlspecialchars($order['color'] ?? '-'); ?></p>
                                    <?php if (!empty($order['serial_number'])): ?>
                                        <p class="mb-2"><strong>หมายเลขซีเรียล:</strong> <?php echo htmlspecialchars($order['serial_number']); ?></p>
                                    <?php endif; ?>
                                    <?php if (!empty($order['purchase_date'])): ?>
                                        <p class="mb-2"><strong>วันที่ซื้อ:</strong> <?php echo thai_date($order['purchase_date']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if (!empty($order['notes'])): ?>
                                <hr class="my-4">
                                <h6 class="fw-bold mb-3">หมายเหตุคำสั่งซ่อม</h6>
                                <p class="text-muted"><?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Status Updates Timeline -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-history me-2"></i>ไทม์ไลน์สถานะการซ่อมสินค้า</h5>
                        </div>
                        <div class="card-body">
                            <ul class="timeline">
                                <?php if (!empty($status_updates)): ?>
                                    <?php foreach ($status_updates as $update): ?>
                                        <li class="timeline-item">
                                            <div class="timeline-content">
                                                <h6 class="mb-1"><?php echo thai_date($update['update_time'], 'full'); ?></h6>
                                                <span class="badge <?php echo getStatusColor($update['status']); ?>"><?php echo getStatusThai($update['status']); ?></span>
                                                <?php if (!empty($update['notes'])): ?>
                                                    <p class="mt-2 text-muted"><?php echo nl2br(htmlspecialchars($update['notes'])); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <!-- Display the initial status from repair_orders -->
                                    <li class="timeline-item">
                                        <div class="timeline-content">
                                            <h6 class="mb-1"><?php echo thai_date($order['received_date'], 'full'); ?></h6>
                                            <span class="badge <?php echo getStatusColor($order['status']); ?>"><?php echo getStatusThai($order['status']); ?></span>
                                            <p class="mt-2 text-muted">สถานะเริ่มต้นของคำสั่งซ่อม</p>
                                        </div>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>

                    <!-- Repair Items Card -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-tools me-2"></i>รายการซ่อมและอะไหล่</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-striped">
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
                                            <tr class="fw-bold">
                                                <td class="text-end">รวมทั้งสิ้น:</td>
                                                <td><?php echo number_format(array_sum(array_column($repair_items, 'cost')), 2); ?> บาท</td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Link to Detailed View -->
                    <div class="text-center">
                        <a href="view_order.php?order_id=<?php echo $order['id']; ?>" class="btn btn-primary"><i class="fas fa-info-circle me-2"></i>ดูรายละเอียดคำสั่งซ่อมทั้งหมด</a>
                    </div>
                </div>
            </div>
        <?php elseif (!empty($_GET['order_number'])): ?>
            <div class="row mt-5 justify-content-center">
                <div class="col-md-8">
                    <div class="card text-center">
                        <div class="card-header bg-danger text-white">
                            <h4 class="mb-0">ไม่พบคำสั่งซ่อม</h4>
                        </div>
                        <div class="card-body">
                            <p class="lead">ขออภัย ไม่พบคำสั่งซ่อมสำหรับเลขที่ออเดอร์ <strong><?php echo htmlspecialchars($_GET['order_number']); ?></strong></p>
                            <p>กรุณาตรวจสอบเลขที่ออเดอร์ให้ถูกต้องหรือติดต่อร้านค้าที่ <a href="mailto:support@rebikeshop.com" class="text-primary">support@rebikeshop.com</a></p>
                            <a href="track.php" class="btn btn-primary mt-3"><i class="fas fa-arrow-left me-2"></i>กลับไปค้นหาใหม่</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('trackForm').addEventListener('submit', function(e) {
            const orderNumberInput = document.getElementById('order_number');
            const orderNumber = orderNumberInput.value.trim().toUpperCase(); // Convert to uppercase for consistency
            const regex = /^RO-(202[4-9][0-1][0-9][0-3][0-9])-([0-9]{3,})$/;

            if (!orderNumber) {
                e.preventDefault();
                orderNumberInput.classList.add('is-invalid');
                return;
            }

            if (!regex.test(orderNumber)) {
                e.preventDefault();
                orderNumberInput.classList.add('is-invalid');
                return;
            }

            const datePart = orderNumber.match(regex)[1];
            const year = parseInt(datePart.slice(0, 4));
            const month = parseInt(datePart.slice(4, 6)) - 1; // Months are 0-based in JS
            const day = parseInt(datePart.slice(6, 8));
            const date = new Date(year, month, day);

            // Check if the date is valid and matches the input
            const isValidDate = !isNaN(date.getTime()) &&
                date.getFullYear() === year &&
                date.getMonth() === month &&
                date.getDate() === day;

            if (!isValidDate) {
                e.preventDefault();
                orderNumberInput.classList.add('is-invalid');
            } else {
                orderNumberInput.classList.remove('is-invalid');
            }
        });
    </script>
</body>
</html>