<?php include '../includes/header.php'; ?>

<div class="app-container">

    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include '../includes/navbar.php'; ?>

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


                <form id="vehicleForm">

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
                            >

                                <option value="">
                                    Select vehicle type
                                </option>

                                <option value="car">
                                    Car
                                </option>

                                <option value="van">
                                    Van
                                </option>

                                <option value="suv">
                                    SUV
                                </option>

                                <option value="motorcycle">
                                    Motorcycle
                                </option>

                                <option value="three_wheeler">
                                    Three Wheeler
                                </option>

                                <option value="bus">
                                    Bus
                                </option>

                                <option value="lorry">
                                    Lorry
                                </option>

                                <option value="other">
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
                            >

                        </div>


                        <!-- Year -->
                        <div class="form-group">

                            <label for="year">
                                Manufacturing Year
                            </label>

                            <input
                                type="number"
                                id="year"
                                name="year"
                                placeholder="e.g. 2020"
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

<script>

document.getElementById("vehicleForm").addEventListener("submit", function(event) {

    event.preventDefault();

    alert("Vehicle saved successfully! (Prototype)");

});

</script>


</body>
</html>