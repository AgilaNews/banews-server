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
    public function __construct($code, $message) {
        if (is_array($message)) {
            $this->message = json_encode($message);
        } else {
            $this->message = $message;
        }
        $this->status_code = $this->get_http_code($code);
        $this->code = $code;
    }


    public function getStatusCode() {
        return $this->status_code;
    }

    public function getBody($format = "json") {
        return json_encode (array(
                                  "code" => $this->code,
                                  "message" => $this->message,
                                  ));
    }
    
    protected function get_http_code($code) {
        if (is_int($code)) {
            return (int) ($code / 100);
        } else {
            return 500;
        }
    }
};
