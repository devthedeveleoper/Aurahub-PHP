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
        $s->execute([$username, strtolower($email), password_hash($password, PASSWORD_DEFAULT)]);
        return (int)static::db()->lastInsertId();
    }
    public function findByUsername($username) {
        $s = static::db()->prepare('SELECT id, username, bio, created_at FROM users WHERE username = ?');
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
                                    GROUP BY u.id ORDER BY u.created_at DESC LIMIT $limit")->fetchAll();
    }
}
