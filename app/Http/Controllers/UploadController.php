<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    public function file(Request $request)
    {
        $file = $request->file('file_name');
        $options = ['disk' => 's3'];
        if($file->getClientOriginalExtension() == 'apk'){
            $options['Content-Type'] = 'application/vnd.android.package-archive';
        }
        $path = $file->storePubliclyAs('/' . date('Ymd') , date('His') . random_int(1, 1000). '.' . $file->getClientOriginalExtension(), $options );

        $path = config('filesystems.disks.s3.url') . $path;

        return $this->responseItem($path);
    }
}