<?php
class HeartBeat
{

    public $redisServer = "198.27.69.122";

    public $redisPort = "6385";

    public $redisDatabase = 2;

    private $redis;

    private $keyName;

    private $prefix = "zocial:heartbeat:";

    public $pid = "";

    public function __construct($keyName)
    {
        $this->$keyName = $keyName;

        if (!class_exists('Redis')) {
            throw new Exception("Redis extension is not found.", 1);
        } else {
            $this->redis = new Redis;
            $this->redis->pconnect($this->redisServer, $this->redisPort);
            $this->redis->select($this->redisDatabase);
        }
    }
    //Lifetime
    public function processStart()
    {
        $startTime = time();
        $this->redis->set($this->prefix . $this->keyName . "lifetime", $startTime);
        return date("r", $startTime);
    }

    public function processEnd()
    {
        $endTime = time();
        $this->redis->append($this->prefix . $this->keyName . "lifetime", "|" . $endTime);
        return date("r", $endTime);
    }

    public function getLifeTime()
    {
        $returnVal = array();
        $lifeTime = $this->redis->get($this->prefix . $this->keyName . "lifetime");
        $explodeLifeTime = explode("|", $lifeTime);
        $returnVal['startTime'] = $explodeLifeTime[0];
        if (isset($explodeLifeTime[1])) {
            $returnVal['endTime'] = $explodeLifeTime[1];
        }
        return $returnVal;
    }

    //Heartbeat
    public function pulse()
    {
        $currentTime = time();
        $this->redis->set($this->prefix . $this->keyName . "heartbeat", $currentTime);
        return $currentTime;
    }

    public function getLastPulse()
    {
        return $this->redis->get($this->prefix . $this->keyName . "heartbeat");
    }

    public function getLifeStatus($acceptDiff = 3600)
    {
        $lifeStatus = $this->getLifeTime();
        $lastBeat = $this->redis->get($this->prefix . $this->keyName . "heartbeat");
        $return['diffSec'] = $lastBeat - $lifeStatus['startTime'];
        $lifeStatus['lastPulse'] = $lastBeat;
        $return['lifeDetail'] = $lifeStatus;
        if ($return['diffSec'] <= $acceptDiff && $return['diffSec'] > 0) {
            $return['lifeStatus'] = "alive";
        } else {
            if (!isset($lifeStatus['endTime'])) {
                $return['lifeStatus'] = "die";
            } else {
                $return['lifeStatus'] = "process_finish";
            }

        }
        return $return;
    }

    //Heartbeat Worker
    public function pulseWorker()
    {
        $startTime = time();
        return $this->redis->set($this->prefix . $this->keyName . "heartbeat_worker:" . getmypid(), $startTime);
    }

    public function workerEnd()
    {
        return $this->redis->delete($this->prefix . $this->keyName . "heartbeat_worker:" . getmypid());
    }

    public function checkWorkerStatus($acceptDiff = 3600)
    {
        $now = time();
        $keys = $this->redis->keys($this->prefix . $this->keyName . "heartbeat_worker:*");
        foreach ($keys as $key => $value) {
            $diff = $now - $this->redis->get($value);
            $return[$value]['lastPulse'] = ($diff <= $acceptDiff) ? "alive" : "die";
        }
        return $return;
    }

    public function deletePulseWorker()
    {
        $keys = $this->redis->keys($this->prefix . $this->keyName . "heartbeat_worker:*");
        foreach ($keys as $key => $value) {
            $del = $this->redis->delete($value);
        }
        return true;
    }

    //Count data
    public function setCounterValue($key, $value)
    {
        return $this->redis->set($this->prefix . $this->keyName . "counter:" . $key, $value);
    }

    public function getCounterValue($key)
    {
        return $this->redis->get($this->prefix . $this->keyName . "counter:" . $key);
    }

    public function increaseCounterValue($key)
    {
        return $this->redis->incr($this->prefix . $this->keyName . "counter:" . $key);
    }

    public function decreaseCounterValue($key)
    {
        return $this->redis->decr($this->prefix . $this->keyName . "counter:" . $key);
    }

}
