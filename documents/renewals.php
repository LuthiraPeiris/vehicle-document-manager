<?php
require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../config/database.php';

$userId = (int) $_SESSION['user_id'];
$today = new DateTimeImmutable('today');
$todayString = $today->format('Y-m-d');
$soonLimit = $today->modify('+30 days')->format('Y-m-d');

$stmt = $pdo->prepare(
    'SELECT
        d.id,
        d.document_type,
        d.issue_date,
        d.expiry_date,
        d.vehicle_id,
        v.registration_number,
        v.vehicle_type,
        v.make,
        v.model
     FROM documents d
     LEFT JOIN vehicles v
        ON v.id = d.vehicle_id
        AND v.user_id = d.user_id
     WHERE d.user_id = :user_id
       AND d.is_current = 1
       AND d.expiry_date <= :soon_limit
     ORDER BY d.expiry_date ASC, d.id DESC'
);
$stmt->execute([
    'user_id' => $userId,
    'soon_limit' => $soonLimit,
]);
$documents = $stmt->fetchAll(PDO::FETCH_ASSOC);

$urgentCount = 0;
$upcomingCount = 0;
$expiredCount = 0;

foreach ($documents as &$document) {
    $expiryDate = new DateTimeImmutable($document['expiry_date']);
    $daysRemaining = (int) $today->diff($expiryDate)->format('%r%a');

    if ($daysRemaining < 0) {
        $document['category'] = 'expired';
        $document['status_label'] = 'Expired';
        $document['status_class'] = 'danger-badge';
        $document['status_icon'] = 'bi-x-circle';
        $document['date_class'] = 'danger';
        $document['dot_class'] = 'danger';
        $document['icon_class'] = 'danger-icon';
        $document['days_message'] = 'Expired ' . abs($daysRemaining)
            . (abs($daysRemaining) === 1 ? ' day ago' : ' days ago');
        $document['days_class'] = 'danger-text';
        $expiredCount++;
    } elseif ($daysRemaining <= 7) {
        $document['category'] = 'urgent';
        $document['status_label'] = $daysRemaining === 0 ? 'Expires Today' : 'Due Soon';
        $document['status_class'] = 'warning-badge';
        $document['status_icon'] = 'bi-exclamation-circle';
        $document['date_class'] = 'warning';
        $document['dot_class'] = 'warning';
        $document['icon_class'] = 'warning-icon';
        $document['days_message'] = $daysRemaining === 0
            ? 'Expires today'
            : $daysRemaining . ($daysRemaining === 1 ? ' day remaining' : ' days remaining');
        $document['days_class'] = 'warning-text';
        $urgentCount++;
    } else {
        $document['category'] = 'upcoming';
        $document['status_label'] = 'Expiring Soon';
        $document['status_class'] = 'warning-badge';
        $document['status_icon'] = 'bi-clock';
        $document['date_class'] = 'normal';
        $document['dot_class'] = 'normal';
        $document['icon_class'] = 'blue-icon';
        $document['days_message'] = $daysRemaining . ' days remaining';
        $document['days_class'] = '';
        $upcomingCount++;
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

    $vehicleName = trim(($document['make'] ?? '') . ' ' . ($document['model'] ?? ''));
    if ($vehicleName === '' && $document['vehicle_id'] !== null) {
        $vehicleName = ucfirst((string) ($document['vehicle_type'] ?? 'Vehicle'));
    }
    $document['vehicle_name'] = $vehicleName;
    $document['expiry_day'] = $expiryDate->format('d');
    $document['expiry_month'] = strtoupper($expiryDate->format('M'));
}
unset($document);

$attentionCount = count($documents);
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="app-container">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="main-content">
        <?php include __DIR__ . '/../includes/navbar.php'; ?>

        <div class="content-wrapper">
            <div class="page-header">
                <div>
                    <h1>Renewals</h1>
                    <p>Keep track of documents that need to be renewed.</p>
                </div>

                <div class="renewal-summary-badge">
                    <i class="bi bi-bell"></i>
                    <?= $attentionCount ?> document<?= $attentionCount === 1 ? '' : 's' ?> need<?= $attentionCount === 1 ? 's' : '' ?> attention
                </div>
            </div>

            <div class="renewal-overview-grid">
                <div class="renewal-overview-card urgent">
                    <div class="renewal-overview-icon"><i class="bi bi-exclamation-circle"></i></div>
                    <div>
                        <span>Urgent</span>
                        <strong><?= $urgentCount ?></strong>
                        <small>Within 7 days</small>
                    </div>
                </div>

                <div class="renewal-overview-card upcoming">
                    <div class="renewal-overview-icon"><i class="bi bi-clock"></i></div>
                    <div>
                        <span>Upcoming</span>
                        <strong><?= $upcomingCount ?></strong>
                        <small>Within 8–30 days</small>
                    </div>
                </div>

                <div class="renewal-overview-card expired">
                    <div class="renewal-overview-icon"><i class="bi bi-x-circle"></i></div>
                    <div>
                        <span>Expired</span>
                        <strong><?= $expiredCount ?></strong>
                        <small>Requires action</small>
                    </div>
                </div>
            </div>

            <div class="renewals-section">
                <div class="section-heading">
                    <div>
                        <h2>Documents Requiring Attention</h2>
                        <p>Your current documents that are expired or expiring within 30 days.</p>
                    </div>

                    <select class="renewal-filter" id="renewalFilter" aria-label="Filter renewals">
                        <option value="all">All</option>
                        <option value="urgent">Within 7 Days</option>
                        <option value="upcoming">Within 8–30 Days</option>
                        <option value="expired">Expired</option>
                    </select>
                </div>

                <div class="renewal-list-card" id="renewalList">
                    <?php foreach ($documents as $document): ?>
                        <?php
                        $renewUrl = 'add.php?id=' . (int) $document['id'];
                        if ($document['vehicle_id'] !== null) {
                            $renewUrl .= '&vehicle_id=' . (int) $document['vehicle_id'];
                        }
                        $displayName = $document['vehicle_id'] === null
                            ? 'Personal Document'
                            : ($document['vehicle_name'] !== '' ? $document['vehicle_name'] : 'Vehicle');
                        ?>
                        <div class="renewal-page-item" data-category="<?= htmlspecialchars($document['category'], ENT_QUOTES, 'UTF-8') ?>">
                            <div class="renewal-date-column <?= htmlspecialchars($document['date_class'], ENT_QUOTES, 'UTF-8') ?>">
                                <strong><?= htmlspecialchars($document['expiry_day'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <span><?= htmlspecialchars($document['expiry_month'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>

                            <div class="renewal-timeline-line">
                                <span class="timeline-dot <?= htmlspecialchars($document['dot_class'], ENT_QUOTES, 'UTF-8') ?>"></span>
                            </div>

                            <div class="renewal-page-content">
                                <div class="renewal-page-main">
                                    <div class="renewal-page-icon <?= htmlspecialchars($document['icon_class'], ENT_QUOTES, 'UTF-8') ?>">
                                        <i class="bi <?= htmlspecialchars($document['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                                    </div>
                                    <div>
                                        <h3><?= htmlspecialchars($document['document_type'], ENT_QUOTES, 'UTF-8') ?></h3>
                                        <p>
                                            <?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>
                                            <?php if (!empty($document['registration_number'])): ?>
                                                <span>•</span>
                                                <?= htmlspecialchars($document['registration_number'], ENT_QUOTES, 'UTF-8') ?>
                                            <?php endif; ?>
                                        </p>
                                        <small>Expiry date: <?= htmlspecialchars((new DateTimeImmutable($document['expiry_date']))->format('d M Y'), ENT_QUOTES, 'UTF-8') ?></small>
                                    </div>
                                </div>

                                <div class="renewal-page-status">
                                    <span class="status-badge <?= htmlspecialchars($document['status_class'], ENT_QUOTES, 'UTF-8') ?>">
                                        <i class="bi <?= htmlspecialchars($document['status_icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                                        <?= htmlspecialchars($document['status_label'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                    <strong class="<?= htmlspecialchars($document['days_class'], ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($document['days_message'], ENT_QUOTES, 'UTF-8') ?>
                                    </strong>
                                </div>

                                <div class="renewal-page-actions">
                                    <a class="renewal-action-btn" href="<?= htmlspecialchars($renewUrl, ENT_QUOTES, 'UTF-8') ?>">
                                        <i class="bi bi-arrow-repeat"></i>
                                        Renew
                                    </a>
                                    <a class="renewal-action-btn" href="edit.php?id=<?= (int) $document['id'] ?>">
                                        <i class="bi bi-pencil"></i>
                                        Edit
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="renewals-empty-state" id="renewalsEmptyState" <?= empty($documents) ? '' : 'style="display:none;"' ?>>
                    <div class="empty-icon"><i class="bi bi-check-circle"></i></div>
                    <h3>No renewals found</h3>
                    <p>There are no documents matching this filter.</p>
                    <?php if (empty($documents)): ?>
                        <a href="add.php" class="secondary-action"><i class="bi bi-plus-lg"></i> Add Document</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="reminder-info-card">
                <div class="reminder-info-icon"><i class="bi bi-envelope"></i></div>
                <div>
                    <h3>Email Reminders</h3>
                    <p>Email reminders are not connected yet. We'll configure and test reminder delivery in a later step.</p>
                </div>
                <span class="reminder-status">
                    <i class="bi bi-clock"></i>
                    Not configured
                </span>
            </div>
        </div>
    </main>
</div>

<script src="../assets/js/app.js"></script>
<script>
const renewalFilter = document.getElementById('renewalFilter');
const renewalItems = document.querySelectorAll('.renewal-page-item');
const renewalsEmptyState = document.getElementById('renewalsEmptyState');

renewalFilter.addEventListener('change', function () {
    const selected = this.value;
    let visibleCount = 0;

    renewalItems.forEach(item => {
        const matches = selected === 'all' || item.dataset.category === selected;
        item.style.display = matches ? '' : 'none';
        if (matches) visibleCount++;
    });

    renewalsEmptyState.style.display = visibleCount === 0 ? 'block' : 'none';
});
</script>

</body>
</html>
