<?php
require_once 'includes/db_connect.php';
require_once 'includes/functions.php';
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

    <!-- Main Content -->
    <div class="container my-5">
        <div class="row">
            <div class="col-12">
                <div class="jumbotron bg-light p-5 rounded shadow-sm text-center">
                    <h1 class="display-4">ยินดีต้อนรับสู่ RebikeShop</h1>
                    <p class="lead">ร้านซ่อมจักรยานมืออาชีพ พร้อมให้บริการด้วยความเชี่ยวชาญมากกว่า 10 ปี</p>
                    <hr class="my-4">
                    <p>เรามีบริการซ่อมบำรุงจักรยานทุกประเภท โดยช่างผู้มีความชำนาญ</p>
                </div>
            </div>
        </div>

        <!-- Services -->
        <div class="row mt-5">
            <div class="col-12">
                <h2 class="text-center mb-4">บริการของเรา</h2>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100 text-center shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">ซ่อมจักรยานทั่วไป</h5>
                        <p class="card-text">บริการซ่อมบำรุงจักรยานทุกประเภท โดยช่างผู้ชำนาญการ</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100 text-center shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">เปลี่ยนอะไหล่</h5>
                        <p class="card-text">บริการเปลี่ยนอะไหล่จักรยานแท้จากผู้ผลิต รับประกันคุณภาพ</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100 text-center shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">บริการล้างและปรับแต่ง</h5>
                        <p class="card-text">บริการล้างทำความสะอาดและปรับแต่งจักรยานให้พร้อมใช้งาน</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-3 mt-5">
        <p class="mb-0">© 2025 RebikeShop - All Rights Reserved.</p>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/script.js"></script>
</body>
</html>