<?php
/*
 * Copyright (c) 2021 - Terminus Studio (https://Terminus.Studio)
 *
 * ezDB - https://github.com/TerminusStudio/ezDB
 *
 * @license https://github.com/TerminusStudio/ezDB/blob/dev/LICENSE.md (MIT License)
 */

namespace TS\ezDB\Tests\Query\Processor;

use TS\ezDB\Query\Processor\MySQLProcessor;
use TS\ezDB\Tests\TestCase;

class MySQLProcessorTest extends ProcessorTest
{
    protected $processor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->processor = new MySQLProcessor();
    }

    public function testWrap()
    {
        $result = $this->processor->wrap('test');
        $this->assertEquals('`test`', $result);

        $result = $this->processor->wrap('test.name');
        $this->assertEquals('`test`.`name`', $result);

        $result = $this->processor->wrap('test as t');
        $this->assertEquals('`test` as `t`', $result);
    }


}