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
        } else {
            /*
            $cookies = new Cookies();
            $cookies->useEncryption(false);
            $cookies->set("session", UserSession::genSessionId($this->deviceToken));
            $this->di->set("cookies", $cookies);
            */
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

    protected function get_request_param($name, $type, $is_required=false) {
        if (isset($_REQUEST[$name])) {
            return $this->filter->sanitize($_REQUEST[$name], $type);
        }
        if ($is_required) {
            throw new HttpException(40000, "'$name' is not set");
        }
        return "";
    }
}
