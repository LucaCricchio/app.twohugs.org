<?php

namespace App\Models;

use App\Http\Controllers\VipController;
use Hash;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Carbon\Carbon;
use League\Flysystem\Exception;


/**
 * App\Models\User
 *
 * @property integer $id
 * @property string $username
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $password
 * @property string $birth_date
 * @property string $gender
 * @property string $telephone
 * @property string $avatar Contiene il percorso relativo al profilo dell'utente.
 * @property string $facebook_user_id
 * @property string $google_user_id
 * @property string $activation_code Contiene il codice di attivazione quando l'utente si registra  con la propria email.
 * @property string $activation_date Contiene la data in cui l'utente ha attivato il proprio account
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $last_login
 * @property integer $country
 * @property string $city
 * @property string $address
 * @property string $zipcode
 * @property int $status Contiene lo stato dell'utente:
 * [0] => Non disponibile
 * [1] => Disponibile
 * [2] => Bloccato in attesa di risposta per un abbraccio (in pratica quando viene fatto il fetch)
 * [3] => Abbraccio in corso
 * [4] => In ricerca (sta cercando un abbraccio)
 * @property float $geo_latitude
 * @property float $geo_longitude
 * @property string $geo_last_update Contiene la data dell'ultimo aggiornamento riguardo la posizione.
 * @property boolean $blocked Contiene un boolean che descrivi se l'utente è stato bloccato o meno. (E' utile per bloccare un account utente)
 * @property boolean $completed Indica se il profilo dell'utente è completo (i profili non completi non possono né ricerca né essere trovati)
 * @property string $gcm_device_id
 * @property integer $max_duration
 * @property integer $max_distance
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereUsername($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereFirstName($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereLastName($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereEmail($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User wherePassword($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereBirthDate($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereGender($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereTelephone($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereAvatar($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereFacebookUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereGoogleUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereActivationCode($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereActivationDate($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereLastLogin($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereCountry($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereCity($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereAddress($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereZipcode($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereStatus($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereGeoLatitude($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereGeoLongitude($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereGeoLastUpdate($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereBlocked($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereCompleted($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereGcmDeviceId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereMaxDuration($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereMaxDistance($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\User whereParentEmail($value)
 * @mixin \Eloquent
 */
class User extends Model implements AuthenticatableContract,
                                    AuthorizableContract,
                                    CanResetPasswordContract
{
    use Authenticatable, Authorizable, CanResetPassword;


    // Non disponibile ad abbracciare
    const STATUS_NOT_AVAILABLE = 0;

    // Disponibile ad abbracci [Ricerca passiva]
    const STATUS_AVAILABLE     = 1;

    // Bloccato in attesa di risposta per un abbraccio (in pratica quando viene fatto il fetch)
    const STATUS_PENDENT       = 2;

    // L'utente ha un abbraccio in corso [Nella room di abbraccio]
    const STATUS_HUGGING       = 3;

    // In ricerca di un abbraccio [Ricerca Attiva]
    const STATUS_SEARCHING     = 4;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'email', 'password'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token'];



    public function toArray()
    {
        $attributes = parent::toArray();
        //aggiungo un campo is_vip ogni qualvolta restituisco l'utente.
        $attributes["is_vip"] = $this->is_vip;
        $attributes['hugs'] = $this->getClosedhugs()->count();
        $attributes['feedbacks'] = $this->getReceivedFeedbacks();

        return $attributes;
    }
    //accessor to set is_vip value
     public function getIsVipAttribute($value)
    {
        return VipController::isVip($this->id);
    }


    /**
     * Registra un nuovo utente
     * @param $data
     */
    public function register($data)
    {
        $this->username        = $data['username'];
        $this->email           = strtolower($data['email']);
        $this->password        = Hash::make($data['password']);
        $this->status          = 0;

        $this->activation_code = $this->createActivationCode();

        $this->save();
    }


    /**
     * Crea un codice di attivazione unico.
     * @return string
     */
    protected function createActivationCode()
    {
        do {
            $code = str_random(100);
            // Mi assicuro che la stringa creata è unica
            $user = self::whereActivationCode($code)->first();
        } while (!empty($user));

        return $code;
    }

    /**
     * Aggiorno la posizione dell'utente.
     * @param $geoLatitude
     * @param $geoLongitude
     */
    public function updatePosition($geoLatitude, $geoLongitude)
    {
        $this->geo_latitude = $geoLatitude;
        $this->geo_longitude = $geoLongitude;
        $this->geo_last_update = Carbon::now()->toDateTimeString();

        $this->save();
    }

    public function getClosedhugs(){

        return  Hug::whereNotNull('closed_at')->where(function ($query) {
            /**
             * @var Builder $query
             */
            $query
                ->where('user_seeker_id', '=', $this->id)
                ->orWhere('user_sought_id', '=', $this->id);
        })->get();

    }

    //return the no. of positive, neutral and  negative feedback received by the user
    public function getReceivedFeedbacks(){

        $receivedFeedbacks =
            \DB::table('user_hug_feedbacks')
                ->select('result')
                ->selectRaw('count(*) as number')
                ->join('hugs', 'user_hug_feedbacks.hug_id', '=', 'hugs.id')
                ->join('users', 'users.id', '=', 'hugs.user_seeker_id')
                ->whereUserId($this->id)
                ->groupBy('result')
                ->get();

        $result['positive'] = $result['negative'] = $result['neutral'] = 0;

        foreach ($receivedFeedbacks AS $feedback) {
            switch ($feedback->result) {
                case UserHugFeedback::FEEDBACK_POSITIVE:
                    $result['positive'] = $feedback->number;
                    break;
                case UserHugFeedback::FEEDBACK_NEGATIVE:
                    $result['negative'] = $feedback->number;
                    break;
                case UserHugFeedback::FEEDBACK_NEUTRAL:
                    $result['neutral'] = $feedback->number;
                    break;
            }
        }

        return $result;
    }

}
