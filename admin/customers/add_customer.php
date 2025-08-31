<?php
session_start();
require_once '../../includes/db_connect.php';
require_once '../../includes/functions.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

$errors = [];
$work = [
    'first_name' => '',
    'last_name' => '',
    'phone' => '',
    'email' => '',
    'address' => '',
    'district' => '',
    'province' => '',
    'zip_code' => '',
    'notes' => '',
    'brand' => '',
    'model' => '',
    'color' => '',
    'name' => '', // Stores "ประเภท"
    'description' => '' // Stores "คำอธิบาย"
];
$customer_id = null;
$bike_id = null;
$order_number = null;

// Generate a unique order number (e.g., ORD-YYYYMMDD-NNN)
function generateOrderNumber($pdo) {
    $date = date('Ymd'); // e.g., 20250828
    $prefix = 'ORD-' . $date . '-';
    
    // Find the last order number for today
    $stmt = $pdo->prepare("SELECT MAX(order_number) as last_number FROM repair_orders WHERE order_number LIKE ?");
    $stmt->execute([$prefix . '%']);
    $result = $stmt->fetch();
    
    $lastNumber = $result['last_number'];
    if ($lastNumber) {
        $num = intval(substr($lastNumber, -3)) + 1; // Extract and increment the sequence (e.g., 001 -> 002)
    } else {
        $num = 1; // Start with 001 if no orders exist today
    }
    return $prefix . sprintf('%03d', $num); // Format as ORD-20250828-001
}

// เมื่อฟอร์มถูกส่ง
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $work['first_name'] = trim($_POST['first_name'] ?? '');
    $work['last_name'] = trim($_POST['last_name'] ?? '');
    $work['phone'] = trim($_POST['phone'] ?? '');
    $work['email'] = trim($_POST['email'] ?? '');
    $work['address'] = trim($_POST['address'] ?? '');
    $work['district'] = trim($_POST['district'] ?? '');
    $work['province'] = trim($_POST['province'] ?? '');
    $work['zip_code'] = trim($_POST['zip_code'] ?? '');
    $work['notes'] = trim($_POST['notes'] ?? '');
    $work['brand'] = trim($_POST['brand'] ?? '');
    $work['model'] = trim($_POST['model'] ?? '');
    $work['color'] = trim($_POST['color'] ?? '');
    $work['name'] = trim($_POST['name'] ?? ''); // Still using 'name' for database
    $work['description'] = trim($_POST['description'] ?? ''); // Still using 'description' for database

    // ตรวจสอบความถูกต้อง
    if (empty($work['first_name'])) {
        $errors['first_name'] = 'กรุณากรอกชื่อ';
    }
    if (empty($work['last_name'])) {
        $errors['last_name'] = 'กรุณากรอกนามสกุล';
    }
    if (empty($work['phone'])) {
        $errors['phone'] = 'กรุณากรอกเบอร์โทรศัพท์';
    } elseif (!preg_match('/^[0-9]{10,15}$/', $work['phone'])) {
        $errors['phone'] = 'รูปแบบเบอร์โทรศัพท์ไม่ถูกต้อง';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM customers WHERE phone = ?");
        $stmt->execute([$work['phone']]);
        if ($stmt->fetch()) {
            $errors['phone'] = 'เบอร์โทรศัพท์นี้มีอยู่ในระบบแล้ว';
        }
    }
    if (!empty($work['email']) && !filter_var($work['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'รูปแบบอีเมลไม่ถูกต้อง';
    }
    if (empty($work['brand'])) {
        $errors['brand'] = 'กรุณาเลือกยี่ห้อ';
    }
    if (empty($work['name'])) {
        $errors['name'] = 'กรุณาเลือกประเภท';
    }
    if (empty($work['description'])) {
        $errors['description'] = 'กรุณาเลือกคำอธิบาย';
    }

    // หากไม่มี error ให้บันทึกข้อมูล
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // เพิ่มลูกค้าใหม่
            $stmt = $pdo->prepare("INSERT INTO customers (first_name, last_name, phone, email, address, district, province, zip_code, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([
                $work['first_name'],
                $work['last_name'],
                $work['phone'],
                $work['email'],
                $work['address'],
                $work['district'],
                $work['province'],
                $work['zip_code'],
                $work['notes']
            ]);
            $customer_id = $pdo->lastInsertId();

            // เพิ่มจักรยานใหม่
            $stmt = $pdo->prepare("INSERT INTO bikes (customer_id, brand, model, color, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([
                $customer_id,
                $work['brand'],
                $work['model'],
                $work['color']
            ]);
            $bike_id = $pdo->lastInsertId();

            // Generate order number and add repair order
            $order_number = generateOrderNumber($pdo);
            $stmt = $pdo->prepare("INSERT INTO repair_orders (customer_id, bike_id, name, description, order_number, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([
                $customer_id,
                $bike_id,
                $work['name'],
                $work['description'],
                $order_number
            ]);

            $pdo->commit();
            $success = 'เพิ่มงานและลูกค้าใหม่สำเร็จ! หมายเลขคำสั่งซื้อ: ' . $order_number;
            $work = [
                'first_name' => '',
                'last_name' => '',
                'phone' => '',
                'email' => '',
                'address' => '',
                'district' => '',
                'province' => '',
                'zip_code' => '',
                'notes' => '',
                'brand' => '',
                'model' => '',
                'color' => '',
                'name' => '',
                'description' => ''
            ];
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'เกิดข้อผิดพลาดในการบันทึก: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เพิ่มงานใหม่ - ร้านซ่อมจักรยาน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script>
        function updateDescription() {
            var typeSelect = document.getElementById('name');
            var descSelect = document.getElementById('description');
            var typeValue = typeSelect.value;

            // Mapping ประเภท to คำอธิบาย
            var descMap = {
                'จักรยานทางเรียบ': 'จักรยานสำหรับขี่บนทางเรียบในเมือง',
                'จักรยานภูเขา': 'จักรยานสำหรับขี่บนทางวิบากและทางธรรมชาติ',
                'จักรยานไฟฟ้า': 'จักรยานที่มีระบบขับเคลื่อนด้วยไฟฟ้า',
                'จักรยานเด็ก': 'จักรยานขนาดเล็กสำหรับเด็ก'
            };

            // Update คำอธิบาย based on selected ประเภท
            descSelect.value = descMap[typeValue] || '';
        }
    </script>
