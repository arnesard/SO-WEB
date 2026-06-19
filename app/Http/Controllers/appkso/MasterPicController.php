<?php

namespace App\Http\Controllers\appkso;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MasterPicController extends Controller
{
    public function index()
    {
        $currentRoute = request()->route()->getName();
        return view('appkso.master_pic', compact('currentRoute'));
    }
}
