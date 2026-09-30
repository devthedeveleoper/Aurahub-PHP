<?php
namespace App\Controllers;
use Core\Controller;
use PDOException;

class AuthController extends Controller {
    public function login() {
        if (user()) redirect('/aurahub/public/');

        $err = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_check();
            $id = trim($_POST['identity'] ?? '');
            $userModel = new \App\Models\UserModel();
            $row = $userModel->findByCredentials($id);
            $passwordIsValid = $row && password_verify($_POST['password'] ?? '', $row['password_hash']);
            
            if ($passwordIsValid && $row['account_status'] !== 'active') {
                $err = 'This account is suspended. Contact an administrator.';
            } elseif ($passwordIsValid) {
                session_regenerate_id(true);
                $_SESSION['uid'] = (int)$row['id'];
                redirect('/aurahub/public/');
            } else {
                $err = 'Wrong email/username or password.';
            }
        }
        
        $this->view('auth/login', ['err' => $err]);
    }

    public function register() {
        if (user()) redirect('/aurahub/public/');

        $err = null; 
        $f = ['username' => '', 'email' => ''];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_check();
            $f['username'] = trim($_POST['username'] ?? '');
            $f['email']    = trim($_POST['email'] ?? '');
            $pass          = $_POST['password'] ?? '';
            
            if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $f['username'])) {
                $err = 'Username must be 3–30 letters, numbers or underscores.';
            } elseif ($f['email'] !== '' && !filter_var($f['email'], FILTER_VALIDATE_EMAIL)) {
                $err = 'Enter a valid email address.';
            } elseif (strlen($pass) < 8) {
                $err = 'Password must be at least 8 characters.';
            } else {
                try {
                    $userModel = new \App\Models\UserModel();
                    $newId = $userModel->create($f['username'], $f['email'], $pass);
                    session_regenerate_id(true);
                    $_SESSION['uid'] = $newId;
                    redirect('/aurahub/public/');
                } catch (PDOException $ex) {
                    $err = $ex->getCode() === '23000' ? 'That username or email is already taken.' : 'Could not create account.';
                }
            }
        }
        
        $this->view('auth/register', ['err' => $err, 'f' => $f]);
    }

    public function logout() {
        session_destroy();
        redirect('/aurahub/public/');
    }
}
