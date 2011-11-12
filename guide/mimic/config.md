# Configuration

Mimic uses the Kohana [Config] system to manage its configuration. Settings are
stored in the mimic config group, and by default the module ships with the
following configuration:

Key             |Default                          |Function
----------------|---------------------------------|--------
base_path       | APPPATH/tests/test_data/mimic/  | Root path for storage of Mimic files
active_scenario | default                         | [Scenario](/#scenarios) in use (sub-path for file storage)
debug_headers   | FALSE                           | Add X-Mimic debug headers to responses to indicate where match was found?
enable_recording| FALSE                           | Allow recording of requests that aren't matched?
enable_updating | FALSE                           | Update matched requests by executing and storing new response?
external_client | NULL                            | Specify an external client to use when executing requests - if NULL will use whatever is set for [Request_Client_External]::$client
response_formatters | array                       | An array of [formatters](formatters) to use for given content-types

## Default formatters

As shipped, Mimic uses the following default [formatters](formatters):

Content-Type             | Formatter
-------------------------|------------
text/html                |[Mimic_Response_Formatter_HTML]
application/json         |[Mimic_Response_Formatter_JSON]
application/javascript   |[Mimic_Response_Formatter_Javascript]
text/javascript          |[Mimic_Response_Formatter_Javascript]
application/xml          |[Mimic_Response_Formatter_XML]
text/xml                 |[Mimic_Response_Formatter_XML]
application/atom+xml     |[Mimic_Response_Formatter_XML]
application/rss+xml      |[Mimic_Response_Formatter_XML]
* (others)               |[Mimic_Response_Formatter]

---
Continue to [Response Formatters](formatters)