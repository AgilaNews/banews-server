<?php
/**
 * @file   HttpException.php
 * @author Gethin Zhang <zhangguanxing01@baidu.com>
 * @date   Tue Apr 12 22:02:27 2016
 * 
 * @brief  
 * 
 * 
 */
class HttpException extends Exception{
    public function __construct($code, $message, $extra = null) {
        if (is_array($message)) {
            $this->message = json_encode($message);
        } else {
            $this->message = $message;
        }
        $this->status_code = $this->get_http_code($code);
        $this->code = $code;
        $this->extra = $extra;
    }


    public function getStatusCode() {
        return $this->status_code;
    }

    public function getBody($format = "json") {
        $body = array(
                    "code" => $this->code,
                    "message" => $this->message,
                );

        if ($this->extra) {
            $body = array_merge($body, $this->extra); 
        }

        return json_encode($body);
    }

    public function getExtra() {
       return $this->extra; 
    }
    
    protected function get_http_code($code) {
        if (is_int($code)) {
            return (int) ($code / 100);
        } else {
            return 500;
        }
    }
};
