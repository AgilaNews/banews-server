<?php
class CollectListRender extends BaseListRender {
    public function __construct($controller) {
        parent::__construct($controller);
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
            $cell = $this->serializeNewsCell($news_model);
            $collect = $collects[$news_model->url_sign];
            $cell["collect_id"] = $collect->id;
            $cell["public_time"] = $collect->create_time;
            $ret []= $cell;
        }

        return $ret;
    }
}
