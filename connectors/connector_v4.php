<?php

/*************************************************************************************
 * TodayCMS PHP SDK
 * Author: Justin Walsh (justin@todaymade.com)
 * Copyright: (c) 2012 Todaymade
 * Version: 4.3
 *
 * Changelog:
 * 4.0 New Node API Version
 * - Added create() and update() methods for accessing API
 * - Bug fixes to post(). POST data was not serializing to http params
 * 4.1 New REST functionality
 * - added universal rest_call() that accesses API via curl
 * - added REST actions POST, GET, PUT, DELETE
 * - added sort() and count()
 * - added join()
 * 4.2 Added timeouts to CURL
 * 4.3 Added client-side caching via cache()
 ************************************************************************************/

 class TodaycmsView {
    private $layout = 'default';
    private $views = array();
    private $vars = array();

    public function layout($file) {
        $this->layout = $file;
        return $this;
    }

    public function view($file) {
        $this->views[] = $file;
        return $this;
    }

    public function set($key, $val) {
        $this->vars[$key] = $val;
        return $this;
    }

    public function render() {
        header('X-Powered-By: TodayCMS (http://todaymade.com)');

        $views = $this->views;
        $vars = $this->vars;

        if (empty($views)) {
            $views = 'default';
        }

        $view = function() use ($views, $vars) {
            foreach ($vars as $key => $value) {
                $$key = $value;
            }

            if (is_array($views)) {
                foreach ($views as $v) {
                    include (dirname(__FILE__) . "/views/" . $v . ".php");
                }
            } else {
                include (dirname(__FILE__) . "/views/" . $views . ".php");
            }
        };

        foreach ($vars as $key => $value) {
            $$key = $value;
        }

        include (dirname(__FILE__) . "/views/layouts/" . $this->layout . ".php");
    }
 }

class Todaycms {
    public $client = '';
    private $api_url = 'http://todaycms-api.herokuapp.com';
    private $debug = false;
    private $config = false;
    private $id = false;
    private $cache_folder = '_cache';
    private $cache_time = false;
    private $params = array();

    public function __construct($client = '') {
        $this -> client = $client;

        // Error
        if (isset($_GET['error'])) {
            ini_set('display_errors', 1);
            ini_set('log_errors', 1);
            ini_set('error_log', dirname(__FILE__) . '/error_log.txt');
            error_reporting(E_ALL);
        }

        // Debug
        if (isset($_GET['debug'])) {
            $this -> debug = true;
        }

        //  Staging Server
        if (isset($_GET['stage'])) {
            $this -> api_url = 'http://stage.todaymade.com';
        }
    }

    public function template() {
        $template = new TodaycmsView();
        $template->set('cms', $this);
        return $template;
    }

    public function reset() {
        $this->id = false;
        $this->cache_time = false;
        $this->params = array();
    }

    public function id($value) {
        $this->id = $value;
        return $this;
    }

    public function key($value) {
        throw new Exception('CMS PHP SDK no longer supports the use of ->key() ');
    }

    public function parent($value) {
        throw new Exception('CMS PHP SDK no longer supports the use of ->parent() ');
    }

    public function slug($value) {
        $this->param('slug', $value);
        return $this;
    }

    public function cache($time = 600) {
        // default to 600 seconds (10 minutes)
        $this->cache_time = $time;
        return $this;
    }

    public function param($key, $value) {
        $this->params[$key] = $value;
        return $this;
    }

    private function params() {
        $url = '';
        foreach ($this->params as $k => $v) {
            $url .= '&' . $k . '=' . $v;
        }
        return $url;
    }

    public function filter($filter_obj) {
        if(is_array($filter_obj)) {
            $filter_obj = json_encode($filter_obj);
        }
        $this->param('filter', urlencode($filter_obj));
        return $this;
    }

    public function sort($sort_obj) {
        if(is_array($sort_obj)) {
            $sort_obj = json_encode($sort_obj);
        }
        $this->param('sort', urlencode($sort_obj));
        return $this;
    }

    public function join($join_obj) {
        if(is_array($join_obj)) {
            $join_obj = json_encode($join_obj);
        }
        $this->param('join', urlencode($join_obj));
        return $this;
    }

