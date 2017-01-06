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

        foreach ($news_model_list as $sign => $news_model) {
            if (!$news_model) {
                continue;
            }
            $cell = "";
            if (RenderLib::isVideoChannel($news_model->channel_id)) {
                $cell = $this->video_render->serializeNewsCell($news_model);
            } else {
                $cell = $this->serializeNewsCell($news_model);
                unset($cell["filter_tags"]);
            }
            $collect = $collects[$news_model->url_sign];
            $cell["collect_id"] = $collect->id;
            $cell["public_time"] = $collect->create_time;
            $ret []= $cell;
        }

        RenderLib::FillCommentsCount($ret);
        RenderLib::FillTpl($ret, RenderLib::PLACEMENT_RECOMMEND);

        return $ret;
    }
}
