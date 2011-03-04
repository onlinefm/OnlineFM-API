<?php

class MemcacheWrapper
{
    private static $_handler;
    private static $_keyPrefix;


    public static function create() {
        if (!self::$_handler) {
            $config = include(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'config.php');
            if (!$config['memcache']['use']) {
                return false;
            }
            self::$_keyPrefix = $config['memcache']['key_prefix'];
            $memcacheServers = $config['memcache']['servers'];
            $memcache = new Memcache();
            $memcacheConnect = true;
            foreach($memcacheServers as $memcacheServer) {
                if (!($memcache->addServer($memcacheServer['host'],
                                           $memcacheServer['port'])))
                    $memcacheConnect = false;
            }
            if ($memcacheConnect) {
                self::$_handler = $memcache;
            }
            else {
                self::$_handler = false;
            }
        }
        return self::$_handler;
    }

    public static function getValue($key, $lifetime, $getValueCallback) {
        if ($memcache = self::create()) {
            $res = null;
            $fullKey = self::$_keyPrefix . $key;
            if (False === ($res = $memcache->get($fullKey))) {
                //if no data in cache
                //retrieve from DB...
                $res = call_user_func($getValueCallback);
                //...and put in cache
                $memcache->set($fullKey, $res, null, $lifetime);
            }
            return $res;
        }
        else {
            return call_user_func($getValueCallback);
        }
    }

    public static function getValueFromCache($key) {
        $res = null;
        if ($memcache = self::create()) {
            $fullKey = self::$_keyPrefix . $key;
            $res = $memcache->get($fullKey);
        }
        return $res;
    }
    public static function setValueToCache($key, $value, $lifetime) {
        if ($memcache = self::create()) {
            $fullKey = self::$_keyPrefix . $key;
            return $memcache->set($fullKey, $value, null, $lifetime);
        }
    }

}

