<?php defined('SYSPATH') OR die('Kohana bootstrap needs to be included before tests run');
/**
 * Unit tests for the core Mimic class which controls Mimic
 *
 * @group mimic
 * @group mimic.core
 *
 * @package    Mimic
 * @category   Tests
 * @author     Andrew Coulton
 * @copyright  (c) 2011 Ingenerator
 * @license    http://kohanaframework.org/license
 */
class Mimic_MimicTest extends Unittest_TestCase {
	
	protected static $_old_config = null;
	
	/**
	 * Override Kohana::$config to return our test configuration settings
	 */
	public static function setUpBeforeClass()
	{
		parent::setUpBeforeClass();
		
		// Override Kohana::$config->load() to return our test configs
		self::$_old_config = Kohana::$config;
		
		$config = PHPUnit_Framework_MockObject_Generator::getMock('Config');
		$config->expects(new PHPUnit_Framework_MockObject_Matcher_AnyInvokedCount)
				->method('load')
				->with('mimic')
				->will(new PHPUnit_Framework_MockObject_Stub_Return(array(
					'base_path' => '/foo/config_setting',
					'enable_recording' => false,
					'enable_updating' => false,
					'active_mime' => 'default_config',
					'external_client' => null,
					'debug_headers' => false
					)));
		Kohana::$config = $config;
	}
	
	/**
	 * Reset Kohana::$config
	 */
	public static function tearDownAfterClass()
	{		
		Kohana::$config = self::$_old_config;		
		parent::tearDownAfterClass();
	}	
		
	/**
	 * By default, Mimic loads its configuration from the mimic config group
	 */
	public function test_constructor_should_get_properties_from_config()
	{		
		$mimic = new Mimic;
		$this->assertAttributeEquals('/foo/config_setting', '_base_path', $mimic);
		$this->assertAttributeEquals('default_config', '_active_mime', $mimic);
	}
	
	/**
	 * @depends test_constructor_should_get_properties_from_config
	 */
	public function test_constructor_params_should_override_config()
	{
		$mimic =new Mimic(array('base_path'=>'/foo/local_setting')); 
		
		$this->assertAttributeEquals('/foo/local_setting', '_base_path', $mimic);			
		$this->assertAttributeEquals('default_config', '_active_mime', $mimic);
	}		
	
	public function provider_instance_should_implement_singleton_with_reset()
	{
		return array(
			array(TRUE),
			array(FALSE)
		);
	}
	
	/**
	 * @dataProvider provider_instance_should_implement_singleton_with_reset
	 * @param boolean $reset 
	 */
	public function test_instance_should_implement_singleton_with_reset($reset)
	{
		$mimic1 = Mimic::instance();		
		$mimic2 = Mimic::instance(array(),$reset);
		
		$this->assertInstanceOf('Mimic', $mimic1);
		$this->assertInstanceOf('Mimic', $mimic2);
		
		$this->assertEquals( ! $reset, $mimic1 === $mimic2, "Both mimic instances are the same");
	}
	
	/**
	 * @depends test_constructor_params_should_override_config
	 * @depends test_instance_should_implement_singleton_with_reset
	 */
	public function test_instance_should_pass_config_array_to_new_instance()
	{
		$mimic = Mimic::instance(array('base_path'=>'/foo/singleton/path'), TRUE);
		$this->assertAttributeEquals('/foo/singleton/path', '_base_path', $mimic);
		$this->assertAttributeEquals('default_config', '_active_mime', $mimic);	
	}
	
	/**
	 * @expectedException Mimic_Exception_AlreadyInitialised
	 */
	public function test_instance_should_fail_when_setting_config_for_existing_instance()
	{
		$mimic = Mimic::instance();
		$mimic = Mimic::instance(array('/foo/singleton/path'));		
	}
		
	
	public function provider_property_methods()
	{
		return array(
			array('base_path'),
			array('enable_recording'),
			array('enable_updating'),
			array('external_client'),
			array('debug_headers')
		);
	}
	
	/**
	 * @dataProvider provider_property_methods
	 * @depends test_constructor_params_should_override_config
	 */
	public function test_property_methods_should_act_as_getters_with_no_params($property_method)
	{
		$mimic = new Mimic(array($property_method=>'foo_value'));		
		$value1 = $mimic->$property_method();
		
		$this->assertEquals('foo_value', $value1);		
		$this->assertEquals($value1, $mimic->$property_method(), "Property value has changed");		
	}
	
	/**
	 * @dataProvider provider_property_methods	 
	 * @depends test_property_methods_should_act_as_getters_with_no_params
	 */
	public function test_property_methods_should_act_as_setters($property_method)
	{
		$mimic = new Mimic();
		$mimic_test = $mimic->$property_method('foo_value');
		
		$this->assertEquals($mimic, $mimic_test, "Property setters should return instance for chaining");
		$this->assertEquals('foo_value', $mimic->$property_method());
	}
	
