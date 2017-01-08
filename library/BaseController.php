<?php

use Phalcon\Mvc\Controller;
use Phalcon\Http\Response;
use Phalcon\Filter;

define("DEVICE_LARGE", "xxhdpi");
define("DEVICE_MEDIUM", "xhdpi");
define("DEVICE_SMALL", "hdpi");

define("EVENT_NEWS_DETAIL", "020103");
define("EVENT_NEWS_LIST", "020104");
define("EVENT_NEWS_RECOMMEND", "020108");
define("EVENT_NEWS_LIKE", "020204");
define("EVENT_NEWS_COLLECT", "020205");
define("EVENT_NEWS_COMMENT", "020207");
define("EVENT_NEWS_COMMENT_LIKE", "020208");
define("EVENT_TOPIC_DETAIL", "021701");
define("EVENT_TOPIC_INTERVENE", "021702");
define("EVENT_NEWS_COLDSETTING", "030101");
define("EVENT_NEWS_REFERRER", "040102");
define("EVENT_SEARCH_LIST", "070101");

class BaseController extends Controller{
    public function initialize(){
        $this->logger = $this->di->get('logger');
        $this->eventlogger = $this->di->get('eventlogger');
        $this->featureLogger = $this->di->get('featureLogger');

        $this->logger->begin();
        $this->response = new Response();
        $this->filter = new Filter();
        $this->start_time = microtime(true);
        $this->logger->info(sprintf("[%s:%s]",
                                      $_SERVER["REQUEST_METHOD"],
                                      $_SERVER["REQUEST_URI"]));
        $this->view->disable();
    }
    
    public function onConstruct(){
        $this->logid = mt_rand();
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
        $this->client_version = $this->get_request_param("client_version", "string", false, "1.0.0");
        // we just need version code after 'v' character
        if ($this->client_version && strcasecmp(substr($this->client_version, 0, 1), "v") == 0) {
            $this->client_version = substr($this->client_version, 1);
        }
        
        $this->build = $this->get_request_param("build", "int", false, BUILD_MAIN);
        $this->os = $this->get_request_param("os", "string", false, "android");
        $this->os_version  = $this->get_request_param("os_version", "string", false, "");

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

        $this->initAbFlag();
    }

    public function check_user_and_device(){
        if (!$this->userSign) {
            throw new HttpException(ERR_NOT_AUTH, "usersign not set");
        }
        if (!$this->deviceId) {
            throw new HttpException(ERR_DEVICE_NON_EXISTS, "device-id not found");
        }
       
        $user_model = User::getBySign($this->userSign);
        if (!$user_model) {
            throw new HttpException(ERR_USER_NON_EXISTS,
                                    "user non exists");
        }
    }

    public function beforeExecuteRoute($dispatcher) {
        if ($this->request->isOptions()) {
            $this->setResponseHeaders();
            return false;
        }

        return true;
    }

    public function afterExecuteRoute($dispatcher) {
        $this->logger->info(sprintf("[di:%s][user:%s][density:%s][net:%s][isp:%s][tz:%s][gps:%sX%s][lang:%s][abflag:%s][logid:%s]",
                                    $this->deviceId, $this->userSign, $this->density, $this->net, $this->isp, 
                                    $this->tz, $this->lat, $this->lng, $this->lang, json_encode($this->abflags), $this->logid));
        $this->logger->info(sprintf("[cost:%sms]",
                                      round((microtime(true) - $this->start_time) * 1000)));
        $this->logger->commit();
    }
    
    protected function get_request_param($name, $type, $is_required=false, $default="") {
        if (isset($_REQUEST[$name])) {
            return $_REQUEST[$name];
        }
        
        if ($is_required) {
            throw new HttpException(ERR_KEY, "'$name' is not set");
        }
        return $default;
    }

    protected function get_or_default($table, $k, $type, $default = null) {
        if (array_key_exists($k, $table)) {
            return $table[$k];
        } else {
            return $default;
        }
    }

    protected function get_or_fail($table, $k, $type) {
        if (array_key_exists($k, $table)) {
            return $table[$k];
        } else {
            throw new HttpException(ERR_KEY, "'$k' is not set");
        }
    }

    protected function setResponseHeaders(){
        $this->response->setHeader("Content-Type", "application/ph");
        $this->response->setHeader("Cache-Control", "private, no-cache, no-store, must-revalidate, max-age=0");
        $this->response->setHeader("Pragma", "no-cache");
        $this->response->setHeader("ACCESS-CONTROL-ALLOW-ORIGIN", "*");
        $this->response->setHeader("ACCESS-CONTROL-ALLOW-METHODS", "GET,OPTIONS");
        $this->response->setHeader("ACCESS-CONTROL-ALLOW-HEADERS", "X-USER-D,X-USER-A,AUTHORIZATION,DENSITY,X-SESSION");
    }

    protected function setJsonResponse($arr, $options = 0) {
        $content = json_encode($arr, $options);
        $this->response->setHeader("Content-Length", strlen($content));
        $this->response->setContent($content);
        $this->setResponseHeaders();
    }

    protected function logFeature($dispatchId, $param) {
        if (!$this->featureLogger) {
            return;
        }

        $param['dispatchId'] = $dispatchId; 
        $param["session"] = $this->session;
        if ($this->userSign) {
            $param["uid"] = $this->userSign;
        }
        $param["did"] = $this->deviceId;
        $param["net"] = $this->net;
        $param["lng"] = $this->lng;
        $param["lat"] = $this->lat;
        $param["time"] = round(microtime(true) * 1000);
        $param["abflag"] = $this->abflags; 
        $this->featureLogger->info(json_encode($param)); 
    }

    protected function logEvent($event_id, $param) {
        if (!$this->eventlogger) {
            return;
        }

        $param["event-id"] = $event_id;
        $param["session"] = $this->session;
        $param["ua"] = $this->ua;
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
        $param["client-ip"] = $this->client_ip; 
        $param["client-version"] = $this->client_version;
        $param["os"] = $this->os;
        $param["os-version"] = $this->os_version;
        $param["build"] = $this->build;
        $param["abflag"] = $this->abflags;
        $this->eventlogger->info(json_encode($param));
    }

    private function initAbFlag() {
        $ctx = RequestContext::GetCtxFromController($this);
        
        $service = $this->di->get("abtest");
        $this->abflags = $service->requestFlags($this->config->abtest->product_key, $ctx);
    }
}
