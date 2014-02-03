<?php (defined('BASEPATH')) || exit('No direct script access allowed');

(defined('EXT')) || define('EXT', '.php');

global $CFG;

/* Get module locations from config settings, or use the default module location
 * and offset
 */
is_array(Modules::$locations = $CFG->item('modules_locations')) OR Modules::$locations = array(
	APPPATH.'modules/' => '../modules/',
);

/* PHP5 spl_autoload */
spl_autoload_register('Modules::autoload');

/**
 * Modular Extensions - HMVC
 *
 * Adapted from the CodeIgniter Core Classes
 * @link	http://codeigniter.com
 *
 * Description:
 * This library provides functions to load and instantiate controllers
 * and module controllers allowing use of modules and the HMVC design pattern.
 *
 * Install this file as application/third_party/MX/Modules.php
 *
 * @copyright	Copyright (c) 2011 Wiredesignz
 * @version 	5.4
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 **/
class Modules
{
	public static $routes, $registry, $locations;

	/**
	* Run a module controller method
	* Output from module is buffered and returned.
	**/
	public static function run($module) {

		$method = 'index';

		if(($pos = strrpos($module, '/')) != FALSE) {
			$method = substr($module, $pos + 1);
			$module = substr($module, 0, $pos);
		}

		if($class = self::load($module)) {

			if (method_exists($class, $method))	{
				ob_start();
				$args = func_get_args();
				$output = call_user_func_array(array($class, $method), array_slice($args, 1));
				$buffer = ob_get_clean();
				return ($output !== NULL) ? $output : $buffer;
			}
		}

		log_message('error', "Module controller failed to run: {$module}/{$method}");
	}

	/** Load a module controller **/
	public static function load($module) {

		(is_array($module)) ? list($module, $params) = each($module) : $params = NULL;

		/* get the requested controller class name */
		$alias = strtolower(basename($module));

		/* create or return an existing controller from the registry */
		if ( ! isset(self::$registry[$alias])) {

			/* find the controller */
			list($class) = CI::$APP->router->locate(explode('/', $module));

			/* controller cannot be located */
			if (empty($class)) return;

			/* set the module directory */
			$path = APPPATH.'controllers/'.CI::$APP->router->fetch_directory();

			/* load the controller class */
			$class = $class.CI::$APP->config->item('controller_suffix');
			self::load_file($class, $path);

			/* create and register the new controller */
			$controller = ucfirst($class);
			self::$registry[$alias] = new $controller($params);
		}

		return self::$registry[$alias];
	}

	/** Library base class autoload **/
	public static function autoload($class) {

		/* don't autoload CI_ prefixed classes or those using the config subclass_prefix */
		if (strstr($class, 'CI_') OR strstr($class, config_item('subclass_prefix'))) return;

		/* autoload Modular Extensions MX core classes */
		if (strstr($class, 'MX_') AND is_file($location = dirname(__FILE__).'/'.substr($class, 3).EXT)) {
			include_once $location;
			return;
		}

		/* autoload core classes */
		if(is_file($location = APPPATH.'core/'.$class.EXT)) {
			include_once $location;
			return;
		}

		/* autoload library classes */
		if(is_file($location = APPPATH.'libraries/'.$class.EXT)) {
			include_once $location;
			return;
		}

		/* autoload Bonfire library classes */
		if(is_file($location = BFPATH.'libraries/'.$class.EXT)) {
			include_once $location;
			return;
		}
	}

	/** Load a module file **/
	public static function load_file($file, $path, $type = 'other', $result = TRUE)	{

		$file = str_replace(EXT, '', $file);
		$location = $path.$file.EXT;

		if ($type === 'other') {
			if (class_exists($file, FALSE))	{
				log_message('debug', "File already loaded: {$location}");
				return $result;
			}
			include_once $location;
		} else {

			/* load config or language array */
			include $location;

			if ( ! isset($$type) OR ! is_array($$type))
				show_error("{$location} does not contain a valid {$type} array");

			$result = $$type;
		}
		log_message('debug', "File loaded: {$location}");
		return $result;
	}

