<?php
// admin/orders/add.php
require_once '../../includes/db_connect.php';
require_once '../../includes/functions.php';

// Check admin login
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$customers = [];
$bikes = [];
$error = '';
$success = '';

try {
    // ดึงข้อมูลผู้ใช้
    $stmt = $pdo->prepare("SELECT user_id, username, role, customer_id, email, phone, technician_id FROM users WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        $errors[] = "ไม่พบข้อมูลผู้ใช้";
        header('Location: index.php');
        exit;
    }
    // จำกัดการเข้าถึงสำหรับลูกค้า
    if ($user['role'] === 'customer') {
        $errors[] = "คุณไม่มีสิทธิ์เพิ่มคำขอซ่อม";
        header('Location: index.php');
        exit;
    }
    if (!in_array($user['role'], ['admin', 'technician'])) {
        $errors[] = "คุณไม่มีสิทธิ์เพิ่มคำขอซ่อม";
        header('Location: index.php');
        exit;
    }

    // ดึงข้อมูลสถานะและช่าง
    $stmt = $pdo->query("SELECT status_id, status_name FROM repair_status");
    $statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt = $pdo->query("SELECT technician_id, name FROM technician");
    $technicians = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Database error in add_request.php (initial queries): " . $e->getMessage());
    $errors[] = "เกิดข้อผิดพลาดในการดึงข้อมูล: " . htmlspecialchars($e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "การตรวจสอบความปลอดภัยล้มเหลว กรุณาลองใหม่";
    } else {
        // รับข้อมูลจากฟอร์ม
        $request_date = trim($_POST['request_date'] ?? '');
        $status_id = trim($_POST['status_id'] ?? '');
        $technician_id = trim($_POST['technician_id'] ?? '');
        $customer_name = trim($_POST['customer_name'] ?? '');
        $customer_email = trim($_POST['customer_email'] ?? '');
        $customer_phone = trim($_POST['customer_phone'] ?? '');
        $customer_address = trim($_POST['customer_address'] ?? '');
        $product_name = trim($_POST['product_name'] ?? '');
        $product_model = trim($_POST['product_model'] ?? '');
        $repair_note = trim($_POST['repair_note'] ?? '');
        $repair_date = trim($_POST['repair_date'] ?? '');
        $repair_cost = trim($_POST['repair_cost'] ?? '');

        // ตรวจสอบความถูกต้องของข้อมูล
        if (empty($request_date)) $errors[] = "กรุณาเลือกวันที่ขอ";
        if (empty($status_id)) $errors[] = "กรุณาเลือกสถานะ";
        if (empty($technician_id)) $errors[] = "กรุณาเลือกช่าง";
        if (empty($customer_name)) $errors[] = "กรุณาระบุชื่อลูกค้า";
        if (empty($customer_email)) $errors[] = "กรุณาระบุอีเมลลูกค้า";
        if (empty($customer_phone)) $errors[] = "กรุณาระ.expand_lessบุเบอร์โทรลูกค้า";
        if (empty($customer_address)) $errors[] = "กรุณาระบุที่อยู่ลูกค้า";
        if (empty($product_name)) $errors[] = "กรุณาระบุชื่อผลิตภัณฑ์";
        if (empty($product_model)) $errors[] = "กรุณาระบุรุ่นผลิตภัณฑ์";
        if (empty($repair_note)) $errors[] = "กรุณาระบุหมายเหตุการซ่อม";
        if (empty($repair_date)) $errors[] = "กรุณาเลือกวันที่ซ่อม";
        if (empty($repair_cost) || !is_numeric($repair_cost) || $repair_cost < 0) {
            $errors[] = "กรุณาระบุราคาซ่อมที่ถูกต้อง (ต้องเป็นตัวเลขที่ไม่ติดลบ)";
        }

        // ตรวจสอบว่า status_id มีอยู่ในตาราง repair_status
        if (!empty($status_id)) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM repair_status WHERE status_id = :status_id");
            $stmt->execute([':status_id' => $status_id]);
            if ($stmt->fetchColumn() == 0) {
                $errors[] = "สถานะที่เลือกไม่ถูกต้อง";
            }
        }

        // ตรวจสอบความถูกต้องของอีเมล
        if (!empty($customer_email) && !filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "รูปแบบอีเมลลูกค้าไม่ถูกต้อง";
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                // เพิ่มข้อมูลลูกค้า
                $stmt = $pdo->prepare("
                    INSERT INTO customer (name, email, phone, address) 
                    VALUES (:name, :email, :phone, :address)
                ");
                $stmt->execute([
                    ':name' => $customer_name,
                    ':email' => $customer_email,
                    ':phone' => $customer_phone,
                    ':address' => $customer_address
                ]);
                $customer_id = $pdo->lastInsertId();

                // เพิ่มผลิตภัณฑ์
                $stmt = $pdo->prepare("
                    INSERT INTO product (product_name, product_model) 
                    VALUES (:product_name, :product_model)
                ");
                $stmt->execute([
                    ':product_name' => $product_name,
                    ':product_model' => $product_model
                ]);
                $product_id = $pdo->lastInsertId();

                // เพิ่มคำขอซ่อม
                $stmt = $pdo->prepare("
                    INSERT INTO repair_request (request_date, status_id, technician_id, customer_id, repair_cost) 
                    VALUES (:request_date, :status_id, :technician_id, :customer_id, :repair_cost)
                ");
                $stmt->execute([
                    ':request_date' => $request_date,
                    ':status_id' => $status_id,
                    ':technician_id' => $technician_id,
                    ':customer_id' => $customer_id,
                    ':repair_cost' => $repair_cost
                ]);
                $request_id = $pdo->lastInsertId();

                // เพิ่มรายละเอียดการซ่อม
                $stmt = $pdo->prepare("
                    INSERT INTO repair_detail (request_id, product_id, repair_note, repair_date) 
                    VALUES (:request_id, :product_id, :repair_note, :repair_date)
                ");
                $stmt->execute([
                    ':request_id' => $request_id,
                    ':product_id' => $product_id,
                    ':repair_note' => $repair_note,
                    ':repair_date' => $repair_date
                ]);

                $pdo->commit();
                $success = "เพิ่มคำขอซ่อมเรียบร้อยแล้ว";
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                header('Location: view_requests.php?success=' . urlencode($success));
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                error_log("Database error in add_request.php (insert): " . $e->getMessage());
                $errors[] = "เกิดข้อผิดพลาดในการบันทึกคำขอ: " . htmlspecialchars($e->getMessage());
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset

System: UTF-8">
    <title>RebikeShop</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">RebikeShop</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link active" href="index.php">หน้าแรก</a></li>
                    <li class="nav-item"><a class="nav-link" href="customer/track.php">ติดตามการซ่อม</a></li>
                    <li class="nav-item"><a class="nav-link" href="admin/login.php">เข้าสู่ระบบ</a></li>
                </ul>
            </div>
        </div>
    </nav>
    <style>
        :root {
            --primary-color: #1e3a8a;
            --secondary-color: #6c757d;
            --accent-color: #17a2b8;
            --success-color: #28a745;
            --background-color: #f8fafc;
            --card-background: #ffffff;
            --text-color: #1a202c;
            --border-radius: 12px;
            --shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Noto Sans Thai', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            margin: 0;
            padding-top: 80px;
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary-color), #3b82f6);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
            padding: 1rem 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1030;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.6rem;
            color: #ffffff;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .navbar-brand:hover {
            color: #e6f0fa;
            transform: translateY(-2px);
        }

        .nav-link {
            font-size: 1rem;
            font-weight: 500;
            color: #ffffff;
            padding: 0.6rem 1.5rem;
            border-radius: 8px;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-link:hover, .nav-link.active {
            background-color: rgba(255, 255, 255, 0.25);
            color: #e6f0fa !important;
            transform: translateY(-2px);
        }

        .navbar-toggler {
            border: none;
            transition: transform 0.3s ease;
        }

        .navbar-toggler:focus {
            box-shadow: none;
        }

        .navbar-toggler[aria-expanded="true"] .navbar-toggler-icon {
            transform: rotate(90deg);
        }

        .navbar-collapse {
            transition: max-height 0.3s ease;
        }

        .card {
            background-color: var(--card-background);
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            transition: var(--transition);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .form-label.required::after {
            content: '*';
            color: #dc3545;
            margin-left: 4px;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            transition: var(--transition);
        }

        .btn-primary:hover {
            background-color: #1e40af;
            border-color: #1e3a8a;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            transition: var(--transition);
        }

        .btn-secondary:hover {
            background-color: #5a6268;
            border-color: #545b62;
            transform: translateY(-2px);
        }

        .alert {
            border-radius: var(--border-radius);
        }

        .footer {
            background-color: var(--text-color);
            color: #ffffff;
            padding: 2rem 0;
            font-size: 0.9rem;
            text-align: center;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(30, 58, 138, 0.25);
        }

        @media (max-width: 768px) {
            body {
                padding-top: 100px;
            }

            .navbar-brand {
                font-size: 1.4rem;
            }

            .nav-link {
                font-size: 0.95rem;
                padding: 0.5rem 1rem;
            }

            .card {
                padding: 1rem;
            }

            .form-control, .form-select {
                font-size: 0.9rem;
            }

            .navbar-nav {
                padding: 1rem;
                background-color: rgba(0, 0, 0, 0.05);
            }

            .navbar-collapse {
                max-height: 80vh;
                overflow-y: auto;
            }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Navigation Bar -->
        <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
            <div class="container">
                <a class="navbar-brand" href="index.php"><i class="bi bi-tools"></i> ระบบจัดการคำขอซ่อม</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                       <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                        <a class="nav-link active" href="Home.php" aria-current="page">หน้าหลัก</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php"><i class="bi bi-person"></i> โปรไฟล์</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">ออกจากระบบ</a>
                        </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link" href="login.php"><i class="bi bi-box-arrow-in-right"></i> เข้าสู่ระบบ</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>


        <!-- Main Content -->
        <section class="content py-5">
            <div class="container">
                <h2 class="mb-4 text-center">เพิ่มคำขอซ่อม</h2>
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php echo implode('<br>', array_map('htmlspecialchars', $errors)); ?>
                    </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="card p-4">
                            <form id="repairForm" action="add_request.php" method="POST" class="needs-validation" novalidate>
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="request_date" class="form-label required">วันที่ขอ</label>
                                        <input type="date" class="form-control" id="request_date" name="request_date" required>
                                        <div class="invalid-feedback">กรุณาเลือกวันที่ขอ</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="status_id" class="form-label required">สถานะ</label>
                                        <select class="form-select" id="status_id" name="status_id" required>
                                            <option value="">เลือกสถานะ</option>
                                            <?php foreach ($statuses as $status): ?>
                                                <option value="<?php echo $status['status_id']; ?>">
                                                    <?php echo htmlspecialchars($status['status_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback">กรุณาเลือกสถานะ</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="technician_id" class="form-label required">ช่าง</label>
                                        <select class="form-select" id="technician_id" name="technician_id" required>
                                            <option value="">เลือกช่าง</option>
                                            <?php foreach ($technicians as $tech): ?>
                                                <option value="<?php echo $tech['technician_id']; ?>">
                                                    <?php echo htmlspecialchars($tech['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="invalid-feedback">กรุณาเลือกช่าง</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="customer_name" class="form-label required">ชื่อลูกค้า</label>
                                        <input type="text" class="form-control" id="customer_name" name="customer_name" required>
                                        <div class="invalid-feedback">กรุณาระบุชื่อลูกค้า</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="customer_email" class="form-label required">อีเมลลูกค้า</label>
                                        <input type="email" class="form-control" id="customer_email" name="customer_email" required>
                                        <div class="invalid-feedback">กรุณาระบุอีเมลลูกค้า</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="customer_phone" class="form-label required">เบอร์โทรลูกค้า</label>
                                        <input type="tel" class="form-control" id="customer_phone" name="customer_phone" required>
                                        <div class="invalid-feedback">กรุณาระบุเบอร์โทรลูกค้า</div>
                                    </div>
                                    <div class="col-12">
                                        <label for="customer_address" class="form-label required">ที่อยู่ลูกค้า</label>
                                        <textarea class="form-control" id="customer_address" name="customer_address" rows="3" required></textarea>
                                        <div class="invalid-feedback">กรุณาระบุที่อยู่ลูกค้า</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="product_name" class="form-label required">ชื่อผลิตภัณฑ์</label>
                                        <input type="text" class="form-control" id="product_name" name="product_name" required>
                                        <div class="invalid-feedback">กรุณาระบุชื่อผลิตภัณฑ์</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="product_model" class="form-label required">รุ่นผลิตภัณฑ์</label>
                                        <input type="text" class="form-control" id="product_model" name="product_model" required>
                                        <div class="invalid-feedback">กรุณาระบุรุ่นผลิตภัณฑ์</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="repair_date" class="form-label required">วันที่ซ่อม</label>
                                        <input type="date" class="form-control" id="repair_date" name="repair_date" required>
                                        <div class="invalid-feedback">กรุณาเลือกวันที่ซ่อม</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="repair_cost" class="form-label required">ราคาซ่อม (บาท)</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-currency-bitcoin"></i></span>
                                            <input type="number" class="form-control" id="repair_cost" name="repair_cost" min="0" step="0.01" required aria-describedby="repair-cost-help">
                                            <span class="input-group-text">฿</span>
                                        </div>
                                        <div class="invalid-feedback">กรุณาระบุราคาซ่อมที่ถูกต้อง</div>
                                        <small id="repair-cost-help" class="form-text text-muted">ระบุราคาค่าซ่อมเป็นตัวเลข (เช่น 1500.50)</small>
                                    </div>
                                    <div class="col-12">
                                        <label for="repair_note" class="form-label required">หมายเหตุการซ่อม</label>
                                        <textarea class="form-control" id="repair_note" name="repair_note" rows="5" required></textarea>
                                        <div class="invalid-feedback">กรุณาระบุหมายเหตุการซ่อม</div>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between mt-4">
                                    <a href="view_requests.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> กลับไปดูคำขอ</a>
                                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> บันทึกคำขอ</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="footer">
            <div class="container">
                <p class="mb-0">© 2025 ระบบจัดการคำขอซ่อม | พัฒนาเพื่อการศึกษา</p>
            </div>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('repairForm');
            const inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                input.addEventListener('input', function () {
                    if (input.checkValidity()) {
                        input.classList.remove('is-invalid');
                        input.classList.add('is-valid');
                    } else {
                        input.classList.remove('is-valid');
                        input.classList.add('is-invalid');
                    }
                });
            });
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    </script>
</body>
</html>