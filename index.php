<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Shukla Smile Hospital</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    @keyframes gradientSlide {
      0% {
        background-position: 0% 50%;
      }
      100% {
        background-position: 100% 50%;
      }
    }
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
    .glass-card.card-portal {
      background-size: 200% 200%;
      transition: background-position 0.5s ease-in-out;
    }
    .glass-card.card-portal:hover {
      background-position: 100% 50%;
      animation: gradientSlide 3s ease infinite;
    }
    .btn-glass {
      border: none;
      border-radius: 10px;
      padding: 0.75rem 1.5rem;
      font-size: 1rem;
      margin-top: 1rem;
      width: 100%;
      background: linear-gradient(to right,rgb(135, 102, 232), #9575cd); /* Soft purple tones */
      color: white;
      transition: 0.3s ease-in-out;
    }
    .btn-glass:hover {
      opacity: 0.9;
      transform: scale(1.02);
      background-position: 100% 50%;
      animation: gradientSlide 2s ease infinite;
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
    .bg-purple {
      background: linear-gradient(to right, #8e44ad,rgb(254, 154, 226));
    }
    .bg-pink {
      background: linear-gradient(to right, #f06292,rgb(254, 188, 229));
    }
  </style>
</head>
<body>
  <div class="text-center mb-5">
    <h1 class="fw-bold">Shukla Smile Hospital</h1>
    <p class="lead">Hospital Management System</p>
  </div>

  <div class="d-flex flex-wrap justify-content-center">
    <div class="glass-card card-portal bg-purple bg-opacity-25">
      <img src="assets/media/mother.png" alt="Children Icon">
      <h4>Children Hospital</h4>
      <p class="small">Specialized pediatric care with treatments for children.</p>
      <a href="login.php?role=children" class="btn btn-glass text-white">➜ Enter Portal</a>
    </div>

    <div class="glass-card card-portal bg-pink bg-opacity-25">
      <img src="assets/media/gynae.png" alt="Gynae Icon">
      <h4>Gynaecology</h4>
      <p class="small">Specialized care for reproductive and maternity health.</p>
      <a href="login.php?role=gynaecologist" class="btn btn-glass text-white">➜ Enter Portal</a>
    </div>
  </div>

  <div class="footer">
    &copy; 2025 Shukla Smile Hospital. All rights reserved.
  </div>
</body>
</html>
