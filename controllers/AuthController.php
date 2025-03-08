<?php
session_start();
require_once '../models/UserModel.php';

class AuthController {
    private $userModel;

    public function __construct($database) {
        $this->userModel = new UserModel($database);
    }

    public function login($username, $password) {
        $user = $this->userModel->getUserByUsername($username);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            header("Location: /dashboard.php");
            exit();
        } else {
            echo "Invalid username or password";
        }
    }

    public function logout() {
        session_destroy();
        header("Location: /login.php");
        exit();
    }
}
?>