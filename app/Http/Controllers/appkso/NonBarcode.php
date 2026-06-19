<?php

namespace App\Http\Controllers\appkso;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NonBarcode extends Controller
{
    public function index()
    {
        // Ambil nama route biar class 'btn-warning' di navbar nyala
        $currentRoute = request()->route()->getName();

        // Mengarah ke resources/views/appkso/non_barcode.blade.php
        return view('appkso.non_barcode', compact('currentRoute'));
    }
}
