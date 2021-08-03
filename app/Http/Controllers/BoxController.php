<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class BoxController extends Controller
{
    public function show($class, $boxid){

        define("BOXES_BUCKET", env('VAGRANT_BOXES_BUCKET', ''));

        $s3 = \AWS::createClient('s3', [ 'region' => "eu-west-1" ]);
        $res = $s3->listObjectsV2(array(
            'Bucket'     => BOXES_BUCKET,
            'Prefix'     => "$class/$boxid",
            // 'Key'        => 'YOUR_OBJECT_KEY',
            // 'SourceFile' => '/the/path/to/the/file/you/are/uploading.ext',
        ));

        $output = [
            "name" => "$class/$boxid",
            "description" => "$class $boxid",
            "versions"=> [

            ]
        ];

        $v = 0;

        $versions = [];

        foreach($res['Contents'] as $object){
            $key = $object['Key'];
            $filename = substr($key,0,-4);

            $v++;
            [$boxname, $provider, $date] = explode("_", $filename);
            [$year, $month, $day] = explode('-', $date);
            $version = implode(".", [intval($year), intval($month), intval($day)]);
            $versions[$version]['version'] = $version;

            $presign_cmd = $s3->getCommand('GetObject', [
                'Bucket' => BOXES_BUCKET,
                'Key' => $key
            ]);
            $presigned = $s3->createPresignedRequest($presign_cmd, '+10 minutes');

            $versions[$version]['providers'] = [
                'name' => $provider,
                'url'  => (string)$presigned->getUri()
            ];
        }

        foreach($versions as $version){
            $output['versions'][] = $version;
        }

        return response()->json($output);

    }
}
