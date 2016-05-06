<?php

use Phalcon\Mvc\Controller;
use Phalcon\Http\Response;
use Phalcon\Filter;

class BaseController extends Controller{
    public function initialize(){
        $this->logger = $this->di->get('logger');
        $this->eventlogger = $this->di->get('eventlogger');
        $this->logger->begin();

        $this->response = new Response();

        $this->filter = new Filter();
        $this->view->disable();
    }
    
    public function onConstruct(){
        $this->deviceId = $this->request->getHeader('X-USER-D');
        $this->userSign = $this->request->getHeader('X-USER-A');
        
        $this->session = $this->request->getHeader("X-SESSION-ID");
    }

    public function __destruct(){
        $this->logger->commit();
    }

    protected function get_request_param($name, $type, $is_required=false) {
        if (isset($_REQUEST[$name])) {
            return $this->filter->sanitize($_REQUEST[$name], $type);
        }
        
        if ($is_required) {
            throw new HttpException(ERR_KEY_ERR, "'$name' is not set");
        }
        return "";
    }

    protected function get_or_default($table, $k, $type, $default) {
        if (array_key_exists($k, $table)) {
            return $this->filter->sanitize($table[$k], $type);
        } else {
            return $default;
        }
    }

    protected function get_or_fail($table, $k, $type) {
        if (array_key_exists($k, $table)) {
            return $this->filter->sanitize($table[$k], $type);
        } else {
            throw new HttpException(ERR_KEY_ERR, "'$k' is not set");
        }
    }

    protected function setJsonResponse($arr) {
        $this->response->setContent(json_encode($arr));
        $this->response->setHeader("Content-Type", "application/json; charset=UTF-8");
    }

    protected function logEvent($event_id, $param) {
        $msg = json_encode($param);

        if (!$this->eventlogger) {
            $this->logger->info("[EVENT] $msg");
            return;
        }

        $param["id"] = $event_id;
        $this->eventlogger->info($msg . "\n");
    }
}
