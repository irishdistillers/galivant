<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Boxen;

class BoxController extends Controller
{
    public function list_boxes($class){
        $output = Boxen::list_boxes($class);

        return view('list_boxes', ['name' => $class, 'boxes' => $output ]);

    }

    public function list_versions_json($class, $boxid){


        $output = Boxen::list_versions($class, $boxid);

        return response()->json($output);

    }

    public function list_versions($class, $boxid){


        $output = Boxen::list_versions($class, $boxid);

        return view('list_versions', $output);

    }
}
