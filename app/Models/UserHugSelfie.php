<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\UserHugSelfie
 *
 * @property integer $id
 * @property integer $hug_id
 * @property integer $user_id
 * @property string $created_at
 * @property string $file_path Contiene il percorso relativo fino all'immagine.
 * @property string $file_name
 * @property integer $file_size Numero di byte del file
 * @method static \Illuminate\Database\Query\Builder|\App\Models\UserHugSelfie whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\UserHugSelfie whereHugId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\UserHugSelfie whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\UserHugSelfie whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\UserHugSelfie whereFilePath($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\UserHugSelfie whereFileName($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\UserHugSelfie whereFileSize($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\UserHugSelfie whereTimedOut($value)
 * @mixin \Eloquent
 */
class UserHugSelfie extends Model
{

    public $timestamps = false;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'user_hug_selfies';

    public function save(array $options = [])
    {
        if (!$this->exists && !$this->isDirty(static::CREATED_AT)) {
            $this->setCreatedAt($this->freshTimestamp());
        }

        return parent::save($options);
    }

}
