<?php

namespace App\Http\Controllers;

use App\Models\ReportTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AnalyticController extends Controller
{
    public function index(){
           return Inertia::render('Analytics', [
            'reportTemplates' => ReportTemplate::latest()->get(),
        ]);
    }
}
