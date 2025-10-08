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
  <!-- Header with back button -->
  <header class="pricing-header">
    <a href="dashboardAll.php" style="text-decoration:none;display:inline-block;background:#00aaff;color:#fff;padding:6px 12px;border-radius:6px;font-weight:bold;margin-bottom:10px;">
      ← Back to Dashboard
    </a>
    <h1>Plans & Pricing</h1>
    <p>Find a plan that suits your needs</p>
  </header>

  <!-- Plans Section -->
  <section class="pricing-section">
    <!-- Free Plan -->
    <div class="plan-card <?php echo $currentUser['plan'] === 'free' ? 'current-plan' : ''; ?>">
      <h2>Free Plan</h2>
      <p class="price"><span>Rp</span>0<span>/month</span></p>
      <p class="annual">Free forever</p>
      <?php if ($currentUser['plan'] === 'free'): ?>
        <div class="current-badge">Current Plan</div>
      <?php else: ?>
        <a href="#" class="btn-checkout disabled">Current Plan</a>
      <?php endif; ?>
      <ul class="features">
        <li><strong><?php echo $planLimits['free']['qr_codes_per_month']; ?></strong> QR Codes per month</li>
        <li><strong>Unlimited</strong> Scans</li>
        <li>30 days analytics trial</li>
        <li>Ads displayed</li>
        <li>Custom logo, URL, color</li>
      </ul>
    </div>

    <!-- Starter Plan -->
    <div class="plan-card highlight <?php echo $currentUser['plan'] === 'starter' ? 'current-plan' : ''; ?>" style="opacity: 0.7;">
      <div class="badge">Most Popular</div>
      <h2>Starter Plan</h2>
      <p class="price"><span>Rp</span><?php echo number_format($planLimits['starter']['price'], 0, ',', '.'); ?><span>/month</span></p>
      <p class="annual">Monthly payment</p>
      <?php if ($currentUser['plan'] === 'starter'): ?>
        <div class="current-badge">Current Plan</div>
      <?php else: ?>
        <button class="btn-checkout" disabled style="opacity:0.6;cursor:not-allowed;background:#ccc;">Coming Soon</button>
      <?php endif; ?>
      <ul class="features">
        <li><strong><?php echo $planLimits['starter']['qr_codes_per_month']; ?></strong> QR Codes per month</li>
        <li><strong>Unlimited</strong> Scans</li>
        <li>Full Analytics features</li>
        <li>No ads</li>
        <li>Custom logo, URL, color</li>
        <li>Email Support</li>
      </ul>
    </div>

    <!-- Pro Plan -->
    <div class="plan-card <?php echo $currentUser['plan'] === 'pro' ? 'current-plan' : ''; ?>" style="opacity: 0.7;">
      <h2>Pro Plan</h2>
      <p class="price"><span>Rp</span><?php echo number_format($planLimits['pro']['price'], 0, ',', '.'); ?><span>/month</span></p>
      <p class="annual">Monthly payment</p>
      <?php if ($currentUser['plan'] === 'pro'): ?>
        <div class="current-badge">Current Plan</div>
      <?php else: ?>
        <button class="btn-checkout" disabled style="opacity:0.6;cursor:not-allowed;background:#ccc;">Coming Soon</button>
      <?php endif; ?>
      <ul class="features">
        <li><strong><?php echo $planLimits['pro']['qr_codes_per_month']; ?></strong> QR Codes per month</li>
        <li><strong>Unlimited</strong> Scans</li>
        <li>Full Analytics features</li>
        <li>No ads</li>
        <li>Custom logo, URL, color</li>
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
  <section style="max-width:800px;margin:40px auto;background:#fff;border:1px solid #ddd;border-radius:12px;padding:30px;text-align:center;">
    <h2 style="margin-bottom:20px;color:#1d2d50;">Terms & Conditions</h2>
    <p style="margin-bottom:20px;font-size:16px;color:#666;">By subscribing to any plan, you agree to our terms and conditions.</p>
    <a href="terms.php" target="_blank" style="display:inline-block;background:#00aaff;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:bold;font-size:16px;transition:background 0.3s ease;">
      📄 Read Full Terms & Conditions
    </a>
    </section>
</body>
</html>
