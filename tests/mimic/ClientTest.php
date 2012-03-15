<?php defined('SYSPATH') OR die('Kohana bootstrap needs to be included before tests run');
/**
 * Unit tests for the request client which is at the core of Mimic and handles
 * all loading and saving of web resources
 *
 * @group mimic
 * @group mimic.client
 *
 * @package    Mimic
 * @category   Tests
 * @author     Andrew Coulton
 * @copyright  (c) 2011 Ingenerator
 * @license    http://kohanaframework.org/license
 */
class Mimic_ClientTest extends Unittest_TestCase {

	/**
	 * A mock [Mimic_Request_Store] class
	 * @var Mimic_Request_Store
	 */
	protected $_store = null;
	
	/**
	 * A mock [Mimic] class
	 * @var Mimic 
	 */
	protected $_mimic = null;
	
	/**
	 * Used for testing - mock mimic and store instances are injected during setUp
	 * @var Request_Client_Mimic 
	 */
	protected $_client = null;
		
	/**
	 * This is the actual application behaviour in normal use
	 */
	public function test_should_use_mimic_singleton_by_default()
	{
		$mimic = Mimic::instance();
		$client = new Request_Client_Mimic;
		
		$this->assertSame($mimic, $client->mimic());
	}
	
	/**
	 * This is the actual application behaviour in normal use
	 */
	public function test_should_create_new_store_by_default()
	{
		$client = new Request_Client_Mimic;
		$client2 = new Request_Client_Mimic;
		
		$this->assertInstanceOf('Mimic_Request_Store', $client->store());
		$this->assertSame($client->store(), $client->store());
		$this->assertNotSame($client->store(), $client2->store());		
	}

	/**
	 * We need to be able to inject a [Mimic] for testing
	 */
	public function test_should_allow_injection_of_mimic()
	{
		$mimic = new Mimic;
		$client = new Request_Client_Mimic;
		$client->mimic($mimic);
		
		$this->assertSame($mimic, $client->mimic());
	}		
	

	/**
	 * We need to be able to inject a [Mimic_Request_Store] for testing
	 */
	public function test_should_allow_injection_of_store()
	{
		$client = new Request_Client_Mimic;
		$mimic = new Mimic;
		$store = new Mimic_Request_Store($mimic);
		$client->store($store);
		
		$this->assertSame($store, $client->store());
	}
	
	/**
	 * Setup the mock objects and inject them into a Request_Client_Mimic instance
	 * ready for testing
	 */
	public function setUp()
	{
		parent::setUp();
		$this->_mimic = $this->getMock('Mimic');
		$this->_store = $this->getMock('Mimic_Request_Store',array(),array($this->_mimic));
		
		$this->_client = new Request_Client_Mimic;
		$this->_client->mimic($this->_mimic);
		$this->_client->store($this->_store);
	}
	
	/**
	 * Prepares the Mimic instance to receive mocked method calls
	 * @param PHPUnit_Framework_MockObject_Matcher_Invocation $expectation
	 * @param string $method
	 * @param array $param
	 * @param mixed $return 
	 */
	protected function _mock_mimic_methods($expectation, $method, $param = NULL, $return = FALSE)
	{
		$this->_mimic->expects($expectation)
				->method($method)
				->with($param)
				->will($this->returnValue($return));		
	}
	
	/**
	 * Sets up the Mimic to verify that a particular method is never called
	 * @param string $method 
	 */
	protected function _mock_mimic_never($method)
	{
		$this->_mimic->expects($this->never())
				->method($method);
	}
	
	/**
	 * Prepares the Mimic_Request_Store instance to receive mocked method calls
	 * @param PHPUnit_Framework_MockObject_Matcher_Invocation $expectation
	 * @param string $method
	 * @param array $param
	 * @param mixed $return 
	 */	
	protected function _mock_store_methods($expectation, $method, $param = NULL, $return = FALSE)
	{
		$this->_store->expects($expectation)
				->method($method)
				->with($param)
				->will($this->returnValue($return));		
	}
	
	/**
	 * Sets up the Mimic_Request_Store instance to verify that a particular method is never called
	 * @param string $method 
	 */
	protected function _mock_store_never($method)
	{
		$this->_store->expects($this->never())
				->method($method);
	}

	
	/**
	 * @expectedException Mimic_Exception_RecordingDisabled
	 * @depends test_should_allow_injection_of_store
	 * @depends test_should_allow_injection_of_mimic
	 */
	public function test_should_not_allow_recording_if_recording_disabled()
	{
		// Create a request
		$request = Request::factory('http://foo.bar.com/a/page');
		
		// Mock a Mimic to have recording disabled
		$this->_mock_mimic_methods($this->once(), 'enable_recording', NULL, FALSE);
		$this->_mock_mimic_methods($this->once(), 'log_request', $request);
		$this->_mock_mimic_never('external_client');

		// Mock a store to return empty
		$this->_mock_store_methods($this->once(), 'load', $request, FALSE);
				
		// Try to execute
		$this->_client->_send_message($request);
	}
	
