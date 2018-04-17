<?php

namespace App\Http\Controllers;

use Storage;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function getRegister(Request $request)
    {
        $request->session()->forget('new');
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
            'name' => 'required|unique:users'
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

        User::create([
            'name' => $username,
            'key' => $key,
        ]);

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
            $grayPixels = self::getGrayPixels("../storage/app/" . $image);
            // Decrypt
            $decrypted = openssl_decrypt($grayPixels, 'aes-128-cbc', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $key);
            if ($decrypted === false)
                throw ValidationException::withMessages([
                    'images' => "Wrong key.",
                ]);
            
            //Storage::put("decrypted", $decrypted);
            self::grayPixelsToFile($decrypted, "../storage/app/" . $image);
            dd($image);
            // Compare
            if (!$this->compareImages("../storage/app/" . $correct, "../storage/app/" . $image))
                throw ValidationException::withMessages([
                    'images' => "Wrong password.",
                ]);

            ++$i;
        }
        if (Storage::exists("registered/$username/$i.jpg"))
            throw ValidationException::withMessages([
                'images' => "Wrong password.",
            ]);

        $request->session()->forget('login');

        Auth::login(User::where('name', $username)->first());

        return redirect('/home')->with('status', 'Logged in!');
    }

    public function logout(Request $request)
    {
        $request->session()->invalidate();

        return redirect('/');
    }

    function toby()
    {
        return $this->compareImages('../storage/app/idarling.jpeg', '../storage/app/idarling.jpeg') ? "Y" : "N";
    }

    public static function grayPixelsToFile($pixels, $imagePath)
    {
        $img = imagecreatefromjpeg($imagePath);
        $width = imagesx($img);
        $height = imagesy($img);

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $r = ord($pixels[$y * $width + $x]);
                $new_color = imagecolorallocate($img, $r, $r, $r);
                imagesetpixel($img, $x, $y, $new_color);
            }
        }
        header('Content-Type: image/jpeg');
        imagejpeg($img);

        imagedestroy($img);

        exit();
    }

    public static function getGrayPixels($imagePath)
    {
        $img = imagecreatefromjpeg($imagePath);
        $width = imagesx($img);
        $height = imagesy($img);

        $result = "";
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgb = imagecolorat($img, $x, $y);
                $colors = imagecolorsforindex($img, $rgb);
                $result .= sprintf('%c', $colors['red']);
            }
        }
        return $result;
    }

    public static function compareImages($imagePathA, $imagePathB)
    {
        $imgA = imagecreatefromjpeg($imagePathA);
        $widthA = imagesx($imgA);
        $heightA = imagesy($imgA);

        $imgB = imagecreatefromjpeg($imagePathB);
        $widthB = imagesx($imgB);
        $heightB = imagesy($imgB);

        if ($widthA != $widthB || $heightA != $heightB)
            return false;

        $matchedCount = 0;
        for ($y = 0; $y < $heightA; $y++) {
            for ($x = 0; $x < $widthA; $x++) {

                $rgbA = imagecolorat($imgA, $x, $y);
                $colorsA = imagecolorsforindex($imgA, $rgbA);

                $rgbB = imagecolorat($imgB, $x, $y);
                $colorsB = imagecolorsforindex($imgB, $rgbB);

                if (self::colorComp($colorsA['red'], $colorsB['red'])
                        && self::colorComp($colorsA['green'], $colorsB['green'])
                        && self::colorComp($colorsA['blue'], $colorsB['blue']))
                    ++$matchedCount;
                else
                    return false;
            }
        }
        return ($matchedCount <> 0);
    }

    public static function colorComp($base, $masked)
    {
        return (!$masked || $base == $masked);
    }
}
