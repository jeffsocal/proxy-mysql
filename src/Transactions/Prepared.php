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
 * = sql_string -> success/fail
 *
 * MOD -> UPDATE
 * = sql_string -> success/fail
 *
 * DEL -> DELETE
 * = sql_string -> success/fail
 *
 */
namespace ProxyMySQL\Transactions;

use ProxyMySQL\Base\Connect;
use mysqli;

class Prepared extends Connect
{

    protected $data;

    protected $schema;

    protected $table;

    protected $trans_is_modify;

    private $statement;

    private $variables;

    private $statement_types;

    private $variable_types;

    private $sql_last_insert_id;

    //
    public function __construct($server, $port = 3306)
    {
        parent::__construct($server, $port);

        $this->setVariableTypes();
        $this->deleteLastInsertID();
        $this->delVaribles();
    }

    private function setVariableTypes()
    {
        /*
         * Character Description
         * i corresponding variable has type integer
         * d corresponding variable has type double
         * s corresponding variable has type string
         * b corresponding variable is a blob and will be sent in packets
         */
        $this->statement_types = array(
            'i',
            'd',
            's',
            'b'
        );
    }

    private function validateType($type)
    {
        if (in_array($type, $this->statement_types))
            return $type;

        return 's';
    }

    public function setStatement($string)

    {
        $this->delVaribles();
        $this->statement = $string;
    }

    public function addVarible($variable, $value, $type = 's')
    {
        array_push($this->variables, $value);
        array_push($this->variable_types, $this->validateType($type));
    }

    public function delVaribles()
    {
        $this->variables = array();
        $this->variable_types = array();
    }

    //
    //
    //
    //
    //
    //
    // GET / SELECT
    public function paramGet()
    {
        $this->setModify(false);
        if (is_false($this->sqlStringCheck($this->statement, '(SELECT|SHOW)')))
            return false;

        return $this->paramTransaction();
    }

    // PUT / INSERT
    public function paramPut()
    {
        if (is_false($this->sqlStringCheck($this->statement, 'INSERT')))
            return false;

        return $this->modifyFromStatement();
    }

    // MOD / UPDATE
    public function paramMod()
    {
        if (is_false($this->sqlStringCheck($this->statement, 'UPDATE')))
            return false;

        return $this->modifyFromStatement();
    }

    // DEL / DELETE
    public function paramDel()
    {
        if (is_false($this->sqlStringCheck($this->statement, 'DELETE')))
            return false;

        return $this->modifyFromStatement();
    }

    /*
     * COPIED FROM TRANSACTION.PHP
     */
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
    protected function modifyFromStatement()
    {
        $this->setModify(true);
        if (is_false($this->paramTransaction())) {
            $this->addToLog(__METHOD__, 'FAIL on MODIFY');
            return false;
        }

        return true;
    }

    private function deleteLastInsertID()
    {
        $this->sql_last_insert_id = FALSE;
    }

    protected function setLastInsertID($id)
    {
        $this->sql_last_insert_id = $id;
        $this->addToLog(__METHOD__, $id);
    }

    public function getLastInsertID()
    {
        return $this->sql_last_insert_id;
    }

    protected function paramTransaction()
    {
        $sql_conn = new mysqli($this->server, $this->login, $this->password, $this->schema, $this->port);

        /*
         * ERROR on CONNECT
         */
        $this->deleteLastInsertID();
        if ($sql_conn->connect_errno) {
            $this->addToLog(__METHOD__, 'CONNECTIONERROR => [' . $sql_conn->connect_errno . '] ' . $sql_conn->connect_error);
            return FALSE;
        }
        /*
         * get the thread id
         */
        $sql_thread_id = $sql_conn->thread_id;

        $sql_string = $this->statement;

        $sql_string = preg_replace("/(\n|\r)+/", " ", $sql_string);
        $sql_string_log = trim($sql_string);
        if (strlen($sql_string_log) > 1000) {
            $sql_string_log = substr($sql_string_log, 0, 1000) . "...";
        }

        /* create a prepared statement */
        $params = array();
        if ($stmt = $sql_conn->prepare($sql_string)) {

            /* create a parameters array to bind */
            $params[] = array_tostring($this->variable_types, '', '');
            foreach ($this->variables as $n => $var) {
                $params[] = &$this->variables[$n];
            }

            /* bind parameters */
            if (sizeof($params) != 0)
                call_user_func_array(array(
                    $stmt,
                    'bind_param'
                ), $params);

            /* execute query */
            $stmt->execute();

            $sql_error = '';

            /* fetch data */
            $this->data = array();
            if (is_false($this->trans_is_modify)) {
                /* fetch value */
                if ($result = $stmt->get_result()) {
                    while ($row = $result->fetch_assoc()) {
                        $this->data[] = $row;
                    }
                }

                /* if the fetch fails */
                if (sizeof($this->data) == 0) {
                    $sql_error .= 'FAIL: ' . sizeof($this->data) . ' rows returned';
                }
            }

            if (strstr($sql_string, 'INSERT')) {
                $this->setLastInsertID($sql_conn->insert_id);
            }

            $sql_error .= $sql_conn->error;
            $sql_conn->close();

            if ($sql_error != '') {
                $this->addToLog(__METHOD__, $sql_error);
                $this->addToLog(__METHOD__, $sql_string);
                if (is_array($this->variables))
                    $this->addToLog(__METHOD__, array_tostring($this->variables));

                return false;
            } else {

                if (sizeof($this->data) == 0)
                    return true;

                return array_rowtocol($this->data);
            }
        } else {
            $this->addToLog(__METHOD__, "COULD NOT PREPARE STATEMENT");
            $this->addToLog(__METHOD__, $sql_string);

            return false;
        }
    }
}

?>