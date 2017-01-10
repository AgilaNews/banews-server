<?php
/**
 * 
 * @file    Render30001.php
 * @authors Zhao Yulong (elysium.zyl@gmail.com)
 * @date    2016-10-27 21:35:42
 * @version $Id$
 */

class RecommendRender30001 extends Render30001 {
    public function render($models) {
        $ret = array();
        foreach ($models as $sign => $news_model) {
            if (!$news_model) {
                continue;
            }
            $cell = $this->serializeNewsCell($news_model);
            if ($cell == null) {
                continue;
            }
            $ret []= $cell;
        }

        RenderLib::FillCommentsCount($ret);
        RenderLib::FillTpl($ret, $this->placement_id, RenderLib::PLACEMENT_RECOMMEND);
        return $ret;
    } 
}
