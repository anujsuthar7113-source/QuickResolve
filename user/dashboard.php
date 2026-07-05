<?php
// user/dashboard.php — User Dashboard

session_start();
require_once '../config/db_18.php';
require_once '../includes/auth_check.php';

// Only users can access this page
requireRole('user');

$userId = $_SESSION['user_id'];

// ── Fetch dashboard stats for this user
$total      = $conn->query("SELECT COUNT(*) FROM complaints WHERE user_id=$userId")->fetch_row()[0];
$pending    = $conn->query("SELECT COUNT(*) FROM complaints WHERE user_id=$userId AND status='pending'")->fetch_row()[0];
$inProgress = $conn->query("SELECT COUNT(*) FROM complaints WHERE user_id=$userId AND status IN('assigned','in_progress')")->fetch_row()[0];
$completed  = $conn->query("SELECT COUNT(*) FROM complaints WHERE user_id=$userId AND status='completed'")->fetch_row()[0];

// Fetch recent 5 complaints for the activity table
$recent = $conn->query("
    SELECT c.id, c.title, c.priority, c.status, c.created_at, d.name AS dept_name
    FROM complaints c
    LEFT JOIN departments d ON c.dept_id = d.id
    WHERE c.user_id = $userId
    ORDER BY c.created_at DESC
    LIMIT 5
");

$pageTitle = 'My Dashboard';
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<div class="dashboard-layout">

    <!-- Sidebar -->
    <aside class="qr-sidebar">
        <div class="sidebar-user-card">
            <div class="d-flex align-items-center gap-2">
                <div style="width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,#3B5BDB,#6366F1);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:1rem">
                    <?= strtoupper(substr($_SESSION['name'],0,1)) ?>
                </div>
                <div>
                    <div class="text-white fw-semibold small"><?= htmlspecialchars($_SESSION['name']) ?></div>
                    <div style="color:rgba(255,255,255,0.45);font-size:0.75rem">User Account</div>
                </div>
            </div>
        </div>

        <div class="sidebar-section-label">Main Menu</div>
        <a href="dashboard.php" class="sidebar-link">
            <i class="fas fa-home"></i> Dashboard
        </a>
        <a href="submit_complaint.php" class="sidebar-link">
            <i class="fas fa-plus-circle"></i> Submit Complaint
        </a>
        <a href="history.php" class="sidebar-link">
            <i class="fas fa-history"></i> My Complaints
        </a>

        <div class="sidebar-section-label">Account</div>
        <a href="../logout.php" class="sidebar-link">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </aside>

    <!-- Main content -->
    <main class="qr-main-content">

        <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h1>Welcome back, <?= htmlspecialchars(explode(' ', $_SESSION['name'])[0]) ?>! 👋</h1>
                <p class="text-muted mb-0">Here's a summary of your complaint activity.</p>
            </div>
            <a href="submit_complaint.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>New Complaint
            </a>
        </div>

        <?php showFlash(); ?>

        <!-- Dashboard stat cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="dash-card primary">
                    <div class="card-icon bg-primary-soft">
                        <i class="fas fa-clipboard-list text-primary"></i>
                    </div>
                    <div class="card-num" data-count="<?= $total ?>"><?= $total ?></div>
                    <div class="card-label">Total Submitted</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="dash-card warning">
                    <div class="card-icon bg-warning-soft">
                        <i class="fas fa-clock text-warning"></i>
                    </div>
                    <div class="card-num" data-count="<?= $pending ?>"><?= $pending ?></div>
                    <div class="card-label">Pending</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="dash-card purple">
                    <div class="card-icon bg-purple-soft">
                        <i class="fas fa-spinner text-purple"></i>
                    </div>
                    <div class="card-num" data-count="<?= $inProgress ?>"><?= $inProgress ?></div>
                    <div class="card-label">In Progress</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="dash-card success">
                    <div class="card-icon bg-success-soft">
                        <i class="fas fa-check-circle text-success"></i>
                    </div>
                    <div class="card-num" data-count="<?= $completed ?>"><?= $completed ?></div>
                    <div class="card-label">Resolved</div>
                </div>
            </div>
        </div>

        <!-- Recent complaints table -->
        <div class="qr-form-card mb-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold mb-0"><i class="fas fa-history me-2 text-primary"></i>Recent Complaints</h5>
                <a href="history.php" class="btn btn-outline-primary btn-sm">View All</a>
            </div>

            <?php if ($recent->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="qr-table">
                    <thead>
                        <tr>
                            <th>#ID</th>
                            <th>Title</th>
                            <th>Department</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $recent->fetch_assoc()): ?>
                        <tr>
                            <td><span class="fw-semibold text-primary">#<?= $row['id'] ?></span></td>
                            <td><?= htmlspecialchars(substr($row['title'], 0, 45)) ?><?= strlen($row['title']) > 45 ? '…' : '' ?></td>
                            <td><?= htmlspecialchars($row['dept_name'] ?? '<span class="text-muted">Unassigned</span>') ?></td>
                            <td><?= priorityBadge($row['priority']) ?></td>
                            <td><?= statusBadge($row['status']) ?></td>
                            <td class="text-muted small"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                            <td>
                                <a href="view_complaint.php?id=<?= $row['id'] ?>" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-eye me-1"></i>View
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-clipboard fa-3x text-muted opacity-50 mb-3 d-block"></i>
                <p class="text-muted">You haven't submitted any complaints yet.</p>
                <a href="submit_complaint.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Submit Your First Complaint
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Quick action cards -->
        <div class="row g-4">
            <div class="col-md-4">
                <a href="submit_complaint.php" class="text-decoration-none">
                    <div class="feature-card text-center">
                        <div class="feature-icon-wrap bg-primary-soft mx-auto">
                            <i class="fas fa-plus-circle text-primary"></i>
                        </div>
                        <h6 class="fw-bold mb-1">Submit Complaint</h6>
                        <p class="text-muted small mb-0">Report a new issue with smart auto-routing</p>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="history.php" class="text-decoration-none">
                    <div class="feature-card text-center">
                        <div class="feature-icon-wrap bg-warning-soft mx-auto">
                            <i class="fas fa-list-ul text-warning"></i>
                        </div>
                        <h6 class="fw-bold mb-1">View History</h6>
                        <p class="text-muted small mb-0">Track all your submitted complaints</p>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="history.php?status=completed" class="text-decoration-none">
                    <div class="feature-card text-center">
                        <div class="feature-icon-wrap bg-success-soft mx-auto">
                            <i class="fas fa-star text-success"></i>
                        </div>
                        <h6 class="fw-bold mb-1">Leave Feedback</h6>
                        <p class="text-muted small mb-0">Rate resolved complaints and share thoughts</p>
                    </div>
                </a>
            </div>
        </div>

    </main>
</div>

<?php include '../includes/footer.php'; ?>
