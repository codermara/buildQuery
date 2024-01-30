<?php
namespace FpDbTest;

use Exception;
use mysqli;

class Database implements DatabaseInterface
{
    private mysqli $mysqli;

    public function __construct(mysqli $mysqli)
    {
        $this->mysqli = $mysqli;
    }

    public function buildQuery(string $query, array $args = []): string
    {
        $escapedArgs = [];
            foreach ($args as $arg) {
                if (is_int($arg) || is_float($arg)) {
                    $escapedArgs[] = $arg;
                }
                elseif (is_string($arg)) {
                    $escapedArgs[] =  $this->mysqli->real_escape_string($arg);
                }
                 elseif (is_null($arg)) {
                    $escapedArgs[] = 'NULL';
                } elseif (is_bool($arg)) {
                    $escapedArgs[] = (int)$arg;
                } elseif (is_array($arg)) {
                    $escapedArgs[] = $arg;

                    if (empty($arg)) {
                        throw new Exception('Empty array passed as a parameter.');
                    }


                } else {
                    throw new Exception('Unsupported parameter type.');
                }
            }
    
            $queryParts = preg_split('/(\\?[dfas#]?)/', $query, null, PREG_SPLIT_DELIM_CAPTURE);
            $query = '';
            foreach ($queryParts as $i => $part) {

                if ($part === '?') {
                    if (empty($escapedArgs)) {
                        throw new Exception('Not enough parameters passed.');
                    }
                   $query .= "'".array_shift($escapedArgs)."'";


                } elseif (preg_match('/^(\\?d|\\?f)$/', $part)) {
                    if (empty($escapedArgs)) {
                        throw new Exception('Not enough parameters passed.');
                    }
                    $val = array_shift($escapedArgs);
                    $exit_temp = 'exit';
                    if (is_null($val)) {
                        $query .= 'NULL';
                    }
                    elseif ($val == $exit_temp)
                    {
                        $regex = '/\s*AND\s+\w+\s*=\s*|\(\(\'\d+\'\s*,\s*\)+\'\d+\'\)/';
                        $result = preg_replace($regex, '', $query);
                        $result = preg_replace('/\(\((.*?)\)\)/', '($1)', $result);
                        $query = $result;
                    }
                    else {

                        $query .= $val;
                        $query = str_replace(array('((','))'), array('(',')'), $query);

                    }
                } elseif ($part === '?a') {
                    if (empty($escapedArgs)) {
                        throw new Exception('Not enough parameters passed.');
                    }
                    $arg = array_shift($escapedArgs);
                    if (!is_array($arg)) {
                        throw new Exception('?a parameter must be an array.');
                    }
                    if (empty($arg)) {
                        throw new Exception('Empty array passed as a parameter.');
                    }
                    $keys = array_keys($arg);
                    if (is_int($keys[0])) {
                            $tuTemp .= implode(',', array_map(function ($val) {
                                return is_null($val) ? 'NULL' : " " . $this->mysqli->real_escape_string($val) . "";
                            }, $arg));
                            $tuTemp = '(' . substr($tuTemp,1). ')';
                            $query .= $tuTemp;
                    }
                     else {
                            $quTemp .= implode(',', array_map(function ($key, $val) {
                                if (is_null($val)) {
                                    return " `$key` = NULL";
                                }
                                return " `$key` = '" . $this->mysqli->real_escape_string($val) . "'";
                            }, array_keys($arg), $arg));
                            $quTemp = substr($quTemp,1);
                            $query .= $quTemp;

                    }
                 
                } elseif ($part === '?#') {
                    if (empty($escapedArgs)) {
                        throw new Exception('Not enough parameters passed.');
                    }
                   
                    $arg = array_shift($escapedArgs);
                    if (!is_string($arg) && !is_array($arg)) {
                                        throw new Exception('?# parameter must be a string or an array of strings.');
                                    }
                                   if (is_string($arg)) {
                                        $query .= '`' . $this->mysqli->real_escape_string($arg) . '`';
                                    } else {
                                        $quTemp = implode(',', array_map(function ($val) {
                                                return ' `' . $this->mysqli->real_escape_string($val) . '`';
                                            }, $arg));
                                            $quTemp = substr($quTemp,1);
                                            $query .= $quTemp;
                                    }
                                }
                                elseif($part === "}")
                                {
                                    $query = str_replace(array('{','}'), array('',''), $query);
                                } else {
                                    $query .= $part;
                                }
            }
    
            if (!empty($escapedArgs)) {
                throw new Exception('Too many parameters passed.');
            }
            return $query;
    }

    public function skip() : string
    {
        $exit = 'exit';
        return $exit;
    }
}