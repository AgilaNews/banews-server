<?php
/**
 * @file   BaseValidation.php
 * @author Gethin Zhang <zhangguanxing01@baidu.com>
 * @date   Tue Apr 12 21:40:48 2016
 * 
 * @brief  
 * 
 * 
 */
use Phalcon\Validation;
use Phalcon\Validation\Validator\PresenceOf;

class BaseValidation extends Validation {
    public function afterValidation($data, $entity, $messages){
        if (count($messages)) {
            throw new HttpException(400, $messages[0]);
        }
    }
}

