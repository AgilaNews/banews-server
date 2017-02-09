<?php
class GifListRender extends BaseListRender {
    public function render($models) {
        $ret = array();

        foreach ($models as $news_model) {
            $cell = $this->serializeNewsCell($news_model);
            if (!$cell) {
                continue;   
            }

            $ret []= $cell;
        }

        RenderLib::FillTags($ret);
        RenderLib::FillCommentsCount($ret);
        RenderLib::FillTpl($ret, $this->placement_id, RenderLib::PLACEMENT_TIMELINE);

        return $ret;
    } 

    protected function serializeNewsCell($news_model){
        $gifs = NewsGif::getGifOfNews($news_model->url_sign);
        if (!$gifs || 
            count($gifs) != 1 || 
            $gifs[0]->is_deadlink == 1 ||
            !$gifs[0]->gif_meta) {
            return null;
        }

        $gif_model = $gifs[0];
        $cover_meta = json_decode($gif_model->cover_meta, true);
        $meta = json_decode($gif_model->gif_meta, true);
        $size= $meta["size"];
        $duration = $meta["duration"];
        $oh = $meta["height"];
        $ow = $meta["width"];
        if ($this->os == "ios") {
            $aw = (int) ($this->screen_w - 44);
        } else {
            $aw = (int) ($this->screen_w * 11 / 12);
        }
        $ah = (int) min($this->screen_h * 0.9, $aw * $oh / $ow);


        $ret = RenderLib::GetPublicData($news_model);
        $ret["imgs"] = array(array(
                                   "src" => sprintf(GIF_COVER_PATTERN, $gif_model->gif_url_sign),
                                   "width" => $aw,
                                   "height" => $ah,
                                   ),
                             );
        $ret["videos"] = array(array(
                                     "src" => sprintf(GIF_CHANNEL_PATTERN, $gif_model->gif_url_sign),
                                     "width" => $aw,
                                     "height" => $ah,
                                     "duration" => $duration,
                                     "size" => $size,
                                     ),
                               );

        $ret["views"] = $news_model->liked * 3;

        return $ret;
    }
}
