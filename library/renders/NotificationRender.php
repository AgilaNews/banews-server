<?php
/**
 * @file   NotificationRender.php
 * @author Gethin Zhang <zgxcassar@gmail.com>
 * @date   Thu Jan  5 23:27:37 2017
 * 
 * @brief  
 * 
 * 
 */
class NotificationRender {
    const REPLY_COMMENT_NOTIFICATION_TYPE = 1;
    const LIKE_NOTIFICATION_TYPE = 3;
    
    public function __construct($controller) {
        $this->client_version = $controller->client_version;
        $this->os = $controller->os;
    }
    
    public function render($notifications) {
        $ret = array();
        
        foreach ($notifications as $notify) {
            $notiType = $notify->getType();
            
            if ($notiType == self::REPLY_COMMENT_NOTIFICATION_TYPE){ 
                $replyMsg = $notify->getReplyMsg();
                $cell = Comment::renderComment($replyMsg->getComment());
            } else if ($notiType == self::LIKE_NOTIFICATION_TYPE){
                if (!Features::Enabled(Features::LIKE_NOTIFY_FEATURE,
                                       $this->client_version, $this->os)) {
                    continue;
                }
                
                $LikeMsg = $notify->getLikeMsg();
                $cell = Comment::renderLikeComment($LikeMsg->getComment(), $LikeMsg->getLikeNumber());
            } else{
                continue;
            }

            $cell["notify_id"] = $notify->getNotificationId();
            $cell["status"] = $notify->getStatus();
            $cell["type"] = $notiType;
            
            if ($cell["status"] == null) {
                $cell["status"] = 0;
            }
            $ret []= $cell;
        }

        RenderLib::FillTpl($ret, null, RenderLib::PLACEMENT_NOTIFICATION_CENTER);

        return $ret;
    }
}
