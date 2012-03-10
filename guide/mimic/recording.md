# Recording External Requests

By default, Mimic fully isolates your application from any external requests. If
it cannot match a request to a stored definition, the [Mimic_Request_Client] will
throw an [Exception_Mimic_Recording_Disabled].

This makes it easy to identify requests that you have not already mocked, and
ensures that your test scenarios can execute without making external web services
calls.

    // Activate Mimic
    $mimic = Mimic::instance();

    // Executing a request will throw Exception_Mimic_Recording_Disabled
    Request::factory('http://www.ingenerator.com/about')
        ->execute();

You can of course create request definition files by hand, but this is prone to
errors and doesn't offer much advantage over simply mocking the Request classes
in your unit tests.

Instead, Mimic allows you to record a live request (complete with full details
of the request and the response) and save it to disk for customisation and future
use.

To use this feature, you'll need to enable recording mode either in your
[configuration](config.md) or by setting [Mimic::enable_recording]\(TRUE).

    // Enable recording
    Mimic::instance()->enable_recording(TRUE);

    // This will execute the external request and record the details to disk
    Request::factory('http://www.ingenerator.com/about')
        ->execute();

---
Continue to [Matching Requests](matching.md)
