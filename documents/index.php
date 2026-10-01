<?php include '../includes/header.php'; ?>

<div class="app-container">

    <?php include '../includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include '../includes/navbar.php'; ?>

        <div class="content-wrapper">

            <!-- Page Header -->
            <div class="page-header">

                <div>
                    <h1>Documents</h1>

                    <p>
                        View and manage all vehicle documents in one place.
                    </p>
                </div>

            </div>


            <!-- Document Summary -->
            <div class="document-stats-grid">

                <div class="document-stat-card">

                    <div class="document-stat-icon blue">
                        <i class="bi bi-files"></i>
                    </div>

                    <div>
                        <span>Total Documents</span>
                        <strong>8</strong>
                    </div>

                </div>


                <div class="document-stat-card">

                    <div class="document-stat-icon green">
                        <i class="bi bi-check-circle"></i>
                    </div>

                    <div>
                        <span>Valid</span>
                        <strong>5</strong>
                    </div>

                </div>


                <div class="document-stat-card">

                    <div class="document-stat-icon orange">
                        <i class="bi bi-clock"></i>
                    </div>

                    <div>
                        <span>Expiring Soon</span>
                        <strong>2</strong>
                    </div>

                </div>


                <div class="document-stat-card">

                    <div class="document-stat-icon red">
                        <i class="bi bi-exclamation-circle"></i>
                    </div>

                    <div>
                        <span>Expired</span>
                        <strong>1</strong>
                    </div>

                </div>

            </div>


            <!-- Search / Filter -->
            <div class="documents-toolbar">

                <div class="document-search-box">

                    <i class="bi bi-search"></i>

                    <input
                        type="text"
                        id="documentSearch"
                        placeholder="Search by document, vehicle or registration..."
                    >

                </div>


                <select id="documentTypeFilter" class="document-filter">

                    <option value="all">
                        All Document Types
                    </option>

                    <option value="driving-license">
                        Driving License
                    </option>

                    <option value="revenue-license">
                        Revenue License
                    </option>

                    <option value="insurance">
                        Vehicle Insurance
                    </option>

                    <option value="emission">
                        Emission Test Certificate
                    </option>

                </select>


                <select id="documentStatusFilter" class="document-filter">

                    <option value="all">
                        All Status
                    </option>

                    <option value="valid">
                        Valid
                    </option>

                    <option value="expiring">
                        Expiring Soon
                    </option>

                    <option value="expired">
                        Expired
                    </option>

                </select>

            </div>


            <!-- Documents Table -->
            <div class="documents-card">

                <div class="documents-table">

                    <!-- Header -->
                    <div class="document-row document-row-header">

                        <span>Document</span>
                        <span>Vehicle</span>
                        <span>Expiry Date</span>
                        <span>Status</span>
                        <span></span>

                    </div>


                    <!-- Driving License -->
                    <div
                        class="document-row document-item"
                        data-type="driving-license"
                        data-status="valid"
                        data-search="driving license toyota corolla abc-123"
                    >

                        <div class="document-name-cell">

                            <div class="document-list-icon blue">
                                <i class="bi bi-person-vcard"></i>
                            </div>

                            <div>
                                <strong>Driving License</strong>
                                <small>Driver documentation</small>
                            </div>

                        </div>


                        <div class="document-vehicle-cell">

                            <strong>Toyota Corolla</strong>
                            <small>ABC-123</small>

                        </div>


                        <div class="document-expiry-cell">

                            <strong>15 Nov 2026</strong>

                            <small>
                                45 days remaining
                            </small>

                        </div>


                        <div>
                            <span class="status-badge success">
                                <i class="bi bi-check-circle"></i>
                                Valid
                            </span>
                        </div>


                        <button class="document-view-btn">
                            <i class="bi bi-chevron-right"></i>
                        </button>

                    </div>


                    <!-- Revenue License -->
                    <div
                        class="document-row document-item"
                        data-type="revenue-license"
                        data-status="expiring"
                        data-search="revenue license toyota corolla abc-123"
                    >

                        <div class="document-name-cell">

                            <div class="document-list-icon orange">
                                <i class="bi bi-file-earmark-text"></i>
                            </div>

                            <div>
                                <strong>Revenue License</strong>
                                <small>Vehicle licensing</small>
                            </div>

                        </div>


                        <div class="document-vehicle-cell">

                            <strong>Toyota Corolla</strong>
                            <small>ABC-123</small>

                        </div>


                        <div class="document-expiry-cell">

                            <strong>07 Oct 2026</strong>

                            <small class="warning-text">
                                7 days remaining
                            </small>

                        </div>


                        <div>
                            <span class="status-badge warning-badge">
                                <i class="bi bi-exclamation-circle"></i>
                                Expiring Soon
                            </span>
                        </div>


                        <button class="document-view-btn">
                            <i class="bi bi-chevron-right"></i>
                        </button>

                    </div>


                    <!-- Insurance -->
                    <div
                        class="document-row document-item"
                        data-type="insurance"
                        data-status="valid"
                        data-search="vehicle insurance toyota corolla abc-123"
                    >

                        <div class="document-name-cell">

                            <div class="document-list-icon purple">
                                <i class="bi bi-shield-check"></i>
                            </div>

                            <div>
                                <strong>Vehicle Insurance</strong>
                                <small>Insurance coverage</small>
                            </div>

                        </div>


                        <div class="document-vehicle-cell">

                            <strong>Toyota Corolla</strong>
                            <small>ABC-123</small>

                        </div>


                        <div class="document-expiry-cell">

                            <strong>25 Dec 2026</strong>

                            <small>
                                85 days remaining
                            </small>

                        </div>


                        <div>
                            <span class="status-badge success">
                                <i class="bi bi-check-circle"></i>
                                Valid
                            </span>
                        </div>


                        <button class="document-view-btn">
                            <i class="bi bi-chevron-right"></i>
                        </button>

                    </div>


                    <!-- Emission -->
                    <div
                        class="document-row document-item"
                        data-type="emission"
                        data-status="expired"
                        data-search="emission test certificate honda vezel xyz-456"
                    >

                        <div class="document-name-cell">

                            <div class="document-list-icon green">
                                <i class="bi bi-wind"></i>
                            </div>

                            <div>
                                <strong>Emission Test Certificate</strong>
                                <small>Emission compliance</small>
                            </div>

                        </div>


                        <div class="document-vehicle-cell">

                            <strong>Honda Vezel</strong>
                            <small>XYZ-456</small>

                        </div>


                        <div class="document-expiry-cell">

                            <strong>01 Oct 2026</strong>

                            <small class="danger-text">
                                Expired 1 day ago
                            </small>

                        </div>


                        <div>
                            <span class="status-badge danger-badge">
                                <i class="bi bi-x-circle"></i>
                                Expired
                            </span>
                        </div>


                        <button class="document-view-btn">
                            <i class="bi bi-chevron-right"></i>
                        </button>

                    </div>


                    <!-- Second Vehicle Insurance -->
                    <div
                        class="document-row document-item"
                        data-type="insurance"
                        data-status="valid"
                        data-search="vehicle insurance honda vezel xyz-456"
                    >

                        <div class="document-name-cell">

                            <div class="document-list-icon purple">
                                <i class="bi bi-shield-check"></i>
                            </div>

                            <div>
                                <strong>Vehicle Insurance</strong>
                                <small>Insurance coverage</small>
                            </div>

                        </div>


                        <div class="document-vehicle-cell">

                            <strong>Honda Vezel</strong>
                            <small>XYZ-456</small>

                        </div>


                        <div class="document-expiry-cell">

                            <strong>20 Dec 2026</strong>

                            <small>
                                80 days remaining
                            </small>

                        </div>


                        <div>
                            <span class="status-badge success">
                                <i class="bi bi-check-circle"></i>
                                Valid
                            </span>
                        </div>


                        <button class="document-view-btn">
                            <i class="bi bi-chevron-right"></i>
                        </button>

                    </div>


                </div>


                <!-- Empty State -->
                <div class="document-empty-state" id="documentEmptyState">

                    <div class="empty-icon">
                        <i class="bi bi-search"></i>
                    </div>

                    <h3>No documents found</h3>

                    <p>
                        Try changing your search or filters.
                    </p>

                </div>

            </div>

        </div>

    </main>

