<?php
// ============================================================
// user/history.php — Complaint History
// QuickResolve_18 – Smart Complaint Management System
// ============================================================

session_start();
require_once '../config/db_18.php';
require_once '../includes/auth_check.php';

requireRole('user');

$userId = $_SESSION['user_id'];

// ── Filter by status if requested ────────────────────────────
$filterStatus = isset($_GET['status']) ? sanitize($conn, $_GET['status']) : '';
$validStatuses = ['pending','assigned','in_progress','completed','rejected'];
if ($filterStatus && !in_array($filterStatus, $validStatuses)) $filterStatus = '';

// Build WHERE clause
$where = "WHERE c.user_id = $userId";
if ($filterStatus) $where .= " AND c.status = '$filterStatus'";

// Fetch complaints with department name
$complaints = $conn->query("
    SELECT c.id, c.title, c.priority, c.status, c.auto_assigned, c.created_at,
           d.name AS dept_name,
           (SELECT COUNT(*) FROM feedback f WHERE f.complaint_id = c.id) AS has_feedback
    FROM complaints c
    LEFT JOIN departments d ON c.dept_id = d.id
    $where
    ORDER BY c.created_at DESC
");

$pageTitle = 'My Complaints';
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<div class="dashboard-layout">
    <aside class="qr-sidebar">
        <div class="sidebar-user-card">
            <div class="d-flex align-items-center gap-2">
                <div style="width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,#3B5BDB,#6366F1);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700">
                    <?= strtoupper(substr($_SESSION['name'],0,1)) ?>
                </div>
                <div>
                    <div class="text-white fw-semibold small"><?= htmlspecialchars($_SESSION['name']) ?></div>
                    <div style="color:rgba(255,255,255,0.45);font-size:0.75rem">User Account</div>
                </div>
            </div>
        </div>
        <div class="sidebar-section-label">Main Menu</div>
        <a href="dashboard.php"        class="sidebar-link"><i class="fas fa-home"></i> Dashboard</a>
        <a href="submit_complaint.php" class="sidebar-link"><i class="fas fa-plus-circle"></i> Submit Complaint</a>
        <a href="history.php"          class="sidebar-link active"><i class="fas fa-history"></i> My Complaints</a>
        <div class="sidebar-section-label">Account</div>
        <a href="../logout.php" class="sidebar-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </aside>

    <main class="qr-main-content">
        <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h1><i class="fas fa-history me-2 text-primary"></i>My Complaints</h1>
                <p class="text-muted mb-0">View and track all your submitted complaints</p>
            </div>
            <a href="submit_complaint.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>New Complaint
            </a>
        </div>

        <?php showFlash(); ?>

        <!-- Filter tabs -->
        <div class="d-flex gap-2 flex-wrap mb-4">
            <a href="history.php" class="btn btn-sm <?= !$filterStatus ? 'btn-primary' : 'btn-outline-secondary' ?>">All</a>
            <a href="history.php?status=pending"     class="btn btn-sm <?= $filterStatus==='pending'     ? 'btn-warning'  : 'btn-outline-secondary' ?>">Pending</a>
            <a href="history.php?status=assigned"    class="btn btn-sm <?= $filterStatus==='assigned'    ? 'btn-primary'  : 'btn-outline-secondary' ?>">Assigned</a>
            <a href="history.php?status=in_progress" class="btn btn-sm <?= $filterStatus==='in_progress' ? 'btn-warning'  : 'btn-outline-secondary' ?>" style="<?= $filterStatus==='in_progress' ? 'background:var(--purple);border-color:var(--purple);color:#fff' : '' ?>">In Progress</a>
            <a href="history.php?status=completed"   class="btn btn-sm <?= $filterStatus==='completed'   ? 'btn-success'  : 'btn-outline-secondary' ?>">Completed</a>
            <a href="history.php?status=rejected"    class="btn btn-sm <?= $filterStatus==='rejected'    ? 'btn-danger'   : 'btn-outline-secondary' ?>">Rejected</a>
        </div>

        <div class="qr-form-card">
            <?php if ($complaints->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="qr-table">
                    <thead>
                        <tr>
                            <th>#ID</th>
                            <th>Title</th>
                            <th>Department</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Auto-Routed</th>
                            <th>Submitted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $complaints->fetch_assoc()): ?>
                        <tr>
                            <td><span class="fw-semibold text-primary">#<?= $row['id'] ?></span></td>
                            <td>
                                <a href="view_complaint.php?id=<?= $row['id'] ?>" class="text-dark fw-semibold">
                                    <?= htmlspecialchars(substr($row['title'], 0, 50)) ?><?= strlen($row['title']) > 50 ? '…' : '' ?>
                                </a>
                            </td>
                            <td><?= $row['dept_name'] ? htmlspecialchars($row['dept_name']) : '<span class="text-muted small">Unassigned</span>' ?></td>
                            <td><?= priorityBadge($row['priority']) ?></td>
                            <td><?= statusBadge($row['status']) ?></td>
                            <td>
                                <?php if ($row['auto_assigned']): ?>
                                    <span class="badge bg-primary-soft text-primary" data-bs-toggle="tooltip" title="Smart keyword routing">
                                        <i class="fas fa-magic me-1"></i>Auto
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted">Manual</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted small"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="view_complaint.php?id=<?= $row['id'] ?>"
                                       class="btn btn-outline-primary btn-sm" data-bs-toggle="tooltip" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <?php if ($row['status'] === 'completed' && !$row['has_feedback']): ?>
                                    <a href="feedback.php?id=<?= $row['id'] ?>"
                                       class="btn btn-warning btn-sm" data-bs-toggle="tooltip" title="Leave Feedback">
                                        <i class="fas fa-star"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-search fa-3x text-muted opacity-50 mb-3 d-block"></i>
                <p class="text-muted mb-3">
                    <?= $filterStatus ? "No complaints with status '$filterStatus' found." : "You haven't submitted any complaints yet." ?>
                </p>
                <a href="submit_complaint.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Submit a Complaint
                </a>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
