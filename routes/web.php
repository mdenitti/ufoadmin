<?php

use Illuminate\Support\Facades\Route;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Intervention\Image\Facades\Image;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {

    $html = "<h1 style='color:red'>Aliens</h1>";

    return view('welcome', [
        'html' => $html
    ]);
});

Route::get('/date' , function(){
    $date = Carbon::now();
    $date->locale('nl_BE'); // Set the locale to Dutch (Belgium)

    echo $date->isoFormat('LL');
    echo $date->isoFormat('LLLL');
    echo "<hr>";
    echo $date->toDateString();
});


Route::group(['prefix' => 'admin'], function () {
    Voyager::routes();
    // not secure... not adding any new routes
});

// default a route login and redirect to admin/login
Route::get('/login', function () {
    return redirect('/admin/login');
})->name('login');


// create export csv routes that are protected by auth middlewar
Route::get('/export/csv', function(){
    // get all aliens and eager load the abilities
    $aliens = App\Models\Alien::with('abilities')->get();
    // foreach the aliens into a csv file without using a third party package
    $csvEport = "";
    foreach ($aliens as $alien) {
        // export the alien name, email and location and implode the abilities
        $csvEport .= $alien->name . ",".$alien->email.",".$alien->location.",".implode(",",$alien->abilities->pluck('name')->toArray())."\n";
        // $csvEport .= $alien->name . ",".$alien->email.",".$alien->location."\n";
    }
    // return the csv file
    return response($csvEport, 200, [
        'Content-Type' => 'text/csv',
        'Content-Disposition' => 'attachment; filename="aliensExport.csv"'
    ]);
})->middleware('auth')->name('export');

// alternative using laracsv package

Route::get('/export/csv2', function(){
    $aliens = App\Models\Alien::with('abilities')->get();
    $csvExporter = new \Laracsv\Export();
    $csvExporter->build($aliens, ['email', 'location'])->download();
})->middleware('auth')->name('export2');

Route::post('/export/csv3', function(Request $request){
   
    //dd($request->all());

    $startDate = Carbon::parse($request->startDate);
    $endDate = Carbon::parse($request->endDate);

    if ($request->startDate == null || $request->endDate == null) {
        $aliens = App\Models\Alien::with('abilities')->get();
    } else {
        $aliens = App\Models\Alien::with('abilities')
        ->whereBetween('created_at', [$startDate, $endDate])
        ->get();
    }
    $csvExporter = new \Laracsv\Export();
    $csvExporter->build($aliens, ['email', 'location'])->download();

})->middleware('auth')->name('export3');


// upload route 
Route::post('/upload',function(Request $request) {

    // dd($request->file->getClientOriginalName());

    $fileName = $request->file->getClientOriginalName();
    $request->file->move(public_path('storage'), $fileName);

    $request->file->store('public');
    $request->file->storeAs('public', 'test.jpg');

    // return succes response
    return response()->json(['success'=>'You have successfully upload file.']);


})->middleware('auth')->name('upload');

// second way to protect routes with auth middleware using a group function
/* Route::middleware('auth')->group(function () {
    Route::get('/test', function(){
        return "test";
    });
   
    Route::get('/export/csv', function(){
        return "export aliens";
    });

    Route::get('/export/pdf', function(){
        return "export aliens";
    });
}); */

// route post upload file
// route::post('/upload', function(request $request) {
//     $fileName = $request->file->getClientOriginalName();
//     $request->file->move(public_path('storage'), $fileName);
//     return response()->json(['success' => 'The file has been successfully uploaded, motherfucker!']);

// })-> middleware('auth')->name('upload');

route::post('/upload', function(request $request) {
    try {
        // Validate file MIME type
        $validatedData = $request->validate([
            'file' => 'required|mimes:jpeg,png,gif',
        ]);

        // Handle file upload
        $fileName = $request->file->getClientOriginalName();
        $request->file->move(public_path('storage'), $fileName);

        // Check if the modified image exists in the cache
        $cacheKey = 'modified_image_' . $fileName;
        $imageUrl = Cache::get($cacheKey);

        if (!$imageUrl) {
            // Resize the uploaded image while maintaining aspect ratio
            $image = Image::make(public_path('storage/' . $fileName))->resize(300, null, function ($constraint) {
                $constraint->aspectRatio();
            });

            // Add watermark
            $watermark = Image::make(public_path('watermark.png'));

            // Resize the watermark based on the dimensions of the resized image
            $watermarkWidth = $image->width() * 0.25; // Adjust the watermark size as needed
            $watermarkHeight = $watermark->height() * ($watermarkWidth / $watermark->width());
            $watermark->resize($watermarkWidth, $watermarkHeight);
            $watermark->opacity(50); // Adjust the opacity of the watermark image as needed

            // Position the watermark at the bottom right corner with a margin of 10 pixels
            $x = $image->width() - $watermark->width() - 10;
            $y = $image->height() - $watermark->height() - 10;
            $image->insert($watermark, 'top-left', $x, $y);

            // Save the modified image
            $image->save(public_path('storage/' . $fileName));

            // Generate the image URL
            $imageUrl = asset('storage/' . $fileName);

            // Cache the image URL for future requests
            Cache::put($cacheKey, $imageUrl, 1440); // Cache for 24 hours (1440 minutes)
        }

        return view('IMGupload', compact('imageUrl'));
    } catch (\Exception $e) {
        // Handle the exception
        $errorMessage = $e->getMessage();
        $imageUrl = null;
        return view('IMGupload', compact('errorMessage', 'imageUrl'));
    }
})->middleware('auth')->name('upload');