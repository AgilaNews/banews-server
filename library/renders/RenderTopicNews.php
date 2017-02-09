<?php

use Phalcon\DI;
class RenderTopicNews extends BaseListRender {
    protected function useLargeImageNews($img) {
        return true;
    }
}
