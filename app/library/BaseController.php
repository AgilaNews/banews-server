<?php

use Phalcon\Mvc\Controller;
use Phalcon\Http\Response;
use Phalcon\Filter;

class BaseController extends Controller{
    public function onConstruct(){
        $this->deviceId = $this->request->getHeader('X-USER-D');
        $this->userSign = $this->request->getHeader('X-USER-A');
        
        $this->session = $this->request->getHeader("X-SESSION-ID");
        $this->logger = $this->di->get('logger');
        $this->logger->begin();

        $this->response = new Response();

        $this->filter = new Filter();
        $this->view->disable();
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

    protected function getUserBySign($sign) {
        $user_model = User::findFirst(array ("conditions" => "sign = ?1",
                                             "bind" => array (1 => $sign),
                                             /*
                                             "cache" => array (
                                                              "lifetime" => $this->config->cache->general_life_time,
                                                               "key" => $this->config->cache->keys->user
                                                               ),
                                             */

                                             ));
        if (!$user_model) {
            throw new HttpException(ERR_USER_NON_EXISTS,
                                    "user $sign not exists");
        }
        
        return $user_model;
    }

    protected function getUserById($id) {
        $user_model = User::findFirst(array ("conditions" => "id = ?1",
                                             "bind" => array (1 => $id),
                                             /*
                                             "cache" => array (
                                                               "lifetime" => $this->config->cache->general_life_time,
                                                               "key" => $this->config->cache->keys->user
                                                               ),*/
                                             ));
        if (!$user_model) {
            throw new HttpException(ERR_USER_NON_EXISTS,
                                    "user $id bit exists");
        }
        
        return $user_model;
    }
}
