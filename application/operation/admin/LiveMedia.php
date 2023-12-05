<?php
// +----------------------------------------------------------------------
// | 中犇单商户
// +----------------------------------------------------------------------
// | Copyright (c) 2019-2021 中犇科技 All rights reserved.
// +----------------------------------------------------------------------

namespace app\operation\admin;

use app\admin\admin\Base;
use service\Format;
use think\paginator\driver\Bootstrap;
use think\facade\Config;
use think\Db;

/**
 * 微信小程序素材管理
 * @package app\operation\admin
 */
class LiveMedia extends Base
{

    /**
     * 临时素材列表
     */
    public function index()
    {
        $data = Db::name('live_media')->order('id desc')->paginate()->each(function ($v) {
            $v['path'] = get_file_url($v['upload_id']);
            $v['status'] = '可用';
            if (time() - $v['create_time'] > 86400 * 3) {
                $v['status'] = '已失效';
            }
            return $v;
        });

        $fields = [
            ['id', lang('ID')],
            ['name', lang('素材名字')],
            ['media_id', lang('media_id')],
            ['type', lang('类型')],
            ['path', lang('图片'), 'picture'],
            ['status', lang('状态')],
            ['create_time', lang('上传时间'), 'callback', function ($data) {
                return date('Y-m-d H:i:s', $data);
            }]
        ];

        return Format::ins()//实例化
        ->hideCheckbox()
            ->addColumns($fields)//设置字段
            ->setTopButtons($this->top_button)
            ->setData($data)//设置数据
            ->setPages($pages)
            ->fetch();//显示
    }


    /**
     * 上传临时素材
     */
    public function add()
    {
        // 保存数据
        if ($this->request->isPost()) {
            $real_path = "{$_SERVER['DOCUMENT_ROOT']}";
            $name = $this->request->param("name", "");
            $filename = $real_path . '/uploads/sucai/' . $_FILES['media']['name'];
            if (!is_dir($real_path . '/uploads/sucai/')) {
                mkdir($real_path . '/uploads/sucai/', 0777, true);
            }
            try {
                move_uploaded_file($_FILES['media']['tmp_name'], $filename);
                $upres = $this->uploadPath($filename);
                $res = addons_action('WeChat/MiniPay/addmedia', $filename);
                if (isset($res['media_id'])) {
                    if ($upres['id']) {
                        $media_data = [
                            'upload_id' => $upres['id'],
                            'type' => $res['type'],
                            'media_id' => $res['media_id'],
                            'create_time' => time(),
                            'is_temp' => 1,
                            'name' => $name
                        ];
                        Db::name('live_media')->insert($media_data);
                    }
                    $this->success(lang('上传成功'), cookie('__forward__'));
                } else {
                    $this->error($res['errmsg']);
                }
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
        }
        $this->assign('form_items', $fields);
        return $this->fetch();
    }
}
