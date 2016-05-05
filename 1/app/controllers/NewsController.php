<?php
/**
 * @file   NewsController.php
 * @author Gethin Zhang <zgxcassar@gmail.com>
 * @date   Thu May  5 10:45:33 2016
 * 
 * @brief  
 * 
 * 
 */
class NewsController extends BaseController {
    public function DetailAction() {
        if (!$this->request->isGet()){
            throw new HttpException(ERR_INVALID_METHOD,
                "read news must be get");
        }

        $newsSign = $this->get_request_param("id", "string", true);
        $news_model = News::getBySign($newsSign);
        if (!$news_model) {
            throw new HttpException(ERR_NEWS_NON_EXISTS, "news not found");
        }

        $commentCount = Comment::getCount($news_model->id);
        $topComment = Comment::getAll($news_model->id, null, 3);

        $imgs = NewsImage::getImagesOfNews($newsSign);

        $ret = array(
            "body" => $news_model->json_text,
            "commentCount" => $commentCount,
            "comments" => array(), 
            "imgs" => array(),
            "recommend_news" => array(),
            "newsid" => $news_model->url_sign,
            "title" => $news_model->title,
            "source" => $news_model->source_name,
            "source_url" => $news_model->source_url,
            "public_time" => $news_model->publish_time,
            "share_url" => $news_model->shared_url,
            "template" => $news_model->display_type,
            "content_type" => $news_model->content_type,
            "likedCount" => 100,
        );

        foreach ($topComment as $comment) {
            array_push($ret["comments"], $this->serializeComment($comment));
        }

        foreach($imgs as $img) {
            array_push($ret["imgs"], $this->serializeImage($img));
        }

        $this->setJsonResponse($ret);
        return $this->response;
    }

    protected function serializeImage($img){
        return array (
            "src" => $img->saved_url ? $img->saved_url : $img->source_url,
            "width" => 128,
            "height" => 128,
        );
    }

   private function serializeComment($comment){
        $ret = array (
                      "id" => $comment->id,
                      "time" => $comment->create_time,
                      "comment" => $comment->user_comment,
                      "user_id" => $comment->user_id,
                      "user_name" => "anonymous",
                      "user_portrait_url" => "",
                      );
        
        $user_model = User::getById($comment->user_id);
        if ($user_model) {
            $ret["user_name"] = $user_model->name;
            $ret["user_portrait_url"] = $user_model->portrait_url;
        }
        return $ret;
    }
}
