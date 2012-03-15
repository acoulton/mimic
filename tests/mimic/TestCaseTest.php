<?php defined('SYSPATH') or die('Kohana bootstrap needs to be included before tests run');

/**
 * Tests for the Mimic_Unittest_Testcase base testing class
 *
 * @group mimic
 * @group mimic.testcase
 *
 * @package    Mimic
 * @category   Tests
 * @author     Andrew Coulton
 * @copyright  (c) 2011 Ingenerator
 * @license    http://kohanaframework.org/license
 */
class Mimic_TestCaseTest extends Unittest_Testcase {

	public function test_setup_should_assign_mimic_instance_to_class()
	{
		$testcase = new Mimic_TestCase_Foo;
		$mimic = Mimic::instance(array(), TRUE);
		$testcase->setUp();

		$this->assertSame($mimic, $testcase->mimic);
		return $testcase;
	}

	/**
	 * @depends test_setup_should_assign_mimic_instance_to_class
	 */
	public function test_setup_should_reset_requests(Mimic_TestCase_Foo $testcase)
	{
		// Pretend a request happened
		$request = Request::factory('http://foo.bar.com/ok');
		$testcase->mimic->log_request($request);
		$this->assertEquals(1, $testcase->mimic->request_count(), "Verify expectation pre-test");

		// Setup
		$testcase->setUp();

		$this->assertEquals(0, $testcase->mimic->request_count());
	}

	public function provider_setup_should_set_scenario_if_set_in_class()
	{
		return array(
			array(NULL, 'default'),
			array('foo', 'foo')
		);
	}

	/**
	 * @dataProvider provider_setup_should_set_scenario_if_set_in_class
         */
	public function test_setup_should_set_scenario_if_set_in_class($property, $expected)
	{
		$testcase = new Mimic_TestCase_Foo;
		$testcase->_mimic_default_scenario = $property;
		$mimic = Mimic::instance(array(), TRUE);
		$mimic->load_scenario('default');

		$testcase->setUp();

		$this->assertEquals($expected, $mimic->get_active_scenario());
	}

	/**
	 * Helper method to test that an assertion produces expected pass/fail result
	 *
	 * @param Mimic_Testcase $testcase The testcase instance
	 * @param string $method The method to call
	 * @param array $args Arguments to pass to the method
	 * @param boolean $should_pass Whether or not the assertion should pass
	 * @return void
	 */
	protected function _test_assertion($testcase, $method, $args, $should_pass)
	{
		try
		{
			// Call the assertion method
			call_user_func_array(array($testcase, $method), $args);
		}
		catch (PHPUnit_Framework_ExpectationFailedException $e)
		{
			// Check that this is expected to fail
			if ($should_pass)
			{
				$this->fail('Unexpected assertion failure with message '.$e->getMessage());
			}
			return;
		}
		catch (PHPUnit_Framework_AssertionFailedError $e)
		{
			// Check that this is expected to fail
			if ($should_pass)
			{
				$this->fail('Unexpected assertion failure with message '.$e->getMessage());
			}
			return;
		}

		// Check that it is expected to pass
		if ( ! $should_pass)
		{
			$this->fail("Unexpected assertion pass for method $method");
		}
	}

	/**
	 * Gets a testcase with a mock mimic attached ready for use in testing
	 * @param string $method
	 * @param mixed $return
	 * @return Mimic_TestCase_Foo
	 */
	protected function _testcase_with_mock_mimic($method, $return)
	{
		$testcase = new Mimic_TestCase_Foo;

		// Mock a Mimic to return the matching value
		$testcase->mimic = $this->getMock('Mimic');
		$testcase->mimic->expects($this->once())
					->method($method)
					->will($this->returnValue($return));

		return $testcase;
	}

