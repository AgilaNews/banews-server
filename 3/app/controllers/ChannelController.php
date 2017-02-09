<?php
/**
 * @file   ChannelController.php
 * @author Gethin Zhang <zgxcassar@gmail.com>
 * @date   Mon Aug 22 20:24:08 2016
 * 
 * @brief  
 * 
 * 
 */
class ChannelController extends BaseController {
    public function IndexAction(){
        $version = $this->get_request_param("version", "int", true);

        $channels = ChannelV2::getChannelsOfVersion($version, $this->client_version, $this->os);
        $this->logger->info(sprintf("[CHANNEL_GET][version:%s][ret:%d]", $version, count($channels)));
        $this->setJsonResponse($channels);
        return $this->response;
    }
}
