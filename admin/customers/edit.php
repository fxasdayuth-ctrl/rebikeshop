<?php
session_start();
require_once '../../includes/db_connect.php';
require_once '../../includes/functions.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

// ตรวจสอบว่ามี ID ที่ส่งมาหรือไม่
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = 'ไม่พบลูกค้าที่ต้องการแก้ไข';
    header("Location: list.php");
    exit;
}

$customer_id = $_GET['id'];
$errors = [];
$customer = null;
$bike = null;
$status = '';
$total_cost = null;

// ดึงข้อมูลลูกค้าจากฐานข้อมูล
$stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->execute([$customer_id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

// ดึงข้อมูลจักรยานจากตาราง bikes
$stmt = $pdo->prepare("SELECT brand, model, color, bike_type_id FROM bikes WHERE customer_id = ? LIMIT 1");
$stmt->execute([$customer_id]);
$bike = $stmt->fetch(PDO::FETCH_ASSOC);

// ดึงสถานะและราคาจากตาราง repair_orders
$stmt = $pdo->prepare("SELECT status, total_cost FROM repair_orders WHERE customer_id = ? LIMIT 1");
$stmt->execute([$customer_id]);
$repair_order = $stmt->fetch(PDO::FETCH_ASSOC);
$status = $repair_order ? $repair_order['status'] : '';
$total_cost = $repair_order ? $repair_order['total_cost'] : null;

// ดึงประเภทจักรยานจากตาราง bike_types
$stmt = $pdo->prepare("SELECT id, name FROM bike_types");
$stmt->execute();
$bike_types = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ตรวจสอบว่าพบลูกค้าหรือไม่
if (!$customer) {
    $_SESSION['error'] = 'ไม่พบลูกค้าที่ต้องการแก้ไข';
    header("Location: list.php");
    exit;
}

// เมื่อฟอร์มถูกส่ง
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // รับค่าจากฟอร์มพร้อมตรวจสอบว่า key มีอยู่
    $first_name = isset($_POST['first_name']) ? trim($_POST['first_name']) : $customer['first_name'];
    $last_name = isset($_POST['last_name']) ? trim($_POST['last_name']) : $customer['last_name'];
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : $customer['phone'];
    $email = isset($_POST['email']) ? trim($_POST['email']) : $customer['email'];
    $address = isset($_POST['address']) ? trim($_POST['address']) : $customer['address'];
    $district = isset($_POST['district']) ? trim($_POST['district']) : $customer['district'];
    $province = isset($_POST['province']) ? trim($_POST['province']) : $customer['province'];
    $zip_code = isset($_POST['zip_code']) ? trim($_POST['zip_code']) : $customer['zip_code'];
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : $customer['notes'];
    $brand = isset($_POST['brand']) ? trim($_POST['brand']) : ($bike ? $bike['brand'] : '');
    $model = isset($_POST['model']) ? trim($_POST['model']) : ($bike ? $bike['model'] : '');
    $color = isset($_POST['color']) ? trim($_POST['color']) : ($bike ? $bike['color'] : '');
    $bike_type_id = isset($_POST['bike_type_id']) ? trim($_POST['bike_type_id']) : ($bike ? $bike['bike_type_id'] : '');
    $status = isset($_POST['status']) ? trim($_POST['status']) : $status;
    $total_cost_post = isset($_POST['total_cost']) ? trim($_POST['total_cost']) : $total_cost;

    // ตรวจสอบความถูกต้อง
    if (empty($first_name)) {
        $errors['first_name'] = 'กรุณากรอกชื่อ';
    }

    if (empty($last_name)) {
        $errors['last_name'] = 'กรุณากรอกนามสกุล';
    }

    if (empty($phone)) {
        $errors['phone'] = 'กรุณากรอกเบอร์โทรศัพท์';
    } elseif (!preg_match('/^[0-9]{10,15}$/', $phone)) {
        $errors['phone'] = 'รูปแบบเบอร์โทรศัพท์ไม่ถูกต้อง';
    } else {
        // ตรวจสอบว่าเบอร์โทรซ้ำหรือไม่ (ยกเว้นลูกค้าปัจจุบัน)
        $stmt = $pdo->prepare("SELECT id FROM customers WHERE phone = ? AND id != ?");
        $stmt->execute([$phone, $customer_id]);
        if ($stmt->fetch()) {
            $errors['phone'] = 'เบอร์โทรศัพท์นี้มีอยู่ในระบบแล้ว';
        }
    }

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'รูปแบบอีเมลไม่ถูกต้อง';
    }

    if (empty($brand)) {
        $errors['brand'] = 'กรุณาเลือกยี่ห้อ';
    }

    if (empty($bike_type_id)) {
        $errors['bike_type_id'] = 'กรุณาเลือกประเภทจักรยาน';
    }

    // ตรวจสอบสถานะ
    $allowed_statuses = ['received', 'diagnosing', 'repairing', 'waiting_parts', 'completed', 'delivered'];
    if (empty($status)) {
        $errors['status'] = 'กรุณาเลือกสถานะ';
    } elseif (!in_array($status, $allowed_statuses)) {
        $errors['status'] = 'สถานะไม่ถูกต้อง';
    }

    // ตรวจสอบ total_cost
    if (!empty($total_cost_post) && !is_numeric($total_cost_post)) {
        $errors['total_cost'] = 'ราคาค่าซ่อมต้องเป็นตัวเลข';
    } elseif (!empty($total_cost_post) && $total_cost_post < 0) {
        $errors['total_cost'] = 'ราคาค่าซ่อมต้องมากกว่าหรือเท่ากับ 0';
    }

    // หากไม่มี error ให้อัปเดตข้อมูล
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // อัปเดตข้อมูลลูกค้า
            $stmt = $pdo->prepare("UPDATE customers SET first_name = ?, last_name = ?, phone = ?, email = ?, 
                                  address = ?, district = ?, province = ?, zip_code = ?, notes = ?, updated_at = NOW() 
                                  WHERE id = ?");
            $stmt->execute([
                $first_name,
                $last_name,
                $phone,
                $email,
                $address,
                $district,
                $province,
                $zip_code,
                $notes,
                $customer_id
            ]);

            // ตรวจสอบว่ามีจักรยานในตาราง bikes หรือไม่
            $stmt = $pdo->prepare("SELECT id FROM bikes WHERE customer_id = ?");
            $stmt->execute([$customer_id]);
            $existing_bike = $stmt->fetch();

            if ($existing_bike) {
                // อัปเดตข้อมูลจักรยาน
                $stmt = $pdo->prepare("UPDATE bikes SET brand = ?, model = ?, color = ?, bike_type_id = ? 
                                      WHERE customer_id = ?");
                $stmt->execute([$brand, $model, $color, $bike_type_id, $customer_id]);
            } else {
                // สร้างข้อมูลจักรยานใหม่
                $stmt = $pdo->prepare("INSERT INTO bikes (customer_id, brand, model, color, bike_type_id, created_at) 
                                      VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$customer_id, $brand, $model, $color, $bike_type_id]);
            }

            // ตรวจสอบว่ามี repair order หรือไม่
            $stmt = $pdo->prepare("SELECT id FROM repair_orders WHERE customer_id = ?");
            $stmt->execute([$customer_id]);
            $existing_order = $stmt->fetch();

            $total_cost_update = !empty($total_cost_post) ? $total_cost_post : null;

            if ($existing_order) {
                // อัปเดตสถานะและราคาใน repair_orders
                $stmt = $pdo->prepare("UPDATE repair_orders SET status = ?, total_cost = ?, updated_at = NOW() WHERE customer_id = ?");
                $stmt->execute([$status, $total_cost_update, $customer_id]);
            } else {
                // สร้าง repair order ใหม่
                $stmt = $pdo->prepare("INSERT INTO repair_orders (customer_id, bike_id, status, total_cost, created_at) 
                                      VALUES (?, ?, ?, ?, NOW())");
                $bike_id = $existing_bike ? $existing_bike['id'] : $pdo->lastInsertId();
                $stmt->execute([$customer_id, $bike_id, $status, $total_cost_update]);
            }

            $pdo->commit();
            $_SESSION['success'] = 'อัปเดตข้อมูลลูกค้า จักรยาน และสถานะเรียบร้อยแล้ว';
            header("Location: list.php");
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errors['database'] = 'เกิดข้อผิดพลาดในการอัปเดตข้อมูล: ' . $e->getMessage();
        }
    }

    // อัปเดตข้อมูลในตัวแปร $customer และ $bike สำหรับแสดงในฟอร์ม
    $customer = [
        'first_name' => $first_name,
        'last_name' => $last_name,
        'phone' => $phone,
        'email' => $email,
        'address' => $address,
        'district' => $district,
        'province' => $province,
        'zip_code' => $zip_code,
        'notes' => $notes
    ];
    $bike = [
        'brand' => $brand,
        'model' => $model,
        'color' => $color,
        'bike_type_id' => $bike_type_id
    ];
    $total_cost = $total_cost_post;
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขข้อมูลลูกค้า - ReBikeShop Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../css/style.css">
</head>
<body>
    <?php include '../../includes/header.php'; ?>

        <main class="flex-grow-1 p-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="bi bi-pencil-square me-2"></i>แก้ไขข้อมูลลูกค้า #<?php echo $customer_id; ?></h4>
                    <a href="list.php" class="btn btn-light"><i class="bi bi-arrow-left me-1"></i>กลับไปยังรายชื่อลูกค้า</a>
                </div>
                <div class="card-body">
                    <?php if (isset($errors['database'])): ?>
                        <div class="alert alert-danger"><?php echo $errors['database']; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label for="first_name">ชื่อ <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?php echo isset($errors['first_name']) ? 'is-invalid' : ''; ?>" id="first_name" name="first_name" value="<?php echo htmlspecialchars($customer['first_name']); ?>" required>
                                <?php if (isset($errors['first_name'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['first_name']; ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="last_name">นามสกุล <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?php echo isset($errors['last_name']) ? 'is-invalid' : ''; ?>" id="last_name" name="last_name" value="<?php echo htmlspecialchars($customer['last_name']); ?>" required>
                                <?php if (isset($errors['last_name'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['last_name']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label for="phone">เบอร์โทรศัพท์ <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?php echo isset($errors['phone']) ? 'is-invalid' : ''; ?>" id="phone" name="phone" value="<?php echo htmlspecialchars($customer['phone']); ?>" required>
                                <?php if (isset($errors['phone'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['phone']; ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="email">อีเมล</label>
                                <input type="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" id="email" name="email" value="<?php echo htmlspecialchars($customer['email']); ?>">
                                <?php if (isset($errors['email'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label for="address">ที่อยู่</label>
                                <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($customer['address']); ?>">
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="district">อำเภอ</label>
                                <input type="text" class="form-control" id="district" name="district" value="<?php echo htmlspecialchars($customer['district']); ?>">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label for="province">จังหวัด</label>
                                <input type="text" class="form-control" id="province" name="province" value="<?php echo htmlspecialchars($customer['province']); ?>">
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="zip_code">รหัสไปรษณีย์</label>
                                <input type="text" class="form-control" id="zip_code" name="zip_code" value="<?php echo htmlspecialchars($customer['zip_code']); ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="notes">หมายเหตุ</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo htmlspecialchars($customer['notes']); ?></textarea>
                        </div>

                        <h5 class="mt-4">ข้อมูลจักรยาน</h5>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label for="brand">ยี่ห้อ <span class="text-danger">*</span></label>
                                <select class="form-select <?php echo isset($errors['brand']) ? 'is-invalid' : ''; ?>" id="brand" name="brand" required>
                                    <option value="">เลือกยี่ห้อ</option>
                                    <option value="Trek" <?php echo ($bike && $bike['brand'] == 'Trek') ? 'selected' : ''; ?>>Trek</option>
                                    <option value="Scott" <?php echo ($bike && $bike['brand'] == 'Scott') ? 'selected' : ''; ?>>Scott</option>
                                    <option value="Specialized" <?php echo ($bike && $bike['brand'] == 'Specialized') ? 'selected' : ''; ?>>Specialized</option>
                                    <option value="Strider" <?php echo ($bike && $bike['brand'] == 'Strider') ? 'selected' : ''; ?>>Strider</option>
                                    <option value="Schwinn" <?php echo ($bike && $bike['brand'] == 'Schwinn') ? 'selected' : ''; ?>>Schwinn</option>
                                    <option value="Rad Power" <?php echo ($bike && $bike['brand'] == 'Rad Power') ? 'selected' : ''; ?>>Rad Power</option>
                                    <option value="Merida" <?php echo ($bike && $bike['brand'] == 'Merida') ? 'selected' : ''; ?>>Merida</option>
                                    <option value="Giant" <?php echo ($bike && $bike['brand'] == 'Giant') ? 'selected' : ''; ?>>Giant</option>
                                    <option value="Cannondale" <?php echo ($bike && $bike['brand'] == 'Cannondale') ? 'selected' : ''; ?>>Cannondale</option>
                                    <option value="Bianchi" <?php echo ($bike && $bike['brand'] == 'Bianchi') ? 'selected' : ''; ?>>Bianchi</option>
                                </select>
                                <?php if (isset($errors['brand'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['brand']; ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="model">รุ่น</label>
                                <select class="form-select" id="model" name="model">
                                    <option value="">เลือกรุ่น</option>
                                    <option value="Marlin 5" <?php echo ($bike && $bike['model'] == 'Marlin 5') ? 'selected' : ''; ?>>Marlin 5</option>
                                    <option value="Defy Advanced" <?php echo ($bike && $bike['model'] == 'Defy Advanced') ? 'selected' : ''; ?>>Defy Advanced</option>
                                    <option value="Turbo Vado" <?php echo ($bike && $bike['model'] == 'Turbo Vado') ? 'selected' : ''; ?>>Turbo Vado</option>
                                    <option value="Kids Balance" <?php echo ($bike && $bike['model'] == 'Kids Balance') ? 'selected' : ''; ?>>Kids Balance</option>
                                    <option value="Trail 6" <?php echo ($bike && $bike['model'] == 'Trail 6') ? 'selected' : ''; ?>>Trail 6</option>
                                    <option value="Aria" <?php echo ($bike && $bike['model'] == 'Aria') ? 'selected' : ''; ?>>Aria</option>
                                    <option value="RadRover 6" <?php echo ($bike && $bike['model'] == 'RadRover 6') ? 'selected' : ''; ?>>RadRover 6</option>
                                    <option value="12 Sport" <?php echo ($bike && $bike['model'] == '12 Sport') ? 'selected' : ''; ?>>12 Sport</option>
                                    <option value="Scale 970" <?php echo ($bike && $bike['model'] == 'Scale 970') ? 'selected' : ''; ?>>Scale 970</option>
                                    <option value="Scultura 400" <?php echo ($bike && $bike['model'] == 'Scultura 400') ? 'selected' : ''; ?>>Scultura 400</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label for="color">สี</label>
                                <select class="form-select" id="color" name="color">
                                    <option value="">เลือกสี</option>
                                    <option value="Black" <?php echo ($bike && $bike['color'] == 'Black') ? 'selected' : ''; ?>>Black</option>
                                    <option value="Blue" <?php echo ($bike && $bike['color'] == 'Blue') ? 'selected' : ''; ?>>Blue</option>
                                    <option value="Red" <?php echo ($bike && $bike['color'] == 'Red') ? 'selected' : ''; ?>>Red</option>
                                    <option value="Green" <?php echo ($bike && $bike['color'] == 'Green') ? 'selected' : ''; ?>>Green</option>
                                    <option value="Silver" <?php echo ($bike && $bike['color'] == 'Silver') ? 'selected' : ''; ?>>Silver</option>
                                    <option value="White" <?php echo ($bike && $bike['color'] == 'White') ? 'selected' : ''; ?>>White</option>
                                    <option value="Yellow" <?php echo ($bike && $bike['color'] == 'Yellow') ? 'selected' : ''; ?>>Yellow</option>
                                    <option value="Orange" <?php echo ($bike && $bike['color'] == 'Orange') ? 'selected' : ''; ?>>Orange</option>
                                    <option value="Grey" <?php echo ($bike && $bike['color'] == 'Grey') ? 'selected' : ''; ?>>Grey</option>
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label for="bike_type_id">ประเภท <span class="text-danger">*</span></label>
                                <select class="form-select <?php echo isset($errors['bike_type_id']) ? 'is-invalid' : ''; ?>" id="bike_type_id" name="bike_type_id" required>
                                    <option value="">เลือกประเภท</option>
                                    <?php foreach ($bike_types as $type): ?>
                                        <option value="<?php echo $type['id']; ?>" <?php echo ($bike && $bike['bike_type_id'] == $type['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($type['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['bike_type_id'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['bike_type_id']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="status">สถานะงานซ่อม <span class="text-danger">*</span></label>
                            <select class="form-select <?php echo isset($errors['status']) ? 'is-invalid' : ''; ?>" id="status" name="status" required>
                                <option value="">เลือกสถานะ</option>
                                <option value="received" <?php echo $status == 'received' ? 'selected' : ''; ?>>รับงานแล้ว</option>
                                <option value="diagnosing" <?php echo $status == 'diagnosing' ? 'selected' : ''; ?>>กำลังวินิจฉัยปัญหา</option>
                                <option value="repairing" <?php echo $status == 'repairing' ? 'selected' : ''; ?>>กำลังซ่อม</option>
                                <option value="waiting_parts" <?php echo $status == 'waiting_parts' ? 'selected' : ''; ?>>รออะไหล่</option>
                                <option value="completed" <?php echo $status == 'completed' ? 'selected' : ''; ?>>ซ่อมเสร็จแล้ว</option>
                                <option value="delivered" <?php echo $status == 'delivered' ? 'selected' : ''; ?>>ส่งมอบแล้ว</option>
                            </select>
                            <?php if (isset($errors['status'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['status']; ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label for="total_cost">ราคาค่าซ่อม (บาท)</label>
                            <input type="number" step="0.01" min="0" class="form-control <?php echo isset($errors['total_cost']) ? 'is-invalid' : ''; ?>" id="total_cost" name="total_cost" value="<?php echo htmlspecialchars($total_cost); ?>">
                            <?php if (isset($errors['total_cost'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['total_cost']; ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-save-fill me-1"></i>อัปเดตข้อมูล</button>
                            <a href="list.php" class="btn btn-secondary"><i class="bi bi-x-circle me-1"></i>ยกเลิก</a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>