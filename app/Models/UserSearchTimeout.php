<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\UserSearchTimeout
 *
 * @property integer $id
 * @property integer $search_id
 * @property integer $user_id
 * @property string $timed_out_at
 * @method static \Illuminate\Database\Query\Builder|\App\Models\UserSearchTimeout whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\UserSearchTimeout whereSearchId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\UserSearchTimeout whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\UserSearchTimeout whereTimedOutAt($value)
 * @mixin \Eloquent
 */
class UserSearchTimeout extends Model
{

    public $timestamps = false;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'user_search_timeouts';

}
