<?php
/*
 * Version: 1.8
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
 * 1.8 Integrate with new API
 */

class Todaycms {
	public $client = '';
	private $api_url = 'http://todaycms-api.herokuapp.com';
	private $debug = false;
	private $config = false;
	private $formbuilder_init = false;
	private $joins = array();

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

		$this->config();
	}

	public function reset() {
		$this->joins = array();
	}

	public function config($key = false) {
		if (empty($this->config)) {
			$url = '/config';
			$this -> config = $this -> get($this -> api_url . $url.'?_token='.$this->client);
		};

		if ($key) {
			return $this->config[$key];
		} else {
			return $this->config;
		}
	}

	// chainable join calls
	public function join($foreign_key, $collection) {
		$this->joins[$foreign_key] = $collection;
		return $this;
	}

	public function parent($key) {
		throw new Exception('CMS PHP SDK no longer supports ->parent() ');
	}

	public function multiple($parent, $p = array()) {
		// Params
		$params = '';
		foreach ($p as $k => $v) {
			if ($k == 'filter') {
				$filters = array();
				foreach ($v as $field => $value) {
					$filters[$field] = $value;
				}
				$params .= '&filter='. urlencode(json_encode($filters));
			} else {
				$params .= '&' . $k . '=' . $v;
			}
		}
		// Collection
		$collection = $parent;

		$url = $this->api_url.'/collections/'.$collection.'?_token='.$this->client . $params;

		if (!empty($this->joins)) {
			$url .= '&join='.urlencode(json_encode($this->joins));
		}

		$data = $this -> get($url);

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

	public function single($p) {
		// Params
		$params = '';
		$id = false;
		foreach ($p as $k => $v) {
			if ($k == 'slug') {
				$params .= '&slug=' . $v;
			} elseif ($k == 'parent') {
				$this->parent = $v;
			} elseif ($k == 'key') {
				if (is_numeric($v)) {
					$id = $v;
				} else {
					$this->parent = $v;
				}
			} elseif ($k == 'id') {
				$id = $v;
			} else {
				$params .= '&' . $k . '=' . $v;
			}
		}

		if (!$this->parent) {
			throw new Exception('CMS PHP SDK call to ->single() requires parent collection to be defined ');
		} else {
			$collection = $this->parent;
		}

		$url = $this->api_url.'/collections/'.$collection;

		if ($id) {
			$url .= '/'.$id;
		}

		$url .= '?_token='.$this->client . $params;

		if (!empty($this->joins)) {
			$url .= '&join='.urlencode(json_encode($this->joins));
		}

		$data = $this -> get($url);

		if ($data) {
			$data[0] = $this->append_url($data[0]);
			$data[0] = $this->append_title($data[0]);
			return $data[0];
		} else {
			return false;
		}

		return $this -> load($url);
	}

	public function social($service, $call = '', $params = array()) {
		throw new Exception('CMS PHP SDK no longer supports ->social() ');
	}

	public function save($type, $data) {
		throw new Exception('CMS PHP SDK no longer supports ->save() ');
	}

	public function outline() {
		throw new Exception('CMS PHP SDK no longer supports ->outline() ');
	}

	public function formbuilder($form = false, $page = false) {
		if (!$this -> formbuilder_init) {
			$this -> formbuilder_init = true;
			echo '<script type="text/javascript" src="http://launch.todaymade.com/frontend/formbuilder.js"></script>';
		}

		if ($form) {
			$form['page'] = $page['id'];
			$form['client'] = $page['client_id'];
			echo '<div id="' . $form['id'] . '"></div>';
			echo '<script type="text/javascript">$(function() {formbuilder.insert(' . json_encode($form) . ');});</script>';
		}
	}

	private function get($url) {
		return $this->rest_call($url, 'get');
	}

	private function post($url, $data) {
		return $this->rest_call($url, 'post', $data);
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
		if ($data) {
			$data = http_build_query($data);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		}

		$output = curl_exec($ch);
		$info = curl_getinfo($ch);
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

	/** Helper methods to fill missing data (no longer automatically added by API)
	************************************************************************************/

	private function append_url($data) {
		if (isset($data['fields']['link'])) {
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

}
?>