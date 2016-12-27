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
    public function __construct($context = array()){
        $this->context = $context;
        $this->empty = false;
    }

    abstract function render();

    function isEmpty() {
        return $this->empty;
    }
}
