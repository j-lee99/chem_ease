<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChemEase - Chemistry Learning Platform</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
        <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="96x96" href="/favicon-96x96.png">
    <link rel="shortcut icon" href="/favicon.ico">

    <!-- Apple Touch Icons -->
    <link rel="apple-touch-icon" sizes="57x57" href="/apple-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="/apple-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="/apple-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="/apple-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="/apple-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="/apple-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="/apple-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/apple-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-icon-180x180.png">
    <link rel="apple-touch-icon" href="/apple-icon.png">
    <link rel="apple-touch-icon-precomposed" href="/apple-icon-precomposed.png">

    <!-- Android Icons -->
    <link rel="icon" type="image/png" sizes="36x36" href="/android-icon-36x36.png">
    <link rel="icon" type="image/png" sizes="48x48" href="/android-icon-48x48.png">
    <link rel="icon" type="image/png" sizes="72x72" href="/android-icon-72x72.png">
    <link rel="icon" type="image/png" sizes="96x96" href="/android-icon-96x96.png">
    <link rel="icon" type="image/png" sizes="144x144" href="/android-icon-144x144.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/android-icon-192x192.png">

    <!-- Microsoft Tiles -->
    <meta name="msapplication-TileColor" content="#0d6efd">
    <meta name="msapplication-TileImage" content="/ms-icon-144x144.png">
    <meta name="msapplication-square70x70logo" content="/ms-icon-70x70.png">
    <meta name="msapplication-square150x150logo" content="/ms-icon-150x150.png">
    <meta name="msapplication-square310x310logo" content="/ms-icon-310x310.png">
    <style>
        :root {
            --primary-blue: #17a2b8;
            --light-blue: #e8f4f8;
            --dark-text: #2c3e50;
        }

        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden; /* Prevents any scrolling */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, var(--light-blue) 0%, #e3f2fd 50%, #f0f9ff 100%);
            background-image:
                radial-gradient(circle at 20% 20%, rgba(23,162,184,0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(6,182,212,0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 60%, rgba(14,165,233,0.05) 0%, transparent 50%);
        }

        .hero-section {
            min-height: 100vh;
            height: 100vh;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            overflow: hidden;
        }

        .floating-icon {
            position: absolute;
            animation: float 6s ease-in-out infinite;
            opacity: 0.8;
            filter: drop-shadow(0 0 10px rgba(23,162,184,0.3));
            transition: all 0.3s ease;
            pointer-events: none;
            user-select: none;
        }

        .floating-icon:hover {
            transform: scale(1.3) !important;
            opacity: 1;
            filter: drop-shadow(0 0 20px rgba(23,162,184,0.6));
        }

        .h2o-1   { top: 10%; right: 10%; font-size: 2.8rem; color: var(--primary-blue); animation-delay: 0s; }
        .h2o-2   { top: 25%; left: 8%;   font-size: 2.2rem; color: #0ea5e9; animation-delay: 2s; }
        .co2     { bottom: 35%; left: 5%; font-size: 2.5rem; color: #06b6d4; animation-delay: 4s; }
        .dna     { top: 15%; left: 25%;  font-size: 2.4rem; color: #14b8a6; animation-delay: 1.5s; }
        .molecule{ bottom: 20%; right: 15%; font-size: 3.2rem; color: var(--primary-blue); animation-delay: 1s; }
        .flask   { top: 45%; right: 5%;  font-size: 3rem; color: #0891b2; animation-delay: 3s; }
        .atom    { top: 65%; left: 3%;   font-size: 2.8rem; color: #0284c7; animation-delay: 5s; }
        .benzene { bottom: 40%; right: 8%; font-size: 2.6rem; color: #06b6d4; animation-delay: 3.5s; }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            25%      { transform: translateY(-25px) rotate(3deg); }
            50%      { transform: translateY(-10px) rotate(-2deg); }
            75%      { transform: translateY(15px) rotate(4deg); }
        }

        .main-content {
            text-align: center;
            z-index: 10;
            max-width: 650px;
            padding: 3rem 2rem;
            backdrop-filter: blur(12px);
            background: rgba(255, 255, 255, 0.15);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.25);
            box-shadow: 0 25px 50px rgba(23,162,184,0.15);
            animation: fadeInUp 1.2s ease-out;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(40px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .brand-title    { font-size: 3.5rem; font-weight: 700; color: var(--dark-text); margin-bottom: 0.5rem; }
        .brand-subtitle { font-size: 3.8rem; font-weight: 800; color: var(--primary-blue); margin-bottom: 2rem; }
        .description    { font-size: 1.15rem; color: #555; margin-bottom: 2.5rem; line-height: 1.7; }

        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary-blue), #0891b2);
            border: none;
            padding: 1rem 3rem;
            font-size: 1.1rem;
            font-weight: 700;
            border-radius: 12px;
            color: white;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 10px 30px rgba(23,162,184,0.3);
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-primary-custom:hover {
            transform: translateY(-4px) scale(1.03);
            box-shadow: 0 20px 40px rgba(23,162,184,0.45);
        }

        .btn-primary-custom::before {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,.3), transparent);
            transition: left .7s;
        }
        .btn-primary-custom:hover::before { left: 100%; }

        .footer {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            color: #6c757d;
            font-size: 0.9rem;
            z-index: 10;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .brand-title    { font-size: 2.6rem; }
            .brand-subtitle { font-size: 3rem; }
            .floating-icon  { font-size: 1.6rem !important; }
            .btn-primary-custom { width: 100%; max-width: 300px; }
        }
    </style>
</head>
<body>

    <div class="hero-section">
        <!-- Floating Icons -->
        <div class="floating-icon h2o-1">H₂O</div>
        <div class="floating-icon h2o-2">H₂O</div>
        <div class="floating-icon co2">CO₂</div>
        <div class="floating-icon dna"><i class="fas fa-dna"></i></div>
        <div class="floating-icon molecule"><i class="fas fa-atom"></i></div>
        <div class="floating-icon flask"><i class="fas fa-flask"></i></div>
        <div class="floating-icon atom"><i class="fas fa-circle-nodes"></i></div>
        <div class="floating-icon benzene">⬡</div>

        <!-- Main Content -->
        <div class="main-content">
            <h1 class="brand-title">Welcome to</h1>
            <h1 class="brand-subtitle">ChemEase</h1>
            <p class="description">
                Your ultimate chemistry learning companion. Master chemical concepts, practice problems, and excel in your studies with our interactive platform.
            </p>
            <div class="d-flex justify-content-center">
                <button class="btn btn-primary-custom" onclick="window.location.href='signin.php'">
                    <i class="fas fa-play me-2"></i>Start Learning
                </button>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            © 2025 ChemEase. All rights reserved.
        </div>
    </div>

</body>
</html>