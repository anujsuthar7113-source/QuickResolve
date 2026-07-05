<?php
// user/submit_complaint.php — Complaint Submission
// Users submit complaints here. The system reads keywords in
// description and auto-assigns to the correct department.

session_start();
require_once '../config/db_18.php';
require_once '../includes/auth_check.php';

requireRole('user');

$userId = $_SESSION['user_id'];
$error  = '';
$success = '';

// ── Process form submission ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $title       = sanitize($conn, $_POST['title']       ?? '');
    $description = sanitize($conn, $_POST['description'] ?? '');
    $priority    = sanitize($conn, $_POST['priority']    ?? 'medium');

    // Validate required fields
    if (empty($title) || empty($description)) {
        $error = 'Title and description are required.';
    } elseif (!in_array($priority, ['low','medium','high','critical'])) {
        $error = 'Invalid priority selected.';
    } else {

        // ── Smart routing logic ───────────────────────────────
        // Detect keywords in description to auto-assign department
        $autoAssignedDeptId = smartRoute($description, $conn);
        $autoAssigned       = $autoAssignedDeptId ? 1 : 0;

        // Set complaint status based on routing
        $status = $autoAssignedDeptId ? 'assigned' : 'pending';

        // ── Handle image upload ───────────────────────────────
        $imagePath = null;
        if (isset($_FILES['complaint_image']) && $_FILES['complaint_image']['error'] === UPLOAD_ERR_OK) {
            $file     = $_FILES['complaint_image'];
            $allowed  = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $maxSize  = 5 * 1024 * 1024; // 5 MB

            if (!in_array($file['type'], $allowed)) {
                $error = 'Only JPG, PNG, GIF, or WEBP images are allowed.';
            } elseif ($file['size'] > $maxSize) {
                $error = 'Image must be smaller than 5 MB.';
            } else {
                // Generate unique filename and move to uploads folder
                $ext       = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename  = 'complaint_' . $userId . '_' . time() . '.' . $ext;
                $destPath  = UPLOAD_DIR . $filename;

                if (move_uploaded_file($file['tmp_name'], $destPath)) {
                    $imagePath = $filename;
                } else {
                    $error = 'Failed to upload image. Check uploads/ folder permissions.';
                }
            }
        }

        // Insert complaint into database if no upload error
        if (empty($error)) {
            $stmt = $conn->prepare("
                INSERT INTO complaints (user_id, dept_id, title, description, priority, status, image_path, auto_assigned)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param('iisssssi',
                $userId, $autoAssignedDeptId, $title, $description,
                $priority, $status, $imagePath, $autoAssigned
            );

            if ($stmt->execute()) {
                $newId = $conn->insert_id;

                // Log the status creation
                $log = $conn->prepare("INSERT INTO complaint_logs (complaint_id, changed_by, old_status, new_status, note) VALUES (?,?,NULL,?,?)");
                $logNote = $autoAssigned ? 'Auto-routed by smart keyword detection' : 'Submitted — awaiting admin assignment';
                $log->bind_param('iiss', $newId, $userId, $status, $logNote);
                $log->execute();
                $log->close();

                setFlash('success', 'Your complaint has been submitted successfully!' .
                    ($autoAssigned ? ' It has been auto-routed to the correct department.' : ' Admin will review and assign it.'));
                redirect(SITE_URL . '/user/view_complaint.php?id=' . $newId);
            } else {
                $error = 'Failed to submit complaint. Please try again.';
            }
            $stmt->close();
        }
    }
}

$pageTitle = 'Submit Complaint';
?>
<?php include '../includes/header.php'; ?>
<?php include '../includes/navbar.php'; ?>

