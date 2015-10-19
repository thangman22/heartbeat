<?php
class HeartBeat
{

    public $keyName;

    public $redis;

    public $prefix = "zocialheartbeat:";

    public function __construct($redisServer = "198.27.69.122", $redisPort = "6385", $redisDatabase = 2, $keyName = "")
    {

        $this->workerPrefix = $this->prefix;

        $this->prefix .= gethostname() . ":";

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
        return date("r", $startTime);
    }

    public function processEnd()
    {
        $endTime = time();
        $this->redis->append($this->prefix . $this->keyName . "lifetime", "|" . $endTime);
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

    public function deleteProcess()
    {
        $startTime = time();
        $this->pulse();
        $keys = $this->redis->set($this->prefix . $this->addColon($this->keyName) . "*");
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
        return $this->redis->set($this->prefix . $this->keyName . "heartbeat_worker:" . getmypid(), $startTime);
    }

    public function workerEnd()
    {
        return $this->redis->delete($this->prefix . $this->keyName . "heartbeat_worker:" . getmypid());
    }

    public function checkWorkerStatus($keyName, $acceptDiff = 3600)
    {
        $now = time();
        $return['count_die'] = 0;
        $return['count_worker'] = 0;
        $keys = $this->redis->keys($this->workerPrefix ."*:". $this->addColon($keyName) . "heartbeat_worker:*");
        foreach ($keys as $key => $value) {
            $diff = $now - $this->redis->get($value);
            $explode_key = explode(":",$value);
            $status = ($diff <= $acceptDiff) ? "alive" : "die";
            $return['detail'][$explode_key[1]][$explode_key[4]]['status'] = $status;
            $return['detail'][$explode_key[1]][$explode_key[4]]['timeDiff'] = $diff;
            $return['count_worker']++;
            if($status == "die"){
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
        $keys = $this->redis->keys($this->workerPrefix ."*:". $this->addColon($keyName) . "heartbeat_worker:*");
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

    public function extractServerName($key){
        $explode_key = explode(":", $key);
        return $explode_key[1];
    }

}
