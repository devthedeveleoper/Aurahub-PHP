<?php
namespace App\Models;
use Core\Model;
use PDOException;

class UserModel extends Model {

    public function findByCredentials($identity) {
        $s = static::db()->prepare('SELECT id, password_hash, account_status FROM users WHERE email = ? OR username = ? LIMIT 1');
        $s->execute([strtolower($identity), $identity]);
        return $s->fetch();
    }

    public function create($username, $email, $password) {
        $s = static::db()->prepare('INSERT INTO users (username, email, password_hash) VALUES (?,?,?)');
        $s->execute([$username, $email === '' ? null : strtolower($email), password_hash($password, PASSWORD_DEFAULT)]);
        return (int)static::db()->lastInsertId();
    }
    public function findByUsername($username) {
        $s = static::db()->prepare('SELECT id, username, bio, avatar_url, created_at FROM users WHERE username = ?');
        $s->execute([$username]);
        return $s->fetch();
    }

    public function findPasswordHash($userId) {
        $st = static::db()->prepare('SELECT password_hash FROM users WHERE id = ?');
        $st->execute([$userId]);
        return $st->fetchColumn();
    }

    public function updatePassword($userId, $password) {
        return static::db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($password, PASSWORD_DEFAULT), $userId]);
    }

    public function updateBio($userId, $bio) {
        return static::db()->prepare('UPDATE users SET bio = ? WHERE id = ?')
            ->execute([$bio, $userId]);
    }

    public function updateAvatarUrl($userId, $avatarUrl) {
        return static::db()->prepare('UPDATE users SET avatar_url = ? WHERE id = ?')
            ->execute([$avatarUrl, $userId]);
    }

    public function updateKeepHistory($userId, $keepHistory) {
        return static::db()->prepare('UPDATE users SET keep_history = ? WHERE id = ?')
            ->execute([(int)$keepHistory, $userId]);
    }

    public function updatePinnedVideo($userId, $videoId) {
        return static::db()->prepare('UPDATE users SET pinned_video_id = ? WHERE id = ?')
            ->execute([$videoId, $userId]);
    }

    public function suspendUser($userId) {
        return static::db()->prepare("UPDATE users SET account_status = 'suspended' WHERE id = ? AND role = 'user'")
            ->execute([$userId]);
    }

    public function reactivateUser($userId) {
        return static::db()->prepare("UPDATE users SET account_status = 'active' WHERE id = ? AND role = 'user'")
            ->execute([$userId]);
    }

    public function getUsersWithVideoCount($limit = 200) {
        return static::db()->query("SELECT u.id, u.username, u.email, u.account_status, u.created_at,
                                     COUNT(DISTINCT v.id) AS video_count
                                    FROM users u LEFT JOIN videos v ON v.user_id = u.id
                                    WHERE u.role = 'user'
                                    GROUP BY u.id ORDER BY u.created_at DESC LIMIT " . (int)$limit)->fetchAll();
    }

    public function isSubscribed($subscriberId, $creatorId) {
        $st = static::db()->prepare('SELECT 1 FROM subscriptions WHERE subscriber_id = ? AND creator_id = ?');
        $st->execute([$subscriberId, $creatorId]);
        return (bool)$st->fetchColumn();
    }

    public function subscribe($subscriberId, $creatorId) {
        return static::db()->prepare('INSERT IGNORE INTO subscriptions (subscriber_id, creator_id) VALUES (?, ?)')
            ->execute([$subscriberId, $creatorId]);
    }

    public function unsubscribe($subscriberId, $creatorId) {
        return static::db()->prepare('DELETE FROM subscriptions WHERE subscriber_id = ? AND creator_id = ?')
            ->execute([$subscriberId, $creatorId]);
    }
}
