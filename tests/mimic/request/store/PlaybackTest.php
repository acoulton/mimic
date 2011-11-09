<?php defined('SYSPATH') OR die('Kohana bootstrap needs to be included before tests run');

/**
 * These tests require the vfsStream mock filesystem driver 
 * from https://github.com/mikey179/vfsStream/
 */
require_once 'vfsStream/vfsStream.php';

/**
 * Unit tests for the playback functionality of the Mimic_Request_Store class
 *
 * @group mimic
 * @group mimic.store
 * @group mimic.store.playback
 *
 * @package    Mimic
 * @category   Tests
 * @author     Andrew Coulton
 * @copyright  (c) 2011 Ingenerator
 * @license    http://kohanaframework.org/license
 */
class Mimic_Request_Store_PlaybackTest extends Unittest_TestCase {

	/**
	 * A mock Mimic object
	 * @var Mimic
	 */
	protected $_mimic = null;
	
	/**
	 * The mock file system
	 * @var vfsStreamDirectory 
	 */
	protected $_file_system = null;
	
	/**
	 * Sets up a mock file system using vfsStream and a mock mimic that will use
	 * this as the base path for request storage
	 */
	public function setUp()
	{
		parent::setUp();
		
		// Setup a mock file system
		$this->_file_system = vfsStream::setup('mimes');
		
		// Setup the mock mimic
		$this->_mimic = $this->getMock('Mimic', array(), 
				array(), '', FALSE);
		$this->_mimic->expects($this->any())
				->method('get_mime_path')
				->will($this->returnValue(vfsStream::url('mimes/')));
	}
	
	
	protected function _create_index_file($requests)
	{
		$request_store_path = vfsStream::url('mimes/http/foo.bar.com/test/');
		
		mkdir($request_store_path, 0700, true);
		
		// Store the bodies as separate files
		foreach ($requests as $index=>$request)
		{
			if (isset($request['response']) AND isset($request['response']['body_file']))
			{
				$file = "request$index.body";
				file_put_contents($request_store_path.$file, $request['response']['body_file']);
				$requests[$index]['response']['body_file'] = $file;
			}
		}
	
		// Store the index
		file_put_contents($request_store_path.'request_index.php',
				'<?php'.PHP_EOL
				.'return '.var_export($requests,true).';');		
	}
	
	protected function _request($method = 'GET', $query = array('filter'=>'bar'), $headers = array('X-Test'=>'foo'))
	{
		$request = Request::factory('http://foo.bar.com/test')
					->method($method)
					->query($query);
		foreach ($headers as $key=>$value)
		{
			$request->headers($key,$value);
		}
		
		return $request;
	}
	
	protected function _generic_request_data()
	{
		return array(
			array(
				'method' => 'GET',
				'headers' => array('x-test' => 'foo'),
				'query' => array('filter' => 'bar'),
				'response' => array(
					'status' => '201',
					'headers' => array(
						'x-response' => 'present',
						'cookie' => 'yummy',
					),
					'body_file' => 'The response has a body with content data',
			)));
	}
	
	public function test_should_match_exact_request()
	{
		// Set up the index
		$this->_create_index_file($this->_generic_request_data());		
		$request = $this->_request();
		
		$store = new Mimic_Request_Store($this->_mimic);
		$response = $store->load($request);
		
		$this->assertInstanceOf('Response', $response);
		
		return array($request, $response);
	}
	
	/**
	 * @depends test_should_match_exact_request
	 */
	public function test_matched_response_should_be_bound_to_request($request_response)
	{
		list($request, $response) = $request_response;
		/* @var $request Request */
		/* @var $response Response */
		
		$this->assertSame($response, $request->response());
	}

	/**
	 * @depends test_should_match_exact_request
	 */
	public function test_matched_response_should_have_correct_status($request_response)
	{
		list($request, $response) = $request_response;
		/* @var $request Request */
		/* @var $response Response */
		
		$this->assertEquals('201', $response->status());
	}

	/**
	 * @depends test_should_match_exact_request
	 */
	public function test_matched_response_should_have_correct_headers($request_response)
	{
		list($request, $response) = $request_response;
		/* @var $request Request */
		/* @var $response Response */
		
		$this->assertEquals('present', $response->headers('X-Response'));
		$this->assertEquals('yummy', $response->headers('Cookie'));		
	}
	
