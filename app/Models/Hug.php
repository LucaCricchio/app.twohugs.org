<?php

namespace App\Models;


use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;


/**
 * App\Models\Hug
 *
 * @property integer $id
 * @property string $created_at
 * @property integer $search_id
 * @property integer $user_seeker_id Contiene l'ID dell'utente che ha avviato la ricerca
 * @property integer $user_sought_id Contiene L'id dell'utente B che ha accettato l'abbraccio [che è stato cercato]
 * @property string $user_seeker_last_checkin Indica la data dell'ultimo accesso/segnale inviato dall'utente dall'interno della stanza dell'abbraccio. Questo campo è necessario per verificare che l'utente sia attivo nella stanza dell'abbraccio.
 * @property string $user_sought_last_checkin
 * @property string $closed_at Indica quando è stato chiuso l'abbraccio. Può essere chiuso da uno dei due utenti. Viene registrata la data  del primo utente che lo chiude
 * @property integer $closed_by Contiene l'id dell'utente che ha chiuso l'abbraccio.
 * @property string $code Contiene un codice univoco per l'abbraccio. Utile per visualizzarlo agli utenti
 * @property string $user_seeker_who_are_you_request Contiene la data della richiesta di who are you
 * @property string $user_sought_who_are_you_request
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Hug whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Hug whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Hug whereSearchId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Hug whereUserSeekerId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Hug whereUserSoughtId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Hug whereUserSeekerLastCheckin($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Hug whereUserSoughtLastCheckin($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Hug whereClosedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Hug whereClosedBy($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Hug whereCode($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Hug whereUserSeekerWhoAreYouRequest($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Hug whereUserSoughtWhoAreYouRequest($value)
 * @mixin \Eloquent
 * @property integer $timed_out_user_id Id dell'utente andando in timeout. Se un utente non manda più il segnale di checkIn per un tempo specifico, l'altro utente, al momento del refresh, chiuderà in automatico l'abbraccio.
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Hug whereTimedOutUserId($value)
 */
class Hug extends Model
{

    const MAX_CHECK_IN_DELAY = 180; // Un utente può ritardare massimo di 180 secondi nell'inviare un checkIn

    public $timestamps = false;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'hugs';


    public function save(array $options = [])
    {
        if (!$this->exists && !$this->isDirty(static::CREATED_AT)) {
            $this->setCreatedAt($this->freshTimestamp());

            // Genero il codice.
            $this->code = sha1(Carbon::now()->getTimestamp());
        }

        return parent::save($options);
    }

    /**
     * Chiude l'abbraccio
     *
     * @param User $closedBy
     * @param null $timedOutUserId
     */
    public function close(User $closedBy, $timedOutUserId = null)
    {
        $this->closed_at = Carbon::now()->toDateTimeString();
        $this->closed_by = $closedBy->id;

        if($timedOutUserId) {
            $this->timed_out_user_id = $timedOutUserId;
        }

        $this->save();
    }

	/**
	 * @return \Illuminate\Database\Eloquent\Relations\HasMany
	 */
    public function selfies()
    {
    	return $this->hasMany(UserHugSelfie::class);
    }

}
