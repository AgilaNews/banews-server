<?php
/**
 * @file   SearchController.php
 * @author Gethin Zhang <zgxcassar@gmail.com>
 * @date   Fri Dec  9 11:00:50 2016
 * 
 * @brief  
 * 
 * 
 */
class SearchController extends BaseController {
    public function HotwordsAction() {
        $this->setJsonResponse(array(
                                     "hotwords" => array("lian", "zhan", "animal"),
                                     ));
        return $this->response;
    }

    public function IndexAction() {
        
    }
}


