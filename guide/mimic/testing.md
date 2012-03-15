# Testing with Mimic

Mimic is designed to offer an easy, consistent and isolated way to test how your
application interacts with external web services. It mocks the Kohana Request
execution at low level, allowing you to comprehensively test that your higher
level application code reacts as expected to normal usage and simulated error
conditions.

The usual workflow would be:

* Build your unit tests to excercise your application code, and enable Mimic in
the setUp method of your tests.
* Run the unit tests with [recording](recording.md) enabled to allow Mimic to record the real-world
behaviour of the external services you are accessing.
* [Customise](customising.md) and tidy up your request and response definitions -
remembering to remove any sensitive details.
* Commit the tests and scenarios to source control, for later re-use on other
developer machines, CI servers, etc.
* If and when external APIs change (or you could schedule a regular check) re-run
the tests with [updating](updating.md) enabled to automatically refresh your
definitions with new response formats and content, updating your unit tests and
application code accordingly.

## Basic usage with PHPUnit

For most common testing, Mimic comes bundled with a [Mimic_Unittest_Testcase]
base class which provides a useful set of assertions and activates Mimic with
a clean stack in the setUp method. You can of course easily incorporate Mimic within
any test method or class, for example if it's only required for a few of your tests.

You could therefore simply build a test case like this:

    class APITest extends Mimic_Unittest_Testcase
    {
	
		/**
		 * Mimic will set the scenario name to this during setUp (if not NULL)
		 * @var string
		 */
		protected $_mimic_default_scenario = 'api_foo';

        public static function setUp()
        {
			parent::setUp();
			            
            /*
			 * If you want to set recording and updating mode from environment variables (may be
			 * useful for ease) rather than config, you can do something like this. Otherwise, the
			 * base setUp() will do everything you need.
			 */			 
			$this->mimic->enable_recording(Arr::get($_SERVER,'MIMIC_ENABLE_RECORD', FALSE) ? TRUE : FALSE);
			$this->mimic->enable_updating(Arr::get($_SERVER,'MIMIC_ENABLE_UPDATE', FALSE) ? TRUE : FALSE);
        }

        /**
         * This test has four runtime scenarios:
         * - No recordings and recording disabled - Mimic_Exception_RecordingDisabled
         * - No recordings and recording enabled - execute and record live requests
         * - Matching recording and updating disabled - replay response from disk
         * - Matching recording and updating enabled - execute live requests and update
         */
        public function test_api_gets_version()
        {
            $api = new MyApi;
            /*
             * Presume that MyApi::get_version executes some sort of web request
             * to get the API version
             */
            $version = $api->get_version();
            $this->assertEquals('1.2', $version);
        }
    }

In the most basic usage, Mimic is entirely transparent to your tests other than
making it active before calling methods that execute external requests.

## Verifying expectations

Of course, more often you'll be interested in more than just your application's
high-level behaviour. You'll probably want to test the observable external actions
of your code - for example verifying that your code is not calling more API methods
than you expect, or that reading a resource is not making changes to it.

The [Mimic_Unittest_Testcase] base class provides a set of useful assertion methods to
verify common expectations.

Method                        | Tests
------------------------------|--------
assertMimicRequestCount		  | An expected number of requests were made
assertMimicLastRequestURL	  | The last request URL
assertMimicLastRequestMethod  | The last request method
assertMimicLastRequestHeader  | A header value in the last request
assertMimicLastRequestQuery   | A query value in the last request
assertMimicLastRequestBody    | The body-content of the last request
assertMimicRequestsContains   | At least one request was made to a given URL (optionally, with a given method)
assertMimicRequestsNotContains| No requests were made to the given URL (optionally, with a given method)

### Testing individual requests
To build on the test above, you could add the following to your testcase:

    class APITest extends Mimic_Unittest_Testcase
    {
	
        public function test_api_gets_version()
        {
            $api = new MyApi;
            /*
             * Presume that MyApi::get_version executes some sort of web request
             * to get the API version
             */
            $version = $api->get_version();
            $this->assertEquals('1.2', $version);
			
			// Check the web request happened
			$this->assertMimicRequestCount(1);
			$this->assertMimicLastRequestURL('http://my.api.com/version');
			$this->assertMimicLastRequestMethod('GET');
        }
    }

### Requests (not)? contains
Obviously, assertMimicRequestsContains and assertMimicRequestsNotContains are less powerful than the tests against
last request, but are also less brittle over time and allow many simple checks. For example, perhaps you want to
establish that an API call only makes a connection if the client has supplied authentication. You'll obviously
want to verify that the method throws an exception (or whatever your logic flow requires). But you may also want
to be sure that the exception was thrown before the request was sent - in that case you could test:

    public function test_api_only_deletes_if_authenticated()
    {
		$api = new MyApi;
		
		try
		{
			$api->delete(5);
		}
		catch(MyApi_Exception_Not_Authenticated $e)
		{
			$this->assertMimicRequestsNotContains('http://api.foo.thing/5', 'DELETE');
			return;
		}
		
		$this->fail('Expected MyApi_Exception_Not_Authenticated was not thrown');
    }

### More complex tests

For other expectations, Mimic provides a number of methods that allow you to access and 
inspect the requests your application code has made.

Method                       | Returns / Action
-----------------------------|--------
[Mimic::last_request]        | [Request] object for the most recently executed request
[Mimic::request_count]       | The number of requests that have been executed
[Mimic::request_history]\(id)| An array of requests (or a single element) in order executed
[Mimic::reset_requests]      | Reset the request stack for a fresh test

[!!] Mimic operates as a singleton by default (as we can't inject an instance into
the [Mimic_Request_Client]) so you need to call [Mimic::reset_requests] before
each test - the easiest way to do this is in your setUp() method. The [Mimic_Unittest_Testcase]
base class handles this for you.

If you find you're regularly using the same custom assertions, please do [send a pull request](https://github.com/acoulton/mimic/pulls)
for inclusion in the base test cases.

---
Continue to [Updating Definitions](updating.md)
