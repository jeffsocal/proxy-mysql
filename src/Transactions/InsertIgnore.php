<?php

/*
 * Written by Jeff Jones (jeff@socalbioinformatics.com)
 * Copyright (2016) SoCal Bioinformatics Inc.
 *
 * See LICENSE.txt for the license.
 */
namespace ProxyMySQL\Transactions;

class InsertIgnore extends Simple
{

    //
    public function __construct($server)
    {
        parent::__construct($server);
    }

    //
    public function insert($str)
    {
        $str = str_replace("INSERT", "INSERT IGNORE", $str);
        
        return $this->modifyFromString($str);
    }
}

?>