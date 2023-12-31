<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\admin\admin;

use service\Sql;
use think\Db;

/**
 * 数据库操作
 * @package app\admin\admin
 */
class Database extends Base
{

    /**
     * 数据库备份/还原列表
     * @param String $type import-还原，export-备份
     * @return mixed|void
     * @throws \think\Exception
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function index($type = 'export')
    {
        switch ($type) {
            /* 数据还原 */
            case 'import':
                //列出备份文件列表
                $path = config('data_backup_path');
                if (!is_dir($path)) {
                    mkdir($path, 0755, true);
                }
                $path = realpath($path);
                $flag = \FilesystemIterator::KEY_AS_FILENAME;
                $glob = new \FilesystemIterator($path, $flag);

                $list = array();
                foreach ($glob as $name => $file) {
                    if (preg_match('/^\d{8,8}-\d{6,6}-\d+\.sql(?:\.gz)?$/', $name)) {
                        $name = sscanf($name, '%4s%2s%2s-%2s%2s%2s-%d');

                        $date = "{$name[0]}-{$name[1]}-{$name[2]}";
                        $time = "{$name[3]}:{$name[4]}:{$name[5]}";
                        $part = $name[6];

                        if (isset($list["{$date} {$time}"])) {
                            $info = $list["{$date} {$time}"];
                            $info['part'] = max($info['part'], $part);
                            $info['size'] = $info['size'] + $file->getSize();
                        } else {
                            $info['part'] = $part;
                            $info['size'] = $file->getSize();
                        }
                        $extension = strtoupper(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
                        $info['compress'] = ($extension === 'SQL') ? '-' : $extension;
                        $info['time'] = strtotime("{$date} {$time}");

                        $list["{$date} {$time}"] = $info;
                    }
                }
                $title = lang('数据还原');
                break;
            /* 数据备份 */
            case 'export':
                $Db = Db::connect();
                $list = $Db->query('SHOW TABLE STATUS');
                $list = array_map('array_change_key_case', $list);
                $title = lang('数据备份');
                break;
            default:
                return $this->error(lang('参数错误'));
        }
        //渲染模板
        $this->assign('page_title', $title);
        $this->assign('list', $list);
        $this->assign('type', $this->request->param('type'));
        return $this->fetch($type);
    }

    /**
     * 优化表
     * @param String $tables 表名
     * @throws \think\Exception
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */

    public function optimize($tables = null)
    {
        if ($tables) {
            $Db = Db::connect();
            if (is_array($tables)) {
                $tables = implode('`,`', $tables);
                $list = $Db->query("OPTIMIZE TABLE `{$tables}`");

                if ($list) {
                    // 记录行为
                    action_log('admin_database_optimize', 'database', 0, UID, $tables);
                    return $this->success(lang('数据表优化完成'));
                } else {
                    return $this->error(lang('数据表优化出错请重试'));
                }
            } else {
                $list = $Db->query("OPTIMIZE TABLE `{$tables}`");
                if ($list) {
                    // 记录行为
                    action_log('admin_database_optimize', 'database', 0, UID, $tables);
                    return $this->success(lang('数据表').$tables.lang('优化完成'));
                } else {
                    return $this->error(lang('数据表').$tables.lang('优化出错请重试'));
                }
            }
        } else {
            return $this->error(lang('请指定要优化的表'));
        }
    }

    /**
     * 修复表
     * @param String $tables 表名
     * @throws \think\Exception
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function repair($tables = null)
    {
        if ($tables) {
            $Db = Db::connect();
            if (is_array($tables)) {
                $tables = implode('`,`', $tables);
                $list = $Db->query("REPAIR TABLE `{$tables}`");

                if ($list) {
                    // 记录行为
                    action_log('admin_database_repair', 'database', 0, UID, $tables);
                    return $this->success(lang('数据表修复完成'));
                } else {
                    return $this->error(lang('数据表修复出错请重试'));
                }
            } else {
                $list = $Db->query("REPAIR TABLE `{$tables}`");
                if ($list) {
                    action_log('admin_database_repair', 'database', 0, UID, $tables);
                    return $this->success(lang('数据表').$tables.lang('修复完成'));
                } else {
                    return $this->error(lang('数据表').$tables.lang('修复出错请重试'));
                }
            }
        } else {
            return $this->error(lang('请指定要修复的表'));
        }
    }

    /**
     * 备份数据库
     * @param String $tables 表名
     * @param Integer $id 表ID
     * @param Integer $start 起始行数
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function export($tables = null, $id = null, $start = null)
    {
        if (request()->isPost() && !empty($tables) && is_array($tables)) {
            //初始化
            $path = config('data_backup_path');
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
            //读取备份配置
            $config = array('path' => realpath($path) . DIRECTORY_SEPARATOR, 'part' => config('data_backup_part_size'), 'compress' => config('data_backup_compress'), 'level' => config('data_backup_compress_level'));
            //检查是否有正在执行的任务
            $lock = "{$config['path']}backup.lock";
            if (is_file($lock)) {
                return $this->error(lang('检测到有一个备份任务正在执行，请稍后再试'));
            } else {
                //创建锁文件
                file_put_contents($lock, time());
            }
            //检查备份目录是否可写
            if (!is_writeable($config['path'])) {
                return $this->error(lang('备份目录不存在或不可写，请检查后重试'));
            }
            session('backup_config', $config);
            //生成备份文件信息
            $file = array('name' => date('Ymd-His', time()), 'part' => 1);
            session('backup_file', $file);
            //缓存要备份的表
            session('backup_tables', $tables);
            //创建备份文件
            $Database = new \service\Database($file, $config);
            if (false !== $Database->create()) {
                $tab = array('id' => 0, 'start' => 0);
                return $this->success(lang('初始化成功'), '', array('tables' => $tables, 'tab' => $tab));
            } else {
                return $this->error(lang('初始化失败，备份文件创建失败'));
            }
        } elseif (request()->isGet() && is_numeric($id) && is_numeric($start)) {
            //备份数据
            $tables = session('backup_tables');
            //备份指定表
            $Database = new \service\Database(session('backup_file'), session('backup_config'));
            $start = $Database->backup($tables[$id], $start);
            if (false === $start) {
                //出错
                return $this->error(lang('备份出错'));
            } elseif (0 === $start) {
                //下一表
                if (isset($tables[++$id])) {
                    $tab = array('id' => $id, 'start' => 0);
                    return $this->success(lang('备份完成'), '', array('tab' => $tab));
                } else {
                    //备份完成，清空缓存
                    unlink(session('backup_config.path') . 'backup.lock');
                    session('backup_tables', null);
                    session('backup_file', null);
                    session('backup_config', null);
                    // 记录行为
                    action_log('admin_database_export', 'database', 0, UID, implode(',', $tables));
                    return $this->success(lang('备份完成'));
                }
            } else {
                $tab = array('id' => $id, 'start' => $start[0]);
                $rate = floor(100 * ($start[0] / $start[1]));
                return $this->success(lang('正在备份')."...({$rate}%)", '', array('tab' => $tab));
            }
        } else {
            //出错
            return $this->error(lang('参数错误'));
        }
    }

    /**
     * 还原数据库
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function import($time = 0, $part = null, $start = null)
    {
        if (is_numeric($time) && is_null($part) && is_null($start)) {
            //初始化
            //获取备份文件信息
            $name = date('Ymd-His', $time) . '-*.sql*';
            $path = realpath(config('data_backup_path')) . DIRECTORY_SEPARATOR . $name;
            $files = glob($path);
            $list = array();
            foreach ($files as $name) {
                $basename = basename($name);
                $match = sscanf($basename, '%4s%2s%2s-%2s%2s%2s-%d');
                $gz = preg_match('/^\d{8,8}-\d{6,6}-\d+\.sql.gz$/', $basename);
                $list[$match[6]] = array($match[6], $name, $gz);
            }
            ksort($list);
            //检测文件正确性
            $last = end($list);
            if (count($list) === $last[0]) {
                session('backup_list', $list); //缓存备份列表
                return $this->success(lang('初始化完成，正在还原数据，请勿关闭页面'), '', array('part' => 1, 'start' => 0));
            } else {
                return $this->error(lang('备份文件可能已经损坏，请检查'));
            }
        } elseif (is_numeric($part) && is_numeric($start)) {
            $list = session('backup_list');

            $db = new \service\Database($list[$part], array('path' => realpath(config('data_backup_path')) . DIRECTORY_SEPARATOR, 'compress' => $list[$part][2]));

            $start = $db->import($start);

            if (false === $start) {
                return $this->error(lang('还原数据出错'));
            } elseif (0 === $start) {
                //下一卷
                if (isset($list[++$part])) {
                    $data = array('part' => $part, 'start' => 0);
                    return $this->success(lang('正在还原')."...#{$part}", '', $data);
                } else {
                    session('backup_list', null);
                    // 记录行为
                    action_log('admin_database_import', 'database', 0, UID, date('Ymd-His', $time));
                    return $this->success(lang('还原完成'));
                }
            } else {
                $data = array('part' => $part, 'start' => $start[0]);
                if ($start[1]) {
                    $rate = floor(100 * ($start[0] / $start[1]));
                    return $this->success(lang('正在还原')."...#{$part} ({$rate}%)", '', $data);
                } else {
                    $data['gz'] = 1;
                    return $this->success(lang('正在还原')."...#{$part}", '', $data);
                }
            }
        } else {
            return $this->error(lang('参数错误'));
        }
    }

    /**
     * 删除备份文件
     * @param Integer $time 备份时间
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function del($time = 0)
    {
        if ($time) {
            $name = date('Ymd-His', $time) . '-*.sql*';
            $path = realpath(config('data_backup_path')) . DIRECTORY_SEPARATOR . $name;
            array_map("unlink", glob($path));
            if (count(glob($path))) {
                return $this->error(lang('备份文件删除失败，请检查权限'));
            } else {
                // 记录行为
                action_log('admin_database_backup_delete', 'database', 0, UID, date('Ymd-His', $time));
                return $this->success(lang('备份文件删除成功'));
            }
        } else {
            return $this->error(lang('参数错误'));
        }
    }

    /**
     * 运行sql语句
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @created 2019/10/13 16:45
     */
    public function run_sql()
    {
        // 保存数据
        if ($this->request->isAjax()) {
            // 表单数据
            $data = input('param.');
            if ($data['type'] == 1) {
                $result = Db::query($data['sql']);
            }
            if ($data['type'] == 2) {
                // 启动事务
                Db::startTrans();
                try {
                    $sql = Sql::parseSql($data['sql']);
                    foreach ($sql as $v) {
                        $res = Db::execute($v);
                        if (!$res) {
                            exception($v . lang('执行失败'));
                        }
                    }
                    // 提交事务
                    Db::commit();
                } catch (\Exception $e) {
                    // 回滚事务
                    Db::rollback();
                    $this->error($e->getMessage());
                }
            }
            $this->success(lang('执行成功'), '', $result);
        }
        $list = $this->getDataBaseData();
        $this->assign('tableData', $list);
        $this->assign('page_title', '运行SQL语句');
        return $this->fetch();
    }

    private function getDataBaseData()
    {
        if (cache('tableListInfo')) {
            $data = cache('tableListInfo');
        } else {
            $data = [];
            $list = Db::query("select table_name,table_comment from information_schema.tables where table_schema = '" . config('database.database') . "'");
            foreach ($list as $item) {
                if (strpos($item['table_name'], 'admin') !== false) {
                    continue;
                }
                if (strpos($item['table_name'], 'addons') !== false) {
                    continue;
                }
                if (empty($item['table_comment'])) {
                    continue;
                }
//                $createTable = Db::query("show create table ".$item['Tables_in_jssb']);


                $tableFile = [];
                $filed = Db::query("SHOW FULL COLUMNS FROM " . $item['table_name']);
                foreach ($filed as $v) {
                    if (empty($v['Comment'])) {
                        $v['Comment'] = $v['Field'];
                    }
                    $tableFile[] = [
                        'name' => $v['Field'],
                        'value' => $v['Comment'],
                    ];
                }
                $data[$item['table_name']] = [
                    'table_name' => $item['table_name'],
                    'table_comment' => $item['table_comment'],
//                    'create_table' => $createTable[0]['Create Table'],
                    'fields' => $tableFile
                ];
            }
            cache('tableListInfo', $data, 7200);
        }
        return $data;
    }

    /**
     * Notes: 获取表字段
     * User: Fengxing
     * Date: 2020/8/24
     * Time: 17:38
     */
    public function getTableField()
    {
        $table_name = $this->request->param('table_name');

        $field = DB::query('SHOW FULL FIELDS FROM ' . $table_name);
        return json(['code' => 1, 'data' => $field]);
    }

    /**
     * Notes: 生成查询语句
     * User: Fengxing
     * Date: 2020/8/24
     * Time: 17:39
     */
    public function createSql()
    {
        $table_name = $this->request->param('table_name');
        $fields = $this->request->param('fileds');
        $select_type = $this->request->param('select_type');
        $keyword = $this->request->param('keyword');
        $show_fields = $this->request->param('show_fields','*');
        if(empty($show_fields) || $show_fields == '' || strlen($show_fields) <= 0){
            $show_fields_for = '*';
        }else{
            $show_fields_for = implode(',',$show_fields);
        }
        if ($select_type == 'like') {
            $keyword = "%{$keyword}%";
        }

        $sql = "SELECT {$show_fields_for} FROM {$table_name} WHERE {$fields} {$select_type} '{$keyword}'";

        return json(['code' => 1, 'data' => $sql]);
    }

    /**
     * Notes: 执行sql语句
     * User: Fengxing
     * Date: 2020/8/24
     * Time: 18:13
     */
    public function excuteSql()
    {
        $sql = $this->request->param('sql');
        $table_name = $this->request->param('table');
        $page = $this->request->param('page');
        $start = $page * 10;
        $sql = $sql . " LIMIT {$start},10";
        $list = Db::query($sql);
        $fields = DB::query('SHOW FULL FIELDS FROM ' . $table_name);
        return json(['code' => 1, 'data' => ['data'=>$list,'fields'=>$fields]]);
    }
}