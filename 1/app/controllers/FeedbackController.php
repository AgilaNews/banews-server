<?php
/**
 * @file   FeedbackController.php
 * @author Gethin Zhang <zgxcassar@gmail.com>
 * @date   Mon May 16 16:09:16 2016
 * 
 * @brief  
 * 
 * 
 */

define (MAX_FB_SIZE, 1024);

class FeedbackController extends BaseController {
    public function IndexAction(){
        if (!$this->request->isPost()) {
            throw new HttpException(ERR_INVALID_METHOD,
                "login must POST");
        }

        $req = $this->request->getJsonRawBody(true);
        $content = $this->get_or_fail($req, "fb_detail", "string");
        $email = $this->get_or_default($req, "email", "string", "");
        $problems = $this->get_or_default($req, "problems", "string", "");
        if (count($content) > MAX_FB_SIZE || count($problems) > MAX_FB_SIZE) {
            throw new HttpException(ERR_FB_TOO_LONG, "feedback too long");
        }
        $fb = new Feedback();

        if ($this->userSign) {
            $user_model = User::getBySign($this->userSign);
            if (!$user_model) {
                throw new HttpException(ERR_USER_NON_EXISTS, "user non exists");
            }
            $fb->user_id = $user_model->id;
        }

        if (!$this->deviceId) {
            throw new HttpException(ERR_DEVICE_NON_EXISTS, "device id not found");
        }
        $fb->device_id = $this->deviceId;
        $fb->feedback = $content;
        $fb->email = $email;
        $fb->ctime = time();
        $fb->problems = $problems;

        $ret = $fb->save();
        if ($ret) {
            $this->setJsonResponse(array("message" => "ok"));
            return $this->response;
        } else {
            $this->logger->warning("[FB_SAVE_ERR][NEED_CARE:yes][err: " . $fb->getMessages()[0]);
            throw new HttpException(ERR_INTERNAL_DB, "db error");
        }

        $this->logger->info(sprintf("[Feedback][user:%s][di:%s]", $userSign, $this->deviceId));
    }  
}
