# Customising responses

For some use cases you will be able to use Mimic without ever customising its
request matching or responses. You will simply enable recording mode, execute your
tests to capture the details and then run future tests against the recorded
definitions and responses.

For most use cases, though, you will want to customise the way that Mimic matches
particular requests, or customise responses. If your application is accessing
sensitive or authenticated resources you will also want to remove any credentials
or details before committing the [scenario](/#scenarios) files to source control.

[!!] **Whenever you are accessing sensitive data or using authentication, make sure
you edit and review your scenarios before publishing to ensure any secrets are
removed!**

## Scenario File Storage

Mimic uses a configurable base path, name of scenario and details of the request
to store details of requests and responses on the file system in a predictable way:

Request                                |Path
---------------------------------------|--------------------
http://www.google.co.uk/about          | {base}/{scenario}/http/www.google.co.uk/about/
https://www.google.co.uk/              | {base}/{scenario}/https/www.google.co.uk/
http://www.google.co.uk/search.php?q=1 | {base}/{scenario}/http/www.google.co.uk/search.php/

Each URL therefore has a specific folder on the file system containing:

 * A definition file (request_index.php)
 * Zero or more response body files

All of these files can be edited by hand to customise the way that Mimic matches
requests and the responses it returns.

## Definition Files

Definition files are PHP files that return an array of request definitions. Each
definition contains an associative array of details that are used to match the
request and define the response that will be returned. The format is fairly
straightforward:

    return array(
        array(
            // The request method to match
            'method' => '*',
            // The query parameters required for a match
            'query' => array(
                'filter' => 'active',
                'order' => new Mimic_Request_Wildcard_Require,
            ),
            // The request header values required for a match
            'headers' => array(),
            // Details of the response that will be returned if matched
            'response' => array(
                // HTTP response status code
                'status' => '200',
                // Response headers
                'headers' => array(),
                // Relative path to a file containing the response body
                'body_file' => '1.html'
            // Details of the request that was executed to build this definition (optional)
            '_executed_request' => array(
                'method' => 'GET',
                'query' => array(
                    'filter' => 'active',
                    'order' => 'desc',
                ),
                'headers' => array(),
            )
            )));

For more on how requests are matched, see [the section on request matching](matching).

## Response bodies

For performance reasons, response bodies are stored separately to the definition
file in the same directory. By default, they're named for the index of the
relevant request definition, and their relative filename stored in the definition
file. They can easily be renamed (and the definition updated) for more semantic
purposes if required. Likewise the same response body can be used by multiple
request definitions (for example, if different response headers are required but
the body is the same).

Response bodies are stored using [response formatters](formatters) which apply an
appropriate file extension for the content type and can convert the content to a
human readable format (for example, adding newlines and indentation to JSON responses)
for easier editing and verification.

---
Continue to [Testing with Mimic](testing)