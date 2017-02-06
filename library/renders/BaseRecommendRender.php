<?php
/**
 * 
 * @file    BaseRecommendRender.php
 * @authors Zhao Yulong (elysium.zyl@gmail.com)
 * @date    2016-10-28 11:29:44
 * @version $Id$
 */

class BaseRecommendRender extends BaseListRender {
    public function render($models) {
        $ret = array();
        foreach ($models as $news_model) {
            if(!$news_model) {
                continue;
            }
            
            $cell = $this->serializeNewsCell($news_model);
            $ret[] = $cell;
        }

        RenderLib::FillCommentsCount($ret);
        RenderLib::FillTpl($ret, null, RenderLib::PLACEMENT_RECOMMEND);
        
        return $ret;
    }

    public static function getRecommendRender($controller, $channel_id) {
        if (RenderLib::isVideoChannel($channel_id)) {
            return new VideoRecommendRender($controller, $channel_id);
        }

        return new BaseRecommendRender($controller, $channel_id);
    }
}