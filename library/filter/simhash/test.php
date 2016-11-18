<?php

$filter = new SimhashFilter();

$news1 = array(
            "id"=>'1',
            "score"=>0.8,
            "related_sign"=>'1000010000000000000000000000011000000000000000000000000000000000',
        );

$news2 = array( 
            "id"=>'2',
            "score"=>0.7,
            "related_sign"=>'1000010000000001110000000000011000000000000000000000000000000000',
        );

$newsLst = array($news1, $news2);

$newsArr = $filter->filterDuplicate($newsLst);
foreach ($newsArr as $news){
    echo $news['id'];
    echo $news['score'];
}
