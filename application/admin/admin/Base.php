<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------


namespace app\admin\admin;

use app\common\controller\Common;
use app\admin\model\Menu as MenuModel;
use app\goods\model\ActivityDetails;
use app\goods\model\GoodsStockLog;
use app\goods\model\Goods;
use app\user\model\MoneyLog;
use app\user\model\ScoreLog;
use app\user\model\User;
use service\ApiReturn;
use think\Db;
use app\admin\model\Role;
use app\goods\model\ActivityDetails as ActivityDetailsModel;

/**
 * 后台公共控制器
 * @package app\admin\controller
 */
class Base extends Common
{
    use \app\common\traits\controller\Upload;//继承中间件
    /**
     * 初始化
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    protected function initialize()
    {
        parent::initialize();
        // 判断是否登录，并定义用户ID常量
        defined('UID') or define('UID', $this->isLogin());
        // 如果不是ajax请求，则读取菜单
        if (!$this->request->isAjax()) {
            // 读取顶部菜单
            $this->assign('topMenus', MenuModel::getTopMenu(config('top_menu_max'), 'topMenus'));
            // echo "<pre>";
            // print_r(MenuModel::getTopMenu(config('top_menu_max')));die;

            // 读取全部顶级菜单
            $this->assign('topMenusAll', MenuModel::getTopMenu('', 'topMenusAll'));
            // 获取侧边栏菜单
            $sidebarMenus = MenuModel::getSidebarMenu();

            $this->assign('sidebarMenus', $sidebarMenus);
            $this->assign('sidebarJson', json_encode($sidebarMenus));
            // 获取面包屑导航
            $this->assign('location', MenuModel::getLocation('', true));
        }
        //批量操作路径
        $form_action = '/' . $this->request->module() . '/' . $this->request->controller() . '/setStatus';
        $this->assign('action', $form_action);
        //判断权限
        //user/index/setstatus  =>  user/index/disable 
        if($this->request->action() == 'setstatus' && $this->request->param('type')) {
            $current_url_value = strtolower($this->request->module() . '/' . $this->request->controller() . '/' . $this->request->param('type'));
        } else {
            $current_url_value = strtolower($this->request->module() . '/' . $this->request->controller() . '/' .  $this->request->action());
        }
        $current_menu_id = MenuModel::getMenusByUrl($current_url_value);
        if ($current_menu_id) {
            if (!Role::checkAuth($current_menu_id)) {
                $this->error(lang('无权访问'));
            }
        }

        //多语言
        $lang_select = $_COOKIE['language']??'';
        $this->assign('lang_select', $lang_select);
        $this->assign('lang_array', lang_array());
    }


    /**
     * 检查是否登录，没有登录则跳转到登录页面
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @return int
     */
    final protected function isLogin()
    {
        // 判断是否登录
        if ($uid = is_signin()) {
            // 已登录
            return $uid;
        } else {
            // 未登录
            $this->redirect('admin/login/signin');
        }
    }

    /**
     * 清空缓存
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    final protected function clearCache()
    {
        if (!empty(config('cache_type'))) {
            foreach (config('cache_type') as $item) {
                if ($item == 'ALL') {
                    //清空整个缓存
                    \Cache::clear();
                    return true;
                } else if ($item == 'LOG_PATH') {
                    $dirs = (array)glob(constant($item) . '*');
                    foreach ($dirs as $dir) {
                        array_map('unlink', glob($dir . '/*.log'));
                    }
                    array_map('rmdir', $dirs);
                } else if ($item == 'USER_LOGIN') {
                    \Cache::rmbatch('user_token_*');
                } else if ($item == 'CACHE_PATH') {
                    \Cache::rmbatch('apiFields_*');//接口字段缓存
                    \Cache::rmbatch('apiInfo_*');//接口缓存
                    \Cache::rmbatch('apiRule_*');//接口验证缓存
                    \Cache::rmbatch('topMenus*');//顶部菜单缓存
                    \Cache::rmbatch('topMenusAll*');//顶部菜单缓存
                    \Cache::rmbatch('sidebar_menus_*');//侧边菜单缓存
                    \Cache::rmbatch('location_menu_*');//面包屑菜单缓存
                    \Cache::rm('addons_all');//插件
                } else {
                    array_map('unlink', glob(constant($item) . '/*.*'));
                }
            }
            return true;
        } else {
            $this->error(lang('请在系统设置中选择需要清除的缓存类型'));
        }
    }

    /**
     * 获取当前操作模型
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     * @return object|\think\db\Query
     */
    final protected function getCurrModel()
    {
        $table_token = input('param._t', '');
        $module = $this->request->module();
        $controller = parse_name($this->request->controller());
        if ($table_token) {
            !session('?' . $table_token) && $this->error(lang('参数错误'));
            $table_data = session($table_token);
            $table = $table_data['table'];
        } else {
            $table = input('param.model');
            $table_data['prefix'] = 1;
            $table_data['module'] = input('param.module');
            $table_data['controller'] = input('param.controller');
        }

        $table == '' && $this->error(lang('参数错误'));

        $Model = null;
        if ($table_data['prefix'] == 2) {
            // 使用模型
            try {
                $Model = Loader::model($table);
            } catch (\Exception $e) {
                $this->error(lang('找不到模型') . '：' . $table);
            }
        } else {
            // 使用DB类
            $table == '' && $this->error(lang('缺少表名'));
            if ($table_data['module'] != $module || $table_data['controller'] != $controller) {
                $this->error(lang('非法操作'));
            }

            $Model = $table_data['prefix'] == 0 ? Db::table($table) : Db::name($table);
        }

        return $Model;
    }

