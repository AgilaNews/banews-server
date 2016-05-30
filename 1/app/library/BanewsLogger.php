<?php
/**
 * @file   Logger.php
 * @author Gethin Zhang <zhangguanxing01@baidu.com>
 * @date   Wed Apr 13 12:03:33 2016
 * 
 * @brief  
 * 
 * 
 */
use Phalcon\Logger;
use Phalcon\Logger\Adapter;
use Phalcon\Logger\Adapter\File as FA;


class BanewsLogger extends FA {
    public function __construct($name, $option = null){
        $mode = "ab";
        $this->wf_handler = fopen($name . ".wf", $mode);
        if (!$this->wf_handler) {
            throw new Exception("optn $name.wf error");
        }
        $this->handler = fopen($name, $mode);
        if (!$this->handler) {
            throw new Exception("open $name error");
        }
        $this->path = $name;
    }

    public function commit(){
        $wf_msg = "";
        $msg = "";
        $time = time();

        foreach ($this->_queue as $record) {
            $type = $record->gettype();
            $message = $record->getmessage();
            if ($type <= Logger::WARNING) {
                $wf_msg .= $message;
            } else {
                $msg .= $message;
            }
        }

        if ($wf_msg) {
            fwrite($this->wf_handler,
                   $this->getFormatter()->format($wf_msg, Logger::WARNING, $time));
        }

        if ($msg) {
            fwrite($this->handler,
                   $this->getFormatter()->format($msg, Logger::NOTICE, $time));
        }
    }

    public function close(){
        fclose($this->handler);
        fclose($this->wf_handler);
    }

    public function __wakeup(){
        $mode = "ab";
        $this->handler = fopen($name, $mode);
        $this->wf_handler = fopen($name . ".wf", $mode);
    }
}

