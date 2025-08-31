<?php
session_start();
require_once '../includes/db_connect.php';

// Set security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; script-src 'self' https://cdn.jsdelivr.net");

// Check if already logged in
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

// CSRF token generation
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = 'คำขอไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง';
    } else {
        $username = filter_var(trim($_POST['username'] ?? ''), FILTER_SANITIZE_STRING);
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน';
        } else {
            try {
                // Prepare and execute query
                $stmt = $pdo->prepare("SELECT id, username, password, role, first_name, last_name, is_active FROM users WHERE username = :username AND is_active = 1");
                $stmt->execute(['username' => $username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user) {
                    // Check if password is hashed or plain text
                    if (password_verify($password, $user['password'])) {
                        // Password is hashed (correct case)
                        $password_valid = true;
                    } elseif ($password === $user['password']) {
                        // Password is plain text (as in your database)
                        $password_valid = true;
                        // Log warning about plain text password
                        error_log("Warning: Plain text password detected for user {$username}. Please update to hashed password.");
                    } else {
                        $password_valid = false;
                    }

                    if ($password_valid) {
                        // Update last login
                        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
                        $stmt->execute(['id' => $user['id']]);

                        // Set session variables
                        session_regenerate_id(true);
                        $_SESSION['logged_in'] = true;
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['first_name'] = $user['first_name'];
                        $_SESSION['last_name'] = $user['last_name'];

                        header('Location: dashboard.php');
                        exit;
                    } else {
                        $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
                    }
                } else {
                    $error = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
                }
            } catch (PDOException $e) {
                error_log("Login error: " . $e->getMessage());
                $error = 'เกิดข้อผิดพลาดในระบบ กรุณาลองใหม่ภายหลัง';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>เข้าสู่ระบบผู้ดูแล - RebikeShop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background-color: #f8f9fa;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }
        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #004085;
        }
        .form-label {
            font-weight: 500;
        }
        .text-logo {
            color: #007bff;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-4">
                    <div class="card p-4">
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <h2 class="text-logo">RebikeShop</h2>
                                <p class="text-muted">ระบบจัดการร้านซ่อมจักรยาน</p>
                            </div>
                            
                            <?php if ($error): ?>
                                <div class="alert alert-danger" role="alert">
                                    <?= htmlspecialchars($error) ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" class="needs-validation" novalidate>
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                <div class="mb-3">
                                    <label for="username" class="form-label">ชื่อผู้ใช้ <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
                                    <div class="invalid-feedback">กรุณากรอกชื่อผู้ใช้</div>
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">รหัสผ่าน <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <div class="invalid-feedback">กรุณากรอกรหัสผ่าน</div>
                                </div>
                                <div class="mb-3 text-end">
                                    <a href="forgot_password.php" class="text-decoration-none">ลืมรหัสผ่าน?</a>
                                </div>
                                <div class="d-grid mb-3">
                                    <button type="submit" class="btn btn-primary"><i class="bi bi-box-arrow-in-right me-2"></i>เข้าสู่ระบบ</button>
                                </div>
                                <div class="text-center">
                                    <a href="../index.php" class="text-decoration-none">กลับไปหน้าแรก</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Bootstrap form validation
        (function () {
            'use strict'
            const forms = document.querySelectorAll('.needs-validation')
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>
</body>
</html>