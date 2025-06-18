<?php
class Stock {
    // Database connection and table name
    private $conn;
    private $table_name = "stock";
    
    // Object properties
    public $stock_id;
    public $product_id;
    public $supplier_id;
    public $quantity_added;
    public $current_quantity;
    public $unit_cost;
    public $batch_number;
    public $date_added;
    public $expiry_date;
    public $location_code;
    
    // Constructor with DB connection
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Read all stock records with pagination
    function readAll($from_record_num, $records_per_page) {
        // Select query
        $query = "SELECT 
                    s.stock_id, s.product_id, s.supplier_id, s.quantity_added, 
                    s.current_quantity, s.unit_cost, s.batch_number, 
                    s.date_added, s.expiry_date, s.location_code,
                    p.name as product_name, p.sku, p.minimum_stock_level
                  FROM " . $this->table_name . " s
                  LEFT JOIN products p ON s.product_id = p.product_id
                  ORDER BY s.date_added DESC
                  LIMIT ?, ?";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind variables
        $stmt->bindParam(1, $from_record_num, PDO::PARAM_INT);
        $stmt->bindParam(2, $records_per_page, PDO::PARAM_INT);
        
        // Execute query
        $stmt->execute();
        
        return $stmt;
    }
    
    // Get total count of stock records
    public function countAll() {
        $query = "SELECT COUNT(*) as total_rows FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total_rows'];
    }
    
    // Get all unique storage locations
    public function getLocations() {
        $query = "SELECT DISTINCT location_code FROM " . $this->table_name . " ORDER BY location_code";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Add new stock
    public function addStock() {
        // Query to insert stock
        $query = "INSERT INTO " . $this->table_name . "
                  SET
                    product_id = :product_id,
                    supplier_id = :supplier_id,
                    quantity_added = :quantity_added,
                    current_quantity = :current_quantity,
                    unit_cost = :unit_cost,
                    batch_number = :batch_number,
                    date_added = :date_added,
                    location_code = :location_code";
        
        // Add expiry date if provided
        if ($this->expiry_date) {
            $query .= ", expiry_date = :expiry_date";
        }
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Sanitize inputs
        $this->batch_number = htmlspecialchars(strip_tags($this->batch_number));
        $this->location_code = htmlspecialchars(strip_tags($this->location_code));
        
        // Bind values
        $stmt->bindParam(":product_id", $this->product_id);
        $stmt->bindParam(":supplier_id", $this->supplier_id);
        $stmt->bindParam(":quantity_added", $this->quantity_added);
        $stmt->bindParam(":current_quantity", $this->current_quantity);
        $stmt->bindParam(":unit_cost", $this->unit_cost);
        $stmt->bindParam(":batch_number", $this->batch_number);
        $stmt->bindParam(":date_added", $this->date_added);
        $stmt->bindParam(":location_code", $this->location_code);
        
        // Bind expiry date if provided
        if ($this->expiry_date) {
            $stmt->bindParam(":expiry_date", $this->expiry_date);
        }
        
        // Execute query
        if ($stmt->execute()) {
            // Update product stock level in the products table if needed
            $this->updateProductStock($this->product_id);
            return true;
        }
        
        return false;
    }
    
    // Read one stock record
    public function readOne() {
        $query = "SELECT
                    s.*, p.name as product_name, p.sku, p.minimum_stock_level,
                    sup.name as supplier_name
                  FROM
                    " . $this->table_name . " s
                  LEFT JOIN products p ON s.product_id = p.product_id
                  LEFT JOIN suppliers sup ON s.supplier_id = sup.supplier_id
                  WHERE
                    s.stock_id = ?
                  LIMIT 0,1";
                  
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind stock_id
        $stmt->bindParam(1, $this->stock_id);
        
        // Execute query
        $stmt->execute();
        
        // Get record
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            // Set properties
            $this->product_id = $row['product_id'];
            $this->supplier_id = $row['supplier_id'];
            $this->quantity_added = $row['quantity_added'];
            $this->current_quantity = $row['current_quantity'];
            $this->unit_cost = $row['unit_cost'];
            $this->batch_number = $row['batch_number'];
            $this->date_added = $row['date_added'];
            $this->expiry_date = $row['expiry_date'];
            $this->location_code = $row['location_code'];
            
            return true;
        }
        
        return false;
    }
    
    // Update stock quantity
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                  SET
                    current_quantity = :current_quantity,
                    location_code = :location_code,
                    expiry_date = :expiry_date
                  WHERE
                    stock_id = :stock_id";
                    
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Sanitize inputs
        $this->location_code = htmlspecialchars(strip_tags($this->location_code));
        
        // Bind values
        $stmt->bindParam(":current_quantity", $this->current_quantity);
        $stmt->bindParam(":location_code", $this->location_code);
        $stmt->bindParam(":expiry_date", $this->expiry_date);
        $stmt->bindParam(":stock_id", $this->stock_id);
        
        // Execute query
        if ($stmt->execute()) {
            // Update product stock level in the products table if needed
            $this->updateProductStock($this->product_id);
            return true;
        }
        
        return false;
    }
    
    // Update product stock total when adding/updating stock
    private function updateProductStock($product_id) {
        // This method would be used if you have a total_stock field in your products table
        // If you're calculating totals on the fly via queries, you may not need this
        return true;
    }
    
    // Search stocks
    public function search($keywords, $from_record_num, $records_per_page) {
        // Search query
        $query = "SELECT 
                    s.stock_id, s.product_id, s.supplier_id, s.quantity_added, 
                    s.current_quantity, s.unit_cost, s.batch_number, 
                    s.date_added, s.expiry_date, s.location_code,
                    p.name as product_name, p.sku, p.minimum_stock_level
                  FROM " . $this->table_name . " s
                  LEFT JOIN products p ON s.product_id = p.product_id
                  WHERE 
                    p.name LIKE ? OR p.sku LIKE ? OR s.batch_number LIKE ?
                  ORDER BY s.date_added DESC
                  LIMIT ?, ?";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Sanitize keywords
        $keywords = htmlspecialchars(strip_tags($keywords));
        $keywords = "%{$keywords}%";
        
        // Bind variables
        $stmt->bindParam(1, $keywords);
        $stmt->bindParam(2, $keywords);
        $stmt->bindParam(3, $keywords);
        $stmt->bindParam(4, $from_record_num, PDO::PARAM_INT);
        $stmt->bindParam(5, $records_per_page, PDO::PARAM_INT);
        
        // Execute query
        $stmt->execute();
        
        return $stmt;
    }
    
    // Filter by location
    public function filterByLocation($location, $from_record_num, $records_per_page) {
        // Filter query
        $query = "SELECT 
                    s.stock_id, s.product_id, s.supplier_id, s.quantity_added, 
                    s.current_quantity, s.unit_cost, s.batch_number, 
                    s.date_added, s.expiry_date, s.location_code,
                    p.name as product_name, p.sku, p.minimum_stock_level
                  FROM " . $this->table_name . " s
                  LEFT JOIN products p ON s.product_id = p.product_id
                  WHERE 
                    s.location_code = ?
                  ORDER BY s.date_added DESC
                  LIMIT ?, ?";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Sanitize location
        $location = htmlspecialchars(strip_tags($location));
        
        // Bind variables
        $stmt->bindParam(1, $location);
        $stmt->bindParam(2, $from_record_num, PDO::PARAM_INT);
        $stmt->bindParam(3, $records_per_page, PDO::PARAM_INT);
        
        // Execute query
        $stmt->execute();
        
        return $stmt;
    }
}