<?php
class UserController {
    private $userModel;

    public function __construct(User $userModel) {
        $this->userModel = $userModel;
    }

    public function getUser($id) {
        return $this->userModel->getUser($id);
    }

    public function login($username, $password) {
        return $this->userModel->checkCredentials($username, $password);
    }
}