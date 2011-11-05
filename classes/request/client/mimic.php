<?php defined('SYSPATH') or die('No direct script access.');
/**
 * [Request_Client_External] Mimic driver handles the core functionality of mimic -
 * matching requests against existing known requests and replaying responses, or
 * executing unmatched requests and recording the responses for future use.
 * 
 * @package    Mimic
 * @category   Requests
 * @author     Andrew Coulton
 * @copyright  (c) 2011 Ingenerator
 * @license    http://kohanaframework.org/license 
 */
class Request_Client_Mimic extends Request_Client_External 
{
	/**
	 * The Mimic instance where configuration data is held
	 * @var Mimic
	 */
	protected $_mimic = null;
	
	/**
	 * The Mimic_Request_Store responsible for loading and recording requests
	 * @var Mimic_Request_Store
	 */
	protected $_store = null;
		
	/**
	 * Entry point - determines whether to load a stored response, record a new 
	 * one or throw an exception
	 *
	 * @param   Request   request to send
	 * @return  Response
	 */
	public function _send_message(Request $request)
	{
		// Create or retrieve the Mimic and Mimic_Request_Store instances
		$mimic = $this->mimic();
		$store = $this->store();
		
		// Log every request
		$mimic->log_request($request);
		
		// Check to see if the request can be replayed from storage
		if ($response = $store->load($request))
		{
			return $response;
		}
		else
		{
			// Not matched to a recording - check that recording is enabled
			if ($mimic->enable_recording())
			{
				// Execute the subrequest, record the response and return
				$this->_send_external($request);
				$store->record($request);
				return $request->response();
			}
			else
			{				
				throw new Mimic_Exception_RecordingDisabled(
						'Mimic recording is not enabled, so the :method request to :uri was not executed',
						array(
							':method' => $request->method(),
							':uri' => $request->uri()
						));
			}
		}
	}
	
	/**
	 * Sets or gets a [Mimic] instance - provided to allow injection
	 * of a [Mimic] - primarily for testing purposes.
	 * 
	 * @param Mimic $mimic
	 * @return Mimic
	 */
	public function mimic(Mimic $mimic = null)
	{
		// Explicitly set a mimic if called as setter
		if ($mimic !== null)
		{
			$this->_mimic = $mimic;
			$this->_store = null;
			return $mimic;
		}
		
		// Get the singleton mimic if required
		if ($this->_mimic === null)
		{
			$this->_mimic = Mimic::instance();
		}
		return $this->_mimic;
	}
	
	/**
	 * Sets or gets a [Mimic_Request_Store] instance - provided to allow injection
	 * of a [Mimic_Request_Store] - primarily for testing purposes.
	 * 
	 * @param Mimic_Request_Store $store
	 * @return Mimic_Request_Store 
	 */
	public function store(Mimic_Request_Store $store = null)
	{		
		// Explicitly set a mimic if called as setter
		if ($store !== null)
		{
			$this->_store = $store;
			return $store;
		}
		
		// Get a new instance if required
		if ($this->_store === null)
		{
			$this->_store = new Mimic_Request_Store($this->mimic());
		}
		
		// Return the instance
		return $this->_store;

	}
		
	/**
	 * Executes the external request using the fallback client
	 * 
	 * @param Request $request 
	 */
	protected function _send_external($request)
	{
		// Load the external Request_Client class
		$client = $this->_mimic->external_client();
		$client = new $client;		
		/* @var $client	Request_Client_External */
		
		// Execute the request and return the response
		return $client->_send_message($request);
	}
		
} // End Request_Client_Mimic