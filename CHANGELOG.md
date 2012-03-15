# Mimic for PHP
#(c) 2012 Andrew Coulton
# Changelog

## v0.6

### New features
* Use .md extension for userguide links so that they can be read on github 
(requires latest version of Kohana 3.2 to browse in the userguide module)
* Provides a base unittest testcase class for common assertions and requirements (see issue #4)
* 

### Bugfixes
* Explicitly sets Request content-length header if not present - fixes #5
* Only requires vfsStream to run Mimic's own unit tests - previously caused an 
error if trying to run other testcases in the project without vfsStream installed.

## v0.5

* First version