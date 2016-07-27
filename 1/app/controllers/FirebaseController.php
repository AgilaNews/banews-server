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

        $token = $this->get_or_fail($req, "token", "string");
        $cache = $this->di->get('cache');
        if (!$cache) {
            throw new HttpException(ERR_INTERNAL_DB, "get redis error");
        }

        $redis = new NewsRedis($cache);
        $redis->registeNewDevice($this->deviceId, $token, $this->client_version);

        $this->logger->info(sprintf("[REG][token:%s]", $token));
        $this->setJsonResponse(array(
                                     "message"  => "ok",
                                     ));
        return $this->response;
    }
}