    public function url($pos) {
        $url = parse_url($_SERVER['REQUEST_URI']);
        $url = $url['path'];
        $params = explode('/', trim($url, '/'));
        if (isset($params[$pos])) {
            return $params[$pos];
        } else {
            return false;
        }
    }

    public function config($key = false) {
        if (empty($this->config)) {
            $url = '/config';
            $this -> config = $this -> rest_call($this -> api_url . $url.'?_token='.$this->client, 'get');
        };

        if ($key) {
            if (isset($this->config[$key])) {
                return $this->config[$key];
            } else {
                return false;
            }
        } else {
            return $this->config;
        }
    }

    public function single($collection) {
        $this->param('limit', 1);

        $data = $this -> read($collection);

        if (isset($data[0]) && !empty($data[0])) {
            return $data[0];
        } else {
            return false;
        }
    }

    public function multiple($collection) {
        // multiple is an alias of read
        return $this -> read($collection);
    }

    public function count($collection) {
        $this->param('count', 'true');

        $data = $this -> read($collection);

        if (isset($data['count'])) {
            return $data['count'];
        } else {
            return false;
        }
        //return $this -> read($collection);
    }

    public function get_api_url($collection) {
        $url = $this -> api_url . '/collections/'.$collection;
        if ($this->id) {
            $url .= '/'.$this->id;
        }

        return $url .'?_token='.$this->client . $this->params();
    }

    /*
    * API calls
    */

    private function read($collection) {
        $perform_cache = $this->cache_time;
        $url = $this->get_api_url($collection);

        // Check for data in cache
        if ($perform_cache) {
            $data = $this->read_from_cache($url);
            if ($data) {
                // Manually reset params
                $this->reset();
                return $data;
            }
        }

        $data = $this -> rest_call ($url, 'get');

        // Write to cache if data is valid
        if ($perform_cache && $data) {
            $this->write_to_cache($url, json_encode($data));
        }

        return $data;
    }

    public function create($collection, $data) {
        $data = $this -> rest_call ($this->get_api_url($collection), 'post', $data);

        if (!empty($data)) {
            return $data;
        } else {
            return false;
        }
    }

    public function update($collection, $data) {
        $data = $this -> rest_call ($this->get_api_url($collection), 'put', $data);

        if (!empty($data)) {
            return $data;
        } else {
            return false;
        }
    }

    public function delete($collection) {
        $data = $this -> rest_call ($this->get_api_url($collection), 'delete');

        if (!empty($data)) {
            return $data;
        } else {
            return false;
        }
    }

    private function rest_call($url, $verb, $data = false) {
        $this->reset();
        // check for valid verb
        $verb = strtoupper($verb);
        $valid_verbs = array('POST', 'GET', 'PUT', 'DELETE');
        if (!in_array($verb, $valid_verbs)) {
            return false;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $verb);

        // Timeouts
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);

        if ($data) {
            $data = http_build_query($data);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        $output = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($output, true);

        if ($this -> debug) {
            echo '<div style="background-color:white;"><pre>';
            echo $url . '<br>';
            print_r($data);
            echo '</pre></div>';
        }

        return $data;
    }

    private function read_from_cache($url) {
        $url = md5($url);
        $folder = $this->cache_folder;
        $subfolder = substr($url, 0, 1).'/'.substr($url, 1, 1);
        $file_path = $folder.'/'.$subfolder.'/'.$url.'.txt';
        $cache_window_floor = time() - $this->cache_time;

        // Is data still in the cache window?
        if (file_exists($file_path) && filemtime($file_path) >= $cache_window_floor) {
            $json = file_get_contents($file_path);
            return json_decode($json, true);
        } else {
            return false;
        }
    }

    private function write_to_cache($url, $json) {
        $url = md5($url);
        $folder = $this->cache_folder;
        $subfolder = substr($url, 0, 1).'/'.substr($url, 1, 1);
        $file_path = $folder.'/'.$subfolder.'/'.$url.'.txt';

        // If no folder exists, create one with zero permissions
        // for non-owners
        if (!file_exists($folder.'/'.$subfolder)) {
            mkdir($folder.'/'.$subfolder, 0600, true);
        }

        return file_put_contents($file_path, $json);
    }
}
?>