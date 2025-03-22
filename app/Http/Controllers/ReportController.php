<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\Device;
use App\Models\Transfer;
use App\Models\EmiDetail;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index');
    }

    public function sales(Request $request)
    {
        return view('reports.sales');
    }

    public function inventory(Request $request)
    {
        return view('reports.inventory');
    }

    public function transfers(Request $request)
    {
        return view('reports.transfers');
    }

    public function emis(Request $request)
    {
        return view('reports.emis');
    }
}
