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
        $di = DI::getDefault();
        $comment_service = $di->get('comment');
        $config = $di->get('config');
        
        $ret = array();
        $max_quality = 0.0;
        $news_sign = "";
        $hot_tags = 0;

        $keys = array();
        foreach ($models as $model) {
            $keys []= $model->url_sign;
        }
        
        $comment_counts = Comment::getCount($keys);
        
        foreach ($models as $news_model) {
            if(!$news_model) {
                continue;
            }
            if ($news_model instanceof AdIntervene) {
                $r = $news_model->render();
                if ($r) {
                    $ret [] = $r; 
                }
            } else {
                $cell = $this->serializeNewsCell($news_model);
                
                if(array_key_exists($news_model->url_sign, $comment_counts)) {
                    $cell["commentCount"] = $comment_counts[$news_model->url_sign];
                }
                
                $ret[] = $cell;
            }
        }
        
        return $ret;
    }
}