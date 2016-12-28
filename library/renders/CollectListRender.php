<?php
class CollectListRender extends BaseListRender {
    public function __construct($controller) {
        parent::__construct($controller);
        $this->video_render = new Render30001($controller);
    }

    public function render($collect_models) {
        $ret = array();
        $signs = array();
        $collects = array();

        foreach ($collect_models as $collect) {
            $signs []= $collect->news_sign;
            $collects[$collect->news_sign] = $collect;
        }

        $news_model_list = News::batchGet($signs);
        $comment_counts = Comment::getCount($signs);

        foreach ($news_model_list as $sign => $news_model) {
            if (!$news_model) {
                continue;
            }
            $cell = "";
            if ($news_model->channel_id == "30001") {
                $cell = $this->video_render->serializeNewsCell($news_model);
                if(array_key_exists($news_model->url_sign, $comment_counts)) {
                    $cell["commentCount"] = $comment_counts[$news_model->url_sign];
                }
            } else {
                $cell = $this->serializeNewsCell($news_model);
                unset($cell["filter_tags"]);
            }
            $collect = $collects[$news_model->url_sign];
            $cell["collect_id"] = $collect->id;
            $cell["public_time"] = $collect->create_time;
            $ret []= $cell;
        }

        return $ret;
    }
}
