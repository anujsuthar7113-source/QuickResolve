<?php
// Include this at the top of any protected page.
// Usage: require_once '../includes/auth_check.php';
//        requireRole('admin');   // or 'user' or 'department'

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Check: Is the user logged in? 
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// ── Enforce login + role check 
// Accepts single role string or array of allowed roles
function requireRole($roles) {
    // Redirect to login if no session
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . '/login.php?msg=Please+login+to+continue');
        exit();
    }

    // Normalise to array so we can check multiple allowed roles
    if (is_string($roles)) {
        $roles = [$roles];
    }

    // Block if user's role is not in the allowed list
    if (!in_array($_SESSION['role'], $roles)) {
        // Redirect to their own dashboard instead of showing 403
        $dashMap = [
            'admin'      => SITE_URL . '/admin/dashboard.php',
            'department' => SITE_URL . '/department/dashboard.php',
            'user'       => SITE_URL . '/user/dashboard.php',
        ];
        $dest = $dashMap[$_SESSION['role']] ?? SITE_URL . '/login.php';
        header('Location: ' . $dest . '?msg=Access+Denied');
        exit();
    }
}

// ── Redirect logged-in users away from guest pages 
// Use on login.php / register.php to avoid re-login
function redirectIfLoggedIn() {
    if (!isLoggedIn()) return;
    $dashMap = [
        'admin'      => SITE_URL . '/admin/dashboard.php',
        'department' => SITE_URL . '/department/dashboard.php',
        'user'       => SITE_URL . '/user/dashboard.php',
    ];
    $dest = $dashMap[$_SESSION['role']] ?? SITE_URL . '/index.php';
    header('Location: ' . $dest);
    exit();
}

// ── Smart keyword → department routing 
// Returns dept_id or NULL (admin manually assigns if NULL)
function smartRoute($description, $conn) {
    // Smart routing logic — map keywords to department IDs
    // Department IDs must match what is seeded in DB
    $keywords = [
        1 => ['light', 'electricity', 'electrical', 'bulb', 'wire', 'power', 'switch', 'fan', 'socket', 'voltage', 'short circuit'],
        2 => ['water', 'pipe', 'leak', 'plumbing', 'tap', 'drain', 'flush', 'sewage', 'toilet', 'basin', 'overflow'],
        3 => ['clean', 'cleaning', 'dirty', 'garbage', 'waste', 'sweep', 'mop', 'trash', 'hygiene', 'housekeeping', 'dust'],
        4 => ['internet', 'wifi', 'network', 'computer', 'laptop', 'software', 'hardware', 'printer', 'server', 'it', 'email', 'system'],
        5 => ['repair', 'broken', 'crack', 'wall', 'ceiling', 'door', 'window', 'paint', 'civil', 'maintenance', 'fix', 'structure'],
        6 => ['security', 'cctv', 'camera', 'lock', 'theft', 'noise', 'safety', 'guard', 'access', 'intruder'],
    ];

    $descLower = strtolower($description);

    // Check each department's keywords against the description
    foreach ($keywords as $deptId => $words) {
        foreach ($words as $word) {
            if (strpos($descLower, $word) !== false) {
                // Keyword found — return this department ID
                return $deptId;
            }
        }
    }

    // No keyword matched — return NULL for manual admin assignment
    return NULL;
}
?>