	/**
	 * @depends test_should_match_exact_request
	 */
	public function test_matched_response_should_have_correct_body($request_response)
	{
		list($request, $response) = $request_response;
		/* @var $request Request */
		/* @var $response Response */
		
		$this->assertEquals('The response has a body with content data', 
				$response->body());
	}
	
	/**
	 * @depends test_should_match_exact_request
	 */
	public function test_should_support_wildcard_for_request_method()
	{
		// Setup the test data
		$requests = $this->_generic_request_data();
		$requests[0]['method'] = '*';
		$this->_create_index_file($requests);
		
		// Test matching
		$store = new Mimic_Request_Store($this->_mimic);
		
		$request = $this->_request();				
		$this->assertInstanceOf('Response', $store->load($request), "Matches GET");
		
		$request = $this->_request('HEAD');
		$this->assertInstanceOf('Response', $store->load($request), "Matches HEAD");		
	}
	
	/**
	 * @depends test_should_match_exact_request
	 */
	public function test_should_match_HEAD_as_GET_if_not_found()
	{
		// Setup the test data
		$this->_create_index_file($this->_generic_request_data());
		
		// Test matching
		$store = new Mimic_Request_Store($this->_mimic);
		
		$request = $this->_request('HEAD');
		$this->assertInstanceOf('Response', $store->load($request));		
	}
		
	/**
	 * @depends test_should_match_exact_request
	 */
	public function test_should_match_ignoring_wildcard_header_keys()
	{
		// Setup the test data
		$requests = $this->_generic_request_data();
		$requests[0]['headers']['x-test'] = new Mimic_Request_Wildcard_Require;
		$this->_create_index_file($requests);
		
		// Test matching
		$store = new Mimic_Request_Store($this->_mimic);
		
		// Should match when a value is passed
		$request = $this->_request();				
		$this->assertInstanceOf('Response', $store->load($request));
		
		// Should not match when the header is missing
		$request = $this->_request('GET',array('filter'=>'bar'),array());
		$this->assertNull($store->load($request));				
	}
		
	/**
	 * @depends test_should_match_exact_request
	 */
	public function test_should_match_ignoring_wildcard_query_params()
	{
		// Setup the test data
		$requests = $this->_generic_request_data();
		$requests[0]['query']['filter'] = new Mimic_Request_Wildcard_Require;
		$this->_create_index_file($requests);
		
		// Test matching
		$store = new Mimic_Request_Store($this->_mimic);
		
		// Should match when a value is passed
		$request = $this->_request();				
		$this->assertInstanceOf('Response', $store->load($request));
		
		// Should not match when the query param is missing
		$request = $this->_request('GET',array());
		$this->assertNull($store->load($request));		
	}
	
	protected function _create_multiple_index()
	{
		$this->_create_index_file(array(
			array(
				// Should match GET or HEAD request with no params/headers
				'method' => 'GET',
				'headers' => array(),
				'query' => array(),
				'response' => array(
					'status' => '201',
					'headers' => array('x-mimic-id'=>1),
					'body_file' => null)),
			array(
				// Should match GET with a filter query
				'method' => 'GET',
				'headers' => array(),
				'query' => array('filter'=>'bar'),
				'response' => array(
					'status' => '201',
					'headers' => array('x-mimic-id'=>2),
					'body_file' => null)),			
			array(
				// Should match GET with an ajax header
				'method' => 'GET',
				'headers' => array('x-requested-with'=>'ajax'),
				'query' => array(),
				'response' => array(
					'status' => '201',
					'headers' => array('x-mimic-id'=>3),
					'body_file' => null)),
			array(
				// Should match GET with an ajax header and filter query
				'method' => 'GET',
				'headers' => array('x-requested-with'=>'ajax'),
				'query' => array('filter'=>'bar'),
				'response' => array(
					'status' => '201',
					'headers' => array('x-mimic-id'=>4),
					'body_file' => null)),
			array(
				// Should match GET with a page query
				'method' => 'GET',
				'headers' => array(),
				'query' => array('page'=>'1'),
				'response' => array(
					'status' => '201',
					'headers' => array('x-mimic-id'=>5),
					'body_file' => null)),
			array(
				// Should match anything with a page query
				'method' => '*',
				'headers' => array(),
				'query' => array('page'=>'1'),
				'response' => array(
					'status' => '201',
					'headers' => array('x-mimic-id'=>6),
					'body_file' => null)),
			array(
				// Should match GET with a page query and specific authorization header
				'method' => 'GET',
				'headers' => array('authorization'=>'token secured'),
				'query' => array('page'=>'1'),
				'response' => array(
					'status' => '201',
					'headers' => array('x-mimic-id'=>7),
					'body_file' => null)),
			array(
				// Should match GET with a page query and any authorization header
				'method' => 'GET',
				'headers' => array('authorization'=>new Mimic_Request_Wildcard_Require),
				'query' => array('page'=>'1'),
				'response' => array(
					'status' => '201',
					'headers' => array('x-mimic-id'=>8),
					'body_file' => null)),
		));
	}
	
