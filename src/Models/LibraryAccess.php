<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

class LibraryAccess
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Check user acces to book another user
     *
     * @param int $ownerId  - ID of user who allow access
     * @param int $targetId - ID of user who accept access
     *
     * @throws PDOException
     *
     * @return bool - true if user has access, false otherwise
     */
    public function hasAccess(int $ownerId, int $targetId) : bool
    {
        $sql = <<<'SQL'
        SELECT
            1
        FROM
            `library_access`
        WHERE
            `owner_id` = :owner_id AND
            `target_id` = :target_id
        LIMIT 1
        SQL;

        $query = $this->db->prepare($sql);
        $query->execute([
            ':owner_id' => $ownerId,
            ':target_id' => $targetId,
        ]);

        return $query->fetch() !== false;
    }
}
