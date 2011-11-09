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
	 * @var string The current external client to use
	 */
	protected $_external_client = null;
	
	/**
	 * @var boolean Whether to add debugging headers to matched response
	 */
	protected $_debug_headers = null;
	
	/**
	 * @var array The history of requests (with responses) that have been made
	 */
	protected $_request_stack = array();
	
	/**	 
	 * @var string The previously active external request client
	 */
	protected static $_previous_external_client = null;
	
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
	
	/**
	 * The Request_Client_External client type that was active before Mimic was
	 * loaded (used for recording new requests)
	 * 
	 * @return string
	 */
	public static function previous_external_client()
	{
		return self::$_previous_external_client;
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
		$config = Arr::merge((array)Kohana::$config->load('mimic'), $config);
		foreach ($config as $property => $value)
		{
			$property = '_'.$property;
			$this->$property = $value;
		}
		
		// Store the previously active request client and setup Mimic
		if (Kohana_Request_Client_External::$client != 'Request_Client_Mimic')
		{
			self::$_previous_external_client = Kohana_Request_Client_External::$client;
			Kohana_Request_Client_External::$client = 'Request_Client_Mimic';
		}
		
	}
	
	/**
	 * DRY implementation of getter/setter methods
	 * @param string $property
	 * @param mixed $value 
	 */
	protected function _getter_setter($property,$value)
	{
		// Use as a getter
		if ($value === null)
		{
			return $this->$property;
		}
		
		// Use as a setter
		$this->$property = $value;		
		return $this;		
	}
	
	/**
	 * Getter/Setter for the base_path where mime scenario files will be stored.
	 * If called with no parameters, returns the current setting.
	 * 
	 * @param string $path
	 * @return Mimic (If used as setter)
	 * @return string (If used as getter)
	 */
	public function base_path($path = null)
	{
		return $this->_getter_setter('_base_path', $path);
	}

	/**
	 * Getter/Setter for whether to enable recording of unmatched requests
	 * If called with no parameters, returns the current setting.
	 * 
	 * @param boolean $enable
	 * @return Mimic (If used as setter)
	 * @return string (If used as getter)
	 */
	public function enable_recording($enable = null)
	{
		return $this->_getter_setter('_enable_recording', $enable);
	}
	
	/**
	 * Getter/Setter for whether to enable updating of request recordings
	 * If called with no parameters, returns the current setting.
	 * 
	 * @param boolean $enable
	 * @return Mimic (If used as setter)
	 * @return string (If used as getter)
	 */	
	public function enable_updating($enable = null)
	{
		return $this->_getter_setter('_enable_updating', $enable);
	}
	
	/**
	 * Getter/Setter for whether to add debug headers to matched responses
	 * If called with no parameters, returns the current setting.
	 * 
	 * @param boolean $debug
	 * @return Mimic (If used as setter)
	 * @return boolean (If used as getter)
	 */	
	public function debug_headers($debug = null)
	{
		return $this->_getter_setter('_debug_headers', $debug);
	}
	
	/**
	 * Loads a new mime scenario - used to handle testing multiple responses from the 
	 * same request - for example if the destination server is down, or to 
	 * deal with authenticated vs anonymous access.
	 * 
	 * @param string $mime_name
	 * @return Mimic 
	 */
	public function load_mime($mime_name)
	{
		$this->_active_mime = $mime_name;
		return $this;
	}
	
	/**
	 * Returns the currently active mime scenario
	 * 
	 * @return type 
	 */
	public function get_active_mime()
	{
		return $this->_active_mime;
	}
	
	/**
	 * Returns the base path for request/response files in the current mime
	 * scenario.
	 * 
	 * @return string
	 */
	public function get_mime_path()
	{
		return $this->_base_path.DIRECTORY_SEPARATOR.$this->_active_mime.DIRECTORY_SEPARATOR;
	}
	
	/**
	 * Getter/Setter for the external request client to use for recording
	 * If called with no parameters, returns the current setting.
	 * 
	 * If the external_client property of this instance (from constructor or 
	 * config) is null, will use the value of Mimic::previous_external_client()
	 * 
	 * @param string $client
	 * @return Mimic (If used as setter)
	 * @return string (If used as getter)
	 */	
	public function external_client($client = null)
	{
		if ($client === null)
		{
			if ($this->_external_client === null)
			{
				return self::previous_external_client();
			}
			return $this->_external_client;
		}
		
		$this->_external_client = $client;
		return $this;		
	}
	
	/**
	 * Reset the request history and create a clean stack for testing - this
	 * would generally be called in your setUp() method of a test case in PHPUnit, 
	 * for example.
	 */
	public function reset_requests()
	{
		$this->_request_stack = array();
	}
	
	/**
	 * Get the number of requests that have been executed
	 * 
	 * @return integer
	 */
	public function request_count()
	{
		return count($this->_request_stack);
	}
	
	/**
	 * Get the request at position <$id> in the history, or get the full history
	 * if called with no parameters. Requests are collected as a stack, with
	 * the first request at id=0 and so on.
	 * 
	 * @param integer $id The position of the request to get
	 * @return Request
	 */
	public function request_history($id = null)
	{
		// With no parameter, return the full history
		if ($id === null)
		{
			return $this->_request_stack;
		}
		
		// Test that the requested id is in range
		if ( ! isset($this->_request_stack[$id]))
		{
			throw new RangeException("Request $id is out of range - ".count($this->_request_stack)." requests in the history");
		}
		
		// Return the request at $id
		return $this->_request_stack[$id];		
	}
	
	/**
	 * Get the most recent request from the stack - a quick helper method.
	 * 
	 *    // These are equivalent
	 *    $last = array_pop($mimic->request_history());
	 *    
	 *    $last = $mimic->request_history($mimic->request_count() - 1);
	 *    
	 *    $last = $mimic->last_request();
	 * 
	 * @return Request 
	 */
	public function last_request()
	{		
		$id = count($this->_request_stack) - 1;
		
		if ($id < 0)
		{
			throw new RangeException("Cannot return last request as there are no requests in the history");
		}
		
		return $this->_request_stack[$id];
	}
	
	/**
	 * Adds a request to the history stack - called by Request_Client_Mimic
	 * every time a request is executed.
	 * 
	 * [!!] It would be very rare to call this method from a client application/test case.
	 * @param Request $request 
	 */
	public function log_request($request)
	{
		$this->_request_stack[] = $request;
	}

} // End Mimic