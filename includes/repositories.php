<?php
declare(strict_types=1);

function search_pattern(string $search): string
{
    return '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $search) . '%';
}

function list_plants(string $search = '', string $category = ''): array
{
    $sql = 'SELECT id, name, category, description, care, image FROM plants WHERE 1 = 1';
    $parameters = [];
    if ($search !== '') {
        $sql .= " AND (name LIKE :name ESCAPE '!' OR category LIKE :group_name ESCAPE '!'"
            . " OR description LIKE :description ESCAPE '!' OR care LIKE :care ESCAPE '!')";
        foreach (['name', 'group_name', 'description', 'care'] as $key) {
            $parameters[$key] = search_pattern($search);
        }
    }
    if ($category !== '') {
        $sql .= ' AND category = :category';
        $parameters['category'] = $category;
    }
    $statement = db()->prepare($sql . ' ORDER BY id');
    $statement->execute($parameters);
    return $statement->fetchAll();
}

function find_plant(int $plantId): ?array
{
    $statement = db()->prepare('SELECT id, name, category, description, care, image FROM plants WHERE id = ?');
    $statement->execute([$plantId]);
    return $statement->fetch() ?: null;
}

function find_user(int $userId): ?array
{
    $statement = db()->prepare('SELECT * FROM users WHERE id = ?');
    $statement->execute([$userId]);
    return $statement->fetch() ?: null;
}

function find_user_by_email(string $email): ?array
{
    $statement = db()->prepare('SELECT * FROM users WHERE email = ?');
    $statement->execute([$email]);
    return $statement->fetch() ?: null;
}

function email_in_use(string $email, int $exceptId = 0): bool
{
    $statement = db()->prepare('SELECT id FROM users WHERE email = ? AND id <> ?');
    $statement->execute([$email, $exceptId]);
    return $statement->fetchColumn() !== false;
}

function create_user(string $name, string $email, string $password, string $role = 'user'): int
{
    $statement = db()->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)');
    $statement->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
    return (int) db()->lastInsertId();
}

function duplicate_email_error(PDOException $exception): bool
{
    return (int) ($exception->errorInfo[1] ?? 0) === 1062;
}

function user_favorite_ids(int $userId): array
{
    $statement = db()->prepare('SELECT plant_id FROM favorites WHERE user_id = ? ORDER BY created_at DESC, plant_id');
    $statement->execute([$userId]);
    return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
}

function list_users(string $search, string $status, int $page, int $perPage = 20): array
{
    $where = ' WHERE 1 = 1';
    $parameters = [];
    if ($search !== '') {
        $where .= " AND (u.name LIKE :name ESCAPE '!' OR u.email LIKE :email ESCAPE '!')";
        $parameters['name'] = search_pattern($search);
        $parameters['email'] = search_pattern($search);
    }
    if (in_array($status, ['active', 'blocked'], true)) {
        $where .= ' AND u.status = :status';
        $parameters['status'] = $status;
    }
    $statement = db()->prepare('SELECT COUNT(*) FROM users u' . $where);
    $statement->execute($parameters);
    $total = (int) $statement->fetchColumn();
    $pages = max(1, (int) ceil($total / $perPage));
    $page = min(max(1, $page), $pages);
    $offset = ($page - 1) * $perPage;
    $statement = db()->prepare('SELECT u.id, u.name, u.email, u.role, u.status, u.created_at, u.last_login_at,'
        . ' (SELECT COUNT(*) FROM favorites f WHERE f.user_id = u.id) AS favorite_count FROM users u'
        . $where . ' ORDER BY u.id DESC LIMIT ' . $perPage . ' OFFSET ' . $offset);
    $statement->execute($parameters);
    return ['users' => $statement->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => $pages];
}

function user_statistics(): array
{
    return db()->query("SELECT COUNT(*) AS total, COALESCE(SUM(status = 'active'), 0) AS active,"
        . " COALESCE(SUM(status = 'blocked'), 0) AS blocked, COALESCE(SUM(role = 'admin'), 0) AS admins FROM users")->fetch();
}
