<?php
/**
 * 
 * @file    Render30001.php
 * @authors Zhao Yulong (elysium.zyl@gmail.com)
 * @date    2016-10-27 21:35:42
 * @version $Id$
 */

class Render30001 extends BaseListRender {

    public function __construct($controller) {
        parent::__construct($controller);
    }

    public function render($models) {
        $ret = array();

        $keys = array();
        foreach ($models as $model) {
            if (!$this->isIntervened($model)) {
                $keys []= $model->url_sign;
            }
        }
        
        $comment_counts = Comment::getCount($keys);

        foreach ($models as $sign => $news_model) {
            $cell = $this->serializeNewsCell($news_model);
            if ($cell == null) {
                continue;
            }

            if(array_key_exists($news_model->url_sign, $comment_counts)) {
                $cell["commentCount"] = $comment_counts[$news_model->url_sign];
            }
            $ret []= $cell;
        }

        return $ret;
    } 

    public function serializeNewsCell($news_model) {
        $video = Video::getByNewsSign($news_model->url_sign);
        if ($video) {
            $ret = RenderLib::GetPublicData($news_model);
            $ret["tpl"] = 12;
            $ret["views"] = $video->view;
            $meta = json_decode($video->cover_meta, true);
            if (!$meta || 
                !is_numeric($meta["width"]) || 
                !is_numeric($meta["height"])) {
                continue;
            }

            $ret["imgs"][] = RenderLib::LargeImageRender($this->_net, $news_model->url_sign,
                $meta, $this->_screen_w, $this->_screen_h, $this->_os);
            $ret["videos"][] = RenderLib::VideoRender($video, $meta, $this->_screen_w, 
                $this->_screen_h, $this->_os);
            return $ret;
        }
        return null;
    }
}
