<?php

namespace app\common\model;

use think\Model;

class Group extends Model
{
    protected $hidden = ['create_by', 'update_by','create_time', 'update_time', 'delete_time'];
}
