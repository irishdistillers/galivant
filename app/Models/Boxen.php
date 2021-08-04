<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Boxen extends Model
{
    use HasFactory;

    public static function list_boxes($class){

        $s3 = \AWS::createClient('s3', [ 'region' => "eu-west-1" ]);
        $res = $s3->listObjectsV2(array(
            'Bucket'     => env('VAGRANT_BOXES_BUCKET', ''),
            'Prefix'     => $class.'/'
        ));

        $boxes = [];


        foreach($res['Contents'] as $object){
            $key = $object['Key'];
            $filename = substr($key,0,-4);
            [$boxname, $provider, $date] = explode("_", $filename);

            $boxes[] = $boxname;

        }

        return array_unique($boxes);
    }

    public static function list_versions($class = false, $boxid = false){

        if($boxid){
            $desc = $prefix = "$class/$boxid";
        } elseif($class){
            $prefix = "$class/";
            $desc = "Boxes for $class";
        } else {
            $prefix = false;
            $desc = "All boxes";
        }

        $s3 = \AWS::createClient('s3', [ 'region' => "eu-west-1" ]);
        $res = $s3->listObjectsV2(array(
            'Bucket'     => env('VAGRANT_BOXES_BUCKET', ''),
            'Prefix'     => $prefix
        ));

        $output = [
            "description" => "$class $boxid",
            "short_description" => "$class $boxid",
            "name" => "$class/$boxid",
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
            $date_arr = explode('-', $date);

            $version = implode($date_arr).".0.0";

            $versions[$version]['version'] = $version;
            $versions[$version]['status'] = 'active';
            $versions[$version]['description_html'] = null;
            $versions[$version]['description_markdown'] = null;

            $presign_cmd = $s3->getCommand('GetObject', [
                'Bucket' => env('VAGRANT_BOXES_BUCKET', ''),
                'Key' => $key
            ]);
            $presigned = $s3->createPresignedRequest($presign_cmd, '+10 minutes');

            $versions[$version]['providers'][] = [
                'name' => $provider,
                'url'  => (string)$presigned->getUri(),
                'checksum' => null,
                'checksum_type' => null,
            ];
        }

        foreach($versions as $version){
            $output['versions'][] = $version;
        }

        return $output;
    }
}
