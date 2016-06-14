<?php
class CheckController extends BaseController {
    public function indexAction(){
        $this->setJsonResponse(
            array(
                "min_version" => MIN_VERSION,
                "new_version" => NEW_VERSION,
                "avc" => ANDROID_VERSION_CODE,
                "update_url" => UPDATE_URL
                )
            );
       return $this->response;
    }
}
