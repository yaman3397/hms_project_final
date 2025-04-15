<?php
$currentPage = basename($_SERVER['PHP_SELF']);
function isActive($page) {
  global $currentPage;
  return $currentPage === $page ? 'active' : '';
}

$role = $_SESSION['role'] ?? '';
$isChildren = $role === 'children';
$isGynae = $role === 'gynaecologist';
$themeColor = $isChildren ? '#3f37c9' : '#d63384';
$dashboardLabel = $isChildren ? 'Children' : 'Gynae';
$logo = $isChildren ? 'mother.png' : 'gynae.png';
$base = $isChildren ? 'children' : 'gynaecology';
?>

<style>
  .sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 240px;
    height: 100vh;
    background: #f6f8ff;
    border-radius: 24px;
    padding: 2rem 1rem;
    display: flex;
    flex-direction: column;
    box-shadow: 0 4px 30px rgba(0, 0, 0, 0.05);
    z-index: 1000;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none; /* Hide scrollbar in Firefox */
  }

  .sidebar::-webkit-scrollbar {
    display: none;
  }

  .brand {
    font-size: 1.4rem;
    font-weight: 700;
    color: #3f37c9;
    text-align: center;
    margin-bottom: 2.5rem;
  }

  .sidebar .nav-link {
    display: flex;
    align-items: center;
    padding: 0.8rem 1rem;
    margin-bottom: 0.5rem;
    border-radius: 14px;
    font-size: 0.95rem;
    font-weight: 500;
    color: #333;
    text-decoration: none;
    transition: background-color 0.3s ease, color 0.3s ease, transform 0.3s ease;
  }

  .sidebar .nav-link i {
    font-size: 1.1rem;
    margin-right: 0.75rem;
  }

  .sidebar .nav-link.active {
    background-color: <?= $isGynae ? '#f8c7dd' : '#cdb4f9' ?>;
    color: <?= $isGynae ? '#b3005e' : '#3a0ca3' ?>;
    transform: scale(1.02);
  }

  .sidebar .nav-link:hover {
    background-color: <?= $isGynae ? '#fce0ec' : '#e0d4fd' ?>;
    color: <?= $isGynae ? '#b3005e' : '#3a0ca3' ?>;
    transform: translateX(5px);
  }

  .sidebar .brand img {
    transition: transform 0.5s ease;
  }

  .sidebar .brand img:hover {
    transform: rotate(-5deg) scale(1.1);
  }

  .sidebar .bottom-links {
    margin-top: auto;
    padding-top: 1rem;
    border-top: 1px solid #e0e0e0;
  }

  @media (max-width: 1024px) {
    .sidebar {
      position: relative;
      width: 100%;
      height: auto;
      flex-direction: row;
      flex-wrap: wrap;
      justify-content: space-around;
      border-radius: 0;
      padding: 1rem;
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
      scrollbar-width: none; /* Firefox */
    }
    .brand {
      width: 100%;
      text-align: center;
      margin-bottom: 1rem;
    }
    .sidebar .nav-link {
      flex: 1 1 40%;
      justify-content: center;
      text-align: center;
      margin-bottom: 0.5rem;
    }
  }

  @media (max-width: 576px) {
    .sidebar .nav-link {
      flex: 1 1 100%;
    }
  }

  body {
    overflow-x: hidden;
    overflow-y: auto;
  }

  @media (max-width: 1024px) {
    html, body {
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }

    .main {
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }
  }

  @media (max-width: 1024px) {
    .sidebar {
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
      scrollbar-width: none; /* Firefox */
    }

    .sidebar::-webkit-scrollbar {
      display: none; /* Chrome, Safari */
    }
  }
</style>

<div class="sidebar">
  <div class="brand text-center">
    <img src="../assets/media/<?= $logo ?>" alt="Logo" width="70" height="70" style="margin-bottom: 10px;">
    <div style="font-weight: 700; font-size: 1.3rem; color: <?= $themeColor ?>; line-height: 1.2;">
      <?= $dashboardLabel ?><br>
      Dashboard
    </div>
  </div>

  <a href="../<?= $base ?>/dashboard.php" class="nav-link <?= isActive('dashboard.php') ?>"><i class="bi bi-grid-1x2-fill"></i> Dashboard</a>
  <a href="../<?= $base ?>/opd.php" class="nav-link <?= isActive('opd.php') ?>"><i class="bi bi-person-plus-fill"></i> OPD Registration</a>
  <a href="../<?= $base ?>/ipd.php" class="nav-link <?= isActive('ipd.php') ?>"><i class="bi bi-hospital-fill"></i> IPD Admission</a>
  <?php if ($isChildren): ?>
    <a href="../children/newborn.php" class="nav-link <?= isActive('newborn.php') ?>"><i class="bi bi-person-bounding-box"></i> Newborn Registration</a>
  <?php endif; ?>
  <a href="../<?= $base ?>/discharge.php" class="nav-link <?= isActive('discharge.php') ?>"><i class="bi bi-box-arrow-in-right"></i> Discharge Summary</a>
  <a href="../<?= $base ?>/invoice.php" class="nav-link <?= isActive('invoice.php') ?>"><i class="bi bi-receipt-cutoff"></i> Invoice Generation</a>
  <a href="../<?= $base ?>/print_invoice_list.php" class="nav-link <?= isActive('print_invoice.php') ?>"><i class="bi bi-printer-fill"></i> Print Invoice</a>
  <a href="../<?= $base ?>/print_discharge_summary.php" class="nav-link <?= isActive('print_discharge.php') ?>"><i class="bi bi-printer-fill"></i> Print Discharge Summary</a>
  <a href="../<?= $base ?>/opd_list.php" class="nav-link <?= isActive('opd_list.php') ?>"><i class="bi bi-list-ul"></i> OPD List</a>
  <a href="../<?= $base ?>/ipd_list.php" class="nav-link <?= isActive('ipd_list.php') ?>"><i class="bi bi-list-check"></i> IPD List</a>
  <?php if ($isChildren): ?>
    <a href="../children/newborn_list.php" class="nav-link <?= isActive('newborn_list.php') ?>"><i class="bi bi-person-badge"></i> Newborn List</a>
  <?php endif; ?>
  <div class="bottom-links">
    <a href="../logout.php" class="nav-link"><i class="bi bi-box-arrow-left"></i> Logout</a>
  </div>
</div>

<!-- Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">