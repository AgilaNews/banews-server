<?php
/**
 * @file   DetailVideoRender.php
 * @author Gethin Zhang <zgxcassar@gmail.com>
 * @date   Sat Jan  7 14:35:38 2017
 * 
 * @brief  
 * 
 * 
 */
class DetailVideoRender extends BaseDetailRender {
    public function render($news_model, $recommend_models = null) {
        $ret = parent::render($news_model, $recommend_models);

        $ret["videos"] = array();
        $view = 0;
        
        $videos = NewsYoutubeVideo::getVideosOfNews($newsSign);
        foreach($videos as $video) {
            if (!$video || $video->is_deadlink == 1 || !$video->cover_meta) {
                continue;
            }
            
            if ($video->cover_origin_url) {
                $cover_meta = json_decode($video->cover_meta, true);
                if (!$cover_meta || !$cover_meta["width"] || !$cover_meta["height"]) {
                    continue;
                }
            }
            
            $c = $this->getImgCell($video->video_url_sign, $cover_meta);
            $c["video_pattern"] = $c["pattern"] . "|v=1";
            $c["youtube_id"] = $video->youtube_video_id;
            $c["name"] = "<!--YOUTUBE" . $video->news_pos_id . "-->";
            $view = $video->view;
            $ret["videos"] []= $c;
        }
        
        return $ret;
    }
}