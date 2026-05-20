<?php

declare(strict_types=1);

/**
 * CategoryRepository
 *
 * Reads and writes the SERVICE_CATEGORIES table.
 * The frontend currently hardcodes category names; this repository
 * allows them to be managed by admins and fetched dynamically.
 */
final class CategoryRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Return all active categories ordered by name.
     */
    public function findAllActive(): array
    {
        $sql  = 'SELECT CATEGORY_ID, NAME, DESCRIPTION, ICON
                 FROM SERVICE_CATEGORIES
                 WHERE IS_ACTIVE = TRUE
                 ORDER BY NAME ASC';
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Find a category by its name (used to map frontend string → UUID).
     */
    public function findByName(string $name): ?array
    {
        $sql  = 'SELECT * FROM SERVICE_CATEGORIES WHERE NAME = :name LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':name' => trim($name)]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Find a category by UUID.
     */
    public function findById(string $categoryId): ?array
    {
        $sql  = 'SELECT * FROM SERVICE_CATEGORIES WHERE CATEGORY_ID = :id LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $categoryId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Insert a new category and return its UUID.
     */
    public function create(string $name, string $description = '', string $icon = ''): string
    {
        $uuid = $this->generateUuid();
        $sql  = 'INSERT INTO SERVICE_CATEGORIES (CATEGORY_ID, NAME, DESCRIPTION, ICON)
                 VALUES (:id, :name, :desc, :icon)';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id'   => $uuid,
            ':name' => trim($name),
            ':desc' => trim($description),
            ':icon' => trim($icon),
        ]);
        return $uuid;
    }

    /**
     * Update an existing category.
     */
    public function update(string $categoryId, array $fields): bool
    {
        $allowed = ['NAME', 'DESCRIPTION', 'ICON', 'IS_ACTIVE'];
        $sets    = [];
        $params  = [':id' => $categoryId];
        foreach ($allowed as $col) {
            if (array_key_exists(strtolower($col), $fields) || array_key_exists($col, $fields)) {
                $val          = $fields[$col] ?? $fields[strtolower($col)];
                $sets[]       = "$col = :$col";
                $params[":$col"] = $val;
            }
        }
        if (empty($sets)) {
            return false;
        }
        $stmt = $this->db->prepare('UPDATE SERVICE_CATEGORIES SET ' . implode(', ', $sets) . ' WHERE CATEGORY_ID = :id');
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    private function generateUuid(): string
    {
        $data    = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
