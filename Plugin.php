<?php
namespace TypechoPlugin\Typembed;

use Typecho\Plugin\PluginInterface;
use Typecho\Plugin\Exception;
use Typecho\Widget;
use Typecho\Widget\Helper\Form;
use Typecho\Widget\Helper\Form\Element\Text;
use Typecho\Widget\Helper\Form\Element\Radio;
use Typecho\Widget\Helper\Form\Element\Hidden;
use Widget\Archive;


if (!defined('__TYPECHO_ROOT_DIR__')) {
    throw new Exception('__TYPECHO_ROOT_DIR__ is not defined');
}
/**
 * Typembed 视频播放插件
 *
 * @package Typembed
 * @author Fengzi
 * @version 2.0.0
 * @dependence 1.2.0-*
 * @link https://7yper.com/3636
 */
class Plugin implements PluginInterface{

    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     *
     * @access public
     * @return void
     * @throws Exception
     */
    public static function activate(){
        \Typecho\Plugin::factory('Widget_Abstract_Contents')->contentEx = array(__CLASS__, 'parse');
        \Typecho\Plugin::factory('Widget_Abstract_Contents')->excerptEx = array(__CLASS__, 'parse');
    }

    public static function parse($content, $widget, $lastResult){
        $content = empty($lastResult) ? $content : $lastResult;
        if ($widget instanceof Archive){
            $content = preg_replace_callback('/<p>(?:(?:<a[^>]+>)?(?<video_url>(?:(http|https):\/\/)+[a-z0-9_\-\/\.\?%#=]+)(?:<\/a>)?)<\/p>/si', array(__CLASS__, 'parseCallback'), $content);
        }
        return $content;
    }

