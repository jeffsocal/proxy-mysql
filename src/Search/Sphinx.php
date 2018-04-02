<?php

/*
 * Written by Jeff Jones (jeff@socalbioinformatics.com)
 * Copyright (2016) SoCal Bioinformatics Inc.
 *
 * See LICENSE.txt for the license.
 */
namespace ProxyMySQL\Search;

use ProxyMySQL\Transaction;
use ProxyMySQL\DetectHack;

class Sphinx
{

    protected $data;

    protected $schema;

    protected $table;

    protected $Transaction;

    protected $DetectHack;

    public function __construct($server = '120.0.0.1', $port = '9306')
    {
        $this->Transaction = new Transaction($server, $port);
        $this->DetectHack = new DetectHack('sphinx');
    }

    function setSchema($schema)
    {
        $this->schema = $schema;
    }

    function setTable($table)
    {
        $this->table = $table;
    }

    function search($str, $limit = 10)
    {
        if (is_true($this->DetectHack->is_sqlinject($str)))
            return false;
        
        $index = trim($this->schema . '.' . $this->table, '.');
        
        $sql_query = 'SELECT * FROM ' . $index . " 
                      WHERE MATCH('" . $str . "')";
        
        if (! is_false($limit)) {
            $sql_query .= '
                            LIMIT ' . $limit;
        }
        
        $this_table = $this->sqlGet($sql_query);
        if (is_false($table)) {
            $table = $this_table;
        } else {
            $table = table_bind($table, $this_table);
        }
        
        if (is_false($table))
            return false;
        
        return ($table);
    }
}

?>