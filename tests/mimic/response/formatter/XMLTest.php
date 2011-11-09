<?php defined('SYSPATH') OR die('Kohana bootstrap needs to be included before tests run');

/**
 * Unit tests for the XML Response Formatter
 *
 * @group mimic
 * @group mimic.formatter
 * @group mimic.formatter.xml
 *
 * @package    Mimic
 * @category   Tests
 * @author     Andrew Coulton
 * @copyright  (c) 2011 Ingenerator
 * @license    http://kohanaframework.org/license
 */
class Mimic_Response_Formatter_XMLTest extends Mimic_Response_FormatterBaseTest {
	
	protected $_formatter_class_name = 'Mimic_Response_Formatter_XML';
	protected $_expect_extension = '.xml';
	
	public function provider_should_prettify_XML_data()
	{	
		return array(
			array(
				'<?xml version="1.0"?><xs:schema xmlns:xs="http://www.w3.org/2001/XMLSchema"><xs:element name="note"><xs:complexType><xs:sequence><xs:element name="to" type="xs:string"/><xs:element name="from" type="xs:string"/><xs:element name="heading" type="xs:string"/><xs:element name="body" type="xs:string"/></xs:sequence></xs:complexType></xs:element></xs:schema>',
			)			
		);
	}
	
	/**
	 * @dataProvider provider_should_prettify_XML_data
	 * @param array $input_data 
	 */
	public function test_should_prettify_XML_data($response_text)
	{		
		
		// Store the data
		$formatter = new Mimic_Response_Formatter_XML;
		$formatter->put_contents(vfsStream::url('responses/'), 'xml_test', $response_text);
		
		// Load the data		
		$xml_text = file_get_contents(vfsStream::url('responses/xml_test.xml'));
		
		$this->assertXmlStringEqualsXmlString($response_text, $xml_text);
		$this->assertNotEquals($response_text, $xml_text);
	}

}
