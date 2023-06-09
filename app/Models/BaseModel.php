<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;

class BaseModel extends Model{
    public function __construct(array $attributes = []){
        parent::__construct($attributes);
    }
}
