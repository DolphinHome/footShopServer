<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\admin\model;

use think\Model;

/**
 * 插件模型
 * @package app\admin\model
 */
class Addons extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = '__ADDONS__';

    // 自动写入时间戳
    protected $autoWriteTimestamp = true;

    // 写入时处理config
    public function setConfigAttr($value)
    {
        return !empty($value) ? json_encode($value) : '';
    }

    /**
     * 获取所有插件信息
     * @param string $keyword 查找关键词
     * @param string $status 查找状态
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @return array|mixed
     */
    public function getAll($keyword = '', $status = '')
    {
        $result = cache('addons_all');
        if (!$result) {
            // 获取插件目录下的所有插件目录
            $dirs = array_map('basename', glob(ROOT_PATH . 'addons/'.'*', GLOB_ONLYDIR));
            if ($dirs === false || !file_exists(ROOT_PATH . 'addons/')) {
                $this->error = lang('插件目录不可读或者不存在');
                return false;
            }
            // 读取数据库插件表
            $addons = $this->order('sort asc,id desc')->column(true, 'name');

            // 读取未安装的插件
            foreach ($dirs as $plugin) {
                if (!isset($addons[$plugin])) {
                    $addons[$plugin]['name'] = $plugin;

                    // 获取插件类名
                    $class = get_addons_class($plugin);

                    // 插件类不存在则跳过实例化
                    if (!class_exists($class)) {
                        // 插件的入口文件不存在！
                        $addons[$plugin]['status'] = '-2';
                        continue;
                    }

                    // 实例化插件
                    $obj = new $class;

                    // 插件插件信息缺失
                    if (!isset($obj->info) || empty($obj->info)) {
                        // 插件信息缺失！
                        $addons[$plugin]['status'] = '-3';
                        continue;
                    }

                    // 插件插件信息不完整
                    if (!$this->checkInfo($obj->info)) {
                        $addons[$plugin]['status'] = '-4';
                        continue;
                    }

                    // 插件未安装
                    $addons[$plugin] = $obj->info;
                    $addons[$plugin]['status'] = '-1';

                }
            }

            // 数量统计
            $total = [
                'all' => count($addons), // 所有插件数量
                '-2'  => 0,               // 错误插件数量
                '-1'  => 0,               // 未安装数量
                '0'   => 0,               // 未启用数量
                '1'   => 0,               // 已启用数量
            ];

            // 过滤查询结果和统计数量
            foreach ($addons as $key => $value) {
                // 统计数量
                if (in_array($value['status'], ['-2', '-3', '-4'])) {
                    // 已损坏数量
                    $total['-2']++;
                } else {
                    $total[(string)$value['status']]++;
                }

                // 过滤查询
                if ($status != '') {
                    if ($status == '-2') {
                        // 过滤掉非已损坏的插件
                        if (!in_array($value['status'], ['-2', '-3', '-4'])) {
                            unset($addons[$key]);
                            continue;
                        }
                    } else if ($value['status'] != $status) {
                        unset($addons[$key]);
                        continue;
                    }
                }
                if ($keyword != '') {
                    if (stristr($value['name'], $keyword) === false && (!isset($value['title']) || stristr($value['title'], $keyword) === false) && (!isset($value['author']) || stristr($value['author'], $keyword) === false)) {
                        unset($addons[$key]);
                        continue;
                    }
                }
            }

            // 处理状态及插件按钮
            foreach ($addons as &$plugin) {
                switch ($plugin['status']) {
                    case '-4': // 插件信息不完整
                        $plugin['title'] = lang('插件信息不完整');
                        $plugin['bg_color'] = 'danger';
                        $plugin['status_class'] = 'text-danger';
                        $plugin['status_info'] = '<i class="fa fa-times"></i> 已损坏';
                        $plugin['actions'] = '<button class="btn btn-xs btn-noborder btn-default" type="button" disabled>不可操作</button>';
                        break;
                    case '-3': // 插件信息缺失
                        $plugin['title'] = lang('插件信息缺失');
                        $plugin['bg_color'] = 'danger';
                        $plugin['status_class'] = 'text-danger';
                        $plugin['status_info'] = '<i class="fa fa-times"></i> 已损坏';
                        $plugin['actions'] = '<button class="btn btn-xs btn-noborder btn-default " type="button" disabled>不可操作</button>';
                        break;
                    case '-2': // 入口文件不存在
                        $plugin['title'] = lang('入口文件不存在');
                        $plugin['bg_color'] = 'danger';
                        $plugin['status_class'] = 'text-danger';
                        $plugin['status_info'] = '<i class="fa fa-times"></i> 已损坏';
                        $plugin['actions'] = '<button class="btn btn-xs btn-noborder btn-default " type="button" disabled>不可操作</button>';
                        break;
                    case '-1': // 未安装
                        $plugin['bg_color'] = 'info';
                        $plugin['actions'] = '<a class="btn btn-xs btn-noborder btn-default ajax-get confirm " href="'.url('install', ['name' => $plugin['name']]).'">安装</a>';
                        $plugin['status_class'] = 'text-info';
                        $plugin['status_info'] = '<i class="fa fa-fw fa-th-large"></i> 未安装';
                        break;
                    case '0': // 禁用
                        $plugin['bg_color'] = 'warning';
                        $plugin['actions'] = '<a class="btn btn-xs btn-noborder btn-default ajax-get confirm " href="'.url('enable', ['ids' => $plugin['id']]).'"><i class="fa fa-check-circle pr5"></i> 启用</a> ';
                        $plugin['actions'] .= '<a class="btn btn-xs btn-noborder btn-default ajax-get confirm " data-tips="如果包括数据库，将同时删除数据库！" href="'.url('uninstall', ['name' => $plugin['name']]).'"><i class="fa fa-recycle pr5"></i> 卸载</a> ';
                        if (isset($plugin['config']) && $plugin['config'] != '') {
                            $plugin['actions'] .= '<a class="btn btn-xs btn-noborder btn-default " href="'.url('config', ['name' => $plugin['name']]).'"><i class="fa fa-cog pr5"></i> 设置</a> ';
                        }
                        if ($plugin['admin'] != '0') {
                            $plugin['actions'] .= '<a class="btn btn-xs btn-noborder btn-default  " href="'.url('manage', ['name' => $plugin['name']]).'"><i class="fa fa-list pr5"></i> 管理</a> ';
                        }
                        $plugin['status_class'] = 'text-warning';
                        $plugin['status_info'] = '<i class="fa fa-ban"></i> 已禁用';
                        break;
                    case '1': // 启用
                        $plugin['bg_color'] = 'success';
                        $plugin['actions'] = '<a class="btn btn-xs btn-noborder btn-default ajax-get confirm " href="'.url('disable', ['ids' => $plugin['id']]).'"><i class="fa fa-ban pr5"></i> 禁用</a> ';
                        $plugin['actions'] .= '<a class="btn btn-xs btn-noborder btn-default ajax-get confirm " data-tips="如果包括数据库，将同时删除数据库！" href="'.url('uninstall', ['name' => $plugin['name']]).'"><i class="fa fa-recycle pr5"></i> 卸载</a> ';
                        if (isset($plugin['config']) && $plugin['config'] != '') {
                            $plugin['actions'] .= '<a class="btn btn-xs btn-noborder btn-default " href="'.url('config', ['name' => $plugin['name']]).'"><i class="fa fa-cog pr5"></i> 设置</a> ';
                        }
                        if ($plugin['admin'] != '0') {
                            $plugin['actions'] .= '<a class="btn btn-xs btn-noborder btn-default " href="'.url('manage', ['name' => $plugin['name']]).'"><i class="fa fa-list pr5"></i> 管理</a> ';
                        }
                        $plugin['status_class'] = 'text-success';
                        $plugin['status_info'] = '<i class="fa fa-check"></i> 已启用';
                        break;
                    default: // 未知
                        $plugin['title'] = lang('未知');
                        break;
                }
            }

            $result = ['total' => $total, 'addons' => $addons];
            // 非开发模式，缓存数据
            if (config('develop_mode') == 0) {
                cache('addons_all', $result);
            }
        }
        return $result;
    }

    /**
     * 检查插件插件信息是否完整
     * @param string $info 插件插件信息
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @return bool
     */
    private function checkInfo($info = '')
    {
        $default_item = ['name','title','author','version'];
        foreach ($default_item as $item) {
            if (!isset($info[$item]) || $info[$item] == '') {
                return false;
            }
        }
        return true;
    }

    /**
     * 获取插件配置
     * @param string $name 插件名称
     * @param string $item 指定返回的插件配置项
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @return array|bool|mixed|string
     */
    public static function getConfig($name = '', $item = '')
    {
        $config = cache('addons_config_'.$name);
        if (!$config) {
            $config = self::where('name', $name)->value('config');
            if (!$config) {
                return [];
            }

            $config = json_decode($config, true);
            // 非开发模式，缓存数据
            if (config('develop_mode') == 0) {
                cache('addons_config_'.$name, $config);
            }
        }

        if (!empty($item)) {
            $items = explode(',', $item);
            if (count($items) == 1) {
                return isset($config[$item]) ? $config[$item] : '';
            }

            $result = [];
            foreach ($items as $item) {
                $result[$item] = isset($config[$item]) ? $config[$item] : '';
            }
            return $result;
        }
        return $config;
    }

    /**
     * 设置插件配置
     * @param string $name 插件名.配置名
     * @param string $value 配置值
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @return bool
     */
    public static function setConfig($name = '', $value = '')
    {
        $item = '';
        if (strpos($name, '.')) {
            list($name, $item) = explode('.', $name);
        }

        // 获取缓存
        $config = cache('addons_config_'.$name);

        if (!$config) {
            $config = self::where('name', $name)->value('config');
            if (!$config) {
                return false;
            }

            $config = json_decode($config, true);
        }

        if ($item === '') {
            // 批量更新
            if (!is_array($value) || empty($value)) {
                // 值的格式错误，必须为数组
                return false;
            }

            $config = array_merge($config, $value);
        } else {
            // 更新单个值
            $config[$item] = $value;
        }

        if (false === self::where('name', $name)->setField('config', json_encode($config))) {
            return false;
        }

        // 非开发模式，缓存数据
        if (config('develop_mode') == 0) {
            cache('addons_config_'.$name, $config);
        }

        return true;
    }
}
