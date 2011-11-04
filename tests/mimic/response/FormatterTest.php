<?php defined('SYSPATH') OR die('Kohana bootstrap needs to be included before tests run');

/**
 * These tests require the vfsStream mock filesystem driver 
 * from https://github.com/mikey179/vfsStream/
 */
require_once 'vfsStream/vfsStream.php';

/**
 * Unit tests for the generic Response Formatter
 *
 * @group mimic
 * @group mimic.formatter
 * @group mimic.formatter.generic
 *
 * @package    Mimic
 * @category   Tests
 * @author     Andrew Coulton
 * @copyright  (c) 2011 Ingenerator
 * @license    http://kohanaframework.org/license
 */
class Mimic_Response_FormatterTest extends Unittest_TestCase {

	public function test_should_create_file_and_return_name()
	{
		$file_system = vfsStream::setup('responses');	
		$formatter = new Mimic_Response_Formatter_Foo;
		$path = vfsStream::url('responses/');
		
		$file_name = $formatter->put_contents($path, 'foo_test', 
				'test-foo-content');		
		
		$this->assertTrue($file_system->hasChild($file_name));
		
		return array(
			'file_system' => $file_system,
			'file_name' => $file_name
		);
	}
	
	/**
	 * @depends test_should_create_file_and_return_name
	 */
	public function test_should_store_response_body($test_data)
	{
		extract($test_data);		
		$this->assertEquals('test-foo-content', 
				$file_system->getChild($file_name)
						->getContent());
	}
	
	/**
	 * @depends test_should_create_file_and_return_name
	 */
	public function test_should_apply_suitable_file_extension($test_data)
	{
		extract($test_data);
		$this->assertStringEndsWith('.foo', $file_name);
	}	
}

class Mimic_Response_Formatter_Foo extends Mimic_Response_Formatter
{
	protected $_extension = '.foo';
}