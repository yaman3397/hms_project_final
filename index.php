<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Shukla Smile Hospital</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      background: radial-gradient(circle at top, #2b2b4f, #1e1e30);
      color: white;
      font-family: 'Segoe UI', sans-serif;
      height: 100vh;
      margin: 0;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
    }
    .glass-card {
      background: rgba(255, 255, 255, 0.05);
      backdrop-filter: blur(14px);
      border-radius: 20px;
      padding: 2rem 3rem;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
      color: white;
      text-align: center;
    }
    .btn-glass {
      border: none;
      border-radius: 10px;
      padding: 0.75rem 1.5rem;
      font-size: 1rem;
      margin-top: 1rem;
      width: 100%;
    }
    .card-portal {
      max-width: 300px;
      margin: 1rem;
    }
    .card-portal img {
      max-height: 60px;
      margin-bottom: 1rem;
    }
    .footer {
      position: absolute;
      bottom: 1rem;
      text-align: center;
      font-size: 0.875rem;
      color: #bbb;
    }
  </style>
</head>
<body>
  <div class="text-center mb-5">
    <h1 class="fw-bold">Shukla Smile Hospital</h1>
    <p class="lead">Hospital Management System</p>
  </div>

  <div class="d-flex flex-wrap justify-content-center">
    <div class="glass-card card-portal bg-primary bg-opacity-25">
      <img src="https://cdn-icons-png.flaticon.com/512/4240/4240704.png" alt="Children Icon">
      <h4>Children Hospital</h4>
      <p class="small">Comprehensive pediatric care with treatments for infants and children.</p>
      <a href="login.php?role=children" class="btn btn-primary btn-glass">➜ Enter Portal</a>
    </div>

    <div class="glass-card card-portal bg-danger bg-opacity-25">
      <img src="https://cdn-icons-png.flaticon.com/512/4320/4320337.png" alt="Gynae Icon">
      <h4>Gynaecology</h4>
      <p class="small">Specialized care for reproductive and maternity health.</p>
      <a href="login.php?role=gynaecologist" class="btn btn-danger btn-glass">➜ Enter Portal</a>
    </div>
  </div>

  <div class="footer">
    &copy; 2025 Shukla Smile Hospital. All rights reserved.
  </div>
</body>
</html>
