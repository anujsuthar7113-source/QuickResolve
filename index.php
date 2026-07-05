<?php

// index.php — Landing Page
// stats, and call-to-action sections.

session_start();
require_once 'config/db_18.php';
require_once 'includes/auth_check.php';

$pageTitle = 'Home';

// Get live stats for the stats section
$totalComplaints    = $conn->query("SELECT COUNT(*) FROM complaints")->fetch_row()[0] ?? 0;
$totalResolved      = $conn->query("SELECT COUNT(*) FROM complaints WHERE status='completed'")->fetch_row()[0] ?? 0;
$totalUsers         = $conn->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetch_row()[0] ?? 0;
$totalDepartments   = $conn->query("SELECT COUNT(*) FROM departments")->fetch_row()[0] ?? 0;
?>
<?php include 'includes/header.php'; ?>
<?php include 'includes/navbar.php'; ?>

    <!-- HERO SECTION-->
     
<section class="hero-section" id="home">
    <div class="container position-relative z-1">
        <div class="row align-items-center g-5">

            <!-- Left: Text content -->
            <div class="col-lg-6">
                <div class="hero-badge animate-fade-up delay-1">
                    <i class="fas fa-bolt me-2"></i>Powered by Smart Routing
                </div>

                <h1 class="hero-title animate-fade-up delay-2">
                    Resolve Every<br>
                    Complaint <span>Faster</span><br>
                    Than Ever Before
                </h1>

                <p class="hero-subtitle animate-fade-up delay-3">
                    QuickResolve is a modern, intelligent complaint management
                    platform that automatically routes issues to the right department —
                    so nothing ever slips through the cracks.
                </p>

                <!-- buttons -->
                <div class="d-flex gap-3 flex-wrap animate-fade-up delay-4">
                    <?php if (!isLoggedIn()): ?>
                        <a href="register.php" class="btn btn-warning btn-lg px-5 fw-bold">
                            <i class="fas fa-rocket me-2"></i>Get Started Free
                        </a>
                        <a href="login.php" class="btn btn-outline-light btn-lg px-5">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </a>
                    <?php else: ?>
                        <a href="<?= SITE_URL ?>/<?= $_SESSION['role'] ?>/dashboard.php"
                           class="btn btn-warning btn-lg px-5 fw-bold">
                            <i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Trust pills -->
                <div class="d-flex gap-3 flex-wrap mt-4 animate-fade-up delay-5">
                    <div class="hero-stat-pill">
                        <i class="fas fa-shield-alt text-warning"></i> Secure & Private
                    </div>
                    <div class="hero-stat-pill">
                        <i class="fas fa-magic text-info"></i> Smart Auto-Routing
                    </div>
                    <div class="hero-stat-pill">
                        <i class="fas fa-clock text-success"></i> Real-time Tracking
                    </div>
                </div>
            </div>

            <!-- Right: Visual card -->
            <div class="col-lg-6 animate-fade-up delay-3">
                <div class="hero-visual">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div class="flex-shrink-0" style="width:44px;height:44px;border-radius:12px;background:rgba(245,158,11,0.2);display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-bolt text-warning fs-5"></i>
                        </div>
                        <div>
                            <div class="text-white fw-semibold">Live Complaint Feed</div>
                            <div class="small" style="color:rgba(255,255,255,0.5)">Updated in real-time</div>
                        </div>
                        <span class="ms-auto badge bg-success px-3 py-2">
                            <i class="fas fa-circle me-1" style="font-size:0.5rem"></i>Live
                        </span>
                    </div>

                    <!-- Mock complaint items -->
                    <?php
                    // Fetch latest 4 complaints for the hero preview
                    $preview = $conn->query("SELECT c.title, c.status, d.name as dept FROM complaints c LEFT JOIN departments d ON c.dept_id=d.id ORDER BY c.created_at DESC LIMIT 4");
                    $mockData = [
                        ['title'=>'Street light outage in Block C', 'status'=>'completed',   'dept'=>'Electrical'],
                        ['title'=>'Water pipe leak in corridor',     'status'=>'in_progress','dept'=>'Plumbing'],
                        ['title'=>'WiFi down in meeting room',       'status'=>'assigned',   'dept'=>'IT Support'],
                        ['title'=>'Broken window latch – Room 204',  'status'=>'pending',   'dept'=>'Maintenance'],
                    ];
                    $rows = $preview->num_rows > 0 ? [] : $mockData;
                    while ($row = $preview->fetch_assoc()) $rows[] = $row;
                    $rows = array_slice($rows, 0, 4);
                    ?>
                    <?php foreach ($rows as $r): ?>
                    <div class="d-flex align-items-center gap-3 mb-3 p-3"
                         style="background:rgba(255,255,255,0.06);border-radius:10px;border:1px solid rgba(255,255,255,0.08)">
                        <div class="flex-shrink-0" style="width:8px;height:8px;border-radius:50%;background:<?=
                            $r['status']==='completed' ? '#10B981' : ($r['status']==='in_progress' ? '#8B5CF6' : ($r['status']==='assigned' ? '#3B5BDB' : '#F59E0B'))
                        ?>"></div>
                        <div class="flex-grow-1 text-white small" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                            <?= htmlspecialchars($r['title']) ?>
                        </div>
                        <div class="small" style="color:rgba(255,255,255,0.45);white-space:nowrap">
                            <?= htmlspecialchars($r['dept'] ?? 'Unassigned') ?>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <div class="text-center mt-4">
                        <div class="row g-3">
                            <div class="col-4">
                                <div style="background:rgba(255,255,255,0.06);border-radius:10px;padding:14px 8px">
                                    <div class="fw-bold text-white fs-5"><?= $totalComplaints ?></div>
                                    <div class="small" style="color:rgba(255,255,255,0.45)">Total</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div style="background:rgba(16,185,129,0.12);border-radius:10px;padding:14px 8px">
                                    <div class="fw-bold text-success fs-5"><?= $totalResolved ?></div>
                                    <div class="small" style="color:rgba(255,255,255,0.45)">Resolved</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div style="background:rgba(245,158,11,0.12);border-radius:10px;padding:14px 8px">
                                    <div class="fw-bold text-warning fs-5"><?= $totalDepartments ?></div>
                                    <div class="small" style="color:rgba(255,255,255,0.45)">Depts</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>
