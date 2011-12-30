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
}

/**
 * Extension as the base class is abstract
 */
class Mimic_TestCase_Foo extends Mimic_Unittest_Testcase
{
	public $_mimic_default_scenario = NULL;
}