	public function test_should_store_previous_request_client_globally()
	{
		Request_Client_External::$client = 'Request_Client_Curl';
		$mimic = new Mimic();
		
		$this->assertEquals('Request_Client_Curl', Mimic::previous_external_client());
		return $mimic;
	}	
	
	/**
	 * @depends test_should_store_previous_request_client_globally
	 */
	public function test_should_assign_mimic_request_client()
	{
		$this->assertEquals('Request_Client_Mimic', Request_Client_External::$client);
	}
	
	/**
	 * @depends test_should_store_previous_request_client_globally
	 */
	public function test_should_not_overwrite_previous_request_client_with_mimic()
	{
		Request_Client_External::$client = 'Request_Client_Mimic';
		$mimic = new Mimic();
		$this->assertEquals('Request_Client_Curl', Mimic::previous_external_client());
	}
	
	/**
	 * @depends test_should_store_previous_request_client_globally
	 */
	public function test_instance_should_use_global_previous_client_by_default(Mimic $mimic)
	{
		$this->assertEquals('Request_Client_Curl', $mimic->external_client());		
	}
	
	/**
	 * @depends test_constructor_params_should_override_config
	 */
	public function test_instance_should_allow_configuration_of_external_client()
	{
		$mimic = new Mimic(array('external_client'=>'Request_Client_Foo'));
		$this->assertEquals('Request_Client_Foo', $mimic->external_client());
	}
	
	/**
	 * @depends test_constructor_should_get_properties_from_config
	 */
	public function test_should_get_and_set_active_mime()
	{
		$mimic = new Mimic();
		$this->assertEquals('default_config', $mimic->get_active_mime());
		
		$mimic->load_mime('another_mime');
		
		$this->assertEquals('another_mime', $mimic->get_active_mime());
	}
	
	/**
	 * @depends test_constructor_should_get_properties_from_config	 
	 */
	public function test_should_return_mime_path()
	{
		$mimic = new Mimic();
		
		$this->assertEquals('/foo/config_setting'.DIRECTORY_SEPARATOR.'default_config'.DIRECTORY_SEPARATOR, $mimic->get_mime_path());
	}
	
	public function test_should_count_requests_made()
	{
		$mimic = new Mimic;
		$this->assertEquals(0, $mimic->request_count());
		
		for ($i = 0; $i < 3; $i++)
		{
			$request = Request::factory("http://foo.bar.com/$i");
			$mimic->log_request($request);
		}
		
		$this->assertEquals(3, $mimic->request_count());
		return $mimic;
	}
	
	public function provider_should_return_single_request_by_index()
	{
		return array(
			array(0),
			array(1),
			array(2)
		);
	}
	
	/**
	 * @depends test_should_count_requests_made
	 * @dataProvider provider_should_return_single_request_by_index
	 * @param int $index
	 * @param Mimic $mimic 
	 */
	public function test_should_return_single_request_by_index($index, $mimic)
	{
		$request = $mimic->request_history($index);		
		
		$this->assertInstanceOf('Request', $request);
		$this->assertEquals("http://foo.bar.com/$index", $request->uri());
	}
	
	/**
	 * @depends test_should_count_requests_made
	 * @expectedException RangeException
	 * @param Mimic $mimic
	 */
	public function test_should_not_return_out_of_range_request($mimic)
	{
		$request = $mimic->request_history(5);
	}
	
	/**
	 * @depends test_should_count_requests_made
	 * @param Mimic $mimic 
	 */
	public function test_should_return_array_of_requests($mimic)
	{
		$requests = $mimic->request_history();
		$this->assertInternalType('array', $requests);
		$this->assertEquals(3, count($requests));
	}
	
	/**
	 * @depends test_should_count_requests_made
	 * @param type $mimic 
	 */
	public function test_should_return_last_request($mimic)
	{
		$request = $mimic->last_request();
		
		$this->assertInstanceOf('Request', $request);
		$this->assertEquals("http://foo.bar.com/2", $request->uri());
	}		
	
	/**
	 * @depends test_should_count_requests_made
	 * @param Mimic $mimic
	 */
	public function test_reset_should_clear_request_count($mimic)
	{
		$mimic->reset_requests();
		
		$this->assertEquals(0, $mimic->request_count());
		return $mimic;
	}
	
	/**
	 * @depends test_reset_should_clear_request_count
	 * @param Mimic $mimic 
	 * @expectedException RangeException
	 */
	public function test_should_not_return_last_request_with_no_requests($mimic)
	{
		$request = $mimic->last_request();
	}
	
	/**
	 * @depends test_reset_should_clear_request_count
	 * @param Mimic $mimic 
	 */
	public function test_reset_should_clear_request_array($mimic)
	{
		$this->assertEmpty($mimic->request_history());
	}		
	
}

class Mimic_Test extends Mimic
{
	public static $_instance = null;
}