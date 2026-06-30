<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Redirect to the contractors index page.
     *
     * Temporary: the dashboard will be implemented here in the future.
     *
     * @return RedirectResponse
     */
    public function index()
    {
        return redirect()->route('contractors.index');
    }
}
