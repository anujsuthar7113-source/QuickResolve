<?php
// includes/navbar.php — Top Navigation Bar
?>
<!-- Top Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark qr-navbar sticky-top shadow-sm">
    <div class="container">

        <!-- Brand logo -->
        <a class="navbar-brand fw-bold" href="<?= SITE_URL ?>/index.php">
            <i class="fas fa-bolt me-2 text-warning"></i>
            QuickResolve<span class="text-info"></span>
        </a>

        <!-- Mobile hamburger toggle -->
        <button class="navbar-toggler border-0" type="button"
                data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Nav links -->
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-1">

                <?php if (!isLoggedIn()): ?>
                    <!-- Guest navigation -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?= SITE_URL ?>/index.php#about">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= SITE_URL ?>/index.php#features">Features</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= SITE_URL ?>/login.php">
                            <i class="fas fa-sign-in-alt me-1"></i>Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-warning btn-sm px-4 ms-2 fw-semibold" href="<?= SITE_URL ?>/register.php">
                            Get Started
                        </a>
                    </li>

                <?php elseif ($_SESSION['role'] === 'admin'): ?>
                    <!-- Admin navigation -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?= SITE_URL ?>/admin/dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= SITE_URL ?>/admin/view_complaints.php">Complaints</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= SITE_URL ?>/admin/manage_users.php">Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= SITE_URL ?>/admin/analytics.php">Analytics</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-outline-light btn-sm ms-2" href="<?= SITE_URL ?>/logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i>Logout
                        </a>
                    </li>

                <?php elseif ($_SESSION['role'] === 'department'): ?>
                    <!-- Department navigation -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?= SITE_URL ?>/department/dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= SITE_URL ?>/department/assigned_complaints.php">My Complaints</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-outline-light btn-sm ms-2" href="<?= SITE_URL ?>/logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i>Logout
                        </a>
                    </li>

                <?php else: ?>
                    <!-- User navigation -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?= SITE_URL ?>/user/dashboard.php">
                            <i class="fas fa-home me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= SITE_URL ?>/user/submit_complaint.php">Submit</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= SITE_URL ?>/user/history.php">History</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i><?= htmlspecialchars($_SESSION['name']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?= SITE_URL ?>/logout.php">
                                <i class="fas fa-sign-out-alt me-2 text-danger"></i>Logout</a>
                            </li>
                        </ul>
                    </li>
                <?php endif; ?>

            </ul>
        </div>
    </div>
</nav>
<!-- End Navbar -->
