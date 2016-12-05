<?php
use Phalcon\DI;

class ReferrerController extends BaseController {
    public function IndexAction(){
         if (!$this->request->isPost()) {
            throw new HttpException(ERR_INVALID_METHOD, "referret must be POST");
        }

        $req = $this->request->getJsonRawBody(true);
        if (null === $req) {
            throw new HttpException(ERR_BODY, "body format error");
        }

        $referrer = $this->get_or_fail($req, "referrer", "string");
        $version = $this->get_or_fail($req, "version", "string");

        //* hack for oppo phone
        if (stristr($referrer, "oppo")) {
            $cache = DI::getDefault()->get('cache');
            if ($cache) {
                $key = sprintf(OPPO_DEVICE_KEY, $this->deviceId);
                if ($cache->exists($key)) {
                    break;
                }
                $cache->multi();
                $cache->set($key, 1);
                $cache->expire($key, OPPO_DEVICE_KEY_TTL);
                $cache->exec();
            }
        }
        //*/
        $this->logger->info(sprintf("[Referrer][param:%s][version:%s]", $referrer, $version));
        $this->logEvent(EVENT_NEWS_REFERRER, array(
            "referrer" => $referrer,
            "version" => $version,
        ));
        $this->setJsonResponse(array("message" => "ok"));
        return $this->response;
    }
}
