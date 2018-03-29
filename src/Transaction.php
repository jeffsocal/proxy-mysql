<?php

/*
 * Written by Jeff Jones (jeff@socalbioinformatics.com)
 * Copyright (2016) SoCal Bioinformatics Inc.
 *
 * See LICENSE.txt for the license.
 */

/*
 * GET -> SELECT
 * = sql_string -> table_data
 *
 * PUT -> INSERT
 * = table_data -> success/fail
 * = sql_string -> success/fail
 *
 * MOD -> UPDATE
 * = table_data -> success/fail
 * = sql_string -> success/fail
 *
 * DEL -> DELETE
 * = sql_string -> success/fail
 *
 */
namespace ProxyMySQL;

use ProxyIO\File\Delim\WriteDelim;
use mysqli;

class Transaction extends Connect
{

    protected $data;

    protected $schema;

    protected $table;

    protected $trans_is_modify;

    //
    public function __construct($server)
    {
        parent::__construct($server);
    }

    //
    function setSchema($schema)
    {
        $this->schema = $schema;
    }

    //
    function setTable($table)
    {
        $this->table = $table;
    }

    // GET / SELECT
    public function sqlGet($obj)
    {
        if (is_false($this->sqlStringCheck($obj, '(SELECT|SHOW)')))
            return false;
        
        $this->setModify(false);
        return $this->sqlTransaction($obj);
    }

    // PUT / INSERT
    public function sqlPut($obj, $split = 1000)
    {
        $this->setModify(true);
        
        if (is_array($obj)) {
            return $this->insertFromTableArray($obj, $split);
        } elseif (is_true($this->sqlStringCheck($obj, 'INSERT'))) {
            return $this->modifyFromString($obj);
        }
        
        $this->addToLog(__METHOD__, 'ERROR: object is not recognized as a string or table_data');
        return false;
    }

    // MOD / UPDATE
    public function sqlMod($obj)
    {
        if (is_false($this->sqlStringCheck($obj, 'UPDATE')))
            return false;
        
        return $this->modifyFromString($obj);
    }

    // DEL / DELETE
    public function sqlDel($obj)
    {
        if (is_false($this->sqlStringCheck($obj, 'DELETE')))
            return false;
        
        return $this->modifyFromString($obj);
    }

    // DESCRIBE
    public function sqlDescribe()
    {
        $this->setModify(false);
        $obj = 'DESCRIBE ' . $this->schema . '.' . $this->table;
        
        return $this->sqlTransaction($obj);
    }

    //
    protected function sqlTransaction($sql_string = null)
    {
        
        //
        if (is_null($sql_string)) {
            return false;
        }
        
        $sql_conn = new mysqli($this->server, $this->login, $this->password, $this->schema);
        $sql_thread_id = $sql_conn->thread_id;
        
        /*
         * ERROR on CONNECT
         */
        if ($sql_conn->connect_errno) {
            $this->addToLog(__METHOD__, 'CONNECTIONERROR => [' . $sql_conn->connect_errno . '] ' . $sql_conn->connect_error);
        }
        
        $sql_string = preg_replace("/(\n|\r)+/", " ", $sql_string);
        $sql_string_log = trim($sql_string);
        if (strlen($sql_string_log) > 1000) {
            $sql_string_log = substr($sql_string_log, 0, 1000) . "...";
        }
        
        /*
         * TURN OFF AUTO COMMIT
         */
        $sql_conn->autocommit(false);
        
        if (is_true($this->trans_is_modify)) {
            
            /*
             * MODIFY SQL
             */
            
            $this->sql_last_insert_id = NULL;
            if (is_false($sql_conn->query($sql_string))) {
                $this->addToLog(__METHOD__, $sql_string_log);
                $this->addToLog(__METHOD__, 'ERROR => [' . $sql_conn->errno . '] ' . $sql_conn->error);
                $sql_conn->rollback();
                $sql_conn->close();
                return false;
            }
            
            //
            // $this->addToLog ( __METHOD__, 'SUCCESS on MODIFY' );
            if (strstr($sql_string, 'INSERT')) {
                $this->setLastInsertID($sql_conn->insert_id);
            }
            $sql_conn->commit();
            $sql_conn->close();
            return true;
        } else {
            
            /*
             * SELECT
             */
            
            $this->data = array();
            if ($result = $sql_conn->query($sql_string)) {
                
                /*
                 * NOT AVAIABLE in Ubuntu 14.01LTS
                 * --------------------------------------
                 * > php -i
                 * mysqli
                 * MysqlI Support => enabled
                 * Client API library version => 5.5.44
                 * MYSQLI_SOCKET => /var/run/mysqld/mysqld.sock
                 */
                //
                $this->data = $result->fetch_all(MYSQLI_ASSOC);
                
                // while ( $row = $result->fetch_array ( MYSQLI_ASSOC ) ) {
                // $this->data [] = $row;
                // }
                
                $result->free();
            }
            
            //
            $sql_error = $sql_conn->error;
            $sql_conn->close();
            
            //
            if (sizeof($this->data) == 0) {
                $this->addToLog(__METHOD__, 'FAIL: ' . sizeof($this->data) . ' rows returned');
                $this->addToLog(__METHOD__, $sql_error);
                $this->addToLog(__METHOD__, $sql_string);
                return false;
            } else {
                // $this->addToLog ( __METHOD__, 'SUCCESS: ' . sizeof ( $this->data ) . ' rows returned' );
            }
            //
            return array_rowtocol($this->data);
        }
    }

