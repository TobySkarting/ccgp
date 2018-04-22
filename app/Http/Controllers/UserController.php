<?php

namespace App\Http\Controllers;

use File;
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
            Storage::move($image, "registered/$username/$i.png");
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
        foreach ($images as $imagePath) {
            $correctImgPath = "registered/$username/$i.png";
            if (!Storage::exists($correctImgPath))
                throw ValidationException::withMessages([
                    'images' => "Wrong password.",
                ]);
            // Extract
            $receivedImg = imagecreatefrompng("../storage/app/" . $imagePath);
            $grayPixels = self::getGrayPixels($receivedImg);

            // Decrypt
            $decrypted = openssl_decrypt($grayPixels, 'aes-128-cbc', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $key);
            if ($decrypted === false)
                throw ValidationException::withMessages([
                    'images' => "Wrong key.",
                ]);
            $correctImg = imagecreatefrompng("../storage/app/" . $correctImgPath);
            $decryptedImg = self::pixelsToImage($decrypted, imagesx($correctImg), imagesy($correctImg));

            // Compare            
            if (!$this->compareImages($correctImg, $decryptedImg))
                throw ValidationException::withMessages([
                    'images' => "Wrong password.",
                ]);

            ++$i;
        }
        if (Storage::exists("registered/$username/$i.png"))
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

    function getCover() 
    {
        $files = File::glob("../storage/app/color/*.jpg");
        $count = count($files);
        $img = imagecreatefromjpeg($files[rand() % $count]);
        header('Content-Type: image/png');
        imagepng($img);
        imagedestroy($img);
    }

    public static function pixelsToImage($pixels, $width, $height)
    {
        $img = imagecreatetruecolor($width, $height);
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $r = ord($pixels[$y * $width + $x]);
                $color = imagecolorallocate($img, $r, $r, $r);
                imagesetpixel($img, $x, $y, $color);
            }
        }
        return $img;
    }

    public static function getGrayPixels($img)
    {
        $width = imagesx($img);
        $height = imagesy($img);

        $result = "";
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgb = imagecolorat($img, $x, $y);
                $colors = imagecolorsforindex($img, $rgb);
                $r = $colors['red'];
                $g = $colors['green'];
                $b = $colors['blue'];
                $result .= sprintf('%c', ($r & 0x7) << 5 | ($g & 0x3) << 3 | ($b & 0x7));
            }
        }
        return $result;
    }

    public static function compareImageFiles($imagePathA, $imagePathB)
    {
        $imgA = imagecreatefrompng($imagePathA);
        $imgB = imagecreatefrompng($imagePathB);
        return self::compareImages($imgA, $imgB);
    }

    public static function compareImages($imgA, $imgB)
    {
        $widthA = imagesx($imgA);
        $heightA = imagesy($imgA);

        $widthB = imagesx($imgB);
        $heightB = imagesy($imgB);

        if ($widthA != $widthB || $heightA != $heightB)
            return false;

        $matchedCount = 0;
        for ($y = 0; $y < $heightA; $y++) {
            for ($x = 0; $x < $widthA; $x++) {
                $rgbA = imagecolorat($imgA, $x, $y);
                $rgbB = imagecolorat($imgB, $x, $y);
                if ($rgbB == 0)
                    continue;
                if ($rgbA == $rgbB)
                    ++$matchedCount;
                else
                    return false;
            }
        }
        return ($matchedCount > 0);
    }
}
