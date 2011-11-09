<?php
return array(
	'base_path' => APPPATH.'tests/test_data/mimic/',
	'enable_recording' => FALSE,
	'enable_updating' => FALSE,
	'active_mime' => 'default',
	'external_client' => NULL,
	'response_formatters' => array(
		// HTML
		'text/html' => 'Mimic_Response_Formatter_HTML',
		
		// Javascript and JSON
		'application/json' => 'Mimic_Response_Formatter_JSON',
		'application/javascript' => 'Mimic_Response_Formatter_Javascript',
		'text/javascript' => 'Mimic_Response_Formatter_Javascript',
		
		// Common XML Content-Type headers
		'application/xml' => 'Mimic_Response_Formatter_XML',
		'text/xml' => 'Mimic_Response_Formatter_XML',
		'application/atom+xml' => 'Mimic_Response_Formatter_XML',
		'application/rss+xml' => 'Mimic_Response_Formatter_XML',
		
		// Generic fallback formatter	
		'*' => 'Mimic_Response_Formatter')
	);