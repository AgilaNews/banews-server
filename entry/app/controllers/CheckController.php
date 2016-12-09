<?php
class CheckController extends BaseController {
    public function indexAction(){
        $update_info = Version::getUpdateInfo($this->client_version, $this->os, $this->build);
        if (!$update_info) {
            throw new HttpException(ERR_INTERNAL_DB,
                                    "internal error");
        }
        unset($update_info["models"]);

        $this->setJsonResponse($update_info);

        return $this->response;
    }

    public function EarlierAction(){
        $belows = Version::getAllVersionBelow($this->client_version);

        if (!$belows) {
            throw new HttpException(ERR_INTERNAL_DB, "internal error");
        }
        
        $ret = array("belows" => array());

        foreach ($belows as $below) {
            $ret["belows"] []= $below->client_version;
        }

        $this->setJsonResponse($ret);
        return $this->response;
    }
}
