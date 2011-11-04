<?php defined('SYSPATH') OR die('Kohana bootstrap needs to be included before tests run');

/**
 * These tests require the vfsStream mock filesystem driver 
 * from https://github.com/mikey179/vfsStream/
 */
require_once 'vfsStream/vfsStream.php';

/**
 * Unit tests for the recording functionality of the Mimic_Request_Store class
 *
 * @group mimic
 * @group mimic.store
 * @group mimic.store.recording
 *
 * @package    Mimic
 * @category   Tests
 * @author     Andrew Coulton
 * @copyright  (c) 2011 Ingenerator
 * @license    http://kohanaframework.org/license
 */
class Mimic_Request_Store_RecordingTest extends Unittest_TestCase {
		
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
	
	/**
	 * Helper method to populate a request/response pair for testing
	 * @param string $url
	 * @param string $method
	 * @param array $query
	 * @param array $headers
	 * @param string $response_status
	 * @param array $response_headers
	 * @param string $response_body
	 * @return Request 
	 */
	protected function _get_request($url = 'http://ingenerator.com/data',
			$method = 'GET', $query = array(), $headers = array(), $response_status = 200, 
			$response_headers = array(), $response_body = null)
	{
		// Create request and set method, query and headers
		$request = Request::factory($url)
					->method($method);		
		foreach ($query as $key=>$value)
		{
			$request->query($key, $value);			
		}		
		foreach ($headers as $key=>$value)
		{
			$request->headers($key, $value);
		}
		
		// Create response
		$response = $request->create_response();
		$response->status($response_status);
		$response->body($response_body);	
		foreach ($response_headers as $key=>$value)
		{
			$response->headers($key,$value);
		}
		
		return $request;				
	}
	
	/**
	 * Helper method to get the data from a given index file - uses require to
	 * trigger an error if the file doesn't exist
	 * 
	 * @param string $index_file
	 * @return array 
	 */
	protected function _get_recorded_index($index_file = 'http/ingenerator/com/data/request_index.php')
	{
		$index = require(vfsStream::url('mimes/'.$index_file));		
		return $index;
	}
	
	public function provider_should_store_request_in_expected_file()
	{
		return array(
			array($this->_get_request('http://www.ingenerator.com/the/page/here.php'),
				'http/www/ingenerator/com/the/page/here.php/request_index.php'),
			array($this->_get_request('https://www.ingenerator.com/the/page/here.aspx'),
				'https/www/ingenerator/com/the/page/here.aspx/request_index.php'),
			array($this->_get_request('http://www.ingenerator.com/page', 'GET', 
					array('foo'=>'bar')),
				'http/www/ingenerator/com/page/request_index.php')
		);
	}

	/**
	 * @expectedException Mimic_Exception_NotExecuted
	 */
	public function test_should_not_store_request_that_is_not_executed()
	{
		$store = new Mimic_Request_Store($this->_mimic);
		$store->record(Request::factory('http://www.ingenerator.com/data'));
	}

	
	/**
	 * @dataProvider provider_should_store_request_in_expected_file
	 * @param Request $request
	 * @param string $filename 
	 */
	public function test_should_store_request_in_expected_file($request, $filename)
	{
		$store = new Mimic_Request_Store($this->_mimic);
		$store->record($request);
		
		$this->assertTrue($this->_file_system->hasChild($filename));
	}		
	
	/**
	 * @depends test_should_store_request_in_expected_file
	 */	
	public function test_should_store_index_as_exported_php_array()
	{
		$store = new Mimic_Request_Store($this->_mimic);
		$request = $this->_get_request();
		$store->record($request);
		
		$index = $this->_get_recorded_index();
		$this->assertInternalType('array', $index);		
	}
	
	/**
	 * @depends test_should_store_request_in_expected_file
	 * @depends test_should_store_index_as_exported_php_array
	 */ 
	public function test_should_store_request_method()
	{
		$store = new Mimic_Request_Store($this->_mimic);
		$request = $this->_get_request('http://ingenerator.com/data', 'POST');
		$store->record($request);
		
		$index = $this->_get_recorded_index();
		$this->assertEquals($index[0]['method'], 'POST');		
	}

	/**
	 * @depends test_should_store_request_in_expected_file
	 * @depends test_should_store_index_as_exported_php_array
	 */ 
	public function test_should_store_request_headers()
	{
		// NB - HTTP headers are not case sensitive, and Kohana lowercases them
		$headers = array(
			'authorization'=>'Token foo',
			'accept'=>'*/*',
			'x-request-with'=>'ajax');
		
		$store = new Mimic_Request_Store($this->_mimic);
		$request = $this->_get_request('http://ingenerator.com/data', 'GET', 
				array(), $headers);
		$store->record($request);
		
		$index = $this->_get_recorded_index();
		$this->assertEquals($index[0]['headers'], $headers);
	}
	
	/**
	 * @depends test_should_store_request_in_expected_file
	 * @depends test_should_store_index_as_exported_php_array
	 */ 
	public function test_should_store_request_params()
	{
		$query = array(
			'page'=>'1',
			'foo'=>'bar',
			'filter'=>'foo');
		
		$store = new Mimic_Request_Store($this->_mimic);
		$request = $this->_get_request('http://ingenerator.com/data', 'GET', $query);
		$store->record($request);
		
		$index = $this->_get_recorded_index();
		$this->assertEquals($index[0]['query'], $query);
	}

	/**
	 * @depends test_should_store_request_in_expected_file
	 * @depends test_should_store_index_as_exported_php_array
	 */ 
	public function test_should_store_response_body_with_appropriate_formatter()
	{		
		$this->markTestIncomplete('Formatter implementation is pending');
	}

	/**
	 * @depends test_should_store_request_in_expected_file
	 * @depends test_should_store_index_as_exported_php_array
	 */ 
	public function test_should_store_correct_response_filename()
	{
		$this->markTestIncomplete('Formatter implementation is pending');
	}

	/**
	 * @depends test_should_store_request_in_expected_file
	 * @depends test_should_store_index_as_exported_php_array
	 */ 
	public function test_should_append_to_index_file_if_no_matches()
	{
		$this->markTestIncomplete('Append is pending');
	}
}