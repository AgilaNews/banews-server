<?php
/**
 * @file   SphinxSelector.php
 * @author Gethin Zhang <zgxcassar@gmail.com>
 * @date   Fri Dec 23 16:40:38 2016
 * 
 * @brief  
 * 
 * 
 */
use Phalcon\DI;
class SphinxSelector extends BaseNewsSelector {
    public function __construct($channel_id, $controller) {
        $config = DI::getDefault()->get('config');

        $this->ctx = RequestContext::GetCtxFromController($controller);

        parent::__construct($channel_id, $controller);
    }

    protected function sampling($sample_count, $prefer) {
        $sphinx = DI::getDefault()->get('sphinx');

        $selected_news_list = $sphinx->select($this->ctx, $this->_channel_id, $prefer, $sample_count);
        if ($selected_news_list === null) {
            //TODO
        }

        return $selected_news_list;
    }
}
