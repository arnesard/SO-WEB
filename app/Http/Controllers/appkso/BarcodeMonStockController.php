<?php

namespace App\Http\Controllers\appkso;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class BarcodeMonStockController extends Controller
{
    public function index()
    {
        $currentRoute = request()->route()->getName();
        return view('appkso.barcode_mon_stock', compact('currentRoute'));
    }
}
