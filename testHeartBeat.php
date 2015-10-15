<?php
require 'HeartBeat.php';
class HeartBeatTest extends PHPUnit_Framework_TestCase
{
    // ...

    public $heartBeat;

    public $startTime;

    protected function setUp()
    {
        $this->heartBeat = new HeartBeat("test");
    }

    public function testProcessStart()
    {
        $startTime = $this->heartBeat->processStart();
        $lifeTime = $this->heartBeat->getLifeTime();
        sleep(10);
        $this->assertEquals(strtotime($startTime), $lifeTime['startTime']);
    }

    public function testPulse()
    {
        $lastBeat = $this->heartBeat->pulse();
        $this->assertEquals(10, strlen($lastBeat));
    }

    public function testGetLifeStatusDie()
    {

        $lifeStatus = $this->heartBeat->getLifeStatus(1);
        $this->assertEquals("die", $lifeStatus['lifeStatus']);
    }

    public function testProcessEnd()
    {
        $endTime = $this->heartBeat->processEnd();
        $lifeTime = $this->heartBeat->getLifeTime();
        $this->assertEquals(strtotime($endTime), $lifeTime['endTime']);
    }

    public function testGetPulse()
    {
        $lastBeat = $this->heartBeat->getLastPulse();
        $this->assertEquals(10, strlen($lastBeat));
    }

    public function testGetLifeStatusAlive()
    {
        $lifeStatus = $this->heartBeat->getLifeStatus(3600);
        $this->assertEquals("alive", $lifeStatus['lifeStatus']);
    }

    public function testGetLifeStatusFinish()
    {
        $lifeStatus = $this->heartBeat->getLifeStatus(1);
        $this->assertEquals("process_finish", $lifeStatus['lifeStatus']);
    }

    public function testPulseWorker()
    {
        $pulseStatus = $this->heartBeat->pulseWorker();
        $this->assertTrue($pulseStatus);
    }

    public function testCheckWorkerStatus()
    {
        $pulseStatus = $this->heartBeat->checkWorkerStatus();
        $this->assertEquals(1, count($pulseStatus));
    }

    public function testDeleteWorkerPulse()
    {
        $deletePulseStatus = $this->heartBeat->deletePulseWorker();
        $this->assertEquals(true, $deletePulseStatus);
    }

    public function testSetCounter()
    {
        $testCounterStatus = $this->heartBeat->setCounterValue("test", 10);
        $this->assertEquals(true, $testCounterStatus);
    }

    public function testGetCounter()
    {
        $testCounterStatus = $this->heartBeat->getCounterValue("test");
        $this->assertEquals(10, $testCounterStatus);
    }

    public function testIncreaseCounter()
    {
        $this->heartBeat->increaseCounterValue("test");
        $testCounterStatus = $this->heartBeat->getCounterValue("test");
        $this->assertEquals(11, $testCounterStatus);
    }

    public function testDecreaseCounter()
    {
        $this->heartBeat->decreaseCounterValue("test");
        $testCounterStatus = $this->heartBeat->getCounterValue("test");
        $this->assertEquals(10, $testCounterStatus);
    }

}
