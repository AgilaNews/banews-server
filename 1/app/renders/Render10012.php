<?php
class Render10012 extends BaseListRender {
    public function __construct($did, $screen_width, $screen_height, $net) {
        parent::__construct($did, $screen_width, $screen_height, $net);
    }

    public function render($models) {
        $ret = array();

        foreach ($models as $sign => $news_model) {
            $cell = $this->serializeNewsCell($news_model);
            if (!$cell) {
                continue;   
            }
            $ret []= $cell;
        }

        return $ret;
    } 

    protected function serializeNewsCell($news_model){
        $gifs = NewsGif::getGifOfNews($news_model->url_sign);
        if (!$gifs || 
            count($gifs) != 1 || 
            $gifs[0]->is_deadlink != 1 ||
            !$gifs[0]->meta) {
            return null;
        }

        $gif_model = $gifs[0];
        $meta = $gif_model->meta;
        $duration = $meta["duration"];
        $height = $meta["height"];
        $width = $meta["width"];
        
        $ret = array(
            "title" => $news_model->title,
            "news_id" => $news_model->url_sign,
            "source" => $news_model->source_name,
            "source_url" => $news_model->source_url,
            "public_time" => $news_model->publish_time,
            "gif" => array(
                "cover" => sprintf(GIF_COVER_PATTERN, $gif_model->gif_url_sign),
                "src" => $gif_model->gif_save_url,
                "width" => $width,
                "height" => $height,
                "duration" => $duration,
            )
        );

        $ret["tpl"] = NEWS_LIST_TPL_VIDEO;

        return $ret;
    }
}
