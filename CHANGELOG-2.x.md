CHANGELOG for 2.x.x
===================

This changelog references the relevant changes done in 2.x minor versions.

To get the diff between two versions, go to 
https://bitbucket.org/gmodev/beanstalk-library/branches/compare/v2.0.0..v1.0.0

* NEXT

* 2.7.0 (2017-07-26)
    * Moved ServiceProviders to `Bridge` folder and deprecated old ones. Didn't keep Pimple 3 version since it wasn't in use yet.
    * Deprecated `BeanstalkKeys`.
    * Added php requirement to composer.json (>=5.3).
    * Only redirect worker output to log file if directory exists.
    * Fix logic in `RunOnceRunnerDecorator`. It's not really "run once" anymore; it's run until there are no more jobs.
    * Fix `RpcRunner` not returning job from `preProcessJob`.
    * PSR-2 Formatting.
    * Replaced deprecated `ISerializable` with `SerializableInterface`.
    * Replaced deprecated `String` with `Str`.
    * Removed deprecated `ClassNameResolverInterface`, it was worthless.
    * Removed remaining .idea files.
    * Tests
        * Updated PHPUnit 3.7 -> 4.8.
        * Added Symfony's PHPUnit Bridge.
        * Added PHPUnit config. 
        * Added Travis config.
        * Updated Tests namespace. Organize structure to match their src files.
        * Fixed failing tests.

* 2.6.0 (2016-02-18)
    * Updated WebJobProducer to wrap Queue instead of creating it
    * Updated job unserialization to handle native serialization
    * Moved console commands to ServiceProvider and automatically add them to "console" key
    * Fixed queue:stats command accepting tubes to filter

* 2.5.0 (2015-11-12)
    * Added Tube class which wraps Queue commands for a specific tube
    * Added getTube($name) and getTubes() to Queue. Deprecated listTubes()
    * Added Job and Worker Logger Processors
    * Added FluentJobError
    * Updated peek command to dump job data with Symfony VarDumper (if installed)
    * Updated String to Str for PHP 7.0 compatibility
    * Added cron option to queue stats command
    * Many updates to ArrayQueue
    * Fixed delete jobs command getting stuck in infinite loop
    * Fixed workers not starting with Ansible (using "nohup")

* 2.4.x (2015-03-09)

    * Added `ContainerAwareWorker`
    * Allowing JobErrorHandlers to be given a logger if they implement `LoggerAwareInterface`.
    * Fix throwing exception if beanstalkd is down
    * Remove and ignore composer.lock

* 2.3.x (2014-12-11)

    * `RunnerInterface::preProcessJob` returns a `Job` instance
    * `RunnerDecorator` does not call wrapped runner's `processJob` method, since it breaks the wrapping.
    * Added pause command to console app

* 2.2.0 (2014-12-11)

    * Added bury command to console app

* 2.1.x (2014-12-08)

    * `Queue` peek methods return `NullJobs` instead of throwing exceptions
    * Handling `NullJobs` within `Queue` class
    * Fix deleting jobs with `ArrayQueue`
    * Burying unserializable jobs by default
    * Added `JobConverterRunner` to handle jobs with unserializable data

* 2.0.0 (2014-11-20)

    * first release
