<?php
// Friendly 500. Deliberately shows nothing about the underlying error —
// real details go to error_log(), never to the response body.
if (!headers_sent()) {
    http_response_code(500);
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Something Went Wrong — XPORN LOVERS</title>
<link rel="icon" type="image/svg+xml" href="/assets/img/favicon.svg">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,700;0,900;1,700;1,900&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/tokens.css">
<link rel="stylesheet" href="/assets/css/main.css">
<style>
  .error-screen {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 24px;
    background:
      radial-gradient(50% 50% at 50% 0%, rgba(255, 45, 117, 0.14) 0%, transparent 60%),
      var(--bg-primary);
  }
  .error-code {
    font-family: var(--font-display);
    font-weight: 900;
    font-style: italic;
    font-size: clamp(4rem, 14vw, 8rem);
    line-height: 1;
    background: var(--gradient-brand);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
  }
  .error-screen h1 { margin-top: 8px; font-family: var(--font-display); font-size: 1.4rem; }
  .error-screen p { color: var(--text-secondary); margin-top: 10px; max-width: 420px; }
  .error-screen .btn { margin-top: 28px; }
</style>
</head>
<body>
<div class="error-screen">
  <div>
    <div class="logo logo--lg" style="justify-content:center; margin-bottom:24px;">
      <span class="logo-mark">X</span><span class="logo-text">PORN <span class="accent">LOVERS</span></span>
    </div>
    <div class="error-code">500</div>
    <h1>Something went wrong on our end</h1>
    <p>We're already looking into it. Please try again in a moment.</p>
    <a href="/index.php" class="btn btn-primary">Back to Home</a>
  </div>
</div>
</body>
</html>
