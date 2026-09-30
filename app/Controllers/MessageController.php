<?php
namespace App\Controllers;
use Core\Controller;

class MessageController extends Controller {

    public function index() {
        require_login();
        $me = user();
        
        $messageModel = new \App\Models\MessageModel();
        $conversations = $messageModel->getConversations($me['id']);

        $this->view('messages/index', [
            'me' => $me,
            'conversations' => $conversations
        ]);
    }

    public function chat() {
        require_login();
        $me = user();
        
        $username = trim($_GET['u'] ?? '');
        $userModel = new \App\Models\UserModel();
        $otherUser = $userModel->findByUsername($username);

        if (!$otherUser || $otherUser['id'] === $me['id']) {
            redirect('/aurahub/public/messages');
        }

        $messageModel = new \App\Models\MessageModel();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_check();
            $body = trim($_POST['body'] ?? '');
            $videoId = (int)($_POST['video_id'] ?? 0);
            if ($videoId === 0) $videoId = null;

            if ($body !== '' || $videoId !== null) {
                $messageModel->sendMessage($me['id'], $otherUser['id'], $body, $videoId);
            }
            redirect('/aurahub/public/messages/chat?u=' . rawurlencode($otherUser['username']));
        }

        // Mark messages from the other user as read
        $messageModel->markAsRead($otherUser['id'], $me['id']);

        $messages = $messageModel->getMessages($me['id'], $otherUser['id']);

        $this->view('messages/chat', [
            'me' => $me,
            'otherUser' => $otherUser,
            'messages' => $messages
        ]);
    }
}
