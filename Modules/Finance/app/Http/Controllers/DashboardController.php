<?php

namespace Modules\Finance\Http\Controllers;

use Illuminate\Routing\Controller;

class DashboardController extends Controller
{
    public function shell()
    {
        return view('finance::dashboard');
    }

    public function overview()
    {
        return view('finance::maindash');
    }
}
