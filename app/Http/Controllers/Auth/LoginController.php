<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use App\Services\InterestSyncService;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = RouteServiceProvider::HOME;

    public function __construct()
    {
        $this->middleware("guest")->except("logout");
    }

    /**
     * The user has been authenticated.
     * Trigger interest sync to catch up missed calculations.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function authenticated(\Illuminate\Http\Request $request, $user)
    {
        try {
            $syncRun = InterestSyncService::syncOnLogin($user->id);
            Log::info("Interest sync triggered on login", [
                "user_id" => $user->id,
                "sync_run_id" => $syncRun->id,
                "status" => $syncRun->status,
            ]);
        } catch (\Exception $e) {
            // Log but don't block login
            Log::error("Interest sync failed on login: " . $e->getMessage(), [
                "user_id" => $user->id,
            ]);
        }
    }
}
