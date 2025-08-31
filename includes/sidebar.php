<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ตรวจสอบว่าผู้ใช้ล็อกอินและเป็น admin
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    header('Location: /NNN.1/admin/login.php');
    exit;
}
?>

<aside class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <h4 class="sidebar-heading px-3 mb-3"><i class="bi bi-gear-fill me-2"></i>เมนูผู้ดูแล</h4>
        <ul class="nav flex-column nav-pills">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>" 
                   href="/NNN.1/admin/dashboard.php" aria-label="แดชบอร์ด">
                    <i class="bi bi-speedometer2 me-2"></i>แดชบอร์ด
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'add.php' ? 'active' : ''; ?>" 
                   href="/NNN.1/admin/customers/add.php" aria-label="เพิ่มข้อมูลลูกค้า">
                    <i class="bi bi-person-plus-fill me-2"></i>เพิ่มข้อมูลลูกค้า
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'list.php' ? 'active' : ''; ?>" 
                   href="/NNN.1/admin/customers/list.php" aria-label="รายการลูกค้า">
                    <i class="bi bi-list-stars me-2"></i>รายการลูกค้า
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/NNN.1/admin/logout.php" aria-label="ออกจากระบบ">
                    <i class="bi bi-box-arrow-right me-2"></i>ออกจากระบบ
                </a>
            </li>
        </ul>
    </div>
</aside>

<style>
    .sidebar {
        min-height: 100vh;
        box-shadow: 2px 0 5px rgba(0,0,0,0.1);
    }
    .sidebar-heading {
        font-size: 1.25rem;
        font-weight: 500;
        color: #343a40;
    }
    .nav-link {
        font-size: 1.1rem;
        color: #495057;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
    }
    .nav-link:hover, .nav-link:focus {
        background-color: #e9ecef;
        color: #0d6efd !important;
        transform: translateX(5px);
    }
    .nav-link.active {
        background-color: #0d6efd;
        color: white !important;
        font-weight: 600;
    }
    .nav-link .bi {
        font-size: 1.3rem;
    }
    @media (max-width: 767.98px) {
        .sidebar {
            min-height: auto;
        }
        .nav-link {
            font-size: 1rem;
            padding: 0.5rem 1rem;
        }
        .nav-link .bi {
            font-size: 1.15rem;
        }
    }
</style>