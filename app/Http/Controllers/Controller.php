<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Shared base controller. Carries policy authorization for all
 * feature controllers (patients own data, staff same hospital).
 */
abstract class Controller
{
    use AuthorizesRequests;
}
