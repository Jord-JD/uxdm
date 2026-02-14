<?php

use JordJD\uxdm\Objects\DestinationContainer;
use JordJD\uxdm\Objects\Destinations\DebugOutputDestination;
use PHPUnit\Framework\TestCase;

final class DestinationContainerTest extends TestCase
{
    public function testCreation()
    {
        $destination = new DebugOutputDestination();
        $fields = ['name'];

        $destinationContainer = new DestinationContainer($destination, $fields);

        $this->assertEquals($destination, $destinationContainer->destination);
        $this->assertEquals($fields, $destinationContainer->fields);
    }
}
