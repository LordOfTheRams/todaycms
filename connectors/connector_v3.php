
<?php
/*************************************************************************************
 * TodayCMS PHP SDK
 * Author: Justin Walsh (justin@todaymade.com)
 * Copyright: (c) 2012 Todaymade
 * Version: 3.0
 *
 * This version of the connector is designed to be a drop in replacement
 * for older sites using the 1.x or 2.x versions of the connector. New projects
 * should be started using the 4.x version of the connector.
 *
 * Changelog:
 * 3.0 New Node API Version
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
	private $server = 'http://todaycms-api.herokuapp.com';
	private $api_url = 'http://todaycms-api.herokuapp.com';
	private $debug = false;
	private $config = false;
	private $formbuilder_init = false;

	private $id = false;
	private $key = false;
	private $slug = false;
	private $parent = false;
	private $filters = false;
	private $joins = false;
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

		$this -> config();
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
		$this->filters = false;
		$this->joins = false;
		$this->params = array();
	}

	public function id($value) {
		$this->id = $value;
		return $this;
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
			$url .= '&' . $k . '=' . $v;
		}
		return $url;
	}

	// chainable filter calls
	public function filter($key, $value) {
		$this->filters[$key] = $value;
		$this->param('filter', urlencode(json_encode($this->filters)));
		return $this;
	}

	// chainable join calls
	public function join($foreign_key, $collection) {
		$this->joins[$key] = $value;
		$this->param('join', urlencode(json_encode($this->joins)));
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
			$this -> config = $this -> get($this -> api_url . $url .'?_token='. $this -> client);
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

	public function formbuilder($form = false, $page = false) {
		if (!$this -> formbuilder_init) {
			$this -> formbuilder_init = true;
			//echo '<script type="text/javascript" src="' . $this -> server . '/frontend/formbuilder.js"></script>';
			echo '<script type="text/javascript" src="http://launch.todaymade.com/frontend/formbuilder.js"></script>';
		}

		if ($form) {
			$form['page'] = $page['id'];
			$form['client'] = $page['client_id'];
			echo '<div id="' . $form['id'] . '"></div>';
			echo '<script type="text/javascript">$(function() {formbuilder.insert(' . json_encode($form) . ');});</script>';
		}
	}

	public function social($p = false) {
		throw new Exception('CMS PHP SDK no longer supports the use of ->social() ');
	}

	public function group($p = false) {
		throw new Exception('CMS PHP SDK no longer supports the use of ->group() ');
	}

	public function outline($p = false) {
		throw new Exception('CMS PHP SDK no longer supports the use of ->outline() ');
	}

	public function multiple() {
		// Is key(id) called without a collection?
		if (is_numeric($this->key) && !$this->parent) {
			throw new Exception('CMS PHP SDK call to ->key(id) requires a parent collection ');
		}

		$data = $this -> get($this -> api_url . '/collections/'.$this->key.'?_token='.$this->client . $this->params());

		if ($data) {
			for ($i=0; $i<count($data); $i++) {
				$data[$i] = $this->append_url($data[$i]);
				$data[$i] = $this->append_title($data[$i]);
			}
			return $data;
		} else {
			return false;
		}
		
	}

	public function single() {
		if ($this->id) {
			$this->param('id', $this->id);
		}
		if ($this->slug) {
			$this->param('slug', $this->slug);
		}

		// Collection
		if ($this->key) {
			$collection = $this->key;
		} elseif ($this->parent) {
			$collection = $this->parent;
		} else {
			$collection = '';
		}

		// Is key(id) called without a collection?
		if (is_numeric($this->key) && !$this->parent) {
			throw new Exception('CMS PHP SDK call to ->key(id) requires a parent collection ');
		}

		$data = $this -> get($this -> api_url . '/collections/'.$collection.'?_token='.$this->client . $this->params());

		if ($data) {
			$data[0] = $this->append_url($data[0]);
			$data[0] = $this->append_title($data[0]);
			return $data[0];
		} else {
			return false;
		}
		
	}

	private function append_url($data) {
		if ($data['status'] == 3) {
			// Link Page
			$data['url'] = $data['fields']['link'];
            $data['url_target'] = $data['fields']['target'];
		} else {
			// Standard page
			$url_config = (isset($this->config[$data['parent']]['url'])?$this->config[$data['parent']]['url']:true);
            $data['url'] = $this->build_url($url_config, $data);
            $data['url_target'] = '_self';
		}

		return $data;
	}

	private function build_url($url, $data) {
		if ($url === true) {
            return (!empty($data['parent']) ? '/' . $data['parent'] : '') . '/' . $data['slug'];
        } else {
            $url = str_replace('{{slug}}', $data['slug'], $url);
            $url = str_replace('{{id}}', $data['id'], $url);
            return str_replace('{{parent}}', $data['parent'], $url);
        }
	}

	// Calculate the Title field based on first field in the object or slug
	private function append_title($data) {
		if (!isset($data['title'])) {
			$title = '';
			if (is_string($data['fields'][0])) {
				$title = $data['fields'][0];
			} elseif (isset($data['slug'])) {
				$title = ucfirst(str_replace('-', ' ', $data['slug']));
			}
			$data['title'] = $title;
		}
		return $data;
	}

	private function get($url) {
		$data = json_decode(file_get_contents($url), true);
		if ($this -> debug) {
			echo '<div style="background-color:white;"><pre>';
			echo $url . '<br>';

			// Display Filters
			if ($this->filters) {
				echo "Filter: " . $this->filters . '<br>';;
			}

			print_r($data);
			echo '</pre></div>';
		}
		$this->reset();

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