<?php

namespace App\Controller;

use App\Core\BaseController;
use App\Core\Database;
use Exception;
use PDO;
use PDOException;

class AuthController extends BaseController
{
    /**
     * Show login form
     */
    public function login(): void
    {
        $this->render('login');
    }

    /**
     * Show registration form
     */
    public function register(): void
    {
        $this->render('register');
    }

    /**
     * Handle form submission for registration
     */
    public function handleRegister(): void
    {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (empty($username) || empty($email) || empty($password)) {
            $this->render('register', ['error' => 'All fields are required.']);
            return;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->render('register', ['error' => 'Invalid email address.']);
            return;
        }
        if ($password !== $confirm) {
            $this->render('register', ['error' => 'Passwords do not match.']);
            return;
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        try {
            $db = Database::getConnection();
            pg_prepare($db, "user_exists", 'SELECT id FROM users WHERE username = $1 OR email = $2');
            $result = pg_execute($db, "user_exists", [$username, $email]);
            if (pg_num_rows($result) > 0) {
                $this->render('register', ['error' => 'Username or email already in use.']);
                return;
            }

            pg_prepare($db, "users_create", 'INSERT INTO users (username, email, password) VALUES ($1, $2, $3) RETURNING id');
            $result = pg_execute($db, "users_create", [$username, $email, $hash]);
            if (!!$result) {
                $result = pg_fetch_assoc($result);
                $_SESSION['user_id'] = $result["id"];
                $_SESSION['username'] = $username;
                header('Location: /');
                exit;
            }
        } catch (Exception $e) {
            $msg = str_contains($e->getMessage(), 'UNIQUE') ? 'Username or email already in use.' : 'Registration failed.';
            $this->render('register', ['error' => $msg]);
        }
    }

    /**
     * Handle login submission
     */
    public function handleLogin(): void
    {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        if (empty($email) || empty($password)) {
            $this->render('login', ['error' => 'Both fields are required.']);
            return;
        }
        $db = Database::getConnection();
        pg_prepare($db, "user_query", 'SELECT id, username, password FROM users WHERE email = $1');
        $result = pg_execute($db, "user_query", [$email]);
        $user = pg_fetch_assoc($result);
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header('Location: /');
            exit;
        }
        $this->render('login', ['error' => 'Invalid credentials.']);
    }

    /**
     * Logout the user
     */
    public function logout(): void
    {
        session_destroy();
        header('Location: /login');
        exit;
    }
}
