<?php
/**
 * @file   SplashController.php
 * @author Gethin Zhang <zgxcassar@gmail.com>
 * @date   Fri Jan 13 01:08:36 2017
 * 
 * @brief  
 * 
 * 
 */
class SplashController extends BaseController {
    public function IndexAction(){
        if (!$this->request->isGet()) {
            throw new HttpException(ERR_INVALID_METHOD,
                                    "invalid method");
        }

        $ret = array(
                     "ads" => array(
                                    array(
                                          "dataid" => 1,
                                          ),
                                    ),
                     );
        
        if ($this->os == "android") {
            $ret["ads"][0]["image"] = sprintf(AD_IMAGE_PATTERN, "/ad/tcl/Android-01.jpg");
        } else {
            $ret["ads"][0]["image"] = sprintf(AD_IMAGE_PATTERN, "/ad/tcl/Ios-01.jpg.jpg");
        }
        
        $this->setJsonResponse($ret);
        return $this->response;
    }
}
