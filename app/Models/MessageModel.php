<?php
namespace App\Models;
use Core\Model;

class MessageModel extends Model {

    public function sendMessage($senderId, $recipientId, $body, $videoId = null) {
        return static::db()->prepare('INSERT INTO direct_messages (sender_id, recipient_id, body, video_id) VALUES (?,?,?,?)')
            ->execute([$senderId, $recipientId, $body, $videoId]);
    }

    public function getConversations($userId) {
        // Get the latest message for each conversation
        $st = static::db()->prepare("
            SELECT 
                u.id as other_user_id, u.username as other_username, u.avatar_url,
                m.body as last_message, m.created_at as last_message_date,
                m.sender_id,
                (SELECT COUNT(*) FROM direct_messages WHERE recipient_id = ? AND sender_id = u.id AND read_at IS NULL) as unread_count
            FROM users u
            JOIN direct_messages m ON (m.sender_id = u.id AND m.recipient_id = ?) OR (m.sender_id = ? AND m.recipient_id = u.id)
            WHERE m.id = (
                SELECT MAX(id) FROM direct_messages 
                WHERE (sender_id = u.id AND recipient_id = ?) OR (sender_id = ? AND recipient_id = u.id)
            )
            ORDER BY m.created_at DESC
        ");
        $st->execute([$userId, $userId, $userId, $userId, $userId]);
        return $st->fetchAll();
    }

    public function getMessages($userId1, $userId2, $limit = 50) {
        $st = static::db()->prepare("
            SELECT m.*, v.title as video_title, v.thumbnail_url as video_thumbnail
            FROM direct_messages m
            LEFT JOIN videos v ON v.id = m.video_id
            WHERE (m.sender_id = ? AND m.recipient_id = ?) OR (m.sender_id = ? AND m.recipient_id = ?)
            ORDER BY m.created_at DESC
            LIMIT " . (int)$limit
        );
        $st->execute([$userId1, $userId2, $userId2, $userId1]);
        $messages = $st->fetchAll();
        return array_reverse($messages); // Return chronological order
    }

    public function markAsRead($senderId, $recipientId) {
        static::db()->prepare('UPDATE direct_messages SET read_at = CURRENT_TIMESTAMP WHERE sender_id = ? AND recipient_id = ? AND read_at IS NULL')
            ->execute([$senderId, $recipientId]);
    }
    
    public function getUnreadCount($userId) {
        $st = static::db()->prepare('SELECT COUNT(*) FROM direct_messages WHERE recipient_id = ? AND read_at IS NULL');
        $st->execute([$userId]);
        return (int)$st->fetchColumn();
    }
}
