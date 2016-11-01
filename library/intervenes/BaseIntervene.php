<?php
/**
 * @file   BaseIntervenes.php
 * @author Gethin Zhang <zgxcassar@gmail.com>
 * @date   Mon Oct 31 18:44:03 2016
 * 
 * @brief  
 * 
 * 
 */
abstract class BaseIntervene {
    public function __construct($context){
        $this->context = $context;
    }

    abstract function render();
}
