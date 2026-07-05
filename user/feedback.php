<?php
// user/feedback.php — Complaint Feedback & Rating

session_start();
require_once '../config/db_18.php';
require_once '../includes/auth_check.php';

requireRole('user');

$userId      = $_SESSION['user_id'];
$complaintId = (int)($_GET['id'] ?? 0);

if (!$complaintId) {
    redirect(SITE_URL . '/user/history.php');
}

// Verify complaint belongs to user and is completed
$stmt = $conn->prepare("SELECT id, title, status FROM complaints WHERE id = ? AND user_id = ?");
$stmt->bind_param('ii', $complaintId, $userId);
$stmt->execute();
$complaint = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$complaint) {
    setFlash('danger', 'Complaint not found.');
    redirect(SITE_URL . '/user/history.php');
}

if ($complaint['status'] !== 'completed') {
    setFlash('warning', 'Feedback can only be submitted for completed complaints.');
    redirect(SITE_URL . '/user/view_complaint.php?id=' . $complaintId);
}

// Check if feedback already exists
$existing = $conn->query("SELECT id FROM feedback WHERE complaint_id = $complaintId")->fetch_assoc();
if ($existing) {
    setFlash('info', 'You have already submitted feedback for this complaint.');
    redirect(SITE_URL . '/user/view_complaint.php?id=' . $complaintId);
}

$error = '';

// ── Process feedback form 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating   = (int)($_POST['rating']   ?? 0);
    $comments = sanitize($conn, $_POST['comments'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $error = 'Please select a rating between 1 and 5 stars.';
    } else {
        $stmt = $conn->prepare("INSERT INTO feedback (complaint_id, user_id, rating, comments) VALUES (?,?,?,?)");
        $stmt->bind_param('iiis', $complaintId, $userId, $rating, $comments);

        if ($stmt->execute()) {
            setFlash('success', 'Thank you for your feedback! It helps us improve.');
            redirect(SITE_URL . '/user/view_complaint.php?id=' . $complaintId);
        } else {
            $error = 'Failed to submit feedback. Please try again.';
        }
        $stmt->close();
    }
}

$pageTitle = 'Leave Feedback';
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
            <a href="view_complaint.php?id=<?= $complaintId ?>" class="btn btn-outline-secondary btn-sm mb-3">
                <i class="fas fa-arrow-left me-1"></i>Back to Complaint
            </a>
            <h1><i class="fas fa-star me-2 text-warning"></i>Leave Feedback</h1>
            <p class="text-muted mb-0">Share your experience for: <strong><?= htmlspecialchars($complaint['title']) ?></strong></p>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="qr-form-card">

                    <?php if ($error): ?>
                    <div class="alert alert-danger auto-dismiss"><?= $error ?></div>
                    <?php endif; ?>

                    <form method="POST" action="feedback.php?id=<?= $complaintId ?>" class="qr-validate" novalidate>

                        <!-- Star rating widget -->
                        <div class="mb-5 text-center">
                            <label class="form-label d-block mb-3 fs-5 fw-semibold">How would you rate the resolution?</label>

                            <div class="star-rating justify-content-center" style="font-size:2.5rem">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star star" data-value="<?= $i ?>"></i>
                                <?php endfor; ?>
                            </div>

                            <input type="hidden" id="ratingInput" name="rating" value="0" required>
                            <div id="ratingLabel" class="mt-2 fw-semibold text-warning"></div>
                        </div>

                        <!-- Comments -->
                        <div class="mb-4">
                            <label for="comments" class="form-label">Additional Comments (Optional)</label>
                            <textarea class="form-control" id="comments" name="comments" rows="4"
                                      placeholder="Share your thoughts about how the issue was handled..."><?= htmlspecialchars($_POST['comments'] ?? '') ?></textarea>
                        </div>

                        <button type="submit" class="btn btn-warning w-100 py-3 fw-bold">
                            <i class="fas fa-paper-plane me-2"></i>Submit Feedback
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
// Update rating label text
document.querySelectorAll('.star-rating .star').forEach((star, i) => {
    star.addEventListener('click', () => {
        const labels = ['', 'Very Unsatisfied 😞', 'Unsatisfied 🙁', 'Neutral 😐', 'Satisfied 😊', 'Very Satisfied 😄'];
        document.getElementById('ratingLabel').textContent = labels[i + 1];
    });
});
</script>

<?php include '../includes/footer.php'; ?>
