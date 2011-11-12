# About Mimic

[Mimic] allows easy mocking and testing of an application's interaction with external
web services.

If you've used the [VCR module for Ruby](https://github.com/myronmarston/vcr),
you'll recognise the concept. By default, [Mimic] intercepts all external requests
and throws an exception. When [recording mode](recording) is enabled, [Mimic] executes the
external request and records the response (complete with headers and response
status) to disk. Future requests to the external resource will return the
response that has been stored on disk, allowing increased performance
and more importantly an idempotent [implementation of unit and functional
tests](testing) with a minimum of configuration or mocking code.

## Scenarios
Mimic supports multiple named scenarios (nothing more complex than a separate
set of disk paths where request/response files are stored) so that you can model
different behaviour for the same request URL - for example an error condition, or
the difference between authenticated and anonymous requests. This is broadly
equivalent to VCR's "Cassettes".

    $mimic = Mimic::instance()
            ->load_scenario('server_down');

## Matching requests
By default, Mimic matches all of:

* URL
* HTTP Method
* URI Parameters
* Request Headers

However, [matching rules can be easily edited](matching) to allow looser
matching of requests which can be useful if you want to return the same response
for multiple requests - for example to minimise the effort involved in supporting
query parameters that aren't relevant to your test scenario.

## Customising responses
Requests and responses are stored in [easily editable formats](customising),
allowing you to customise both the request and response for a variety of scenarios.
For example, you might want to edit the response to simulate an error condition that is difficult
to trigger from the client side.

[!!] **If you are performing authenticated requests or accessing non-public content,
you should always review the recording files by hand and remove any passwords,
authentication tokens or private content before committing to a source code
repository!**

Mimic aids review, source control and editing of responses by passing supported
content types (currently XML and JSON) through a [formatter](formatters) before
saving. Formatters "pretty-print" the responses, introducing newlines, indentation,
and whitespace to make responses human-readable.

## Verifying application behaviour
In addition to replaying "canned" responses, Mimic keeps a history of requests
executed and responses returned. You can
[access the history from your test cases](testing#verifying-expectations)
to verify:

* That an expected pattern of requests were sent (for example, that a given
  parameter was present in an outbound query string)
* That an expected number of requests were sent
* Pretty much anything else you can think of.

## Minimum requirements

 *  Kohana 3.2 or greater
 *  PHP 5.3 or greater

If you want to run the unit tests, you will need PHPUnit and the
[vfsStream](https://github.com/mikey179/vfsStream) virtual filesystem library.