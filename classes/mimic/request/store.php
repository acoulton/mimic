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
	/**
	 *
	 * @var Mimic
	 */
	protected $_mimic = null;
	
	/**
	 * Creates a new Mimic_Request_Store instance - requires a mimic instance
	 * to be injected.
	 * 
	 * @param Mimic $mimic 
	 */
	public function __construct(Mimic $mimic)
	{
		$this->_mimic = $mimic;
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
		// Check that the request has been executed
		if ($request->response() === NULL)
		{
			throw new Mimic_Exception_NotExecuted(
				'Could not record the :method request to :uri because the request has not been executed', 
					array(':method'=>$request->method(),':uri'=>$request->uri()));
		}
		
		// Prepare the filesystem and request data
		$request_store_path = $this->_request_store_path($request, true);
		$request_data = array();
		
		
		
		// Make an entry in the index file
		$requests = array($request_data);
		file_put_contents($request_store_path.'request_index.php',
				'<?php'.PHP_EOL
				.'return '.var_export($requests,true).';');
	}
	
	/**
	 * Calculates (and optionally creates) the path for storage of the index
	 * and response files for the given request.
	 * 
	 * @param Request $request
	 * @param boolean $create Whether to create the path if not found
	 * @return string 
	 */
	protected function _request_store_path(Request $request, $create = false)
	{		
		// Basic path is http/host/com/url/etc
		$url_parts = parse_url($request->uri());				
		$path = $url_parts['scheme'].'/'
				.str_replace('.', '/', $url_parts['host'])
				.$url_parts['path'];
		
		// Ensure there is a trailing /
		if (substr($path, -1) != '/')
		{
			$path .= '/';
		}
		
		// Combine with the current mimic mime_path to get a full path
		$path = $this->_mimic->get_mime_path().$path;
		
		// Create if required
		if ($create)
		{
			mkdir($path, '0700', true);
		}
		
		return $path;
	}
}