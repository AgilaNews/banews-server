<?php
class CollectListRender extends BaseListRender {
    public function __construct($controller) {
        parent::__construct($controller);
        $this->controller = $controller;
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
            
            $cname = "Render" . $news_model->channel_id;
            if (class_exists($cname)) {
                $render = new $cname($this->controller);
            } else {
                $render = new BaseListRender($this->controller);
            }
            
            $cell = $render->serializeNewsCell($news_model);
            if (!$cell) {
                continue;
            }
            unset($cell["filter_tags"]);
            
            $collect = $collects[$news_model->url_sign];
            $cell["collect_id"] = $collect->id;
            $cell["public_time"] = $collect->create_time;
            $ret []= $cell;
        }

        RenderLib::FillCommentsCount($ret);
        RenderLib::FillTpl($ret, null, RenderLib::PLACEMENT_COLLECT);

        return $ret;
    }
}
