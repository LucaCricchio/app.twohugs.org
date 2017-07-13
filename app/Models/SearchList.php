<?php

namespace App\Models;

use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Database\Eloquent\Model;


/**
 * App\Models\SearchList
 *
 * @property integer $id
 * @property integer $search_id
 * @property string  $created_at
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SearchList whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SearchList whereSearchId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SearchList whereCreatedAt($value)
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\SearchListUser[] $fetchUser
 * @mixin \Eloquent
 */
class SearchList extends Model
{

    const MAX_USERS_FOR_LIST = 5;
    const OLD_LIST_AFTER_SECONDS = 120; // Dopo quanti secondi una lista è considerata "vecchia"

    public $timestamps = false;

    public function save(array $options = [])
    {
        if (!$this->exists && !$this->isDirty(static::CREATED_AT)) {
            $this->setCreatedAt($this->freshTimestamp());
        }

        return parent::save($options);
    }


    /**
     * Algoritmo di riempimento di una lista. E' quello che decide quali utenti scegliere per la ricerca
     *
     * @throws Exception
     */
    public function fillList()
    {
        if (!$this->exists) {
            throw new Exception('Non utilizzabile', 500);
        }

        /**
         * @var Search $search
         */
        $search      = Search::whereId($this->search_id)->first();
        $lastTimeout = Carbon::now()->subMinutes(5)->toDateTimeString();
	    //$lastUpdate  = Carbon::now()->subMinutes(10)->toDateTimeString(); // TODO: Da fixare in produzione
	    $lastUpdate  = Carbon::now()->subHours(240)->toDateTimeString();

	    $usersWhoRefused     = $search->usersWhoRefused();
        $usersAlreadyFetched = $search->userAlreadyFetched();


        $users =
            DB::table('users AS USER')
                ->leftJoin('user_search_timeouts AS USER_TIMEOUT', 'USER_TIMEOUT.user_id', '=', 'USER.id')
                ->selectRaw(
                	implode(", ", [
                	    "USER.id",
	                    sprintf("haversine(USER.geo_latitude, USER.geo_longitude, %s, %s) AS distance", $search->geo_latitude, $search->geo_longitude),
	                    "MAX(USER_TIMEOUT.timed_out_at) AS last_timeout"
	                ]))
                ->where('USER.geo_last_update', '>', DB::getPdo()->quote($lastUpdate))
                ->whereRaw('haversine(USER.geo_latitude, USER.geo_longitude, 10, 20) <= ' . (float)$search->max_distance)// TODO: La distanza bisogna passarla in km
                ->whereNotIn('USER.id', $usersWhoRefused)
                ->whereNotIn('USER.id', $usersAlreadyFetched)
                ->whereNotNull('USER.gcm_device_id')
                ->where('USER.id', '<>', $search->user_id)
                ->where('USER.status', '=', User::STATUS_AVAILABLE)
                ->groupBy('USER.id')
                ->havingRaw('last_timeout < ' . DB::getPdo()->quote($lastTimeout) . ' OR last_timeout IS NULL')
                ->orderBy('distance')
                ->limit(self::MAX_USERS_FOR_LIST)
                ->get();

        if (!empty($users)) {
            $tuples = [];
            foreach ($users as $i => $user) {
                /**
                 * @var \stdClass $user
                 */
                $tuples [] = [
                    'search_list_id' => $this->id,
                    'user_id'        => $user->id,
                    'order'          => $i + 1,
                ];
            }
            DB::table('search_list_users')->insert($tuples);
        }
    }

    /**
     * @return User|null
     */
    public function fetchUser()
    {
        $listUser = $this
            ->hasMany('\App\Models\SearchListUser')
            ->join('users', 'search_list_users.user_id', '=', 'users.id')
            ->whereNull('fetched_at')
            ->whereNotNull('users.gcm_device_id')
            ->where('users.status', '=', User::STATUS_AVAILABLE)
            ->orderBy('order')
            ->first(['search_list_users.*']);

        if ($listUser instanceof SearchListUser && $listUser->user_id > 0) {
            $listUser->fetched_at = Carbon::now()->toDateTimeString();
            $listUser->save();

            $user         = User::whereId($listUser->user_id)->first();
            $user->status = User::STATUS_PENDENT;
            $user->save();

            return $user;
        } else {
            return null;
        }
    }
}