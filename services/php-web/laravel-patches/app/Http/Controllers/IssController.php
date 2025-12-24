<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class IssController extends Controller
{
    public function index()
    {
        return view('iss');
    }
}
