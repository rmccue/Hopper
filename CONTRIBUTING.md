Structure
---------
Hopper is based on the Symfony Web Profiler, and shares much of its internal
structure.

Hopper is based around the concept of collectors. Collectors supply data, and
act as a sort of debug-information-model. These collectors are automatically
run as needed by Hopper, and the data is stored for each request in a Profile.

To show the data, you also need to create a view for the collected data.
