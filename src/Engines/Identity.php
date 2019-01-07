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

class Identity
{

    protected $data;

    protected $index;

    protected $Transaction;

    protected $DetectHack;

    public function __construct($server = '127.0.0.1', $port = '9304')
    {
        $this->Transaction = new Simple($server, $port);
        $this->DetectHack = new DetectHack('sphinx');
    }

    function setIndex($index)
    {
        $this->index = $index;
    }

    function search($str, $limit = 10)
    {
        if ($str == '' or strlen($str) <= 3)
            return false;
        
        // if (is_true($this->DetectHack->is_sqlinject($str)))
        // return false;
        
        $sql_query = 'SELECT protein_name, protein_id, protein_desc, organism 
                        FROM ' . $this->index . " 
                        WHERE MATCH('" . $str . "')";
        
        if (! is_false($limit))
            $sql_query .= ' LIMIT ' . $limit;
        
        $table = $this->Transaction->sqlGet($sql_query);
        
        if (is_false($table))
            return false;
        
        return $table;
    }
}

?>