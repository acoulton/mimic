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
	
	public function record(Request $request)
	{
		
	}
}