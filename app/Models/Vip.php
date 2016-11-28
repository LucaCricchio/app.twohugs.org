<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;



/**
 * App\Models\Vip
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $created_at
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Vip whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Vip whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Vip whereCreatedAt($value)
 * @mixin \Eloquent
 */
class Vip extends Model
{
    //disabling autofilling timestamps fields (created_at and updated_at)
    public $timestamps = false;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'vips';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    //protected $fillable = ['name', 'email', 'password'];


    /**
     * Registra un nuovo Vip
     * @param $data
     */
    public function makeVip($userID)
    {
        $this->user_id = $userID;

        $this->created_at = Carbon::now()->toDateTimeString();

        $this->save();
    }



}
