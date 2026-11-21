<?php
declare(strict_types=1);

/**
 * Star ratings (1-5, one per user and plant) and comments under each plant.
 * Everything here degrades to "no reviews" until scripts/setup-database.php has created
 * the plant_ratings and plant_comments tables, so the catalog never breaks on an old database.
 */
const PLANT_COMMENT_MAX_LENGTH = 1000;
const PLANT_COMMENTS_PER_PAGE = 10;

function plant_reviews_ready(): bool
{
    static $ready = null;
    if ($ready === null) {
        try {
            db()->query('SELECT 1 FROM plant_ratings LIMIT 1');
            db()->query('SELECT 1 FROM plant_comments LIMIT 1');
            $ready = true;
        } catch (PDOException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) !== 1146) {
                throw $exception;
            }
            error_log('plant_ratings/plant_comments tables are missing; run scripts/setup-database.php to enable plant reviews.');
            $ready = false;
        }
    }
    return $ready;
}

/** Average and count of every rated plant, loaded once per request (the catalog is small). */
function plant_rating_stats(int $plantId): array
{
    static $stats = null;
    if ($stats === null) {
        $stats = [];
        if (plant_reviews_ready()) {
            foreach (db()->query('SELECT plant_id, AVG(rating) AS average, COUNT(*) AS total FROM plant_ratings GROUP BY plant_id') as $row) {
                $stats[(int) $row['plant_id']] = ['average' => round((float) $row['average'], 1), 'count' => (int) $row['total']];
            }
        }
    }
    return $stats[$plantId] ?? ['average' => 0.0, 'count' => 0];
}

/** Number of ratings per star value, 5 down to 1. */
function plant_rating_distribution(int $plantId): array
{
    $distribution = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
    if (!plant_reviews_ready()) {
        return $distribution;
    }
    $statement = db()->prepare('SELECT rating, COUNT(*) AS total FROM plant_ratings WHERE plant_id = ? GROUP BY rating');
    $statement->execute([$plantId]);
    foreach ($statement->fetchAll() as $row) {
        $distribution[(int) $row['rating']] = (int) $row['total'];
    }
    return $distribution;
}

function user_plant_rating(int $userId, int $plantId): ?int
{
    if (!plant_reviews_ready()) {
        return null;
    }
    $statement = db()->prepare('SELECT rating FROM plant_ratings WHERE plant_id = ? AND user_id = ?');
    $statement->execute([$plantId, $userId]);
    $rating = $statement->fetchColumn();
    return $rating === false ? null : (int) $rating;
}

/** Newest comments first, each with its author's name and current star rating for the plant. */
function list_plant_comments(int $plantId, int $page): array
{
    if (!plant_reviews_ready()) {
        return ['comments' => [], 'total' => 0, 'page' => 1, 'pages' => 1];
    }
    $statement = db()->prepare('SELECT COUNT(*) FROM plant_comments WHERE plant_id = ?');
    $statement->execute([$plantId]);
    $total = (int) $statement->fetchColumn();
    $pages = max(1, (int) ceil($total / PLANT_COMMENTS_PER_PAGE));
    $page = min(max(1, $page), $pages);
    $statement = db()->prepare('SELECT c.id, c.user_id, c.content, c.created_at, u.name AS author, r.rating'
        . ' FROM plant_comments c JOIN users u ON u.id = c.user_id'
        . ' LEFT JOIN plant_ratings r ON r.plant_id = c.plant_id AND r.user_id = c.user_id'
        . ' WHERE c.plant_id = ? ORDER BY c.id DESC LIMIT ' . PLANT_COMMENTS_PER_PAGE . ' OFFSET ' . (($page - 1) * PLANT_COMMENTS_PER_PAGE));
    $statement->execute([$plantId]);
    return ['comments' => $statement->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => $pages];
}

/** Read-only star meter; the screen-reader text carries the number. */
function plant_stars_html(float $rating, string $extraClass = ''): string
{
    $rating = max(0.0, min(5.0, $rating));
    $label = $rating > 0 ? number_format($rating, 1, ',', '') . ' trên 5 sao' : 'Chưa có đánh giá';
    return '<span class="star-meter' . ($extraClass !== '' ? ' ' . e($extraClass) : '') . '" style="--rating: ' . e(number_format($rating, 2, '.', '')) . '"'
        . ' role="img" aria-label="' . e($label) . '"></span>';
}
