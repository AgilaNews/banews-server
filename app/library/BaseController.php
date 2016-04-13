<?php

use Phalcon\Mvc\Controller;
use Phalcon\Filter;

class BaseController extends Controller{
    public function onConstruct(){
        $this->deviceId = $this->request->getHeader('X-USER-D');
        $this->deviceToken = $this->request->getHeader('X-USER-T');
        $this->userId = $this->request->getHeader('X-USER-A');
        
        $this->sessionId = "default"; //TODO change this
        $this->cookies->useEncryption (false);
        if ($this->cookies->has('session')) {
            $this->sessionId = $this->cookies->get('session')->getValue();
        }
        $this->logger = $this->di->get('logger');
        $this->logger->begin();

        $this->filter = new Filter();
    }

    public function initialize(){

    }
	
    public function __deconstruct(){
        $this->logger->commit();
    }

    protected function get_request_param($name, $type) {
        return $this->filter->sanitize($_REQUEST[$name], $type);
    }
}
