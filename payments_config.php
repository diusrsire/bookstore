<?php
// payments_config.php
class DB {
    private $dbHost     = "localhost";         // Update with your DB host
    private $dbUsername = "root";              // Update with your DB username
    private $dbPassword = "";                  // Update with your DB password
    private $dbName     = "shop_db";           // Update with your DB name
    public $db;

    public function __construct(){
        if(!isset($this->db)){
            $conn = new mysqli($this->dbHost, $this->dbUsername, $this->dbPassword, $this->dbName);
            if($conn->connect_error){
                die("Database Connection Failed: " . $conn->connect_error);
            } else {
                $this->db = $conn;
            }
        }
    }

    public function insert_payment_details($arr_data = array()) {
        if (empty($arr_data['payment_id'])) return false;

        $stmt = $this->db->prepare("SELECT * FROM payments WHERE payment_id = ?");
        $stmt->bind_param("s", $arr_data['payment_id']);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows == 0) {
            $columns = implode(", ", array_keys($arr_data));
            $placeholders = implode(", ", array_fill(0, count($arr_data), "?"));
            $values = array_values($arr_data);
            $types = str_repeat("s", count($values));

            $stmt = $this->db->prepare("INSERT INTO payments($columns) VALUES($placeholders)");
            $stmt->bind_param($types, ...$values);
            return $stmt->execute();
        }

        return false;
    }
}
?>
