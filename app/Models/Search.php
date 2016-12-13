<?php

namespace App\Models;

use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Joomla\Registry\Registry;


/**
 * App\Models\Search
 *
 * @property integer $id
 * @property integer $user_id
 * @property string  $created_at
 * @property string  $keep_alive   Contiene la data e ora dell'ultimo segnale di keep_search_alive.
 * IMPORTANTE: Il keep search alive verrà per default inviato ogni tot secondi. Tuttavia è possibile che il server
 * modifiche il timer di A in base a determinate situazioni. Ad esempio: supponiamo che il keep_search_alive sia
 * inviato ogni 45 secondi e il tempo massimo disponbile da un utente B per rispondere sia di 35 secondi. Supponiamo
 * che A avvi la ricerca al tempo 0, S elabora la lista e prende allo stesso tempo il primo B della lista. Mettiamo
 * caso che B rifiuti al tempo 20 e S estrae il secondo B al tempo 22. Il nuovo 'utente B viene contattato ed avrà 35
 * secondi per rispondere, ovvero avrà come deadline il tempo 57. Al tempo 45 arriva il keep_search_alive di A. In
 * questo caso S non dovrà continuare la ricerca (estraendo un nuovo B) ma dovrà comunicare ad A di re-inviare il
 * keep_search_alive dopo 57 - 45 + scarto di 10 secondi. Ovvero S comunicherà ad A di re-inviare il keep_search_alive
 * dopo 22 secondi.
 * @property boolean $stopped      Se l'utente ha bloccato intenzionalmente la ricerca, questo campo contiene true
 *           (Questo campo non viene coinvolto qualora l'utente termina la ricerca per "durata massima")
 * @property integer $max_duration Durata massima, in secondi, della ricerca. E' dfinito dalle impostazione
 *           dell'utente.
 * @property integer $max_distance
 * @property string  $finished_at  Indica la data in cui è finita la ricerca. Se è stata stoppata dall'utente, conterrà
 *           lo stesso valore di stopped_at
 * @property boolean $success
 * @property boolean $timeout      Indica se la ricerca è stata terminata per timeout di tempo massimo.
 * @property float   $geo_latitude
 * @property float   $geo_longitude
 * @property string  $ip
 * @property string  $extra
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Search whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Search whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Search whereCreatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Search whereKeepAlive($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Search whereStopped($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Search whereMaxDuration($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Search whereMaxDistance($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Search whereFinishedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Search whereSuccess($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Search whereTimeout($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Search whereGeoLatitude($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Search whereGeoLongitude($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Search whereIp($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\Search whereExtra($value)
 * @mixin \Eloquent
 */
class Search extends Model
{

    // Numero di secondi dopo quanto bisogna inviare il keep alive
    const KEEP_ALIVE_INTERVAL  = 45;
    const KEEP_ALIVE_TOLERANCE = 0; //Per test su 0 //10; // Scarto di tolleranza

    const MAX_USER_RESPONSE_TIME = 40; // L'utente ha 40 secondi per rispondere nell'app

    public $timestamps = false;

	protected $attributes = [
		'extra' => '{}',
	];

	protected $extraRegistry;

    public function save(array $options = [])
    {
        if (!$this->exists && !$this->isDirty(static::CREATED_AT)) {
            $this->setCreatedAt($this->freshTimestamp());
        }

        if ($this->extraRegistry instanceof Registry) {
            $this->extra = $this->extraRegistry->toString();
        } else {
        	$this->extra = '{}';
        }

        return parent::save($options);
    }


    /**
     * @return Registry
     */
    public function extra()
    {
        if (!($this->extraRegistry instanceof Registry)) {
            if (isset($this->extra) && !empty($this->extra)) {
                $this->extraRegistry = new Registry($this->extra);
            } else {
                $this->extraRegistry = new Registry('{}');
            }
        }

        return $this->extraRegistry;
    }

    /**
     * @return SearchList
     * @throws Exception
     */
    public function createNewList()
    {
        if (!$this->exists || !$this->id) {
            throw new Exception('Non utilizzabile', 500);
        }

        $list            = new SearchList;
        $list->search_id = $this->id;
        $list->save();

        // La riempo
        $list->fillList();

        return $list;
    }


