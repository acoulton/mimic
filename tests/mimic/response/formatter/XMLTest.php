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
}
