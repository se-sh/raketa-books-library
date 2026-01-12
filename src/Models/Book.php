<?php

declare(strict_types=1);

namespace App\Models;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use PDO;

class Book
{
    private PDO $db;
    private Client $httpClient;

    public function __construct(PDO $db)
    {
        $this->db = $db;

        $this->httpClient = new Client(['timeout' => 10]);
    }

    /**
     * Create new book record for specified user
     *
     * @param int         $userId     - owner user identifier
     * @param string      $title      - book title
     * @param string      $text       - book text
     * @param null|string $externalId - [optional] ID of external book (google\mif)
     *
     * @return int generated book ID (auto-increment primary key)
     *
     * @throws PDOException
     */
    public function createBook(int $userId, string $title, string $text, ?string $externalId = null) : int
    {
        $sql = <<<'SQL'
        INSERT INTO `books`
            (`user_id`, `title`, `text`, `external_id`)
        VALUES
            (:user_id, :title, :text, :external_id)
        SQL;

        $query = $this->db->prepare($sql);
        $query->execute([
            ':user_id' => $userId,
            ':title' => $title,
            ':text' => $text,
            ':external_id' => $externalId,
        ]);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Get list of user books by user ID
     *
     * @param int $userId
     *
     * @return array - list of books
     *
     * @throws PDOException
     */
    public function getUserBooksByUser(int $userId) : array
    {
        $sql = <<<'SQL'
        SELECT
            `id`,
            `title`
        FROM
            `books`
        WHERE
            `user_id` = :user_id AND
            `is_deleted` = 0
        ORDER BY
            `id` ASC
        SQL;

        $query = $this->db->prepare($sql);
        $query->execute([
            ':user_id' => $userId,
        ]);

        return $query->fetchAll();
    }

    /**
     * Get book
     *
     * @param int $id
     *
     * @return null|array - book or null if not found\deleted
     *
     * @throws PDOException
     */
    public function getBookById(int $id) : ?array
    {
        $sql = <<<'SQL'
        SELECT
            `id`,
            `user_id`,
            `title`,
            `text`,
            `is_deleted`
        FROM
            `books`
        WHERE
            `id` = :id AND
            `is_deleted` = 0
        LIMIT 1
        SQL;

        $query = $this->db->prepare($sql);
        $query->execute([
            ':id' => $id,
        ]);

        //$result = $query->fetchAll();
        //return !empty($result) ? $result[0] : null;
        return $query->fetch() ?: null;
    }

    /**
     * Update book title and text by book ID
     *
     * @param int    $id    - book ID to update
     * @param string $title - new book title
     * @param string $text  - new book text
     *
     * @return bool - true if book updated successfully, false if not found\deleted
     *
     * @throws PDOException
     */
    public function saveBook(int $id, string $title, string $text) : bool
    {
        $sql = <<<'SQL'
        UPDATE
            `books`
        SET
            `title` = :title,
            `text` = :text
        WHERE
            `id` = :id AND
            `is_deleted` = 0
        SQL;

        $query = $this->db->prepare($sql);
        $query->execute([
            ':id' => $id,
            ':title' => $title,
            ':text' => $text,
        ]);

        return $query->rowCount() > 0;
    }

    /**
     * Delete (soft) book by marking as deleted (is_deleted = 1)
     *
     * @param int $id - book ID to delete
     *
     * @return bool - true if book successfully deleted, false otherwise
     *
     * @throws PDOException
     */
    public function softDelete(int $id) : bool
    {
        $sql = <<<'SQL'
        UPDATE
            `books`
        SET
            `is_deleted` = 1
        WHERE
            `id` = :id AND
            `is_deleted` = 0
        SQL;

        $query = $this->db->prepare($sql);
        $query->execute([
            ':id' => $id,
        ]);

        return $query->rowCount() > 0;
    }

    /**
     * Restore deleted book
     *
     * @param int $id - book ID to restore
     *
     * @return bool - true if book restored
     *
     * @throws PDOException
     */
    public function restoreBook(int $id) : bool
    {
        $sql = <<<'SQL'
        UPDATE
            `books`
        SET
            `is_deleted` = 0
        WHERE
            `id` = :id AND
            `is_deleted` = 1
        SQL;

        $query = $this->db->prepare($sql);
        $query->execute([
            ':id' => $id,
        ]);

        return $query->rowCount() > 0;
    }

    /**
     * Searches books in external APIs (Google Books, MIF)
     *
     * @param string $query  - search query string
     * @param string $source - google or mif
     *
     * @return array - list of books
     *
     * @throws Exception
     */
    public function searchExternalBooks(string $query, string $source) : array
    {
        $config = match ($source) {
            'google' => [
                'url' => 'https://www.googleapis.com/books/v1/volumes',
                'query_key' => 'q',
                'items_key' => 'items',
                'book_in' => function ($item) {
                    $volumeInfo = $item['volumeInfo'] ?? [];
                    return [
                        'id' => $item['id'] ?? '',
                        'title' => $volumeInfo['title'] ?? '',
                        'url' => $volumeInfo['canonicalVolumeLink'] ?? '',
                    ];
                },
            ],
            'mif' => [
                'url' => 'https://www.mann-ivanov-ferber.ru/book/search.ajax',
                'query_key' => 'q',
                'items_key' => 'books',
                'book_in' => function ($book) {
                    return [
                        'id' => $book['id'] ?? '',
                        'title' => $book['title'] ?? '',
                        'url' => $book['url'] ?? '',
                    ];
                },
            ],
            default => throw new Exception('Source must be "google" or "mif"', 400)
        };

        $response = $this->httpClient->get($config['url'], [
            'query' => [$config['query_key'] => urlencode($query)],
        ]);

        $body = $response->getBody()->getContents();
        $data = json_decode($body, true);

        $books = [];

        if (isset($data[$config['items_key']]) && is_array($data[$config['items_key']])) {
            foreach ($data[$config['items_key']] as $item) {
                $books[] = $config['book_in']($item);
            }
        }

        return $books;
    }
}