    /**
     * 快速编辑
     * @param array $record 行为日志内容
     * @author 刘明美 [ liumingmei@zhongbenjituan.com ]
     */
    public function quickEdit($record = [])
    {
        $field = input('post.name', '');
        $value = input('post.value', '');
        $type = input('post.type', '');
        $id = input('post.pk', '');
        $validate = input('post.validate', '');
        $validate_fields = input('post.validate_fields', '');
        if (empty($value) && $value != 0) {
            $this->error(lang('更改的内容不能为空'));
        }
        $field == '' && $this->error(lang('缺少字段名'));
        $id == '' && $this->error(lang('缺少主键值'));

        $Model = $this->getCurrModel();
        $table = $Model->getTable();
        //设置文章点击量
        if (is_numeric($value) === true) {
            if (strlen($value) > 8) {
                $this->error(lang('数据字符过长'));
            }
        }
        //记录会员余额变更记录
        if ($table == 'lb_user' && $field == 'user_money') {
            if (strlen($value) > 8) {
                $this->error(lang('设置金额失败'));
            }
            $info = User::get($id);
            $before_money = $info['user_money'];
            $money = $value - $before_money;
            $type = 3;
            $remark = lang('系统快速变更');
            $user_id = $id;
            MoneyLog::changeMoney($user_id, $before_money, $money, $type, $remark);
        }
        //记录会员积分变更记录
        if ($table == 'lb_user' && $field == 'score') {
            $info = User::get($id);
            $before_score = $info['score'];
            $score = $value - $before_score;
            $type = 6;
            $remark = lang('系统快速变更');
            $user_id = $id;
            ScoreLog::change($user_id, $score, $type, $remark);
        }
        if ($field == 'activity_price' || $field == 'member_activity_price' || $field == 'sales_integral') {
            $Model = new ActivityDetails();
        }
        //记录库存变更记录
        if ($table == 'lb_goods' && $field == 'stock') {
            $info = Goods::get($id);
            $goods_id = $id;
            $stock = $info['stock'];
            if ($stock == $value) {
                // 如果相同证明没有改动,跳出记录
                goto break_stock;
            }
            if ($stock > $value) {
                $type = 2;
            } else {
                $type = 1;
            }
            $stock_change = abs($stock - $value);
            $sku_id = 0;
            $order_sn = '';
            $remark = lang('管理员操作');
            $operator = UID;
            GoodsStockLog::AddStockLog($goods_id, $sku_id, $order_sn, $stock, $stock_change, $value, $type, $operator, $remark, $info['sn']);
        }
        //快速编辑判断,正参与活动的商品不能下架
        if ($table == 'lb_goods' && $field == 'is_sale' &&  $value=='false' ) {
            if (ActivityDetailsModel::isActivityGoods($id)) {
                $this->error(lang('商品有参与活动，不能下架'));
            }
        }

        break_stock:
        $protect_table = [
            '__ADMIN_USER__',
            '__ADMIN_ROLE__',
            config('database.prefix') . 'admin_user',
            config('database.prefix') . 'admin_role',
        ];

        // 验证是否操作管理员
        if (in_array($Model->getTable(), $protect_table) && $id == 1) {
            $this->error(lang('禁止操作超级管理员'));
        }

        // 验证器
        if ($validate != '') {
            $validate_fields = array_flip(explode(',', $validate_fields));
            if (isset($validate_fields[$field])) {
                $result = $this->validate([$field => $value], $validate . '.' . $field);
                if (true !== $result) $this->error($result);
            }
        }

        switch ($type) {
            // 日期时间需要转为时间戳
            case 'combodate':
                $value = strtotime($value);
                break;
            // 开关
            case 'switch':
                $value = $value == 'true' ? 1 : 0;
                break;
            // 开关
            case 'password':
                $value = Hash::make((string)$value);
                break;
        }
        // 主键名
        $pk = $Model->getPk();
        $result = $Model->where($pk, $id)->setField($field, $value);
        cache('hook_plugins', null);
        cache('system_config', null);
        cache('access_menus', null);
        if (false !== $result) {
            // 记录行为日志
            if (!empty($record)) {
                call_user_func_array('action_log', $record);
            }
            $this->success(lang('操作成功'));
        } else {
            $this->error(lang('操作失败'));
        }
    }

