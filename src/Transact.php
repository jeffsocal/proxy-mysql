<?php

/*
 * Written by Jeff Jones (jeff@socalbioinformatics.com)
 * Copyright (2016) SoCal Bioinformatics Inc.
 *
 * See LICENSE.txt for the license.
 */

/*
 * SELECT
 * INSERT
 * UPDATE
 * DELETE
 */
namespace ProxyMySQL;

use ProxyMySQL\Transactions\Prepared;
use ProxyMySQL\Transactions\Simple;

class Transact
{

    protected $data;

    protected $sql_server;

    protected $sql_port;

    protected $sql_schema;

    protected $sql_table;

    public function __construct($server, $port = 3306)
    {
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
        preg_match("/^[a-zA-Z]+/", $statement, $type);
        $type = strtolower($type[0]);
        
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
    }
}

?>