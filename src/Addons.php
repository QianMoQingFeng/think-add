<?php

/**
 * +----------------------------------------------------------------------
 * | think-addons [thinkphp6]
 * +----------------------------------------------------------------------
 *  .--,       .--,             | FILE: Addons.php
 * ( (  \.---./  ) )            | AUTHOR: byron
 *  '.__/o   o\__.'             | EMAIL: xiaobo.sun@qq.com
 *     {=  ^  =}                | QQ: 150093589
 *     /       \                | DATETIME: 2019/11/5 14:47
 *    //       \\               |
 *   //|   .   |\\              |
 *   "'\       /'"_.-~^`'-.     |
 *      \  _  /--'         `    |
 *    ___)( )(___               |-----------------------------------------
 *   (((__) (__)))              | 高山仰止,景行行止.虽不能至,心向往之。
 * +----------------------------------------------------------------------
 * | Copyright (c) 2019 http://www.zzstudio.net All rights reserved.
 * +----------------------------------------------------------------------
 */

declare(strict_types=1);

namespace think;

use think\App;
use think\helper\Str;
use think\facade\Config;
use think\facade\Lang;
use think\facade\View;
use think\Response;

abstract class Addons
{
    // app 容器
    protected $app;
    // 请求对象
    protected $request;
    // 当前插件标识
    protected $name;
    // 插件路径
    protected $addon_path;
    // 视图模型
    protected $view;
    // 视图layout
    protected $view_layout;
    // 插件配置
    protected $addon_config;
    // 插件信息
    protected $addon_info;
    // 模版后缀
    protected $view_suffix = 'html';
    // 模块信息
    protected $addon_module = '';
    // 模块信息
    protected $controller_path = '';
    /**
     * 插件构造函数
     * Addons constructor.
     * @param \think\App $app
     */
    public function __construct(App $app)
    {
        if (!defined('IS_ADDON'))  define('IS_ADDON', false);
        $this->app = $app;
        $this->request = $app->request;
        $this->view = clone View::engine('Think');

        $this->name = $this->getAddonName();
        $this->addon_module = $this->getAddonModule();
        $this->addon_path = $app->addons->getAddonsPath() . $this->name . DIRECTORY_SEPARATOR . $this->addon_module . ($this->addon_module ? DIRECTORY_SEPARATOR : '');
        $this->addon_config = "addon_{$this->name}_config";
        $this->addon_info = "addon_{$this->name}_info";



      
        // $files = glob($this->app->getAppPath() . 'lang' . DIRECTORY_SEPARATOR . $langset . '.*');
        // $this->load($files);
        // glob($this->app->getAppPath() . 'lang' . DIRECTORY_SEPARATOR . $langset . '.*')
        // Lang::load(['/media/psf/qianmokeji/SaetAdmin/saet.io/app/admin/lang/config/zh-cn.php']);
        // 插件视图目录

        // $this->app->getRootPath()

        // Lang::load(root_path() . 'addons' . DIRECTORY_SEPARATOR . $addon . '\\lang\\' . $controller);
        // dump(root_path() . 'addons' . DIRECTORY_SEPARATOR . $addon . '\\lang\\' . $controller.);

        // dump($this->name);
        

        // dump(str_replace('.', '/', $this->request->controller()));
        // if (IS_ADDON) {
        //     dump('addons' . '/' . $this->name . ($this->addon_module ? '@' . $this->addon_module : '') . '/' . parse_name($this->request->controller()));
        // } 
        // else {
        //     dump('app' . '/' . app('http')->getName() . '/' . parse_name($this->request->controller()));
        // }

        //修正路径
        // dump( $this->addon_path);




        $this->view->config([
            'view_suffix' => $this->view_suffix
        ]);

        if (IS_ADDON)  $this->view->config(['view_path' => $this->addon_path . 'view' . DIRECTORY_SEPARATOR]);
        $this->view_layout ? $this->view->layout($this->view_layout) : null;
        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    {
    }

    /**
     * 获取插件标识
     * @return mixed|null
     */
    final protected function getAddonName()
    {
        if (IS_ADDON == false) return null;
        $class = get_class($this);
        list(, $name) = explode('\\', $class);
        $this->request->addon = $name;
        return $name;
    }
    /**
     * 获取插件模块
     * @return mixed|null
     */
    final protected function getAddonModule()
    {

        $module = null;
        if (mb_strstr($this->request->pathinfo(), $this->getAddonName() . '@')) {
            $str_arr = explode('@', $this->request->pathinfo());
            $str_arr = explode('/', $str_arr[1]);
            $module = $str_arr[0];
        } else if (app('http')->getName() == 'admin') {
            $module = 'admin';
        }
        // dump($module);
        return $module;
    }

    /**
     * 加载模板输出
     * @param string $template
     * @param array $vars           模板文件名
     * @return false|mixed|string   模板输出变量
     * @throws \think\Exception
     */
    protected function fetch($template = '', $vars = [])
    {
        return $this->view->fetch($template, $vars);
    }

    /**
     * 渲染内容输出
     * @access protected
     * @param  string $content 模板内容
     * @param  array  $vars    模板输出变量
     * @return mixed
     */
    protected function display($content = '', $vars = [])
    {
        return $this->view->display($content, $vars);
    }

    /**
     * 模板变量赋值
     * @access protected
     * @param  mixed $name  要显示的模板变量
     * @param  mixed $value 变量的值
     * @return $this
     */
    protected function assign($name, $value = '')
    {
        $data = [];
        if (is_array($name)) {
            $data = array_merge($data, $name);
        } else {
            $data[$name] = $value;
        }
        $this->view->assign($data);
        return $this;
    }

    /**
     * 初始化模板引擎
     * @access protected
     * @param  array|string $engine 引擎参数
     * @return $this
     */
    protected function engine($engine)
    {
        $this->view->engine($engine);

        return $this;
    }

    /**
     * 插件基础信息
     * @return array
     */
    final public function getAddonInfo()
    {
        $info = Config::get($this->addon_info, []);

        if ($info) {
            return $info;
        }

        // 文件属性
        $info = $this->info ?? [];
        // 文件配置
        $info_file = $this->addon_path . 'info.ini';
        if (is_file($info_file)) {
            $_info = parse_ini_file($info_file, true, INI_SCANNER_TYPED) ?: [];
            $_info['url'] = addons_url();
            $info = array_merge($_info, $info);
        }
        Config::set($info, $this->addon_info);

        return isset($info) ? $info : [];
    }

    /**
     * 获取配置信息
     * @param bool $type 是否获取完整配置
     * @return array|mixed
     */
    final public function getAddonConfig($type = false)
    {
        $config = Config::get($this->addon_config, []);
        if ($config) {
            return $config;
        }
        $config_file = $this->addon_path . 'config.php';
        if (is_file($config_file)) {
            $temp_arr = (array)include $config_file;
            if ($type) {
                return $temp_arr;
            }
            foreach ($temp_arr as $key => $value) {
                $config[$key] = $value['value'];
            }
            unset($temp_arr);
        }
        Config::set($config, $this->addon_config);

        return $config;
    }

    //必须实现安装
    abstract public function install();

    //必须卸载插件方法
    abstract public function uninstall();
}