	/**
	 * Gets a mock request with one method mocked to return a value for testing
	 * @param string $method
	 * @param mixed $return
	 * @return Request
	 */
	protected function _mock_request($method = NULL, $return = NULL)
	{
		// Mock a request
		$request = $this->getMock('Request', array(),array(), '', FALSE);

		// Mock a method if required
		if ($method !== NULL)
		{
			$request->expects($this->once())
				->method($method)
				->will($this->returnValue($return));
		}

		return $request;
	}

	public function provider_should_assert_mimic_request_count()
	{
		return array(
			array(1, 1, TRUE),
			array(1, 0, FALSE)
		);
	}

	/**
	 * @dataProvider provider_should_assert_mimic_request_count
	 * @param integer $mock_count
	 * @param integer $expected
	 * @param boolean $should_pass
	 */
	public function test_should_assert_mimic_request_count($mock_count, $expected, $should_pass)
	{
		$testcase = $this->_testcase_with_mock_mimic('request_count', $mock_count);

		$this->_test_assertion($testcase, 'assertMimicRequestCount', array($expected), $should_pass);
	}

	public function provider_should_assert_last_request_url()
	{
		return array(
			array('http://www.foo.bar/abc', 'http://www.foo.bar/abc', TRUE),
			array('http://www.foo.bar/abc', 'http://www.foo.bar/foo', FALSE)
		);
	}

	/**
	 * @dataProvider provider_should_assert_last_request_url
	 * @param string $mock_url
	 * @param integer $expected
	 * @param boolean $should_pass
	 */
	public function test_should_assert_last_request_url($mock_url, $expected, $should_pass)
	{
		$testcase = $this->_testcase_with_mock_mimic('last_request',
				$this->_mock_request('uri', $mock_url));

		$this->_test_assertion($testcase, 'assertMimicLastRequestURL', array($expected), $should_pass);
	}

	public function provider_should_assert_last_request_method()
	{
		return array(
			array('GET', 'GET', TRUE),
			array('GET', 'POST', FALSE)
		);
	}

	/**
	 * @dataProvider provider_should_assert_last_request_method
	 * @param string $mock_method
	 * @param integer $expected
	 * @param boolean $should_pass
	 */
	public function test_should_assert_last_request_method($mock_method, $expected, $should_pass)
	{
		$testcase = $this->_testcase_with_mock_mimic('last_request',
				$this->_mock_request('method', $mock_method));

		$this->_test_assertion($testcase, 'assertMimicLastRequestMethod', array($expected), $should_pass);
	}

	public function provider_should_assert_last_request_header()
	{
		return array(
			array('X-foo', 'bar', 'bar', TRUE),
			array('X-foo', 'bar', 'biddy', FALSE)
		);
	}

	/**
	 * @dataProvider provider_should_assert_last_request_header
	 * @param string $header
	 * @param string $mock_value
	 * @param integer $expected
	 * @param boolean $should_pass
	 */
	public function test_should_assert_last_request_header($header, $mock_value, $expected, $should_pass)
	{
		$request = $this->_mock_request();
		$request->expects($this->once())
				->method('headers')
				->with($header)
				->will($this->returnValue($mock_value));

		$testcase = $this->_testcase_with_mock_mimic('last_request',
				$request);

		$this->_test_assertion($testcase, 'assertMimicLastRequestHeader', array($header, $expected), $should_pass);
	}

	public function provider_should_assert_last_request_query()
	{
		return array(
			array('foo', 'bar', 'bar', TRUE),
			array('foo', 'bar', 'biddy', FALSE)
		);
	}

	/**
	 * @dataProvider provider_should_assert_last_request_query
	 * @param string $key
	 * @param string $mock_value
	 * @param integer $expected
	 * @param boolean $should_pass
	 */
	public function test_should_assert_last_request_query($key, $mock_value, $expected, $should_pass)
	{
		$request = $this->_mock_request();
		$request->expects($this->once())
				->method('query')
				->with($key)
				->will($this->returnValue($mock_value));

		$testcase = $this->_testcase_with_mock_mimic('last_request',
				$request);

		$this->_test_assertion($testcase, 'assertMimicLastRequestQuery', array($key, $expected), $should_pass);
	}

