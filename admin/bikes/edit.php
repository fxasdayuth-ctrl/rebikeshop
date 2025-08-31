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

$error = '';
$success = '';

// ตรวจสอบว่ามี id หรือไม่
$bike_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($bike_id <= 0) {
    header('Location: list.php');
    exit;
}

// ดึงข้อมูลจักรยาน
try {
    $stmt = $conn->prepare("
        SELECT b.id, b.customer_id, b.bike_type_id, b.brand, b.model, b.color, b.serial_number, b.purchase_date,
               c.first_name, c.last_name, bt.name AS bike_type
        FROM bikes b
        LEFT JOIN customers c ON b.customer_id = c.id
        LEFT JOIN bike_types bt ON b.bike_type_id = bt.id
        WHERE b.id = ?
    ");
    $stmt->bind_param("i", $bike_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        header('Location: list.php');
        exit;
    }
    $bike = $result->fetch_assoc();
    $stmt->close();
} catch (Exception $e) {
    $error = 'เกิดข้อผิดพลาดในการดึงข้อมูลจักรยาน: ' . $e->getMessage();
}

// ดึงข้อมูลลูกค้าและประเภทจักรยานสำหรับฟอร์ม
try {
    $customers_stmt = $conn->query("SELECT id, first_name, last_name FROM customers ORDER BY first_name");
    $customers = $customers_stmt->fetch_all(MYSQLI_ASSOC);

    $bike_types_stmt = $conn->query("SELECT id, name FROM bike_types ORDER BY name");
    $bike_types = $bike_types_stmt->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $error = 'เกิดข้อผิดพลาดในการดึงข้อมูล: ' . $e->getMessage();
}

// ตรวจสอบการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = $_POST['customer_id'] ?? '';
    $bike_type_id = $_POST['bike_type_id'] ?? '';
    $brand = trim($_POST['brand'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $color = trim($_POST['color'] ?? '');
    $serial_number = trim($_POST['serial_number'] ?? '');
    $purchase_date = $_POST['purchase_date'] ?? null;

    // ตรวจสอบข้อมูล
    if (empty($customer_id) || empty($bike_type_id) || empty($brand)) {
        $error = 'กรุณากรอกข้อมูลที่จำเป็นให้ครบ';
    } else {
        try {
            $stmt = $conn->prepare("
                UPDATE bikes 
                SET customer_id = ?, bike_type_id = ?, brand = ?, model = ?, color = ?, serial_number = ?, purchase_date = ?, updated_at = NOW()
                WHERE id = ?
            ");
            if (!$stmt) {
                throw new Exception("ข้อผิดพลาดในการเตรียมคำสั่ง SQL: " . $conn->error);
            }
            $stmt->bind_param("iisssssi", $customer_id, $bike_type_id, $brand, $model, $color, $serial_number, $purchase_date, $bike_id);
            
            if ($stmt->execute()) {
                $success = 'แก้ไขข้อมูลจักรยานสำเร็จ!';
            } else {
                $error = 'เกิดข้อผิดพลาดในการแก้ไขข้อมูล: ' . $stmt->error;
            }
            $stmt->close();
        } catch (Exception $e) {
            $error = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แก้ไขจักรยาน - RebikeShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .form-container {
            max-width: 600px;
            margin: 50px auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h2 class="mb-4">แก้ไขข้อมูลจักรยาน</h2>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label for="customer_id" class="form-label">เจ้าของจักรยาน <span class="text-danger">*</span></label>
                    <select class="form-select" id="customer_id" name="customer_id" required>
                        <option value="">-- เลือกลูกค้า --</option>
                        <?php foreach ($customers as $customer): ?>
                            <option value="<?= $customer['id'] ?>" <?= $bike['customer_id'] == $customer['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="bike_type_id" class="form-label">ประเภทจักรยาน <span class="text-danger">*</span></label>
                    <select class="form-select" id="bike_type_id" name="bike_type_id" required>
                        <option value="">-- เลือกประเภท --</option>
                        <?php foreach ($bike_types as $bike_type): ?>
                            <option value="<?= $bike_type['id'] ?>" <?= $bike['bike_type_id'] == $bike_type['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($bike_type['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="brand" class="form-label">ยี่ห้อ <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="brand" name="brand" value="<?= htmlspecialchars($bike['brand']) ?>" required>
                </div>
                <div class="mb-3">
                    <label for="model" class="form-label">รุ่น</label>
                    <input type="text" class="form-control" id="model" name="model" value="<?= htmlspecialchars($bike['model'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label for="color" class="form-label">สี</label>
                    <input type="text" class="form-control" id="color" name="color" value="<?= htmlspecialchars($bike['color'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label for="serial_number" class="form-label">หมายเลขซีเรียล</label>
                    <input type="text" class="form-control" id="serial_number" name="serial_number" value="<?= htmlspecialchars($bike['serial_number'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label for="purchase_date" class="form-label">วันที่ซื้อ</label>
                    <input type="date" class="form-control" id="purchase_date" name="purchase_date" value="<?= htmlspecialchars($bike['purchase_date'] ?? '') ?>">
                </div>
                <button type="submit" class="btn btn-primary w-100">บันทึกการเปลี่ยนแปลง</button>
            </form>

            <div class="mt-3">
                <a href="list.php" class="btn btn-secondary">กลับไปที่รายการจักรยาน</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
```