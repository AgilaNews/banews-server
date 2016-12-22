<?php
/**
 * 
 * @file    InterestController.php
 * @authors Zhao Yulong (elysium.zyl@gmail.com)
 * @date    2016-12-20 11:58:15
 * @version $Id$
 */
use Phalcon\DI;

class InterestController extends BaseController {
    public function IndexAction() {
        if ($this->request->isPost()) {
            return $this->addInterests();
        } else if ($this->request->isGet()) {
            return $this->getInterests();
        } else {
            throw new HttpException(ERR_INVALID_METHOD,
                                    "Push video method error");
        }
    }

    protected function addInterests() {
        $param = $this->request->getJsonRawBody(true);
        $interests = $param["interests"];

        $this->logger->info("[AddInterests]");
        foreach ($interests as $interest) {
            $interest_id = $this->get_or_fail($interest, "id", "string");
            $interest_name = $this->get_or_fail($interest, "name", "string");

            $userInterest = new UserInterests();
            $userInterest->device_id = $this->deviceId;
            if ($this->userSign) {
                $userInterest->user_id = $this->userSign;
            }
            $userInterest->interest_id = $interest_id;
            $userInterest->interest_name = $interest_name;
            $userInterest->upload_time = time();
            $userInterest->save();
            $this->logger->info(sprintf("[name:%d]", $interest_name));
        }
        $this->setDeviceSeenToBF();
        $this->setJsonResponse(array("message" => "OK"));
        return $this->response;
    }

    protected function getInterests() {
        $ret = array(
            "interests" => array(),
            );
        $interests = Interests::getAll();
        foreach ($interests as $interest) {
            $ret["interests"][] = array(
                "id" => $interest->id,
                "name" => $interest->name,
                );
        }
        $this->setJsonResponse($ret);
        return $this->response;
    }

    protected function setDeviceSeenToBF() {
        $filterName = BloomFilterService::FILTER_FOR_INTERESTS;
        $bf_service = DI::getDefault()->get("bloomfilter");

        $bf_service->add($filterName, array($this->deviceId));
    }
}
