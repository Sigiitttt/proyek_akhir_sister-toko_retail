<?php
session_start();
require_once '../config/database.php'; // Koneksi DB Lokal

// Cek jika sudah login
if (isset($_SESSION['kasir_logged_in']) && $_SESSION['kasir_logged_in'] === true) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Query ke DB Lokal (Tabel users)
    $stmt = $db_lokal->prepare("SELECT * FROM users WHERE username = :u LIMIT 1");
    $stmt->execute([':u' => $username]);
    $user = $stmt->fetch();

    if ($user) {
        // Cek Password
        // CATATAN: Untuk demo/tugas sesuai dummy data SQL sebelumnya (plain text '123456')
        // Jika nanti production, gunakan password_verify()
        if ($password === $user['password']) {
            
            $_SESSION['kasir_logged_in'] = true;
            $_SESSION['kasir_id'] = $user['id_user'];
            $_SESSION['kasir_nama'] = $user['nama_lengkap'];
            
            header("Location: dashboard.php");
            exit;
        }
    }
    
    $error = "Username atau Password salah.";
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login POS - Toko Retail</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f5;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            width: 100%;
            max-width: 380px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .card-header-custom {
            background-color: #198754; /* Hijau Emerald */
            color: white;
            padding: 25px 20px;
            text-align: center;
        }
        .btn-success-custom {
            background-color: #198754;
            border: none;
            padding: 12px;
            font-weight: 600;
        }
        .btn-success-custom:hover {
            background-color: #157347;
        }
        .form-control {
            padding: 12px;
            border-radius: 8px;
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="card-header-custom">
            <h4 class="mb-1"><i class="fa-solid fa-cash-register me-2"></i>POS SYSTEM</h4>
            <small class="opacity-75">Login Kasir Cabang</small>
        </div>
        <div class="p-4">
            <?php if($error): ?>
                <div class="alert alert-danger py-2 small border-0 bg-danger bg-opacity-10 text-danger text-center">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Username</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-user text-secondary"></i></span>
                        <input type="text" name="username" class="form-control border-start-0" placeholder="kasir1" required autofocus>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label small fw-bold text-muted">Password</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-lock text-secondary"></i></span>
                        <input type="password" name="password" class="form-control border-start-0" placeholder="••••••" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 btn-success-custom rounded-pill">
                    BUKA TOKO
                </button>
            </form>
        </div>
        <div class="bg-light p-3 text-center border-top">
            <small class="text-muted" style="font-size: 0.7rem;">Sistem Terdistribusi Retail v1.0</small>
        </div>
    </div>

</body>
</html>