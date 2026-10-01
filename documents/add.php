<?php include '../includes/header.php'; ?>

<div class="app-container">

    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include '../includes/navbar.php'; ?>

        <div class="content-wrapper">

            <div class="page-header">

                <div>

                    <div class="breadcrumb">

                        <a href="../vehicles/index.php">
                            My Vehicles
                        </a>

                        <i class="bi bi-chevron-right"></i>

                        <span>Add Document</span>

                    </div>

                    <h1>Add Document</h1>

                    <p>
                        Add an expiry date for one of your vehicle documents.
                    </p>

                </div>

                <a
                    href="../vehicles/view.php"
                    class="secondary-action"
                >
                    <i class="bi bi-arrow-left"></i>
                    Back to Vehicle
                </a>

            </div>


            <div class="form-card">

                <div class="form-card-header">

                    <div class="form-section-icon">
                        <i class="bi bi-file-earmark-plus"></i>
                    </div>

                    <div>
                        <h2>Document Information</h2>

                        <p>
                            Enter the document information for this vehicle.
                        </p>
                    </div>

                </div>


                <form id="documentForm">

                    <div class="form-grid">

                        <div class="form-group">

                            <label for="vehicle">
                                Vehicle
                                <span>*</span>
                            </label>

                            <select id="vehicle" required>

                                <option value="">
                                    Select vehicle
                                </option>

                                <option value="abc-123">
                                    Toyota Corolla — ABC-123
                                </option>

                                <option value="xyz-456">
                                    Honda Vezel — XYZ-456
                                </option>

                            </select>

                        </div>


                        <div class="form-group">

                            <label for="document_type">
                                Document Type
                                <span>*</span>
                            </label>

                            <select id="document_type" required>

                                <option value="">
                                    Select document type
                                </option>

                                <option>
                                    Driving License
                                </option>

                                <option>
                                    Revenue License
                                </option>

                                <option>
                                    Vehicle Insurance
                                </option>

                                <option>
                                    Emission Test Certificate
                                </option>

                            </select>

                        </div>


                        <div class="form-group">

                            <label for="issue_date">
                                Issue Date
                            </label>

                            <input
                                type="date"
                                id="issue_date"
                            >

                        </div>


                        <div class="form-group">

                            <label for="expiry_date">
                                Expiry Date
                                <span>*</span>
                            </label>

                            <input
                                type="date"
                                id="expiry_date"
                                required
                            >

                            <small>
                                This date will be used to track renewals.
                            </small>

                        </div>

                    </div>


                    <div class="document-form-notice">

                        <div class="document-form-notice-icon">
                            <i class="bi bi-bell"></i>
                        </div>

                        <div>

                            <h3>Expiry Reminders</h3>

                            <p>
                                The system will use the expiry date to
                                identify upcoming renewals and send
                                email reminders.
                            </p>

                        </div>

                    </div>


                    <div class="form-actions">

                        <a
                            href="../vehicles/view.php"
                            class="btn-cancel"
                        >
                            Cancel
                        </a>

                        <button
                            type="submit"
                            class="btn-save"
                        >
                            <i class="bi bi-check-lg"></i>
                            Save Document
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </main>

</div>


<script src="../assets/js/app.js"></script>

<script>

document
    .getElementById("documentForm")
    .addEventListener("submit", function(event) {

        event.preventDefault();

        alert("Document saved successfully! (Prototype)");

        window.location.href = "../vehicles/view.php";

    });

</script>


</body>
</html>