<?php

namespace App\Controllers;

use Dotenv\Dotenv;

class AuthController
{

    public function __construct()
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->load();
    }

    public function logout()
    {
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
            header('location: /statistik');
            exit();
        }
    }

    public function login()
    {
        $body = (object) $_POST;
        $pass = $body->password;


        if ($pass == $_ENV['PW_ADMIN']) {
            $_SESSION['authToken'] = base64_encode(md5(date('YmdHis')));
            header('location: /statistik');
            exit();
        } else {
            header('location: /login');
            exit();
        }
    }
}
