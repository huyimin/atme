<?php
class User {
    private $db;

    public function __construct(DB $db) {
        $this->db = $db;
    }

    public function getUser($id) {
        $id = $this->db->escape($id);
        return $this->db->query("SELECT * FROM users WHERE id = $id")->row;
    }

    public function checkCredentials($username, $password) {
        $username = $this->db->escape($username);
        $password = $this->db->escape($password); // consider hashing password
        return $this->db->query("SELECT * FROM users WHERE username = '$username' AND password = '$password'")->row;
    }
}