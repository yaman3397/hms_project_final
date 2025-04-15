<?php
session_start();
$role = $_GET['role'] ?? 'children';
$title = $role === 'gynaecologist' ? 'Gynaecology Department' : 'Children Hospital';
$themeColor = $role === 'gynaecologist' ? '#f06292' : '#8e44ad'; // pink for gynaecology

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include('config/db.php');
    $username = $_POST['username'];
    $password = hash('sha256', $_POST['password']);
    $role = $_POST['role'];

    $stmt = $conn->prepare("SELECT id, username FROM users WHERE username=? AND password=? AND role=?");
    $stmt->bind_param("sss", $username, $password, $role);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $role;

        header("Location: " . ($role === 'gynaecologist' ? 'gynaecology/dashboard.php' : 'children/dashboard.php'));
        exit();
    } else {
        $error = "Invalid credentials or role mismatch!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= $title ?> Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(to bottom right, <?= $themeColor ?>55, <?= $themeColor ?>);
      height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Segoe UI', sans-serif;
    }

    .login-box {
      background: rgba(255, 255, 255, 0.12);
      border-radius: 20px;
      padding: 2rem;
      width: 100%;
      max-width: 360px;
      backdrop-filter: blur(14px);
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
      color: white;
    }

    .login-box input {
      background: rgba(255, 255, 255, 0.2);
      border: none;
      color: white;
    }

    .login-box input::placeholder {
      color: #eee;
    }

    .login-box .btn {
      background-color: <?= $themeColor ?>;
      border: none;
    }

    .login-box .btn:hover {
      background-color: <?= $themeColor ?>dd;
    }

    .login-box .icon {
      font-size: 2rem;
    }

    .login-box a {
      color: white;
      font-size: 0.9rem;
      text-decoration: underline;
    }

    .form-control:focus {
      box-shadow: 0 0 0 0.2rem rgba(255,255,255,0.2);
    }
  </style>
</head>
<body>
  <div class="login-box text-center">
    <img src="assets/media/<?= $role === 'gynaecologist' ? 'gynae.png' : 'mother.png' ?>" alt="Logo" width="70" class="mb-3">
    <h4><?= $title ?></h4>
    <p class="small mb-4">Please login to continue</p>

    <?php if (!empty($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

    <form method="POST">
      <input type="hidden" name="role" value="<?= htmlspecialchars($role) ?>">
      <div class="mb-3">
        <input type="text" class="form-control" name="username" placeholder="Username" required>
      </div>
      <div class="mb-3">
        <input type="password" class="form-control" name="password" placeholder="Password" required>
      </div>
      <button type="submit" class="btn btn-lg w-100">🔐 Login</button>
    </form>

    <div class="mt-3">
      <?php if ($role === 'children'): ?>
        <a href="login.php?role=gynaecologist">Switch to Gynaecology</a>
      <?php else: ?>
        <a href="login.php?role=children">Switch to Children Hospital</a>
      <?php endif; ?>
    </div>
    <div class="mt-2">
      <a href="index.php">← Back to Home</a>
    </div>
  </div>
</body>
</html>
