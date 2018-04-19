<?php

/*
 * Written by Jeff Jones (jeff@socalbioinformatics.com)
 * Copyright (2016) SoCal Bioinformatics Inc.
 *
 * See LICENSE.txt for the license.
 */
namespace ProxyMySQL\Base;

use ProxyIO\File\Log;

class Credentials extends Log
{

    protected $port;
    
    protected $server;

    protected $login;

    protected $password;

    //
    public function __construct($server, $port = 3306)
    {
        parent::__construct('sql');
        $this->setSqlCredentials($server, $port);
    }

    //
    private function setSqlCredentials($server, $port = 3306)
    {
        $sql_ini = parse_ini_file(get_include_path(). 'ini/' . $server . "." . $port . ".ini");
        
        $this->server = $server;
        $this->port = $port;
        $this->login = $sql_ini['login'];
        $this->password = $sql_ini['password'];
    }

    //
    protected function getLogin()
    {
        return ($this->login);
    }

    //
    protected function getPassword()
    {
        return ($this->password);
    }
}

?>