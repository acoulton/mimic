# Mimic

Mimic is a module for [Kohana Framework v3.2 and up](http://kohanaframework.org)
that allows easy mocking and testing of an application's interaction with external
web services.

If you've used the VCR module for Ruby, you'll recognise the concept. By default,
Mimic intercepts all external requests and throws an exception. When recording mode
is enabled, Mimic executes the external request and records the response (complete
with headers and response status) to disk. Future requests to the external resource
will return the response that has been stored on disk, allowing increased performance
and more importantly an idempotent implementation of unit and functional tests with
a minimum of configuration or mocking code.

## Matching requests
Outgoing requests can be matched by a variety of characteristics:

* URL
* HTTP Method
* URI Parameters
* Request Headers

By default, Mimic records all of these. The recording files can then be easily
modified to allow looser matching of requests (useful if you want to return the
same response in multiple request scenarios).

## Tweaking responses
Requests and responses are stored in easily editable formats, allowing you to tweak
both the request and response for a variety of scenarios. For example, you might
want to customise the response to fake an error condition that is difficult
to trigger from the client side.

***If you are performing authenticated requests or accessing non-public content,
you should always review the recording files by hand and remove any passwords,
authentication tokens or private content before committing to a source code
repository!***

## Verifying application behaviour
In addition to replaying "canned" responses, Mimic keeps a history of requests
executed and responses returned. You can access the history from your test cases
to verify:

* That an expected pattern of requests were sent (for example, that a given 
  parameter was present in an outbound query string)
* That an expected number of requests were sent
* Pretty much anything else you can think of.

## Roadmap and stability
Mimic is currently under initial development. Intended functionality includes:

* Allowing multiple "mime" scenarios (equivalent to VCR's "Cassettes") where the
  same requests will return different responses - for example to separately test
  behaviour under normal or fault conditions, or to handle authenticated and 
  anonymous access.
* "Prettifying" responses of known content types (HTML, XML, JSON) to ease editing
  them by hand if they would normally be served minified by the external application.
* A self-contained Kohana application using Mimic to power a proxy server controlled
  through an API. This can be used for isolated functional testing with tools like
  Behat (the proxy will come with a set of useful Behat step definitions). You will
  configure your server's internet routing table to pass all outbound requests to
  the Mimic Proxy, meaning your production application code can be fully exercised
  against a set of mocked web request/responses and allowing you to share web scenarios
  between unit and functional tests.