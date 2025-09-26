<?php
  // cari nama file yang sedang dibuka
  $currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar" id="sidebar">
  <div class="menu-top">
    <a href="dashboardAll.php"
       class="menu-link <?= $currentPage=='dashboardAll.php' ? 'active' : '' ?>">All QR Codes</a>
    <a href="dashboardActive.php"
       class="menu-link <?= $currentPage=='dashboardActive.php' ? 'active' : '' ?>">Active</a>
    <a href="dashboardPause.php"
       class="menu-link <?= $currentPage=='dashboardPause.php' ? 'active' : '' ?>">Paused</a>
  </div>
  <div class="menu-bottom">
    <p class="trial-text">Start free trial for 7 days</p>
    <a href="payment.php" class="btn-upgrade">Upgrade</a>
  </div>
</aside>
