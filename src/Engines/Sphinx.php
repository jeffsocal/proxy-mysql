<?php

/*
 * Written by Jeff Jones (jeff@socalbioinformatics.com)
 * Copyright (2016) SoCal Bioinformatics Inc.
 *
 * See LICENSE.txt for the license.
 */
namespace ProxyMySQL\Engines;

use ProxyMySQL\Base\DetectHack;
use ProxyMySQL\Transactions\Simple;

class Sphinx
{

    protected $data;

    protected $schema;

    protected $table;

    protected $Transaction;

    protected $DetectHack;

    public function __construct($server = '127.0.0.1', $port = '9306')
    {
        $this->Transaction = new Simple($server, $port);
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
        if ($str == '' or strlen($str) <= 3)
            return false;
        
        if (is_true($this->DetectHack->is_sqlinject($str)))
            return false;
        
        $index = trim($this->schema . '.' . $this->table, '.');
        
        $sql_query = 'SELECT * FROM ' . $index . " 
                      WHERE MATCH('" . $str . "')";
        
        if (! is_false($limit)) {
            $sql_query .= '
                            LIMIT ' . $limit;
        }
        
        $table = $this->Transaction->sqlGet($sql_query);
        
        if (is_false($table))
            return false;
        
        return ($table);
    }

    function searchHits($str)
    {
        if ($str == '' or strlen($str) <= 3)
            return 0;
        
        if (is_true($this->DetectHack->is_sqlinject($str)))
            return 0;
        
        $index = trim($this->schema . '.' . $this->table, '.');
        
        $sql_query = 'SELECT count(*) hits FROM ' . $index . "
                      WHERE MATCH('" . $str . "')";
        
        $table = $this->Transaction->sqlGet($sql_query);
        
        if (is_false($table))
            return 0;
        
        return ($table['hits'][0]);
    }
}

?>