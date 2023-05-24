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
       // IMPLODE/RELATIONS FF NIET -  $csvEport .= $alien->name . ",".$alien->email.",".$alien->location.",".implode(",",$alien->abilities->pluck('name')->toArray())."\n";
       $csvEport .= $alien->name . "," . $alien->email . "," . $alien->location . "," . $alien->date . "," . $alien->time . "," . $alien->scary . "\n";
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
Route::post('/upload', function (Request $request) {
    // Validate file MIME type
    $validatedData = $request->validate([
        'file' => 'required|mimes:jpeg,png,gif',
    ]);

    // Handle file upload
    $fileName = $request->file->getClientOriginalName();
    $request->file->move(public_path('storage'), $fileName);

    // Create an Intervention Image instance from the uploaded file
    try {
        $image = Image::make(public_path('storage/' . $fileName));
    } catch (\Exception $e) {
        // Delete the uploaded file if it's not a valid image
        unlink(public_path('storage/' . $fileName));

        // Return error response
        return response()->json(['error' => 'The uploaded file is not a valid image.']);
    }

    // Add a watermark to the image
    $watermarkUrl = 'https://upload.wikimedia.org/wikipedia/commons/thumb/0/04/ChatGPT_logo.svg/128px-ChatGPT_logo.svg.png'; // Replace with the actual URL of your watermark image
    $watermark = Image::make($watermarkUrl)->opacity(50);
    $image->insert($watermark, 'center');

    // Perform other desired image manipulations
    $image->resize(300, 200);
    // Add more manipulation methods as needed

    // Save the modified image
    $image->save(public_path('storage/' . $fileName));

    // Return success response
    return response()->json(['success' => 'You have successfully uploaded and edited the file.']);
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

route::post('/upload2', function(request $request) {
    // Validate file MIME type
    $validatedData = $request->validate([
        'file' => 'required|mimes:jpeg,png,gif',
    ]);

    // Handle file upload
    $fileName = $request->file->getClientOriginalName();
    $request->file->move(public_path('storage'), $fileName);

    // Pass the image URL to the view
    $imageUrl = asset('storage/' . $fileName);
    return view('IMGupload', compact('imageUrl'));
})->middleware('auth')->name('upload2');


route::post('/csvupload', function(Request $request) {
    if ($request->hasFile('file')) {
        $file = $request->file('file');
        $handle = fopen($file->getPathname(), "r");

        $existingEmails = []; // Collection to store existing emails
        
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $name = $data[0];
            $email = $data[1];
            $location = $data[2];
            $date = $data[3];
            $time = $data[4];

            // Check if email already exists in Alien model
            $existingAlien = App\Models\Alien::where('email', $email)->first();
            if ($existingAlien) {
                session()->flash('message', 'Import succesfull. But some duplicate emails found and not added.');
            } else {
                $alien = new App\Models\Alien;
                $alien->name = $name;
                $alien->email = $email;
                $alien->location = $location;
                $alien->date = $date;
                $alien->time = $time;
                $alien->save();
                session()->flash('message', 'Import successful.');
            }   
        }
        fclose($handle);
    }

    return redirect()->back();
})->middleware('auth')->name('csvupload');


// logout function
Route::get('/logout', function () {
    Auth::logout();
    session()->flash('message', 'you have been terminated');
    return redirect()->back();
})->name('logout');

