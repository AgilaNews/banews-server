<?php
/**
 * @file   IndexController.php
 * @author Gethin Zhang <zhangguanxing01@baidu.com>
 * @date   Tue Apr 12 21:38:05 2016
 * 
 * @brief  index actions, provides main settings from clients
 *         this function will records main client informations to databases and log 
 * 
 * 
 */
class IndexController extends BaseController {
    public function IndexAction(){
        $validator = new IndexValidator();
        $validator->validate($_GET);
    }

    public function ErrorAction($exception) {
        if ($exception) {
            $this->response->status_code = $exception->getStatusCode();
            echo $excetpion->getBody();
        } else {
            $this->response->status_code = 404;
        }

    }
}

