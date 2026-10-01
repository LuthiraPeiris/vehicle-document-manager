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

        <div class="auth-card">

            <div class="auth-header">

                <div class="auth-icon">
                    <i class="bi bi-person"></i>
                </div>

                <h1>Welcome back</h1>

                <p>
                    Sign in to manage your vehicles and documents.
                </p>

            </div>


            <form id="loginForm">

                <div class="auth-form-group">

                    <label for="email">
                        Email Address
                    </label>

                    <div class="auth-input-wrapper">

                        <i class="bi bi-envelope"></i>

                        <input
                            type="email"
                            id="email"
                            placeholder="you@example.com"
                            required
                        >

                    </div>

                </div>


                <div class="auth-form-group">

                    <div class="auth-label-row">

                        <label for="password">
                            Password
                        </label>

                        <a href="#">
                            Forgot password?
                        </a>

                    </div>


                    <div class="auth-input-wrapper">

                        <i class="bi bi-lock"></i>

                        <input
                            type="password"
                            id="password"
                            placeholder="Enter your password"
                            required
                        >

                        <button
                            type="button"
                            class="password-toggle"
                            id="passwordToggle"
                        >
                            <i class="bi bi-eye"></i>
                        </button>

                    </div>

                </div>


                <div class="auth-remember">

                    <label>

                        <input
                            type="checkbox"
                            id="remember"
                        >

                        <span>Remember me</span>

                    </label>

                </div>


                <button
                    type="submit"
                    class="auth-submit-btn"
                >
                    Sign In
                    <i class="bi bi-arrow-right"></i>
                </button>

            </form>


            <div class="auth-divider">
                <span>New to VehicleCare?</span>
            </div>


            <a
                href="register.php"
                class="auth-create-account"
            >
                Create an account
            </a>

        </div>


        <p class="auth-footer-text">
            By continuing, you agree to use the VehicleCare system responsibly.
        </p>

    </div>

</div>


<script src="../assets/js/app.js"></script>

<script>

const passwordToggle =
    document.getElementById("passwordToggle");

const passwordInput =
    document.getElementById("password");

passwordToggle.addEventListener("click", function () {

    const isPassword =
        passwordInput.type === "password";

    passwordInput.type =
        isPassword ? "text" : "password";

    this.innerHTML =
        isPassword
            ? '<i class="bi bi-eye-slash"></i>'
            : '<i class="bi bi-eye"></i>';

});


document
    .getElementById("loginForm")
    .addEventListener("submit", function (event) {

        event.preventDefault();

        window.location.href =
            "../dashboard.php";

    });

</script>


</body>
</html>