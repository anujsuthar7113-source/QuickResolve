<?php
// register.php — New User Registration
// Public registration for regular users only.
// Accounts are set to 'pending' — admin must approve.


session_start();
require_once 'config/db_18.php';
require_once 'includes/auth_check.php';

// Redirect logged-in users away from register
redirectIfLoggedIn();

$error   = '';
$success = '';

// ── Process registration form 
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name     = sanitize($conn, $_POST['name']     ?? '');
    $email    = sanitize($conn, $_POST['email']    ?? '');
    $password = $_POST['password']         ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    // ── Server-side validation 
    if (empty($name) || empty($email) || empty($password) || empty($confirm)) {
        $error = 'All fields are required.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';

    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';

    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';

    } else {
        // Check if email already exists in the database
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param('s', $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = 'An account with this email already exists. Try logging in.';
        } else {
            // Hash the password securely
            $hashedPass = password_hash($password, PASSWORD_DEFAULT);

            // Insert user with role=user, status=pending (awaits admin approval)
            $insert = $conn->prepare(
                "INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, 'user', 'pending')"
            );
            $insert->bind_param('sss', $name, $email, $hashedPass);

            if ($insert->execute()) {
                $success = 'Account created! Please wait for admin approval before logging in.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
            $insert->close();
        }
        $check->close();
    }
}

$pageTitle = 'Register';
?>
<?php include 'includes/header.php'; ?>

<div class="auth-page">
    <div class="auth-card" style="max-width:500px">

        <!-- Logo -->
        <div class="auth-logo">
            <i class="fas fa-bolt me-2 text-warning"></i>
            Quick<span>Resolve</span><span style="color:#888"></span>
        </div>
        <p class="auth-subtitle">Create your free account to get started</p>

        <!-- Error alert -->
        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show auto-dismiss mb-4">
            <i class="fas fa-times-circle me-2"></i><?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Success alert -->
        <?php if ($success): ?>
        <div class="alert alert-success mb-4">
            <i class="fas fa-check-circle me-2"></i><?= $success ?>
            <div class="mt-2">
                <a href="login.php" class="btn btn-success btn-sm">Go to Login</a>
            </div>
        </div>
        <?php else: ?>

        <!-- Registration Form -->
        <form method="POST" action="register.php" class="qr-validate" novalidate>

            <!-- Full Name -->
            <div class="mb-3">
                <label for="name" class="form-label">Full Name</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0" style="border-radius:10px 0 0 10px">
                        <i class="fas fa-user text-muted"></i>
                    </span>
                    <input type="text" class="form-control border-start-0 ps-0" id="name" name="name"
                           placeholder="John Doe"
                           value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                           style="border-radius:0 10px 10px 0" required>
                </div>
            </div>

            <!-- Email Address -->
            <div class="mb-3">
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

            <!-- Password -->
            <div class="mb-2">
                <label for="password" class="form-label">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0" style="border-radius:10px 0 0 10px">
                        <i class="fas fa-lock text-muted"></i>
                    </span>
                    <input type="password" class="form-control border-start-0 ps-0" id="password"
                           name="password" placeholder="Min 6 characters"
                           style="border-radius:0 10px 10px 0" required>
                    <button type="button" class="btn btn-light border"
                            onclick="togglePass('password',this)"
                            style="border-radius:0 10px 10px 0">
                        <i class="fas fa-eye text-muted"></i>
                    </button>
                </div>
                <!-- Password strength bar -->
                <div class="strength-bar-wrap mt-2">
                    <div class="strength-bar" id="strengthBar"></div>
                </div>
                <div class="small mt-1" id="strengthText" style="color:var(--mid)"></div>
            </div>

            <!-- Confirm Password -->
            <div class="mb-4">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0" style="border-radius:10px 0 0 10px">
                        <i class="fas fa-lock text-muted"></i>
                    </span>
                    <input type="password" class="form-control border-start-0 ps-0" id="confirm_password"
                           name="confirm_password" placeholder="Re-enter password"
                           style="border-radius:0 10px 10px 0" required>
                </div>
                <div id="matchFeedback" class="form-text"></div>
            </div>

            <!-- Info note -->
            <div class="alert alert-info py-2 px-3 small mb-4">
                <i class="fas fa-info-circle me-2"></i>
                Your account will need <strong>admin approval</strong> before you can log in.
            </div>

            <!-- Submit button -->
            <button type="submit" class="btn btn-primary w-100 py-3 fw-semibold">
                <i class="fas fa-user-plus me-2"></i>Create Account
            </button>
        </form>
        <?php endif; ?>

        <!-- Login link -->
        <p class="text-center text-muted small mt-4 mb-0">
            Already have an account?
            <a href="login.php" class="text-primary fw-semibold">Sign in here</a>
        </p>

    </div>
</div>

<script>
function togglePass(inputId, btn) {
    const inp  = document.getElementById(inputId);
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= SITE_URL ?>/assets/js/script.js"></script>
</body>
</html>
