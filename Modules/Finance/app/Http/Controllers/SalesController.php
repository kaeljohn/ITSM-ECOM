<?php

namespace Modules\Finance\Http\Controllers;

use Illuminate\Routing\Controller;

class SalesController extends Controller
{
    public function index()
    {
        return view('finance::salesdash');
    }
}
