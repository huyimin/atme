<?php
require_once('../../m/DB.php');

class CRUD {
    private $db;

    public function __construct() {
        $this->db = new DB();
    }

    public function create($table, $data) {
        $keys = implode(", ", array_keys($data));
        $values = implode("', '", array_values($data));
        $sql = "INSERT INTO $table ($keys) VALUES ('$values')";
        $this->db->query($sql);
        return $this->db->getLastId();
    }

    public function read($table) {
        $sql = "SELECT * FROM $table";
        $result = $this->db->query($sql);
        return $result->rows;
    }

    public function readOne($table, $id) {
        $id = intval($id);
        $sql = "SELECT * FROM $table WHERE id = $id";
        $result = $this->db->query($sql);
        return $result->row;
    }

    public function update($table, $data, $id) {
        $id = intval($id);
        $updates = array_map(function ($value, $key) {
            return "$key = '" . $this->db->escape($value) . "'";
        }, array_values($data), array_keys($data));

        $updates = implode(", ", $updates);
        $sql = "UPDATE $table SET $updates WHERE id = $id";
        $this->db->query($sql);
    }

    public function delete($table, $id) {
        $id = intval($id);
        $sql = "DELETE FROM $table WHERE id = $id";
        $this->db->query($sql);
    }
}

?>