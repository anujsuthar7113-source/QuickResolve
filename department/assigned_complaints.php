<?php
session_start();
require_once '../config/db_18.php';
require_once '../includes/auth_check.php';

requireRole('department');

$deptId = $_SESSION['dept_id'];
$dept   = $conn->query("SELECT name FROM departments WHERE id=$deptId")->fetch_assoc();

// Filter by status
$filterStatus = sanitize($conn, $_GET['status'] ?? '');
$where = "WHERE c.dept_id = $deptId";
if ($filterStatus) $where .= " AND c.status='$filterStatus'";

// Fetch department-specific complaints
$complaints = $conn->query("
    SELECT c.id, c.title, c.priority, c.status, c.auto_assigned, c.created_at, c.admin_note,
           u.name AS user_name
    FROM complaints c
    LEFT JOIN users u ON c.user_id = u.id
    $where
    ORDER BY
        CASE c.priority WHEN 'critical' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 ELSE 4 END,
        c.created_at ASC
");

$pageTitle = 'Assigned Complaints';
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<div class="dashboard-layout">
    <aside class="qr-sidebar">
        <div class="sidebar-user-card">
            <div class="d-flex align-items-center gap-2">
                <div style="width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,#8B5CF6,#6366F1);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700">
                    <?= strtoupper(substr($dept['name'],0,1)) ?>
                </div>
                <div>
                    <div class="text-white fw-semibold small"><?= htmlspecialchars($dept['name']) ?></div>
                    <div style="color:rgba(255,255,255,0.45);font-size:0.75rem">Department</div>
                </div>
            </div>
        </div>
        <div class="sidebar-section-label">My Department</div>
        <a href="dashboard.php"              class="sidebar-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="assigned_complaints.php"    class="sidebar-link active"><i class="fas fa-clipboard-list"></i> All Complaints</a>
        <a href="assigned_complaints.php?status=assigned"    class="sidebar-link"><i class="fas fa-tag"></i> New Assigned</a>
        <a href="assigned_complaints.php?status=in_progress" class="sidebar-link"><i class="fas fa-spinner"></i> In Progress</a>
        <a href="filter.php"                 class="sidebar-link"><i class="fas fa-filter"></i> Filter</a>
        <div class="sidebar-section-label">Account</div>
        <a href="../logout.php" class="sidebar-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </aside>

    <main class="qr-main-content">
        <div class="page-header">
            <h1><i class="fas fa-clipboard-list me-2 text-primary"></i>Complaints – <?= htmlspecialchars($dept['name']) ?></h1>
            <p class="text-muted mb-0"><?= $complaints->num_rows ?> complaint(s) in queue</p>
        </div>

        <?php showFlash(); ?>

        <!-- Status filter tabs -->
        <div class="d-flex gap-2 flex-wrap mb-4">
            <a href="assigned_complaints.php"                    class="btn btn-sm <?= !$filterStatus ? 'btn-primary' : 'btn-outline-secondary' ?>">All</a>
            <a href="assigned_complaints.php?status=assigned"    class="btn btn-sm <?= $filterStatus==='assigned'    ? 'btn-primary' : 'btn-outline-secondary' ?>">Assigned</a>
            <a href="assigned_complaints.php?status=in_progress" class="btn btn-sm <?= $filterStatus==='in_progress' ? 'btn-warning' : 'btn-outline-secondary' ?>" style="<?= $filterStatus==='in_progress' ? 'background:var(--purple);border-color:var(--purple);color:#fff' : '' ?>">In Progress</a>
            <a href="assigned_complaints.php?status=completed"   class="btn btn-sm <?= $filterStatus==='completed'   ? 'btn-success' : 'btn-outline-secondary' ?>">Completed</a>
        </div>

        <div class="qr-form-card">
            <div class="table-responsive">
                <table class="qr-table">
                    <thead>
                        <tr><th>#ID</th><th>Title</th><th>Submitted By</th><th>Priority</th><th>Status</th><th>Auto-Routed</th><th>Date</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                    <?php if ($complaints->num_rows > 0):
                        while ($row = $complaints->fetch_assoc()): ?>
                        <tr>
                            <td><span class="fw-semibold text-primary">#<?= $row['id'] ?></span></td>
                            <td>
                                <div class="fw-semibold small"><?= htmlspecialchars(substr($row['title'],0,45)) ?>…</div>
                                <?php if ($row['admin_note']): ?>
                                <div class="small text-muted mt-1">
                                    <i class="fas fa-comment me-1"></i><?= htmlspecialchars(substr($row['admin_note'],0,50)) ?>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($row['user_name']) ?></td>
                            <td><?= priorityBadge($row['priority']) ?></td>
                            <td><?= statusBadge($row['status']) ?></td>
                            <td>
                                <?= $row['auto_assigned']
                                    ? '<span class="badge badge-purple"><i class="fas fa-magic me-1"></i>Auto</span>'
                                    : '<span class="badge bg-secondary">Manual</span>' ?>
                            </td>
                            <td class="text-muted small"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                            <td>
                                <a href="update_status.php?id=<?= $row['id'] ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-edit me-1"></i>Update
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr><td colspan="8" class="text-center py-5 text-muted">No complaints found in this view.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
