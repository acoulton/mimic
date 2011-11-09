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
		$request_data = $this->_search_index($request);
		
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
	 * Searches within a request index file to identify the most specific match
	 * for the passed in request. Returns an array of request/response data if 
	 * found, or NULL if not.
	 * 
	 * @param Request $request
	 * @param array $request_index_array Passed by reference and returns the full index array
	 * @param integer $matched_index Passed by reference and returns index of the matched entry
	 * @return array 
	 */
	protected function _search_index($request, & $request_index_array = array(), & $matched_index = null)
	{
		// Check the index file exists and load it into memory
		$data_path = $this->_request_store_path($request);
		if ( ! file_exists($data_path.'request_index.php'))
		{
			return FALSE;
		}		
		$request_index_array = include($data_path.'request_index.php');
		
		// Work through the index and score the quality of matches
		$matching_scores = array();		
		foreach ($request_index_array as $index_key => $request_criteria)
		{
			$scores = array();
			
			// Test the request method
			if ( ! $this->_score_match_method($request->method(), $scores, 
					$request_criteria['method']))
			{
				continue;
			}
			
			// Test the headers
			if ( ! $this->_score_match_array((array) $request->headers(), $scores, 
					$request_criteria['headers'], 'header'))
			{
				continue;
			}
			
			// Test the query params
			if ( ! $this->_score_match_array((array) $request->query(), $scores, 
					$request_criteria['query'], 'query'))
			{
				continue;
			}
			
			// Combine the scores to give an overall value
			// Request method always trumps everything else, header and query are equal
			$score = (1000 * $scores['method']) + $scores['header'] + $scores['query'];			
			$matching_scores[$index_key] = $score;
			
		}
		
		// Take the highest scored item
		if ( ! $matching_scores)
		{
			return FALSE;
		}
		arsort($matching_scores);
		reset($matching_scores);
		$matched_index = key($matching_scores);
		$matched_request = $request_index_array[$matched_index];
				
		// Make a full path to the body file
		if (isset($matched_request['response']['body_file']))
		{
			$matched_request['response']['body_file'] = $data_path.$matched_request['response']['body_file'];
		}
		return $matched_request;
	}
	
	/**
	 * Allocates a match score for the request method
	 * 
	 * @param string $request_method The method from the request
	 * @param array $scores The current array of scores for this criteria
	 * @param string $criteria_method The method from the index entry
	 * @return boolean 
	 */
	protected function _score_match_method($request_method, & $scores, $criteria_method)
	{
		if ($request_method == $criteria_method)
		{
			$scores['method'] = 3;
		}
		elseif (($request_method == 'HEAD') AND ($criteria_method == 'GET'))
		{
			$scores['method'] = 2;
		}
		elseif ($criteria_method == '*')
		{
			$scores['method'] = 1;
		}
		else
		{
			return FALSE;			
		}
		
		return TRUE;
	}
	
	/**
	 * Allocates a match score for the request header/query array, including
	 * allowing for Mimic_Request_Wildcard_Require values.
	 * 
	 * @param array $request_array	The header/query array from the request
	 * @param array $scores	The current array of scores for this criteria
	 * @param array $criteria_array The header/query array from the index entry
	 * @param string $type query|header
	 * @return boolean 
	 */
	protected function _score_match_array($request_array, & $scores, $criteria_array, $type)
	{
		$score = 0;
		
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
				$score += 2;
			}
			elseif ($criteria_value instanceof Mimic_Request_Wildcard_Require)
			{
				// It matches less well
				$score += 1;
			}
			
			// Remove from the request array
			unset($request_array[$criteria_key]);
		}
		
		// Check if the request array has extra keys
		if (count($request_array))
		{
			return FALSE;
		}
		
		// Set the score
		$scores[$type]	= $score;	
		return TRUE;
	}
	
}