<?php

class FirebaseController extends BaseController {
    public function IndexAction(){
        if (!$this->request->isPost()) {
            throw new HttpException(ERR_INVALID_METHOD, "not supported method");
        }
        if (!$this->deviceId) {
            throw new HttpException(ERR_DEVICE_NON_EXISTS, "device-id not found");
        }
        
        $req = $this->request->getJsonRawBody(true);
        if (null === $req) {
            throw new HttpException(ERR_BODY, "body format error");
        }

        $device = Device::getByDeviceId($this->deviceId);
        if (!$device) {
            $device = new Device();
        }
                
        $device->token = $this->get_or_fail($req, "token", "string");
        $device->os = $this->get_or_fail($req, "os", "string");
        $device->os_version = $this->get_or_fail($req, "os_version", "string");
        $device->vendor = $this->get_or_default($req, "vendor", "string", "");
        $device->imsi = $this->get_or_default($req, "imsi", "string", "");
        $device->user_id = $this->userSign || "";
        $device->device_id = $this->deviceId;
        $device->client_version = $this->client_version;

        $ret = $device->save();
        if ($ret === false) {
            $this->logger->warning("[DEVICE_SAVE_ERR][NEED_CARE:yes][err: " . $device->getMessages()[0]);
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
