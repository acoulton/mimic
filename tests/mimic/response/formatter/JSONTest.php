<?php defined('SYSPATH') OR die('Kohana bootstrap needs to be included before tests run');

/**
 * Unit tests for the JSON Response Formatter
 *
 * @group mimic
 * @group mimic.formatter
 * @group mimic.formatter.json
 *
 * @package    Mimic
 * @category   Tests
 * @author     Andrew Coulton
 * @copyright  (c) 2011 Ingenerator
 * @license    http://kohanaframework.org/license
 */
class Mimic_Response_Formatter_JSONTest extends Mimic_Response_FormatterBaseTest {
	
	protected $_formatter_class_name = 'Mimic_Response_Formatter_JSON';
	protected $_expect_extension = '.json';
	
	public function provider_should_prettify_JSON_data()
	{	
		return array(
			array(
				'{"string":"bar","int":4,"escaped":"{\"test\":\"data\"}","object":{"string":"bar"},"null":null,"bool":true}',
				array(
					'string'=>'bar',
					'int' => 4,
					'escaped' => '{"test":"data"}',
					'object' => array('string'=>'bar'),
					'null' => NULL,
					'bool' => TRUE,
					
					)
			),
			array(
				'{"newline":"line\r\n\there"}',
				array(
					'newline' => "line\r\n\there",
				),
			),
			array(
				'{"escaped":"\/\\\\\"\'"}',
				array(
					'escaped' => '/\"\'',	
				)				
			)
			
		);
	}
	
	/**
	 * @dataProvider provider_should_prettify_JSON_data
	 * @param array $input_data 
	 */
	public function test_should_prettify_JSON_data($response_text, $expect_result)
	{		
		
		// Store the data
		$formatter = new Mimic_Response_Formatter_JSON;
		$formatter->put_contents(vfsStream::url('responses/'), 'json_test', $response_text);
		
		// Load the data
		$json_text = file_get_contents(vfsStream::url('responses/json_test.json'));
		$output_data = json_decode($json_text,true);
		
		// Compare the data
		$this->assertEquals($expect_result, $output_data);
		$this->assertNotEquals($response_text, $json_text);
	}
}
