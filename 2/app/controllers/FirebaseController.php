<?php

class FirebaseController extends BaseController {
    public function IndexAction(){
        if (!$this->request->isPost()) {
            throw new HttpException(ERR_INVALID_METHOD, "not supported method");
        }
        $this->check_user_and_device();
        
        $req = $this->request->getJsonRawBody(true);
        if (null === $req) {
            throw new HttpException(ERR_BODY, "body format error");
        }

        $device = new Device();
                
        $device->token = $this->get_or_fail($req, "token", "string");
        $device->os = $this->get_or_fail($req, "os", "string");
        $device->os_version = $this->get_or_fail($req, "os_version", "string");
        $device->vendor = $this->get_or_fail($req, "vendor", "string");
        $device->imsi = $this->get_or_default($req, "imsi", "string", "");
        $device->user_id = $this->userSign;
        $device->device_id = $this->deviceId;

        $ret = $device->save();
        if ($ret === false) {
            $this->logger->warning("[DEVICE_SAVE_ERR][NEED_CARE:yes][err: " . $user->getMessages()[0]);
            throw new HttpException(ERR_INTERNAL_DB,
                                    "save device info error");
        }

        $this->logger->info(sprintf("[REG][token:%s]", $token));
        $this->setJsonResponse(array(
                                     "message"  => "ok",
                                     ));
        return $this->response;
    }
}
