<?php

/*
 * Written by Jeff Jones (jeff@socalbioinformatics.com)
 * Copyright (2016) SoCal Bioinformatics Inc.
 *
 * See LICENSE.txt for the license.
 */
namespace ProxyMySQL;

use ProxyIO\File\Log;

class DetectHack extends Log
{

    //
    public function __construct()
    {
        parent::__construct('sql');
    }

    //
    public function is_sqlinject($str)
    {
        
        //
        $total = 0;
        $str_orig = $str;
        
        $regex_quote = '\'|\"|`';
        
        $regex_and = '(A|a)(N|n)(D|d)';
        $regex_or = '(O|o)(R|r)';
        $regex_equal = '(=)';
        $arr_regex = array(
            '/(\-\-|\#|\/\*)\s*$/si',
            '/(' . $regex_quote . ')?\s*' . $regex_or . '\s*(\d+)\s*' . $regex_equal . '\s*\\4\s*/si',
            '/(' . $regex_quote . ')?\s*' . $regex_or . '\s*(' . $regex_quote . ')(\d+)\\4\s*' . $regex_equal . '\s*\\5\s*/si',
            '/(' . $regex_quote . ')?\s*' . $regex_or . '\s*(\d+)\s*' . $regex_equal . '\s*(' . $regex_quote . ')\\4\\6?/si',
            '/(' . $regex_quote . ')?\s*' . $regex_or . '\s*(' . $regex_quote . ')?(\d+)\\4?/si',
            '/(' . $regex_quote . ')?\s*' . $regex_or . '\s*(' . $regex_quote . ')([^\\4]*)\\4\\5\s*' . $regex_equal . '\s*(' . $regex_quote . ')/si',
            '/(((' . $regex_quote . ')\s*)|\s+)' . $regex_or . '\s+([a-z_]+)/si',
            '/(' . $regex_quote . ')?\s*' . $regex_or . '\s+([a-z_]+)\s*' . $regex_equal . '\s*(d+)/si',
            '/(' . $regex_quote . ')?\s*' . $regex_or . '\s+([a-z_]+)\s*' . $regex_equal . '\s*(' . $regex_quote . ')/si',
            '/(' . $regex_quote . ')?\s*' . $regex_or . '\s*(' . $regex_quote . ')([^\\4]+)\\4\s*' . $regex_equal . '\s*([a-z_]+)/si',
            '/(' . $regex_quote . ')?\s*' . $regex_or . '\s*(' . $regex_quote . ')([^\\4]+)\\4\s*' . $regex_equal . '\s*(' . $regex_quote . ')/si',
            '/(' . $regex_quote . ')?\s*\)\s*' . $regex_or . '\s*\(\s*(' . $regex_quote . ')([^\\4]+)\\4\s*' . $regex_equal . '\s*(' . $regex_quote . ')/si',
            '/(' . $regex_quote . '|\d)?(;|%20|\s)*(union|select|insert|update|delete|drop|alter|create|show|truncate|load_file|exec|concat|benchmark)((\s+)|\s*\()/ix',
            '/from(\s*)information_schema.tables/ix'
        );
        
        foreach ($arr_regex as $n => $regex) {
            if (preg_match($regex, $str)) {
                $this->addToLog(__METHOD__, $str);
                return true;
            }
        }
        
        return false;
    }
}

?>