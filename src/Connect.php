<?php

/*
 * Written by Jeff Jones (jeff@socalbioinformatics.com)
 * Copyright (2016) SoCal Bioinformatics Inc.
 *
 * See LICENSE.txt for the license.
 */
namespace ProxyMySQL;

class Connect extends Credentials
{

    protected $schema;

    protected $table;

    protected $retry_conn_open;

    protected $retry_conn_close;

    protected $retry_query;

    //
    public function __construct($server)
    {
        parent::__construct($server);
        $this->setRetryOnOpen(1);
    }

    //
    public function setRetryOnOpen($n = 1)
    {
        $this->retry_conn_open = $n;
    }

    public function setRetryOnClose($n = 1)
    {
        $this->retry_conn_close = $n;
    }

    public function setRetryOnQuery($n = 1)
    {
        $this->retry_query = $n;
    }

    //
    public function setSchema($schema)
    {
        $this->schema = $schema;
    }

    //
    public function setTable($table)
    {
        $this->table = $table;
    }
}

?>