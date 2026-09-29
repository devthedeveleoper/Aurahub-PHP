<?php
namespace App\Controllers;
use Core\Controller;
use PDO;

class AccountController extends Controller {
    
    public function index() {
        require_login();
        $me = user();
        $error = null;
        $success = ($_GET['updated'] ?? '') === '1';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_check();
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            $userModel = new \App\Models\UserModel();
            $passwordHash = $userModel->findPasswordHash($me['id']);

            if (!$passwordHash || !password_verify($currentPassword, $passwordHash)) {
                $error = 'Current password is incorrect.';
            } elseif (strlen($newPassword) < 8) {
                $error = 'New password must be at least 8 characters.';
            } elseif ($newPassword !== $confirmPassword) {
                $error = 'New password and confirmation do not match.';
            } else {
                $userModel->updatePassword($me['id'], $newPassword);
                session_regenerate_id(true);
                redirect('/aurahub/public/account?updated=1');
            }
        }

        $this->view('account/settings', [
            'me' => $me,
            'success' => $success,
            'error' => $error
        ]);
    }

    public function history() {
        require_login();
        $me = user();
        $videoModel = new \App\Models\VideoModel();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_check();
            $action = $_POST['action'] ?? '';
            if ($action === 'clear') {
                $videoModel->clearHistory($me['id']);
            } elseif ($action === 'remove') {
                $videoModel->removeFromHistory($me['id'], (int)($_POST['video_id'] ?? 0));
            }
            redirect('/aurahub/public/history');
        }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 24;
        $total = $videoModel->countHistory($me['id']);
        $pages = max(1, (int)ceil($total / $perPage));
        $page = min($page, $pages);
        $offset = ($page - 1) * $perPage;
        
        $videos = $videoModel->getHistory($me['id'], $perPage, $offset);

        $this->view('account/history', [
            'total' => $total,
            'videos' => $videos,
            'page' => $page,
            'pages' => $pages
        ]);
    }

    public function watchLater() {
        require_login();
        $me = user();
        $videoModel = new \App\Models\VideoModel();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_check();
            $videoId = (int)($_POST['video_id'] ?? 0);
            $videoModel->removeFromWatchLater($me['id'], $videoId);
            redirect('/aurahub/public/watch_later');
        }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 24;
        $total = $videoModel->countWatchLater($me['id']);
        $pages = max(1, (int)ceil($total / $perPage));
        $page = min($page, $pages);
        $offset = ($page - 1) * $perPage;

        $videos = $videoModel->getWatchLater($me['id'], $perPage, $offset);

        $this->view('account/watch_later', [
            'videos' => $videos,
            'page' => $page,
            'pages' => $pages
        ]);
    }

    public function analytics() {
        require_login();
        $me = user();
        
        $videoModel = new \App\Models\VideoModel();
        $stats = $videoModel->getAnalyticsSummary($me['id']);
        $videos = $videoModel->getAnalyticsVideos($me['id'], 100);

        $this->view('account/analytics', [
            'stats' => $stats,
            'videos' => $videos
        ]);
    }

    public function reports() {
        require_login();
        $me = user();
        
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 25;
        $reportModel = new \App\Models\ReportModel();
        $total = $reportModel->countUserReports($me['id']);
        $pages = max(1, (int)ceil($total / $perPage));
        $page = min($page, $pages);
        $offset = ($page - 1) * $perPage;

        $reports = $reportModel->getUserReports($me['id'], $perPage, $offset);

        $this->view('account/reports', [
            'reports' => $reports,
            'page' => $page,
            'pages' => $pages
        ]);
    }

    public function channel() {
        $username = trim($_GET['u'] ?? $_POST['u'] ?? '');
        $me = user();
        
        $userModel = new \App\Models\UserModel();
        $creator = $userModel->findByUsername($username);
        
        if (!$creator) {
            http_response_code(404);
            $this->view('account/channel_not_found');
            return;
        }

        $isOwner = $me && (int)$me['id'] === (int)$creator['id'];
        $bioError = null;
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_bio') {
            csrf_check();
            if (!$isOwner) {
                http_response_code(403);
                exit('Only the channel owner can edit this bio.');
            }
            $bio = trim($_POST['bio'] ?? '');
            if (mb_strlen($bio) > 1000) {
                $bioError = 'Bio must be 1,000 characters or fewer.';
            } else {
                $userModel->updateBio($creator['id'], $bio);
                redirect('/aurahub/public/channel?u=' . rawurlencode($creator['username']));
            }
        }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 24;
        $videoModel = new \App\Models\VideoModel();
        $total = $videoModel->countVideos($creator['username']);
        $pages = max(1, (int)ceil($total / $perPage));
        $page = min($page, $pages);
        $offset = ($page - 1) * $perPage;

        $videos = $videoModel->getRecentVideos($perPage, $offset, $creator['username']);
        
        $playlistModel = new \App\Models\PlaylistModel();
        $publicPlaylists = $playlistModel->getPublicPlaylistsForUser($creator['id']);

        $this->view('account/channel', [
            'creator' => $creator,
            'isOwner' => $isOwner,
            'bioError' => $bioError,
            'videos' => $videos,
            'publicPlaylists' => $publicPlaylists,
            'total' => $total,
            'page' => $page,
            'pages' => $pages
        ]);
    }
}
