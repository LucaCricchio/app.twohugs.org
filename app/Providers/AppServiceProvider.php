<?php

namespace App\Providers;

use App\Models\User;
use App\Models\UserHugFeedback;
use App\Observers\StatusObserver;
use DB;
use Illuminate\Support\ServiceProvider;
use Validator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Validator::extend('username', function($attribute, $value, $parameters, $validator) {
            return (boolean) preg_match("/^[a-zA-Z0-9_]+$/", $value);
        });
        Validator::extend('birth_date', function($attribute, $value, $parameters, $validator) {
            $date = \DateTime::createFromFormat("Y-m-d", $value);
            if($date instanceof \DateTime) {
                $err = $date->getLastErrors();
                if($err['warning_count'] == 0 && $err['error_count'] == 0) {
                    return true;
                }
            }
            return false;
        });
        Validator::extend('user.status', function($attribute, $value, $parameters, $validator) {
            switch($value) {
                // Gli unici status impostabile dall'app. Gli altri sono a scopo interno
                case User::STATUS_AVAILABLE:
                case User::STATUS_NOT_AVAILABLE:
                    return true;

                default:
            }
            return false;
        });
        Validator::extend('hug.feedback', function($attribute, $value, $parameters, $validator) {
            switch($value) {
                case UserHugFeedback::FEEDBACK_POSITIVE:
                case UserHugFeedback::FEEDBACK_NEUTRAL:
                case UserHugFeedback::FEEDBACK_NEGATIVE:
                    return true;

                default:
            }
            return false;
        });

        DB::listen(function ($sql) {
            // $sql is an object with the properties:
            //  sql: The query
            //  bindings: the sql query variables
            //  time: The execution time for the query
            //  connectionName: The name of the connection

            // To save the executed queries to file:
            // Process the sql and the bindings:
            foreach ($sql->bindings as $i => $binding) {
                if ($binding instanceof \DateTime) {
                    $sql->bindings[$i] = $binding->format('\'Y-m-d H:i:s\'');
                } else {
                    if (is_string($binding)) {
                        $sql->bindings[$i] = "'$binding'";
                    }
                }
            }

            // Insert bindings into query
            $query = str_replace(array('%', '?'), array('%%', '%s'), $sql->sql);

            $query = vsprintf($query, $sql->bindings);

            // Save the query to file
            $logFile = fopen(
                storage_path('logs' . DIRECTORY_SEPARATOR . date('Y-m-d') . '_query.log'),
                'a+'
            );
            fwrite($logFile, date('Y-m-d H:i:s') . ': ' . $query . PHP_EOL);
            fclose($logFile);
        });
        User::observe(StatusObserver::class);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
