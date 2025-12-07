<?php
session_start();
require_once '../config/database.php';
require_once '../controllers/AuthController.php';

// Jika sudah login, lempar ke dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: index.php");
    exit;
}

$error = '';

// Proses Login saat form disubmit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $auth = new AuthController($db);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $result = $auth->login($username, $password);

    if ($result['status'] == 'success') {
        header("Location: index.php");
        exit;
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Retail Pusat</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f4f6f9;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            background: #fff;
            padding: 2rem;
        }
        .form-control {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            padding: 12px;
            border-radius: 8px;
        }
        .form-control:focus {
            background-color: #fff;
            box-shadow: none;
            border-color: #0d6efd;
        }
        .btn-primary {
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            background-color: #2c3e50; /* Warna gelap elegan */
            border: none;
        }
        .btn-primary:hover {
            background-color: #1a252f;
        }
        .brand-logo {
            font-weight: 700;
            color: #2c3e50;
            letter-spacing: -0.5px;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="text-center mb-4">
            <h4 class="brand-logo mb-1">RETAIL PUSAT</h4>
            <p class="text-muted small">Masuk untuk mengelola sistem terdistribusi</p>
        </div>

        <?php if($error): ?>
            <div class="alert alert-danger py-2 small rounded-3 border-0 bg-danger bg-opacity-10 text-danger mb-3">
                <i class="bi bi-exclamation-circle me-1"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label small text-muted fw-bold">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Masukkan username admin" required autofocus>
            </div>
            <div class="mb-4">
                <label class="form-label small text-muted fw-bold">Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">MASUK DASHBOARD</button>
        </form>

        <div class="text-center mt-4">
            <small class="text-muted" style="font-size: 0.75rem;">&copy; 2024 Sistem Terdistribusi Retail</small>
        </div>
    </div>

</body>
</html>