<?php

/*
 * Written by Jeff Jones (jeff@socalbioinformatics.com)
 * Copyright (2016) SoCal Bioinformatics Inc.
 *
 * See LICENSE.txt for the license.
 */

/*
 * Statements all begin with
 * SELECT | INSERT | UPDATE | DELETE
 *
 * AVOID __ 'LIKE'
 * USE ____ 'REGEX' INSTEAD
 */
namespace ProxyMySQL;

use ProxyMySQL\Transactions\Prepared;
use ProxyMySQL\Transactions\Simple;
use ProxyIO\File\Log;

class Transact
{

    protected $data;

    protected $sql_server;

    protected $sql_port;

    protected $sql_schema;

    protected $sql_table;

    private $Log;

    public function __construct($server, $port = 3306)
    {
        $this->Log = new Log('sql');
        
        $this->force_simple = false;
        $this->sql_server = $server;
        $this->sql_port = $port;
    }

    function setSchema($schema)
    {
        $this->sql_schema = $schema;
    }

    function setTable($table)
    {
        $this->sql_table = $table;
    }

    function transaction($statement, $parameters = NULL, $force_simple = FALSE)
    {
        
        /*
         * MODIFY LIKE TO REGEXP
         */
        $statement = preg_replace("/like/i", "REGEXP", $statement);
        $statement = trim(preg_replace("/[\r\s]+/", " ", $statement));
        
        /*
         * AUTO DETECT STATEMENT TYPE
         */
        preg_match("/^[\s\n]*[a-zA-Z]+/", $statement, $type);
        $type = strtolower($type[0]);
        
        if (! strstr('select|update|insert|delete', $type)) {
            $this->Log->addToLog("SQL not a valid statement [" . $type . "]");
            return false;
        }
        
        if (!is_null($parameters) and ! is_array($parameters)) {
            $parameters = [
                'i' => $parameters
            ];
        }
        
        if (! is_null($parameters)) {
            $n_par = count($parameters);
            preg_match_all("/\?/", $statement, $n_str);
            
            if (is_false($n_str)) {
                $this->Log->addToLog("SQL not a prepared statement");
                return false;
            }
            
            /*
             * IF #? does not match # params
             */
            if ($n_par != $n_str = count($n_str[0])) {
                
                /*
                 * IF only 1 ?, we can fix that by adding more ?
                 */
                if ($n_str == 1) {
                    
                    /*
                     * TEST if parameter is a comparitor
                     */
                    if (preg_match("/(in|\=)[\s\n\(]*\?/", $statement)) {
                        
                        $statement_expand = array_tostring(array_fill(0, $n_par, '?'), ', ', '');
                        $statement = preg_replace("/\?/", $statement_expand, $statement, 1);
                    } elseif (preg_match("/(regexp|like)[\s\n]*\?/i", $statement)) {
                        
                        $parameters = [
                            strtolower(array_tostring($parameters, '|', ''))
                        ];
                    } else {
                        $this->Log->addToLog("SQL statement parameters not properly formed");
                        return false;
                    }
                } else {
                    $this->Log->addToLog("SQL parameters/variables miss-match [" . $n_par . "] != [" . $n_str . "]");
                    return false;
                }
            }
        }
        
        /*
         * PREPARED STATEMENTS
         */
        
        if (! is_null($parameters) && is_false($force_simple)) {
            
            $sqlp = new Prepared($this->sql_server, $this->sql_port);
            
            $sqlp->setStatement($statement);
            foreach ($parameters as $variable => $value) {
                $sqlp->addVarible($variable, $value);
            }
            
            if ($type == 'select')
                return $sqlp->paramGet();
            
            if ($type == 'insert')
                return $sqlp->paramPut();
            
            if ($type == 'update')
                return $sqlp->paramMod();
            
            if ($type == 'delete')
                return $sqlp->paramDel();
        }
        
        if (! is_null($parameters) && is_true($force_simple)) {
            foreach ($parameters as $variable => $value) {
                $statement = preg_replace("/\?/", '"' . $value . '"', $statement, 1);
            }
        }
        
        /*
         * SIMPLE, NON-PREPARED STATEMENTS
         */
        $sqls = new Simple($this->sql_server, $this->sql_port);
        
        if ($type == 'select')
            return $sqls->sqlGet($statement);
        
        if ($type == 'insert')
            return $sqls->sqlPut($statement);
        
        if ($type == 'update')
            return $sqls->sqlMod($statement);
        
        if ($type == 'delete')
            return $sqls->sqlDel($statement);
        
        return "Humm..";
    }
}

?>