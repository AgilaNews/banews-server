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
        $client_version = $this->get_request_param("client_version", "string");
        $resolution = $this->get_request_param("resolution", "string");
        $os = $this->get_request_param("os", "string");
        $os_version = $this->get_request_param("os", "version");
        $net = $this->get_request_param("os", "net");
        $isp = $this->get_request_param("isp", "string");
        $tz = $this->get_request_param("tz", "int");
        $lang = $this->get_request_param("lang", "string");
        $client_time = $this->get_request_param("os", "int");

        
    }

    public function ErrorAction() {
        $exception = $this->dispatcher->getParam(0);

        if ($exception) {
            $this->response->status_code = $exception->getStatusCode();
            echo $exception->getBody();
        } else {
            $this->response->status_code = 404;
        }

    }
}

