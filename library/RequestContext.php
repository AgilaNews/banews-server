<?php
class RequestContext {
    public static function GetCtxFromController($controller) {
        $ctx = new ipeninsula\RequestContext();
        
        $ctx->setUserId($controller->userSign);
        $ctx->setDeviceId($controller->deviceId);
        $ctx->setSessionId($controller->session);
        $ctx->setUserAgent($controller->ua);
        $ctx->setClientIp($controller->client_ip);
        $ctx->setTraceId($controller->logid);

        switch($controller->net) {
        case "2G":
            $ctx->setNet(ipeninsula\NetworkStatus::CELLAR_2G);
            break;
        case "3G":
            $ctx->setNet(ipeninsula\NetworkStatus::CELLAR_3G);
            break;
        case "4G":
            $ctx->setNet(ipeninsula\NetworkStatus::CELLAR_4G);
            break;
        case "WIFI":
            $ctx->setNet(ipeninsula\NetworkStatus::WIFI);
            break;
        default:
            $ctx->setNet(ipeninsula\NetworkStatus::NET_Unknown);
        }

        $ctx->setIsp($controller->isp);

        switch ($controller->lang) {
        case "EN":
            $ctx->setLanguage(ipeninsula\Language::EN);
            break;
        case "CN":
            $ctx->setLanguage(ipeninsula\Language::CN);
            break;
        default:
            $ctx->setLanguage(ipeninsula\Language::LANG_Unknown);
            break;
        }
            
        $ctx->setClientVersion($controller->client_version);
        switch($controller->os) {
        case "android":
            $ctx->setOs(ipeninsula\OS::Android);
            break;
            
        case "ios":
            $ctx->setOs(ipeninsula\OS::Ios);
            break;
            
        default:
            $ctx->setOs(ipeninsula\OS::OS_Unknown);
            break;
        }
        
        $ctx->setOsVersion($controller->os_version);
        $ctx->setLongitude($controller->lng);
        $ctx->setLatitude($controller->lat);
        $ctx->setTimeZone($controller->tz);
        $ctx->setScreenWidth($controller->resolution_w);
        $ctx->setScreenHeight($controller->resolution_h);
        $ctx->setDpi($controller->dpi);

        foreach ($controller->abflags as $key => $value) {
            $group = new \ipeninsula\RequestContext\AbGroupsEntry();
            $group->setKey($key, $value);
            
            $ctx->addAbGroups($group);
        }
        
        return $ctx;
    }
}