	/**
	* Find a file
	* Scans for files located within modules directories.
	* Also scans application directories for models, plugins and views.
	* Generates fatal error if file not found.
	**/
	public static function find($file, $module, $base) {

		$segments = explode('/', $file);

		$file = array_pop($segments);
		$file_ext = (pathinfo($file, PATHINFO_EXTENSION)) ? $file : $file.EXT;

		$path = ltrim(implode('/', $segments).'/', '/');
		$module ? $modules[$module] = $path : $modules = array();

		if ( ! empty($segments)) {
			$modules[array_shift($segments)] = ltrim(implode('/', $segments).'/','/');
		}

		foreach (Modules::$locations as $location => $offset) {
			foreach($modules as $module => $subpath) {
				$fullpath = $location.$module.'/'.$base.$subpath;

				if ($base == 'libraries/' AND is_file($fullpath.ucfirst($file_ext)))
					return array($fullpath, ucfirst($file));

				if (is_file($fullpath.$file_ext)) return array($fullpath, $file);
			}
		}

		return array(FALSE, $file);
	}

	/** Parse module routes **/
	public static function parse_routes($module, $uri) {

		/* load the route file */
		if ( ! isset(self::$routes[$module])) {
			if (list($path) = self::find('routes', $module, 'config/') AND $path)
				self::$routes[$module] = self::load_file('routes', $path, 'route');
		}

		if ( ! isset(self::$routes[$module])) return;

		/* parse module routes */
		foreach (self::$routes[$module] as $key => $val) {

			$key = str_replace(array(':any', ':num'), array('.+', '[0-9]+'), $key);

			if (preg_match('#^'.$key.'$#', $uri)) {
				if (strpos($val, '$') !== FALSE AND strpos($key, '(') !== FALSE) {
					$val = preg_replace('#^'.$key.'$#', $val, $uri);
				}

				return explode('/', $module.'/'.$val);
			}
		}
	}

    /**
     * Determines whether a controller exists for a module.
     *
     * @param $controller string The name of the controller to look for (without the .php)
     * @param $module string The name of module to look in.
     *
     * @return boolean
     */
    public static function controller_exists($controller=null, $module=null)
    {
		if (empty($controller) || empty($module)) {
			return false;
		}

		// Look in all module paths
        $folders = Modules::folders();
		foreach ($folders as $folder) {
			if (is_file($folder . $module . '/controllers/' . $controller . '.php')) {
				return true;
			}
		}

		return false;
    }

	/**
	 * Finds the path to a module's file.
	 *
	 * @param $module string The name of the module to find.
	 * @param $folder string The folder within the module to search for the file (ie. controllers).
	 * @param $file string The name of the file to search for.
	 *
	 * @return string The full path to the file.
	 */
    public static function file_path($module=null, $folder=null, $file=null)
    {
		if (empty($module) || empty($folder) || empty($file)) {
			return false;
		}

        $folders = Modules::folders();
		foreach ($folders as $module_folder) {
			$test_file = $module_folder . $module .'/'. $folder .'/'. $file;

			if (is_file($test_file)) {
				return $test_file;
			}
		}
    }

	/**
	 * Returns the path to the module and it's specified folder.
	 *
	 * @param $module string The name of the module (must match the folder name)
	 * @param $folder string The folder name to search for. (Optional)
	 *
	 * @return string The path, relative to the front controller.
	 */
    public static function path($module=null, $folder=null)
    {
        $folders = Modules::folders();
		foreach ($folders as $module_folder) {
			if (is_dir($module_folder . $module)) {
				if ( ! empty($folder) && is_dir($module_folder . $module . '/' . $folder)) {
					return $module_folder . $module . '/' . $folder;
				} else {
					return $module_folder . $module . '/';
				}
			}
		}
    }

