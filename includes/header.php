<!DOCTYPE html>
<html lang="en">
<head>
    <!-- includes/header.php — Global HTML Head-->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="QuickResolve_18 – Smart, fast complaint management for modern organisations">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' – ' . SITE_NAME : SITE_NAME . ' – Smart Complaint Management' ?></title>

    <!-- Bootstrap 5 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

    <!-- Font Awesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Google Fonts — Sora (display) + DM Sans (body) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">

    <!-- Custom stylesheet -->
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">

    <!-- Chart.js (loaded on every page — used only where needed) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" defer></script>
</head>
<body>
