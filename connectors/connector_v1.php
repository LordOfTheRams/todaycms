<?php
/*
 * Version: 1.6
 *
 * Changelog:
 * 1.0 Initial release
 * 1.1 Added parent api call
 * 1.2 Bug Fix for Multiple call
 * 1.3 Added save api call
 * 1.4 Added social api call
 * 1.4.1 Added staging server option
 * 1.5 Added Formbuilder call
 * 1.6 Added link redirects to Single Call
 * 1.6.1 Removed the link redirects added in 1.6
 * 1.7 Added config api call
 */

class Todaycms {
	public $client = '';
	private $server = 'http://launch.todaymade.com';
	private $api_url = '';
	private $debug = false;
	private $config = false;
	private $formbuilder_init = false;

	public function __construct($client = '') {
		$this -> client = $client;

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

	public function config($key = false) {
		if (empty($this->config)) {
			$url = 'config';
			$this -> config = $this -> get($this -> api_url . $url);
		};

		if ($key) {
			return $this->config[$key];
		} else {
			return $this->config;
		}
	}

	public function parent($key) {
		$url = 'parent/key:' . $key;
		return $this -> get($this -> api_url . $url);
	}

	public function multiple($parent, $params = array()) {
		$url = 'multiple/parent:' . $parent;
		foreach ($params as $k => $v) {
			if ($k == 'filter') {
				$url .= '/filter:';
				$filters = array();
				foreach ($v as $field => $value) {
					$filters[] = $field . '--' . $value;
				}
				$url .= implode('||', $filters);
			} else {
				$url .= '/' . $k . ':' . $v;
			}
		}
		return $this -> get($this -> api_url . $url);
	}

	public function single($params) {
		$url = 'single';
		foreach ($params as $k => $v) {
			$url .= '/' . $k . ':' . $v;
		}

		return $this -> get($this -> api_url . $url);
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

	private function get($url) {
		$data = json_decode(file_get_contents($url), true);
		if ($this -> debug) {
			echo '<div style="background-color:white;"><pre>';
			echo $url . '<br>';
			print_r($data);
			echo '</pre></div>';
		}
		return $data;
	}

	private function post($url, $data) {
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