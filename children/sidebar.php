<!-- includes/sidebar.php -->
<style>
  .sidebar {
    width: 250px;
    background: #f7f9fc;
    height: 100vh;
    border-right: 1px solid #e0e6ed;
    padding: 2rem 1rem;
    position: fixed;
    top: 0;
    left: 0;
  }

  .sidebar a {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    border-radius: 12px;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: #4a4a4a;
    text-decoration: none;
    transition: all 0.3s ease;
  }

  .sidebar a:hover, .sidebar a.active {
    background-color: #d6e8fd;
    color: #1a73e8;
  }

  .sidebar h5 {
    font-weight: 700;
    color: #1a73e8;
    margin-bottom: 2rem;
  }

  .sidebar i {
    margin-right: 0.75rem;
  }
</style>

<div class="sidebar">
  <h5>Children HMS</h5>
  <a href="/children/dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>"><i class="bi bi-house-door"></i>Dashboard</a>
  <a href="/children/opd.php" class="<?= basename($_SERVER['PHP_SELF']) == 'opd.php' ? 'active' : '' ?>"><i class="bi bi-journal-text"></i>OPD</a>
  <a href="/children/ipd.php" class="<?= basename($_SERVER['PHP_SELF']) == 'ipd.php' ? 'active' : '' ?>"><i class="bi bi-hospital"></i>IPD</a>
  <a href="/children/newborn.php" class="<?= basename($_SERVER['PHP_SELF']) == 'newborn.php' ? 'active' : '' ?>"><i class="bi bi-baby"></i>Newborn Registration</a>
  <a href="/children/discharge.php" class="<?= basename($_SERVER['PHP_SELF']) == 'discharge.php' ? 'active' : '' ?>"><i class="bi bi-file-earmark-text"></i>Discharge Summary</a>
  <a href="/children/invoice.php" class="<?= basename($_SERVER['PHP_SELF']) == 'invoice.php' ? 'active' : '' ?>"><i class="bi bi-credit-card"></i>Invoice</a>
  <a href="/children/users.php" class="<?= basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : '' ?>"><i class="bi bi-person-gear"></i>Admin</a>
  <a href="/logout.php" class="text-danger"><i class="bi bi-box-arrow-left"></i>Logout</a>
</div>
