<?php
// C:\xampp\htdocs\projectweb\sheronair\auth\signin.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Sign In | Sheron Airways</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --bg: #f8fafc;
      --card: #ffffff;
      --border: #e2e8f0;
      --brand: #2563eb;
      --brand-hover: #1d4ed8;
      --text: #1e293b;
      --text-light: #64748b;
      --error: #ef4444;
      --success: #10b981;
    }
    
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    
    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background-image: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
    }
    
    .container {
      width: 100%;
      max-width: 460px;
      padding: 2rem;
    }
    
    .card {
      background: var(--card);
      border-radius: 16px;
      padding: 2.5rem;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.5);
      backdrop-filter: blur(10px);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
    }
    
    .logo {
      text-align: center;
      margin-bottom: 2rem;
    }
    
    .logo i {
      font-size: 2.5rem;
      color: var(--brand);
      margin-bottom: 1rem;
    }
    
    .logo h1 {
      font-size: 1.5rem;
      font-weight: 600;
      color: var(--text);
      margin-bottom: 0.5rem;
    }
    
    .logo p {
      color: var(--text-light);
      font-size: 0.875rem;
    }
    
    h2 {
      font-size: 1.5rem;
      font-weight: 600;
      margin-bottom: 1.5rem;
      text-align: center;
    }
    
    .flash {
      padding: 0.75rem 1rem;
      border-radius: 8px;
      font-size: 0.875rem;
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    
    .error {
      background: rgba(239, 68, 68, 0.1);
      color: var(--error);
      border-left: 4px solid var(--error);
    }
    
    .success {
      background: rgba(16, 185, 129, 0.1);
      color: var(--success);
      border-left: 4px solid var(--success);
    }
    
    .form-group {
      margin-bottom: 1.25rem;
      position: relative;
    }
    
    label {
      display: block;
      font-size: 0.875rem;
      font-weight: 500;
      margin-bottom: 0.5rem;
      color: var(--text);
    }
    
    .input-wrapper {
      position: relative;
    }
    
    input {
      width: 100%;
      padding: 0.875rem 1rem 0.875rem 2.5rem;
      border: 1px solid var(--border);
      border-radius: 8px;
      font-size: 0.875rem;
      transition: all 0.2s ease;
      background-color: #f8fafc;
    }
    
    input:focus {
      outline: none;
      border-color: var(--brand);
      box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }
    
    .input-icon {
      position: absolute;
      left: 1rem;
      top: 50%;
      transform: translateY(-50%);
      color: var(--text-light);
      font-size: 1rem;
    }
    
    .options {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin: 1rem 0 1.5rem;
      font-size: 0.875rem;
    }
    
    .checkbox {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    
    .checkbox input {
      width: auto;
    }
    
    .forgot-password {
      color: var(--brand);
      text-decoration: none;
      font-weight: 500;
    }
    
    .forgot-password:hover {
      text-decoration: underline;
    }
    
    .btn {
      width: 100%;
      padding: 0.875rem;
      border: none;
      border-radius: 8px;
      background: var(--brand);
      color: white;
      font-weight: 500;
      font-size: 0.875rem;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    
    .btn:hover {
      background: var(--brand-hover);
    }
    
    .btn i {
      margin-right: 0.5rem;
    }
    
    .divider {
      display: flex;
      align-items: center;
      margin: 1.5rem 0;
      color: var(--text-light);
      font-size: 0.75rem;
    }
    
    .divider::before,
    .divider::after {
      content: "";
      flex: 1;
      border-bottom: 1px solid var(--border);
    }
    
    .divider::before {
      margin-right: 1rem;
    }
    
    .divider::after {
      margin-left: 1rem;
    }
    
    .footer {
      text-align: center;
      margin-top: 1.5rem;
      font-size: 0.875rem;
      color: var(--text-light);
    }
    
    .footer a {
      color: var(--brand);
      text-decoration: none;
      font-weight: 500;
    }
    
    .footer a:hover {
      text-decoration: underline;
    }
    
    @media (max-width: 480px) {
      .container {
        padding: 1rem;
      }
      
      .card {
        padding: 1.5rem;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="card">
      <div class="logo">
        <i class="fas fa-plane"></i>
        <h1>Sheron Airways</h1>
        <p>Sign in to your account</p>
      </div>

      <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="flash error">
          <i class="fas fa-exclamation-circle"></i>
          <?php echo htmlspecialchars($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($_SESSION['flash_ok'])): ?>
        <div class="flash success">
          <i class="fas fa-check-circle"></i>
          <?php echo htmlspecialchars($_SESSION['flash_ok']); unset($_SESSION['flash_ok']); ?>
        </div>
      <?php endif; ?>

      <form method="post" action="<?php echo BASE_URL; ?>/auth/handle_signin.php" autocomplete="on">
        <div class="form-group">
          <label for="email">Email Address</label>
          <div class="input-wrapper">
            <i class="fas fa-envelope input-icon"></i>
            <input id="email" name="email" type="email" placeholder="you@example.com" required />
          </div>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <div class="input-wrapper">
            <i class="fas fa-lock input-icon"></i>
            <input id="password" name="password" type="password" placeholder="••••••••" required />
          </div>
        </div>

        <div class="options">
          <div class="checkbox">
            <input type="checkbox" id="remember" name="remember" value="1">
            <label for="remember">Remember me</label>
          </div>
          <a href="<?php echo BASE_URL; ?>/auth/forgot-password.php" class="forgot-password">Forgot password?</a>
        </div>

        <button class="btn" type="submit">
          <i class="fas fa-sign-in-alt"></i> Sign In
        </button>
      </form>

      <div class="divider">OR</div>

      <div class="footer">
        Don't have an account? <a href="<?php echo BASE_URL; ?>/auth/signup.php">Sign up</a>
      </div>
    </div>
  </div>
</body>
</html>