<?php
/**
 * @file   BloomFilterService.php
 * @author Gethin Zhang <zgxcassar@gmail.com>
 * @date   Thu Nov 24 17:23:29 2016
 * 
 * @brief  
 * 
 * 
 */
use Phalcon\DI;
class BloomFilterService {
    const FILTER_FOR_VIDEO = "agilanews_video_channel_filter";
    const FILTER_FOR_IMAGE = "agilanews_images_channel_filter";
    const FILTER_FOR_GIF = "agilanews_gifs_channel_filter";
    
    public function BloomFilterService($client) {
        $this->client = $client;
        $this->di = DI::getDefault();
        $this->call_timeout = $this->di->get('config')->bloomfilter->call_timeout;
        $this->logger = $this->di->get("logger");
    }

    public function add($filterName, $keys)  {
        $req = new bloomiface\AddRequest();
        $req->setName($filterName);
        $req->setAsync(false); //TODO change this to configurable
        $req->setKeys($keys);

        list($resp, $status) = $this->client->Add($req, array(), array("timeout" => $this->call_timeout))->wait();
        if ($status->code != 0) {
            $this->logger->warning("add filter error:" . $status->code . ":". json_encode($status->details, true));
        }

        return;
    }


    public function test($filterName, $keys) {
        $req = new bloomiface\TestRequest();
        $req->setName($filterName);
        $req->setKeys($keys);

        list($resp, $status) = $this->client->Test($req, array(), array("timeout" => $this->call_timeout))->wait();
        if ($status->code != 0) {
            $this->logger->warning("add filter error:" . $status->code . ":". json_encode($status->details, true));
            return array();
        }
        return $resp->getExistsList();
    }

    public function filter($filterName, $objs, $get_key_func) {
        $keys = array_map($get_key_func, $objs);
        $exists = $this->test($filterName, $keys);

        $ret = array();

        for ($i = 0 ; $i < count($exists); $i++) {
            if (!$exists[$i]) {
                $ret []= $objs[$i];
            }
        }

        return $ret;
    }
}
