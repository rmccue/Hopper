Hopper
======

What even is this?
------------------
The [Debug Bar][] plugin has been an invaluable tool for working with WordPress
code, however it's far from perfect. While it incorporates a heap of useful
data, there's a lot more that it misses. It also doesn't provide many useful
APIs for development.

Hopper is an effort to improve this. Based on Symfony's Web Profiler, the idea
is to use WordPress' internals against itself and provide the best dang profiler
and debugger around.

[Debug Bar]: http://wordpress.org/plugins/debug-bar/


How do I use this?
------------------
### Installing
Download the plugin from this repository, then install the dependencies with
`composer install`.

### Usage
After activation, load any page. You should see the Symfony toolbar appear on
the page.


Using Hopper's features
-----------------------

### Logging
Hopper offers a PSR-3 compatible logger interface that stores messages with the
request data, and displays them in the Hopper interface.

To get the current global logger:

```php
$logger = apply_filters( 'hopper_logger', null );
```

If Hopper is disabled, your code will continue to function correctly as long as
you check for null values. Using a filter for this ensures that your code does
not depend on Hopper, which is important for production environments.


What's with the name?
---------------------
Hopper is named for the legendary [Grace Hopper][] who was a pioneer in the
field of computing, inventing the first compiler and first machine-independent
programming language (COBOL). She's also credited with popularising the term
"debugging", based on her experience with fixing a computer problem by removing
a moth from the system.

A [hopper][] is also a container used to collect and disperse material, which is
a fitting name for a plugin that collects data and compiles it for developers.

[Grace Hopper]: http://en.wikipedia.org/wiki/Grace_Hopper
[hopper]: http://en.wikipedia.org/wiki/Hopper


License
-------
Includes code from the Symfony project. Copyright (c) 2004-2013 Fabien
Potencier.

Includes icons from the Symfony project. Icons created by Sensio
(http://www.sensio.com/) are shared under a Creative Commons Attribution license
(http://creativecommons.org/licenses/by-sa/3.0/).

Hopper is licensed under the GPL license. Copyright 2013 Ryan McCue.