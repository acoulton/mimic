<?php
defined('SYSPATH') or die('No direct script access.');

/**
 * Base class for test cases that use Mimic to test interaction with external web
 * services, handling set up and tear down of the Mimic request client and providing
 * suitable assertions as required.
 *
 * @package    Mimic
 * @category   TestInterface
 * @author     Andrew Coulton
 * @copyright  (c) 2011 Ingenerator
 * @license    http://kohanaframework.org/license
 */
abstract class Mimic_Unittest_Testcase extends Unittest_TestCase
{
	/**
	 * The current Mimic instance
	 * @var Mimic
	 */
	public $mimic = NULL;

	/**
	 * If set, will load a default scenario in setUp
	 * @var string
	 */
	protected $_mimic_default_scenario = NULL;

	/**
	 * Stores a reference to the Mimic instance, and resets requests for the
	 * next test execution
	 */
	public function setUp() {
		parent::setUp();
		$this->mimic = Mimic::instance();
		$this->mimic->reset_requests();
		if ($this->_mimic_default_scenario)
		{
			$this->mimic->load_scenario($this->_mimic_default_scenario);
		}
	}

	/**
	 * Asserts that an expected number of requests were made
	 * @param integer $expected
	 */
	public function assertMimicRequestCount($expected)
	{
		$this->assertEquals($expected, $this->mimic->request_count());
	}

	/**
	 * Asserts that the URL of the most recent request is equal to an expected value
	 * @param string $expected
	 */
	public function assertMimicLastRequestURL($expected)
	{
		$this->assertEquals($expected, $this->mimic->last_request()->uri());
	}

	/**
	 * Asserts that the HTTP request method of the most recent request is equal to
	 * an expected value
	 *
	 * @param string $expected
	 */
	public function assertMimicLastRequestMethod($expected)
	{
		$this->assertEquals($expected, $this->mimic->last_request()->method());
	}

	/**
	 * Asserts that the most recent request included a header with the given value
	 *
	 * @param string $header The header name
	 * @param string $expected The expected value
	 */
	public function assertMimicLastRequestHeader($header, $expected)
	{
		$this->assertEquals($expected, $this->mimic->last_request()->headers($header));
	}

	/**
	 * Asserts that the most recent request included an expected $_GET parameter
	 *
	 * @param string $key
	 * @param string $expected
	 */
	public function assertMimicLastRequestQuery($key, $expected)
	{
		$this->assertEquals($expected, $this->mimic->last_request()->query($key));
	}

	/**
	 * Asserts that the request body of the most recent request was as expected
	 * @param string $expected
	 */
	public function assertMimicLastRequestBody($expected)
	{
		$this->assertEquals($expected, $this->mimic->last_request()->body());
	}

}