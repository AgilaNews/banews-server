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
    public function __construct($controller) {
        $this->client_version = $controller->client_version;
        $this->os = $controller->os;
    }
    
    public function render($notifications) {
        $ret = array();
        
        foreach ($notifications as $notify) {
            $notiType = $notify->getType();
            
            if ($notiType == REPLY_COMMENT_NOTIFICATION_TYPE){ 
                $replyMsg = $notify->getReplyMsg();
                $cell = Comment::renderComment($replyMsg->getComment());
            } else if ($notiType == LIKE_NOTIFICATION_TYPE){
                if (!Features::Enabled(Features::LIKE_NOTIFICATION_TYPE,
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

        RenderLib::FillTpl($ret, RenderLib::PLACEMENT_NOTIFICATION_CENTER);
    }
}
