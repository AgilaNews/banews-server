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
        $this->ctx = RequestContext::GetCtxFromController($controller);
        parent::__construct($channel_id, $controller);
    }

    protected function select($prefer) {
        $sphinx = DI::getDefault()->get('sphinx');
        $required = mt_rand(MIN_NEWS_SEND_COUNT, MAX_NEWS_SENT_COUNT);

        $selected_news_list = $sphinx->select($this->ctx, $this->_channel_id, $prefer, $required);
        if ($selected_news_list === null) {
            //TODO
        }
        
        $models = News::BatchGet($selected_news_list);
        $models = $this->removeInvisible($models);
        $models = $this->removeDup($models);

        $this->insertAd($selected_news_list);

        return $selected_news_list;
    }
}
