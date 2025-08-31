<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; script-src 'self' https://cdn.jsdelivr.net");
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>RebikeShop - ร้านซ่อมจักรยาน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="/NNN.1/css/style.css">
    <style>
        /* Navbar Styling */
        .navbar { background: linear-gradient(90deg, #212529, #343a40); box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2); padding: 0.75rem 1rem; } /* เพิ่ม padding */
        .navbar-brand { font-weight: 700; font-size: 1.8rem; color: #ffffff; transition: color 0.3s ease, transform 0.3s; display: flex; align-items: center; }
        .navbar-brand:hover { color: #0d6efd; transform: scale(1.02); } /* เพิ่ม animation */
        .navbar-brand .bi { font-size: 1.6rem; margin-right: 0.5rem; }
        .nav-link { color: #e9ecef !important; font-size: 1.15rem; font-weight: 500; padding: 0.75rem 1.5rem; display: flex; align-items: center; gap: 0.5rem; transition: color 0.3s ease, background-color 0.3s ease, transform 0.3s; }
        .nav-link:hover, .nav-link:focus { color: #0d6efd !important; background-color: rgba(255, 255, 255, 0.15); border-radius: 8px; transform: translateY(-2px); } /* เพิ่ม lift effect */
        .nav-link.active { color: #ffffff !important; background-color: rgba(13, 110, 253, 0.3); border-radius: 8px; font-weight: 600; }
        .nav-link .bi { font-size: 1.3rem; } /* ขยายไอคอน */
        .navbar-nav .nav-item { list-style-type: none !important; margin-right: 0.75rem; }
        .dropdown-menu { background-color: #2c3035; border: none; border-radius: 12px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2); min-width: 12rem; } /* ปรับ radius */
        .dropdown-item { color: #e9ecef; font-size: 1.1rem; font-weight: 500; padding: 0.75rem 1.5rem; display: flex; align-items: center; gap: 0.5rem; transition: all 0.3s; }
        .dropdown-item:hover, .dropdown-item:focus { color: #0d6efd; background-color: rgba(255, 255, 255, 0.15); transform: translateX(5px); } /* เพิ่ม slide effect */
        .dropdown-item .bi { font-size: 1.25rem; }
        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .navbar-nav { margin-top: 1rem; }
            .nav-link { padding: 0.6rem 1rem; }
            .nav-link .bi, .dropdown-item .bi { font-size: 1.15rem; }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="/NNN.1/index.php"><i class="bi bi-bicycle"></i>RebikeShop</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>" href="/NNN.1/index.php" aria-label="หน้าแรก"><i class="bi bi-house-door-fill"></i>หน้าแรก</a> <!-- เปลี่ยนไอคอนให้ละเอียดขึ้น -->
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'track.php' ? 'active' : ''; ?>" href="/NNN.1/customer/track.php" aria-label="ติดตามการซ่อม"><i class="bi bi-search-heart-fill"></i>ติดตามการซ่อม</a> <!-- เปลี่ยนไอคอน -->
                    </li>
                    <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && $_SESSION['role'] === 'admin'): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo in_array(basename($_SERVER['PHP_SELF']), ['dashboard.php', 'add.php', 'list.php']) ? 'active' : ''; ?>" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-gear-wide-connected"></i>ผู้ดูแล <!-- เปลี่ยนไอคอน -->
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="adminDropdown">
                            <li><a class="dropdown-item" href="/NNN.1/admin/dashboard.php" aria-label="แดชบอร์ด"><i class="bi bi-speedometer2"></i>แดชบอร์ด</a></li>
                            <li><a class="dropdown-item" href="/NNN.1/admin/customers/add.php" aria-label="เพิ่มข้อมูลลูกค้า"><i class="bi bi-person-plus-fill"></i>เพิ่มข้อมูลลูกค้า</a></li> <!-- เปลี่ยนไอคอน -->
                            <li><a class="dropdown-item" href="/NNN.1/admin/customers/list.php" aria-label="รายการลูกค้า"><i class="bi bi-list-stars"></i>รายการลูกค้า</a></li> <!-- เปลี่ยนไอคอน -->
                        </ul>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && $_SESSION['role'] === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/NNN.1/admin/logout.php" aria-label="ออกจากระบบ"><i class="bi bi-box-arrow-right"></i>ออกจากระบบ</a>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/NNN.1/admin/login.php" aria-label="เข้าสู่ระบบ (ผู้ดูแล)"><i class="bi bi-box-arrow-in-right"></i>เข้าสู่ระบบ (ผู้ดูแล)</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container mt-4">
</body>
</html>