    protected function exportExcel($expTitle, $expCellName, $expTableData)
    {
        include_once '../vendor/PHPExcel/PHPExcel.php';//方法二
        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//文件名称
        $fileName = $expTitle . date('_YmdHis');//or $xlsTitle 文件名称可根据自己情况设定
        $cellNum = count($expCellName);

        //$objPHPExcel = new PHPExcel();//方法一
        $objPHPExcel = new \PHPExcel();//方法二
        $cellName = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');
        $objPHPExcel->getActiveSheet()->mergeCells('A1:' . $cellName[$cellNum - 1] . '1');//合并单元格

        foreach ($expTableData as $k => $v) {
            $objPHPExcel->createSheet($k);
            $dataNum = count($v['list']);
//            $objPHPExcel->getActiveSheet($k)->mergeCells('A1:' . $cellName[$cellNum - 1] . '1');//合并单元格
            $objPHPExcel->setActiveSheetIndex($k)->setCellValue('A1', $expTitle . '  导出时间:' . date('Y-m-d H:i:s'));
//            $objPHPExcel->getActiveSheet($k)->setTitle($v['sender']);
            for ($i = 0; $i < $cellNum; $i++) {
                $objPHPExcel->setActiveSheetIndex($k)->setCellValue($cellName[$i] . '2', $expCellName[$i][1]);
            }
            // Miscellaneous glyphs, UTF-8
            for ($i = 0; $i < $dataNum; $i++) {
                for ($j = 0; $j < $cellNum; $j++) {
                    $objPHPExcel->getActiveSheet($k)->setCellValue($cellName[$j] . ($i + 3), $v['list'][$i][$expCellName[$j][0]]);
                }
            }
        }
        ob_end_clean();//这一步非常关键，用来清除缓冲区防止导出的excel乱码
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=utf-8;name="' . $xlsTitle . '.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");//"xls"参考下一条备注
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');//"Excel2007"生成2007版本的xlsx，"Excel5"生成2003版本的xls
        $objWriter->save('php://output');
        exit;
    }

    /**
     * 无限极分类
     * @param type $ids
     * @return boolean
     */

    public function generateTree($data, $map = '')
    {
        $items = $tree = [];
        foreach ($data as &$v) {
            $items[$v['id']] = $v;
        }
        foreach ($items as $k => $item) {
            if (isset($items[$item['pid']])) {
                $items[$item['pid']]['children'][] = &$items[$k];
            } else {
                $tree[] = &$items[$k];
            }
            if (isset($items[$k]['thumb']) && $map == 'goods_cate') {
                $items[$k]['thumb_img'] = get_file_url($items[$k]['thumb']);
            }
        }

        return $tree;

    }

    /*
     * base64图片上传
     *
     */
    public function base64_upload()
    {
        $base64_img = trim($_POST['img_base64']);
        $up_dir = './uploads/';//存放在当前目录的upload文件夹下

        if (!file_exists($up_dir)) {
            mkdir($up_dir, 0777);
        }

        if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_img, $result)) {
            $type = $result[2];
            if (in_array($type, array('pjpeg', 'jpeg', 'jpg', 'gif', 'bmp', 'png'))) {
                $time = date('YmdHis_');
                $name = $time . '.' . $type;
                $path = '/uploads/' . $name;
                $thumb_path = 'uploads/' . $name;
                $thumb_save_path = 'uploads/' . 'thumb_' . $time . '.' . $type;
                $new_file = $up_dir . $name;
                if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $base64_img)))) {
                    //使用trait Upload 上传，本地和oss都支持
                    $res = $this->uploadPath($new_file);
                    if ($res) {
                        $data = [
                            'id' => $res['id'],
                            'path' => $this->_getFileUrl($res['path']),
                        ];
                        return ApiReturn::r(1, $data, 'ok');
                    } else {
                        return ApiReturn::r(0, [], 'error');
                    }
                } else {
                    return ApiReturn::r(0, [], 'error');

                }
            } else {
                //文件类型错误
                return ApiReturn::r(0, [], lang('图片上传类型错误'));
            }

        } else {
            return ApiReturn::r(0, [], lang('文件错误'));
        }
    }

    protected function _getFileUrl($file_path)
    {
        if (!$file_path) {
            return "";
        }
        $parse_url = parse_url($file_path);
        if (!empty($parse_url['scheme'])) {
            return $file_path;
        }
        return config('web_site_domain') . $file_path;
    }

    /*
*
* 删除字符串所有空格
*
*/
    public function trimAll($str)
    {
        $oldchar = array(" ", "　", "\t", "\n", "\r");
        $newchar = array("", "", "", "", "");
        return str_replace($oldchar, $newchar, $str);
    }
}