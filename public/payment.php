<?php
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../config/Config.php';

// Require authentication
Auth::requireAuth();
$currentUser = Auth::getCurrentUser();

// Get plan limits
$planLimits = Config::getPlanLimits();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Plans & Pricing</title>
  <link rel="stylesheet" href="css/payment.css">
</head>
<body>
  <!-- Header dengan tombol back -->
  <header class="pricing-header">
    <a href="dashboardAll.php" style="text-decoration:none;display:inline-block;background:#00aaff;color:#fff;padding:6px 12px;border-radius:6px;font-weight:bold;margin-bottom:10px;">
      ← Back to Dashboard
    </a>
    <h1>Plans & Pricing</h1>
    <p>Find a plan that suits your needs</p>
  </header>

  <!-- Section Plan -->
  <section class="pricing-section">
    <!-- Free Plan -->
    <div class="plan-card <?php echo $currentUser['plan'] === 'free' ? 'current-plan' : ''; ?>">
      <h2>Free Plan</h2>
      <p class="price"><span>Rp</span>0<span>/bulan</span></p>
      <p class="annual">Gratis selamanya</p>
      <?php if ($currentUser['plan'] === 'free'): ?>
        <div class="current-badge">Current Plan</div>
      <?php else: ?>
        <a href="#" class="btn-checkout disabled">Current Plan</a>
      <?php endif; ?>
      <ul class="features">
        <li><strong><?php echo $planLimits['free']['qr_codes_per_month']; ?></strong> QR Codes per bulan</li>
        <li><strong>Unlimited</strong> Scans</li>
        <li>7 hari trial analytics</li>
        <li>Iklan ditampilkan</li>
        <li>Custom logo, URL, warna</li>
      </ul>
    </div>

    <!-- Starter Plan -->
    <div class="plan-card highlight <?php echo $currentUser['plan'] === 'starter' ? 'current-plan' : ''; ?>">
      <div class="badge">Most Popular</div>
      <h2>Starter Plan</h2>
      <p class="price"><span>Rp</span><?php echo number_format($planLimits['starter']['price'], 0, ',', '.'); ?><span>/bulan</span></p>
      <p class="annual">Pembayaran bulanan</p>
      <?php if ($currentUser['plan'] === 'starter'): ?>
        <div class="current-badge">Current Plan</div>
      <?php else: ?>
        <a href="checkout.php?plan=starter" class="btn-checkout">Upgrade Now</a>
      <?php endif; ?>
      <ul class="features">
        <li><strong><?php echo $planLimits['starter']['qr_codes_per_month']; ?></strong> QR Codes per bulan</li>
        <li><strong>Unlimited</strong> Scans</li>
        <li>Fitur Analytics lengkap</li>
        <li>Tanpa iklan</li>
        <li>Custom logo, URL, warna</li>
        <li>Email Support</li>
      </ul>
    </div>

    <!-- Pro Plan -->
    <div class="plan-card <?php echo $currentUser['plan'] === 'pro' ? 'current-plan' : ''; ?>">
      <h2>Pro Plan</h2>
      <p class="price"><span>Rp</span><?php echo number_format($planLimits['pro']['price'], 0, ',', '.'); ?><span>/bulan</span></p>
      <p class="annual">Pembayaran bulanan</p>
      <?php if ($currentUser['plan'] === 'pro'): ?>
        <div class="current-badge">Current Plan</div>
      <?php else: ?>
        <a href="checkout.php?plan=pro" class="btn-checkout">Upgrade Now</a>
      <?php endif; ?>
      <ul class="features">
        <li><strong><?php echo $planLimits['pro']['qr_codes_per_month']; ?></strong> QR Codes per bulan</li>
        <li><strong>Unlimited</strong> Scans</li>
        <li>Fitur Analytics lengkap</li>
        <li>Tanpa iklan</li>
        <li>Custom logo, URL, warna</li>
        <li>Download PDF, CSV, Excel</li>
        <li>Priority Support</li>
      </ul>
    </div>
  </section>

  <!-- Current Usage Section -->
  <?php
  $quotaInfo = Auth::canCreateQRCode($currentUser['id']);
  ?>
  <section style="max-width:800px;margin:20px auto;background:#f8f9fa;border:1px solid #ddd;border-radius:12px;padding:20px;">
    <h3 style="margin-bottom:15px;color:#1d2d50;">Your Current Usage</h3>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;">
      <span>QR Codes this month:</span>
      <strong><?php echo $quotaInfo['used']; ?> / <?php echo $quotaInfo['limit']; ?></strong>
    </div>
    <div style="background:#e9ecef;border-radius:10px;height:10px;overflow:hidden;">
      <div style="background:<?php echo $quotaInfo['used'] >= $quotaInfo['limit'] ? '#dc3545' : '#28a745'; ?>;height:100%;width:<?php echo ($quotaInfo['used'] / $quotaInfo['limit']) * 100; ?>%;"></div>
    </div>
    <?php if ($quotaInfo['used'] >= $quotaInfo['limit']): ?>
      <p style="color:#dc3545;margin-top:10px;font-size:14px;">⚠️ You've reached your monthly limit. Upgrade to create more QR codes.</p>
    <?php endif; ?>
  </section>

  <!-- Terms & Conditions -->
  <section style="max-width:800px;margin:40px auto;background:#fff;border:1px solid #ddd;border-radius:12px;padding:30px;">
    <h2 style="margin-bottom:10px;color:#1d2d50;">Terms & Conditions</h2>
    <p style="margin-bottom:15px;">By subscribing to any plan, you agree to the following terms and conditions:</p>
    <ol style="margin-left:20px;">
      <li>Subscriptions are billed annually. Refunds are only provided for cancellations within the first 7 days.</li>
      <li>You are responsible for maintaining the security of your account credentials. We are not liable for any loss or damage resulting from unauthorized use of your account.</li>
      <li>Dynamic QR codes created under your plan will remain active as long as your subscription is active. Upon expiration, codes may be paused or disabled.</li>
      <li>We reserve the right to modify features, pricing, and terms of service at any time. Changes will be communicated via email prior to implementation.</li>
      <li>Plans are for individual use unless otherwise specified. Reselling or sharing accounts without written permission is prohibited.</li>
      <li>We are not responsible for losses or damages caused by third-party integrations or misuse of QR codes generated through our service.</li>
      <li>Failure to comply with these terms may result in suspension or termination of your account without refund.</li>
    </ol>
    <p style="margin-top:15px;">For full legal details, please review our <a href="#" style="color:#00aaff;">Full Terms & Conditions</a>.</p>
  </section>
</body>
</html>
