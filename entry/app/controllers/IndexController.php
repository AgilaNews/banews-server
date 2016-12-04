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
        $kw["clientTime"] = $this->get_request_param("client_time", "int");

        $kw["os"] = $this->os;
        $kw["osVersion"] = $this->os_version;
        $kw["net"] = $this->net;
        $kw["isp"] = $this->isp;
        $kw["tz"] = $this->tz;
        $kw["lng"] = $this->lng;
        $kw["lat"] = $this->lat;
        $kw["lang"] = $this->lang;
        $kw["device_id"] = $this->deviceId;

        if (!$this->client_version) {
            throw new HttpException(ERR_CLIENT_VERSION_NOT_FOUND, 'client version not found');    
        }

        $update_info = Version::getUpdateInfo($this->client_version, $this->os, $this->build);
        if (!$update_info) {
            throw new HttpException(ERR_INTERNAL_DB,
                                    "internal error");
        }
        if (!$update_info["models"]["cur"]) {
            throw new HttpException(ERR_CLIENT_VERSION_NOT_FOUND, 'client version not supported');    
        }
        
        $cur_model = $update_info["models"]["cur"];
        unset($update_info["models"]);

        $ret = array (
            "interfaces" => array(
                "home" => sprintf($this->config->entries->home, $cur_model->server_version),
                "mon" => sprintf($this->config->entries->mon, $cur_model->server_version),
                "log" => sprintf($this->config->entries->log, $cur_model->server_version),
                "referrer" => $this->config->entries->referrer,
            ),
            "updates" => $update_info,
            "ad" => array(
                "preload" => AD_PRELOAD,
                "expire" => AD_EXPIRE,
            ),
        );

        if (Features::Enabled(Features::LOG_V3_FEATURE, $this->client_version, $this->os)) {
            "log" => sprintf($this->config->entries->log, 3),
        }
        
        if ($cur_model->server_version == 1) {
            $ret["categories"] = array();

            $channels = Channel::getAllVisible($this->client_version);
            $i = 0;
            foreach ($channels as $channel) {
                if (version_compare($this->client_version, $channel->publish_latest_version, "<")) {
                    continue;
                }
                
                $ret["categories"][] = array(
                                         "id" => $channel->channel_id,
                                         "name" => $channel->name,
                                         "fixed" => $channel->fixed,
                                         "index" => $i++,
                                             );
            }
        } else {
            $vm = ChannelDispatch::getNewestVersion();
            if (!$vm) {
                throw new HttpException(ERR_INTERNAL_DB, "internal error");
            }

            $ret["channel_version"] = $vm;

            $newest_package = Package::getNewestVersion();
            $ret["package_version"] = $newest_package->version;
        }

        $log = "[ColdSetting]";
        foreach ($kw as $k=>$v) {
            $log .= "[$k:$v]";
        }

        $this->logger->info($log);
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
