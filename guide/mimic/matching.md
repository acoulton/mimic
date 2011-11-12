# Matching

When an external request is executed, Mimic searches for a request definition that
matches the request. If found, the stored response is loaded from disk and returned.

## Specific matching

Requests are matched against all of:

* URL (including whether http or https)
* Request method
* Query parameters
* Header values

By default, all of these details are recorded with their exact values when recording
requests, and must exactly match the executed request for the response to be replayed.

## Wildcards

Sometimes you will want to allow more generic responses. Mimic request definition
files can be edited to use wildcards for particular matches.

### Request Methods

By default, the request method is matched exactly. You can indicate that a
definition should match any method by using the wildcard *.

[!!] HEAD requests will always match against GET unless a specific definition
for HEAD is found first.

    return array(
        array(
            // will match any method
            'method' => '*',
            'query' => array(),
            'headers' => array(),
            'response' => array(
                'status' => '200',
                'headers' => array(),
                'body_file' => '1.html'
            )));

### Query and Header Values

A request will only match if the header and query keys match the definition file.
You can indicate allow wildcards for these by setting the value to an instance of
the special placeholder class [Mimic_Request_Wildcard_Require].

    return array(
        array(
            'method' => 'GET',
            'query' => array(
                // Matches if the filter query parameter is 'active'
                'filter' => 'active',
                // Matches any request with an 'order' query parameter
                'order' => new Mimic_Request_Wildcard_Require,
            ),
            'headers' => array(),
            'response' => array(
                'status' => '200',
                'headers' => array(),
                'body_file' => '1.html'
            )));

### Storing the original request details

Sometimes you will want to store the original details of the specific recorded
match (for example to provide parameters for updating the request definition in
future, or for debugging.

You can copy the method, query and headers keys to a sub-array _executed_request
for this purpose.

    return array(
        array(
            'method' => '*',
            'query' => array(
                'filter' => 'active',
                'order' => new Mimic_Request_Wildcard_Require,
            ),
            'headers' => array(),
            'response' => array(
                'status' => '200',
                'headers' => array(),
                'body_file' => '1.html'
            '_executed_request' => array(
                'method' => 'GET',
                'query' => array(
                    'filter' => 'active',
                    'order' => 'desc',
                ),
                'headers' => array(),
            )
            )));



## Matching Sequence

A request matches the first definition found in the relevant file. Just as
with Kohana routes, you should therefore define the most specific first, and
wildcards later.

    return array(
        array(
            'method' => '*',
            'query' => array(
                'filter' => 'active',
                'order' => new Mimic_Request_Wildcard_Require,
            ),
            'headers' => array(),
            'response' => array(
                'status' => '200',
                'headers' => array(),
                'body_file' => '1.html'
            )),
        // This definition will never match, because the request will also match
        // the wildcard definition above. It should be placed first in the file.
        array(
            'method' => 'GET',
            'query' => array(
                'filter' => 'active',
                'order' => 'asc',
            ),
            'headers' => array(),
            'response' => array(
                'status' => '200',
                'headers' => array(),
                'body_file' => '2.html'
            )),
    );

---
Continue to [Customising Responses](customising)