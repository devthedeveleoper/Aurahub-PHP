<?php
namespace App\Controllers;
use Core\Controller;
use PDO;

class HomeController extends Controller {
    public function index() {
        // Move logic from original index.php
        $q = trim($_GET['q'] ?? '');
        $creatorFilter = trim($_GET['creator'] ?? '');
        $sort = $_GET['sort'] ?? 'newest';
        if (!in_array($sort, ['newest', 'popular', 'oldest'], true)) $sort = 'newest';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $per = 12; $off = ($page - 1) * $per;
        $videoModel = new \App\Models\VideoModel();
        $videos = $videoModel->getFeed($q, $creatorFilter, $sort, $per, $off);
        $hasMore = count($videos) > $per;
        if ($hasMore) array_pop($videos);

        // Pass variables to view
        $this->view('home/index', [
            'q' => $q,
            'creatorFilter' => $creatorFilter,
            'sort' => $sort,
            'page' => $page,
            'videos' => $videos,
            'hasMore' => $hasMore
        ]);
    }
}
