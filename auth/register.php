<?php include '../includes/header.php'; ?>

<div class="auth-page">

    <div class="auth-brand">

        <a href="../index.php" class="public-brand">

            <span class="public-brand-icon">
                <i class="bi bi-car-front-fill"></i>
            </span>

            <span>VehicleCare</span>

        </a>

    </div>


    <div class="auth-container">

        <div class="auth-card register-card">

            <div class="auth-header">

                <div class="auth-icon">
                    <i class="bi bi-person-plus"></i>
                </div>

                <h1>Create your account</h1>

                <p>
                    Start managing your vehicle documents in one place.
                </p>

            </div>


            <form id="registerForm">

                <div class="auth-form-group">

                    <label for="name">
                        Full Name
                    </label>

                    <div class="auth-input-wrapper">

                        <i class="bi bi-person"></i>

                        <input
                            type="text"
                            id="name"
                            placeholder="Enter your full name"
                            required
                        >

                    </div>

                </div>


                <div class="auth-form-group">

                    <label for="registerEmail">
                        Email Address
                    </label>

                    <div class="auth-input-wrapper">

                        <i class="bi bi-envelope"></i>

                        <input
                            type="email"
                            id="registerEmail"
                            placeholder="you@example.com"
                            required
                        >

                    </div>

                </div>


                <div class="auth-form-group">

                    <label for="phone">
                        Phone Number
                    </label>

                    <div class="auth-input-wrapper">

                        <i class="bi bi-telephone"></i>

                        <input
                            type="tel"
                            id="phone"
                            placeholder="Enter your phone number"
                            required
                        >

                    </div>

                </div>


                <div class="auth-form-row">

                    <div class="auth-form-group">

                        <label for="registerPassword">
                            Password
                        </label>

                        <div class="auth-input-wrapper">

                            <i class="bi bi-lock"></i>

                            <input
                                type="password"
                                id="registerPassword"
                                placeholder="Create password"
                                required
                            >

                        </div>

                    </div>


                    <div class="auth-form-group">

                        <label for="confirmPassword">
                            Confirm Password
                        </label>

                        <div class="auth-input-wrapper">

                            <i class="bi bi-lock"></i>

                            <input
                                type="password"
                                id="confirmPassword"
                                placeholder="Confirm password"
                                required
                            >

                        </div>

                    </div>

                </div>


                <div class="password-requirement">

                    <i class="bi bi-info-circle"></i>

                    <span>
                        Use a strong password with a combination of
                        letters, numbers, and symbols.
                    </span>

                </div>


                <button
                    type="submit"
                    class="auth-submit-btn"
                >
                    Create Account
                    <i class="bi bi-arrow-right"></i>
                </button>

            </form>


            <div class="auth-divider">
                <span>Already have an account?</span>
            </div>


            <a
                href="login.php"
                class="auth-create-account"
            >
                Sign in instead
            </a>

        </div>


        <p class="auth-footer-text">
            Your information will be securely stored in the VehicleCare system.
        </p>

    </div>

</div>


<script src="../assets/js/app.js"></script>

<script>

document
    .getElementById("registerForm")
    .addEventListener("submit", function (event) {

        event.preventDefault();

        const password =
            document.getElementById("registerPassword").value;

        const confirmPassword =
            document.getElementById("confirmPassword").value;

        if (password !== confirmPassword) {

            alert("Passwords do not match.");

            return;

        }

        window.location.href =
            "login.php";

    });

</script>


</body>
</html>