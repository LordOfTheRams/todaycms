<?php
/*************************************************************************************
 * TodayCMS PHP SDK
 * Author: Justin Walsh (justin@todaymade.com)
 * Copyright: (c) 2012 Todaymade
 * Version: 2.0
 *
 * Changelog:
 * 2.0 New Version
 * 2.0.1 Hide the config debug
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
					include ($_SERVER["DOCUMENT_ROOT"] . "/views/" . $v . ".php");
				}
			} else {
				include ($_SERVER["DOCUMENT_ROOT"] . "/views/" . $views . ".php");
			}
		};

		foreach ($vars as $key => $value) {
			$$key = $value;
		}

		include ($_SERVER["DOCUMENT_ROOT"] . "/views/layouts/" . $this->layout . ".php");
 	}
 }

class Todaycms {
	public $client = '';
	private $server = 'http://launch.todaymade.com';
	private $api_url = '';
	private $debug = false;
	private $config = false;
	private $formbuilder_init = false;

	private $id = false;
	private $key = false;
	private $slug = false;
	private $parent = false;
	private $filters = array();
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

		//	Staging Server
		if (isset($_GET['stage'])) {
			$this -> server = 'http://stage.todaymade.com';
		}

		$this -> api_url = $this -> server . '/api/' . $client . '/';
		$this -> social_url = $this -> server . '/social/';
	}

	public function template() {
		$template = new TodaycmsView();
		$template->set('cms', $this);
		return $template;
	}

	public function reset() {
		$this->id = false;
		$this->key = false;
		$this->slug = false;
		$this->parent = false;
		$this->filters = array();
		$this->params = array();
	}

	public function key($value) {
		$this->key = $value;
		return $this;
	}

	public function slug($value) {
		$this->slug = $value;
		return $this;
	}

	public function parent($value) {
		$this->parent = $value;
		return $this;
	}

	public function param($key, $value) {
		$this->params[$key] = $value;
		return $this;
	}

	private function params() {
		$url = '';
		foreach ($this->params as $k => $v) {
			$url .= '/' . $k . ':' . $v;
		}
		return $url;
	}

	public function filter($key, $value) {
		$this->filters[$key] = $value;
		return $this;
	}

	private function filters() {
		if (!empty($this->filters)) {
			$url = '/filter:';
			$filters = array();
			foreach ($this->filters as $field => $value) {
				$filters[] = $field . '--' . $value;
			}
			$url .= implode('||', $filters);
			return $url;
		} else {
			return '';
		}
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
			$url = 'config';
			$this -> config = $this -> get($this -> api_url . $url, false);
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

	// Used to be parent()
	public function group() {
		if ($this->key) {
			$this->param('key', $this->key);
		}
		return $this -> get($this -> api_url . 'parent' . $this->params());
	}

	public function multiple() {
		if ($this->key) {
			$this->param('parent', $this->key);
		}
		return $this -> get($this -> api_url . 'multiple' . $this->params() . $this->filters());
	}

	public function single() {
		if ($this->id) {
			$this->param('id', $this->id);
		}
		if ($this->key) {
			$this->param('key', $this->key);
		}
		if ($this->parent) {
			$this->param('parent', $this->parent);
		}
		if ($this->slug) {
			$this->param('slug', $this->slug);
		}
		return $this -> get($this -> api_url . 'single' . $this->params());
	}

	public function social($service, $call = '', $params = array()) {
		$url = $this -> social_url . $service . '/';
		if (!empty($call)) {
			$url .= trim($call, '/') . '/';
		}
		foreach ($params as $key => $val) {
			$url .= $key . ':' . $val . '/';
		}
		return $this -> get($url);
	}

	public function save($type, $data) {
		$url = 'save/' . $type;
		return $this -> post($url, $data);
	}

	public function outline() {
		return $this -> get($this -> api_url . 'outline');
	}

	public function formbuilder($form = false, $page = false) {
		if (!$this -> formbuilder_init) {
			$this -> formbuilder_init = true;
			echo '<script type="text/javascript" src="' . $this -> server . '/frontend/formbuilder.js"></script>';
		}

		if ($form) {
			$form['page'] = $page['id'];
			$form['client'] = $page['client_id'];
			echo '<div id="' . $form['id'] . '"></div>';
			echo '<script type="text/javascript">$(function() {formbuilder.insert(' . json_encode($form) . ');});</script>';
		}
	}

	private function get($url, $allow_debug = true) {
		$this->reset();
		$data = json_decode(file_get_contents($url), true);
		if ($this -> debug && $allow_debug) {
			$trace = debug_backtrace();
			echo '<div style="background-color:white;"><pre>';
			echo str_repeat("*", 120) . '<br>';
			echo 'FILE: ' . $trace[1]['file'] . '<br>';
			echo 'LINE: ' . $trace[1]['line'] . '<br>';
			echo 'API : '. $url . '<br>';
			print_r($data);
			echo '</pre></div>';
		}
		return $data;
	}

	private function post($url, $data) {
		$this->reset();
		$url = $this -> api_url . $url;
		$data = array('data' => serialize($data));
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		$output = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);
		return $output;
	}

}
?>