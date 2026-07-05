<?php
// ============================================================
// department/update_status.php — Update Complaint Status
// QuickResolve_18 – Smart Complaint Management System
// ============================================================
// Department staff can move complaints through the workflow:
// assigned → in_progress → completed
// ============================================================

session_start();
require_once '../config/db_18.php';
require_once '../includes/auth_check.php';

requireRole('department');

$deptId      = $_SESSION['dept_id'];
$complaintId = (int)($_GET['id'] ?? 0);

if (!$complaintId) redirect(SITE_URL . '/department/assigned_complaints.php');

// Verify complaint belongs to this department
$stmt = $conn->prepare("
    SELECT c.*, u.name AS user_name, u.email AS user_email
    FROM complaints c
    LEFT JOIN users u ON c.user_id = u.id
    WHERE c.id = ? AND c.dept_id = ?
");
$stmt->bind_param('ii', $complaintId, $deptId);
$stmt->execute();
$c = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$c) {
    setFlash('danger', 'Complaint not found or not assigned to your department.');
    redirect(SITE_URL . '/department/assigned_complaints.php');
}

// Fetch logs for timeline
$logs = $conn->query("
    SELECT cl.*, u.name AS changed_by_name
    FROM complaint_logs cl
    LEFT JOIN users u ON cl.changed_by = u.id
    WHERE cl.complaint_id = $complaintId
    ORDER BY cl.changed_at ASC
");

$error = '';

// ── Handle status update ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {

    $newStatus = sanitize($conn, $_POST['status']);
    $note      = sanitize($conn, $_POST['note'] ?? '');
    $deptUserId = $_SESSION['user_id'];

    // Department can only set: in_progress or completed
    $allowed = ['in_progress', 'completed'];
    if (!in_array($newStatus, $allowed)) {
        $error = 'Invalid status selection.';
    } else {
        $oldStatus = $c['status'];

        // Update complaint status
        $upd = $conn->prepare("UPDATE complaints SET status=? WHERE id=?");
        $upd->bind_param('si', $newStatus, $complaintId);
        $upd->execute();
        $upd->close();

        // Log the status change with note
        $logNote = trim($note) ?: 'Status updated by department';
        $log = $conn->prepare("INSERT INTO complaint_logs (complaint_id, changed_by, old_status, new_status, note) VALUES(?,?,?,?,?)");
        $log->bind_param('iisss', $complaintId, $deptUserId, $oldStatus, $newStatus, $logNote);
        $log->execute();
        $log->close();

        // If completed, auto-archive
        if ($newStatus === 'completed') {
            $archStmt = $conn->prepare("INSERT IGNORE INTO archive (complaint_id,user_id,dept_id,title,description,priority,final_status) VALUES(?,?,?,?,?,?,'completed')");
            $archStmt->bind_param('iiisss', $complaintId, $c['user_id'], $deptId, $c['title'], $c['description'], $c['priority']);
            $archStmt->execute();
            $archStmt->close();
        }

        setFlash('success', "Complaint #$complaintId updated to " . ucfirst(str_replace('_',' ',$newStatus)));
        redirect(SITE_URL . '/department/update_status.php?id=' . $complaintId);
    }
}