<!-- End Hero Section -->

<!-- ABOUT SECTION -->
<section class="section-py section-white" id="about">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-5">
                <span class="section-badge">About Us</span>
                <h2 class="section-title">What is QuickResolve?</h2>
                <p class="text-muted lh-lg mb-4">
                    QuickResolve is a next-generation smart complaint management system
                    built for organisations, housing societies, campuses, and businesses.
                    It bridges the gap between citizens and the departments that serve them —
                    ensuring every complaint is tracked, routed intelligently, and resolved efficiently.
                </p>
                <p class="text-muted lh-lg mb-4">
                    With our proprietary smart keyword-routing engine, complaints are automatically
                    sent to the right department the moment they are submitted — no manual sorting,
                    no delays, no bottlenecks.
                </p>
                <div class="d-flex gap-4">
                    <div>
                        <div class="fw-bold fs-4 text-primary">98%</div>
                        <div class="small text-muted">Resolution Rate</div>
                    </div>
                    <div>
                        <div class="fw-bold fs-4 text-success">&lt; 24h</div>
                        <div class="small text-muted">Avg Response Time</div>
                    </div>
                    <div>
                        <div class="fw-bold fs-4 text-warning">6+</div>
                        <div class="small text-muted">Departments</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="row g-4">
                    <?php
                    // Why choose us feature blocks
                    $whyUs = [
                        ['icon'=>'fas fa-magic',        'color'=>'primary',  'title'=>'Smart Auto-Routing',    'desc'=>'Our AI keyword engine reads complaint descriptions and instantly routes them to the correct department.'],
                        ['icon'=>'fas fa-chart-bar',    'color'=>'success',  'title'=>'Real-time Analytics',   'desc'=>'Track complaint trends, department performance, and resolution rates with live dashboards.'],
                        ['icon'=>'fas fa-shield-alt',   'color'=>'purple',   'title'=>'Role-based Security',   'desc'=>'Separate portals for users, admins, and departments with full session-based authentication.'],
                        ['icon'=>'fas fa-star',         'color'=>'warning',  'title'=>'Feedback System',       'desc'=>'Users can rate resolutions and provide feedback to help departments improve continuously.'],
                    ];
                    foreach ($whyUs as $w):
                    ?>
                    <div class="col-sm-6">
                        <div class="feature-card">
                            <div class="feature-icon-wrap bg-<?= $w['color'] ?>-soft">
                                <i class="<?= $w['icon'] ?> text-<?= $w['color'] ?>"></i>
                            </div>
                            <h5 class="fw-bold mb-2"><?= $w['title'] ?></h5>
                            <p class="text-muted small mb-0"><?= $w['desc'] ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- End About Section -->

