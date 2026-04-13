<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Astra LMS | Sign In</title>
    
    <!-- Astra Premium Typography (Outfit) -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Astra Design System -->
    <link rel="stylesheet" href="style.css">
    
    <style>
        .login-experience {
            background-image: url('astra_login_bg_1775326948231.png');
            background-size: cover;
            background-position: center;
        }
    </style>
</head>

<body class="login-experience">

<div class="login-card-astra card-astra animate-up">
    <div class="text-center mb-5">
        <h2 class="fw-bold mb-2">ASTRA <span style="opacity: 0.5;">LMS</span></h2>
        <p class="text-muted small text-uppercase fw-bold" style="letter-spacing: 2px;">Sign in to your premium portal</p>
    </div>

    <form action="login.php" method="post">
        <div class="mb-4">
            <label class="form-label small fw-bold opacity-75">Email Address</label>
            <input type="email" name="email" class="form-control-astra w-100" placeholder="name@example.com" required>
        </div>

        <div class="mb-5">
            <label class="form-label small fw-bold opacity-75">Password</label>
            <input type="password" name="password" class="form-control-astra w-100" placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn-astra w-100 py-3 mb-4">
            Enter Dashboard 🚀
        </button>
    </form>

    <div class="text-center mt-4">
        <div style="font-size: 11px; opacity: 0.5; letter-spacing: 1px; font-weight: 600;">
            PROUDLY DEVELOPED BY <br>
            <span style="color: var(--astra-indigo);">KASUN DUSMANTHA</span>
        </div>
    </div>
</div>

</body>
</html>