	/**
	 * Returns an associative array of files within one or more modules.
	 *
	 * @param $module_name string If not NULL, will return only files from that module.
	 * @param $module_folder string If not NULL, will return only files within that folder of each module (ie 'views')
	 * @param $exclude_core boolean Whether we should ignore all core modules.
	 *
	 * @return array An associative array, like: array('module_name' => array('folder' => array('file1', 'file2')))
	 */
    public static function files($module_name=null, $module_folder=null, $exclude_core=false)
	{
		if ( ! function_exists('directory_map')) {
			$ci =& get_instance();
			$ci->load->helper('directory');
		}

		$files = array();

		foreach (Modules::folders() as $path) {
			// If we're ignoring core modules and we find the core module folder... skip it.
			if ($exclude_core === true && strpos($path, 'bonfire/modules') !== false) {
				continue;
			}

			if ( ! empty($module_name) && is_dir($path . $module_name)) {
				$path = $path . $module_name;
				$modules[$module_name] = directory_map($path);
			} else {
				$modules = directory_map($path);
			}

			// If the element is not an array, we know that it's a file,
			// so we ignore it, otherwise it is assumbed to be a module.
			if ( ! is_array($modules) || ! count($modules)) {
				continue;
			}

			foreach ($modules as $mod_name => $values) {
				if (is_array($values)) {
					// Add just the specified folder for this module
					if ( ! empty($module_folder) && isset($values[$module_folder]) && count($values[$module_folder])) {
						$files[$mod_name] = array(
							$module_folder	=> $values[$module_folder],
						);
					}
					// Add the entire module
					elseif (empty($module_folder)) {
						$files[$mod_name] = $values;
					}
				}
			}
		}

		return count($files) ? $files : false;
	}

	/**
	 * Returns the 'module_config' array from a modules config/config.php
	 * file. The 'module_config' contains more information about a module,
	 * and even provide enhanced features within the UI. All fields are optional
	 *
	 * @author Liam Rutherford (http://www.liamr.com)
	 *
	 * <code>
	 * $config['module_config'] = array(
	 * 	'name'			=> 'Blog', 			// The name that is displayed in the UI
	 *	'description'	=> 'Simple Blog',	// May appear at various places within the UI
	 *	'author'		=> 'Your Name',		// The name of the module's author
	 *	'homepage'		=> 'http://...',	// The module's home on the web
	 *	'version'		=> '1.0.1',			// Currently installed version
	 *	'menu'			=> array(			// A view file containing an <ul> that will be the sub-menu in the main nav.
	 *		'context'	=> 'path/to/view'
	 *	)
	 * );
	 * </code>
	 *
	 * @param $module_name string The name of the module.
	 * @param $return_full boolean If true, will return the entire config array. If false, will return only the 'module_config' portion.
	 *
	 * @return array An array of config settings, or an empty array if empty/not found.
	 */
    public static function config($module_name=null, $return_full=false)
    {
		$config_param = array();
		$config_file = Modules::file_path($module_name, 'config', 'config.php');

		if (file_exists($config_file)) {
			include($config_file);

			/* Check for the optional module_config and serialize if exists*/
			if (isset($config['module_config'])) {
				$config_param =$config['module_config'];
			} elseif ($return_full === true && isset($config) && is_array($config)) {
				$config_param = $config;
			}
		}

		return $config_param;
    }

	/**
	 * Returns an array of the folders that modules are allowed to be stored in.
	 * These are set in *bonfire/application/third_party/MX/Modules.php*.
	 *
	 * @return array The folders that modules are allowed to be stored in.
	 */
    public static function folders()
    {
        return array_keys(Modules::$locations);
    }

    public static function list_modules($exclude_core=false)
    {
		if ( ! function_exists('directory_map')) {
			$ci =& get_instance();
			$ci->load->helper('directory');
		}

		$map = array();

        $folders = Modules::folders();
		foreach ($folders as $folder)
		{
			// If we're excluding core modules and this module
			// is in the core modules folder... ignore it.
			if ($exclude_core && strpos($folder, 'bonfire/modules') !== false) {
				continue;
			}

			$dirs = directory_map($folder, 1);
			if ( ! is_array($dirs)) {
				$dirs = array();
			}

			$map = array_merge($map, $dirs);
		}

		// Clean out any html or php files
		if ($count = count($map)) {
			for ($i = 0; $i < $count; $i++) {
				if (strpos($map[$i], '.html') !== false || strpos($map[$i], '.php') !== false) {
					unset($map[$i]);
				}
			}
		}

		return $map;
    }
}