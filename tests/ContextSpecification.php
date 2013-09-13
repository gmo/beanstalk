<?php

abstract class ContextSpecification extends \PHPUnit_Framework_TestCase
{
    public static function setUpBeforeClass()
    {
        static::given();
        static::when();
    }
    public static function tearDownAfterClass()
    {
        static::cleanUp();
    }

    /**
     * Need to override with the given context
     */
    protected static function given() {}
    /**
     * Need to override and call the method or generate the event that is being tested
     */
    protected static function when() {}
    /**
     * Need to override and clean up any items like clearing queues or truncating tables
     */
    protected static function cleanUp() {}

    // TODO: Need to remove this
    public function setUp()
    {
        $this->establishContext();
        $this->because();
    }
    // TODO: Need to remove this
    protected function  establishContext() {}
    // TODO: Need to remove this
    protected function  because() {}
}
