# Response Formatters
Response formatters are used to assign an appropriate file extension to the response
body file for supported content-types, and to make review, source control and editing
of responses easier by converting compressed formats to human-readable.

All formatters support invalid/incomplete responses, which will be stored to disk
exactly as returned rather than attempting to clean up or throwing exceptions.

## Full implementations
### JSON
The [Mimic_Response_Formatter_JSON] formatter outputs JSON objects with a line
per key/value pair and nested indentation. This is currently achieved with a
PHP replacement for json_encode, though PHP 5.4 introduces a native version with
pretty print functionality which will be used eventually.

### XML
The [Mimic_Response_Formatter_XML] formatter uses DOMDocument to add whitespace
and formatting to an XML document.

## Stubs
### HTML
[Mimic_Response_Formatter_HTML] currently only saves the response
body with an .html extension - a future version will introduce HTML Tidy or similar.

### Javascript
[Mimic_Response_Formatter_Javascript] currently only saves the response body with
a .js extension - a future version will introduce a javascript de-compression/
de-minification library.
