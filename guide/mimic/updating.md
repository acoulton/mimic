# Updating definitions and responses

Over time, the implementation of external services is likely to change - response
content, request parameters etc will need to be updated.

Mimic makes this process much easier by offering an updating mode which can help
get you most of the way towards updating your request definitions and response
specifications.

When you enable updating, Mimic will execute external requests even if it finds
a match, and will update the request definition and response body file with
whatever the remote server returns.

## Wildcard request definitions

Of course, if you're taking advantage of wildcards in your definitions, you probably
don't want to overwrite them with the details of the request that was executed.

Mimic deals with this by storing the request query, headers and method in the
_executed_request array, allowing you to leave your wildcards untouched while still
recording the details of exactly what was executed to generate the returned response.

## Enabling updating

To enable updating mode, simply call [Mimic::enable_updating]\(TRUE) or set the
appropriate value in your [configuration](config.md).

---
Continue to [Configuration](config.md)