	public function provider_should_assert_last_request_body()
	{
		return array(
			array('body-foo', 'body-foo', TRUE),
			array('body-foo', 'body-bar', FALSE)
		);
	}

	/**
	 * @dataProvider provider_should_assert_last_request_body
	 * @param string $mock_body
	 * @param integer $expected
	 * @param boolean $should_pass
	 */
	public function test_should_assert_last_request_body($mock_body, $expected, $should_pass)
	{
		$testcase = $this->_testcase_with_mock_mimic('last_request',
				$this->_mock_request('body', $mock_body));

		$this->_test_assertion($testcase, 'assertMimicLastRequestBody', array($expected), $should_pass);
	}

	public function provider_should_assert_or_not_requests_contains()
	{
		$requests = array(
			array('method'=>'GET', 'uri' => 'http://foo.bar.com/foo'),
			array('method'=>'POST', 'uri' => 'http://foo.bar.com/foo'),
			array('method'=>'GET', 'uri' => 'http://foo.bar.com/bar'),
		);
		return array(
			array($requests, 'http://foo.bar.com/foo', 'POST', TRUE),
			array($requests, 'http://foo.bar.com/foo', NULL, TRUE),
			array($requests, 'http://foo.bar.com/foo', 'PUT', FALSE),
			array($requests, 'http://foo.bar.com/bar', 'GET', TRUE),
		);
	}

	/**
	 * Helper method, as the test strap for assertMimicRequestsContains and
	 * assertMimicRequestsNotContains is very similar.
	 *
	 * @param string $method
	 * @param array $mock_requests
	 * @param string $test_url
	 * @param string $test_method
	 * @param string $should_pass
	 */
	protected function _test_should_or_not_contain($method, $mock_requests, $test_url, $test_method, $should_pass)
	{
		// Build an array of mock requests for the stack
		$request_history = array();
		foreach ($mock_requests as $mock_request)
		{
			$request = $this->_mock_request();

			// Request::uri() will only be called if the method matches the search
			$request->expects($this->any())
					->method('uri')
					->will($this->returnValue($mock_request['uri']));

			// Request::method() will only be called if matching on it
			$request->expects($this->any())
					->method('method')
					->will($this->returnValue($mock_request['method']));
			$request_history[] = $request;
		}

		// Mock the testcase
		$testcase = $this->_testcase_with_mock_mimic('request_history', $request_history);

		// Call and test the assertion
		$this->_test_assertion($testcase, $method, array($test_url, $test_method), $should_pass);
	}

	/**
	 * @dataProvider provider_should_assert_or_not_requests_contains
	 * @param array $mock_requests
	 * @param string $test_url
	 * @param string $test_method
	 * @param boolean $should_contain Whether the stack should contain the request
	 */
	public function test_should_assert_requests_contains($mock_requests, $test_url, $test_method, $should_contain)
	{
		$this->_test_should_or_not_contain('assertMimicRequestsContains',
				$mock_requests, $test_url, $test_method, $should_contain);
	}

	/**
	 * @dataProvider provider_should_assert_or_not_requests_contains
	 * @param array $mock_requests
	 * @param string $test_url
	 * @param string $test_method
	 * @param boolean $should_contain Whether the stack should contain the request
	 */
	public function test_should_assert_requests_not_contains($mock_requests, $test_url, $test_method, $should_contain)
	{
		$should_pass = ! $should_contain;
		$this->_test_should_or_not_contain('assertMimicRequestsNotContains',
				$mock_requests, $test_url, $test_method, $should_pass);
	}


}

/**
 * Extension as the base class is abstract
 */
class Mimic_TestCase_Foo extends Mimic_Unittest_Testcase
{
	public $_mimic_default_scenario = NULL;
}
