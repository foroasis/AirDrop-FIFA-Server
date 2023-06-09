<?php

namespace App\Models;


use Illuminate\Database\Eloquent\SoftDeletes;

class TgGroupUsersModel extends BaseModel
{

    use SoftDeletes;

    protected $table = 'tg_group_users';

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        // 'password', 'remember_token',
    ];

    // // 可被修改
    // protected $fillable = [
    //
    // ];

    // 不可填充
    protected $guarded = [
        'id'
    ];
}
