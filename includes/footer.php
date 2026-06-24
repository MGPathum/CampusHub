<?php
/**
 * CampusHub - Global Footer
 * Closes the .container and .main-content divs opened in header.php.
 */
?>
    </div><!-- /.container -->
</main><!-- /.main-content -->

<!-- ===== SITE FOOTER ===== -->
<footer class="site-footer">
    <div class="container footer-inner">

        <div class="footer-brand">
            <span class="logo-icon">🎓</span>
            <span class="logo-text"><?= SITE_NAME ?></span>
        </div>

        <div class="footer-links">
            <h4>Quick Links</h4>
            <ul>
                <li><a href="<?= SITE_URL ?>/index.php">Home</a></li>
                <li><a href="<?= SITE_URL ?>/announcements.php">Announcements</a></li>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'student'): ?>
                    <li><a href="<?= SITE_URL ?>/student/events.php">Events</a></li>
                    <li><a href="<?= SITE_URL ?>/student/profile.php">My Profile</a></li>
                <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <li><a href="<?= SITE_URL ?>/admin/dashboard.php">Admin Dashboard</a></li>
                <?php else: ?>
                    <li><a href="<?= SITE_URL ?>/auth/login.php">Login</a></li>
                    <li><a href="<?= SITE_URL ?>/auth/register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="footer-contact">
            <h4>Contact</h4>
            <p>📍 Java Institute, Gampaha.</p>
            <p>📞 074 274 1902</p>
            <p>✉️ campushub@gmail.com</p>
        </div>

    </div>

    <div class="footer-bottom">
        <div class="container">
            <p>&copy; <?= date('Y') ?> <?= SITE_NAME ?>. Built for Web Programming class.</p>
        </div>
    </div>
</footer>

<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>
