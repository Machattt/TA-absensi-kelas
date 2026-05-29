<?php
session_start(); // Mulai sesi buat ngecek user
require_once '../config/database.php'; // Panggil koneksi database

// Kalau udah login, langsung aja arahin ke halaman utama
if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$error = '';

// Proses form kalau ada request POST masuk
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Pastikan username dan password nggak kosong
    if (!empty($username) && !empty($password)) {
        // Cari user di database berdasarkan username
        $stmt = $pdo->prepare("SELECT id_user, password FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // Cek passwordnya bener apa nggak
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id_user']; // Simpan id user ke sesi
            header("Location: ../index.php");
            exit();
        } else {
            $error = "Username atau password salah!"; // Kasih tau kalau salah
        }
    } else {
        $error = "Silakan isi semua kolom!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ABSEN REK!</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?= time() ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .login-body {
            background-image: url('../assets/img/foto_kelas.jpg'); 
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            position: relative;
            background-color: #e2e8f0; 
        }
        
        .login-body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.4); 
            z-index: 1;
        }

        .login-container {
            z-index: 2;
            width: 100%;
            max-width: 420px;
            padding: 0 1.5rem;
        }

        .glassmorphism-box {
            background: transparent;
            border: none;
            box-shadow: none;
            backdrop-filter: none;
            -webkit-backdrop-filter: none;
            padding: 1rem 0;
        }

        .logo-container {
            text-align: center;
            margin-bottom: 0.5rem;
        }

        .logo-container img {
            width: 80px;
            height: 80px;
            object-fit: contain;
            border-radius: 50%;
            background: white; 
            padding: 5px;
            border: none;
            box-shadow: none;
        }

        .login-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .login-header h2 {
            font-size: 2rem;
            color: #ffffff;
            font-weight: 500;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .login-header p {
            color: rgba(255, 255, 255, 0.8);
            font-weight: 400;
            margin-top: 0.5rem;
            font-size: 1.1rem;
            letter-spacing: 1px;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: none; 
        }
        
        .form-group input {
            background: transparent !important;
            border: 2px solid rgba(255, 255, 255, 0.8) !important;
            border-radius: 50px !important;
            color: white !important;
            padding: 1rem 1.5rem !important;
            font-size: 1.05rem !important;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .form-group input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .form-group input:focus {
            background: rgba(255, 255, 255, 0.1) !important;
            border-color: #ffffff !important;
            box-shadow: none !important;
            outline: none;
        }

        /* Hilangkan icon mata bawaan browser agar kita bisa pakai icon buatan sendiri */
        input[type="password"]::-ms-reveal,
        input[type="password"]::-ms-clear {
            display: none; 
        }
        
        .btn-primary {
            background: #4f46e5 !important; /* Warna biru solid */
            border: none !important; /* Hapus border putih */
            border-radius: 50px !important;
            color: white !important;
            padding: 1rem 1.5rem !important;
            margin-top: 1rem;
            font-size: 1.1rem !important;
            font-weight: 600 !important;
            transition: all 0.3s ease !important;
            width: 100%;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1) !important;
        }

        .btn-primary:hover {
            background: #4338ca !important; 
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15) !important;
        }
    </style>
</head>
<body class="login-body">
    <div class="login-container">
        <div class="glassmorphism-box">
            <div class="logo-container">
                <!-- Logo RPL 2 -->
                <img src="../assets/img/LogoRpl2.png" alt="Logo RPL 2">
            </div>
            <div class="login-header">
                <h2>ABSEN REK!</h2>
                <p>Siswa Kelas 11 RPL 2</p>
            </div>
            
            <?php if($error): ?>
                <div class="alert alert-error" style="border-radius: 50px; text-align: center; border: 1px solid rgba(255,0,0,0.5);"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form action="" method="POST" class="login-form" autocomplete="off">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div style="position: relative;">
                        <!-- Input dengan Placeholder -->
                        <input type="text" id="username" name="username" value="" placeholder="Masukkan Username" required autocomplete="off" readonly style="padding-right: 50px;">
                        
                        <!-- Icon User di Kanan -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); opacity: 0.9; z-index: 10; pointer-events: none;"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    </div>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <div style="position: relative;">
                        <!-- Input Password -->
                        <input type="password" id="password" name="password" value="" placeholder="Masukkan Password" required autocomplete="new-password" readonly style="padding-right: 50px;">
                        
                        <!-- Icon Mata (Toggle) Buatan Sendiri -->
                        <button type="button" id="togglePassword" style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: white; display: flex; align-items: center; justify-content: center; padding: 0; z-index: 10; opacity: 0.9;">
                            <svg id="eye-icon" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Masuk</button>
            </form>
        </div>
    </div>

    <script>
        const usernameField = document.getElementById('username');
        const passwordField = document.getElementById('password');

        function clearFields() {
            usernameField.value = '';
            passwordField.value = '';
        }

        clearFields();

        setTimeout(clearFields, 100);
        setTimeout(clearFields, 500);
        setTimeout(clearFields, 1000);

        
        function handleInputActivation() {
            if (this.hasAttribute('readonly')) {
                this.removeAttribute('readonly');
                this.value = ''; 
            }
        }

        usernameField.addEventListener('focus', handleInputActivation);
        passwordField.addEventListener('focus', handleInputActivation);
        usernameField.addEventListener('click', handleInputActivation);
        passwordField.addEventListener('click', handleInputActivation);

        // Fitur Lihat Password Custom
        const togglePassword = document.getElementById('togglePassword');
        const eyeIcon = document.getElementById('eye-icon');
        
        // Path SVG untuk ikon mata terbuka (menyembunyikan password)
        const eyeOpenSVG = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
        
        // Path SVG untuk ikon mata dicoret (melihat password)
        const eyeClosedSVG = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';

        togglePassword.addEventListener('click', function () {
            // Toggle atribut type dari password ke text atau sebaliknya
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            
            // Ubah icon jadi mata dicoret kalau password lagi dilihat, dan sebaliknya
            if (type === 'password') {
                eyeIcon.innerHTML = eyeOpenSVG; 
            } else {
                eyeIcon.innerHTML = eyeClosedSVG; 
            }
            
            // Kembalikan fokus ke password agar ngetik tidak terganggu
            passwordField.focus();
        });

        usernameField.focus();
    </script>
</body>
</html>