<!-- FEATURES SECTION -->
<section class="section-py section-light" id="features">
    <div class="container">
        <div class="text-center mb-5">
            <span class="section-badge">Features</span>
            <h2 class="section-title">Everything You Need to Manage Complaints</h2>
            <p class="text-muted mx-auto" style="max-width:540px">
                From submission to resolution, every step is streamlined,
                automated, and beautifully presented.
            </p>
        </div>

        <!-- Features cards -->
        <div class="row g-4">
            <?php
            // Feature cards data
            $features = [
                ['icon'=>'fas fa-paper-plane',      'color'=>'primary',  'title'=>'Easy Submission',         'desc'=>'Submit complaints with title, description, priority level, and photo attachments in seconds.'],
                ['icon'=>'fas fa-route',             'color'=>'secondary','title'=>'Intelligent Routing',     'desc'=>'Keywords in your complaint are detected and it\'s automatically assigned to the right department.'],
                ['icon'=>'fas fa-search',            'color'=>'success',  'title'=>'Live Tracking',           'desc'=>'Track your complaint status from pending through in-progress to completed — in real-time.'],
                ['icon'=>'fas fa-users-cog',         'color'=>'purple',   'title'=>'Department Portals',      'desc'=>'Each department has its own dedicated portal to view, update, and manage assigned complaints.'],
                ['icon'=>'fas fa-user-shield',       'color'=>'danger',   'title'=>'Admin Control Panel',     'desc'=>'Admins manage users, departments, manually assign complaints, and access full analytics.'],
                ['icon'=>'fas fa-comment-dots',      'color'=>'orange',   'title'=>'Rating & Feedback',       'desc'=>'After resolution, users rate their experience and provide comments to drive service improvement.'],
                ['icon'=>'fas fa-filter',            'color'=>'primary',  'title'=>'Advanced Filtering',      'desc'=>'Filter complaints by status, priority, department, date range, and keyword searches.'],
                ['icon'=>'fas fa-archive',           'color'=>'success',  'title'=>'Archive System',          'desc'=>'Completed complaints are archived for record-keeping, audit trails, and future reference.'],
            ];
            foreach ($features as $f):
            ?>
            <div class="col-lg-3 col-md-4 col-sm-6 animate-fade-up">
                <div class="feature-card h-100">
                    <div class="feature-icon-wrap bg-<?= $f['color'] ?>-soft">
                        <i class="<?= $f['icon'] ?> text-<?= $f['color'] ?>"></i>
                    </div>
                    <h6 class="fw-bold mb-2"><?= $f['title'] ?></h6>
                    <p class="text-muted small mb-0 lh-lg"><?= $f['desc'] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<!-- End Features Section -->

