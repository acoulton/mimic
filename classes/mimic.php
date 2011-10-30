<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Mimic records and replays interactions with an external web service, allowing
 * rapid development of isolated, idempotent, unit and functional tests for
 * application code.
 * 
 * @package    Mimic 
 * @author     Andrew Coulton
 * @copyright  (c) 2011 Ingenerator
 * @license    http://kohanaframework.org/license 
 */
class Mimic 
{
	/**
	 * @var string The base path to use for storing request/response files
	 */
	protected $_base_path = null;
	
	/**
	 * @var boolean Whether recording is enabled
	 */
	protected $_enable_recording = null;
	
	/**
	 * @var boolean Whether updating is enabled
	 */
	protected $_enable_updating = null;
	
	/**
	 * @var string The current mime (scenario) to use
	 */
	protected $_active_mime = null;
	
	/**
	 * Provides a singleton implementation for Mimic. The singleton can be reset by
	 * passing TRUE as the second parameter - this is mostly intended for unit
	 * tests, but may be useful in some edge cases.
	 * 
	 *     // Get the singleton
	 *     $mimic = Mimic::instance();
	 * 
	 *     // Get a fresh singleton
	 *     $mimic = Mimic::instance(array(), TRUE);
	 * 
	 *     // Will throw Mimic_Exception_AlreadyInitialised if setting params on existing
	 *     // instance
	 *     try
	 *     {
	 *         $mimic = Mimic::instance(array('base_path'=>'fail'));
	 *     }
	 *     catch (Mimic_Exception_AlreadyInitialised $e)
	 *     {
	 *         // Not a valid operation - reset the singleton or 
	 *         // set individual properties
	 *     }
	 * 
	 * @staticvar Mimic $instance The current instance
	 * @param array $config Configuration data
	 * @param boolean $reset Whether to reset the current singleton
	 * @return Mimic 
	 */
	public static function instance($config = array(), $reset = null)
	{
		static $instance = null;

		if ($reset OR ( ! $instance))
		{
			$instance = new Mimic($config);
		}
		else
		{
			if (count($config))
			{
				throw new Mimic_Exception_AlreadyInitialised(
						'Cannot pass constructor parameters to an existing singleton');
			}
		}		
		return $instance;		
	}
	
	public static function previous_external_client()
	{
		
	}
	
	/**
	 * Constructs a new Mimic instance, optionally setting configuration data.
	 * Configuration data provided will be merged with the configuration in the "mimic" configuration group.
	 * 
	 * @param array $config 
	 */
	public function __construct($config = array())
	{
		// Merge configuration with passed params, and set properties
		$config = Arr::merge(Kohana::$config->load('mimic'), $config);
		foreach ($config as $property => $value)
		{
			$property = '_'.$property;
			$this->$property = $value;
		}	
	}
	
	public function base_path($path = null)
	{
		
	}
	
	public function enable_recording($enable = null)
	{
		
	}
	
	public function enable_updating($enable = null)
	{
		
	}
	
	public function load_mime($mime_name)
	{
		
	}
	
	public function get_active_mime()
	{
		
	}
	
	public function get_mime_path()
	{
		
	}
	
	public function external_client($client = null)
	{
		
	}
	
	public function reset_requests()
	{
		
	}
	
	public function request_count()
	{
		
	}
	
	public function request_history($id = null)
	{
		
	}
	
	public function last_request()
	{
		
	}
	
	public function log_request($request)
	{
		
	}

} // End Mimic