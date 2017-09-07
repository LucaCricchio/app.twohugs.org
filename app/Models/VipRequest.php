<?php

namespace App\Models;

use App\PotentialVipUserList;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;



/**
 * App\Models\VipRequest
 *
 * @property integer $id
 * @property integer $user_id
 * @property string $created_at
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Vip whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Vip whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Vip whereCreatedAt($value)
 * @mixin \Eloquent
 */
class VipRequest extends Model
{
    const VIP_REQUEST_ACCEPT = 1;
    const VIP_REQUEST_DECLINE = -1;

    //disabling autofilling timestamps fields (created_at and updated_at)
    public $timestamps = false;


    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'vip_requests';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    //protected $fillable = ['name', 'email', 'password'];


    /**
     *
     * @param $userID
     */
    public function decline($userID)
    {
        $this->user_id = $userID;

        $this->month = Carbon::now()->month;
        $this->year = Carbon::now()->year;
        $this->responsed_at = Carbon::now()->toDateTimeString();
        $this->response_type = self::VIP_REQUEST_DECLINE;

        $this->save();
    }

    /**
     *
     * @param $userID
     */
    public function accept($userID)
    {
        $this->user_id = $userID;

        $this->month = Carbon::now()->month;
        $this->year = Carbon::now()->year;
        $this->responsed_at = Carbon::now()->toDateTimeString();
        $this->response_type = self::VIP_REQUEST_ACCEPT;

        $this->save();
    }





}
