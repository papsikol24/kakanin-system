<?php
// MongoDB Database Connection for Render

class MongoDBConnection {
    private static $instance = null;
    private $client;
    private $db;
    
    private function __construct() {
        try {
            $connectionString = getenv("MONGODB_URI");
            
            if (!$connectionString) {
                throw new Exception("MONGODB_URI environment variable not set");
            }
            
            $this->client = new MongoDB\Client($connectionString);
            $this->db = $this->client->selectDatabase("kakanin_db");
            
        } catch (Exception $e) {
            die("MongoDB Connection failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new MongoDBConnection();
        }
        return self::$instance;
    }
    
    public function getDatabase() {
        return $this->db;
    }
    
    public function getCollection($collectionName) {
        return $this->db->selectCollection($collectionName);
    }
    
    public function ping() {
        try {
            $this->db->command(["ping" => 1]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}

function getDb() {
    return MongoDBConnection::getInstance()->getDatabase();
}

function getCollection($name) {
    return MongoDBConnection::getInstance()->getCollection($name);
}

function objectId($id) {
    return new MongoDB\BSON\ObjectId($id);
}

function formatDocument($doc) {
    if ($doc && isset($doc["_id"])) {
        $doc["id"] = (string)$doc["_id"];
    }
    return $doc;
}

function checkDbConnection() {
    return MongoDBConnection::getInstance()->ping();
}