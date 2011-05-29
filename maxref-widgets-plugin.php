<?php
class mrefWidgetsPlugin {

	var $version = '1.9.6';
	var $url = '';
	var $plugin_name = '';
	var $plugin_base = '';
	var $debugging = false;	
	var $pre = 'mref';
	
	function mrefWidgetsPlugin() {
		return true;
	}
	
	function register_plugin($name = '', $base = '') {
		$this -> plugin_name = $name;
		$this -> plugin_base = rtrim(dirname($base), '/');
		
		if (function_exists('load_plugin_textdomain')) {		
			load_plugin_textdomain($this -> plugin_name, PLUGINDIR .'/'. $this -> plugin_name . '/');
		}
		
		if ($this -> debugging) {
			global $wpdb;
			$wpdb -> show_errors();
		}
		
		return true;
	}
	
	function initialize_options() {
		$dateformats = array(
			"Y-m-d H:i:s",
			"F j, Y, g:i a",
			"m.d.y",
			"j, n, Y",
			"Ymd",
			'h-i-s, j-m-y',
			"D M j G:i:s T Y",
			'H:m:s',
			"H:i:s",
		);
	
		$this -> add_option('dateformats', $dateformats);
		
		return true;
	}
	
	function add_option($name = '', $value = '') {
		if (add_option($this -> pre . $name, $value)) {
			return true;
		}
		
		return false;
	}
	
	function update_option($name = '', $value = '') {
		if (update_option($this -> pre . $name, $value)) {
			return true;
		}
		
		return false;
	}
	
	function get_option($name = '', $stripslashes = true) {
		if ($option = get_option($this -> pre . $name)) {
			if (@unserialize($option) !== false) {
				return unserialize($option);
			}
			
			if ($stripslashes == true) {
				$option = stripslashes_deep($option);
			}
			
			return $option;
		}
		
		return false;
	}
	
	function add_action($action, $function = '', $priority = 10, $params = array()) {
		if (add_action($action, array($this, (empty($function)) ? $action : $function), $priority, $params)) {
			return true;
		}
		
		return false;
	}
	
	function url() {
		$url = get_bloginfo('wpurl') . substr($this -> plugin_base, strlen(realpath(ABSPATH)));
		return $url;
	}
	
	function get_pages($args = array()) {
		global $wpdb, $items, $levels, $usedpages;
		
		if (!empty($args)) {
			if (!empty($levels) || $levels != 0) {
				if (empty($usedpages) || (!empty($usedpages) && !in_array($args['child_of'], $usedpages))) {
					$levels--;
					$usedpages[] = $args['child_of'];
					
					extract($args, EXTR_SKIP);
					
					$page_query = "SELECT `ID`, `post_title` FROM `" . $wpdb -> posts . "`";
					$page_query .= (empty($child_of) || $child_of == "all") ? " WHERE `post_type` = 'page'" : " WHERE `post_type` = 'page' AND `post_parent` = '" . $child_of . "'";
					$order = (empty($order) || $order == "ASC") ? 'ASC' : 'DESC';
					$orderby = (empty($orderby) || $orderby == "name") ? 'post_title' : 'post_date';
					$page_query .= " ORDER BY `" . $orderby . "` " . $order . "";
					
					if ($pages = $wpdb -> get_results($page_query)) {
						foreach ($pages as $page) {
							$usedpages[] = $page -> ID;
							$this -> get_pages(array('child_of' => $page -> ID));
							
							$items[] = array(
								'title'			=>	$page -> post_title,
								'href'			=>	get_permalink($page -> ID),
							);
						}
					}
				}
			}
		}
		
		return $items;
	}
	
	function get_categories($args = array()) {
		global $items, $levels;
		
		if (!empty($args)) {
			if (!empty($levels) || $levels != 0) {
				$levels--;
			
				if ($categories = get_categories($args)) {
					foreach ($categories as $category) {
						$this -> get_categories(array('parent' => $category -> cat_ID));
						
						$items[] = array(
							'title'			=>	$category -> cat_name,
							'href'			=>	get_category_link($category -> cat_ID),
						);
					}
				}
			}
		}
		
		return $items;
	}
	
	function render($file = '', $params = array(), $output = true, $folder = 'default') {
		if (!empty($file)) {
			$filefull = $this -> plugin_base . '/views/' . $folder . '/' . $file . '.php';
		
			if (file_exists($filefull)) {
				if ($output == false) {
					ob_start();
				}
				
				if (!empty($params)) {
					foreach ($params as $pkey => $pval) {
						${$pkey} = $pval;
					}
				}
				
				include($filefull);
				
				if ($output == false) {
					$data = ob_get_clean();
					return $data;
				}
				
				return true;
			}
		}
		
		return false;
	}
	
	function debug($var = array()) {
		if ($this -> debugging) {
			echo '<pre>' . print_r($var, true) . '</pre>';
		}
		
		return true;
	}
	
	var $adversion = true;
}

?>