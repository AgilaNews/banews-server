<?php
/**
 * @file   DetailGifRender.php
 * @author Gethin Zhang <zgxcassar@gmail.com>
 * @date   Sat Jan  7 14:35:38 2017
 * 
 * @brief  
 * 
 * 
 */
class DetailGifRender extends BaseDetailRender {
    public function render($news_model, $recommend_models = null) {
        $ret = parent::render($news_model, null);
        $ret["views"] = $news_model->liked * 3 + mt_rand() % 100;

        $ret = array_merge($ret, $this->getGif($news_model));
        return $ret;
    }

    protected function getGif($news_model) {
        $gifs = NewsGif::getGifOfNews($news_model->url_sign);
        if (!$gifs ||
            count($gifs) != 1 ||
                        $gifs[0]->is_deadlink == 1 ||
            !$gifs[0]->gif_meta) {
            return array();
        }

        $gif_model = $gifs[0];
        $cover_meta = json_decode($gif_model->cover_meta, true);
        $meta = json_decode($gif_model->gif_meta, true);
        $size= $meta["size"];
        $duration = $meta["duration"];

        $img = RenderLib::LargeImageRender(GIF_COVER_PATTERN,
                                           $this->c->net, $gif_model->gif_url_sign,
                                           $meta, $this->c->resolution_w, $this->c->resolution_h,
                                           $this->c->os);
        $cell = array(
                      "imgs" => array($img),
                      "videos" => array(array(
                                              "src" => sprintf(GIF_CHANNEL_PATTERN, $gif_model->gif_url_sign),
                                              "width" => $img["width"],
                                              "height" => $img["height"],
                                              "duration" => $duration,
                                              "size" => $size,
                                              ),
                                        ),
                      );

        return $cell;
    }
}