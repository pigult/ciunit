<?php

/*
* fooStack, CIUnit for CodeIgniter
* Copyright (c) 2008-2009 Clemens Gruenberger
* Released under the MIT license, see:
* http://www.opensource.org/licenses/mit-license.php
*/

//load Testing
require_once CIUPATH . 'libraries/CIUnitTestCase.php';

/**
 * CIUnit Class
 * guides CI to behave nicely during tests..
 *
 * during the tests you can use:
 *
 * in setUp function:
 * $this->CI = set_controller('controller_to_test');
 * to set a different CI controller active
 *
 * use your controller functions simply like:
 * $this->CI->function();
 *
 * created browser output is accessible like so
 * $output = output();
 * this function yields only once and is then reset to an empty string
 *
 * template vars like so:
 * $vars = viewvars();
 * they are also reset after this call
 */
class CIUnit
{
	private static $instance;
	public static $controller;
	public static $current;
	public static $controllers = array();
	public static $spyc;
	public static $fixture;

	/**
	 * If this class is suppose to be a Singleton shouldn't the constructor be private?
	 * Correct me if I am wrong but this doesn't prevent multiple instances of this class.
	 */
	public function __construct()
	{
		self::$instance =& $this;
	}

	public static function &get_CIU()
	{
		return self::$instance;
	}

	public static function &set_controller($controller = 'CI_Controller', $path = false)
	{
		$controller_name = array_pop(explode('/', $controller));
		//echo "\nc name ".$controller_name;
		//is it the current controller?
		if ($controller_name == self::$current) {
			//we have nothing to do, return current controller
			//echo "current found!"; die();
			output();
			viewvars();

			return self::$controller;
		}

		// the current controller must be archieved before littered
		$loader =& load_class('Loader', 'core');

		// reset all loaded data
		$loader->reset();

		//clean output / viewvars as well;
		if (isset(self::$controller->output)) {
			output();
			viewvars();
		}

		//the requested controller was loaded before?
		if (isset(self::$controllers[$controller_name])) {
			$old =& self::$controllers[$controller_name];
			self::$controller =& $old['address'];
			self::$current = $controller_name;
		} else {
			if (!class_exists($controller_name)) {
				if ($path && file_exists($path . $controller . EXT)) {
					include_once($path . $controller . EXT);
				} else {
					include_once(APPPATH . 'controllers/' . $controller . EXT);
				}
			}

			self::$current = $controller_name;
			self::$controllers[$controller_name] = array(
				'address' => new $controller_name(), 'models' => array()
			);
			self::$controller =& self::$controllers[$controller_name]['address'];
		}

		return self::$controller;
	}

	public static function &get_controller()
	{
		return self::$controller;
	}

	/**
	 * get filenames eg for running test suites
	 * $path is relative
	 */
	public static function files($pattern, $path = ".", $addpath = false)
	{
		// Swap directory separators to Unix style for consistency
		$path = str_replace("\\", "/", $path);

		if (substr($path, -1) !== "/") {
			$path .= "/";
		}

		$dir_handle = @opendir($path) or die("Unable to open $path");
		$outarr = array();

		while (false != ($file = readdir($dir_handle))) {
			if (preg_match($pattern, $file)) {
				if ($addpath) {
					$file = $path . $file;
				}
				$outarr[] = $file;
			}
		}
		//could also use preg_grep!
		closedir($dir_handle);

		return $outarr;
	}
}

//=== convenience functions ===
// instead of referring to CIUnit directly

/**
 * retrieves current CIUnit Class Singleton
 */
function &get_CIU()
{
	return CIUnit::get_CIU();
}

/**
 * sets CI controller
 */
function &set_controller($controller = 'CI_Controller', $path = false)
{
	return CIUnit::set_controller($controller, $path);
}

/**
 * retrieves current CI controller from CIUnit
 */
function &get_controller()
{
	return CIUnit::get_controller();
}

/**
 * retrieves the cached output from the output class
 * and resets it
 */
function output()
{
	return CIUnit::$controller->output->pop_output();
}

/**
 * retrieves the cached template vars from the loader class (stored here for assignment to views)
 * and resets them
 */
function viewvars()
{
	if (isset(CIUnit::$controller->load->_ci_cached_vars)) {
		$out = CIUnit::$controller->load->_ci_cached_vars;
		CIUnit::$controller->load->_ci_cached_vars = array();

		return $out;
	}

	return array();
}
