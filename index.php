<?php include 'includes/header.php'; ?>

<div class="public-page">
    <!-- Navbar -->
    <nav class="public-navbar" aria-label="Main navigation">
        <a href="index.php" class="public-brand">
            <span class="public-brand-icon">
                <i class="bi bi-car-front-fill" aria-hidden="true"></i>
            </span>
            <span>Vehicle Documents Manager</span>
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
                Vehicle Document Management
            </span>

            <h1>
                <span class="hero-heading-line">Never Miss a</span>
                <span>Vehicle Renewal.</span>
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

                <a href="#features" class="hero-secondary-btn">
                    Explore Features
                </a>
            </div>
        </div>

        <!-- Hero vehicle image -->
        <div class="hero-preview">
            <span class="hero-glow" aria-hidden="true"></span>

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

            <h2>
                All your important vehicle documents in one place.
            </h2>

            <p>
                Manage your documents, monitor expiry dates, and stay
                prepared for renewals without the hassle of paperwork.
            </p>
        </div>

        <div class="feature-grid">

            <!-- Driving License -->
            <div class="feature-card">
                <div class="feature-icon blue">
                    <i class="bi bi-person-vcard" aria-hidden="true"></i>
                </div>

                <h3>Driving License</h3>

                <p>
                    Keep your driving license details organized and
                    track its expiry date so you can renew it on time.
                </p>
            </div>

            <!-- Revenue License -->
            <div class="feature-card">
                <div class="feature-icon purple">
                    <i class="bi bi-file-earmark-check" aria-hidden="true"></i>
                </div>

                <h3>Revenue License</h3>

                <p>
                    Record your vehicle's revenue license details
                    and stay informed about upcoming renewals.
                </p>
            </div>

            <!-- Vehicle Insurance -->
            <div class="feature-card">
                <div class="feature-icon orange">
                    <i class="bi bi-shield-check" aria-hidden="true"></i>
                </div>

                <h3>Vehicle Insurance</h3>

                <p>
                    Keep your insurance information accessible
                    and track policy expiry dates before they arrive.
                </p>
            </div>

            <!-- Emission Test Certificate -->
            <div class="feature-card">
                <div class="feature-icon blue">
                    <i class="bi bi-cloud-check" aria-hidden="true"></i>
                </div>

                <h3>Emission Test Certificate</h3>

                <p>
                    Store your emission test certificate details
                    and keep track of when your next test is due.
                </p>
            </div>

        </div>
    </section>

    <!-- CTA -->
    <section class="cta-section">
        <div>
            <h2>Keep your vehicle documents under control.</h2>
            <p>
                Start organizing your vehicle information today.
            </p>
        </div>

        <a href="auth/register.php" class="cta-button">
            Create an Account
            <i class="bi bi-arrow-right" aria-hidden="true"></i>
        </a>
    </section>

    <!-- Footer -->
    <footer class="public-footer">
        <span>
            © <?php echo date('Y'); ?> Vehicle Document Management
        </span>

        <span>Vehicle Document Management</span>
    </footer>
</div>

</body>
</html>
