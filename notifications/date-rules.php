<?php
/** Calendar-based notification milestone calculations. */
function notificationPreviousMonthDate(DateTimeImmutable $expiryDate): DateTimeImmutable
{
    $day = (int) $expiryDate->format('j');
    $previousMonth = $expiryDate->modify('first day of this month')->modify('-1 month');
    return $previousMonth->setDate(
        (int) $previousMonth->format('Y'),
        (int) $previousMonth->format('n'),
        min($day, (int) $previousMonth->format('t'))
    );
}

function notificationMilestonesForExpiry(DateTimeImmutable $expiryDate, DateTimeImmutable $today): array
{
    $todayKey = $today->format('Y-m-d');
    $milestones = [];
    if (notificationPreviousMonthDate($expiryDate)->format('Y-m-d') === $todayKey) {
        $milestones['one_month'] = 'expires in one calendar month';
    }
    if ($expiryDate->modify('-7 days')->format('Y-m-d') === $todayKey) {
        $milestones['final_week'] = 'expires in 7 days';
    }
    if ($expiryDate->format('Y-m-d') === $todayKey) {
        $milestones['expiry_day'] = 'expires today';
    }
    if ($expiryDate->modify('+7 days')->format('Y-m-d') === $todayKey) {
        $milestones['post_expiry'] = 'expired 7 days ago';
    }
    return $milestones;
}
