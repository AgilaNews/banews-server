<?php
/**
 * @file   Features.php
 * @author Gethin Zhang <zgxcassar@gmail.com>
 * @date   Fri Nov 18 16:45:12 2016
 * 
 * @brief  
 * 
 * 
 */
class Features {
    const RICH_COMMENT_FEATURE = 1; // comment with replys

    const VIDEO_NEWS_FEATURE = 2; // news with videos inside

    const LARGE_IMG_FEATURE = 3; // large image news

    const FIX_NBA_TOP_FEATURE = 4;

    const AD_FEATURE = 5;

    const AB_FLAG_FEATURE = 6;

    const VIDEO_SUPPORT_FEATURE = 7;

    const LOG_V3_FEATURE = 8;

    const BANNER_FEATURE = 9;

    const INTERESTS_FEATURE = 10;

    const TOPIC_FEATURE = 11;

    const LIKE_NOTIFY_FEATURE = 12;

    const NAN = "999.999.999";

    private static $_feature_map = array(self::RICH_COMMENT_FEATURE => array(
                                                                             "ios" => "1.2.2",
                                                                             "android" => "1.2.2",
                                                                             ),
                                         self::VIDEO_NEWS_FEATURE => array(
                                                                           "ios" => "1.2.0",
                                                                           "android" => "1.2.0",
                                                                           ),
                                         self::LARGE_IMG_FEATURE => array(
                                                                          "ios" => "1.1.3",
                                                                          "android" => "1.1.3",
                                                                          ),
                                         self::FIX_NBA_TOP_FEATURE => array(
                                                                            "ios" => self::NAN,
                                                                            "android" => "1.2.1",
                                                                            ),
                                         self::AD_FEATURE => array(
                                                                   "ios" => "1.2.2",
                                                                   "android" => "1.2.3",
                                                                   ),
                                         self::AB_FLAG_FEATURE => array(
                                                                        "ios" => "1.2.2",
                                                                        "android" => "1.2.4",
                                                                        ),
                                         self::VIDEO_SUPPORT_FEATURE => array(
                                                                        "ios" => "1.2.2",
                                                                        "android" => "1.2.5",
                                                                        ),
                                         self::LOG_V3_FEATURE => array(
                                                                        "ios" => "1.2.3",
                                                                        "android" => "1.2.6",
                                                                        ),
                                         self::BANNER_FEATURE => array(
                                                                        "ios" => "1.2.3",
                                                                        "android" => "1.2.6"
                                                                        ),
                                         self::INTERESTS_FEATURE => array(
                                                                        "ios" => "1.2.6",
                                                                        "android" => "1.2.7"
                                                                        ),
                                         self::TOPIC_FEATURE => array(
                                                                        "ios" => "1.2.6",
                                                                        "android" => "1.2.7"
                                                                        ),
                                         self::LIKE_NOTIFY_FEATURE => array(
                                                                            "ios"=> "1.2.5",
                                                                            "android" => "1.2.5",
                                                                            ),
                                         );
                                   
    
    public static function Enabled($feature, $client_version, $os) {
        $min_version = self::NAN;
        if (array_key_exists($feature, self::$_feature_map)) {
            $min_version = self::$_feature_map[$feature][$os];
        }
        
        return version_compare($client_version, $min_version, ">=");
    }
        
}
