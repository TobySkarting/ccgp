<?php

namespace App\Http\Controllers;

use Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\ImageManagerStatic as Image;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest');
    }

    public function getRegister()
    {
        return view('register');
    }

    public function addRegister(Request $request)
    {
        if (!$request->hasFile('data'))
            throw ValidationException::withMessages([
                'images' => 'no data',
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

        return redirect(route('register'))->with('status', 'Registered!');
    }

    function getLogin(Request $request)
    {
        $request->session()->forget('login');
        return view('login');
    }

    function addLogin(Request $request)
    {
        if (!$request->hasFile('data'))
            throw ValidationException::withMessages([
                'images' => 'no data',
            ]);
        
        $filename = $request->file('data')->store("login");
        $request->session()->push('login', $filename);
        return $request->session()->all();
    }

    function login(Request $request)
    {
        $request->validate([
            'name' => 'required'
        ]);

        $username = $request->input('name');
        if (!Storage::exists("registered/$username"))
            throw ValidationException::withMessages([
                'name' => 'This is not a registered username.',
            ]);
        
        $images = $request->session()->get('login');
        if (is_null($images) || count($images) === 0)
            throw ValidationException::withMessages([
                'images' => "You haven't uploaded any images.",
            ]);

        $key = Storage::get("registered/$username/key");

        $i = 0;
        foreach ($images as $image) {
            $correct = "registered/$username/$i.jpg";
            if (!Storage::exists($correct))
                throw ValidationException::withMessages([
                    'images' => "Wrong password.",
                ]);
            // Extract

            // Decrypt

            // Compare

            ++$i;
        }
        if (Storage::exists("registered/$username/$i.jpg"))
            throw ValidationException::withMessages([
                'images' => "Wrong password.",
            ]);

        $request->session()->forget('login');
        
        return redirect('/home')->with('status', 'Logged in!');
    }

    function toby()
    {
        $img = Image::make('../storage/app/idarling.jpeg');

        return $img->height();
    }
}
