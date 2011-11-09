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
	protected $_mimic = NULL;
	
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
	
	/**
	 * Searches the index for a request definition that matches the current request
	 * and if found, creates and returns a response object.
	 * 
	 * @param Request $request 
	 * @return Response
	 */
	public function load(Request $request)
	{
		$request_data = $this->_search_index($request, $request_index_array, $matched_index, $index_file);
		
		if ($request_data)
		{
			// Create a response object for the data, and load the body from disk
			$response = $request->create_response();
			$response->status($request_data['response']['status']);
			$response->headers($request_data['response']['headers']);
			if ($body = $request_data['response']['body_file'])
			{
				$response->body(file_get_contents($body));
			}
			
			// Add debug headers if required
			if ($this->_mimic->debug_headers())
			{
				$response->headers('X-Mimic-IndexFile', $index_file);
				$response->headers('X-Mimic-DefinitionCount', count($request_index_array));
				$response->headers('X-Mimic-MatchedIndex', $matched_index);
			}
			
			return $response;
		}
		return NULL;		
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
		$response = $request->response();
		if ($response === NULL)
		{
			throw new Mimic_Exception_NotExecuted(
				'Could not record the :method request to :uri because the request has not been executed', 
					array(':method'=>$request->method(),':uri'=>$request->uri()));
		}
		
		// Prepare the request entry
		$this_request_data = array(
			'method' => $request->method(),
			'headers'=> (array) $request->headers(),
			'query' => $request->query()	
			);
		
		// Search in the index to check if this request matches an existing definition
		$request_store_path = $this->_request_store_path($request, TRUE);
		if ( ! ($matched_request = $this->_search_index($request, $request_index_array, $matched_index)))
		{
			// It doesn't exist - append a new item
			$matched_index = count($request_index_array);
			$request_entry = $this_request_data;
		}
		else
		{
			if ( ! $this->_mimic->enable_updating())
			{
				throw new Mimic_Exception_UpdatingDisabled(
						"Mimic updating is disabled - could not update entry :matched_index in :request_store_path",
						array(':matched_index'=>$matched_index,
							':request_store_path'=>$request_store_path));
			}
			
			// Update the existing request entry - store the actual request executed
			$request_entry = $matched_request;			
			$request_entry['_executed_request'] = $this_request_data;
		}
		
		// Store the item's response
		$formatter = $this->_get_formatter($response->headers('Content-Type'));
		$request_entry['response'] = array(
			'status' => $response->status(),
			'headers' => (array) $response->headers(),
			'body_file' => $formatter->put_contents(
					$request_store_path, "response_$matched_index", $response->body())
			);
								
		// Write the index file		
		$request_index_array[$matched_index] = $request_entry;
		file_put_contents($request_store_path.'request_index.php',
				'<?php'.PHP_EOL
				.'return '.$this->_export_array_pretty($request_index_array).';');
	}
	
	/**
	 * Wraps var_export to give a more compact formatting of arrays - closer
	 * to the Kohana coding standards for config files.
	 * 
	 * @param array $array
	 * @return string 
	 */
	protected function _export_array_pretty($array)
	{
		$code = var_export($array, TRUE);
		$code = preg_replace('/=>\s+array \(/', '=> array(', $code);
		$code = preg_replace('/array\s?\(\s+\),/', 'array(),', $code);	
		return $code;
	}
	
	/**
	 * Calculates (and optionally creates) the path for storage of the index
	 * and response files for the given request.
	 * 
	 * @param Request $request
	 * @param boolean $create Whether to create the path if not found
	 * @return string 
	 */
	protected function _request_store_path(Request $request, $create = FALSE)
	{		
		// Basic path is http/host/com/url/etc
		$url_parts = parse_url($request->uri());				
		$path = $url_parts['scheme'].'/'
				.$url_parts['host']
				.$url_parts['path'];
		
		// Ensure there is a trailing /
		if (substr($path, -1) != '/')
		{
			$path .= '/';
		}
		
		// Combine with the current mimic mime_path to get a full path
		$path = $this->_mimic->get_mime_path().$path;
		
		// Create if required
		if ($create AND ( ! file_exists($path)))
		{
			mkdir($path, 0700, TRUE);
		}
		
		return $path;
	}
	
	/**
	 * Gets a Mimic_Response_Formatter for the given content type
	 * 
	 * @staticvar string $formatters Local cache of the config
	 * @param string $content_type
	 * @return Mimic_Response_Formatter 
	 */
	protected function _get_formatter($content_type)
	{
		static $formatters = NULL;
		if ( ! $formatters)
		{
			$config = Kohana::$config->load('mimic');
			$formatters = $config['response_formatters'];
		}
		
		// Parse a combined content-type value
		$segments = explode(';', $content_type);
		if (is_array($segments))
		{
			$content_type = trim($segments[0]);
		}
		
		// Get the specific formatter for this content type,
		// Or the default generic formatter
		if (isset($formatters[$content_type]))
		{
			$class = $formatters[$content_type];
		}
		else
		{
			$class = $formatters['*'];
		}
		
		return new $class;
	}
	
	/**
	 * Searches within a request index file to identify the first definition that
	 * matches the passed in request. Returns an array of request/response data if 
	 * found, or NULL if not.
	 * 
	 * @param Request $request
	 * @param array $request_index_array Passed by reference and returns the full index array
	 * @param integer $matched_index Passed by reference and returns index of the matched entry
	 * @param string $index_file Full path and name of the index file
	 * @return array 
	 */
	protected function _search_index($request, & $request_index_array = array(), & $matched_index = null, & $index_file = null)
	{
		// Check the index file exists and load it into memory
		$data_path = $this->_request_store_path($request);
		$index_file = $data_path.'request_index.php';
		if ( ! file_exists($index_file))
		{
			return FALSE;
		}		
		$request_index_array = include($index_file);
		
		// Test each definition in sequence		
		$matched = FALSE;
		foreach ($request_index_array as $index_key => $request_definition)
		{		
		
			// Test the request method
			if ( ! $this->_matches_method($request->method(), $request_definition['method']))
			{
				continue;
			}
			
			// Test the headers
			if ( ! $this->_matches_array((array) $request->headers(),
					$request_definition['headers']))
			{
				continue;
			}
			
			// Test the query params
			if ( ! $this->_matches_array((array) $request->query(), 
					$request_definition['query']))
			{
				continue;
			}
			
			// As soon as a match is found, break
			$matched = TRUE;
			break;
		}
		
		if ( ! $matched)
		{
			return FALSE;
		}

		$matched_index = $index_key;
		$matched_request = $request_definition;
				
		// Make a full path to the body file
		if (isset($matched_request['response']['body_file']))
		{
			$matched_request['response']['body_file'] = $data_path.$matched_request['response']['body_file'];
		}
		return $matched_request;
	}
	
	/**
	 * Tests if the method matches a request definition
	 * 
	 * @param string $request_method The method from the request
	 * @param string $criteria_method The method from the index entry
	 * @return boolean 
	 */
	protected function _matches_method($request_method, $criteria_method)
	{
		if ($request_method == $criteria_method)
		{
			return TRUE;
		}
		elseif (($request_method == 'HEAD') AND ($criteria_method == 'GET'))
		{
			return TRUE;
		}
		elseif ($criteria_method == '*')
		{
			return TRUE;
		}
		else
		{
			return FALSE;			
		}				
	}
	
	/**
	 * Allocates a match score for the request header/query array, including
	 * allowing for Mimic_Request_Wildcard_Require values.
	 * 
	 * @param array $request_array	The header/query array from the request
	 * @param array $criteria_array The header/query array from the index entry	 
	 * @return boolean 
	 */
	protected function _matches_array($request_array, $criteria_array)
	{
	
		// Verify that all required keys are present with the correct values
		foreach ($criteria_array as $criteria_key => $criteria_value)
		{
			// Cannot match if the criteria is not there at all
			if ( ! isset($request_array[$criteria_key]))
			{
				return FALSE;
			}
			
			if ($request_array[$criteria_key] === $criteria_value)
			{
				// It matches
			}
			elseif ($criteria_value instanceof Mimic_Request_Wildcard_Require)
			{
				// It matches a wildcard
			}
			else
			{
				return FALSE;
			}
			
			// Remove from the request array
			unset($request_array[$criteria_key]);
		}
		
		// Check if the request array has extra keys
		if (count($request_array))
		{
			return FALSE;
		}
		
		return TRUE;
	}
	
}