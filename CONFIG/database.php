<?php
namespace Config;

class Database
{
    private $host = "localhost";
    private $dbname = "u316670446_uploads_projet";
    private $username = "u316670446_admin";
    private $password = "!b0GNPvWKN/";
    public $conn;
    public function connect()
    {
        $this->conn = null;
        try {
            $this->conn = new \PDO("mysql:host=" . $this->host . ";port=3306;dbname=" . $this->dbname, $this->username, $this->password);
            
            $this->conn->exec("set names utf8mb4");
        } catch (\PDOException $exception) {
            die("Connection error: " . $exception->getMessage());
        }
        return $this->conn;
    }
}
?>
