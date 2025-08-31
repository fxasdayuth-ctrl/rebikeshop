<?php
session_start();

// ตรวจสอบว่าล็อกอินหรือไม่
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || $_SESSION['role'] !== 'admin') {
    header('Location: /NNN.1/admin/login.php');
    exit;
}

require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// คิวรี่ข้อมูลสถิติ
$stmt = $pdo->query("SELECT COUNT(*) as total FROM repair_orders");
$totalOrders = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM customers");
$totalCustomers = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM bikes");
$totalBikes = $stmt->fetch()['total'];

// คิวรี่จำนวนออเดอร์ตามสถานะ
$statusCounts = [
    'received' => 0,
    'diagnosing' => 0,
    'repairing' => 0,
    'waiting_parts' => 0,
    'completed' => 0,
    'delivered' => 0
];

$stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM repair_orders GROUP BY status");
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($results as $row) {
    if (array_key_exists($row['status'], $statusCounts)) {
        $statusCounts[$row['status']] = $row['count'];
    }
}

// คิวรี่ข้อมูลสำหรับกราฟเส้น (จำนวนออเดอร์ต่อวันในช่วง 7 วันล่าสุด)
$lineChartData = [];
$dates = [];
$startDate = date('Y-m-d', strtotime('-6 days'));
$endDate = date('Y-m-d');
$stmt = $pdo->prepare("
    SELECT DATE(received_date) as order_date, COUNT(*) as order_count
    FROM repair_orders
    WHERE DATE(received_date) BETWEEN ? AND ?
    GROUP BY DATE(received_date)
    ORDER BY order_date
");
$stmt->execute([$startDate, $endDate]);
$orderCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$currentDate = new DateTime($startDate);
$endDateObj = new DateTime($endDate);
while ($currentDate <= $endDateObj) {
    $dates[] = $currentDate->format('Y-m-d');
    $currentDate->modify('+1 day');
}

$orderCountMap = array_column($orderCounts, 'order_count', 'order_date');
$lineChartData = array_map(function($date) use ($orderCountMap) {
    return isset($orderCountMap[$date]) ? (int)$orderCountMap[$date] : 0;
}, $dates);

// คิวรี่ข้อมูลสำหรับกราฟวงกลม (การกระจายตามยี่ห้อจักรยาน)
$pieChartData = [];
$stmt = $pdo->prepare("SELECT brand, COUNT(*) as count FROM bikes GROUP BY brand");
$stmt->execute();
$brandCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// คิวรี่ออเดอร์ล่าสุด
$stmt = $pdo->query("
    SELECT ro.*, c.first_name, c.last_name 
    FROM repair_orders ro 
    JOIN customers c ON ro.customer_id = c.id 
    ORDER BY ro.created_at DESC 
    LIMIT 5
");
$recentOrders = $stmt->fetchAll();

// แปลงสถานะเป็นภาษาไทย (ใช้ฟังก์ชันจาก functions.php)
if (!function_exists('getStatusThai')) {
    function getStatusThai($status) {
        $statuses = [
            'received' => 'รับงานแล้ว',
            'diagnosing' => 'กำลังวินิจฉัยปัญหา',
            'repairing' => 'กำลังซ่อม',
            'waiting_parts' => 'รออะไหล่',
            'completed' => 'ซ่อมเสร็จแล้ว',
            'delivered' => 'ส่งมอบแล้ว'
        ];
        return isset($statuses[$status]) ? $statuses[$status] : $status;
    }
}

// ฟังก์ชันสำหรับสีสถานะ
if (!function_exists('getStatusColor')) {
    function getStatusColor($status) {
        $colors = [
            'received' => 'bg-primary',
            'diagnosing' => 'bg-info',
            'repairing' => 'bg-warning',
            'waiting_parts' => 'bg-secondary',
            'completed' => 'bg-success',
            'delivered' => 'bg-dark'
        ];
        return $colors[$status] ?? 'bg-secondary';
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แดชบอร์ดผู้ดูแล - ReBikeShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <style>
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --info-color: #0dcaf0;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --bg-gradient: linear-gradient(135deg, #0d6efd, #6610f2);
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--light-color);
            color: var(--dark-color);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .navbar {
            background: var(--bg-gradient) !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            font-weight: 600;
            color: white !important;
        }

        .nav-link {
            color: rgba(255,255,255,0.8) !important;
            transition: color 0.3s;
        }

        .nav-link:hover, .nav-link.active {
            color: white !important;
        }

        .container {
            max-width: 1200px;
        }

        .card {
            border: none;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }

        .card-header {
            background: var(--bg-gradient);
            color: white;
            padding: 1.5rem;
            border-radius: 20px 20px 0 0 !important;
        }

        .stats-card {
            background: linear-gradient(135deg, #ffffff, #f8f9fa);
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            transition: transform 0.3s;
        }

        .stats-card:hover {
            transform: scale(1.05);
        }

        .stats-card .icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }

        .btn-primary {
            background: var(--bg-gradient);
            border: none;
            border-radius: 50px;
            padding: 0.75rem 2rem;
            font-weight: 500;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .btn-primary:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(13,110,253,0.4);
        }

        .table {
            border-radius: 15px;
            overflow: hidden;
        }

        .table th {
            background: rgba(13,110,253,0.1);
            border-top: none;
        }

        .table-hover tbody tr:hover {
            background: rgba(13,110,253,0.05);
            transition: background 0.3s;
        }

        footer {
            background: var(--bg-gradient);
            color: white;
            padding: 2rem 0;
            margin-top: auto;
        }

        .chart-container {
            position: relative;
            max-width: 100%;
            height: 300px;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="/NNN.1/index.php">ReBikeShop</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link active" href="/NNN.1/index.php">หน้าหลัก</a></li>
                    <li class="nav-item"><a class="nav-link" href="/NNN.1/admin/orders/list.php">แดชบอร์ดผู้ดูแลร์</a></li>
                    <li class="nav-item"><a class="nav-link" href="/NNN.1/admin/customers/list.php">ระบบจัดการงาน</a></li>
                    <li class="nav-item"><a class="nav-link" href="/NNN.1/admin/customers/add.php">เพิ่มงานใหม่</a></li>
                    <li class="nav-item"><a class="nav-link" href="/NNN.1/admin/logout.php">ออกจากระบบ</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5 flex-grow-1">
        <h1 class="text-center mb-5 fw-bold"><i class="fas fa-tachometer-alt me-2"></i>แดชบอร์ดผู้ดูแล</h1>

        <!-- สถิติทั่วไป -->
        <div class="row mb-4 g-4">
            <div class="col-md-4">
                <div class="stats-card">
                    <i class="fas fa-file-invoice icon"></i>
                    <h5 class="fw-bold">ออเดอร์ทั้งหมด</h5>
                    <p class="display-4 text-primary"><?php echo $totalOrders; ?></p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <i class="fas fa-users icon"></i>
                    <h5 class="fw-bold">ลูกค้าทั้งหมด</h5>
                    <p class="display-4 text-success"><?php echo $totalCustomers; ?></p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <i class="fas fa-bicycle icon"></i>
                    <h5 class="fw-bold">จักรยานทั้งหมด</h5>
                    <p class="display-4 text-info"><?php echo $totalBikes; ?></p>
                </div>
            </div>
        </div>

        <!-- กราฟสถานะการซ่อม (Bar Chart) -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>สถานะการซ่อม</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="statusBarChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- กราฟแนวโน้มออเดอร์ (Line Chart) -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>แนวโน้มจำนวนออเดอร์ (7 วันล่าสุด)</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="ordersLineChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- กราฟการกระจายยี่ห้อจักรยาน (Pie Chart) -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>การกระจายตามยี่ห้อจักรยาน</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="brandPieChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ออเดอร์ล่าสุด -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>ออเดอร์ล่าสุด</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>เลขที่ออเดอร์</th>
                                        <th>ลูกค้า</th>
                                        <th>วันที่รับ</th>
                                        <th>สถานะ</th>
                                        <th>การดำเนินการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentOrders as $order): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                            <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars(thai_date($order['received_date'], 'full')); ?></td>
                                            <td>
                                                <span class="badge <?php echo getStatusColor($order['status']); ?>">
                                                    <?php echo htmlspecialchars(getStatusThai($order['status'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="/NNN.1/customer/track.php?order_number=<?php echo urlencode($order['order_number']); ?>" class="btn btn-sm btn-primary"><i class="fas fa-eye me-1"></i>ดูรายละเอียด
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="mt-auto">
        <p class="mb-0 text-center">&copy; 2025 ReBikeShop - All Rights Reserved.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Bar Chart: สถานะการซ่อม
        const statusBarChart = new Chart(document.getElementById('statusBarChart'), {
            type: 'bar',
            data: {
                labels: [
                    'รับงานแล้ว',
                    'กำลังวินิจฉัย',
                    'กำลังซ่อม',
                    'รออะไหล่',
                    'ซ่อมเสร็จ',
                    'ส่งมอบแล้ว'
                ],
                datasets: [{
                    label: 'จำนวนออเดอร์',
                    data: [
                        <?php echo $statusCounts['received']; ?>,
                        <?php echo $statusCounts['diagnosing']; ?>,
                        <?php echo $statusCounts['repairing']; ?>,
                        <?php echo $statusCounts['waiting_parts']; ?>,
                        <?php echo $statusCounts['completed']; ?>,
                        <?php echo $statusCounts['delivered']; ?>
                    ],
                    backgroundColor: [
                        '#0d6efd',
                        '#0dcaf0',
                        '#ffc107',
                        '#6c757d',
                        '#198754',
                        '#212529'
                    ],
                    borderColor: '#ffffff',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    title: { display: true, text: 'จำนวนออเดอร์ตามสถานะ', font: { size: 16 } }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'จำนวนออเดอร์' }
                    },
                    x: {
                        title: { display: true, text: 'สถานะ' }
                    }
                }
            }
        });

        // Line Chart: แนวโน้มจำนวนออเดอร์
        const ordersLineChart = new Chart(document.getElementById('ordersLineChart'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode($dates); ?>,
                datasets: [{
                    label: 'จำนวนออเดอร์',
                    data: <?php echo json_encode($lineChartData); ?>,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.2)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true },
                    title: { display: true, text: 'แนวโน้มจำนวนออเดอร์ (7 วันล่าสุด)', font: { size: 16 } }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'จำนวนออเดอร์' }
                    },
                    x: {
                        title: { display: true, text: 'วันที่' }
                    }
                }
            }
        });

        // Pie Chart: การกระจายตามยี่ห้อจักรยาน
        const brandPieChart = new Chart(document.getElementById('brandPieChart'), {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_column($brandCounts, 'brand')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($brandCounts, 'count')); ?>,
                    backgroundColor: [
                        '#0d6efd', '#198754', '#ffc107', '#dc3545', '#6c757d',
                        '#0dcaf0', '#6610f2', '#fd7e14', '#20c997', '#adb5bd'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'right' },
                    title: { display: true, text: 'การกระจายตามยี่ห้อจักรยาน', font: { size: 16 } }
                }
            }
        });
    </script>
</body>
</html>