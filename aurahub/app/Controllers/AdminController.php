<?php
namespace App\Controllers;
use Core\Controller;
use PDO;

class AdminController extends Controller {

    public function users() {
        $admin = require_admin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_check();
            $userId = (int)($_POST['user_id'] ?? 0);
            $action = $_POST['action'] ?? '';
            $status = $action === 'suspend' ? 'suspended' : ($action === 'reactivate' ? 'active' : null);
            $userModel = new \App\Models\UserModel();
            if ($status === 'suspended' && $userId !== (int)$admin['id']) {
                $userModel->suspendUser($userId);
            } elseif ($status === 'active' && $userId !== (int)$admin['id']) {
                $userModel->reactivateUser($userId);
            }
            redirect('/aurahub/public/admin_users');
        }

        $userModel = new \App\Models\UserModel();
        $users = $userModel->getUsersWithVideoCount(200);

        $this->view('admin/users', ['users' => $users]);
    }

    public function moderation() {
        $admin = require_admin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_check();
            $reportId = (int)($_POST['report_id'] ?? 0);
            $action = $_POST['action'] ?? '';
            $moderatorNote = trim($_POST['moderator_note'] ?? '');
            if (mb_strlen($moderatorNote) > 1000) $moderatorNote = mb_substr($moderatorNote, 0, 1000);
            
            $reportModel = new \App\Models\ReportModel();
            
            if (in_array($action, ['resolved', 'dismissed'], true)) {
                if ($action === 'resolved') {
                    $reportModel->resolveReport($reportId, $moderatorNote, $admin['id']);
                } else {
                    $reportModel->dismissReport($reportId, $moderatorNote, $admin['id']);
                }
            } elseif ($action === 'remove_video') {
                $video = $reportModel->getReportVideoDetails($reportId);
                if ($video && $video['video_id'] !== null) {
                    $note = $moderatorNote !== '' ? $moderatorNote : 'Video removed by moderation.';
                    $reportModel->removeVideoFromReport($reportId, $video['title'], $note, $admin['id'], $video['video_id']);
                    
                    $videoModel = new \App\Models\VideoModel();
                    $videoModel->deleteVideo($video['video_id']);
                    if ($video['stream_id']) wrapper_delete($video['stream_id']);
                }
            }
            redirect('/aurahub/public/moderation');
        }

        $filter = $_GET['status'] ?? 'pending';
        if (!in_array($filter, ['pending', 'resolved', 'dismissed', 'all'], true)) $filter = 'pending';
        $reportModel = new \App\Models\ReportModel();
        $reports = $reportModel->getAdminReports($filter, 100, 0);

        $this->view('admin/moderation', [
            'reports' => $reports,
            'filter' => $filter
        ]);
    }
}
