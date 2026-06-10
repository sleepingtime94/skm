<?php

namespace App\Controllers;

use Dotenv\Dotenv;
use App\Utility\Security;

class AuthController
{

    public function __construct()
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->load();
    }

    public function logout()
    {
        Security::verifyCsrf();
        session_destroy();
        header('location: /');
        exit();
    }

    public function authenticate()
    {
        if (!isset($_SESSION['authToken'])) {
            header('location: /login');
            exit();
        }
    }

    public function logged()
    {
        if (isset($_SESSION['authToken'])) {
            header('location: /dashboard');
            exit();
        }
    }

    public function login()
    {
        // Rate limiting: maks 5 percobaan per 60 detik
        Security::rateLimit('login', 5, 60);

        // Verifikasi CSRF token
        Security::verifyCsrf();

        $body = (object) $_POST;
        $pass = $body->password ?? '';

        if (hash_equals($_ENV['PW_ADMIN'], $pass)) {
            // Cegah session fixation: regenerasi ID session setelah login
            session_regenerate_id(true);
            $_SESSION['authToken'] = bin2hex(random_bytes(32));
            $_SESSION['auth_time'] = time();
            header('location: /dashboard');
            exit();
        } else {
            header('location: /login');
            exit();
        }
    }
}
