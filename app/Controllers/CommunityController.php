<?php
namespace App\Controllers;
use Core\Controller;
use RuntimeException;

class CommunityController extends Controller {
    
    public function index() {
        $communityModel = new \App\Models\CommunityModel();
        $me = user();
        
        $myCommunities = [];
        if ($me) {
            $myCommunities = $communityModel->getUserCommunities($me['id']);
        }
        
        $publicCommunities = $communityModel->getPublicCommunities(24, 0);

        $this->view('communities/index', [
            'myCommunities' => $myCommunities,
            'publicCommunities' => $publicCommunities,
            'me' => $me
        ]);
    }

    public function create() {
        require_login();
        $me = user();
        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_check();
            $name = trim($_POST['name'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            $visibility = $_POST['visibility'] ?? 'public';

            if ($name === '' || mb_strlen($name) > 100) {
                $error = 'Name must be between 1 and 100 characters.';
            } elseif (mb_strlen($desc) > 1000) {
                $error = 'Description cannot exceed 1,000 characters.';
            } elseif (!in_array($visibility, ['public', 'private'])) {
                $visibility = 'public';
            }

            if (!$error) {
                $communityModel = new \App\Models\CommunityModel();
                $id = $communityModel->createCommunity($name, $desc, $me['id'], $visibility);
                redirect('/aurahub/public/community?id=' . $id);
            }
        }

        $this->view('communities/create', ['error' => $error]);
    }

    public function viewCommunity() {
        $id = (int)($_GET['id'] ?? 0);
        $communityModel = new \App\Models\CommunityModel();
        $community = $communityModel->getCommunityById($id);

        if (!$community) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        $me = user();
        $role = $me ? $communityModel->isMember($id, $me['id']) : false;

        // Private communities require membership
        if ($community['visibility'] === 'private' && !$role && (!$me || $community['creator_id'] !== $me['id'])) {
            http_response_code(403);
            $this->view('errors/404'); // Or a dedicated access denied view
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_check();
            require_login();
            $action = $_POST['action'] ?? '';
            
            if ($action === 'join') {
                $communityModel->joinCommunity($id, $me['id']);
                redirect('/aurahub/public/community?id=' . $id);
            } elseif ($action === 'leave') {
                $communityModel->leaveCommunity($id, $me['id']);
                redirect('/aurahub/public/communities');
            } elseif ($action === 'post' && $role) {
                $body = trim($_POST['body'] ?? '');
                $videoId = (int)($_POST['video_id'] ?? 0);
                if ($videoId === 0) $videoId = null;

                if ($body !== '' || $videoId !== null) {
                    $communityModel->createPost($id, $me['id'], $body, $videoId);
                }
                redirect('/aurahub/public/community?id=' . $id);
            }
        }

        $posts = $communityModel->getPosts($id);

        $this->view('communities/view', [
            'community' => $community,
            'role' => $role,
            'posts' => $posts,
            'me' => $me
        ]);
    }
}
