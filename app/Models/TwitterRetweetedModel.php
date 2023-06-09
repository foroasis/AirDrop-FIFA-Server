<?php

namespace App\Models;


class TwitterRetweetedModel extends BaseModel
{
    protected $table = 'twitter_retweeted';

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
