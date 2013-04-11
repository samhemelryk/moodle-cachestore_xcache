XCache variable cache store
===========================

XCache is a PHP opcode cacher designed to accellerate the performance of PHP execution. One of the features it provides is a variable data store that can be used within PHP code to persist variables between requests.
This plugin is an XCache cache store designed to utilise that variable data store and allow Moodle to make better use of XCache.

Installation of XCache
----------------------

For those running an Linux distro and having installed PHP through a package manager I would recommend first checking your package manager to see if an XCache package is provided. If so I would certainly recommend installing through the package manager as it is by far the easiest route.
Through Ubuntu/Debian this can be achieved using the following command:

    sudo apt-get install php5-xcache

It is recommended to follow the installation instructions that XCache provide.
You can download XCache from http://xcache.lighttpd.net/wiki/ReleaseArchive. I recommend using the latest stable version.
Installation depends upon whether you are installing from source, or installing a prebuilt binary package.
Documentation on both methods can be found at http://xcache.lighttpd.net/wiki/DocToc (Look for the Install from ... links on the right)

Configuring XCache for use
--------------------------

Detailed information about the ini settings can be found at http://xcache.lighttpd.net/wiki/XcacheIni.
There are a couple that we recommend understanding as they are vital to the use of this plugin.

    # Controls the size of the variable cache available through XCache.
    # It must be set > 0 in order for the this plugin to be usable.
    xcache.var_size = 64m

    # Sets a default TTL for cached variables.
    # Highly recommend setting this as it ensures you don't end up with
    # a cache full of stale data.
    xcache.var_ttl = 3600

    # Sets the garbage collection interval. Essential for keeping the
    # cache free of stale data.
    xcache.var_gc_interval = 300

Installation within Moodle
--------------------------

Browse to your site and log in as an administrator.
Moodle should detect that a new plugin has been added and will proceed to prompt you through an upgrade process to install it.
The installation of this plugin is very minimal. Once installed you will need to need to create an XCache cache store instance within the Moodle administration interfaces.

Making use of XCache within Moodle
-------------------------------

Installing this plugin makes XCache available to use within Moodle however it does not put it into use.
The first thing you will need to do is create an XCache cache store instance.
This is done through the Cache configuration interface.

1. Log in as an Administrator.
2. In the settings block browse to Site Administration > Plugins > Caching > Configuration.
3. Once the page loads locate the XCache row within the Installed cache stores table.
4. You should see an "Add instance" link within that row. If not then the XCache extension has not being installed correctly.
5. Click "Add instance".
6. Give the new instance a name and click "Save changes". You should be directed back to the configuration page.
7. Locate the Configured cache store instances table and ensure there is now a row for you XCache instance and that it has a green tick in the ready column.

Once done you have an XCache instance that is ready to be used. The next step is to map definitions to make use of the XCache instance.

Locate the known cache definitions table. This table lists the caches being used within Moodle at the moment.
For each cache you should be able to Edit mappings. Find a cache that you would like to map to the XCache instance and click Edit mappings.
One the next screen proceed to select your XCache instance as the primary cache and save changes.
Back in the known cache definitions table you should now see your XCache instance listed under the store mappings for the cache you had selected.
You can proceed to map as many or as few cache definitions to the XCache instance as you see fit.

That is it! you are now using XCache within Moodle.

Information and advice on using XCache within Moodle
-------------------------------------------------

XCache provides a shared application cache that is usually very limited in size but provides excellent performance.
It doesn't provide the ability to configure multiple instances of itself. Moodle allows the admin to create several XCache instances however the prefix for each must be unique as they will all exist within the single XCache variable data store.
Because of its incredible performance but very limited size it is strongly suggested that you map only small, crucial caches to the XCache store.

Also recommended is to map a secondary application cache instance to any definition with the XCache mapped. This ensures that should the XCache data store fill up there will be an alternative cache available.

Bugs, feature requests, help, and further information
-----------------------------------------------------

For bug and feature requests please create issues at tracker.moodle.org.
For help please visit the moodle.org forums.
For more information please visit https://moodle.org/plugins/view.php?plugin=cachestore_xcache