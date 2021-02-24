<?php


namespace TS\ezDB\Tests\Drivers;


use TS\ezDB\DatabaseConfig;
use TS\ezDB\Drivers\PDODriver;

class PDODriverTest extends DriverTestCase
{
    /** @var PDODriverr */
    protected static $driver;

    /** @var \PDOStatement */
    protected static $stmt;

    /**
     * @inheritDoc
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        static::$driver = new PDODriver(new DatabaseConfig(self::$dbConfig['mysqli']));
    }

    public function getDriver()
    {
        return static::$driver;
    }

    public function getStmt()
    {
        return static::$stmt;
    }

    /**
     * @depends testConnect
     */
    public function testHandle()
    {
        $handle = $this->getDriver()->handle();

        $this->assertInstanceOf(\PDO::class, $handle);
    }

    /**
     * @depends testConnect
     */
    public function testPrepare()
    {
        self::$stmt = $this->getDriver()->prepare("INSERT INTO `test` (`name`) VALUES (?)");

        $this->assertInstanceOf(\PDOStatement::class, $this->getStmt());
    }

    /**
     * @depends testPrepare
     */
    public function testBind()
    {
        $name = 'ezDB';
        self::$stmt = $this->getDriver()->bind($this->getStmt(), $name);

        $this->assertInstanceOf(\PDOStatement::class, $this->getStmt());
    }

    /**
     * @depends testBind
     */
    public function testExecute()
    {
        $results = $this->getDriver()->execute($this->getStmt(), true, false);

        $this->assertEquals(1, $results);
    }

    /**
     * @inheritDoc
     */
    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        static::$driver->connect();

        static::$driver->query("TRUNCATE TABLE `test`");
    }
}