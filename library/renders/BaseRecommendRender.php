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
        $keys = array();
        foreach ($models as $model) {
            if ($model) {
                $keys []= $model->url_sign;
            }
        }
        
        foreach ($models as $news_model) {
            if(!$news_model) {
                continue;
            }
            
            $cell = $this->serializeNewsCell($news_model);
            $ret[] = $cell;
        }

        RenderLib::FillCommentsCount($keys, $ret);
        RenderLib::FillTpl($ret, RenderLib::PLACEMENT_RECOMMEND);
        return $ret;
    }
}