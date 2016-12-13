<?php
/**
 * @Author: Luca
 *
 */

namespace App\Helpers;


class ErrorCode
{
	const UNKNOWN                     = "1000";
	const VALIDATION                  = "1001";
	const LOGIN_FAILED                = "1002";
	const USER_NOT_AUTHORISED         = "1003";
	const INVALID_USER_RESPONSE       = "1004"; //in realtà sarebbe comunque una invalid request, meglio abbbondare
	const INVALID_REQUEST             = "1005";
	const PREVIOUS_SEARCH_IN_PROGRESS = "1006";

}