	/**	 
	 * @depends test_should_allow_injection_of_store
	 * @depends test_should_allow_injection_of_mimic
	 */
	public function test_should_record_external_request_if_recording_enabled_and_not_found()
	{
		// Create a request/response
		$request = Request::factory('http://foo.bar.com/a/page');
		$response = $request->create_response();
		
		// Mock a Mimic to have recording enabled
		$this->_mock_mimic_methods($this->once(), 'enable_recording', NULL, TRUE);
		$this->_mock_mimic_methods($this->once(), 'external_client', NULL, 'Request_Client_Nothing');
		$this->_mock_mimic_methods($this->once(), 'log_request', $request);
				
		// Mock a store to return empty
		$this->_mock_store_methods($this->once(), 'load', $request, FALSE);
		$this->_mock_store_methods($this->once(), 'record', $request);
		
		// Execute and check the return values
		$returned_response = $this->_client->_send_message($request);
		$this->assertSame($response, $returned_response, "The same response instance was returned");
	}
	
	/**	 
	 * @depends test_should_allow_injection_of_store
	 * @depends test_should_allow_injection_of_mimic
	 */
	public function test_should_return_matched_response_if_recording_found()
	{
		// Create a request/response
		$request = Request::factory('http://foo.bar.com/a/page');
		$response = $request->create_response();
		
		// Set up Mimic expectations
		$this->_mock_mimic_methods($this->once(), 'enable_updating', NULL, FALSE);
		$this->_mock_mimic_never('enable_recording');
		$this->_mock_mimic_never('external_client');
		$this->_mock_mimic_methods($this->once(), 'log_request', $request);
				
		// Mock a store to return response
		$this->_mock_store_methods($this->once(), 'load', $request, $response);
		$this->_mock_store_never('record');
		
		// Execute and check the return values
		$returned_response = $this->_client->_send_message($request);
		$this->assertSame($response, $returned_response, "The same response instance was returned");
	}
	
	/**	 
	 * @depends test_should_allow_injection_of_store
	 * @depends test_should_allow_injection_of_mimic
	 */
	public function test_should_record_and_return_external_request_if_updating_enabled()
	{
		// Create a request/response
		$request = Request::factory('http://foo.bar.com/a/page');
		$response = $request->create_response();
		
		// Mock a Mimic to have recording enabled
		$this->_mock_mimic_methods($this->once(), 'enable_updating', NULL, TRUE);
		$this->_mock_mimic_never('enable_recording');
		$this->_mock_mimic_methods($this->once(), 'external_client', NULL, 'Request_Client_Nothing');
		$this->_mock_mimic_methods($this->once(), 'log_request', $request);
				
		// Mock a store to return empty
		$this->_mock_store_methods($this->once(), 'load', $request, $response);
		$this->_mock_store_methods($this->once(), 'record', $request);
		
		// Execute and check the return values
		$returned_response = $this->_client->_send_message($request);
		$this->assertSame($response, $returned_response, "The same response instance was returned");
	}

	public function provider_should_assign_request_content_length_if_missing()
	{
		return array(
			array(NULL, '5', '5'),
			array('foo', NULL, '3'),
			array(NULL, NULL, '0'),
			array('foo', '0', '0')
		);
	}
	
	/**
	 * @group ticket.5
	 * @dataProvider provider_should_assign_request_content_length_if_missing
	 */
	public function test_should_assign_request_content_length_if_missing($body, $set_length, $expect_length)
	{
		// Create a request
		$request = Request::factory('http://foo.bar.com/a/page');
		$request->body($body);
		if ($set_length !== NULL)
		{
			$request->headers('content-length', $set_length);
		}
		$response = $request->create_response();

		// Set up Mimic expectations
		$this->_mock_mimic_methods($this->once(), 'enable_updating', NULL, FALSE);
		
		// Mock a store to return response
		$this->_mock_store_methods($this->once(), 'load', $request, $response);

		// Execute and check the content-length header is present
		$this->_client->_send_message($request);
		$this->assertSame($expect_length, $request->headers('content-length'));
			
	}
		
}

class Request_Client_Nothing extends Request_Client_External
{
	
	public function _send_message(Request $request)
	{
		if ( ! $request->response())
		{
			$request->create_response();
		}
		
		return $request->response();
	}
}
