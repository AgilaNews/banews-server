<?php
/**
 * @file   PackageController.php
 * @author Gethin Zhang <zgxcassar@gmail.com>
 * @date   Sun Sep 18 13:12:55 2016
 * 
 * @brief  
 * 
 * 
 */
class PackageController extends BaseController {
    public function IndexAction(){
        $version = $this->get_request_param("version", "int", true);

        $package = Package::getPackage($version);
        if (!$package) {
            throw new HttpException(ERR_PACKAGE_NON_EXISTS, "package not exists");
        }
        $ret = array("url" => $package->download_url,
                     "md5" => $package->md5,
                     );
        $this->logger->info(sprintf("[PACKAGE_CHECK][version:%s]", $version));
        $this->setJsonResponse($ret);
        return $this->response;
    }
}