    public static function parseCallback($matches){
        $is_music = array('music.163.com');

        // 读取配置参数
        try {
            $jump_play = Widget::widget('Widget_Options')->plugin('Typembed')->jump_play;
        } catch(Exception $e) {
            $jump_play = 0;
        }
        try {
            $width = Widget::widget('Widget_Options')->plugin('Typembed')->width;
        } catch(Exception $e) {
            $width = '100%';
        }
        try {
            $height = Widget::widget('Widget_Options')->plugin('Typembed')->height;
        } catch(Exception $e) {
            $height = '500';
        }
        try {
            $mobile_width = Widget::widget('Widget_Options')->plugin('Typembed')->mobile_width;
        } catch(Exception $e) {
            $mobile_width = '100%';
        }
        try {
            $mobile_height = Widget::widget('Widget_Options')->plugin('Typembed')->mobile_height;
        } catch(Exception $e) {
            $mobile_height = '250';
        }

        // CSS 值归一化：数字自动补 px
        $normalize_css = function($val) {
            return is_numeric($val) ? $val . 'px' : $val;
        };
        $w = $normalize_css($width);
        $h = $normalize_css($height);
        $mw = $normalize_css($mobile_width);
        $mh = $normalize_css($mobile_height);

        $providers = array(
            // video
            'www.youtube.com' => array(
                '#https?://www\.youtube\.com/watch\?v=(?<video_id>[a-z0-9_=\-]+)#i',
                'https://www.youtube.com/embed/{video_id}',
            ),
            'youtu.be' => array(
                '#https?://youtu\.be/(?<video_id>[a-z0-9_=\-]+)#i',
                'https://www.youtube.com/embed/{video_id}',
            ),
            'www.bilibili.com' => array(
                '#https?://www\.bilibili\.com/video/(?:av(?<avid>\d+)|(?<bvid>BV[a-zA-Z0-9]+))(?:[?&].*)?#i',
                '//player.bilibili.com/player.html?aid={aid}&bvid={bvid}&page=1&autoplay=0',
            ),
            'b23.tv' => array(
                '#https?://b23\.tv/(?<bvid>BV[a-zA-Z0-9]+)#i',
                '//player.bilibili.com/player.html?aid={aid}&bvid={bvid}&page=1&autoplay=0',
            ),
            'v.youku.com' => array(
                '#https?://v\.youku\.com/v_show/id_(?<video_id>[a-z0-9_=\-]+)#i',
                'https://player.youku.com/embed/{video_id}',
            ),
            'v.qq.com' => array(
                '#https?://v\.qq\.com/(?:[a-z0-9_\./]+\?vid=(?<video_id>[a-z0-9_=\-]+)|(?:[a-z0-9/]+)/(?<video_id2>[a-z0-9_=\-]+))#i',
                'https://v.qq.com/iframe/player.html?vid={video_id}',
            ),
            'www.dailymotion.com' => array(
                '#https?://www\.dailymotion\.com/video/(?<video_id>[a-z0-9_=\-]+)#i',
                'https://www.dailymotion.com/embed/video/{video_id}',
            ),
            'www.acfun.cn' => array(
                '#https?://www\.acfun\.cn/v/ac(?<video_id>\d+)#i',
                'https://www.acfun.cn/player/ac{video_id}',
            ),
            'my.tv.sohu.com' => array(
                '#https?://my\.tv\.sohu\.com/us/(?:\d+)/(?<video_id>\d+)#i',
                'https://tv.sohu.com/upload/static/share/share_play.html#{video_id}_0_0_9001_0',
            ),
            'www.56.com' => array(
                '#https?://(?:www\.)?56\.com/[a-z0-9]+/(?:play_album\-aid\-[0-9]+_vid\-(?<video_id>[a-z0-9_=\-]+)|v_(?<video_id2>[a-z0-9_=\-]+))#i',
                'https://www.56.com/iframe/{video_id}',
            ),
            // music
            'music.163.com' => array(
                '#https?://music\.163\.com/\#/song\?id=(?<video_id>\d+)#i',
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

        // 构建嵌入 URL
        $embed_url = $providers[$site][1];

        // 标准 {video_id} 替换
        $id = isset($match['video_id'][0]) && $match['video_id'][0] !== '' ? $match['video_id'][0] : (isset($match['video_id2'][0]) ? $match['video_id2'][0] : '');
        $embed_url = str_replace('{video_id}', $id, $embed_url);

        // Bilibili {aid}/{bvid} 替换
        if (strpos($embed_url, '{aid}') !== false || strpos($embed_url, '{bvid}') !== false) {
            $aid = isset($match['avid'][0]) ? $match['avid'][0] : '';
            $bvid = isset($match['bvid'][0]) ? $match['bvid'][0] : '';
            $embed_url = str_replace(array('{aid}', '{bvid}'), array($aid, $bvid), $embed_url);
        }

        // 播放器唯一 ID
        $pid = 'tpd-' . substr(md5($video_url), 0, 8);

        // 组装播放器内部内容
        if($jump_play){
            $inner = sprintf(
                '<a href="%1$s" title="点击开始播放" target="_blank" style="display:block; width:50px; height:50px; text-decoration:none; border:0; position:absolute; left:50%%; top:50%%; transform:translate(-50%%,-50%%);">
                    <div style="width:0; height:0; border-top:25px solid transparent; border-left:50px solid #FFF; border-bottom:25px solid transparent;"></div>
                </a>',
                $video_url);
        }else{
            $inner = sprintf(
                '<iframe src="%1$s" style="position:absolute; top:0; left:0; width:100%%; height:100%%; border:0;" allowfullscreen="true" sandbox="allow-scripts allow-same-origin allow-presentation" referrerpolicy="strict-origin-when-cross-origin" loading="lazy"></iframe>',
                $embed_url);
        }

        // 音乐播放器：固定 110px 高度
        if(in_array($site, $is_music)){
            $desktop_css = "width:100%; max-width:{$w}; height:110px; overflow:hidden; position:relative; background:#333;";
            $mobile_css  = "width:100%; height:110px;";
        } else {
            // 视频播放器：桌面端 aspect-ratio 等比缩放，移动端设上限
            if (is_numeric($width) && is_numeric($height)) {
                $desktop_css = "width:100%; max-width:{$w}; aspect-ratio:{$width}/{$height}; overflow:hidden; position:relative; background:#333;";
                $mobile_css  = "max-width:{$mw}; max-height:{$mh};";
            } else {
                $desktop_css = "width:{$w}; height:{$h}; overflow:hidden; position:relative; background:#333;";
                $mobile_css  = "max-width:{$mw}; height:{$mh};";
            }
        }

        $html = sprintf(
            '<div class="typembed-wrap" style="width:100%%;">
                <div id="%1$s" style="%2$s">%3$s</div>
                <style>@media(max-width:768px){#%1$s{%4$s}}</style>
            </div>',
            $pid, $desktop_css, $inner, $mobile_css);

        return $html;
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     *
     * @static
     * @access public
     * @return void
     * @throws Exception
     */
    public static function deactivate(){}

    /**
     * 获取插件配置面板
     *
     * @access public
     * @param Form $form 配置面板
     * @return void
     */
    public static function config(Form $form){
        $width = new Text('width', NULL, '100%', _t('播放器宽度'), _t('例如 800 或 100%'));
        $form->addInput($width);
        $height = new Text('height', NULL, '500', _t('播放器高度'));
        $form->addInput($height);
        $mobile_width = new Text('mobile_width', NULL, '100%', _t('移动设备播放器宽度'), _t('视口宽度 ≤768px 时生效'));
        $form->addInput($mobile_width);
        $mobile_height = new Text('mobile_height', NULL, '250', _t('移动设备播放器高度'));
        $form->addInput($mobile_height);
        $jump_play = new Radio('jump_play', array(
            1   =>  _t('启用'),
            0   =>  _t('关闭')
        ), 0, _t('跳转播放'), _t('启用将跳转到源网站播放<br /><br /><br />升级到<a href="https://7yper.com/3636" target="_blank">最新版本</a>。关注微信公众号<a href="https://7yper.com/usr/uploads/2014/08/972e6fb0794d359.jpg" target="_blank">Typer</a>。'));
        $form->addInput($jump_play->addRule('enum', _t('必须选择一个模式'), array(0, 1)));
        // 兼容旧版已存储的配置 key，防止表单渲染时找不到对应输入项
        $form->addInput(new Hidden('typembed_code', NULL, '', _t('')));
    }

    /**
     * 个人用户的配置面板
     *
     * @access public
     * @param Form $form
     * @return void
     */
    public static function personalConfig(Form $form){}
}
