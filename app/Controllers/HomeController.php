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
        $category = (int)($_GET['category'] ?? 0);
        $page = max(1, (int)($_GET['page'] ?? 1));
        $per = 12; $off = ($page - 1) * $per;
        $videoModel = new \App\Models\VideoModel();
        $videos = $videoModel->getFeed($q, $creatorFilter, $category, $sort, $per, $off);
        $categories = $videoModel->getCategories();
        $hasMore = count($videos) > $per;
        if ($hasMore) array_pop($videos);

        // Pass variables to view
        $this->view('home/index', [
            'q' => $q,
            'creatorFilter' => $creatorFilter,
            'category' => $category,
            'sort' => $sort,
            'page' => $page,
            'videos' => $videos,
            'categories' => $categories,
            'hasMore' => $hasMore
        ]);
    }

    public function subscriptions() {
        require_login();
        $me = user();
        
        $page = max(1, (int)($_GET['page'] ?? 1));
        $per = 12; $off = ($page - 1) * $per;
        $videoModel = new \App\Models\VideoModel();
        $videos = $videoModel->getSubscriptionsFeed($me['id'], $per, $off);
        $hasMore = count($videos) > $per;
        if ($hasMore) array_pop($videos);

        $this->view('home/subscriptions', [
            'page' => $page,
            'videos' => $videos,
            'hasMore' => $hasMore
        ]);
    }

    public function policy() {
        $this->view('pages/policy', []);
    }

    public function transparency() {
        $reportModel = new \App\Models\ReportModel();
        $stats = $reportModel->getTransparencyStats();
        $this->view('pages/transparency', ['stats' => $stats]);
    }
}
