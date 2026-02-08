<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChemEase - <?php echo ucfirst(str_replace('-', ' ', $page)); ?></title>
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
            --light-gray: #f8f9fa;
            --input-bg: rgba(255, 255, 255, 0.9);
            --success-color: #28A745;
            --warning-color: #FFC107;
            --danger-color: #DC3545;
            --purple-color: #6F42C1;
            --gradient-start: #17a2b8;
            --gradient-end: #20c5d4;
        }
        body {
            background: linear-gradient(135deg, var(--light-blue) 0%, var(--light-gray) 50%, #ffffff 100%);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            min-height: 100vh;
            color: var(--dark-text);
        }
        .navbar {
            box-shadow: 0 4px 20px rgba(23, 162, 184, 0.1);
            background: var(--input-bg) !important;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(23, 162, 184, 0.1);
            transition: all 0.3s ease;
            position: sticky;
            top: 0;
            z-index: 1030;
        }
        .navbar-brand {
            color: var(--primary-blue) !important;
            font-weight: 700;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
        }
        .navbar-brand:hover {
            transform: scale(1.05);
        }
        .navbar-brand img {
            transition: all 0.3s ease;
        }
        .navbar-brand:hover img {
            transform: rotate(360deg) scale(1.1);
        }
        /* Nav Links */
        .nav-link {
            color: var(--dark-text) !important;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 0.5rem 0.75rem !important;
            margin: 0 0.1rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
            font-size: 0.95rem;
            white-space: nowrap;
        }
        .nav-link i {
            font-size: 1.1rem;
            width: 22px;
            text-align: center;
        }
        .nav-link:hover {
            color: var(--primary-blue) !important;
            background: rgba(23, 162, 184, 0.1);
            border-radius: 8px;
        }
        .nav-link.active {
            color: var(--primary-blue) !important;
            background: rgba(23, 162, 184, 0.15);
            border-radius: 8px;
            font-weight: 600;
        }
        /* Profile Dropdown */
        .profile-trigger {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(23, 162, 184, 0.3);
            border: 3px solid white;
        }
        .profile-trigger:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(23, 162, 184, 0.4);
        }
        .profile-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .profile-initials {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--primary-blue), var(--gradient-end));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1rem;
        }
        .dropdown-menu {
            background: var(--input-bg);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(23, 162, 184, 0.1);
            border-radius: 10px;
            box-shadow: 0 8px 24px rgba(23, 162, 184, 0.2);
        }
        .dropdown-item {
            color: var(--dark-text);
            font-weight: 500;
            padding: 0.6rem 1.2rem;
        }
        .dropdown-item:hover {
            background: var(--primary-blue);
            color: white !important;
        }

        /* === RESPONSIVE FIXES === */
        @media (max-width: 991.98px) { /* Tablets and below */
            .navbar-collapse {
                background: var(--input-bg);
                backdrop-filter: blur(10px);
                border-radius: 12px;
                margin: 0.75rem -12px 0; /* Extend to full width */
                padding: 1rem 1.5rem;
                box-shadow: 0 8px 24px rgba(23, 162, 184, 0.2);
            }

            .navbar-nav .nav-item {
                margin: 0.25rem 0;
            }
            .navbar-nav .nav-link {
                padding: 0.75rem 1rem !important;
                border-radius: 8px;
                font-size: 1rem;
                justify-content: start;
            }

            .profile-dropdown {
                margin: 1rem 0 0.5rem;
                justify-content: center;
            }
        }

        @media (max-width: 767.98px) {
            .navbar-collapse {
                margin: 0.75rem -12px 0;
                padding: 1rem 1.25rem;
            }
        }

        @media (max-width: 576px) {
            .navbar-brand {
                font-size: 1.3rem;
            }
            .navbar-brand img {
                width: 32px;
                height: 32px;
            }
            .profile-trigger {
                width: 38px;
                height: 38px;
            }
            .profile-initials {
                font-size: 0.9rem;
            }
            .navbar-collapse {
                margin: 0.75rem -8px 0;
                padding: 0.75rem 1rem;
            }
        }

        /* Loader */
        .loader {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: linear-gradient(135deg, white 0%, var(--light-blue) 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            transition: opacity 0.8s ease;
        }
        .loader img {
            width: 100px; height: 100px;
            animation: logoFloat 3s ease-in-out infinite;
        }
        .loader-text {
            margin-top: 2rem;
            font-size: 1.4rem;
            color: var(--primary-blue);
            font-weight: 700;
            animation: textPulse 2s ease-in-out infinite;
        }
        @keyframes logoFloat {
            0%,100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(10deg); }
        }
        @keyframes textPulse {
            0%,100% { opacity: 0.7; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.05); }
        }
        .fade-out { opacity: 0; pointer-events: none; }
    </style>
</head>