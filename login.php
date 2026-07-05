<?php

// login.php for All Roles


//admin, department, and user. it redirects to correct dashboard.

session_start();
require_once 'config/db_18.php';
require_once 'includes/auth_check.php';

// Redirect already-logged-in users to their dashboard
redirectIfLoggedIn();

$error = '';
$success = '';

// Check for message from redirect (e.g., after logout)
if (isset($_GET['msg'])) {
    $success = htmlspecialchars($_GET['msg']);
}

//  Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email    = sanitize($conn, $_POST['email']    ?? '');
    $password = $_POST['password'] ?? '';   // Do NOT sanitize password before hashing check

    // Basic validation
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        // Fetch user by email — check user exists and role
        $stmt = $conn->prepare("SELECT id, name, email, password, role, status, dept_id FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Check user credentials — verify password hash
            if (password_verify($password, $user['password'])) {

                // Check account status
                if ($user['status'] === 'blocked') {
                    $error = 'Your account has been blocked. Please contact admin.';
                } elseif ($user['status'] === 'pending') {
                    $error = 'Your account is pending admin approval. Please wait.';
                } else {
                    // ── Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['name']    = $user['name'];
                    $_SESSION['email']   = $user['email'];
                    $_SESSION['role']    = $user['role'];
                    $_SESSION['dept_id'] = $user['dept_id'];  // For department users

                    // ── Role-based redirection
                    if ($user['role'] === 'admin') {
                        redirect(SITE_URL . '/admin/dashboard.php');
                    } elseif ($user['role'] === 'department') {
                        redirect(SITE_URL . '/department/dashboard.php');
                    } else {
                        redirect(SITE_URL . '/user/dashboard.php');
                    }
                }
            } else {
                // Wrong password
                $error = 'Invalid email or password. Please try again.';
            }
        } else {
            // No user found with that email
            $error = 'Invalid email or password. Please try again.';
        }
        $stmt->close();
    }
}

$pageTitle = 'Login';
?>
<?php include 'includes/header.php'; ?>

<div class="auth-page">
    <div class="auth-card">

        <!-- Logo -->
        <div class="auth-logo">
            <i class="fas fa-bolt me-2 text-warning"></i>
            Quick<span>Resolve</span><span style="color:#888"></span>
        </div>
        <p class="auth-subtitle">Sign in to your account to continue</p>

        <!-- Error alert -->
        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show auto-dismiss mb-4" role="alert">
            <i class="fas fa-times-circle me-2"></i><?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Success / info message (e.g. after logout) -->
        <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show auto-dismiss mb-4" role="alert">
            <i class="fas fa-check-circle me-2"></i><?= $success ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" action="login.php" class="qr-validate" novalidate>

            <!-- Email field -->
            <div class="mb-4">
                <label for="email" class="form-label">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0" style="border-radius:10px 0 0 10px">
                        <i class="fas fa-envelope text-muted"></i>
                    </span>
                    <input type="email" class="form-control border-start-0 ps-0" id="email" name="email"
                           placeholder="you@example.com"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           style="border-radius:0 10px 10px 0" required>
                </div>
            </div>

            <!-- Password field -->
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0" style="border-radius:10px 0 0 10px">
                        <i class="fas fa-lock text-muted"></i>
                    </span>
                    <input type="password" class="form-control border-start-0 ps-0" id="loginPassword"
                           name="password" placeholder="Enter password"
                           style="border-radius:0 10px 10px 0" required>
                    <button type="button" class="btn btn-light border"
                            onclick="togglePass('loginPassword', this)"
                            style="border-radius:0 10px 10px 0">
                        <i class="fas fa-eye text-muted"></i>
                    </button>
                </div>
            </div>

            <!-- Forgot password link -->
            <div class="text-end mb-4">
                <a href="forgot_password.php" class="small text-primary fw-semibold">Forgot password?</a>
            </div>

            <!-- Submit button -->
            <button type="submit" class="btn btn-primary w-100 py-3 fw-semibold">
                <i class="fas fa-sign-in-alt me-2"></i>Sign In
            </button>
        </form>

        <!-- Register link -->
        <p class="text-center text-muted small mt-4 mb-0">
            Don't have an account?
            <a href="register.php" class="text-primary fw-semibold">Create one here</a>
        </p>

        <!-- Demo credentials hint 
        <div class="mt-4 p-3 rounded" style="background:#F8FAFC;border:1px dashed #CBD5E1">
            <p class="small fw-semibold text-muted mb-2"><i class="fas fa-info-circle me-1"></i>Demo Credentials:</p>
            <p class="small mb-1"><strong>Admin:</strong> admin@quickresolve.com / <code>password</code></p>
            <p class="small mb-1"><strong>Dept:</strong> electrical@quickresolve.com / <code>password</code></p>
            <p class="small mb-0"><strong>User:</strong> rahul@example.com / <code>password</code></p>
        </div>-->

    </div>
</div>

<script>
// Toggle password visibility
function togglePass(inputId, btn) {
    const inp = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (inp.type === 'password') {
        inp.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        inp.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>

<!-- Footer without full site footer -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= SITE_URL ?>/assets/js/script.js"></script>
</body>
</html>
