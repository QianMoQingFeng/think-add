<?php
/**
 * +----------------------------------------------------------------------
 * | think-addons [thinkphp6]
 * +----------------------------------------------------------------------
 *  .--,       .--,             | FILE: Route.php
 * ( (  \.---./  ) )            | AUTHOR: byron
 *  '.__/o   o\__.'             | EMAIL: xiaobo.sun@qq.com
 *     {=  ^  =}                | QQ: 150093589
 *     /       \                | DATETIME: 2019/11/5 09:57
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

namespace think\addons;

use think\helper\Str;
use think\facade\Event;
use think\facade\Config;
use think\exception\HttpException;

class Route
{
    /**
     * 插件路由请求
     * @return mixed
     */
    public static function execute()
    {
        $app = app();
        $request = $app->request;

        $addon = $request->route('addon');
        $controller = $request->route('controller');
        $action = $request->route('action');

        if($request['module'] == 'admin' && $request['module'] !=app('http')->getName() ){
            throw new HttpException(500, lang('admin module url error'));
        }
        Event::trigger('addons_begin', $request);

        if (empty($addon) || empty($controller) || empty($action)) {
            throw new HttpException(500, lang('addon can not be empty'));
        }

        $request->addon = $addon;
        // 设置当前请求的控制器、操作
        $request->setController($controller)->setAction($action);

        // 获取插件基础信息
        $info = get_addons_info($addon);
        if (!$info) {
            throw new HttpException(404, lang('addon %s not found', [$addon]));
        }
        if (!$info['status']) {
            throw new HttpException(500, lang('addon %s is disabled', [$addon]));
        }

        // 监听addon_module_init
        Event::trigger('addon_module_init', $request);
        
   
        // 兼容插件多应用模式
        if(mb_strstr( $request->pathinfo(),$addon.'@')){
            $str_arr = explode('@', $request->pathinfo());
            $str_arr = explode('/', $str_arr[1]);
            $addon .= '\\'.$str_arr[0];
        }
        
        $class = get_addons_class($addon, 'controller', $controller);
        if (!$class) {
            throw new HttpException(404, lang('addon controller %s not found', [Str::studly($controller)]));
        }

      
        // 重写视图基础路径 已失效
        // $config = Config::get('view');
        // $config['view_path'] = $app->addons->getAddonsPath() . $addon . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR;
        // Config::set($config, 'view');

        // 生成控制器对象
        $instance = new $class($app);
        $vars = [];
        if (is_callable([$instance, $action])) {
            // 执行操作方法
            $call = [$instance, $action];
        } elseif (is_callable([$instance, '_empty'])) {
            // 空操作
            $call = [$instance, '_empty'];
            $vars = [$action];
        } else {
            // 操作不存在
            throw new HttpException(404, lang('addon action %s not found', [get_class($instance).'->'.$action.'()']));
        }
        Event::trigger('addons_action_begin', $call);

        return call_user_func_array($call, $vars);
    }
}