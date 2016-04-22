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
        $validator = new IndexValidator();
        $validator->validate($_GET);

        $vendor = $this->get_request_param("vendor", "string");
        $mmc = $this->get_request_param("mmc", "int");
        $clientVersion = $this->get_request_param("client_version", "string", true);
        $resolution = $this->get_request_param("r_w", "string");
        $resolution = $this->get_request_param("r_h", "string");
        $os = $this->get_request_param("os", "string");
        $osVersion = $this->get_request_param("os_version", "string");
        $net = $this->get_request_param("net", "string");
        $isp = $this->get_request_param("isp", "string");
        $tz = $this->get_request_param("tz", "int");
        $lng = $this->get_request_param("lng", "float");
        $lat = $this->get_request_param("lat", "float");
        $lang = $this->get_request_param("lang", "string");
        $clientTime = $this->get_request_param("os", "int");

        if (empty($clientVersion)) {
            throw new HttpException(ERR_CLIENT_VERSION_NOT_FOUND, 'client version not found');    
        }

        $vm = VersionModel::find(array(
                  "conditions" => "client_version = ?1",
                  "bind" => array(1 => $clientVersion),
                  "cache" => array(
                                   "lifetime" => 1,
                                   "key" => $this->config->cache->keys->version,
                                   ),
                   ));

        if (count($vm) == 0) {
            throw new HttpException(ERR_CLIENT_VERSION_NOT_FOUND,
                                    "client version not supoprted");
        }

        $vm = $vm[0];
        $ret = array(
                "interfaces" => array(
                    "home" => sprintf($this->config->entries->home, $vm->server_version),
                    "mon" => sprintf($this->config->entries->mon, $vm->server_version),
                    "log" => sprintf($this->config->entries->log, $vm->server_version),
                     ),
                "updates" => array(
                    "min_version" => MIN_VERSION,
                    "new_version" => NEW_VERSION,
                    "update_url"=> UPDATE_URL,
                      ),
                );
        

        echo json_encode($ret);
    }

    public function ErrorAction() {
        $exception = $this->dispatcher->getParam(0);

        if ($exception instanceof HttpException) {
            $this->response->setStatusCode($exception->getStatusCode());
            echo $exception->getBody();
        } else {
            $this->response->setStatusCode(500);

            if (BA_DEBUG) {
                echo "ERRROR!!!!!!\n";
                if ($exception) {
                    echo $exception;
                }
            }
        }

    }
}
