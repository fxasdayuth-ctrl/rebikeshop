<?php
session_start();
require_once '../../includes/db_connect.php';
require_once '../../includes/functions.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    header("Location: /NNN.1/admin/login.php");
    exit;
}

// รับค่าการค้นหาและเรียงลำดับ
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$order = isset($_GET['order']) ? $_GET['order'] : 'DESC';

// สร้างคำสั่ง SQL
$sql = "SELECT ro.*, c.id AS customer_id, c.first_name, c.last_name 
        FROM repair_orders ro 
        JOIN customers c ON ro.customer_id = c.id 
        WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (c.first_name LIKE ? OR c.last_name LIKE ? OR ro.order_number LIKE ? OR ro.name LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

// อนุญาตเฉพาะคอลัมน์ที่ปลอดภัยในการเรียงลำดับ
$allowed_sort = ['first_name', 'last_name', 'order_number', 'name', 'created_at'];
if (!in_array($sort, $allowed_sort)) {
    $sort = 'created_at';
}

$allowed_order = ['ASC', 'DESC'];
if (!in_array($order, $allowed_order)) {
    $order = 'DESC';
}

$sql .= " ORDER BY $sort $order";

// ดึงข้อมูลงาน
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$works = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการงาน - RebikeShop</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"> <!-- อัปเดตเวอร์ชัน -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css"> <!-- อัปเดตไอคอนเวอร์ชัน -->
    <link rel="stylesheet" href="/NNN.1/css/style.css">
    <style>
        .dashboard-card { background: #f8f9fa; border-radius: 12px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); padding: 20px; margin-bottom: 20px; transition: transform 0.3s; }
        .dashboard-card:hover { transform: translateY(-3px); }
        .table th { background-color: #0d6efd; color: white; }
        .table-hover tbody tr:hover { background-color: #e9ecef; } /* เพิ่ม hover effect */
        .action-buttons .btn { margin-right: 5px; transition: all 0.2s; }
        .action-buttons .btn:hover { transform: scale(1.05); }
    </style>
</head>
<body>
    <?php include '../../includes/header.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="dashboard-card">
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="h2"><i class="bi bi-list-task me-2"></i>ระบบจัดการงาน</h1> <!-- เพิ่มไอคอนใหม่ -->
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <a href="/NNN.1/admin/customers/add.php" class="btn btn-success"><i class="bi bi-plus-lg me-1"></i>อัปเดตงาน</a> <!-- เปลี่ยนไอคอน -->
                        </div>
                    </div>

                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="mb-3">
                        <form method="GET" action="" class="row g-3">
                            <div class="col-auto">
                                <input type="text" class="form-control" name="search" placeholder="ค้นหา..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i>ค้นหา</button> <!-- เพิ่มไอคอน -->
                            </div>
                        </form>
                    </div>

                    <div class="card table-responsive"> <!-- ห่อด้วย card เพื่อ UI ใหม่ -->
                        <?php if (count($works) > 0): ?>
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th><a href="?sort=order_number&order=<?php echo $sort === 'order_number' && $order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="text-white">เลขที่ออเดอร์ <i class="bi bi-sort-alpha-down"></i></a></th> <!-- เพิ่ม sorting icon -->
                                        <th><a href="?sort=first_name&order=<?php echo $sort === 'first_name' && $order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="text-white">ชื่อลูกค้า <i class="bi bi-person-lines-fill"></i></a></th> <!-- เปลี่ยนไอคอน -->
                                        <th><a href="?sort=name&order=<?php echo $sort === 'name' && $order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="text-white">ชื่องาน <i class="bi bi-wrench-adjustable"></i></a></th> <!-- เปลี่ยนไอคอน -->
                                        <th><a href="?sort=created_at&order=<?php echo $sort === 'created_at' && $order === 'ASC' ? 'DESC' : 'ASC'; ?>" class="text-white">วันที่สร้าง <i class="bi bi-calendar-date"></i></a></th> <!-- เปลี่ยนไอคอน -->
                                        <th>การดำเนินการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($works as $work): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($work['order_number']); ?></td>
                                            <td><?php echo htmlspecialchars($work['first_name'] . ' ' . $work['last_name']); ?></td>
                                            <td><?php echo isset($work['name']) ? htmlspecialchars($work['name']) : '-'; ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($work['created_at'])); ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <a href="/NNN.1/admin/customers/edit.php?id=<?php echo $work['customer_id']; ?>" class="btn btn-sm btn-primary"><i class="bi bi-pencil-square"></i> แก้ไข</a> <!-- เปลี่ยนไอคอน -->
                                                    <a href="/NNN.1/customer/track.php?order_number=<?php echo urlencode($work['order_number']); ?>" class="btn btn-sm btn-info" target="_blank"><i class="bi bi-eye-fill"></i> ดูรายละเอียด</a> <!-- เปลี่ยนไอคอน -->
                                                    <a href="/NNN.1/admin/customers/delete.php?id=<?php echo $work['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('คุณแน่ใจหรือไม่ที่จะลบ?');"><i class="bi bi-trash-fill"></i> ลบ</a> <!-- เปลี่ยนไอคอน -->
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="alert alert-info m-3"><i class="bi bi-info-circle-fill me-2"></i>ไม่พบข้อมูลงาน</div> <!-- เปลี่ยนไอคอน -->
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script> <!-- อัปเดตเวอร์ชัน -->
</body>
</html>