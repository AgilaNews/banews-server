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
        $kw["os"] = $this->get_request_param("os", "string");
        $kw["osVersion"] = $this->get_request_param("os_version", "string");
        $kw["net"] = $this->get_request_param("net", "string");
        $kw["isp"] = $this->get_request_param("isp", "string");
        $kw["tz"] = $this->get_request_param("tz", "int");
        $kw["lng"] = $this->get_request_param("lng", "float");
        $kw["lat"] = $this->get_request_param("lat", "float");
        $kw["lang"] = $this->get_request_param("lang", "string");
        $kw["clientTime"] = $this->get_request_param("client_time", "int");
        $kw["device_id"] = $this->deviceId;

        if (!$this->client_version) {
            throw new HttpException(ERR_CLIENT_VERSION_NOT_FOUND, 'client version not found');    
        }
       
        $ret = Version::getAllUseable();
        if (!$ret) {
            throw new HttpException(ERR_INTERNAL_DB, "internal error");
        }

        $models = array();

        foreach ($ret as $model) {
            $models []= $model;
        }

        usort($models, function($a, $b) {
            return version_compare($a->client_version, $b->client_version);
        });

        if (count($models) == 0) {
            throw new HttpException(ERR_INTERNAL_DB, "internal error");
        }

        $cur_model = $min_model = $new_model = null;

        /*
           this section is relative tricky
           we have a sequence of version, some version is published for ios while others published for android
           some version number may skip serveral version 
            | for example, android develops v1.1.0 v1.1.1 v1.1.2, v1.1.1 is bug fix version, so v1.1.1 is not useful for ios
            | so ios do not have v1.1.1 version

           We defined four states by two bits to identify this situation
           0 not published for all
           1 only published for android
           2 only published for ios
           1 | 2 published for all platforms

           so we make sure that versions is sorted above, then just iterate from oldest version to newest version
           we can get three version numbers:
            1. min_version, minmal usable version, the first one we check is usable is certain platform
            2. new_version, last usable version, we keep track of usable version util nothing more saw
            3. cur_version, client version number set by client, some actions taken by server is based on client version
        */
        for ($i = 0; $i < count($models); $i++) {
            $model = $models[$i];
            if ($this->os == "ios") {
                if ($model->status & IOS_PUBLISHED) {
                    $new_model = $model;

                    if (!$min_model) {
                        $min_model = $model;
                    }
                    if ($model->client_version == $this->client_version) {
                        $cur_model = $model;
                    }
                }
            }

            if ($this->os == "android") {
                if ($model->status & ANDROID_PUBLISHED) {
                    $new_model = $model;

                    if (!$min_model) {
                        $min_model = $model;
                    }
                    if ($model->client_version == $this->client_version) {
                        $cur_model = $model;
                    }
                }
            }
        }

        if (!$cur_model) {
            throw new HttpException(ERR_CLIENT_VERSION_NOT_FOUND,
                                    "client version not supported");
        }
     
        $ret = array (
            "interfaces" => array(
                "home" => sprintf($this->config->entries->home, $cur_model->server_version),
                "mon" => sprintf($this->config->entries->mon, $cur_model->server_version),
                "log" => sprintf($this->config->entries->log, $cur_model->server_version),
                "referrer" => $this->config->entries->referrer,
            ),
            "updates" => array(
                "min_version" => "v" . $min_model->client_version,
                "new_version" => "v" . $new_model->client_version,
            ),
        );
        
        if ($this->os == "ios") {
            $ret["updates"]["update_url"] = $new_model->ios_update_url;
        } else {
            $ret["updates"]["update_url"] = $new_model->update_url;
            $ret["updates"]["avc"] = $new_model->android_version_code;
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
