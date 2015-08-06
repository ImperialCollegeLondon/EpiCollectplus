<?php

class dbConnection {
    private $con;
    private $resSet;
    private $numRows;
    private $username;// = $DBUSER;
    private $password;// = $DBPASS;
    private $server;// = $DBSERVER;
    private $schema;// = $DBNAME;
    private $port;// = 3306;
    private $lastId;

    public $connected;
    public $errorCode;
    public $lastQuery;

    public function __construct($un = false, $pwd = false) {
        global $cfg;

        ini_set('mysql.connect_timeout', 300);
        ini_set('default_socket_timeout', 300);

        if ($un) {
            $this->username = $un;
            $this->password = $pwd;
        } else {
            $this->username = $cfg->settings["database"]["user"];
            $this->password = $cfg->settings["database"]["password"];
        }
        $this->server = $cfg->settings["database"]["server"];
        $this->schema = $cfg->settings["database"]["database"];;
        $this->port = $cfg->settings["database"]["port"];

        if ($this->server && $this->port && $this->schema && $this->username) {

            try {
                $this->con = new mysqli($this->server, $this->username, $this->password, NULL, $this->port);
            } catch (Exception $e) {
                echo 'Impossible to connect to database, is MySQL working? Does the user exist?';
                exit();
            }
            $this->connected = true;
            echo $this->con->connect_error;
            if ($this->con->connect_errno) {

                $this->connected = false;
                $this->errorCode = $this->con->connect_errno;
                return;
            }


            $this->con->set_charset('utf-8');
            try {
                $this->con->select_db($this->schema);
            } catch (Exception $e) {
            }
        } else {
            $this->connected = false;
        }
    }

    public function __destruct() {
        if ($this->connected)
            $this->con->close();
    }

    public function boolVal($val) {
        return $val && $val !== "false" ? "1" : "0";
    }

    public function boolVal2($val) {
        return $val === false || $val === "false" || $val == 0 ? "0" : "1";
    }

    public function stringVal($val) {
        return $val == "" ? "NULL" : "'" . mysqli_escape_string($this->con, $val) . "'";
    }

    public function numVal($val) {
        return !$val && $val !== 0 && $val !== 0.0 ? "NULL" : "$val";
    }

    public function unescape($val) {
        return stripcslashes($val);
    }

    public function beginTransaction() {
        //if($this->con->query("START TRANSACTION;"))
        //{
        return true;
        //}
        //else
        //{
        //	return "START TRANSACTION;\r\n" . $this->con->errno . " : " . $this->con->error;
        //}
    }

    public function commitTransaction() {
        //if($this->con->query("COMMIT;"))
        //{
        return true;
        //}
        //else
        //{
        //	return "COMMIT;\r\n" . $this->con->errno . " : " .$this->con->error;
        //}
    }

    public function rollbackTransaction() {
        //if($this->con->query( "ROLLBACK;"))
        //{
        return true;
        //}
        //else
        //{
        return "ROLLBACK;\r\n" . $this->con->errno . " : " . $this->con->error;
        //}
    }

    public function free_result() {
        /*$this->resSet->free();
        while( $this->con->more_results() ) {
            $this->resSet = $this->con->next_result(); $this->resSet->close();
        }*/
    }

    public function affectedRows() {
        return $this->numRows;
    }

    public function escapeArg($arg) {
        return $this->con->escape_string($arg);
    }

    public function do_query($qry) {

        if ($this->connected) {
            $this->con->set_charset('utf8');

            if ($this->resSet && !is_bool($this->resSet))
                mysqli_free_result($this->resSet);
            $this->resSet = $this->con->query($qry);
            $this->numRows = $this->con->affected_rows;

            if ($this->resSet) {
                $this->lastQuery = $qry;
                $this->lastId = $this->con->insert_id;
                return true;
            } else {
                //echo $qry .  "\r\n" . mysqli_errno($this->con) . " : " . mysqli_error($this->con);
                return $qry . "\r\n" . $this->con->errno . " : " . $this->con->error;
            }
        } else {
            throw new Exception("Database not yet connected");
        }

    }

    public function do_multi_query($qry) {
        if ($this->connected) {
            if ($this->resSet && !is_bool($this->resSet))
                mysqli_free_result($this->resSet);
            $res = $this->con->multi_query($qry);

            if ($res) {
                //$this->resSet = $this->con->use_result();

                return true;
            } else {
                //echo $qry .  "\r\n" . mysqli_errno($this->con) . " : " . mysqli_error($this->con);
                return $qry . "\r\n" . $this->con->errno . " : " . $this->con->error;
            }
        } else {
            throw new Exception("Database not yet connected");
        }
    }

    public function getLastResultSet() {
        $this->resSet = $this->con->store_result();
        while ($this->con->more_results() && $this->con->next_result()) {
            $this->resSet = $this->con->store_result();
        }
        return true;
    }

    public function exec_sp($spName, $args = Array()) {
        if ($this->connected) {
            //if($this->resSet && !is_bool($this->resSet)) mysqli_free_result($this->resSet);
            for ($i = 0; $i < count($args); $i++) {
                //$args[$i] = mysqli_escape_string($this->con, $args[$i]);

                if ((is_string($args[$i]))) {
                    $args[$i] = "'" . str_replace("'", "\\\\'", $this->con->escape_string($args[$i])) . "'";
                } else if (!$args[$i]) {
                    if (is_int($args[$i]) || is_double($args[$i]) || is_bool($args[$i]))
                        $args[$i] = "0";
                    else $args[$i] = "NULL";
                }
            }

            $qry = "CALL $spName (" . implode(", ", $args) . ");";

            $this->resSet = $this->con->query($qry);
            if ($this->resSet) {
                return true;
            } else {
                return $qry . "\r\n" . $this->con->errno . " : " . $this->con->error;
            }
        } else {
            throw new Exception("Database not yet connected");
        }
    }

    public function get_row_array() {
        return $this->resSet->fetch_assoc();
    }

    public function get_row_object() {
        return $this->resSet->fetch_object();
    }

    public function last_id() {
        return $this->lastId;
    }

}

?>