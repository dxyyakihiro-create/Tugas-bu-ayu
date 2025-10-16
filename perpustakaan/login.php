<?php
session_start();
require_once 'koneksi.php';
require_once 'config.php';

 $error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Username dan password harus diisi!";
    } else {
        $stmt = $conn->prepare("SELECT id, username, password, level FROM registrasi WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $hashed_password_from_db = $user['password'];

            echo "<div style='background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 10px; border-radius: 4px;'>";
            echo "<strong>INFO DEBUG:</strong><br>";
            echo "Username yang diketik: <strong>" . htmlspecialchars($username) . "</strong><br>";
            echo "Password yang diketik: <strong>" . htmlspecialchars($password) . "</strong><br>";
            echo "Password Hash dari Database: <strong>" . htmlspecialchars($hashed_password_from_db) . "</strong><br>";
            echo "</div>";

            if (password_verify($password, $hashed_password_from_db)) {
                $_SESSION['login'] = true;
                $_SESSION['username'] = $user['username'];
                $_SESSION['level'] = $user['level'];
                $_SESSION['user_id'] = $user['id'];

                header("Location: index.php");
                exit();
            } else {
                $error = "Password salah! (Lihat info debug di atas)";
            }
        } else {
            $error = "Username tidak ditemukan!";
        }
        $stmt->close();
    }
}
 $conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f5f5f5; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .container { background-color: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); width: 100%; max-width: 400px; }
        h2 { text-align: center; color: #333; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; color: #555; }
        input[type="text"], input[type="password"] { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .btn { width: 100%; padding: 12px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        .btn:hover { background-color: #0056b3; }
        .alert { padding: 10px; margin-bottom: 15px; border-radius: 4px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; text-align: center; }
        .register-link { text-align: center; margin-top: 20px; }
        .register-link a { color: #007bff; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Login</h2>

        <?php if (!empty($error)): ?>
            <div class="alert"><?= $error ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['status']) && $_GET['status'] == 'registered'): ?>
            <div style="text-align:center; color:green; margin-bottom:15px;">Registrasi berhasil! Silakan login.</div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn">Login</button>
        </form>
        <div class="register-link">
            Belum punya akun? <a href="register.php">Daftar di sini</a>
        </div>
    </div>
</body>
</html>