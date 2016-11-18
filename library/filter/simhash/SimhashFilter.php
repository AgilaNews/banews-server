<?php

use Phalcon\DI;

define ('RELATED_SIGN_LENGTH', 64);
define ('MAX_THRE_SIMILARITY', 0.9);

class SimhashFilter {

    public function filterDuplicate($newsLst) {
        #filter duplicate news in newsLst
        $newsCount = count($newsLst);
        $filteredNewsLst = $newsLst;
        $comparator = new SimhashComparator();
        for ($i=0; $i<$newsCount; $i++) {
            for ($j=$i+1; $j<$newsCount; $j++) {
                $fp1 = $newsLst[$i]['related_sign'];
                $fp2 = $newsLst[$j]['related_sign'];
                $similarity = $comparator->compare($fp1, $fp2);
                if ($similarity >= MAX_THRE_SIMILARITY) {
                    #i and j are duplicated news, compare score
                    if ($newsLst[$j]['score']>$newsLst[$i]['score']) {
                        unset($filteredNewsLst[$i]); 
                    } else {
                        unset($filteredNewsLst[$j]);
                    }
                } 
            }
        }
        return $filteredNewsLst;
    } 


}

