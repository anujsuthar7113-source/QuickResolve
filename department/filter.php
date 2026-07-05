<?php
// ============================================================
// department/filter.php — Department Complaint Filter
// QuickResolve_18 – Smart Complaint Management System
// ============================================================

session_start();
require_once '../config/db_18.php';
require_once '../includes/auth_check.php';

requireRole('department');

$deptId = $_SESSION['dept_id'];
$dept   = $conn->query("SELECT name FROM departments WHERE id=$deptId")->fetch_assoc();

// ── Filter logic ──────────────────────────────────────────────
$status   = sanitize($conn, $_GET['status']   ?? '');
$priority = sanitize($conn, $_GET['priority'] ?? '');
$q        = sanitize($conn, $_GET['q']        ?? '');
$dateFrom = sanitize($conn, $_GET['from']     ?? '');
$dateTo   = sanitize($conn, $_GET['to']       ?? '');

$where = "WHERE c.dept_id = $deptId";
if ($status)   $where .= " AND c.status='$status'";
if ($priority) $where .= " AND c.priority='$priority'";
if ($q)        $where .= " AND (c.title LIKE '%$q%' OR c.description LIKE '%$q%')";
if ($dateFrom) $where .= " AND DATE(c.created_at) >= '$dateFrom'";
if ($dateTo)   $where .= " AND DATE(c.created_at) <= '$dateTo'";

$searched = isset($_GET['q']) || $status || $priority || $dateFrom;

$results = $searched ? $conn->query("
    SELECT c.id, c.title, c.priority, c.status, c.created_at, u.name AS user_name
    FROM complaints c
    LEFT JOIN users u ON c.user_id = u.id
    $where
    ORDER BY c.created_at DESC
") : null;

$pageTitle = 'Filter Complaints';
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
        <a href="dashboard.php"           class="sidebar-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
        <a href="assigned_complaints.php" class="sidebar-link"><i class="fas fa-clipboard-list"></i> All Complaints</a>
        <a href="filter.php"              class="sidebar-link active"><i class="fas fa-filter"></i> Filter</a>
        <div class="sidebar-section-label">Account</div>
        <a href="../logout.php" class="sidebar-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </aside>

    <main class="qr-main-content">
        <div class="page-header">
            <h1><i class="fas fa-filter me-2 text-primary"></i>Filter Complaints</h1>
            <p class="text-muted mb-0"><?= htmlspecialchars($dept['name']) ?> department</p>
        </div>

        <!-- Filter form -->
        <div class="qr-form-card mb-4">
            <form method="GET">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Search</label>
                        <input type="text" class="form-control" name="q"
                               placeholder="Search title or description..."
                               value="<?= htmlspecialchars($q) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Status</label>
                        <select class="form-select" name="status">
                            <option value="">All</option>
                            <?php foreach (['assigned','in_progress','completed'] as $s): ?>
                            <option value="<?= $s ?>" <?= $status===$s?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">Priority</label>
                        <select class="form-select" name="priority">
                            <option value="">All</option>
                            <?php foreach (['critical','high','medium','low'] as $p): ?>
                            <option value="<?= $p ?>" <?= $priority===$p?'selected':'' ?>><?= ucfirst($p) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-semibold">From Date</label>
                        <input type="date" class="form-control" name="from" value="<?= $dateFrom ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end gap-1">
                        <button type="submit" class="btn btn-primary flex-grow-1">
                            <i class="fas fa-search me-1"></i>Search
                        </button>
                        <a href="filter.php" class="btn btn-outline-secondary"><i class="fas fa-times"></i></a>
                    </div>
                </div>
            </form>
        </div>

        <?php if ($searched && $results): ?>
        <div class="qr-form-card">
            <h6 class="fw-bold mb-4"><?= $results->num_rows ?> Result(s)</h6>
            <?php if ($results->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="qr-table">
                    <thead>
                        <tr><th>#ID</th><th>Title</th><th>User</th><th>Priority</th><th>Status</th><th>Date</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                    <?php while ($row = $results->fetch_assoc()): ?>
                    <tr>
                        <td><span class="fw-semibold text-primary">#<?= $row['id'] ?></span></td>
                        <td class="small fw-semibold"><?= htmlspecialchars(substr($row['title'],0,50)) ?>…</td>
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
            <div class="text-center py-5 text-muted">
                <i class="fas fa-search fa-2x mb-3 d-block opacity-40"></i>No results found.
            </div>
            <?php endif; ?>
        </div>
        <?php elseif (!$searched): ?>
        <div class="text-center py-5 text-muted">
            <i class="fas fa-filter fa-3x opacity-30 mb-3 d-block"></i>
            <p>Use the filters above to search your department's complaints.</p>
        </div>
        <?php endif; ?>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
