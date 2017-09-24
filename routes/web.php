<?php


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function(){
    return view('welcome');
});

/** USER */
Route::group(['prefix' => 'user'], function () {

    Route::post('register', 'UserController@register');
    Route::post('changeStatus', 'UserController@changeStatus');

    // Ritorna i dati del profilo dell'utente appena loggato
    Route::get('profile', 'ProfileController@get');
    // Aggiorna i dati del profilo dell'utente loggato
    Route::post('profile', 'ProfileController@update');

    Route::post('setGCMToken', 'UserController@setGCMToken');

    // testing purpose only //
    Route::get('send-notification/{id}', 'VipController@sendNotification');
    Route::get('{id}', 'UserController@get');

    //aggiorna la posizione dell'utente
    Route::post('updatePosition', 'UserController@updatePosition');


    Route::get('avatar', 'ProfileController@getAvatar');
    Route::post('avatar', 'ProfileController@setAvatar');

    Route::post('updatePassword', 'ProfileController@changePasswordOnTheFly');
});

/** AUTHENTICATION */
Route::group(['prefix' => 'auth'], function () {

    Route::post('login', 'Auth\AuthController@login');
    Route::post('loginWithFacebook', 'Auth\AuthController@loginWithFacebook');
    Route::post('loginWithGooglePlus', 'Auth\AuthController@loginWithGooglePlus');
    Route::get('activate/{code}', [
        'as'   => 'auth.activate',
        'uses' => 'Auth\AuthController@activate',
    ]);

});

/** SEARCH */
Route::group(['prefix' => 'search', 'as' => 'search.'], function () {

    Route::post('begin', 'SearchController@begin')->name('begin');
    Route::post('proceed', 'SearchController@proceed')->name('proceed');
    Route::post('stop', 'SearchController@stop')->name('stop');

    Route::group(['prefix' => 'userResponse', 'as' => 'userResponse.'], function () {

        Route::post('accept', 'SearchController@userResponse')->name('accept');
        Route::post('reject', 'SearchController@userResponse')->name('reject');
        Route::post('noResponse', 'SearchController@userResponse')->name('noResponse');

    });

});

/** HUG */
Route::group(['prefix' => 'hugs', 'as' => 'hugs.'], function () {

    Route::post('', 'HugsController@getList')->name('getList'); //todo: da get a post, temporaneo per facilitare Andrea
    Route::post('{id}', 'HugController@get')->name('getHug')->where(['id' => '[0-9]+']); //todo: da get a post, temporaneo per facilitare Andrea

	// {id} fa conflitto con create e (forse anche checkForHug). Onestamente non so perchè. Indagheremo dopo
    Route::post('checkForHug', 'HugController@checkForHug');

    Route::post('create', 'HugController@createHug')->name('create');
    Route::post('{id}/join', 'HugController@joinHug')->name('join');
    Route::post('{id}/refresh', 'HugController@refresh')->name('refresh');
    Route::post('{id}/close', 'HugController@close')->name('close');

	Route::post('{id}/sendSelfie', 'HugController@sendSelfie')->name('sendSelfie');
	Route::get('{id}/getSelfie', 'HugController@getSelfie')->name('getSelfie');
	Route::get('{id}/downloadSelfie', 'HugController@downloadSelfie')->name('downloadSelfie');
    Route::post('{id}/feedback', 'HugController@setFeedback')->name('setFeedback');

    Route::post('sendWhoAreYouRequest', 'HugController@whoAreYou'); //UserController@sendWhoAreYouRequest


});

Route::group(['prefix' => 'chats', 'as' => 'chats.'], function() {
    Route::get('/', 'ChatController@getChatForUser');
    Route::post('/', 'ChatController@newChat');
    Route::get('/withMessages', 'ChatController@getChatMessagesWithLastMessage');
    Route::get('{chat}', 'ChatController@getChatMessages');
    Route::post('{chat}/send', 'ChatController@sendMessage');
});

/** VIP */
Route::group(['prefix' => 'vip'], function () {
    Route::post('createMonthList', 'VipController@createMonthVipList');
    Route::post('sendNextVipProposal', 'VipController@sendNextVipProposal');

    //get last activities (posts) from current VIP
    Route::get('activities', 'VipController@getCurrentVipActivities');

    Route::post('accept', 'VipController@accept');

    Route::post('decline', 'VipController@decline');

    //solo per test
    Route::get('makevip/{id}', 'VipController@makevip');
});



Route::group(['prefix' => 'utils'], function () {

    Route::post('countryList', 'HelperController@getCountryList');
    Route::get('getUsers', 'UserController@getAll');

});


// per i test
//todo: [TEST] solo per testare le mail
Route::get('/send/mail', function (){

    $user = \App\User::findOrFail(1021);


    Mail::send('emails.vip', ['user' => $user], function ($m) use ($user) {
        $m->from('test@twohugs.org', 'Twohugs Test');
        $m->to("luca.cricchio@gmail.com", $user->name)->subject('Test mail');
    });

    return "mail inviata";
});

Route::get('/simulator', function () {
    return view('simulator');
});
Route::group(['prefix' => 'debug'], function () {
    Route::get('logs', '\Rap2hpoutre\LaravelLogViewer\LogViewerController@index');
    Route::group(['prefix' => 'clear'], function () {
        Route::get('/', function () {
            \App\Models\Hug::truncate();
            \App\Models\Search::truncate();
            \App\Models\SearchList::truncate();
            \App\Models\SearchListUser::truncate();
            \App\Models\UserHugFeedback::truncate();
            \App\Models\UserSearchTimeout::truncate();
            DB::table('users')
                ->where('status', '!=', 0)
                ->update([
                    'status' => 0
                ]);
            return "Cleared";
        });
        Route::get('searches', function() {
            \App\Models\Search::truncate();
            \App\Models\SearchList::truncate();
            \App\Models\SearchListUser::truncate();
            return "Cleared";
        });
        Route::get('statuses', function() {
            DB::table('users')
              ->where('status', '!=', 0)
              ->update([
                  'status' => 0
              ]);
            return "Cleared";
        });
    });
});
