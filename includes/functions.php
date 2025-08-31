<?php
// ฟังก์ชันสร้างเลขที่ออเดอร์
function generateOrderNumber($pdo) {
    $prefix = 'RB';
    $year = date('Y');
    $month = date('m');
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM repair_orders WHERE YEAR(created_at) = ? AND MONTH(created_at) = ?");
    $stmt->execute([$year, $month]);
    $result = $stmt->fetch();
    $count = $result['count'] + 1;
    
    return $prefix . $year . $month . str_pad($count, 4, '0', STR_PAD_LEFT);
}

// ฟังก์ชันแสดงสถานะเป็นภาษาไทย
function getStatusThai($status) {
    $statuses = [
        'received' => 'รับงานแล้ว',
        'diagnosing' => 'กำลังตรวจสอบ',
        'repairing' => 'กำลังซ่อม',
        'waiting_parts' => 'รออะไหล่',
        'completed' => 'ซ่อมเสร็จแล้ว',
        'delivered' => 'ส่งมอบแล้ว'
    ];
    
    return $statuses[$status] ?? $status;
}

// ฟังก์ชันตรวจสอบการล็อกอิน
function checkAdminLogin() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header('Location: login.php');
        exit;
    }
}

// ฟังก์ชันจัดรูปแบบวันที่ไทย
function thaiDate($date) {
    if (!$date) return '-';
    
    $thaiMonths = [
        'มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน',
        'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม',
        'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'
    ];
    
    $dateTime = new DateTime($date);
    $day = $dateTime->format('j');
    $month = $thaiMonths[(int)$dateTime->format('m') - 1];
    $year = $dateTime->format('Y') + 543;
    
    return $day . ' ' . $month . ' ' . $year;
}

// functions.php - เพิ่มฟังก์ชันเหล่านี้
function thai_date($date_string) {
    if (empty($date_string)) return '-';
    
    $date = new DateTime($date_string);
    $thai_months = [
        '01' => 'มกราคม',
        '02' => 'กุมภาพันธ์',
        '03' => 'มีนาคม',
        '04' => 'เมษายน',
        '05' => 'พฤษภาคม',
        '06' => 'มิถุนายน',
        '07' => 'กรกฎาคม',
        '08' => 'สิงหาคม',
        '09' => 'กันยายน',
        '10' => 'ตุลาคม',
        '11' => 'พฤศจิกายน',
        '12' => 'ธันวาคม'
    ];
    
    $day = $date->format('d');
    $month = $thai_months[$date->format('m')];
    $year = $date->format('Y') + 543; // แปลงเป็นพ.ศ.
    
    return "$day $month $year";
}

function thai_date_time($datetime_string) {
    if (empty($datetime_string)) return '-';
    
    $date = new DateTime($datetime_string);
    $thai_months = [
        '01' => 'ม.ค.',
        '02' => 'ก.พ.',
        '03' => 'มี.ค.',
        '04' => 'เม.ย.',
        '05' => 'พ.ค.',
        '06' => 'มิ.ย.',
        '07' => 'ก.ค.',
        '08' => 'ส.ค.',
        '09' => 'ก.ย.',
        '10' => 'ต.ค.',
        '11' => 'พ.ย.',
        '12' => 'ธ.ค.'
    ];
    
    $day = $date->format('d');
    $month = $thai_months[$date->format('m')];
    $year = $date->format('Y') + 543; // แปลงเป็นพ.ศ.
    $time = $date->format('H:i');
    
    return "$day $month $year $time น.";
}
?>