</head>
<body>
    <?php include '../../includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h2>เพิ่มงานใหม่</h2>
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo $error; ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="first_name" class="form-label">ชื่อ <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($work['first_name']); ?>" required>
                        <?php if (isset($errors['first_name'])): ?><div class="text-danger"><?php echo $errors['first_name']; ?></div><?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="last_name" class="form-label">นามสกุล <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($work['last_name']); ?>" required>
                        <?php if (isset($errors['last_name'])): ?><div class="text-danger"><?php echo $errors['last_name']; ?></div><?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label">เบอร์โทรศัพท์ <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($work['phone']); ?>" required>
                        <?php if (isset($errors['phone'])): ?><div class="text-danger"><?php echo $errors['phone']; ?></div><?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">อีเมล</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($work['email']); ?>">
                        <?php if (isset($errors['email'])): ?><div class="text-danger"><?php echo $errors['email']; ?></div><?php endif; ?>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="address">ที่อยู่</label>
                                <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($work['address']); ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="district">เขต/อำเภอ</label>
                                <input type="text" class="form-control" id="district" name="district" value="<?php echo htmlspecialchars($work['district']); ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="province">จังหวัด</label>
                                <input type="text" class="form-control" id="province" name="province" value="<?php echo htmlspecialchars($work['province']); ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="zip_code">รหัสไปรษณีย์</label>
                                <input type="text" class="form-control" id="zip_code" name="zip_code" value="<?php echo htmlspecialchars($work['zip_code']); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="brand" class="form-label">ยี่ห้อ <span class="text-danger">*</span></label>
                        <select class="form-control" id="brand" name="brand" required>
                            <option value="">เลือกยี่ห้อ</option>
                            <option value="Trek" <?php echo $work['brand'] == 'Trek' ? 'selected' : ''; ?>>Trek</option>
                            <option value="Strider" <?php echo $work['brand'] == 'Strider' ? 'selected' : ''; ?>>Strider</option>
                            <option value="Specialized" <?php echo $work['brand'] == 'Specialized' ? 'selected' : ''; ?>>Specialized</option>
                            <option value="Scott" <?php echo $work['brand'] == 'Scott' ? 'selected' : ''; ?>>Scott</option>
                            <option value="Schwinn" <?php echo $work['brand'] == 'Schwinn' ? 'selected' : ''; ?>>Schwinn</option>
                            <option value="Rad Power" <?php echo $work['brand'] == 'Rad Power' ? 'selected' : ''; ?>>Rad Power</option>
                            <option value="Merida" <?php echo $work['brand'] == 'Merida' ? 'selected' : ''; ?>>Merida</option>
                            <option value="Giant" <?php echo $work['brand'] == 'Giant' ? 'selected' : ''; ?>>Giant</option>
                            <option value="Cannondale" <?php echo $work['brand'] == 'Cannondale' ? 'selected' : ''; ?>>Cannondale</option>
                            <option value="Bianchi" <?php echo $work['brand'] == 'Bianchi' ? 'selected' : ''; ?>>Bianchi</option>
                        </select>
                        <?php if (isset($errors['brand'])): ?><div class="text-danger"><?php echo $errors['brand']; ?></div><?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="model" class="form-label">รุ่น</label>
                        <select class="form-control" id="model" name="model">
                            <option value="">เลือกรุ่น</option>
                            <option value="Marlin 5" <?php echo $work['model'] == 'Marlin 5' ? 'selected' : ''; ?>>Marlin 5</option>
                            <option value="12 Sport" <?php echo $work['model'] == '12 Sport' ? 'selected' : ''; ?>>12 Sport</option>
                            <option value="Turbo Vado" <?php echo $work['model'] == 'Turbo Vado' ? 'selected' : ''; ?>>Turbo Vado</option>
                            <option value="Scale 970" <?php echo $work['model'] == 'Scale 970' ? 'selected' : ''; ?>>Scale 970</option>
                            <option value="Kids Balance" <?php echo $work['model'] == 'Kids Balance' ? 'selected' : ''; ?>>Kids Balance</option>
                            <option value="RadRover 6" <?php echo $work['model'] == 'RadRover 6' ? 'selected' : ''; ?>>RadRover 6</option>
                            <option value="Scultura 400" <?php echo $work['model'] == 'Scultura 400' ? 'selected' : ''; ?>>Scultura 400</option>
                            <option value="Defy Advanced" <?php echo $work['model'] == 'Defy Advanced' ? 'selected' : ''; ?>>Defy Advanced</option>
                            <option value="Trail 6" <?php echo $work['model'] == 'Trail 6' ? 'selected' : ''; ?>>Trail 6</option>
                            <option value="Aria" <?php echo $work['model'] == 'Aria' ? 'selected' : ''; ?>>Aria</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="color" class="form-label">สี</label>
                        <select class="form-control" id="color" name="color">
                            <option value="">เลือกสี</option>
                            <option value="Black" <?php echo $work['color'] == 'Black' ? 'selected' : ''; ?>>Black</option>
                            <option value="Yellow" <?php echo $work['color'] == 'Yellow' ? 'selected' : ''; ?>>Yellow</option>
                            <option value="Red" <?php echo $work['color'] == 'Red' ? 'selected' : ''; ?>>Red</option>
                            <option value="Orange" <?php echo $work['color'] == 'Orange' ? 'selected' : ''; ?>>Orange</option>
                            <option value="Green" <?php echo $work['color'] == 'Green' ? 'selected' : ''; ?>>Green</option>
                            <option value="Grey" <?php echo $work['color'] == 'Grey' ? 'selected' : ''; ?>>Grey</option>
                            <option value="Blue" <?php echo $work['color'] == 'Blue' ? 'selected' : ''; ?>>Blue</option>
                            <option value="Silver" <?php echo $work['color'] == 'Silver' ? 'selected' : ''; ?>>Silver</option>
                            <option value="White" <?php echo $work['color'] == 'White' ? 'selected' : ''; ?>>White</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="name" class="form-label">ประเภท <span class="text-danger">*</span></label>
                        <select class="form-control" id="name" name="name" required onchange="updateDescription()">
                            <option value="">เลือกประเภท</option>
                            <option value="จักรยานทางเรียบ" <?php echo $work['name'] == 'จักรยานทางเรียบ' ? 'selected' : ''; ?>>จักรยานทางเรียบ</option>
                            <option value="จักรยานภูเขา" <?php echo $work['name'] == 'จักรยานภูเขา' ? 'selected' : ''; ?>>จักรยานภูเขา</option>
                            <option value="จักรยานไฟฟ้า" <?php echo $work['name'] == 'จักรยานไฟฟ้า' ? 'selected' : ''; ?>>จักรยานไฟฟ้า</option>
                            <option value="จักรยานเด็ก" <?php echo $work['name'] == 'จักรยานเด็ก' ? 'selected' : ''; ?>>จักรยานเด็ก</option>
                        </select>
                        <?php if (isset($errors['name'])): ?><div class="text-danger"><?php echo $errors['name']; ?></div><?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">คำอธิบาย <span class="text-danger">*</span></label>
                        <select class="form-control" id="description" name="description" required>
                            <option value="">เลือกคำอธิบาย</option>
                            <option value="จักรยานสำหรับขี่บนทางเรียบในเมือง" <?php echo $work['description'] == 'จักรยานสำหรับขี่บนทางเรียบในเมือง' ? 'selected' : ''; ?>>จักรยานสำหรับขี่บนทางเรียบในเมือง</option>
                            <option value="จักรยานสำหรับขี่บนทางวิบากและทางธรรมชาติ" <?php echo $work['description'] == 'จักรยานสำหรับขี่บนทางวิบากและทางธรรมชาติ' ? 'selected' : ''; ?>>จักรยานสำหรับขี่บนทางวิบากและทางธรรมชาติ</option>
                            <option value="จักรยานที่มีระบบขับเคลื่อนด้วยไฟฟ้า" <?php echo $work['description'] == 'จักรยานที่มีระบบขับเคลื่อนด้วยไฟฟ้า' ? 'selected' : ''; ?>>จักรยานที่มีระบบขับเคลื่อนด้วยไฟฟ้า</option>
                            <option value="จักรยานขนาดเล็กสำหรับเด็ก" <?php echo $work['description'] == 'จักรยานขนาดเล็กสำหรับเด็ก' ? 'selected' : ''; ?>>จักรยานขนาดเล็กสำหรับเด็ก</option>
                        </select>
                        <?php if (isset($errors['description'])): ?><div class="text-danger"><?php echo $errors['description']; ?></div><?php endif; ?>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> บันทึกงาน
                        </button>
                        <a href="list.php" class="btn btn-secondary">ยกเลิก</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>