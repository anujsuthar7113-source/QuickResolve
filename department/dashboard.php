<?php
session_start();
require_once '../config/db_18.php';
require_once '../includes/auth_check.php';

requireRole('department');

$deptId = $_SESSION['dept_id'];

if (!$deptId) {
    setFlash('danger', 'No department linked to your account. Contact admin.');
    redirect(SITE_URL . '/login.php');
}

// ── Fetch department info
$dept = $conn->query("SELECT * FROM departments WHERE id=$deptId")->fetch_assoc();

// ── Fetch counts for this department
$total      = $conn->query("SELECT COUNT(*) FROM complaints WHERE dept_id=$deptId")->fetch_row()[0];
$assigned   = $conn->query("SELECT COUNT(*) FROM complaints WHERE dept_id=$deptId AND status='assigned'")->fetch_row()[0];
$inProgress = $conn->query("SELECT COUNT(*) FROM complaints WHERE dept_id=$deptId AND status='in_progress'")->fetch_row()[0];
$completed  = $conn->query("SELECT COUNT(*) FROM complaints WHERE dept_id=$deptId AND status='completed'")->fetch_row()[0];

// Fetch recent 6 complaints for this department
$recent = $conn->query("
    SELECT c.id, c.title, c.priority, c.status, c.created_at, u.name AS user_name
    FROM complaints c
    LEFT JOIN users u ON c.user_id = u.id
    WHERE c.dept_id = $deptId
    ORDER BY
        CASE c.priority WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END,
        c.created_at ASC
    LIMIT 6
");

$pageTitle = $dept['name'] . ' Dashboard';
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<div class="dashboard-layout">

    <!-- Department Sidebar -->
    <aside class="qr-sidebar">
        <div class="sidebar-user-card">
            <div class="d-flex align-items-center gap-2">
                <div style="width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,#8B5CF6,#6366F1);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700">
                    <?= strtoupper(substr($dept['name'],0,1)) ?>
                </div>
                <div>
                    <div class="text-white fw-semibold small"><?= htmlspecialchars($dept['name']) ?></div>
                    <div style="color:rgba(255,255,255,0.45);font-size:0.75rem">Department Account</div>
                </div>
            </div>
        </div>

        <div class="sidebar-section-label">My Department</div>
        <a href="dashboard.php"              class="sidebar-link active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="assigned_complaints.php"    class="sidebar-link"><i class="fas fa-clipboard-list"></i> All Complaints</a>
        <a href="assigned_complaints.php?status=assigned"    class="sidebar-link"><i class="fas fa-tag"></i> New Assigned</a>
        <a href="assigned_complaints.php?status=in_progress" class="sidebar-link"><i class="fas fa-spinner"></i> In Progress</a>
        <a href="filter.php"                 class="sidebar-link"><i class="fas fa-filter"></i> Filter</a>

        <div class="sidebar-section-label">Account</div>
        <a href="../logout.php" class="sidebar-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </aside>

    <main class="qr-main-content">
        <div class="page-header">
            <h1><?= htmlspecialchars($dept['name']) ?> Department</h1>
            <p class="text-muted mb-0">
                <i class="fas fa-envelope me-1"></i><?= htmlspecialchars($dept['email'] ?: 'No contact email set') ?>
                &nbsp;·&nbsp;
                <i class="fas fa-info-circle me-1"></i><?= htmlspecialchars($dept['description'] ?: 'Department portal') ?>
            </p>
        </div>

        <?php showFlash(); ?>

        <!-- Stat Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="dash-card primary">
                    <div class="card-icon bg-primary-soft"><i class="fas fa-clipboard-list text-primary"></i></div>
                    <div class="card-num" data-count="<?= $total ?>"><?= $total ?></div>
                    <div class="card-label">Total Assigned</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="dash-card warning">
                    <div class="card-icon bg-warning-soft"><i class="fas fa-tag text-warning"></i></div>
                    <div class="card-num" data-count="<?= $assigned ?>"><?= $assigned ?></div>
                    <div class="card-label">New / Assigned</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="dash-card purple">
                    <div class="card-icon bg-purple-soft"><i class="fas fa-spinner text-purple"></i></div>
                    <div class="card-num" data-count="<?= $inProgress ?>"><?= $inProgress ?></div>
                    <div class="card-label">In Progress</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="dash-card success">
                    <div class="card-icon bg-success-soft"><i class="fas fa-check-circle text-success"></i></div>
                    <div class="card-num" data-count="<?= $completed ?>"><?= $completed ?></div>
                    <div class="card-label">Completed</div>
                </div>
            </div>
        </div>

        <!-- Recent complaints with priority ordering -->
        <div class="qr-form-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold mb-0"><i class="fas fa-exclamation-circle me-2 text-primary"></i>Priority Queue</h5>
                <a href="assigned_complaints.php" class="btn btn-outline-primary btn-sm">View All</a>
            </div>

            <?php if ($recent->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="qr-table">
                    <thead>
                        <tr><th>#ID</th><th>Title</th><th>From User</th><th>Priority</th><th>Status</th><th>Date</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                    <?php while ($row = $recent->fetch_assoc()): ?>
                    <tr>
                        <td><span class="fw-semibold text-primary">#<?= $row['id'] ?></span></td>
                        <td class="fw-semibold small"><?= htmlspecialchars(substr($row['title'],0,45)) ?>…</td>
                        <td><?= htmlspecialchars($row['user_name']) ?></td>
                        <td><?= priorityBadge($row['priority']) ?></td>
                        <td><?= statusBadge($row['status']) ?></td>
                        <td class="text-muted small"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                        <td>
                            <a href="update_status.php?id=<?= $row['id'] ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-edit me-1"></i>Update
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-check-circle fa-3x text-success opacity-50 mb-3 d-block"></i>
                <p class="text-muted">No active complaints assigned to your department.</p>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
