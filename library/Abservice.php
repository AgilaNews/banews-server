<?php
/**
 * @file   Abservice.php
 * @author Gethin Zhang <zgxcassar@gmail.com>
 * @date   Wed Nov  9 23:22:37 2016
 * 
 * @brief  
 * 
 * 
 */
use Phalcon\DI;

class Abservice {
    public function Abservice($client){
        $this->client = $client;
        $this->abflags = array();
        $this->logger = DI::getDefault()->get('logger');
    }

    public function requestFlags($product, $ctx){
        if (!$this->client) {
            $this->logger->warning("connect abtest service error");
            return array();
        }
        
        $config = DI::getDefault()->get('config');
        $req = new iface\GetExperimentGroupRequest();
        $req->setProduct($product);
        $req->setContext($ctx);

        list($resp, $status) = $this->client->GetExperimentGroup($req, array(),
                                                                 array(
                                                                       "timeout" => $config->abtest->call_timeout)
                                                                 )->wait();

        if ($status->code != 0) {
            $this->logger->warning("get abflag error:" . $status->code . ":" . json_encode($status->details, true));
            $this->abflags = array();
        } else {
            $this->abflags = array();
            foreach ($resp->getGroupsList() as $group) {
                $this->abflags[$group->key] = $group->value;
            }
        }

        return $this->abflags;
    }

    public function getTag($experiment) {
        if (array_key_exists($experiment, $this->abflags)) {
            return $this->abflags[$experiment];
        }

        return "unknown";
    }
    
}
