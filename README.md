# Mimic [![Build Status](https://secure.travis-ci.org/acoulton/mimic.png)](http://travis-ci.org/acoulton/mimic)

Mimic is a module for [Kohana Framework v3.2 and up](http://kohanaframework.org)
that allows easy mocking and testing of an application's interaction with external
web services.

If you've used the [VCR module for Ruby](https://github.com/myronmarston/vcr),
you'll recognise the concept. By default,
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

## Scenarios
Mimic supports multiple named scenarios (nothing more complex than a separate
set of disk paths where request/response files are stored) so that you can model
different behaviour for the same request URL - for example an error condition, or
the difference between authenticated and anonymous requests. This is broadly
equivalent to VCR's "Cassettes".

## Tweaking responses
Requests and responses are stored in easily editable formats, allowing you to tweak
both the request and response for a variety of scenarios. For example, you might
want to customise the response to fake an error condition that is difficult
to trigger from the client side.

By default, responses with supported content types (currently XML and JSON) will
be passed through a formatter before saving. The formatter adds additional whitespace
so that responses are human-readable and more easily edited, diffed, etc.

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

## Unit Tests
Mimic is fully unit tested (PHPUnit tests are included in this repository). The
tests make use of the [vfsStream](https://github.com/mikey179/vfsStream)
virtual filesystem library for mocking and verifying file system interactions.

To run the tests on a standard Kohana installation (with the unittest module)
just run the following from the base folder:

    phpunit --bootstrap=modules/unittest/bootstrap.php --group=mimic modules/unittest/tests.php

The Mimic test suite also runs continuously on [Travis CI](http://travis-ci.org/acoulton/mimic) against the current codebase. Helper scripts are provided in the dev/ folder to create a sandbox with up to date Kohana core and required modules.

## Further Documentation
Is included with the module, integrated into the Kohana online userguide.

## Roadmap and stability
Mimic is currently under initial development. Intended functionality includes:

* Formatter support for Javascript and HTML.
* Support for matching on body content of POST/PUT requests
* PHPUnit base testcase class integrating Mimic and offering common assertions
* A self-contained Kohana application using Mimic to power a proxy server controlled
  through an API. This can be used for isolated functional testing with tools like
  Behat (the proxy will come with a set of useful Behat step definitions). You will
  configure your server's internet routing table to pass all outbound requests to
  the Mimic Proxy, meaning your production application code can be fully exercised
  against a set of mocked web request/responses and allowing you to share web scenarios
  between unit and functional tests.


## Licence
Mimic &copy; 2011 [Ingenerator](http://www.ingenerator.com) and is
released under the [Kohana Licence](http://kohanaframework.org/licence)

## Issues and contributions
Bug reports and feature requests are welcome via the project's Github issue tracker,
preferably with a pull request attached.

Pull requests should:

* be targeted against the relevant develop branch
* be contained in a separate topic/bug branch in your forked respository (in case
  further commits are required to complete your solution)
* generally speaking, include a unit test or new dataset for an existing test that
  fails before your new code is merged and passes afterwards
* adopt the Kohana coding standards