<!-- COMPLAINT FLOW STEPS -->
<section class="section-py section-white">
    <div class="container">
        <div class="text-center mb-5">
            <span class="section-badge">How It Works</span>
            <h2 class="section-title">From Complaint to Resolution in 4 Steps</h2>
        </div>

        <div class="row g-4 justify-content-center position-relative">
            <?php
            // Flow steps
            $steps = [
                ['num'=>'1', 'icon'=>'fas fa-user-plus',    'color'=>'primary',  'title'=>'User Registers',       'desc'=>'Create your free account and verify your identity through admin approval.'],
                ['num'=>'2', 'icon'=>'fas fa-edit',         'color'=>'secondary','title'=>'Submit Complaint',     'desc'=>'Fill out the form with details. Smart routing instantly detects the right department.'],
                ['num'=>'3', 'icon'=>'fas fa-building',     'color'=>'purple',   'title'=>'Department Acts',      'desc'=>'The assigned department reviews the issue, updates progress, and works on resolution.'],
                ['num'=>'4', 'icon'=>'fas fa-check-circle', 'color'=>'success',  'title'=>'Resolved & Rated',     'desc'=>'Complaint is marked complete and you receive a notification to leave feedback.'],
            ];
            foreach ($steps as $i => $step):
            ?>
            <div class="col-lg-3 col-md-6">
                <div class="flow-step">
                    <!-- Step number bubble -->
                    <div class="flow-step-num"><?= $step['num'] ?></div>

                    <div class="feature-card text-center">
                        <div class="feature-icon-wrap bg-<?= $step['color'] ?>-soft mx-auto">
                            <i class="<?= $step['icon'] ?> text-<?= $step['color'] ?>"></i>
                        </div>
                        <h5 class="fw-bold mb-2"><?= $step['title'] ?></h5>
                        <p class="text-muted small mb-0 lh-lg"><?= $step['desc'] ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<!-- End Flow Section -->

<!--STATS SECTION -->
<section class="section-dark section-py">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title text-white">Trusted by Organisations Everywhere</h2>
            <p style="color:rgba(255,255,255,0.6)">Real numbers that speak for themselves</p>
        </div>
        <div class="row g-4 text-center">
            <?php
            $stats = [
                ['val'=>$totalComplaints ?: 150,  'suffix'=>'+', 'label'=>'Complaints Filed',     'icon'=>'fas fa-clipboard-list', 'color'=>'text-warning'],
                ['val'=>$totalResolved   ?: 120,  'suffix'=>'+', 'label'=>'Issues Resolved',      'icon'=>'fas fa-check-circle',   'color'=>'text-success'],
                ['val'=>$totalUsers      ?: 80,   'suffix'=>'+', 'label'=>'Registered Users',     'icon'=>'fas fa-users',          'color'=>'text-info'],
                ['val'=>$totalDepartments?: 6,    'suffix'=>'',  'label'=>'Active Departments',   'icon'=>'fas fa-building',       'color'=>'text-warning'],
            ];
            foreach ($stats as $s):
            ?>
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <i class="<?= $s['icon'] ?> <?= $s['color'] ?> fs-2 mb-3 d-block"></i>
                    <div class="stat-number" data-count="<?= $s['val'] ?>" data-suffix="<?= $s['suffix'] ?>">
                        0<?= $s['suffix'] ?>
                    </div>
                    <p class="text-white-50 mt-2 mb-0"><?= $s['label'] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<!-- End Stats Section -->

<!-- CTA SECTION-->
<section class="section-py section-white text-center">
    <div class="container">
        <div class="mx-auto" style="max-width:580px">
            <span class="section-badge">Get Started</span>
            <h2 class="section-title">Ready to Resolve Complaints Smarter?</h2>
            <p class="text-muted mb-5">
                Join hundreds of users who have already streamlined their complaint
                management process with QuickResolve.
            </p>
            <?php if (!isLoggedIn()): ?>
            <div class="d-flex gap-3 justify-content-center">
                <a href="register.php" class="btn btn-primary btn-lg px-5">
                    <i class="fas fa-rocket me-2"></i>Create Free Account
                </a>
                <a href="login.php" class="btn btn-outline-primary btn-lg px-5">
                    <i class="fas fa-sign-in-alt me-2"></i>Login
                </a>
            </div>
            <?php else: ?>
            <a href="<?= SITE_URL ?>/<?= $_SESSION['role'] ?>/dashboard.php" class="btn btn-primary btn-lg px-5">
                <i class="fas fa-tachometer-alt me-2"></i>Go to Dashboard
            </a>
            <?php endif; ?>
        </div>
    </div>
</section>
<!-- End CTA Section -->

<?php include 'includes/footer.php'; ?>
