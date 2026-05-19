<?php

namespace App\Http\Controllers\dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OracleVsFisikController extends Controller
{
    public function index()
    {
        return view('dashboard.oracle_vs_fisik.oracle_vs_fisik');
    }

    public function switchMenu(Request $request)
    {
        $menu = $request->query('menu');

        $viewPath = match ($menu) {
            'master_size'        => 'dashboard.oracle_vs_fisik.oracle_vs_fisik_master_size',
            'oracle_snapshot'    => 'dashboard.oracle_vs_fisik.oracle_vs_fisik_oracle_snapshot',
            'barcode_monitoring' => 'dashboard.oracle_vs_fisik.oracle_vs_fisik_barcode_monstock',
            'tagstock'           => 'dashboard.oracle_vs_fisik.oracle_vs_fisik_tagstock',
            'appkso'             => 'dashboard.oracle_vs_fisik.oracle_vs_fisik_appkso',
            'pic'                => 'dashboard.oracle_vs_fisik.oracle_vs_fisik_pic',
            'default'            => 'dashboard.oracle_vs_fisik.oracle_vs_fisik_dashboard',
            default              => 'dashboard.oracle_vs_fisik.oracle_vs_fisik_dashboard',
        };

        return view($viewPath)->render();
    }
}
