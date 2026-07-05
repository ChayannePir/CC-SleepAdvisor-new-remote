<?php

namespace App\Service\Admin;

/**
 * Utilitaire de pagination pour les grandes listes admin.
 */
final class PaginationHelper
{
    /**
     * @return array{page: int, limit: int, offset: int, pages: int, total: int}
     */
    public static function normalize(int $page, int $limit, int $total): array
    {
        $limit = max(5, min(100, $limit));
        $pages = max(1, (int) ceil($total / $limit));
        $page = max(1, min($page, $pages));
        $offset = ($page - 1) * $limit;

        return [
            'page' => $page,
            'limit' => $limit,
            'offset' => $offset,
            'pages' => $pages,
            'total' => $total,
        ];
    }

    /**
     * Fenêtre de numéros de pages affichés (évite 500 liens).
     *
     * @return int[]
     */
    public static function pageRange(int $currentPage, int $totalPages, int $radius = 2): array
    {
        if ($totalPages <= 1) {
            return $totalPages >= 1 ? [1] : [];
        }

        $start = max(1, $currentPage - $radius);
        $end = min($totalPages, $currentPage + $radius);

        return range($start, $end);
    }
}
