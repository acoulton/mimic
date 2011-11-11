<?php
/**
 * This empty class does nothing except provide a unique type for use in request
 * index files to specify that a header or query parameter must be present, but
 * can be matched by any value.
 *
 * @package    Mimic
 * @category   Matcher Wildcards
 * @author     Andrew Coulton
 * @copyright  (c) 2011 Ingenerator
 * @license    http://kohanaframework.org/license
 */
class Mimic_Request_Wildcard_Require {

	/**
	 * Does nothing - required to allow var_export to create instances during testing
	 * @return Mimic_Request_Wildcard_Require
	 */
	public static function __set_state()
	{
		return new Mimic_Request_Wildcard_Require;
	}
}