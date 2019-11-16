<?php
class DBClass {
    private
        $connection,
        $encryptionUtil = null,

        $dbUser,
        $dbPassword,
        $dbHost;
    /*
     * Public
     */
    public function __construct(
        $db_user,
        $db_password,
        $db_host,
        IEncryptionUtil $encryptionUtil = null
    ) {
        $this->dbUser = $db_user;
        $this->dbPassword = $db_password;
        $this->dbHost = $db_host;
        if($encryptionUtil != null) {
            $this->encryptionUtil = $encryptionUtil;
        }
        $this->connect();
    }

    public function isExist($table, $cond_key = "", $cond_value = "") {
        $result = $this->getRows($table, $cond_key, $cond_value);
        return (count($result) > 0);
    }

    public function getRows($table, $cond_key = "", $cond_value = "", $columns = "*", $all = true) {
        if(!compare($columns, "*")) {
            $columns = $this->stringWithGravis($columns);
        }
        $sql = "SELECT ".$columns." FROM `".$table."`".$this->setCondition($cond_key, $cond_value);

        return $this->get($sql, $all);
    }

    public function get($get_query, $all = true) {
        $this->connect();
        try {
            $result = null;
            $query = $this->connection->query($get_query);
            if(!$query) $this->error();
            $result = (($all) ? $query->fetchAll(PDO::FETCH_ASSOC) : $query->fetch(PDO::FETCH_ASSOC));

            if(!is_array($result)) {
                $result = array();
            }
            if(!is_empty($result) && $this->dbEncryption()) {
                $result = $this->encryptionUtil->decryptArray($result);
            }
            return $result;
        } catch (PDOException $e) {
            $this->error($e->getMessage());
        }
        return NULL;
    }

    public function query($insert_query) {
        $this->connect();
        try {
            if(!$this->connection->query($insert_query)) {
                $this->error("Unable to make a query: ".$insert_query);
            }
        } catch (PDOException $e) {
            $this->error($e->getMessage());
        }
    }

    public function clearTable($table) {
        $sql = 'TRUNCATE '.$table;
        $this->query($sql);
    }

    public function insert($table, $values) {
        $sql = "INSERT INTO `".$table."`".$this->setInsertPdo($values);
        $this->query($sql);
    }

    public function close() {
        $this->connection = null;
    }

    /*
     * Private
     */
    private function dbEncryption() {
        return ($this->encryptionUtil != null);
    }

    private function error($err = null) {
        die_redirect(($err == null) ? "DATABASE_ERROR" : $err);
    }

    private function connect() {
        try {
            $this->connection = new PDO('mysql:host='.$this->dbHost.';dbname='.$this->dbUser, $this->dbUser, $this->dbPassword);
        } catch (PDOException $e) {
            $this->error($e->getMessage());
        }
    }

    private function getSign(&$key) {
        $sign = "=";
        $signArr = array(">", "<");
        $lastCharacter = substr($key, -1);

        if(in_array($lastCharacter, $signArr)) {
            $sign = $lastCharacter;
            $key = substr($key, 0, -1);
        }
        return $sign;
    }

    private function setInsertPdo($values) {
        $sql_keys = " (";
        $sql_values = ") VALUES (";

        foreach ($values as $key => $value) {
            $sql_keys .= "`".$key."`, ";

            if($this->dbEncryption()) $value = $this->encryptionUtil->encryptArrayElement($key, $value);

            $sql_values .= ((is_null($value)) ? 'NULL' : $this->connection->quote($value)).", ";
        }
        $sql = $this->deleteComma($sql_keys).$this->deleteComma($sql_values).");";
        return $sql;
    }

    private function setConditions($conditions, $operator = "AND") {
        if(is_empty($conditions) || !is_array($conditions)) return "";

        $sql = ' WHERE';
        foreach ($conditions as $key => $condition) {
            $sign = ((is_empty($condition)) ?  '' : ' '.$this->getSign($key));

            if($this->dbEncryption()) $condition = $this->encryptionUtil->encryptArrayElement($key, $condition);

            $condition = (is_empty($condition)) ? 'is NULL' : ' '.$this->connection->quote($condition);
            $sql .= " `".$key."`".$sign." ".$condition." ".$operator;
        }
        return $this->deleteAND($sql);
    }

    private function setCondition($cond_key, $cond_value) {
        if(!(is_empty($cond_key) && is_empty($cond_value))) {
            return $this->setConditions(array($cond_key => $cond_value));
        }
        return "";
    }

    private function stringWithGravis($str) {
        $arr = $pieces = explode(" ", $str);
        $columns = " ";
        foreach ($arr as $element) {
            $columns .= "`".$element."`, ";
        }
        return $this->deleteComma($columns);
    }

    private function deleteComma($sql) {
        return substr($sql, 0, -2);
    }

    private function deleteAND($sql) {
        return substr($sql, 0, -3);
    }
}