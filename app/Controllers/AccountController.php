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
            $action = $_POST['action'] ?? 'password';
            $userModel = new \App\Models\UserModel();
            
            if ($action === 'avatar') {
                $avatar = $_FILES['avatar'] ?? null;
                if ($avatar && $avatar['error'] !== UPLOAD_ERR_NO_FILE) {
                    if ($avatar['error'] !== UPLOAD_ERR_OK) {
                        $error = 'Upload error: ' . upload_error_message((int)$avatar['error']);
                    } elseif ($avatar['size'] > MAX_IMAGE_MB * 1048576) {
                        $error = 'Image is larger than ' . MAX_IMAGE_MB . ' MB.';
                    } else {
                        $fi = new \finfo(FILEINFO_MIME_TYPE);
                        $mime = $fi->file($avatar['tmp_name']) ?: '';
                        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true)) {
                            $error = 'Image must be a JPG, PNG, WebP or GIF.';
                        } else {
                            $avatarUrl = freeimage_upload($avatar['tmp_name'], $avatar['name'], $mime);
                            if ($avatarUrl) {
                                $userModel->updateAvatarUrl($me['id'], $avatarUrl);
                                $_SESSION['user']['avatar_url'] = $avatarUrl;
                                redirect('/aurahub/public/account?updated=1');
                            } else {
                                $error = 'Failed to upload image to host.';
                            }
                        }
                    }
                } else {
                    $error = 'Please select an image file.';
                }
            } elseif ($action === 'privacy') {
                $keepHistory = isset($_POST['keep_history']) ? 1 : 0;
                $userModel->updateKeepHistory($me['id'], $keepHistory);
                $_SESSION['user']['keep_history'] = $keepHistory;
                redirect('/aurahub/public/account?updated=1');
            } else {
                $currentPassword = $_POST['current_password'] ?? '';
                $newPassword = $_POST['new_password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';

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

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 24;
        $videoModel = new \App\Models\VideoModel();
        
        // RSS Feed Output
        if (($_GET['format'] ?? '') === 'rss') {
            header('Content-Type: application/rss+xml; charset=utf-8');
            $rssVideos = $videoModel->getRecentVideos(50, 0, $creator['username'], false);
            echo '<?xml version="1.0" encoding="UTF-8" ?>';
            echo '<rss version="2.0"><channel>';
            echo '<title>' . e($creator['username']) . ' on Aurahub</title>';
            echo '<link>' . e('https://' . $_SERVER['HTTP_HOST'] . '/aurahub/public/channel?u=' . rawurlencode($creator['username'])) . '</link>';
            echo '<description>' . e($creator['bio']) . '</description>';
            foreach ($rssVideos as $v) {
                echo '<item>';
                echo '<title>' . e($v['title']) . '</title>';
                echo '<link>' . e('https://' . $_SERVER['HTTP_HOST'] . '/aurahub/public/watch?id=' . $v['id']) . '</link>';
                echo '<pubDate>' . date(DATE_RSS, strtotime($v['created_at'])) . '</pubDate>';
                echo '<guid>' . e('https://' . $_SERVER['HTTP_HOST'] . '/aurahub/public/watch?id=' . $v['id']) . '</guid>';
                echo '</item>';
            }
            echo '</channel></rss>';
            exit;
        }

        $isSubscribed = false;
        if ($me && !$isOwner) {
            $isSubscribed = $userModel->isSubscribed($me['id'], $creator['id']);
        }
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
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_subscription') {
            csrf_check();
            if (!$me) {
                redirect('/aurahub/public/login');
            }
            if (!$isOwner) {
                if ($isSubscribed) {
                    $userModel->unsubscribe($me['id'], $creator['id']);
                } else {
                    $userModel->subscribe($me['id'], $creator['id']);
                }
            }
            redirect('/aurahub/public/channel?u=' . rawurlencode($creator['username']));
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'pin_video') {
            csrf_check();
            if (!$isOwner) {
                http_response_code(403);
                exit('Only the channel owner can pin videos.');
            }
            $videoId = (int)($_POST['video_id'] ?? 0);
            if ($videoId === 0) $videoId = null;
            $userModel->updatePinnedVideo($creator['id'], $videoId);
            redirect('/aurahub/public/channel?u=' . rawurlencode($creator['username']));
        }

        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 24;
        $videoModel = new \App\Models\VideoModel();
        $pinnedVideo = null;
        if (!empty($creator['pinned_video_id'])) {
            $pinnedVideo = $videoModel->getVideoById($creator['pinned_video_id']);
            if ($pinnedVideo && $pinnedVideo['visibility'] === 'private' && !$isOwner) {
                $pinnedVideo = null; // hide if private
            }
        }

        $total = $videoModel->countVideos($creator['username'], $isOwner);
        $pages = max(1, (int)ceil($total / $perPage));
        $page = min($page, $pages);
        $offset = ($page - 1) * $perPage;

        $videos = $videoModel->getRecentVideos($perPage, $offset, $creator['username'], $isOwner);
        
        $playlistModel = new \App\Models\PlaylistModel();
        $publicPlaylists = $playlistModel->getPublicPlaylistsForUser($creator['id']);

        $this->view('account/channel', [
            'creator' => $creator,
            'isOwner' => $isOwner,
            'isSubscribed' => $isSubscribed,
            'bioError' => $bioError,
            'pinnedVideo' => $pinnedVideo,
            'videos' => $videos,
            'publicPlaylists' => $publicPlaylists,
            'total' => $total,
            'page' => $page,
            'pages' => $pages
        ]);
    }

    public function export() {
        require_login();
        $me = user();
        
        $userModel = new \App\Models\UserModel();
        $videoModel = new \App\Models\VideoModel();
        
        $exportData = [
            'user' => [
                'username' => $me['username'],
                'created_at' => $me['created_at'],
                'bio' => $me['bio']
            ],
            'videos' => $videoModel->getAnalyticsVideos($me['id'], 1000)
        ];

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="aurahub_export_' . e($me['username']) . '_' . date('Y-m-d') . '.json"');
        echo json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
