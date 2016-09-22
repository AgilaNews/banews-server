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
}
