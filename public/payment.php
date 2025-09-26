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
    <a href="dashboard.php" style="text-decoration:none;display:inline-block;background:#00aaff;color:#fff;padding:6px 12px;border-radius:6px;font-weight:bold;margin-bottom:10px;">
      ← Back to Dashboard
    </a>
    <h1>Plans & Pricing</h1>
    <p>Find a plan that suits your needs</p>
  </header>

  <!-- Section Plan -->
  <section class="pricing-section">
    <!-- Professional Plan -->
    <div class="plan-card">
      <h2>Professional</h2>
      <p class="price"><span>$</span>37.50<span>/month</span></p>
      <p class="annual">Annual charge of $450.00</p>
      <a href="checkout.php?plan=pro" class="btn-checkout">Checkout Now</a>
      <ul class="features">
        <li><strong>250</strong> Dynamic QR Codes</li>
        <li><strong>Unlimited</strong> Scans</li>
        <li>Analytics & Tracking</li>
        <li>Priority Support</li>
      </ul>
    </div>

    <!-- Advanced Plan -->
    <div class="plan-card">
      <h2>Advanced</h2>
      <p class="price"><span>$</span>12.50<span>/month</span></p>
      <p class="annual">Annual charge of $150.00</p>
      <a href="checkout.php?plan=adv" class="btn-checkout">Checkout Now</a>
      <ul class="features">
        <li><strong>50</strong> Dynamic QR Codes</li>
        <li><strong>Unlimited</strong> Scans</li>
        <li>Analytics Dashboard</li>
        <li>Email Support</li>
      </ul>
    </div>

    <!-- Starter Plan -->
    <div class="plan-card highlight">
      <div class="badge">Save 50% Compared to Monthly</div>
      <h2>Starter</h2>
      <p class="price"><span>$</span>5.00<span>/month</span></p>
      <p class="annual">Annual charge of $60.00</p>
      <a href="checkout.php?plan=start" class="btn-checkout">Checkout Now</a>
      <ul class="features">
        <li><strong>2</strong> Dynamic QR Codes</li>
        <li><strong>10,000</strong> Scans</li>
        <li>Basic Analytics</li>
        <li>Standard Support</li>
      </ul>
    </div>
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
