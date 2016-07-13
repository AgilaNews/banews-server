<?php

use Phalcon\Mvc\Controller;
use Phalcon\Http\Response;
use Phalcon\Filter;

define("DEVICE_LARGE", "xxhdpi");
define("DEVICE_MEDIUM", "xhdpi");
define("DEVICE_SMALL", "hdpi");

define("EVENT_NEWS_DETAIL", "020103");
define("EVENT_NEWS_LIST", "020104");
define("EVENT_NEWS_LIKE", "020204");
define("EVENT_NEWS_COLLECT", "020205");
define("EVENT_NEWS_COLDSETTING", "030101");
define("EVENT_NEWS_REFERRER", "040102");

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
        $this->session = $this->request->getHeader('X-SESSION');
        $this->ua = $this->request->getHeader('USER-AGENT');
        $this->client_ip = $this->request->getHeader('X-FORWARDED-FOR');
        $this->deviceModel = DEVICE_MEDIUM;
        $this->resolution_w = 720;
        $this->resolution_h = 1280;
        $this->dpi = 145;
        $this->net = $this->get_request_param("net", "string");
        $this->isp = $this->get_request_param("isp", "string");
        $this->tz = $this->get_request_param("tz", "string");
        $this->lng = $this->get_request_param("lng", "float");
        $this->lat = $this->get_request_param("lat", "float");
        $this->lang = $this->get_request_param("lang", "string");

        if ($this->density) {
            $ret = explode(";", $this->density);
            if (count($ret) == 3) {
                $res_ret = explode("x", $ret[0]);
                if (count($res_ret) != 2) {
                    $res_ret = explode('X', $ret[0]);
                }

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
    }

    public function __destruct() {
        $this->logger->info(sprintf("[di:%s][user:%s][density:%s][net:%s][isp:%s][tz:%s][gps:%sX%s][lang:%s]",
                                      $this->deviceId, $this->userSign, $this->density, $this->net, $this->isp, 
                                      $this->tz, $this->lat, $this->lng, $this->lang));
        $this->logger->info(sprintf("[cost:%sms]",
                                      round((microtime(true) - $this->_start_time) * 1000)));
        $this->logger->commit();
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
        $content = json_encode($arr);
        $this->response->setContent($content);
        $this->response->setHeader("Content-Length", strlen($content));
        $this->response->setHeader("Content-Type", "text/html");
        $this->response->setHeader("Cache-Control", "private, no-cache, no-store, must-revalidate, max-age=0");
        $this->response->setHeader("Pragma", "no-cache");
    }

    protected function logEvent($event_id, $param) {
        if (!$this->eventlogger) {
            return;
        }

        $param["event-id"] = $event_id;
        $param["session"] = $this->session;
        $param["density"] = $this->density;
        if ($this->userSign) {
            $param["uid"] = $this->userSign;
        }
        $param["did"] = $this->deviceId;
        $param["net"] = $this->net;
        $param["isp"] = $this->isp;
        $param["tz"] = $this->tz;
        $param["lng"] = $this->lng;
        $param["lat"] = $this->lat;
        $param["lang"] = $this->lang;
        $param["time"] = round(microtime(true) * 1000);
        $param["ua"] = $this->ua;
        $params["client-ip"] = $this->client_ip; 

        $this->eventlogger->info(json_encode($param));
    }
}
