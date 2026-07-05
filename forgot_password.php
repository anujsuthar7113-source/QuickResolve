<?php
// Step 1: User enters email → token generated + stored
// Step 2: User enters new password with token from URL

session_start();
require_once 'config/db_18.php';
require_once 'includes/auth_check.php';

redirectIfLoggedIn();

$error   = '';
$success = '';
$step    = isset($_GET['token']) ? 2 : 1;   // Step 1 = request, Step 2 = reset

// ── Step 1: Request password reset 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_reset'])) {

    $email = sanitize($conn, $_POST['email'] ?? '');

    if (empty($email)) {
        $error = 'Please enter your email address.';
    } else {
        // Check user exists
        $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Generate a secure random token
            $token  = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Save token and expiry to database
            $update = $conn->prepare("UPDATE users SET reset_token = ?, token_expiry = ? WHERE id = ?");
            $update->bind_param('ssi', $token, $expiry, $user['id']);
            $update->execute();
            $update->close();

            // In a real app, email the link. For XAMPP, we show it on screen.
            $resetLink = SITE_URL . '/forgot_password.php?token=' . $token;
            $success = 'Reset link generated! (In production, this would be emailed.)<br>
                        <strong>Your reset link:</strong><br>
                        <a href="' . $resetLink . '" class="text-break">' . $resetLink . '</a>';
        } else {
            // Don't reveal if email exists — security best practice
            $success = 'If that email is registered, a reset link has been sent.';
        }
        $stmt->close();
    }
}

// ── Step 2: Reset password with token 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do_reset'])) {

    $token    = sanitize($conn, $_POST['token']    ?? '');
    $password = $_POST['new_password']     ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (empty($password) || empty($confirm)) {
        $error = 'Please fill in both password fields.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Validate token and expiry
        $now  = date('Y-m-d H:i:s');
        $stmt = $conn->prepare(
            "SELECT id FROM users WHERE reset_token = ? AND token_expiry > ?"
        );
        $stmt->bind_param('ss', $token, $now);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $hashedPass = password_hash($password, PASSWORD_DEFAULT);

            // Update password and clear token
            $upd = $conn->prepare(
                "UPDATE users SET password = ?, reset_token = NULL, token_expiry = NULL WHERE id = ?"
            );
            $upd->bind_param('si', $hashedPass, $user['id']);
            $upd->execute();
            $upd->close();

            $success = 'Password reset successfully! You can now log in with your new password.';
            $step = 0;  // Show only success message
        } else {
            $error = 'Invalid or expired reset token. Please request a new reset link.';
        }
        $stmt->close();
    }
}

$pageTitle = 'Forgot Password';
?>
<?php include 'includes/header.php'; ?>

<div class="auth-page">
    <div class="auth-card">

        <div class="auth-logo">
            <i class="fas fa-bolt me-2 text-warning"></i>
            Quick<span>Resolve</span><span style="color:#888">_18</span>
        </div>
        <p class="auth-subtitle">
            <?= $step === 1 ? 'Reset your password' : ($step === 2 ? 'Enter your new password' : '') ?>
        </p>

        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show auto-dismiss mb-4">
            <i class="fas fa-times-circle me-2"></i><?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="alert alert-success mb-4">
            <i class="fas fa-check-circle me-2"></i><?= $success ?>
        </div>
        <?php endif; ?>

        <?php if ($step === 1 && !$success): ?>
        <!-- Step 1: Enter email -->
        <form method="POST" action="forgot_password.php" class="qr-validate" novalidate>
            <div class="mb-4">
                <label for="email" class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0" style="border-radius:10px 0 0 10px">
                        <i class="fas fa-envelope text-muted"></i>
                    </span>
                    <input type="email" class="form-control border-start-0 ps-0" id="email" name="email"
                           placeholder="you@example.com"
                           style="border-radius:0 10px 10px 0" required>
                </div>
            </div>
            <button type="submit" name="request_reset" class="btn btn-primary w-100 py-3 fw-semibold">
                <i class="fas fa-paper-plane me-2"></i>Send Reset Link
            </button>
        </form>

        <?php elseif ($step === 2): ?>
        <!-- Step 2: Enter new password -->
        <form method="POST" action="forgot_password.php" class="qr-validate" novalidate>
            <input type="hidden" name="token" value="<?= htmlspecialchars($_GET['token']) ?>">

            <div class="mb-3">
                <label class="form-label">New Password</label>
                <input type="password" class="form-control" id="password" name="new_password"
                       placeholder="Min 6 characters" required>
                <div class="strength-bar-wrap mt-2">
                    <div class="strength-bar" id="strengthBar"></div>
                </div>
                <div class="small mt-1" id="strengthText"></div>
            </div>

            <div class="mb-4">
                <label class="form-label">Confirm New Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                       placeholder="Re-enter new password" required>
                <div id="matchFeedback" class="form-text"></div>
            </div>

            <button type="submit" name="do_reset" class="btn btn-primary w-100 py-3 fw-semibold">
                <i class="fas fa-key me-2"></i>Reset Password
            </button>
        </form>
        <?php endif; ?>

        <p class="text-center text-muted small mt-4 mb-0">
            Remember it?
            <a href="login.php" class="text-primary fw-semibold">Back to Login</a>
        </p>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= SITE_URL ?>/assets/js/script.js"></script>
</body>
</html>
