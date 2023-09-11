<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Owner;

class Shop extends Model
{
    use HasFactory;

    //shopのテーブルの情報
    protected $fillable = [
        'owner_id',
        'name',
        'information',
        'filename',
        'is_selling'
    ];

    public function owner()
    {
        return $this->belongsTo(Owner::class);
    }
    
    //メソッド名相手の名前
    public function product()
    {
        //自分が1で相手が多の場合hasMany
        return $this->hasMany(Product::class);
    }
}
