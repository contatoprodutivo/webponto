<?php

namespace App\Http\Controllers\Auth;
use Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    // protected $redirectTo = '/home';
    protected function authenticated(Request $request) 
    {
        $type = \Auth::user()->acc_type;
        if ($type == 1) 
        {
            return redirect('personal/dashboard');
        } 
        if ($type == 2) 
        {
            return redirect('dashboard');
        } 
        if ($type == 7) 
        {
            return redirect('clock');
        }
        if ($type == null || $type == 0) 
        {
            return redirect('login');
        }
    }

    protected function credentials(\Illuminate\Http\Request $request) 
    {
         return ['email' => $request->{$this->username()}, 'password' => $request->password, 'status' => 1];
    }

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function logout() 
    {
        Auth::logout();
        return redirect('login'); 
    }
}