$pageTitle = 'Update Complaint #' . $complaintId;
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<div class="dashboard-layout">
    <aside class="qr-sidebar">
        <div class="sidebar-user-card">
            <div class="d-flex align-items-center gap-2">
                <div style="width:38px;height:38px;border-radius:50%;background:linear-gradient(135deg,#8B5CF6,#6366F1);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700">D</div>
                <div>
                    <div class="text-white fw-semibold small"><?= htmlspecialchars($_SESSION['name']) ?></div>
                    <div style="color:rgba(255,255,255,0.45);font-size:0.75rem">Department</div>
                </div>
            </div>
        </div>
        <div class="sidebar-section-label">My Department</div>
        <a href="dashboard.php"           class="sidebar-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="assigned_complaints.php" class="sidebar-link"><i class="fas fa-clipboard-list"></i> All Complaints</a>
        <a href="filter.php"              class="sidebar-link"><i class="fas fa-filter"></i> Filter</a>
        <div class="sidebar-section-label">Account</div>
        <a href="../logout.php" class="sidebar-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </aside>

    <main class="qr-main-content">
        <div class="page-header">
            <a href="assigned_complaints.php" class="btn btn-outline-secondary btn-sm mb-3">
                <i class="fas fa-arrow-left me-1"></i>Back to Complaints
            </a>
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <h1 class="mb-0">Complaint #<?= $complaintId ?></h1>
                <?= statusBadge($c['status']) ?>
                <?= priorityBadge($c['priority']) ?>
            </div>
        </div>

        <?php showFlash(); ?>
        <?php if ($error): ?>
        <div class="alert alert-danger auto-dismiss"><?= $error ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Complaint detail left -->
            <div class="col-lg-7">
                <div class="qr-form-card mb-4">
                    <h5 class="fw-bold mb-4"><?= htmlspecialchars($c['title']) ?></h5>

                    <div class="row g-3 mb-4">
                        <div class="col-sm-6">
                            <div class="detail-label">Reported By</div>
                            <div class="detail-value"><?= htmlspecialchars($c['user_name']) ?></div>
                        </div>
                        <div class="col-sm-6">
                            <div class="detail-label">Submitted</div>
                            <div class="detail-value"><?= date('d M Y, h:i A', strtotime($c['created_at'])) ?></div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="detail-label mb-2">Description</div>
                        <div class="p-3 rounded" style="background:var(--light-bg);border:1px solid var(--light-border);line-height:1.7">
                            <?= nl2br(htmlspecialchars($c['description'])) ?>
                        </div>
                    </div>

                    <?php if ($c['image_path']): ?>
                    <div class="mb-4">
                        <div class="detail-label mb-2">Attached Photo</div>
                        <img src="<?= SITE_URL ?>/uploads/<?= htmlspecialchars($c['image_path']) ?>"
                             alt="Complaint Image"
                             class="img-fluid rounded shadow-sm"
                             style="max-height:280px;object-fit:cover">
                    </div>
                    <?php endif; ?>

                    <?php if ($c['admin_note']): ?>
                    <div class="alert alert-info py-2">
                        <small><strong><i class="fas fa-user-shield me-1"></i>Admin Note:</strong>
                        <?= htmlspecialchars($c['admin_note']) ?></small>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Status Timeline -->
                <div class="qr-form-card">
                    <h6 class="fw-bold mb-4"><i class="fas fa-history me-2 text-primary"></i>Status History</h6>
                    <?php while ($log = $logs->fetch_assoc()): ?>
                    <div class="timeline-item">
                        <div class="timeline-dot" style="background:var(--primary)">
                            <i class="fas fa-circle-dot" style="font-size:0.65rem"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="small fw-semibold">
                                <?= $log['old_status'] ? statusBadge($log['old_status']) . '<i class="fas fa-arrow-right mx-2 text-muted" style="font-size:0.7rem"></i>' : '' ?>
                                <?= statusBadge($log['new_status']) ?>
                            </div>
                            <?php if ($log['note']): ?>
                            <div class="text-muted small mt-1"><?= htmlspecialchars($log['note']) ?></div>
                            <?php endif; ?>
                            <div class="text-muted" style="font-size:0.75rem;margin-top:4px">
                                <?= date('d M Y, h:i A', strtotime($log['changed_at'])) ?>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>

            <!-- Update status panel right -->
            <div class="col-lg-5">
                <?php if ($c['status'] !== 'completed' && $c['status'] !== 'rejected'): ?>
                <div class="qr-form-card">
                    <h6 class="fw-bold mb-4"><i class="fas fa-edit me-2 text-primary"></i>Update Status</h6>

                    <form method="POST" action="update_status.php?id=<?= $complaintId ?>" class="qr-validate" novalidate>

                        <div class="mb-4">
                            <label class="form-label">New Status <span class="text-danger">*</span></label>
                            <div class="d-flex flex-column gap-3">

                                <!-- Only allow forward movement -->
                                <?php if (in_array($c['status'], ['assigned'])): ?>
                                <label class="d-flex align-items-center gap-3 p-3 rounded cursor-pointer"
                                       style="border:2px solid var(--purple);background:var(--bg-purple-soft,#F5F3FF);cursor:pointer">
                                    <input type="radio" name="status" value="in_progress" class="form-check-input mt-0 flex-shrink-0">
                                    <div>
                                        <div class="fw-semibold">Mark as In Progress</div>
                                        <div class="small text-muted">You have started working on this complaint</div>
                                    </div>
                                </label>
                                <?php endif; ?>

                                <?php if (in_array($c['status'], ['assigned','in_progress'])): ?>
                                <label class="d-flex align-items-center gap-3 p-3 rounded"
                                       style="border:2px solid var(--success);background:#ECFDF5;cursor:pointer">
                                    <input type="radio" name="status" value="completed" class="form-check-input mt-0 flex-shrink-0">
                                    <div>
                                        <div class="fw-semibold text-success">Mark as Completed</div>
                                        <div class="small text-muted">The issue has been fully resolved</div>
                                    </div>
                                </label>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Resolution note -->
                        <div class="mb-4">
                            <label class="form-label">Resolution Note <span class="text-muted">(Recommended)</span></label>
                            <textarea class="form-control" name="note" rows="4"
                                      placeholder="Describe what action was taken to resolve this complaint..."><?= htmlspecialchars($_POST['note'] ?? '') ?></textarea>
                        </div>

                        <button type="submit" name="update" class="btn btn-primary w-100 py-3 fw-semibold">
                            <i class="fas fa-save me-2"></i>Save Status Update
                        </button>
                    </form>
                </div>
                <?php else: ?>
                <div class="qr-form-card text-center">
                    <i class="fas fa-check-circle fa-3x text-success mb-3 d-block"></i>
                    <h6 class="fw-bold text-success">Complaint Completed</h6>
                    <p class="text-muted small">This complaint has been resolved. No further updates needed.</p>
                    <a href="assigned_complaints.php" class="btn btn-outline-success btn-sm">Back to List</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
