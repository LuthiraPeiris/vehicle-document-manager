<?php include 'includes/header.php'; ?>

<div class="public-page">
    <!-- Navbar -->
    <nav class="public-navbar" aria-label="Main navigation">
        <a href="index.php" class="public-brand">
            <span class="public-brand-icon">
                <i class="bi bi-car-front-fill" aria-hidden="true"></i>
            </span>
            <span>Smart Vehicle Documents</span>
        </a>

        <div class="public-nav-actions">
            <a href="auth/login.php" class="nav-login">Login</a>
            <a href="auth/register.php" class="nav-signup">Get Started</a>
        </div>
    </nav>

    <!-- Hero -->
    <section class="hero-section">
        <div class="hero-content">
            <span class="hero-badge">
                <i class="bi bi-shield-check" aria-hidden="true"></i>
                Smart Vehicle Document Management
            </span>

            <h1>
                Never miss a
                <span>vehicle renewal.</span>
            </h1>

            <p>
                Keep your vehicle documents organized, track expiry dates,
                and stay ahead of upcoming renewals from one simple dashboard.
            </p>

            <div class="hero-actions">
                <a href="auth/register.php" class="hero-primary-btn">
                    Get Started
                    <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </a>
                <a href="#features" class="hero-secondary-btn">Explore Features</a>
            </div>
        </div>

        <!-- Hero vehicle image. Save the supplied car image at this path. -->
        <div class="hero-preview">
            <img
                class="hero-car-image"
                src="assets/images/vehicle-hero.png"
                alt="Modern black luxury car"
                fetchpriority="high"
                decoding="async"
            >
        </div>
    </section>

    <!-- Features -->
    <section class="features-section" id="features">
        <div class="section-intro">
            <span class="section-eyebrow">WHAT YOU CAN DO</span>
            <h2>Everything you need to manage your vehicle documents.</h2>
            <p>
                A simple way to keep track of important documents and
                upcoming renewals.
            </p>
        </div>

        <div class="feature-grid">
            <div class="feature-card">
                <div class="feature-icon blue">
                    <i class="bi bi-car-front-fill" aria-hidden="true"></i>
                </div>
                <h3>Manage Vehicles</h3>
                <p>Keep information about your vehicles organized in one place.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon purple">
                    <i class="bi bi-file-earmark-text" aria-hidden="true"></i>
                </div>
                <h3>Track Documents</h3>
                <p>Keep track of important vehicle documents and their expiry dates.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon orange">
                    <i class="bi bi-bell" aria-hidden="true"></i>
                </div>
                <h3>Stay Ahead</h3>
                <p>Get reminders when important vehicle documents are approaching expiry.</p>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="cta-section">
        <div>
            <h2>Keep your vehicle documents under control.</h2>
            <p>Start organizing your vehicle information today.</p>
        </div>
        <a href="auth/register.php" class="cta-button">
            Create an Account
            <i class="bi bi-arrow-right" aria-hidden="true"></i>
        </a>
    </section>

    <!-- Footer -->
    <footer class="public-footer">
        <span>© <?php echo date('Y'); ?> Smart Vehicle Document Management</span>
        <span>Smart Vehicle Document Management</span>
    </footer>
</div>

</body>
</html>
