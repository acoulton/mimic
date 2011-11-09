<?php defined('SYSPATH') OR die('Kohana bootstrap needs to be included before tests run');

/**
 * These tests require the vfsStream mock filesystem driver 
 * from https://github.com/mikey179/vfsStream/
 */
require_once 'vfsStream/vfsStream.php';

/**
 * Base unit tests for all Mimic_Response_Formatter classes
 *
 * @package    Mimic
 * @category   Tests
 * @author     Andrew Coulton
 * @copyright  (c) 2011 Ingenerator
 * @license    http://kohanaframework.org/license
 */
abstract class Mimic_Response_FormatterBaseTest extends Unittest_TestCase {
	
	protected $_formatter_class_name = NULL;
	protected $_expect_extension = NULL;
	protected $_file_system = NULL;	
	
	public function setUp()
	{
		parent::setUp();
		$this->_file_system = vfsStream::setup('responses');
	}

	public function test_should_create_file_with_arbitrary_content_and_return_name()
	{		
		$formatter = new $this->_formatter_class_name;
		$path = vfsStream::url('responses/');
		
		$file_name = $formatter->put_contents($path, 'foo_test', 
				'test-foo-content');		
		
		$this->assertTrue($this->_file_system->hasChild($file_name));
		
		return array(
			'file_system' => $this->_file_system,
			'file_name' => $file_name
		);
	}
	
	/**
	 * @depends test_should_create_file_with_arbitrary_content_and_return_name
	 */
	public function test_should_store_arbitrary_response_body($test_data)
	{
		extract($test_data);		
		$this->assertEquals('test-foo-content', 
				$file_system->getChild($file_name)
						->getContent());
	}
	
	/**
	 * @depends test_should_create_file_with_arbitrary_content_and_return_name
	 */
	public function test_should_apply_suitable_file_extension($test_data)
	{
		if ($this->_expect_extension === NULL)
		{
			$this->markTestIncomplete(get_class($this).' should define a value for $_expect_extension');
			return;
		}
		extract($test_data);
		$this->assertStringEndsWith($this->_expect_extension, $file_name);
	}	
}