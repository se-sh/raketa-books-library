<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Auth;
use App\Models\Book;
use App\Models\LibraryAccess;
use Exception;
use PDO;

class BookController extends Controller
{
    private Auth $authModel;
    private Book $bookModel;
    private LibraryAccess $accessModel;

    /**
     * Create models: User, Auth, LibraryAccess
     *
     * @param PDO $db - database connection instance
     */
    public function __construct(PDO $db)
    {
        $this->bookModel = new Book($db);
        $this->authModel = new Auth($db);
        $this->accessModel = new LibraryAccess($db);
    }

    /**
     * Get list of books another user by ID
     *
     * @param array $params - route parameters containing user ID
     *
     * @throws Exception
     *
     * @return void
     */
    public function userBooks(array $params) : void
    {
        $userId = (int)$params['id'];

        if ($userId === 0) {
            throw new Exception('ID required', 400);
        }

        $user = $this->authModel->authenticate();

        $requestUserId = (int)$user['sub'];

        if ($userId !== $requestUserId) {
            $access = $this->accessModel->hasAccess($userId, $requestUserId);

            if (!$access) {
                throw new Exception('You have no access', 400);
            }
        }

        $books = $this->bookModel->getUserBooksByUser($userId);

        $this->json(['data' => $books], 200);
    }

    /**
     * Show all user books
     *
     * @return void
     */
    public function index() : void
    {
        $user = $this->authModel->authenticate();
        $userId = (int)$user['sub'];

        $books = $this->bookModel->getUserBooksByUser($userId);

        $this->json(['data' => $books], 200);
    }

    /**
     * Create book, source: from file or from user or from google\mif
     *
     * @throws Exception
     *
     * @return void
     */
    public function store() : void {
        $user = $this->authModel->authenticate();
        $userId = $user['sub'];
        $title = '';
        $text = '';
        $externalId = null;

        if (isset($_FILES['file'])) {
            // Book from file
            $title = $_POST['title'] ?? '';

            $text = $this->loadFile($_FILES['file']);
        } else {
            // Book from JSON
            $data = $this->getJsonInput();
            $title = $data['title'] ?? '';

            // Book from external source (google, mif)
            $externalId = $data['externalId'] ?? null;

            $text = match ($externalId !== null) {
                true => $data['url'] ?? '',
                false => $data['text'] ?? ''
            };
        }

        if (empty($title)) {
            throw new Exception('Title required',400);
        }

        $id = $this->bookModel->createBook($userId, $title, $text, $externalId);

        $this->json(['id' => $id], 201);
    }

    /**
     * Open book by ID
     *
     * @param array $params - route parameters containing book ID
     *
     * @throws Exception
     *
     * @return void
     */
    public function show(array $params) : void
    {
        $user = $this->authModel->authenticate();

        $bookId = (int)$params['id'];

        if ($bookId === 0) {
            throw new Exception('ID required', 400);
        }

        $book = $this->bookModel->getBookById($bookId);

        if ($book === null) {
            throw new Exception('Book not found', 404);
        }

        $requestUserId = (int)$user['sub'];

        if ($book['user_id'] !== $requestUserId) {
            $access = $this->accessModel->hasAccess($book['user_id'], $requestUserId);

            if (!$access) {
                throw new Exception('You have no access', 403);
            }
        }

        $this->json([
            'data' => [
                'title' => $book['title'],
                'text' => $book['text']
            ]
        ],200);
    }

    /**
     * Change book: update title or text
     *
     * @param array $params - route parameters containing book ID
     *
     * @throws Exception
     *
     * @return void
     */
    public function update(array $params) : void
    {
        $user = $this->authModel->authenticate();

        $data = $this->getJsonInput();
        $title = $data['title'] ?? '';
        $text = $data['text'] ?? '';

        if (empty($title)) {
            throw new Exception('Title required', 400);
        }

        $bookId = (int)$params['id'];

        if ($bookId === 0) {
            throw new Exception('ID required', 400);
        }

        $success = $this->bookModel->saveBook($bookId, $title, $text);

        if (!$success) {
            throw new Exception('Book not found', 404);
        }

        $this->json(['message' => 'Book updated'], 200);
    }

    /**
     * Soft delete book
     *
     * @param array $params - route parameters containing book ID
     *
     * @throws Exception
     *
     * @return void
     */
    public function destroy(array $params) : void
    {
        $bookId = (int)$params['id'];

        if ($bookId === 0) {
            throw new Exception('ID required', 400);
        }

        //$user = $this->authModel->authenticate();

        $success = $this->bookModel->softDelete($bookId);

        if (!$success) {
            throw new Exception('Book not found', 404);
        }

        $this->json(['message' => 'Book deleted'], 200);
    }

    /**
     * Restore delete book
     *
     * @param array $params - route parameters containing book ID
     *
     * @throws Exception
     *
     * @return void
     */
    public function restore(array $params) : void
    {
        $bookId = (int)$params['id'];

        if ($bookId === 0) {
            throw new Exception('ID required', 400);
        }

        //$user = $this->authModel->authenticate();

        $success = $this->bookModel->restoreBook($bookId);

        if (!$success) {
            throw new Exception('Book not found', 404);
        }

        $this->json(['message' => 'Book restored'], 200);
    }

    /**
     * Search Internet book: google or mif
     *
     * @throws Exception
     *
     * @return void
     */
    public function searchExternalBooks() : void
    {
        $source = $_GET['source'] ?? '';
        $query = $_GET['q'] ?? '';

        if (empty($source) || empty($query)) {
            throw new Exception('source and q parameters required', 400);
        }

        $books = $this->bookModel->searchExternalBooks($query, $source);

        $this->json(['data' => $books], 200);
    }
}