    //
    //
    //
    //
    //
    //
    //
    protected function sqlStringCheck($obj, $control = '(SELECT|SHOW)')
    {
        // STRING
        if (is_string($obj) == true) {
            $obj = trim($obj);
            
            if (! preg_match("/^$control/i", $obj)) {
                $this->addToLog(__METHOD__, 'ERROR: sql query string is not an ' . $control . ' statement');
                return false;
            }
        } elseif (is_array($obj) == true) {
            $this->addToLog(__METHOD__, 'ERROR: sql query string is a table, attempted as a string');
            return false;
        } else {
            $this->addToLog(__METHOD__, 'ERROR: sql object is not recognized as a string or table_data');
            return false;
        }
        //
        return true;
    }

    //
    protected function setModify($boolean = true)
    {
        $this->trans_is_modify = is_true($boolean);
    }

    //
    protected function modifyFromString($obj)
    {
        $this->setModify(true);
        if (is_false($this->sqlTransaction($obj))) {
            $this->addToLog(__METHOD__, 'FAIL on MODIFY');
            return false;
        }
        
        return true;
    }

    //
    protected function setLastInsertID($id)
    {
        $this->sql_last_insert_id = $id;
        $this->addToLog(__METHOD__, $id);
    }

    function getLastInsertID()
    {
        return $this->sql_last_insert_id;
    }

    // PUT / INSERT ///////////////////////////////////////////////////////////
    protected function insertFromCSV($obj)
    {
        $file_name = $_ENV['PATH_RES'] . "/tmp/" . "mysqlTableData_" . date('Ymdhs') . ".csv";
        $fio = new WriteDelim();
        
        $fio->writeCsvFile($file_name, $obj);
        
        $sql_string = "LOAD DATA INFILE '" . $file_name . "'\n";
        $sql_string .= " INTO TABLE " . $this->schema . "." . $this->table . "\n";
        $sql_string .= " FIELDS TERMINATED BY ','" . "\n";
        $sql_string .= " ENCLOSED BY '\"'" . "\n";
        $sql_string .= " LINES TERMINATED BY '\\n'" . "\n";
        $sql_string .= " IGNORE 1 LINES;";
        
        $return = $this->modifyFromString($sql_string);
        
        $fio->deleteFile($file_name);
        
        return $return;
    }

    //
    protected function insertFromTableArray($obj, $split = 1000)
    {
        $head = table_header($obj);
        $table_index_chunks = array_chunk(array_keys($obj[$head[0]]), $split);
        
        foreach ($table_index_chunks as $table_indexs) {
            
            $sql_string = "INSERT INTO " . $this->schema . "." . $this->table . "\n";
            $sql_string .= "(" . preg_replace("/\"{2}/", "NULL", array_toString($head)) . ")\n";
            $sql_string = str_replace('"', '', $sql_string);
            $sql_string .= " VALUES\n";
            
            foreach ($table_indexs as $i) {
                $sql_string_line = "(";
                foreach ($head as $n => $name) {
                    $val = "NULL";
                    if ($obj[$name][$i] != '')
                        $val = '"' . $obj[$name][$i] . '"';
                    
                    $sql_string_line .= $val . ",";
                }
                $sql_string_line = trim($sql_string_line, ", ");
                $sql_string .= $sql_string_line . "),\n";
            }
            $sql_string = trim($sql_string, ",\n") . ";";
            $return = $this->modifyFromString($sql_string);
            
            if (is_false($return)) {
                return false;
            }
            
            // sleep 2 sec to rest the mysql processes
            sleep(1);
        }
        
        return $return;
    }
}

?>