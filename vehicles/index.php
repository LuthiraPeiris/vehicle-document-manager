<?php include '../includes/header.php'; ?>

<div class="app-container">

    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include '../includes/navbar.php'; ?>

        <div class="content-wrapper">

            <!-- Page Header -->
            <div class="page-header">

                <div>
                    <h1>My Vehicles</h1>

                    <p>
                        Manage your registered vehicles and their documents.
                    </p>
                </div>

                <a href="add.php" class="btn btn-primary add-vehicle-btn">
                    <i class="bi bi-plus-lg"></i>
                    Add Vehicle
                </a>

            </div>


            <!-- Search & Filter -->
            <div class="vehicle-toolbar">

                <div class="search-box">

                    <i class="bi bi-search"></i>

                    <input
                        type="text"
                        id="vehicleSearch"
                        placeholder="Search by registration number or vehicle..."
                    >

                </div>

                <select class="vehicle-filter" id="vehicleFilter">

                    <option value="all">All Vehicles</option>
                    <option value="attention">Needs Attention</option>
                    <option value="active">All Documents Active</option>

                </select>

            </div>


            <!-- Vehicle Cards -->
            <div class="vehicles-grid" id="vehiclesGrid">


                <!-- Vehicle 1 -->
                <div class="vehicle-card"
                     data-status="active"
                     data-search="toyota corolla abc-123">

                    <div class="vehicle-card-top">

                        <div class="vehicle-main-info">

                            <div class="vehicle-large-icon">
                                <i class="bi bi-car-front-fill"></i>
                            </div>

                            <div>
                                <h3>Toyota Corolla</h3>

                                <span class="vehicle-registration">
                                    ABC-123
                                </span>
                            </div>

                        </div>

                        <button class="vehicle-menu-btn">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>

                    </div>


                    <div class="vehicle-details">

                        <div>
                            <span>Vehicle Type</span>
                            <strong>Car</strong>
                        </div>

                        <div>
                            <span>Year</span>
                            <strong>2020</strong>
                        </div>

                        <div>
                            <span>Documents</span>
                            <strong>4</strong>
                        </div>

                    </div>


                    <div class="vehicle-status-row">

                        <span class="status-badge success">
                            <i class="bi bi-check-circle"></i>
                            All Active
                        </span>

                        <span class="document-count">
                            4 / 4 active
                        </span>

                    </div>


                    <div class="vehicle-card-actions">

                        <a href="view.php" class="btn-view-vehicle">
                            View Vehicle
                            <i class="bi bi-arrow-right"></i>
                        </a>

                        <a href="add.php" class="btn-edit-vehicle">
                            <i class="bi bi-pencil"></i>
                        </a>

                    </div>

                </div>


                <!-- Vehicle 2 -->
                <div class="vehicle-card"
                     data-status="attention"
                     data-search="honda vezel xyz-456">

                    <div class="vehicle-card-top">

                        <div class="vehicle-main-info">

                            <div class="vehicle-large-icon">
                                <i class="bi bi-car-front-fill"></i>
                            </div>

                            <div>
                                <h3>Honda Vezel</h3>

                                <span class="vehicle-registration">
                                    XYZ-456
                                </span>
                            </div>

                        </div>

                        <button class="vehicle-menu-btn">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>

                    </div>


                    <div class="vehicle-details">

                        <div>
                            <span>Vehicle Type</span>
                            <strong>SUV</strong>
                        </div>

                        <div>
                            <span>Year</span>
                            <strong>2021</strong>
                        </div>

                        <div>
                            <span>Documents</span>
                            <strong>4</strong>
                        </div>

                    </div>


                    <div class="vehicle-status-row">

                        <span class="status-badge warning-badge">
                            <i class="bi bi-exclamation-circle"></i>
                            Needs Attention
                        </span>

                        <span class="document-count">
                            2 need attention
                        </span>

                    </div>


                    <div class="vehicle-card-actions">

                        <a href="view.php" class="btn-view-vehicle">
                            View Vehicle
                            <i class="bi bi-arrow-right"></i>
                        </a>

                        <a href="add.php" class="btn-edit-vehicle">
                            <i class="bi bi-pencil"></i>
                        </a>

                    </div>

                </div>


            </div>


            <!-- Empty Search State -->
            <div class="empty-search" id="emptySearch">

                <div class="empty-icon">
                    <i class="bi bi-search"></i>
                </div>

                <h3>No vehicles found</h3>

                <p>
                    Try a different search term.
                </p>

            </div>

        </div>

    </main>

</div>

<script src="../assets/js/app.js"></script>

<script>

const searchInput = document.getElementById("vehicleSearch");
const filterSelect = document.getElementById("vehicleFilter");
const vehicleCards = document.querySelectorAll(".vehicle-card");
const emptySearch = document.getElementById("emptySearch");

function filterVehicles() {

    const searchTerm = searchInput.value.toLowerCase().trim();
    const filterValue = filterSelect.value;

    let visibleCount = 0;

    vehicleCards.forEach(card => {

        const searchData = card.dataset.search;
        const status = card.dataset.status;

        const matchesSearch = searchData.includes(searchTerm);

        const matchesFilter =
            filterValue === "all" ||
            filterValue === status;

        if (matchesSearch && matchesFilter) {

            card.style.display = "";

            visibleCount++;

        } else {

            card.style.display = "none";

        }

    });

    emptySearch.style.display =
        visibleCount === 0 ? "block" : "none";
}

searchInput.addEventListener("input", filterVehicles);

filterSelect.addEventListener("change", filterVehicles);

</script>

</body>
</html>