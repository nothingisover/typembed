<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * Typembed 视频播放插件
 *
 * @package Typembed
 * @author Fengzi
 * @version 1.4.0
 * @dependence 13.12.12-*
 * @link https://7yper.com/3636
 */
class Typembed_Plugin implements Typecho_Plugin_Interface{

    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     *
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate(){
        Typecho_Plugin::factory('Widget_Abstract_Contents')->contentEx = array('Typembed_Plugin', 'parse');
        Typecho_Plugin::factory('Widget_Abstract_Contents')->excerptEx = array('Typembed_Plugin', 'parse');
    }

    public static function parse($content, $widget, $lastResult){
        $content = empty($lastResult) ? $content : $lastResult;
        if ($widget instanceof Widget_Archive){
            $content = preg_replace_callback('/<p>(?:(?:<a[^>]+>)?(?<video_url>(?:(http|https):\/\/)+[a-z0-9_\-\/\.\?%#=]+)(?:<\/a>)?)<\/p>/si', array('Typembed_Plugin', 'parseCallback'), $content);
        }
        return $content;
    }

    public static function parseCallback($matches){
        $is_music = array('music.163.com');

        try {
            $jump_play = Typecho_Widget::widget('Widget_Options')->plugin('Typembed')->jump_play;
        } catch(Typecho_Plugin_Exception $e) {
            $jump_play = 0;
        }

        $providers = array(
            // video
            'www.youtube.com' => array(
                '#https?://www\.youtube\.com/watch\?v=(?<video_id>[a-z0-9_=\-]+)#i',
                'https://www.youtube.com/v/{video_id}',
                'https://www.youtube.com/embed/{video_id}',
            ),
            'youtu.be' => array(
                '#https?://youtu\.be/(?<video_id>[a-z0-9_=\-]+)#i',
                'https://www.youtube.com/v/{video_id}',
                'https://www.youtube.com/embed/{video_id}',
            ),
            'www.bilibili.com' => array(
                '#https?://www\.bilibili\.com/video/(?:[av|BV]+)(?:(?<video_id1>[a-zA-Z0-9_=\-]+)/(?:index_|\#page=)(?<video_id2>[a-zA-Z0-9_=\-]+)|(?<video_id>[a-zA-Z0-9_=\-]+))#i',
                '',
                '//player.bilibili.com/player.html?aid={video_id}&bvid={video_id}&cid=&page=1',
            ),
            'v.youku.com' => array(
                '#https?://v\.youku\.com/v_show/id_(?<video_id>[a-z0-9_=\-]+)#i',
                '',
                'https://player.youku.com/embed/{video_id}?client_id=d0b1b77a17cded3b',
            ),
            'v.qq.com' => array(
                '#https?://v\.qq\.com/(?:[a-z0-9_\./]+\?vid=(?<video_id>[a-z0-9_=\-]+)|(?:[a-z0-9/]+)/(?<video_id2>[a-z0-9_=\-]+))#i',
                '',
                'https://v.qq.com/iframe/player.html?vid={video_id}',
            ),
            'www.dailymotion.com' => array(
                '#https?://www\.dailymotion\.com/video/(?<video_id>[a-z0-9_=\-]+)#i',
                '',
                'https://www.dailymotion.com/embed/video/{video_id}',
            ),
            'www.acfun.cn' => array(
                '#https?://www\.acfun\.cn/v/ac(?<video_id>\d+)#i',
                '',
                'https://www.acfun.cn/player/ac{video_id}',
            ),
            'my.tv.sohu.com' => array(
                '#https?://my\.tv\.sohu\.com/us/(?:\d+)/(?<video_id>\d+)#i',
                '',
                'https://tv.sohu.com/upload/static/share/share_play.html#{video_id}_0_0_9001_0',
            ),
            'www.56.com' => array(
                '#https?://(?:www\.)?56\.com/[a-z0-9]+/(?:play_album\-aid\-[0-9]+_vid\-(?<video_id>[a-z0-9_=\-]+)|v_(?<video_id2>[a-z0-9_=\-]+))#i',
                '',
                'https://www.56.com/iframe/{video_id}',
            ),
            'www.wasu.cn' => array(
                '#https?://www\.wasu\.cn/play/show/id/(?<video_id>\d+)#i',
                '',
                'https://www.wasu.cn/Play/iframe/id/{video_id}',
            ),
            // music
            'music.163.com' => array(
                '#https?://music\.163\.com/\#/song\?id=(?<video_id>\d+)#i',
                '',
                'https://music.163.com/outchain/player?type=2&id={video_id}&auto=0&height=90',
            ),
        );
        $video_url = $matches['video_url'];
        $parse = parse_url($video_url);
        $site = $parse['host'];
        if(!in_array($site, array_keys($providers))){
            return '<p><a href="' . $matches['video_url'] . '">' . $matches['video_url'] . '</a></p>';
        }
        preg_match_all($providers[$site][0], $matches['video_url'], $match);
        $id = $match['video_id'][0] == '' ? $match['video_id2'][0] : $match['video_id'][0];
        if(self::isMobile()){
            try{
                $width = Typecho_Widget::widget('Widget_Options')->plugin('Typembed')->mobile_width;
                $height = Typecho_Widget::widget('Widget_Options')->plugin('Typembed')->mobile_height;
            }catch(Typecho_Plugin_Exception $e){
                $width = '100%';
                $height = '500';
            }
        }else{
            try{
                $width = Typecho_Widget::widget('Widget_Options')->plugin('Typembed')->width;
                $height = Typecho_Widget::widget('Widget_Options')->plugin('Typembed')->height;
            }catch(Typecho_Plugin_Exception $e){
                $width = '100%';
                $height = '250';
            }
        }
        if(in_array($site, $is_music)){
            $height = '110px';
        }
        if($jump_play){
            $html = sprintf(
                '<div style="width: %2$s; height: %3$spx; overflow: hidden; position: relative;">
                    <a href="%1$s" title="点击开始播放" target="_blank" style="display: block; margin: 100px auto 0; width: 50px; height: 50px; text-decoration: none; border: 0; position: absolute; left: 50%%; top: 50%%; margin: -25px;">
                        <div style="width: 0; height: 0; border-top: 25px solid transparent; border-left: 50px solid #FFF; border-bottom: 25px solid transparent;"></div>
                    </a>
                </div>',
                $video_url, $width, $height);
        }else{
            $url = str_replace('{video_id}', $id, $providers[$site][2]);
            $html = sprintf(
                '<iframe src="%1$s" width="%2$s" height="%3$s" frameborder="0" allowfullscreen="true"></iframe>',
                $url, $width, $height);
        }
        return '<div id="typembed" style="background: #333; overflow: hidden; line-height: 0;">'.$html.'</div>';
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     *
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){}

