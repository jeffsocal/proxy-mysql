<?php

/*
 * Written by Jeff Jones (jeff@socalbioinformatics.com)
 * Copyright (2016) SoCal Bioinformatics Inc.
 *
 * See LICENSE.txt for the license.
 */
namespace ProxyMySQL;

use ProxyIO\File\Log;

class Credentials extends Log
{

    protected $server;

    protected $login;

    protected $password;

    //
    public function __construct($server)
    {
        parent::__construct('sql');
        $this->setSqlCredentials($server);
    }

    //
    private function setSqlCredentials($server)
    {
        $sql_ini = parse_ini_file('ini/' . $server . ".ini");
        
        $this->server = $server;
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