    /**
     * Ritorna true se la ricerca è finita, false altrimenti
     *
     * @return bool
     */
    public function isFinished()
    {
        return isset($this->finished_at) && !empty($this->finished_at) && Carbon::createFromFormat('Y-m-d H:i:s', $this->finished_at);
    }


    /**
     * Ritorna gli ID degli utenti che hanno rifiutato in ricerche precedenti entro un tot di tempo.
     *
     * @return array
     */
    public function usersWhoRefused()
    {
        // TODO: Da modificare come phpdoc nella funzione. Deve restituire tutti gli utenti che hanno rfiutato in ricerche avviate dall'utente entro un tot di tempo [ad esempio entro 12h]
        $ids =
            DB::table('search_lists')
                ->join('search_list_users', 'search_lists.id', '=', 'search_list_users.search_list_id')
                ->where('search_lists.search_id', '=', $this->id)
                ->where('search_list_users.response_type', '=', SearchListUser::RESPONSE_TYPE_REJECTED)
                ->pluck('search_list_users.user_id');

        return $ids;
    }

    public function userAlreadyFetched()
    {
        $ids =
            DB::table('search_lists')
                ->join('search_list_users', 'search_lists.id', '=', 'search_list_users.search_list_id')
                ->where('search_lists.search_id', '=', $this->id)
                ->whereNotNull('search_list_users.fetched_at')
                ->pluck('search_list_users.user_id');

        return $ids;
    }


    /**
     * @return SearchList|null
     */
    public function getLastList()
    {
        $last = $this
            ->hasMany('App\Models\SearchList')
            ->orderBy('created_at', 'desc')
            ->first();

        return $last;
    }


    /**
     * Prende il primo utente disponibile per l'ultima lista creata
     *
     * @return User|null
     */
    public function fetchUser()
    {
        $user = null;
        $list = $this->getLastList();
        if ($list instanceof SearchList) {
            $user = $list->fetchUser();

            if ($user instanceof User) {
                $this->setLastFetchedUserId($user->id);
                $this->setLastFetchDate(Carbon::now()->toDateTimeString());
                $this->save();
            }
        }

        return $user;
    }


    /**
     * Ritorna l'id dell'ultimo utente estratto
     *
     * @return int|null
     */
    public function getLastFetchedUserId()
    {
        return $this->extra()->get('last_fetch.user_id', null);
    }

    /**
     * Ritorna l'ultimo utente estratto
     *
     * @return User|null
     */
    public function getLastFetchedUser()
    {
        $userId = (int)$this->getLastFetchedUserId();
        $user   = User::whereId($userId)->first();

        return $user;
    }

    /**
     *
     * @param int  $userId
     * @param bool $saveData
     */
    public function setLastFetchedUserId($userId, $saveData = false)
    {
        $this->extra()->set('last_fetch.user_id', $userId);
        if ($saveData) {
            $this->save();
        }
    }


    /**
     * Ritorna la data dell'ultimo fetch
     *
     * @return string|null
     */
    public function getLastFetchDate()
    {
        return $this->extra()->get('last_fetch.date', null);
    }


    /**
     * @param string $date
     * @param bool   $saveData
     */
    public function setLastFetchDate($date, $saveData = false)
    {
        $this->extra()->set('last_fetch.date', $date);
        if ($saveData) {
            $this->save();
        }
    }

    /**
     * @param $userId
     * @param $saveData
     */
    public function setFoundUserId($userId, $saveData = false)
    {
        $this->extra()->set('found.user_id', $userId);
        if ($saveData) {
            $this->save();
        }
    }

    /**
     * Ritorna l'id dell'utente trovato per la ricerca
     *
     * @return int|null
     */
    public function getFoundUserId()
    {
        return $this->extra()->get('found.user_id', null);
    }

    /**
     * Elimina l'ultimo fetch, riportando lo status dell'utente a disponibile (se ancora su pendente)
     */
    public function flushLastFetch()
    {
        $user = $this->getLastFetchedUser();
        if (!empty($user)) {
            if ($user->status == User::STATUS_PENDENT) {
                $user->status = User::STATUS_AVAILABLE;
                $user->save();
            }
        }
        $this->setLastFetchedUserId(null);
        $this->setLastFetchDate(null);
        $this->save();
    }
}