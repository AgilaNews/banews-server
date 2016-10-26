<?php
/**
 * @file   UserController.php
 * @author Gethin Zhang <zgxcassar@gmail.com>
 * @date   Thu Apr 21 20:22:27 2016
 * 
 * @brief  
 * 
 * 
 */
class LoginController extends BaseController {
    public function IndexAction() {
        if (!$this->request->isPost()) {
            throw new HttpException(ERR_INVALID_METHOD,
                "login must be POST");
        }

        // http://php.net/always-populate-raw-post-data
        $req = $this->request->getJsonRawBody(true); 

        // $req = json_decode(file_get_content("php://input"));
        if (null === $req) {
            throw new HttpException(ERR_BODY, "body format error");
        }

        $uid = $this->get_or_fail($req, "uid", "string");
        $source_name = $this->get_or_fail($req, "source", "string");
        $source = $this->get_or_fail(User::SOURCE_MAP, $source_name, "int");
        $user = User::getBySourceAndId($source, $uid);
        $portrait = $this->get_or_default($req, "portrait", "string", "");

        if ($user && $user->portrait_srcurl != $portrait) {
            $user->portrait_url = $portrait;
            $user->portrait_srcurl = $portrait;

            $ret = $user->save();
            if ($ret === false) {
                $this->logger->warning("[FB_SAVE_ERR][NEED_CARE:yes][err: " . $user->getMessages()[0]);
                throw new HttpException(ERR_INTERNAL_DB,
                    "save user info error");
            }
        }

        if (!$user) {
            $user = new User();
            $user->uid = $uid;
            $user->source = $source;
            $user->sign = $this->sign_user($uid, $source);
            $user->name = $this->get_or_fail($req, "name", "string");
            $user->gender = $this->get_or_default($req, "gender", "int", 0);
            $user->portrait_srcurl = $portrait;
            /*
            $uploader = $this->di->get("ufileuploader");
            $user->portrait_url = $uploader->put("userpotraits/" . $user->sign . ".png", $user->portrait_srcurl);
            if (!$user->portrait_url) {
                $this->logger->info("upload portrait url error");
                $user->portrait_url = $user->portrait_srcurl;
            } else {
                $user->portrait_url = "http://" . IMAGE_SERVER_NAME . "/userpotraits/" . $user->sign . ".png";
            }
            */
            $user->portrait_url = $user->portrait_srcurl;
            $user->email = $this->get_or_default($req, "email", "string", "");

            $user->create_time = $user->update_time = time();

            $ret = $user->save();
            if ($ret === false) {
                $this->logger->warning("[FB_SAVE_ERR][NEED_CARE:yes][err: " . $user->getMessages()[0]);
                throw new HttpException(ERR_INTERNAL_DB,
                    "save user info error");
            }
        }
        
        $device = Device::getByDeviceId($this->deviceId);
        if ($device) {
            $device->user_id = $user->sign;
        } else {
            //create new deivce
            $device = new Device();
            $device->os = $this->os;
            $device->os_version = $this->os_version;
            $device->user_id = $user->sign;
            $device->client_version = $this->client_version;
            $device->device_id = $this->deviceId;
        }
        $ret = $device->save();
        if ($ret === false) {
            $this->logger->warning("[DEVICE_SAVE_ERR][NEED_CARE:yes][err: " . $device->getMessages()[0]);
            throw new HttpException(ERR_INTERNAL_DB,
                                    "save device info error");
        }


        $this->logger->info(sprintf("[Login][source:%s][uid:%s][id:%s][gender:%d][email:%s]", 
                            $source_name, $uid, $user->id, $user->gender, $user->email));

        $this->setJsonResponse($this->serializeUser($user));
        return $this->response;
    }

    public function sign_user($uid, $source) {
        // I think there will not be any confliction
        $salt = "buierh013!@$!#%";
        return hash ("sha1", $uid . $salt . $source, false);
    }


    public function serializeUser($user) {
        return array (
           "id" => $user->sign,
           "name" => $user->name,
           "gender" => $user->gender,
           "source" => User::SOURCE_UNMAP[$user->source],
           "portrait" => $user->portrait_url ? $user->portrait_url : $user->portrait_srcurl,
           "email" => $user->email,
           "create_time" => $user->create_time,
        );
    }
}