<div class="dashboard-layout">
    <!-- Sidebar -->
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
        <a href="submit_complaint.php" class="sidebar-link active"><i class="fas fa-plus-circle"></i> Submit Complaint</a>
        <a href="history.php"          class="sidebar-link"><i class="fas fa-history"></i> My Complaints</a>
        <div class="sidebar-section-label">Account</div>
        <a href="../logout.php" class="sidebar-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </aside>

    <main class="qr-main-content">
        <div class="page-header">
            <h1><i class="fas fa-plus-circle me-2 text-primary"></i>Submit a Complaint</h1>
            <p class="text-muted mb-0">Describe your issue clearly — our smart engine will route it to the right team.</p>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-danger auto-dismiss"><i class="fas fa-exclamation-circle me-2"></i><?= $error ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="qr-form-card">
                    <form method="POST" action="submit_complaint.php" enctype="multipart/form-data" class="qr-validate" novalidate>

                        <!-- Complaint Title -->
                        <div class="mb-4">
                            <label for="title" class="form-label">
                                <i class="fas fa-heading me-1 text-primary"></i>Complaint Title <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="title" name="title"
                                   placeholder="e.g., Street light not working in Block B"
                                   value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
                                   maxlength="200" required>
                            <div class="form-text text-muted">Keep it short and descriptive (max 200 chars)</div>
                        </div>

                        <!-- Description with smart routing hint -->
                        <div class="mb-4">
                            <label for="description" class="form-label">
                                <i class="fas fa-align-left me-1 text-primary"></i>Detailed Description <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" id="description" name="description"
                                      rows="5" placeholder="Describe the issue in detail — include location, duration, and any relevant details..."
                                      required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                            <!-- Smart routing indicator shown by JS -->
                            <div id="routeHint" class="mt-2 small text-muted">
                                <i class="fas fa-magic me-2"></i>Type keywords to see smart department routing...
                            </div>
                        </div>

                        <!-- Priority -->
                        <div class="mb-4">
                            <label for="priority" class="form-label">
                                <i class="fas fa-flag me-1 text-primary"></i>Priority Level <span class="text-danger">*</span>
                            </label>
                            <select class="form-select" id="priority" name="priority" required>
                                <option value="low"      <?= ($_POST['priority']??'') === 'low'      ? 'selected':'' ?>>🟢 Low — Minor inconvenience</option>
                                <option value="medium"   <?= ($_POST['priority']??'medium') === 'medium'   ? 'selected':'' ?>>🟡 Medium — Needs attention soon</option>
                                <option value="high"     <?= ($_POST['priority']??'') === 'high'     ? 'selected':'' ?>>🟠 High — Urgent issue</option>
                                <option value="critical" <?= ($_POST['priority']??'') === 'critical' ? 'selected':'' ?>>🔴 Critical — Immediate action required</option>
                            </select>
                        </div>

                        <!-- Image Upload -->
                        <div class="mb-4">
                            <label for="complaint_image" class="form-label">
                                <i class="fas fa-image me-1 text-primary"></i>Attach Photo (Optional)
                            </label>
                            <input type="file" class="form-control" id="complaint_image"
                                   name="complaint_image" accept="image/*">
                            <div class="form-text text-muted">JPG, PNG, GIF or WEBP — max 5 MB</div>
                            <!-- Image preview (shown by JS after file selected) -->
                            <img id="imgPreview" class="img-preview" src="" alt="Preview">
                        </div>

                        <!-- Submit buttons -->
                        <div class="d-flex gap-3">
                            <button type="submit" class="btn btn-primary px-5 py-2 fw-semibold">
                                <i class="fas fa-paper-plane me-2"></i>Submit Complaint
                            </button>
                            <a href="dashboard.php" class="btn btn-outline-secondary px-4">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Info sidebar -->
            <div class="col-lg-4">
                <!-- Smart routing info card -->
                <div class="feature-card mb-4">
                    <div class="feature-icon-wrap bg-primary-soft">
                        <i class="fas fa-magic text-primary"></i>
                    </div>
                    <h6 class="fw-bold mb-3">Smart Auto-Routing</h6>
                    <p class="text-muted small mb-3">
                        Our system reads your description and automatically routes your complaint to the right department.
                    </p>
                    <div class="small">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-primary-soft text-primary">light, power</span>
                            <span class="text-muted">→ Electrical</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-primary-soft text-primary">water, pipe</span>
                            <span class="text-muted">→ Plumbing</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-primary-soft text-primary">clean, trash</span>
                            <span class="text-muted">→ Housekeeping</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-primary-soft text-primary">wifi, internet</span>
                            <span class="text-muted">→ IT Support</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge bg-primary-soft text-primary">repair, broken</span>
                            <span class="text-muted">→ Maintenance</span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary-soft text-primary">security, cctv</span>
                            <span class="text-muted">→ Security</span>
                        </div>
                    </div>
                </div>

                <!-- Tips card -->
                <div class="feature-card">
                    <div class="feature-icon-wrap bg-warning-soft">
                        <i class="fas fa-lightbulb text-warning"></i>
                    </div>
                    <h6 class="fw-bold mb-3">Tips for a Good Complaint</h6>
                    <ul class="small text-muted mb-0 ps-3">
                        <li class="mb-2">Be specific about the location</li>
                        <li class="mb-2">Mention how long the issue has existed</li>
                        <li class="mb-2">Attach a photo if possible</li>
                        <li class="mb-2">Choose the right priority level</li>
                        <li>Use keywords for faster routing</li>
                    </ul>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
