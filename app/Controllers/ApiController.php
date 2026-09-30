<?php
namespace App\Controllers;
use Core\Controller;

class ApiController extends Controller {

    private function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        // Enable CORS for public API
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, OPTIONS');
        echo json_encode($data, JSON_UNESCAPED_SLASHES);
        exit;
    }

    private function errorResponse($message, $statusCode = 400) {
        $this->jsonResponse(['error' => $message], $statusCode);
    }

    public function videos() {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') $this->jsonResponse(['status' => 'ok']);
        
        $limit = max(1, min(50, (int)($_GET['limit'] ?? 20)));
        $page = max(1, (int)($_GET['page'] ?? 1));
        $offset = ($page - 1) * $limit;
        
        $videoModel = new \App\Models\VideoModel();
        // Uses getFeed with newest sort and public visibility
        $videos = $videoModel->getFeed('', '', 0, 'newest', $limit, $offset);

        // Remove the extra video fetched for pagination check
        $hasMore = count($videos) > $limit;
        if ($hasMore) array_pop($videos);

        $this->jsonResponse([
            'data' => $videos,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'has_more' => $hasMore
            ]
        ]);
    }

    public function video() {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') $this->jsonResponse(['status' => 'ok']);

        $id = (int)($_GET['id'] ?? 0);
        if ($id === 0) {
            $this->errorResponse('Video ID is required');
        }

        $videoModel = new \App\Models\VideoModel();
        $video = $videoModel->getVideoById($id);

        if (!$video || $video['visibility'] === 'private') {
            $this->errorResponse('Video not found or access denied', 404);
        }

        $this->jsonResponse(['data' => $video]);
    }

    public function channel() {
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') $this->jsonResponse(['status' => 'ok']);

        $username = trim($_GET['u'] ?? '');
        if ($username === '') {
            $this->errorResponse('Username is required');
        }

        $userModel = new \App\Models\UserModel();
        $user = $userModel->findByUsername($username);

        if (!$user) {
            $this->errorResponse('Channel not found', 404);
        }

        $limit = max(1, min(50, (int)($_GET['limit'] ?? 20)));
        $page = max(1, (int)($_GET['page'] ?? 1));
        $offset = ($page - 1) * $limit;

        $videoModel = new \App\Models\VideoModel();
        $videos = $videoModel->getRecentVideos($limit + 1, $offset, $username, false);

        $hasMore = count($videos) > $limit;
        if ($hasMore) array_pop($videos);

        $this->jsonResponse([
            'data' => [
                'channel' => [
                    'username' => $user['username'],
                    'bio' => $user['bio'],
                    'avatar_url' => $user['avatar_url'],
                    'created_at' => $user['created_at']
                ],
                'videos' => $videos
            ],
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'has_more' => $hasMore
            ]
        ]);
    }
}
