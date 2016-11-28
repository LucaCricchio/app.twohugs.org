<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\UserHugFeedback
 *
 * @property integer $id
 * @property integer $user_id
 * @property integer $hug_id
 * @property string  $created_at
 * @property boolean $result 0 = Neutro, 1 = Positivo, -1 = Negativo
 * @method static \Illuminate\Database\Query\Builder|\App\Models\UserHugFeedback whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\UserHugFeedback whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\UserHugFeedback whereHugId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\UserHugFeedback whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\UserHugFeedback whereResult($value)
 * @mixin \Eloquent
 */
class UserHugFeedback extends Model
{

    const FEEDBACK_POSITIVE = 1;
    const FEEDBACK_NEUTRAL  = 0;
    const FEEDBACK_NEGATIVE = -1;

    public $timestamps = false;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'user_hug_feedbacks';

    public function save(array $options = [])
    {
        if (!$this->exists && !$this->isDirty(static::CREATED_AT)) {
            $this->setCreatedAt($this->freshTimestamp());
        }

        return parent::save($options);
    }

}
