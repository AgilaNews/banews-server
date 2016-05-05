<?php

use Phalcon\Logger;
use Phalcon\Logger\Adapter;
use Phalcon\Logger\AdapterInterface;

class EventLogger extends Adapter implements AdapterInterface {
    protected $handler;

    protected $category;

    public function __construct($name, $category, $timeout = 1) {
        $this->handler = stream_socket_client($name, $errno, $errstr, $timeout * 1000);
        if (!$this->handler) {
            throw new Exception("connect $name error $errno: $errstr");
        }
        $this->category = $category;
    }

    public function getFormatter(){
        
    }

    protected function pack_int32_be($num) {
        if (pack('L', 1) === pack('N', 1)) {
            return pack('l', $num);
        }
        return strrev(pack('l', $num)); 
    }

    
    protected function pack_int32_le($num) {
        if (pack('L', 1) === pack('N', 1)) {
            return strrev(pack('l', $num)); 
        }
        return pack('l', $num); 
    }

    
    protected function pack_scribe_pack($message) {
        $content = hex2bin("0b0001") .
            $this->pack_int32_be(strlen($this->category)) .
            $this->category .
            hex2bin("0b0002") .
            $this->pack_int32_be(strlen($message)) .
            $message .
            hex2bin("00");
        
        $seqid = rand();
        $packet = $this->pack_int32_be(strlen($content) + 24) .
            hex2bin("80010001") .
            hex2bin("00000003") .
            "Log" .
            $this->pack_int32_be($seqid) .
            hex2bin("0f00010c") .
            $this->pack_int32_be(count($message)) .
            $content .
            hex2bin("00");

        $fp = fopen("/home/zhangguanxing/banews-server/app/logs/scribe.dump", "w+");
        fwrite($fp, $packet);
        fclose($fp);
        return $packet;
    }

    
    public function logInternal($message, $type, $time, $context) {
        fwrite($this->handler, $this->pack_scribe_pack($message));
    }

    public function close() {
        fclose($this->handler);
    }
}