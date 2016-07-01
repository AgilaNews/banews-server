<?php
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
        $this->logger->info(sprintf("[Referrer][param:%s][version:%s]", $referrer, $version));
        $this->logEvent(EVENT_NEWS_REFERRER, array(
            "referrer" => $referrer,
            "version" => $version,
        ));
        $this->setJsonResponse(array("message" => "ok"));
        return $this->response;
    }
}
