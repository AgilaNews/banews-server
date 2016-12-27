<?php
/**
 * @file   SphinxService.php
 * @author Gethin Zhang <zgxcassar@gmail.com>
 * @date   Fri Dec 23 14:18:59 2016
 * 
 * @brief  
 * 
 * 
 */
use Phalcon\DI;

class SphinxService {
    public function SphinxService($client) {
        $this->client = $client;
        $this->logger = DI::getDefault()->get('logger');
    }

    public function select($ctx, $channel_id, $prefer, $pn = 10, $start = 0) {
        $config = DI::getDefault()->get('config');
        $req = new iface\SelectRequest();
        $req->setSuiteName($config->sphinx->suite_name);
        $req->setPlacementId($channel_id);
        $req->setRequireCount($pn);
        $req->setStart($start);
        
        if ($prefer == "older") {
            $req->setDir(iface\SelectRequest\Direction::OLDER);
        } else {
            $req->setDir(iface\SelectRequest\Direction::LATER);
        }
        $req->setRequests($ctx);

        list($resp, $status) = $this->client->Select($req, array(),
                                                     array(
                                                           "timeout" => $config->sphinx->call_timeout,
                                                           ))->wait();

        if ($status->code != 0) {
            $this->logger->warning("get abflag error:" . $status->code . ":" . json_encode($status->details, true));
            return null;
        } else {
            $ret = array();

            foreach ($resp->getModelsList() as $model) {
                $ret []= $model->NewsId;
            }

            return $ret;
        }
    }
}
