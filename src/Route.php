<?php
/**
 * Copyright (c) 2018 Pushkar Anand. Under MIT License.Copyright (c) 2018 Pushkar Anand. Under MIT License.
 *
 */

namespace EasyRoute;


use Exception;

/**
 * Class Route
 * @package EasyRoute
 */
class Route
{
    public $match_found = false;
    private $ARRAY_METHOD_KEY = "method";
    private $ARRAY_URI_KEY = "uri";
    private $ARRAY_CALLABLE_BOOL_KEY = "callable";
    private $ARRAY_FILE_KEY = "file";
    private $ARRAY_CALLABLE_KEY = "function";
    private $ARRAY_PARAMS_KEY = "params";

    private $server_uri;
    private $supported_methods = array("GET", "POST");
    private $request_method;
    private $script_name;
    private $current_path;
    private $match_list = array();
    private $match;
    private $send404 = true;

    /**
     * Route constructor.
     */
    public function __construct()
    {
        $this->server_uri = $_SERVER['REQUEST_URI'];
        $this->request_method = $_SERVER["REQUEST_METHOD"];
        $this->script_name = $_SERVER['SCRIPT_NAME'];

        $this->current_path = $this->get_request_path($this->server_uri, $this->script_name);
    }

    /**
     * @param string $server_uri
     * @param string $script_name
     * @return string
     */
    private function get_request_path(string $server_uri, string $script_name): string
    {
        $basepath = implode('/',
                array_slice(explode('/', $script_name),
                    0,
                    -1)) . '/';

        $uri = substr($server_uri, strlen($basepath));

        if (strstr($uri, '?')) {
            $uri = substr($uri, 0, strpos($uri, '?'));
        }
        $uri = '/' . trim($uri, '/');

        return $uri;
    }

    /**
     * If this is set to false 404 NOT FOUND will not be sent when no match is found.
     * By default set to true.
     * @param bool $bool
     */
    public function send404(bool $bool)
    {
        $this->send404 = $bool;
    }

    /**
     * Add all routes by calling this method.
     *
     * @param string $method is the request method.
     * @param string $uri is the uri to be matched.
     * @param string|callable $next is either a file to load or a callable function.
     * @param string|null $params
     * @throws Exception
     */
    public function addMatch(string $method, string $uri, $next, string $params = null)
    {
        $method = strtoupper($method);

        if (!in_array($method, $this->supported_methods)) {
            throw new Exception("Method " . $method . " is not supported.");
        }

        if (!is_string($uri)) {
            throw new Exception("Uri " . $uri . " is not valid.");
        }

        if (($params != null) && !$this->areParamsValid($params)) {
            throw new Exception("Parameters supplied is not a valid json.");
        }

        if (is_callable($next)) {

            $new_match = array(
                $this->ARRAY_METHOD_KEY => $method,
                $this->ARRAY_URI_KEY => $uri,
                $this->ARRAY_CALLABLE_BOOL_KEY => true,
                $this->ARRAY_CALLABLE_KEY => $next
            );

        } elseif (is_string($next)) {

            if (file_exists($next)) {
                $new_match = array(
                    $this->ARRAY_METHOD_KEY => $method,
                    $this->ARRAY_URI_KEY => $uri,
                    $this->ARRAY_CALLABLE_BOOL_KEY => false,
                    $this->ARRAY_FILE_KEY => $next,
                    $this->ARRAY_PARAMS_KEY => $params
                );

            } else {
                $dir_next = __DIR__ . "/" . $next;

                if (file_exists($dir_next)) {
                    $new_match = array(
                        $this->ARRAY_METHOD_KEY => $method,
                        $this->ARRAY_URI_KEY => $uri,
                        $this->ARRAY_CALLABLE_BOOL_KEY => false,
                        $this->ARRAY_FILE_KEY => $dir_next,
                        $this->ARRAY_PARAMS_KEY => $params
                    );

                } else {

                    throw new Exception("File " . $next . " not found.");
                }
            }
        } else {
            throw new Exception("Invalid third parameter. Expecting callable or file.");
        }

        array_push($this->match_list, $new_match);
    }

    /**
     *
     */
    public function execute()
    {
        if ($this->findMatch()) {
            if ($this->match[$this->ARRAY_CALLABLE_BOOL_KEY] == true) {
                $callable = $this->match[$this->ARRAY_CALLABLE_KEY];
                $callable();
            } else {
                $file = $this->match[$this->ARRAY_FILE_KEY];
                require_once $file;
            }
        } else {
            if ($this->send404) {
                header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
                exit();
            }
        }
    }

    private function findMatch(): bool
    {
        foreach ($this->match_list as $match) {

            if ($match[$this->ARRAY_METHOD_KEY] == $this->request_method &&
                $match[$this->ARRAY_URI_KEY] == $this->current_path) {

                $this->match_found = true;
                $this->match = $match;
                return true;
            }
        }
        return false;
    }

    /**
     * This functions checks for the supplied parameters list is valid json.
     * @param string $params
     * @return bool
     */
    public function areParamsValid(string $params): bool
    {
        // 1. Speed up the checking & prevent exception throw when non string is passed
        if (is_numeric($params) || !is_string($params) || !$params) {
            return false;
        }

        $cleaned_str = trim($params);
        if (!$cleaned_str || !in_array($cleaned_str[0], ['{', '['])) {
            return false;
        }

        // 2. Actual checking
        $str = json_decode($params);
        return (json_last_error() == JSON_ERROR_NONE) && $str && $str != $params;
    }
}