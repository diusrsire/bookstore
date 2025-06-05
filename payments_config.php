<?php
// payments_config.php
class DB {
    private $dbHost     = "localhost";         // Update with your DB host
    private $dbUsername = "root";              // Update with your DB username
    private $dbPassword = "";                  // Update with your DB password
    private $dbName     = "shop_db";           // Update with your DB name
    public $db;

    public function __construct(){
        // Load from .env if exists, otherwise use defaults
        $this->dbHost = getenv('DB_HOST') ?: "localhost";
        $this->dbUsername = getenv('DB_USERNAME') ?: "root";
        $this->dbPassword = getenv('DB_PASSWORD') ?: "";
        $this->dbName = getenv('DB_NAME') ?: "shop_db";

        if(!isset($this->db)){
            $conn = new mysqli($this->dbHost, $this->dbUsername, $this->dbPassword, $this->dbName);
            if($conn->connect_error){
                throw new Exception("Database Connection Failed: " . $conn->connect_error);
            }
            $this->db = $conn;
        }
    }

    public function beginTransaction() {
        $this->db->begin_transaction();
    }

    public function commitTransaction() {
        $this->db->commit();
    }

    public function rollbackTransaction() {
        $this->db->rollback();
    }

    public function insert_payment_details($data) {
        if (empty($data['payment_id'])) return false;

        $columns = implode(", ", array_keys($data));
        $placeholders = implode(", ", array_fill(0, count($data), "?"));
        $types = str_repeat("s", count($data));
        $values = array_values($data);

        $stmt = $this->db->prepare("INSERT INTO payments($columns) VALUES($placeholders)");
        $stmt->bind_param($types, ...$values);
        return $stmt->execute();
    }

    public function updateOrderStatus($orderId, $status) {
        $stmt = $this->db->prepare("UPDATE orders SET payment_status = ? WHERE id = ?");
        $stmt->bind_param("ss", $status, $orderId);
        return $stmt->execute();
    }

    public function updatePaymentStatus($data) {
        if (empty($data['payment_id'])) return false;

        $stmt = $this->db->prepare("UPDATE payments SET payment_status = ?, created = ? WHERE payment_id = ?");
        $stmt->bind_param("sss", $data['status'], $data['created'], $data['payment_id']);
        return $stmt->execute();
    }
}
?>
