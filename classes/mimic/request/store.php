<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Mimic_Request_Store handles saving request/response pairs to disk for future
 * use, and loading a saved response based on a given request.
 * 
 * @package    Mimic 
 * @author     Andrew Coulton
 * @copyright  (c) 2011 Ingenerator
 * @license    http://kohanaframework.org/license 
 */
class Mimic_Request_Store
{
	protected $_mimic = null;
	
	/**
	 * Creates a new Mimic_Request_Store instance - requires a mimic instance
	 * to be injected.
	 * 
	 * @param Mimic $mimic 
	 */
	public function __construct(Mimic $mimic)
	{
		
	}
	
	public function load(Request $request)
	{
		
	}
	
	/**
	 * Record the request (and its response) in the appropriate index and
	 * body files for replaying at a future date.
	 * 
	 * @param Request $request 
	 * @throws Mimic_Exception_NotExecuted If the request has not been executed
	 */
	public function record(Request $request)
	{
		if ($request->response() === NULL)
		{
			throw new Mimic_Exception_NotExecuted(
				'Could not record the :method request to :uri because the request has not been executed', 
					array(':method'=>$request->method(),':uri'=>$request->uri()));
		}
	}
}