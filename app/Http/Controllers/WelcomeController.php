<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WelcomeController extends Controller
{
    public function index()
    {
        // If user is logged in, redirect to their dashboard
        if (Auth::check()) {
            $role = Auth::user()->role;
            return redirect()->route($role . '.dashboard');
        }

        // Otherwise show the welcome page
        return view('welcome');
    }
}
