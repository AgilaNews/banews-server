<?php

class Render10011 extends BaseListRender {
    public function __construct($controller) {
        parent::__construct($controller);
    }

    public function render($models) {
        $ret = array();
        foreach ($models as $news_model) {
            if (!$news_model) {
                continue;
            }
            $cell = $this->serializeNewsCell($news_model);
            if (count($cell["imgs"]) == 0) {
                continue;
            }
            
            $ret []= $cell;
        }
        
        RenderLib::FillTags($ret);
        RenderLib::FillCommentsCount($ret);
        RenderLib::FillTpl($ret, RenderLib::PLACEMENT_TIMELINE);
        return $ret;
    } 

    protected function serializeNewsCell($news_model){
        $imgs = NewsImage::getImagesOfNews($news_model->url_sign);
        $ret = RenderLib::GetPublicData($news_model);
        
        foreach($imgs as $img) {
            if (!$img || $img->is_deadlink == 1 || !$img->meta) {
                continue;
            }

            if ($img->origin_url) {
                $meta = json_decode($img->meta, true);
                if (!$meta || 
                    !is_numeric($meta["width"]) || 
                    !is_numeric($meta["height"])) {
                    continue;
                }

                $ow = $meta["width"];
                $oh = $meta["height"];
                if ($this->os == "ios") {
                    $aw = (int) ($this->screen_w  - 44);
                } else {
                    $aw = (int) ($this->screen_w * 11 / 12);
                }
                
                $ah = (int) min($this->screen_h * 0.9, $aw * $oh / $ow);
                $quality = RenderLib::GetImageQuality($this->net);
                
                $url = sprintf(IMAGE_CHANNEL_IMG_PATTERN, 
                               $img->url_sign, 
                               $aw, $aw, $ah, $quality);
                
                $ret["imgs"][] = array(
                                       "pattern" => sprintf(IMAGE_CHANNEL_IMG_PATTERN,
                                                            $img->url_sign,
                                                            '{w}', '{w}', '{h}', $quality),
                                       "src" => $url, 
                                       "height" => $ah, 
                                       "width" => $aw);
            }
        }

        $ret["views"] = $news_model->liked * 3 + mt_rand() % 100;
        
        return $ret;
    }
}
