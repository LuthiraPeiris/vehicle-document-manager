```php
<?php

require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../config/database.php';

$userId = (int) $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| Retrieve previous document versions only
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    'SELECT
        d.id,
        d.document_type,
        d.issue_date,
        d.expiry_date,
        d.vehicle_id,
        d.previous_document_id,
        d.created_at,
        d.file_path,
        v.registration_number,
        v.vehicle_type,
        v.make,
        v.model
     FROM documents d
     LEFT JOIN vehicles v
        ON v.id = d.vehicle_id
        AND v.user_id = d.user_id
     WHERE d.user_id = :user_id
       AND d.is_current = 0
     ORDER BY
        d.document_type ASC,
        d.vehicle_id ASC,
        d.created_at DESC,
        d.id DESC'
);

$stmt->execute(['user_id' => $userId]);
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalHistoryCount = count($documents);

foreach ($documents as &$document) {

    $document['vehicle_name'] = trim(
        ($document['make'] ?? '') . ' ' .
        ($document['model'] ?? '')
    );

    if (
        $document['vehicle_name'] === '' &&
        $document['vehicle_id'] !== null
    ) {
        $document['vehicle_name'] = ucfirst(
            (string) ($document['vehicle_type'] ?? 'Vehicle')
        );
    }

    switch ($document['document_type']) {
        case 'Driving License':
            $document['icon'] = 'bi-person-vcard';
            break;

        case 'Revenue License':
            $document['icon'] = 'bi-file-earmark-text';
            break;

        case 'Vehicle Insurance':
            $document['icon'] = 'bi-shield-check';
            break;

        case 'Emission Test Certificate':
            $document['icon'] = 'bi-wind';
            break;

        default:
            $document['icon'] = 'bi-file-earmark';
            break;
    }
}

unset($document);

function history_h($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="app-container">

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">

        <?php include __DIR__ . '/../includes/navbar.php'; ?>

        <div class="content-wrapper history-page">

            <!-- Page Header -->
            <div class="page-header">

                <div>
                    <h1>Renewal History</h1>

                    <p>
                        View previous versions of your documents
                        that were preserved when you renewed them.
                    </p>
                </div>

                <a href="index.php" class="secondary-action">
                    <i class="bi bi-folder2-open"></i>
                    All Documents
                </a>

            </div>

            <!-- History Summary -->
            <div class="renewal-overview-grid">

                <div class="renewal-overview-card expired">

                    <div class="renewal-overview-icon">
                        <i class="bi bi-clock-history"></i>
                    </div>

                    <div>
                        <span>Previous Versions</span>
                        <strong><?= $totalHistoryCount ?></strong>
                        <small>Saved historical records</small>
                    </div>

                </div>

            </div>

            <!-- History Records -->
            <section class="renewals-section">

                <div class="section-heading">

                    <div>
                        <h2>Previous Document Records</h2>

                        <p>
                            Only old versions are displayed here.
                            Your current documents remain under Documents.
                        </p>
                    </div>

                    <select
                        id="historyTypeFilter"
                        class="renewal-filter"
                        aria-label="Filter by document type"
                    >
                        <option value="all">All document types</option>
                        <option value="Driving License">Driving License</option>
                        <option value="Revenue License">Revenue License</option>
                        <option value="Vehicle Insurance">Vehicle Insurance</option>
                        <option value="Emission Test Certificate">
                            Emission Test Certificate
                        </option>
                    </select>

                </div>

                <?php if (empty($documents)): ?>

                    <div class="renewals-empty-state">

                        <div class="empty-icon">
                            <i class="bi bi-clock-history"></i>
                        </div>

                        <h3>No renewal history yet</h3>

                        <p>
                            Previous versions will appear here after
                            you renew a document. Your current documents
                            are available on the Documents page.
                        </p>

                        <a href="index.php" class="secondary-action">
                            <i class="bi bi-file-earmark-text"></i>
                            View Documents
                        </a>

                    </div>

                <?php else: ?>

                    <div class="renewal-list-card" id="historyList">

                        <?php foreach ($documents as $document): ?>

                            <?php

                            $vehicleLabel = $document['vehicle_id'] === null
                                ? 'Personal Document'
                                : (
                                    $document['vehicle_name'] !== ''
                                        ? $document['vehicle_name']
                                        : 'Vehicle'
                                );

                            $issueLabel = !empty($document['issue_date'])
                                ? (
                                    new DateTimeImmutable(
                                        $document['issue_date']
                                    )
                                )->format('d M Y')
                                : 'Not provided';

                            $expiryLabel = !empty($document['expiry_date'])
                                ? (
                                    new DateTimeImmutable(
                                        $document['expiry_date']
                                    )
                                )->format('d M Y')
                                : 'Not provided';

                            $createdLabel = !empty($document['created_at'])
                                ? (
                                    new DateTimeImmutable(
                                        $document['created_at']
                                    )
                                )->format('d M Y')
                                : 'Unknown';

                            ?>

                            <article
                                class="renewal-page-item"
                                data-document-type="<?= history_h($document['document_type']) ?>"
                            >

                                <div
                                    class="renewal-page-content"
                                    style="width: 100%;"
                                >

                                    <div class="renewal-page-main">

                                        <div class="renewal-page-icon normal-icon">
                                            <i class="bi <?= history_h($document['icon']) ?>"></i>
                                        </div>

                                        <div>

                                            <h3>
                                                <?= history_h($document['document_type']) ?>
                                            </h3>

                                            <p>
                                                <?= history_h($vehicleLabel) ?>

                                                <?php if (!empty($document['registration_number'])): ?>
                                                    <span>•</span>
                                                    <?= history_h($document['registration_number']) ?>
                                                <?php endif; ?>
                                            </p>

                                            <small>
                                                Issue: <?= history_h($issueLabel) ?>
                                                &nbsp;·&nbsp;
                                                Expiry: <?= history_h($expiryLabel) ?>
                                                &nbsp;·&nbsp;
                                                Record added: <?= history_h($createdLabel) ?>
                                            </small>

                                            <?php if (!empty($document['previous_document_id'])): ?>

                                                <small>
                                                    Renewed from record
                                                    #<?= (int) $document['previous_document_id'] ?>
                                                </small>

                                            <?php endif; ?>

                                        </div>

                                    </div>

                                    <div class="renewal-page-status">

                                        <span class="status-badge normal-badge">
                                            <i class="bi bi-clock-history"></i>
                                            Previous version
                                        </span>

                                    </div>

                                    <div class="renewal-page-actions">

                                        <?php if (!empty($document['file_path'])): ?>

                                            <a
                                                class="renewal-action-btn"
                                                href="file.php?id=<?= (int) $document['id'] ?>"
                                                target="_blank"
                                                rel="noopener"
                                            >
                                                <i class="bi bi-eye"></i>
                                                View File
                                            </a>

                                        <?php endif; ?>

                                    </div>

                                </div>

                            </article>

                        <?php endforeach; ?>

                    </div>

                    <div
                        class="renewals-empty-state"
                        id="historyEmptyState"
                        style="display: none;"
                    >

                        <div class="empty-icon">
                            <i class="bi bi-search"></i>
                        </div>

                        <h3>No matching records</h3>

                        <p>Try selecting another document type.</p>

                    </div>

                <?php endif; ?>

            </section>

        </div>

    </main>

</div>

<script src="../assets/js/app.js"></script>

<script>
const historyTypeFilter = document.getElementById('historyTypeFilter');
const historyItems = document.querySelectorAll(
    '#historyList .renewal-page-item'
);
const historyEmptyState = document.getElementById('historyEmptyState');

function filterHistoryRecords() {
    if (!historyTypeFilter) {
        return;
    }

    const type = historyTypeFilter.value;
    let visibleCount = 0;

    historyItems.forEach(item => {
        const visible =
            type === 'all' ||
            item.dataset.documentType === type;

        item.style.display = visible ? '' : 'none';

        if (visible) {
            visibleCount++;
        }
    });

    if (historyEmptyState) {
        historyEmptyState.style.display =
            visibleCount === 0 ? 'block' : 'none';
    }
}

if (historyTypeFilter) {
    historyTypeFilter.addEventListener(
        'change',
        filterHistoryRecords
    );
}
</script>

</body>
</html>
```
