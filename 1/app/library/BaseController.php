<?php

use Phalcon\Mvc\Controller;
use Phalcon\Http\Response;
use Phalcon\Filter;

define("DEVICE_LARGE", "xxhdpi");
define("DEVICE_MEDIUM", "xhdpi");
define("DEVICE_SMALL", "hdpi");

define("EVENT_NEWS_DETAIL", "020103");
define("EVENT_NEWS_LIST", "020103");
define("EVENT_NEWS_LIKE", "020204");
define("EVENT_NEWS_COLLECT", "020305");

class BaseController extends Controller{
    public function initialize(){
        $this->logger = $this->di->get('logger');
        $this->eventlogger = $this->di->get('eventlogger');
        $this->logger->begin();

        $this->response = new Response();
        $this->filter = new Filter();
        $this->_start_time = microtime(true);
        $this->logger->notice(sprintf("[%s:%s]",
                                      $_SERVER["REQUEST_METHOD"],
                                      $_SERVER["REQUEST_URI"]));
        $this->view->disable();
    }
    
    public function onConstruct(){
        $this->deviceId = $this->request->getHeader('X-USER-D');
        $this->userSign = $this->request->getHeader('X-USER-A');
        $this->density = $this->request->getHeader('X-DENSITY');
        $this->deviceModel = DEVICE_MEDIUM;
        $this->resolution_w = 720;
        $this->resolution_h = 1280;
        $this->dpi = 145;

        if ($this->density) {
            $ret = explode(";", $this->density);
            if (count($ret) == 3) {
                $res_ret = explode("x", $ret[0]) || explode("X", $ret[0]);
                if (count($res_ret) == 2) {
                    $this->resolution_w = $res_ret[0];
                    $this->resolution_h = $res_ret[1];
                }

                $this->dpi = $ret[1];
                switch ($ret[1]) {
                case 'l':
                    $this->deviceModel = DEVICE_LARGE;
                    break;
                case 'm':
                    $this->deviceModel = DEVICE_MEDIUM;
                    break;
                case 's':
                    $this->deviceModel = DEVICE_SMALL;
                    break;
                default:
                    $this->deviceMxodel = DEVICE_MEDIUM;
                }
            }
        }
        $this->session = $this->request->getHeader("X-SESSION-ID");
    }

    protected function get_request_param($name, $type, $is_required=false, $default="") {
        if (isset($_REQUEST[$name])) {
            return $this->filter->sanitize($_REQUEST[$name], $type);
        }
        
        if ($is_required) {
            throw new HttpException(ERR_KEY, "'$name' is not set");
        }
        return $default;
    }

    protected function get_or_default($table, $k, $type, $default = null) {
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
            throw new HttpException(ERR_KEY, "'$k' is not set");
        }
    }

    protected function setJsonResponse($arr) {
        $this->response->setContent(json_encode($arr));
        $this->response->setHeader("Content-Type", "application/json; charset=UTF-8");
        $this->logger->notice(sprintf("[cost:%sms]",
                                      round(microtime(true) - $this->_start_time, 6) * 1000));
    }

    protected function logEvent($event_id, $param) {
        $msg = json_encode($param);

        if (!$this->eventlogger) {
            $this->logger->info("[EVENT] $msg");
            return;
        }

        $param["id"] = $event_id;
        $param["session"] = $this->session;
        $param["device_model"] = $this->deviceModel;
        $param["r_w"] = $this->resolution_w;
        $param["r_h"] = $this->resolution_h;
        if ($this->userSign) {
            $param["user"] = $this->userSign;
        }
        $param["deviceId"] = $this->deviceId;
        $this->eventlogger->info($msg . "\n");
    }
}