    /**
     * 获取插件配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form){
        try{
            $typembed_code = Typecho_Widget::widget('Widget_Options')->plugin('Typembed')->typembed_code;
        }catch(Typecho_Plugin_Exception $e){
            $typembed_code = '';
        }
        $width = new Typecho_Widget_Helper_Form_Element_Text('width', NULL, '100%', _t('播放器宽度'));
        $form->addInput($width);
        $height = new Typecho_Widget_Helper_Form_Element_Text('height', NULL, '500', _t('播放器高度'));
        $form->addInput($height);
        $mobile_width = new Typecho_Widget_Helper_Form_Element_Text('mobile_width', NULL, '100%', _t('移动设备播放器宽度'));
        $form->addInput($mobile_width);
        $mobile_height = new Typecho_Widget_Helper_Form_Element_Text('mobile_height', NULL, '250', _t('移动设备播放器高度'));
        $form->addInput($mobile_height);
        $jump_play = new Typecho_Widget_Helper_Form_Element_Radio('jump_play', array(
            1   =>  _t('启用'),
            0   =>  _t('关闭')
        ), 0, _t('跳转播放'), _t('手机端不支持H5播放的视频，将跳转到源网站播放<br /><br /><br />升级到<a href="https://7yper.com/3636" target="_blank">最新版本</a>。关注微信公众号<a href="https://7yper.com/usr/uploads/2014/08/972e6fb0794d359.jpg" target="_blank">Typer</a>。'));
        $form->addInput($jump_play->addRule('enum', _t('必须选择一个模式'), array(0, 1)));
        $typembed_code_text = new Typecho_Widget_Helper_Form_Element_Fake('typembed_code', NULL, '', _t('高级功能激活码'), '');
        $form->addInput($typembed_code_text);
    }

    /**
     * 个人用户的配置面板
     *
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}

    /**
     * 移动设备识别
     *
     * @return boolean
     */
    private static function isMobile(){
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $mobile_browser = Array(
            "mqqbrowser", // 手机QQ浏览器
            "opera mobi", // 手机opera
            "juc","iuc", 'ucbrowser', // uc浏览器
            "fennec","ios","applewebKit/420","applewebkit/525","applewebkit/532","ipad","iphone","ipaq","ipod",
            "iemobile", "windows ce", // windows phone
            "240x320","480x640","acer","android","anywhereyougo.com","asus","audio","blackberry",
            "blazer","coolpad" ,"dopod", "etouch", "hitachi","htc","huawei", "jbrowser", "lenovo",
            "lg","lg-","lge-","lge", "mobi","moto","nokia","phone","samsung","sony",
            "symbian","tablet","tianyu","wap","xda","xde","zte"
        );
        $is_mobile = false;
        foreach ($mobile_browser as $device) {
            if (stristr($user_agent, $device)) {
                $is_mobile = true;
                break;
            }
        }
        return $is_mobile;
    }
}
