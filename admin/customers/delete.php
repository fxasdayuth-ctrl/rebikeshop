<?php
session_start();
require_once '../../includes/db_connect.php';
require_once '../../includes/functions.php';

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// ตรวจสอบว่ามี ID ที่จะลบหรือไม่
if (!isset($_POST['id']) || empty($_POST['id'])) {
    $_SESSION['error'] = "ไม่พบ ID จักรยาน";
    header("Location: list.php");
    exit;
}

$bike_id = intval($_POST['id']);

// ตรวจสอบว่าจักรยานถูกใช้ในออเดอร์ซ่อมหรือไม่
$check_order_query = "SELECT COUNT(*) as order_count FROM repair_orders WHERE bike_id = ?";
$check_stmt = $conn->prepare($check_order_query);
$check_stmt->bind_param("i", $bike_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$order_count = $check_result->fetch_assoc()['order_count'];
$check_stmt->close();

if ($order_count > 0) {
    $_SESSION['error'] = "ไม่สามารถลบจักรยานได้ เนื่องจากมีออเดอร์ซ่อมที่เกี่ยวข้องอยู่";
    header("Location: list.php");
    exit;
}

// ลบข้อมูลจักรยาน
$delete_stmt = $conn->prepare("DELETE FROM bikes WHERE id = ?");
$delete_stmt->bind_param("i", $bike_id);

if ($delete_stmt->execute()) {
    $_SESSION['success'] = "ลบข้อมูลจักรยานเรียบร้อยแล้ว";
} else {
    $_SESSION['error'] = "เกิดข้อผิดพลาดในการลบข้อมูล: " . $conn->error;
}

$delete_stmt->close();
$conn->close();

header("Location: list.php");
exit;
?>