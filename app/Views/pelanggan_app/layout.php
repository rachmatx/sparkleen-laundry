<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title><?= $title ?? 'Sparkleen Pay' ?></title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary: #0ea5e9;
            --primary-dark: #0284c7;
            --secondary: #e0f2fe;
            --bg-color: #f1f5f9;
            --surface: #ffffff;
            --text: #0f172a;
            --text-light: #64748b;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background-color: #e2e8f0;
            color: var(--text);
            -webkit-font-smoothing: antialiased;
        }

        .mobile-container {
            max-width: 480px;
            margin: 0 auto;
            min-height: 100vh;
            background-color: var(--bg-color);
            position: relative;
            box-shadow: 0 0 20px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }

        .btn-primary-custom {
            background-color: var(--primary);
            border: none;
            color: white;
            border-radius: 12px;
            padding: 12px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary-custom:hover, .btn-primary-custom:active {
            background-color: var(--primary-dark);
            color: white;
            transform: translateY(-1px);
        }

        .top-nav {
            padding: 16px 20px;
            display: flex;
            align-items: center;
            background: var(--surface);
            position: sticky;
            top: 0;
            z-index: 50;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .top-nav h5 {
            margin: 0;
            font-weight: 600;
            flex-grow: 1;
            text-align: center;
        }

        .top-nav .back-btn {
            color: var(--text);
            font-size: 1.2rem;
            text-decoration: none;
            width: 32px;
        }

        .content-area {
            flex-grow: 1;
            padding: 20px;
            padding-bottom: 80px;
        }

        /* Utils */
        .rounded-3xl { border-radius: 1.5rem; }
        .text-primary-custom { color: var(--primary); }
        .bg-primary-custom { background-color: var(--primary); }
        .bg-secondary-custom { background-color: var(--secondary); }
    </style>
</head>
<body>

<div class="mobile-container">
    <?= $this->renderSection('content') ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?= $this->renderSection('scripts') ?>
</body>
</html>