	public function provider_should_match_first_entry_in_index_by_method()
	{
		return array(
			// Method, Query, Headers, Expect_Response_ID
			array('GET', array(), array(), 1),			
			array('HEAD', array(), array(), 1),
			array('GET', array('filter'=>'bar'), array(), 2),
			array('POST', array('filter'=>'bar'), array(), null),
			array('GET', array(), array('X-Requested-With'=>'ajax'), 3),
			array('GET', array('filter'=>'bar'), array('X-Requested-With'=>'ajax'), 4),
			array('PUT', array('page'=>'1'), array(), 6),
			array('POST', array('page'=>'1'), array(), 6),			
			array('GET', array('page'=>'1'), array(), 5),			
			array('GET', array('page'=>'1'), array('Authorization'=>'token foo'), 8),
			array('GET', array('page'=>'1'), array('Authorization'=>'token secured'), 7),
		);
	}
	
	/**
	 * @depends test_should_match_ignoring_wildcard_query_params
	 * @depends test_should_match_ignoring_wildcard_header_keys
	 * @depends test_should_support_wildcard_for_request_method
	 * @dataProvider provider_should_match_first_entry_in_index_by_method
	 * 
	 * @param string $method  The request method to use
	 * @param array  $query   Query parameters
	 * @param array  $headers Header keys
	 * @param int    $expect_response_id The ID of the response to expect
	 */
	public function test_should_match_first_entry_in_index_by_method($method, $query, $headers, $expect_response_id)
	{
		$this->_create_multiple_index();
		$store = new Mimic_Request_Store($this->_mimic);
		
		$request = $this->_request($method, $query, $headers);		
		$response = $store->load($request);
		
		if ($expect_response_id === null)
		{
			$this->assertNull($response);
		}
		else
		{
			$this->assertEquals($expect_response_id, $response->headers('X-Mimic-ID'));
		}		
	}
	
		
	public function provider_should_add_debug_headers_if_requested()
	{
		return array(
			array(TRUE),
			array(FALSE)
		);
	}
	
	/**
	 * @dataProvider provider_should_add_debug_headers_if_requested
	 * @param boolean $debug_headers 
	 */
	public function test_should_add_debug_headers_if_requested($debug_headers)
	{
		// Setup the test data and mocks
		$this->_create_multiple_index();
		$this->_mimic->expects($this->once())
				->method('debug_headers')
				->with(NULL)
				->will($this->returnValue($debug_headers));
		
		// Setup and match a request
		$request = $this->_request('GET', array('filter'=>'bar'), array('X-Requested-With'=>'ajax'));
		$store = new Mimic_Request_Store($this->_mimic);
		$response = $store->load($request);
		$headers = (array) $response->headers();
		
		// Verify that debug headers are (not) present
		if ($debug_headers)
		{
			$this->assertEquals($headers['x-mimic-indexfile'], vfsStream::url('mimes/http/foo.bar.com/test/request_index.php'));
			$this->assertEquals($headers['x-mimic-definitioncount'], 8);
			$this->assertEquals($headers['x-mimic-matchedindex'], 3);
		}
		else
		{
			$this->assertArrayNotHasKey('x-mimic-indexfile', $headers);
			$this->assertArrayNotHasKey('x-mimic-definitioncount', $headers);
			$this->assertArrayNotHasKey('x-mimic-matchedindex', $headers);
		}
	}

	
}