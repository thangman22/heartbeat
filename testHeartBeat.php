<?php
require 'HeartBeat.php';
class HeartBeatTest extends PHPUnit_Framework_TestCase
{
    // ...

    public $heartBeat;

    public $startTime;

    protected function setUp()
    {
        $this->heartBeat = new HeartBeat("127.0.0.1", "6379", 0, "test");

    }

    public function testClearHeartBeat()
    {
        $res = $this->heartBeat->clearHeartBeat();
        $this->assertEquals(true, $res);
    }

    public function testProcessStart()
    {
        $startTime = $this->heartBeat->processStart();
        $lifeTime = $this->heartBeat->getLifeTime("test");
        $this->assertEquals(strtotime($startTime), $lifeTime['startTime']);
    }

    public function testPulse()
    {
        sleep(10);
        $lastBeat = $this->heartBeat->pulse();
        $this->assertEquals(10, strlen($lastBeat));
    }

    public function testGetLifeStatusDie()
    {

        $lifeStatus = $this->heartBeat->getLifeStatus("test", 1);
        $this->assertEquals("die", $lifeStatus['lifeStatus']);
    }

    public function testGetLifeStatusAlive()
    {

        $lifeStatus = $this->heartBeat->getLifeStatus("test", 3600);
        $this->assertEquals("alive", $lifeStatus['lifeStatus']);
    }

    public function testProcessEnd()
    {
        $endTime = $this->heartBeat->processEnd();
        $lifeTime = $this->heartBeat->getLifeTime("test");
        $this->assertEquals(strtotime($endTime), $lifeTime['endTime']);
    }

    public function testGetPulse()
    {
        $lastBeat = $this->heartBeat->getLastPulse("test");
        $this->assertEquals(10, strlen($lastBeat));
    }

    public function testGetLifeStatusFinish()
    {
        $lifeStatus = $this->heartBeat->getLifeStatus("test", 1);
        $this->assertEquals("process_finish", $lifeStatus['lifeStatus']);
    }

    public function testPulseWorker()
    {
        $pulseStatus = $this->heartBeat->pulseWorker();
        $this->assertTrue($pulseStatus);
    }

    public function testCheckWorkerStatus()
    {
        $pulseStatus = $this->heartBeat->checkWorkerStatus("test", 3600);
        $this->assertEquals(1, count($pulseStatus));
    }

    public function testDeleteWorkerPulse()
    {
        $deletePulseStatus = $this->heartBeat->deletePulseWorker("test");
        $this->assertEquals(true, $deletePulseStatus);
    }

    public function testSetCounter()
    {
        $testCounterStatus = $this->heartBeat->setCounterValue("test", 10);
        $this->assertEquals(true, $testCounterStatus);
    }

    public function testGetCounter()
    {
        $testCounterStatus = $this->heartBeat->getCounterValue("test", "test");
        $this->assertEquals(10, $testCounterStatus);
    }

    public function testIncreaseCounter()
    {
        $this->heartBeat->increaseCounterValue("test");
        $testCounterStatus = $this->heartBeat->getCounterValue("test", "test");
        $this->assertEquals(11, $testCounterStatus);
    }

    public function testDecreaseCounter()
    {
        $this->heartBeat->decreaseCounterValue("test");
        $testCounterStatus = $this->heartBeat->getCounterValue("test", "test");
        $this->assertEquals(10, $testCounterStatus);
    }

    public function testListAllHeartBeat()
    {
        $countAllHeartbeat = $this->heartBeat->listHeartBeat();
        $this->assertEquals(3, count($countAllHeartbeat));
    }

}
