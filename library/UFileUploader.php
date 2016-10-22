<?php
class UFileUploader {
    public function __construct($ak, $sk, $bucket, $proxy, $suffix) {
        require_once("ucloud/conf.php");
        global $UCLOUD_PROXY_SUFFIX;
        global $UCLOUD_PUBLIC_KEY;
        global $UCLOUD_PRIVATE_KEY;

        $UCLOUD_PUBLIC_KEY = $ak;
        $UCLOUD_PRIVATE_KEY = $sk;
        $UCLOUD_PROXY_SUFFIX = $proxy;

        $this->bucket = $bucket;
        $this->suffix = $suffix;
    }


    public function put($name, $file) {
        require_once("ucloud/http.php");
        require_once("ucloud/proxy.php");
        
        list($data, $err) = UCloud_MultipartForm($this->bucket, $name, $file);
        if ($err) {
            throw new HttpException(ERR_INTERNAL, "upload file error: %s" . $err->ErrMsg);
        }

        return sprintf("http://%s%s/%s", $this->bucket, $this->suffix, $name);
    }
}
