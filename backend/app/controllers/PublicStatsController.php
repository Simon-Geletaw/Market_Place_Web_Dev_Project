<?php

declare(strict_types=1);

/**
 * PublicStatsController
 *
 * Returns live platform metrics for the landing page.
 * No authentication required — these are aggregate totals only.
 */
final class PublicStatsController
{
    public function stats(): array
    {
        $db = DatabaseConnector::getInstance();

        // Count customers
        $customers = (int) $db->query(
            "SELECT COUNT(*) FROM USERS WHERE ROLE = 'Customer'"
        )->fetchColumn();

        // Count providers
        $providers = (int) $db->query(
            "SELECT COUNT(*) FROM USERS WHERE ROLE = 'Provider'"
        )->fetchColumn();

        // Average rating across all reviews
        $avgRating = (float) $db->query(
            "SELECT COALESCE(AVG(RATING), 0) FROM REVIEWS"
        )->fetchColumn();

        return success_response('stats', [
            'customers'  => $customers,
            'providers'  => $providers,
            'avg_rating' => round($avgRating, 1),
        ]);
    }
}
