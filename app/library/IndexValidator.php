<?php
/**
 * @file   IndexValidator.php
 * @author Gethin Zhang <zhangguanxing01@baidu.com>
 * @date   Tue Apr 12 21:47:18 2016
 * 
 * @brief  validators for index control
 * 
 * 
 */
use Phalcon\Validation\Validator\PresenceOf;
class IndexValidator extends BaseValidation {
    public function initialize(){
        $this->add('test', new PresenceOf ( array (
                                                  'message' => "test must set",
                                                  )));
    }
}
