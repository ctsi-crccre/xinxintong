<?php
namespace app\contribute;

require_once dirname(dirname(dirname(__FILE__))).'/member_base.php';
/**
 * 发起投稿 
 */
class base extends \member_base {
    
    protected $user;
    
    public function __construct()
    {
        $mpid = $_GET['mpid'];
        $_SESSION['mpid'] = $mpid;
        
        list($fid, $openid, $mid, $vid) = $this->getCurrentUserInfo($mpid);
        $user = new \stdClass;
        $user->fid = $fid;
        $user->openid = $openid;
        $user->mid = $mid;
        $user->vid = $vid;
        $this->user = $user;
    }
    
    public function get_access_rule() 
    {
        $rule_action['rule_type'] = 'black';
        $rule_action['actions'] = array();

        return $rule_action;
    }
    /**
     * 获得当前用户的信息
     * $mpid
     * $entry
     */
    public function index_action($mpid, $entry, $code=null, $mocker=null) 
    {
        $openid = $this->doAuth($mpid, $code, $mocker);
        $this->afterOAuth($mpid, $entry, $openid);
    }
    /**
     * 单篇文章
     */
    public function articleGet_action($mpid, $id)
    {
        $article = $this->getArticle($mpid, $id);
        
        return new \ResponseData($article);   
    }
    /**
     * 单篇文章
     */
    protected function &getArticle($mpid, $id)
    {
        $articleModel = $this->model('matter\article');
        $article = $articleModel->byId($id);
        $article->disposer = $articleModel->disposer($id);
        /**
         * channels
         */
        $article->channels = $this->model('matter\channel')->byMatter($id, 'article');
        
        return $article;
    }
    /**
     * 更新单图文的字段
     *
     * $id article's id
     * $nv pair of name and value
     */
    public function update_action($mpid, $id) 
    {
        $nv = (array)$this->getPostJson();

        isset($nv['body']) && $nv['body'] = urldecode($nv['body']);

        $nv['modify_at'] = time();
        $rst = $this->model()->update(
            'xxt_article', 
            $nv,
            "mpid='$mpid' and creater='".$this->user->fid."' and id='$id'"
        );

        return new \ResponseData($rst);
    }
    /**
     * 退回到上一步
     */
    public function return_action($mpid, $id)
    {
        $articleModel = $this->model('matter\article');
        $disposer = $articleModel->disposer($id);
        if ($disposer->seq == 1) {
            $article = $articleModel->byId($id);
            $mid = $article->creater;
            $phase = 'I';
        } else {
            $q = array(
                '*',
                'xxt_article_review_log',
                "article_id=$id and seq=".$disposer->seq-1
            );
            $prev = $this->query_obj_ss($q);
            $mid = $prev->mid;
            $phase = $prev->phase;
        }
        
        $log = $articleModel->forward($mpid, $id, $mid, $phase);
        
        return new \ResponseData('ok');
    }
    /**
     * 转发给指定人进行处理
     *
     * $mpid 公众平台ID
     * $id 文章ID
     * $phase 处理的阶段
     */
    public function forward_action($mpid, $id, $phase)
    {
        $reviewer = $this->getPostJson();
        
        $mid = $reviewer->userSet[0]->identity;
        
        $log = $this->model('matter\article')->forward($mpid, $id, $mid, $phase);
        
        return new \ResponseData('ok');
    }
    /**
     * $pid 获得父公众平台下的子平台
     */
    public function mpaccountGet_action($mpid) 
    {
        $mpas = $this->model('mp\mpaccount')->byMpid($mpid);
        
        return new \ResponseData($mpas);    
    }
    /**
     * 可用的频道
     */
    public function channelGet_action($mpid, $acceptType=null)
    {
        $channels = $this->model('matter\channel')->byMpid($mpid, $acceptType);
        
        return new \ResponseData($channels);
    }
    /**
     * 获得当前访问用户的信息
     *
     * $mpid
     */
    protected function getCurrentUserInfo($mpid, $callbackUrl=null) 
    {
        $authapis = $this->model('user/authapi')->byMpid($mpid, 'Y');
        $aAuthids = array();
        foreach ($authapis as $a)
            $aAuthids[] = $a->authid;

        $openid = $this->getCookieOAuthUser($mpid);

        $members = $this->authenticate($mpid, $aAuthids, $callbackUrl, $openid);
        empty($members) && $this->outputError('当前用户不是认证用户');
       
        $mid = $members[0]->mid;
        $fan = $this->model('user/fans')->byMid($mid, 'fid,openid'); 
        $vid = $this->getVisitorId($mpid);

        return array($fan->fid, $fan->openid, $mid, $vid);
    }
}
