<?php
// user/view_complaint.php — Single Complaint Details


session_start();
require_once '../config/db_18.php';
require_once '../includes/auth_check.php';

requireRole('user');

$userId      = $_SESSION['user_id'];
$complaintId = (int)($_GET['id'] ?? 0);

if (!$complaintId) {
    setFlash('danger', 'Invalid complaint ID.');
    redirect(SITE_URL . '/user/history.php');
}

// Fetch complaint — ensure it belongs to this user
$stmt = $conn->prepare("
    SELECT c.*, d.name AS dept_name, u.name AS user_name
    FROM complaints c
    LEFT JOIN departments d ON c.dept_id = d.id
    LEFT JOIN users u ON c.user_id = u.id
    WHERE c.id = ? AND c.user_id = ?
");
$stmt->bind_param('ii', $complaintId, $userId);
$stmt->execute();
$c = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$c) {
    setFlash('danger', 'Complaint not found or access denied.');
    redirect(SITE_URL . '/user/history.php');
}

// Fetch status change logs for timeline
$logs = $conn->query("
    SELECT cl.*, u.name AS changed_by_name, u.role AS changer_role
    FROM complaint_logs cl
    LEFT JOIN users u ON cl.changed_by = u.id
    WHERE cl.complaint_id = $complaintId
    ORDER BY cl.changed_at ASC
");

// Fetch feedback if any
$feedback = $conn->query("SELECT * FROM feedback WHERE complaint_id = $complaintId")->fetch_assoc();

$pageTitle = 'Complaint #' . $complaintId;
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
        <a href="history.php"          class="sidebar-link"><i class="fas fa-history"></i> My Complaints</a>
        <div class="sidebar-section-label">Account</div>
        <a href="../logout.php" class="sidebar-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </aside>

    <main class="qr-main-content">
        <div class="page-header">
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <a href="history.php" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left me-1"></i>Back
                </a>
                <div>
                    <h1 class="mb-0">Complaint #<?= $complaintId ?></h1>
                    <p class="text-muted mb-0 small">Submitted <?= date('d M Y, h:i A', strtotime($c['created_at'])) ?></p>
                </div>
                <?= statusBadge($c['status']) ?>
            </div>
        </div>

        <?php showFlash(); ?>

        <div class="row g-4">
            <!-- Complaint details -->
            <div class="col-lg-8">
                <div class="qr-form-card mb-4">
                    <h5 class="fw-bold mb-4"><?= htmlspecialchars($c['title']) ?></h5>

                    <div class="row g-3 mb-4">
                        <div class="col-sm-4">
                            <div class="detail-label">Status</div>
                            <div><?= statusBadge($c['status']) ?></div>
                        </div>
                        <div class="col-sm-4">
                            <div class="detail-label">Priority</div>
                            <div><?= priorityBadge($c['priority']) ?></div>
                        </div>
                        <div class="col-sm-4">
                            <div class="detail-label">Department</div>
                            <div class="detail-value"><?= $c['dept_name'] ? htmlspecialchars($c['dept_name']) : '<em class="text-muted">Unassigned</em>' ?></div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="detail-label mb-2">Description</div>
                        <div class="p-3 rounded" style="background:var(--light-bg);border:1px solid var(--light-border)">
                            <?= nl2br(htmlspecialchars($c['description'])) ?>
                        </div>
                    </div>

                    <?php if ($c['image_path']): ?>
                    <div class="mb-4">
                        <div class="detail-label mb-2">Attached Photo</div>
                        <img src="<?= SITE_URL ?>/uploads/<?= htmlspecialchars($c['image_path']) ?>"
                             alt="Complaint Image"
                             class="img-fluid rounded shadow-sm"
                             style="max-height:300px;object-fit:cover">
                    </div>
                    <?php endif; ?>

                    <?php if ($c['admin_note']): ?>
                    <div class="alert alert-info">
                        <div class="fw-semibold mb-1"><i class="fas fa-user-shield me-2"></i>Admin Note</div>
                        <?= nl2br(htmlspecialchars($c['admin_note'])) ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($c['auto_assigned']): ?>
                    <div class="alert alert-primary py-2">
                        <i class="fas fa-magic me-2"></i>
                        This complaint was <strong>automatically routed</strong> using smart keyword detection.
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Status Timeline -->
                <div class="qr-form-card mb-4">
                    <h6 class="fw-bold mb-4"><i class="fas fa-history me-2 text-primary"></i>Status Timeline</h6>
                    <?php if ($logs->num_rows > 0): ?>
                    <?php while ($log = $logs->fetch_assoc()): ?>
                    <div class="timeline-item">
                        <div class="timeline-dot" style="background:var(--primary)">
                            <i class="fas fa-circle-dot" style="font-size:0.7rem"></i>
                        </div>
                        <div class="flex-grow-1 pb-1">
                            <div class="fw-semibold small">
                                <?= $log['old_status'] ? statusBadge($log['old_status']) . ' <i class="fas fa-arrow-right mx-2 text-muted small"></i>' : '' ?>
                                <?= statusBadge($log['new_status']) ?>
                            </div>
                            <?php if ($log['note']): ?>
                            <div class="text-muted small mt-1"><?= htmlspecialchars($log['note']) ?></div>
                            <?php endif; ?>
                            <div class="text-muted" style="font-size:0.78rem;margin-top:4px">
                                By <?= htmlspecialchars($log['changed_by_name'] ?? 'System') ?> ·
                                <?= date('d M Y, h:i A', strtotime($log['changed_at'])) ?>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                    <?php else: ?>
                    <p class="text-muted small">No status changes recorded yet.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right panel -->
            <div class="col-lg-4">
                <!-- Feedback section -->
                <?php if ($c['status'] === 'completed'): ?>
                <div class="qr-form-card mb-4">
                    <h6 class="fw-bold mb-3"><i class="fas fa-star me-2 text-warning"></i>Your Feedback</h6>
                    <?php if ($feedback): ?>
                    <div class="mb-2">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="fas fa-star <?= $i <= $feedback['rating'] ? 'text-warning' : 'text-muted' ?>"></i>
                        <?php endfor; ?>
                        <span class="ms-2 fw-semibold"><?= $feedback['rating'] ?>/5</span>
                    </div>
                    <?php if ($feedback['comments']): ?>
                    <p class="text-muted small mt-2">"<?= htmlspecialchars($feedback['comments']) ?>"</p>
                    <?php endif; ?>
                    <div class="badge bg-success-soft text-success">Feedback submitted</div>
                    <?php else: ?>
                    <p class="text-muted small mb-3">This complaint has been resolved! Please share your experience.</p>
                    <a href="feedback.php?id=<?= $complaintId ?>" class="btn btn-warning w-100">
                        <i class="fas fa-star me-2"></i>Leave Feedback
                    </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Complaint info card -->
                <div class="qr-form-card">
                    <h6 class="fw-bold mb-3"><i class="fas fa-info-circle me-2 text-primary"></i>Complaint Info</h6>
                    <div class="mb-3">
                        <div class="detail-label">Submitted On</div>
                        <div class="detail-value"><?= date('d M Y, h:i A', strtotime($c['created_at'])) ?></div>
                    </div>
                    <div class="mb-3">
                        <div class="detail-label">Last Updated</div>
                        <div class="detail-value"><?= date('d M Y, h:i A', strtotime($c['updated_at'])) ?></div>
                    </div>
                    <div class="mb-3">
                        <div class="detail-label">Routing Method</div>
                        <div class="detail-value">
                            <?= $c['auto_assigned'] ? '<span class="badge badge-purple"><i class="fas fa-magic me-1"></i>Auto (Smart)</span>' : '<span class="badge bg-secondary">Manual by Admin</span>' ?>
                        </div>
                    </div>
                    <div>
                        <div class="detail-label">Complaint ID</div>
                        <div class="detail-value fw-bold text-primary">#<?= $c['id'] ?></div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
