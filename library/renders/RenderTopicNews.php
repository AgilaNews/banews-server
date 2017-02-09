<?php

use Phalcon\DI;
class RenderTopicNews extends BaseListRender {
    public function render($models) {
        $ret = array();

        foreach ($models as $news_model) {
            if (!$news_model) {
                continue;
            }
            
            if ($news_model instanceof AdIntervene) {
                $r = $news_model->render();
                if ($r) {
                    $ret [] = $r; 
                }
            } else {
                $cell = $this->serializeNewsCell($news_model);
                $ret[] = $cell;
            }
        }

        $keys = array();
        foreach ($models as $news_model) {
            if ($news_model instanceof News) {
                $keys []= $news_model->url_sign;
            }
        }
        
        RenderLib::FillCommentsCount($ret);
        RenderLib::FillTpl($ret, $this->placement_id, RenderLib::PLACEMENT_TIMELINE);

        return $ret;
    }

    protected function useLargeImageNews($img) {
        return false;
    }
}
