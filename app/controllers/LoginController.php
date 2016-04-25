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
            throw new HttpException(ERR_BODY_ERR, "body format error");
        }

        $uid = $this->get_or_fail($req, "uid", "string");
        $source_name = $this->get_or_fail($req, "source", "string");
        $source = $this->get_or_fail(User::SOURCE_MAP, $source_name, "int");
        $user = User::findFirst( 
            array(
            "conditions" => "source = ?1 and uid = ?2",
            "bind" => array(1 => $source, 
                            2 => $uid),
            )
        );

        if ($user === false) {
            $user = new User();
            $user->uid = $uid;
            $user->source = $source;
            $user->sign = $this->sign_user($uid, $source);
        } else {
            $this->setJsonResponse($this->serializeUser($user));
            return $this->response;
        }

        $user->name = $this->get_or_fail($req, "name", "string");
        $user->gender = $this->get_or_default($req, "gender", "int", 0);
        $user->portrait_srcurl = $this->get_or_default($req, "portrait", "string", "");
        //change this
        $user->portrait_url = $user->portrait_srcurl;
        $user->email = $this->get_or_default($req, "email", "string", "");

        $user->create_time = $user->update_time = time();

        $ret = $user->save();
        if ($ret === false) {
            throw new HttpException(ERR_INTERNAL_DB,
                "save user info error");
        }

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
