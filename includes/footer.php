    <!-- includes/footer.php — Global Footer-->
    <footer class="site-footer mt-auto">
        <div class="container">
            <div class="row align-items-center py-4">
                <!-- Brand column -->
                <div class="col-md-4 text-center text-md-start mb-3 mb-md-0">
                    <span class="footer-brand">
                        <i class="fas fa-bolt me-2 text-warning"></i>QuickResolve<span class="text-primary"></span>
                    </span>
                    <p class="footer-tagline mb-0">Smart Complaint Management</p>
                </div>

                <!-- Middle links -->
                <div class="col-md-4 text-center mb-3 mb-md-0">
                    <a href="<?= SITE_URL ?>/index.php" class="footer-link">Home</a>
                    <span class="footer-sep">·</span>
                    <a href="<?= SITE_URL ?>/login.php" class="footer-link">Login</a>
                    <span class="footer-sep">·</span>
                    <a href="<?= SITE_URL ?>/register.php" class="footer-link">Register</a>
                </div>

                <!-- Copyright -->
                <div class="col-md-4 text-center text-md-end">
                    <p class="footer-copy mb-0">
                        &copy; <?= date('Y') ?> QuickResolve. 
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 Bundle JS (includes Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom scripts -->
    <script src="<?= SITE_URL ?>/assets/js/script.js"></script>
</body>
</html>
