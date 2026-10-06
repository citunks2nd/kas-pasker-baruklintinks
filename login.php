<?php
session_start();
include 'koneksi.php';

if (isset($_SESSION['role'])) {
    header("Location: index.php");
    exit();
}

$error = '';

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = $_POST['password'];

    // Cari user berdasarkan username
    $query = mysqli_query($koneksi, "SELECT * FROM users WHERE username='$username'");
    $user  = mysqli_fetch_assoc($query);

    // Cek password (mencakup hash BCRYPT maupun teks biasa)
    if ($user && ($password === $user['password'] || password_verify($password, $user['password']))) {
        $_SESSION['username']     = $user['username'];
        $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
        $_SESSION['role']         = $user['role'];
        header("Location: index.php");
        exit();
    } else {
        $error = "Username atau password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - PASKER BARUKLINTINKS</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f6f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 100%; max-width: 350px; }
        h2 { text-align: center; margin-bottom: 20px; color: #2c3e50; }
        input { width: 100%; padding: 10px; margin-bottom: 12px; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #27ae60; color: white; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; }
        .error { color: #e74c3c; font-size: 0.85rem; margin-bottom: 10px; text-align: center; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Login Kas</h2>
        <?php if ($error): ?><div class="error"><?= $error; ?></div><?php endif; ?>
        <form action="" method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="login">Masuk</button>
        </form>
    </div>
</body>
</html>