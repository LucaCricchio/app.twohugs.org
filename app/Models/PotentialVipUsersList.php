<?php

namespace App\Models;

use App\Helpers\Loggers\VipLogger;
use App\Models\VipRequest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;


class PotentialVipUsersList extends Model
{

    public function createPreviousMonthList()
    {
        // Creo istanza di ricerca
        VipLogger::debug("Creo lista potenziali utenti VIP...");

        // La riempo
        $potentialVipUsers =
            \DB::table('user_hug_feedbacks')
                ->select('user_id', 'users.username', \DB::raw('sum(result) as feedback_result'))
                ->join('hugs', 'user_hug_feedbacks.hug_id', '=', 'hugs.id')
                ->join('users', 'users.id', '=', 'hugs.user_seeker_id')
                ->whereMonth('hugs.created_at', "=", $this->month)
                ->whereYear('hugs.created_at', "=", $this->year)
                ->groupBy('user_id')
                ->orderBy('feedback_result', 'desc')
                ->limit(10)
                ->get();

        if(!empty($potentialVipUsers)) {
            $tuples = [];
            foreach ($potentialVipUsers AS $user) {
                /**
                 * @var \stdClass $user
                 */
                $tuples [] = [
                    'user_id'               => $user->user_id,
                    'positive_feedbacks'    => $user->feedback_result,
                    'potential_users_list_id' => $this->id,
                ];
            }
            DB::table('vip_requests')->insert($tuples);
            VipLogger::debug("Inseriti " . count($tuples) ." utenti selezionabili.");
        } else {
            VipLogger::debug("Nessun Utente VIP selezionabile.");
        }

    }

}
