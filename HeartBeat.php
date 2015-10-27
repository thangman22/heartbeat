<?php

require_once __DIR__ . "/redis_config.php";

if (!class_exists('Redis')) {
    dl('redis.so');
}

class HeartBeat
{

    public $keyName;

    public $redis;

    public $prefix = "zocialheartbeat:";

    public $workerPrefix;

    public function __construct($keyName = "")
    {

        include "redis_config.php";

        $this->workerPrefix = $this->prefix;

        $this->prefix .= "monitor:";

        $this->keyName = $keyName . ":";

        if (!class_exists('Redis')) {
            throw new Exception("Redis extension is not found.", 1);
        } else {
            $this->redis = new Redis;
            $this->redis->pconnect($redisServer, $redisPort);
            $this->redis->select($redisDatabase);
        }
    }

    //List

    //Type it can be *, lifetime, heartbeat, counter
    public function listHeartBeat($type = "*")
    {
        if (!in_array($type, array("*", "lifetime", "heartbeat", "counter"))) {
            throw new Exception("Heartbeat type is invalid", 1);
        }

        if ($type != "*" && $type != "counter") {
            $type = "*:" . $type;
        } elseif ($type == "counter") {
            $type = "*:" . $type . ":*";
        }

        $return = array();
        $keys = $this->redis->keys($this->prefix . $type);
        foreach ($keys as $key => $value) {
            $explode_key = explode(":", $value);
            if (strpos($explode_key[3], "lifetime") !== false) {
                $return['lifetime'][$value] = $this->redis->get($value);
            } elseif (strpos($explode_key[3], "heartbeat") !== false) {
                $return['heartbeat'][$value] = $this->redis->get($value);
            } elseif (strpos($explode_key[3], "counter") !== false) {
                $return['counter'][$value] = $this->redis->get($value);
            }

        }

        return $return;

    }

    public function listCounter($keyName)
    {
        $return = array();
        $keys = $this->redis->keys($this->prefix . $this->addColon($keyName) . "counter:*");
        foreach ($keys as $key => $value) {

            $couter_name = explode(":", $value);
            $return[$couter_name[4]] = $this->redis->get($value);
        }

        return $return;

    }

    //Lifetime
    public function processStart()
    {
        $startTime = time();
        $this->pulse();
        $this->redis->set($this->prefix . $this->keyName . "lifetime", $startTime);
        $this->setPid();
        return date("r", $startTime);
    }

    public function processEnd($delete = false)
    {
        $endTime = time();
        $this->redis->append($this->prefix . $this->keyName . "lifetime", "|" . $endTime);
        if ($delete == true) {
            $this->redis->delete($this->prefix . $this->keyName . "lifetime");
        }
        return date("r", $endTime);
    }

    public function getLifeTime($keyName)
    {
        $returnVal = array();
        $lifeTime = $this->redis->get($this->prefix . $this->addColon($keyName) . "lifetime");
        $explodeLifeTime = explode("|", $lifeTime);
        $returnVal['startTime'] = $explodeLifeTime[0];
        if (isset($explodeLifeTime[1])) {
            $returnVal['endTime'] = $explodeLifeTime[1];
        }
        return $returnVal;
    }

    public function deleteProcess($keyName)
    {
        $startTime = time();
        $this->pulse();
        $keys = $this->redis->keys($this->prefix . $this->addColon($keyName) . "*");
        foreach ($keys as $key => $value) {
            $this->redis->delete($value);
        }
    }

    //Heartbeat
    public function pulse()
    {
        $currentTime = time();
        $this->redis->set($this->prefix . $this->keyName . "heartbeat", $currentTime);
        return $currentTime;
    }

    public function getLastPulse($keyName)
    {
        return $this->redis->get($this->prefix . $this->addColon($keyName) . "heartbeat");
    }

    public function getLifeStatus($keyName, $acceptDiff = 3600)
    {
        $lifeStatus = $this->getLifeTime($keyName);
        $lastBeat = $this->redis->get($this->prefix . $this->addColon($keyName) . "heartbeat");
        $return['diffSec'] = time() - $lastBeat;
        if ($return['diffSec'] < 0) {
            $return['diffSec'] = 0;
        }
        $lifeStatus['lastPulse'] = $lastBeat;
        $return['lifeDetail'] = $lifeStatus;
        if ($return['diffSec'] <= $acceptDiff && $return['diffSec'] >= 0) {
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
        return $this->redis->set($this->prefix . $this->keyName . "heartbeat_worker:" . getmypid() . "|" . gethostname(), $startTime);
    }

    public function workerEnd()
    {
        return $this->redis->delete($this->prefix . $this->keyName . "heartbeat_worker:" . getmypid() . "|" . gethostname());
    }

    public function checkWorkerStatus($keyName, $acceptDiff = 3600)
    {
        $now = time();
        $return['count_die'] = 0;
        $return['count_worker'] = 0;
        $keys = $this->redis->keys($this->workerPrefix . "*:" . $this->addColon($keyName) . "heartbeat_worker:*");
        foreach ($keys as $key => $value) {
            $diff = $now - $this->redis->get($value);
            $explode_key = explode(":", $value);

            $status = ($diff <= $acceptDiff) ? "alive" : "die";
            $system_detail = explode("|", $explode_key[4]);

            $return['detail'][$system_detail[1]][$system_detail[0]]['status'] = $status;
            $return['detail'][$system_detail[1]][$system_detail[0]]['timeDiff'] = $diff;
            $return['count_worker']++;
            if ($status == "die") {
                $return['count_die']++;
            }
        }
        if (isset($return)) {
            return $return;
        } else {
            return false;
        }

    }

    public function deletePulseWorker($keyName)
    {
        $keys = $this->redis->keys($this->workerPrefix . "*:" . $this->addColon($keyName) . "heartbeat_worker:*");
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

    public function getCounterValue($keyName, $key)
    {
        return $this->redis->get($this->prefix . $this->addColon($keyName) . "counter:" . $key);
    }

    public function increaseCounterValue($key)
    {
        return $this->redis->incr($this->prefix . $this->keyName . "counter:" . $key);
    }

    public function decreaseCounterValue($key)
    {
        return $this->redis->decr($this->prefix . $this->keyName . "counter:" . $key);
    }

    //Get PID
    public function setPid()
    {
        return $this->redis->set($this->prefix . $this->keyName . "pid", getmypid() . "|" . gethostname());
    }

    public function getPid($keyName)
    {
        return $this->redis->get($this->prefix . $this->addColon($keyName) . "pid");
    }

    //Other
    public function clearHeartBeat()
    {
        $keys = $this->redis->keys($this->prefix . "*");
        foreach ($keys as $key => $value) {
            $this->redis->delete($value);
        }
        return true;
    }

    private function addColon($key)
    {
        return $key . ":";
    }

    public function extractKeyname($key)
    {
        $explode_key = explode(":", $key);
        if (strpos($key, ":lifetime") !== false) {
            return $explode_key[2];
        } elseif (strpos($key, "heartbeat:heartbeat") !== false) {
            return $explode_key[2];
        } elseif (strpos($key, ":counter:") !== false) {
            return array("keyname" => $explode_key[2], "counter" => $explode_key[4]);
        }
    }

    public function extractServerName($key)
    {
        $explode_key = explode(":", $key);
        return $explode_key[1];
    }

}