</div>


<script src="../assets/js/app.js"></script>

<script>

const documentSearch =
    document.getElementById("documentSearch");

const documentTypeFilter =
    document.getElementById("documentTypeFilter");

const documentStatusFilter =
    document.getElementById("documentStatusFilter");

const documentItems =
    document.querySelectorAll(".document-item");

const documentEmptyState =
    document.getElementById("documentEmptyState");


function filterDocuments() {

    const search =
        documentSearch.value.toLowerCase().trim();

    const type =
        documentTypeFilter.value;

    const status =
        documentStatusFilter.value;

    let visibleCount = 0;


    documentItems.forEach(item => {

        const searchData =
            item.dataset.search;

        const itemType =
            item.dataset.type;

        const itemStatus =
            item.dataset.status;


        const matchesSearch =
            searchData.includes(search);

        const matchesType =
            type === "all" ||
            itemType === type;

        const matchesStatus =
            status === "all" ||
            itemStatus === status;


        if (
            matchesSearch &&
            matchesType &&
            matchesStatus
        ) {

            item.style.display = "";

            visibleCount++;

        } else {

            item.style.display = "none";

        }

    });


    documentEmptyState.style.display =
        visibleCount === 0 ? "block" : "none";
}


documentSearch.addEventListener(
    "input",
    filterDocuments
);

documentTypeFilter.addEventListener(
    "change",
    filterDocuments
);

documentStatusFilter.addEventListener(
    "change",
    filterDocuments
);

</script>


</body>
</html>