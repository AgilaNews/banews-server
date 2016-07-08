<?php
/**
 * @file   IndexController.php
 * @author Gethin Zhang <zhangguanxing01@baidu.com>
 * @date   Tue Apr 12 21:38:05 2016
 * 
 * @brief  index actions, provides main settings from clients
 *         this function will records main client informations to databases and log 
 * 
 * 
 */
class IndexController extends BaseController {
    public function IndexAction(){
        $kw = array();
        $kw["vendor"] = $this->get_request_param("vendor", "string");
        $kw["mmc"] = $this->get_request_param("mmc", "int");
        $kw["clientVersion"] = $this->get_request_param("client_version", "string", true);
        $kw["os"] = $this->get_request_param("os", "string");
        $kw["osVersion"] = $this->get_request_param("os_version", "string");
        $kw["net"] = $this->get_request_param("net", "string");
        $kw["isp"] = $this->get_request_param("isp", "string");
        $kw["tz"] = $this->get_request_param("tz", "int");
        $kw["lng"] = $this->get_request_param("lng", "float");
        $kw["lat"] = $this->get_request_param("lat", "float");
        $kw["lang"] = $this->get_request_param("lang", "string");
        $kw["clientTime"] = $this->get_request_param("os", "int");
        $kw["device_id"] = $this->deviceId;

        if (empty($kw["clientVersion"])) {
            throw new HttpException(ERR_CLIENT_VERSION_NOT_FOUND, 'client version not found');    
        }
        $client_version = $kw["clientVersion"];

        $vm = Version::getByClientVersion($client_version);
        if (!$vm) {
            throw new HttpException(ERR_CLIENT_VERSION_NOT_FOUND,
                                    "client version not supported");
        }
        $ret = array(
                "interfaces" => array(
                    "home" => sprintf($this->config->entries->home, $vm->server_version),
                    "mon" => sprintf($this->config->entries->mon, $vm->server_version),
                    "log" => sprintf($this->config->entries->log, $vm->server_version),
                    "referrer" => $this->config->entries->referrer
                     ),
                "updates" => array(
                    "avc" => ANDROID_VERSION_CODE, 
                    "min_version" => MIN_VERSION,
                    "new_version" => NEW_VERSION,
                    "update_url"=> UPDATE_URL,
                      ),
                "categories" => array(),
                );

        $channels = Channel::getAllVisible();
        $i = 0;
        foreach ($channels as $channel) {
            if (version_compare(substr($client_version, 1), $channel->publish_latest_version, "<")) {
                continue;
            }

            $ret["categories"][] = array(
                "id" => $channel->channel_id,
                "name" => $channel->name,
                "fixed" => $channel->fixed,
                "index" => $i++,
            );
        } 

        $log = "[ColdSetting]";
        foreach ($kw as $k=>$v) {
            $log .= "[$k:$v]";
        }

        $this->logger->notice($log);
        $this->logEvent(EVENT_NEWS_COLDSETTING, $kw);
        $this->setJsonResponse($ret);
        return $this->response;
    }

    public function ErrorAction() {
        $exception = $this->dispatcher->getParam(0);
        $this->response->setHeader("Content-Type", "application/json; charset=UTF-8");

        if ($exception instanceof HttpException) {
            $this->response->setStatusCode($exception->getStatusCode());
            $this->response->setContent($exception->getBody());
            $this->logger->warning("[HttpError][code:" . $exception->getStatusCode() . "]:" . $exception->getBody());
        } else {
            $this->logger->warning("[InternalError]: " . $exception->getTraceAsString());
            $this->response->setStatusCode(500);

            if (BA_DEBUG) {
                if ($exception) {
                    $this->response->setContent($exception);
                }
            }
        }

        return $this->response;
    }
}
