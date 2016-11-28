<?php
/**
 * @Author: Luca
 *
 */

namespace App\Http\Controllers;


class HelperController extends Controller
{

    public function getCountryList(){

        $countryList = \DB::table('countries')->get();

        return parent::response([
            "countryList" => $countryList,
        ]);

    }

}