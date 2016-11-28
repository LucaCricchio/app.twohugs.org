<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\SearchListUser
 *
 * @property integer $id
 * @property integer $search_list_id
 * @property integer $user_id
 * @property integer $order Rappresenta l'ordine degli utenti nella lista. Naturalmente è ordinata sullo stesso valore di search_list_id. L'ordine sarà crescente per distanza.
 * @property string $fetched_at Data di estrazione dell'elemento. Rappresenta la data e l'ora di quando l'utente è stato selezionato dalla lista. (E quindi la data di quando è stata inviata la richiesta)
 * @property string $responsed_at Rappresenta la data e ora di quando l'utente ha risposto alla richiesta. Resterà NULLA se non è avvenuta risposta.
 * @property boolean $response_type 1 = accettato, 2 = rifiutato, 3 = timeout connesso [ovvero l'app ha comunicato col server che l'utente non ha cliccato nè accetta, nè rifiuta].
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SearchListUser whereId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SearchListUser whereSearchListId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SearchListUser whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SearchListUser whereOrder($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SearchListUser whereFetchedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SearchListUser whereResponsedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Models\SearchListUser whereResponseType($value)
 * @mixin \Eloquent
 */
class SearchListUser extends Model
{

    const RESPONSE_TYPE_ACCEPTED = 1;
    const RESPONSE_TYPE_REJECTED = 2;
    const RESPONSE_TYPE_TIMEOUT  = 3;

    public $timestamps = false;


}