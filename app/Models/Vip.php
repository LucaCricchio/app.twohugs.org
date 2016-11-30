<?php

namespace App\Models;

use App\Models\User;
use App\Models\VipPost;
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
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Vip whereActive($value)
 * @mixin \Eloquent
 */
class Vip extends Model
{
    //disabling autofilling timestamps fields (created_at and updated_at)
    public $timestamps = false;

    protected $id;


    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'vips';


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

    /**
     * Recupera i Post dell'utente vip
     */
    public function posts(){
        return $this->hasMany('App\Models\VipPost');
    }

    /**
     *  Load user data
     */
    public function user(){
        return $this->belongsTo('App\Models\User');
    }

}
