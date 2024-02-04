<?php
class DB {
    public $link;

    public function __construct($server="localhost",$user="root",$pass="tt1201",$database="ulikeqip") {
        $this->link = new mysqli($server,$user,$pass,$database);

        if ($this->link->connect_error) {
            trigger_error('Error: Could not make a database link (' . $this->link->connect_errno . ') ' . $this->link->connect_error);
            exit();
        }

        $this->link->set_charset("utf8");
        $this->link->query("SET SQL_MODE = ''");
    }

    public function query($sql) {
        $query = $this->link->query($sql);

        if (!$this->link->errno) {
            if ($query instanceof mysqli_result) {
                $data = array();

                while ($row = $query->fetch_assoc()) {
                    $data[] = $row;
                }

                $result = new stdClass();
                $result->num_rows = $query->num_rows;
                $result->row = isset($data[0]) ? $data[0] : array();
                $result->rows = $data;

                $query->close();

                return $result;
            } else {
                return true;
            }
        } else {
            trigger_error('Error: ' . $this->link->error  . '<br />Error No: ' . $this->link->errno . '<br />' . $sql);
        }
    }

    public function escape($value) {
        return $this->link->real_escape_string($value);
    }

    public function countAffected() {
        return $this->link->affected_rows;
    }

    public function getLastId() {
        return $this->link->insert_id;
    }

    public function __destruct() {
        $this->link->close();
    }
}
?>
