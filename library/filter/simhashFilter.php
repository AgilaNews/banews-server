<?php
use Phalcon\DI;

define ('TOPIC_NEWS_CANDIDATE_CNT', 200);

class SimhashFilter {

    public function __construct($di, $newsLst) {
        # $newsLst = array(
        #               array("id"=>"xx","score"=>"37","simhash"=>"43"),
        #               array(..),...) 
        $this->_di = $di;
    }

    public function findDuplicate($newsLst) {
        #find all duplicate pairs in newsLst
        $duPairs = array();

        return $duPairs;
    } 

    public function hammingDistance($hash1, $hash2) {
        $left = gmp_init($hash1, 10);
        $right = gmp_init($hash2, 10);
        #change from 10 to 2
        
        $xor = gmp_xor($left, $right);

    } 

}

