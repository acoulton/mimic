# Testing with Mimic

Mimic is designed to offer an easy, consistent and isolated way to test how your
application interacts with external web services. It mocks the Kohana Request
execution at low level, allowing you to comprehensively test that your higher
level application code reacts as expected to normal usage and simulated error
conditions.

The usual workflow would be:

* Build your unit tests to excercise your application code, and enable Mimic in
the setUp method of your tests.
* Run the unit tests with [recording](recording) enabled to allow Mimic to record the real-world
behaviour of the external services you are accessing.
* [Customise](customising) and tidy up your request and response definitions -
remembering to remove any sensitive details.
* Commit the tests and scenarios to source control, for later re-use on other
developer machines, CI servers, etc.
* If and when external APIs change (or you could schedule a regular check) re-run
the tests with [updating](updating) enabled to automatically refresh your
definitions with new response formats and content, updating your unit tests and
application code accordingly.

## Basic usage with PHPUnit

For basic usage with PHPUnit, you could simply build a test case like this:

    class APITest extends Unittest_Testcase
    {
        public static function setUpBeforeClass()
        {
            // Activate Mimic and set recording mode from environment var
            // You can equally just use the config from your config files
            Mimic::instance()
                ->enable_recording(Arr::get($_SERVER, 'MIMIC_RECORD', FALSE));
                ->enable_updating(Arr::get($_SERVER, 'MIMIC_UPDATE', FALSE));
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

Mimic provides a number of methods that allow you to access and inspect the requests
your application code has made.

Method                       | Returns / Action
-----------------------------|--------
[Mimic::last_request]        | [Request] object for the most recently executed request
[Mimic::request_count]       | The number of requests that have been executed
[Mimic::request_history]\(id)| An array of requests (or a single element) in order executed
[Mimic::reset_requests]      | Reset the request stack for a fresh test

[!!] Mimic operates as a singleton by default (as we can't inject an instance into
the [Mimic_Request_Client]) so you need to call [Mimic::reset_requests] before
each test - the easiest way to do this is in your setUp() method.

So, your unit test code might look like this:

    class APITest extends Unittest_Testcase
    {
        protected $_mimic = NULL;

        public function setUp()
        {
            $this->_mimic = Mimic::instance();
            $this->_mimic->reset_requests();
        }

        public function test_api_gets_version()
        {
            $api = new MyApi;
            /*
             * Presume that MyApi::get_version executes some sort of web request
             * to get the API version
             */
            $version = $api->get_version();
            $this->assertEquals('1.2', $version);

            // Verify expected web request calls
            $this->assertEquals(1, $this->_mimic->request_count());
            $last_request = $this->_mimic->last_request();
            $this->assertEquals('http://my.api.com/version', $last_request->uri());
            $this->assertEquals('GET', $last_request()->method());
        }
    }

---
Continue to [Updating Definitions](updating)