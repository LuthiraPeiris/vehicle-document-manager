
<?php

require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../config/database.php';

$errors = [];

$registrationNumber = '';
$vehicleType = '';
$make = '';
$model = '';
$year = '';

$userId = (int) $_SESSION['user_id'];

// Generate a CSRF token.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];

$allowedVehicleTypes = [
    'car',
    'van',
    'suv',
    'motorcycle',
    'three_wheeler',
    'bus',
    'lorry',
    'other'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $registrationNumber = strtoupper(
        trim($_POST['registration_number'] ?? '')
    );

    $vehicleType = trim($_POST['vehicle_type'] ?? '');
    $make = trim($_POST['make'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $year = trim($_POST['year'] ?? '');
    $submittedToken = $_POST['csrf_token'] ?? '';

    // Validate CSRF token.
    if (
        !is_string($submittedToken) ||
        !hash_equals($_SESSION['csrf_token'], $submittedToken)
    ) {
        $errors[] = 'Your session has expired. Please refresh the page and try again.';
    }

    // Registration number validation.
    if ($registrationNumber === '') {
        $errors[] = 'Registration number is required.';
    } elseif (strlen($registrationNumber) > 50) {
        $errors[] = 'Registration number cannot exceed 50 characters.';
    }

    // Vehicle type validation.
    if (!in_array($vehicleType, $allowedVehicleTypes, true)) {
        $errors[] = 'Please select a valid vehicle type.';
    }

    // Optional make and model validation.
    if (strlen($make) > 100) {
        $errors[] = 'Make cannot exceed 100 characters.';
    }

    if (strlen($model) > 100) {
        $errors[] = 'Model cannot exceed 100 characters.';
    }

    // Optional manufacturing year validation.
    $manufacturingYear = null;

    if ($year !== '') {
        $validatedYear = filter_var($year, FILTER_VALIDATE_INT);

        if (
            $validatedYear === false ||
            $validatedYear < 1900 ||
            $validatedYear > 2100
        ) {
            $errors[] = 'Please enter a valid manufacturing year between 1900 and 2100.';
        } else {
            $manufacturingYear = $validatedYear;
        }
    }

    // Save the vehicle if validation succeeds.
    if (empty($errors)) {

        try {
            // Prevent duplicate registration numbers for this user.
            $duplicateStmt = $pdo->prepare(
                'SELECT id
                 FROM vehicles
                 WHERE user_id = :user_id
                   AND LOWER(registration_number) = LOWER(:registration_number)
                 LIMIT 1'
            );

            $duplicateStmt->execute([
                'user_id' => $userId,
                'registration_number' => $registrationNumber
            ]);

            if ($duplicateStmt->fetch()) {
                $errors[] = 'You have already registered a vehicle with this registration number.';
            } else {

                $insertStmt = $pdo->prepare(
                    'INSERT INTO vehicles (
                        user_id,
                        registration_number,
                        vehicle_type,
                        make,
                        model,
                        manufacturing_year
                    ) VALUES (
                        :user_id,
                        :registration_number,
                        :vehicle_type,
                        :make,
                        :model,
                        :manufacturing_year
                    )'
                );

                $insertStmt->execute([
                    'user_id' => $userId,
                    'registration_number' => $registrationNumber,
                    'vehicle_type' => $vehicleType,
                    'make' => $make !== '' ? $make : null,
                    'model' => $model !== '' ? $model : null,
                    'manufacturing_year' => $manufacturingYear
                ]);

                // Rotate the CSRF token after a successful operation.
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                // Redirect after saving to prevent duplicate form submission.
                header('Location: index.php?added=1');
                exit;
            }

        } catch (PDOException $e) {
            error_log('Add vehicle database error: ' . $e->getMessage());

            $errors[] = 'Unable to save the vehicle right now. Please try again.';
        }
    }
}

function vehicleFormEscape($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

include __DIR__ . '/../includes/header.php';

?>

<div class="app-container">

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include __DIR__ . '/../includes/navbar.php'; ?>

        <div class="content-wrapper">

            <!-- Page Header -->
            <div class="page-header">

                <div>
                    <h1>Add Vehicle</h1>

                    <p>
                        Add a vehicle to start managing its documents and renewals.
                    </p>
                </div>

                <a href="index.php" class="secondary-action">
                    <i class="bi bi-arrow-left"></i>
                    Back to Vehicles
                </a>

            </div>

            <!-- Form Card -->
            <div class="form-card">

                <div class="form-card-header">

                    <div class="form-section-icon">
                        <i class="bi bi-car-front-fill"></i>
                    </div>

                    <div>
                        <h2>Vehicle Information</h2>

                        <p>
                            Enter the basic information about your vehicle.
                        </p>
                    </div>

                </div>

                <?php if (!empty($errors)): ?>

                    <div class="alert alert-danger" role="alert">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li>
                                    <?= vehicleFormEscape($error) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                <?php endif; ?>

                <form id="vehicleForm" method="POST" action="add.php">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= vehicleFormEscape($csrfToken) ?>"
                    >

                    <div class="form-grid">

                        <!-- Registration Number -->
                        <div class="form-group">

                            <label for="registration_number">
                                Registration Number
                                <span>*</span>
                            </label>

                            <input
                                type="text"
                                id="registration_number"
                                name="registration_number"
                                placeholder="e.g. ABC-1234"
                                value="<?= vehicleFormEscape($registrationNumber) ?>"
                                maxlength="50"
                                autocomplete="off"
                                required
                            >

                            <small>
                                Enter the vehicle registration number.
                            </small>

                        </div>

                        <!-- Vehicle Type -->
                        <div class="form-group">

                            <label for="vehicle_type">
                                Vehicle Type
                                <span>*</span>
                            </label>

                            <select
                                id="vehicle_type"
                                name="vehicle_type"
                                required
                            >

                                <option value="">
                                    Select vehicle type
                                </option>

                                <option value="car" <?= $vehicleType === 'car' ? 'selected' : '' ?>>
                                    Car
                                </option>

                                <option value="van" <?= $vehicleType === 'van' ? 'selected' : '' ?>>
                                    Van
                                </option>

                                <option value="suv" <?= $vehicleType === 'suv' ? 'selected' : '' ?>>
                                    SUV
                                </option>

                                <option value="motorcycle" <?= $vehicleType === 'motorcycle' ? 'selected' : '' ?>>
                                    Motorcycle
                                </option>

                                <option value="three_wheeler" <?= $vehicleType === 'three_wheeler' ? 'selected' : '' ?>>
                                    Three Wheeler
                                </option>

                                <option value="bus" <?= $vehicleType === 'bus' ? 'selected' : '' ?>>
                                    Bus
                                </option>

                                <option value="lorry" <?= $vehicleType === 'lorry' ? 'selected' : '' ?>>
                                    Lorry
                                </option>

                                <option value="other" <?= $vehicleType === 'other' ? 'selected' : '' ?>>
                                    Other
                                </option>

                            </select>

                        </div>

                        <!-- Make -->
                        <div class="form-group">

                            <label for="make">
                                Make
                            </label>

                            <input
                                type="text"
                                id="make"
                                name="make"
                                placeholder="e.g. Toyota"
                                value="<?= vehicleFormEscape($make) ?>"
                                maxlength="100"
                            >

                        </div>

                        <!-- Model -->
                        <div class="form-group">

                            <label for="model">
                                Model
                            </label>

                            <input
                                type="text"
                                id="model"
                                name="model"
                                placeholder="e.g. Corolla"
                                value="<?= vehicleFormEscape($model) ?>"
                                maxlength="100"
                            >

                        </div>

                        <!-- Manufacturing Year -->
                        <div class="form-group">

                            <label for="year">
                                Manufacturing Year
                            </label>

                            <input
                                type="number"
                                id="year"
                                name="year"
                                placeholder="e.g. 2020"
                                value="<?= vehicleFormEscape($year) ?>"
                                min="1900"
                                max="2100"
                            >

                        </div>

                    </div>

                    <!-- Documents Introduction -->
                    <div class="form-divider"></div>

                    <div class="document-intro">

                        <div class="document-intro-icon">
                            <i class="bi bi-file-earmark-text"></i>
                        </div>

                        <div>
                            <h3>Vehicle Documents</h3>

                            <p>
                                You'll be able to add and manage the vehicle's
                                documents after creating the vehicle.
                            </p>
                        </div>

                    </div>

                    <!-- Form Actions -->
                    <div class="form-actions">

                        <a href="index.php" class="btn-cancel">
                            Cancel
                        </a>

                        <button
                            type="submit"
                            class="btn-save"
                        >
                            <i class="bi bi-check-lg"></i>
                            Save Vehicle
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </main>

</div>

<script src="../assets/js/app.js"></script>

</body>
</html>