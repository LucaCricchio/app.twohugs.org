<?php


namespace App\Http\Controllers;


use App\Models\Hug;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;

class HugsController extends Controller
{
    /**
     * Ritorna la lista degli abbracci effettuati dall'utente.
     * Gestisce anche la paginazione automaticamente.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getList(Request $request)
    {
        $user = $this->getAuthenticatedUser();

        $hugs = Hug::where(function ($query) use ($request) {
            /**
             * @var Builder $query
             */
            $query
                ->whereNull('closed_at')// abbracci in corso (in teoria solo 1, salvo bug)
                ->orWhere(function ($query) {
                    /**
                     * @var Builder $query
                     */
                    $query
                        ->whereNotNull('closed_at')
                        ->where('closed_at', '>=', Carbon::now()->subHours(24)->toDateTimeString()); // Abbracci conclusi
                });
        })
            ->where(function ($query) use ($user) {
                /**
                 * @var Builder $query
                 */
                $query
                    ->where('user_seeker_id', '=', $user->id)
                    ->orWhere('user_sought_id', '=', $user->id);
            })
            ->paginate(20);

        $response       = json_decode($hugs->toJson());
        $response->list = $response->data;
        unset($response->data);

        return parent::response($response);
    }

}