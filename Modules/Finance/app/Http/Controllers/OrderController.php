<?php

namespace Modules\Finance\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class OrderController extends Controller
{
    public function store(Request $request)
    {
        return redirect()->back()->with('info', 'Order creation not yet implemented.');
    }
}
