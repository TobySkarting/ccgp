<?php

namespace App\Http\Controllers;

use Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function getCorn()
    {
        return view('corn');
    }

    public function addCorn(Request $request)
    {
        if (!$request->hasFile('data'))
            throw ValidationException::withMessages([
                'data' => 'no data',
            ]);
        
        $filename = $request->file('data')->store("register");
        $request->session()->push('new', $filename);
        return $request->session()->all();
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required'
        ]);

        $username = $request->input('name');
        if (Storage::exists("registered/$username"))
            throw ValidationException::withMessages([
                'name' => 'The username has been registered.',
            ]);
        
        $images = $request->session()->get('new');
        if (is_null($images) || count($images) === 0)
            throw ValidationException::withMessages([
                'images' => "You haven't uploaded any images.",
            ]);

        $i = 0;
        foreach ($images as $image) {
            Storage::move($image, "registered/$username/$i.jpg");
            ++$i;
        }

        $request->session()->forget('new');
        $key = Str::random();
        Storage::put("registered/$username/key", $key);
        $request->session()->put('key', $key);

        return redirect('/corn')->with('status', 'Registered!');
    }
}
