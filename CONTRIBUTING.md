Structure
---------
Hopper is based on the Symfony Web Profiler, and shares much of its internal
structure.

Hopper is based around the concept of collectors. Collectors supply data, and
act as a sort of debug-information-model. These collectors are automatically
run as needed by Hopper, and the data is stored for each request in a Profile.

To show the data, you also need to create a view for the collected data.


Porting from Symfony
--------------------
If you want to port a component from its Symfony equivalent, the first point to
start is the [Symfony collector configuration][]. This lists the collectors in
the Symfony Framework. Each class can be ported individually, and matched with
its equivalent template. The template should be ported as-is for the most part,
and you should change the collector rather than the template where possible.

[Symfony collector configuration]: https://github.com/symfony/symfony/blob/master/src/Symfony/Bundle/FrameworkBundle/Resources/config/collectors.xml


Hopper's internal workflow
--------------------------
Here's how Hopper works internally:

* You request a monitorable page (admin/front end page, excluding Ajax requests)
* Hopper initialises its components and asks them to collect data
* Hopper adds an item to the admin bar, plus Javascript
* WordPress renders the page
* Hopper sends a final late collection notice to any registered late collectors
* Hopper saves all collected data for the request
* Your browser renders the page
* Hopper's Javascript loads the Hopper UI on to the page
