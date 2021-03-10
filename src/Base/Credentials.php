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
    public function __construct(string $server, int $port = 3306)
    {
        parent::__construct('sql');

        if (! preg_match("/\d+\.\d+\.\d+\.\d+/", $server))
            systemError("bad ip address: " . $server);

        # sometimes the port gets integrated with the server ip address
        # such as 123.45.67.89:3396
        # we need then to over-ride the explicit port and seperate the
        # ip:port strings
        
        if (preg_match("/\d+\.\d+\.\d+\.\d+\:\d+/", $server)) {
            $port = preg_replace("/.+\:/", "", $server);
            $server = preg_replace("/\:.+/", "", $server);
        }

        $this->setSqlCredentials($server, $port);
    }

    //
    private function setSqlCredentials(string $server, int $port = 3306)
    {
        $this->server = $server;
        $this->port = $port;

        $this->login = '';
        $this->password = '';

        if (file_exists(get_include_path() . 'ini/' . $server . "." . $port . ".ini")) {
            $sql_ini = parse_ini_file(get_include_path() . 'ini/' . $server . "." . $port . ".ini");
            $this->login = $sql_ini['login'];
            $this->password = $sql_ini['password'];
        }
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