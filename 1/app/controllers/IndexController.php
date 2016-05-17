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
    public function ErrorAction() {
        $exception = $this->dispatcher->getParam(0);
        $this->response->setHeader("Content-Type", "application/json; charset=UTF-8");

        if ($exception instanceof HttpException) {
            $this->response->setStatusCode($exception->getStatusCode());
            $this->response->setContent($exception->getBody());
            $this->logger->warning("[HttpError][code:" . $exception->getStatusCode() . "]:" . $exception->getBody());
        } else {
            $this->logger->warning("[InternalError]: " . $exception->getTraceAsString());
            $this->response->setStatusCode(500);

            if (BA_DEBUG) {
                if ($exception) {
                    $this->response->setContent($exception);
                }
            }
        }

        return $this->response;
    }
}
