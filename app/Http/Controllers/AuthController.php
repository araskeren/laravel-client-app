<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use InvalidArgumentException;

class AuthController extends Controller
{
    private $state;
    private $codeVerifier;

    public function __construct()
    {
        $this->state = Str::random(128);
        $this->codeVerifier = Str::random(128);
    }
    public function redirect(Request $request)
    {
        session()->put('state', $this->state);
        session()->put('code_verifier', $this->codeVerifier);
        $codeChallenge = strtr(rtrim(
            base64_encode(hash('sha256', $this->codeVerifier, true))
        , '='), '+/', '-_');

        $query = http_build_query([
            'client_id' => '9c488283-2764-4726-9934-037cd6d43d19',
            'redirect_uri' => 'http://localhost:8001/auth/callback',
            'response_type' => 'code',
            'scope' => '*',
            'state' => $this->state,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
            // 'prompt' => '', // "none", "consent", or "login"
        ]);

        return redirect('http://localhost:8000/oauth/authorize?'.$query)->withCookie('state', $this->state)->withCookie('code_verifier', $this->codeVerifier);
    }

    public function callback(Request $request)
    {
        $state = $request->cookie('state');
        $codeVerifier = $request->cookie('code_verifier');
        throw_unless(
            strlen($state) > 0 && $state === $request->state,
            InvalidArgumentException::class
        );

        $response = Http::asForm()->post('http://localhost:8000/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => '9c488283-2764-4726-9934-037cd6d43d19',
            'redirect_uri' => 'http://localhost:8001/auth/callback',
            'code_verifier' => $codeVerifier,
            'code' => $request->code,
        ]);

        if($response->status() !== 200) {
            return $response->json();
        }else{
            session('access_token', $response->json()['access_token']);
            session('refresh_token', $response->json()['refresh_token']);

            return redirect('/auth/profile')->withCookie('access_token', $response->json()['access_token']);
        }
    }

    public function profile(Request $request)
    {
        $accessToken = $request->cookie('access_token');

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$accessToken
        ])->get('http://localhost:8000/api/user');

        return view('dashboard', ['user' => collect($response->json()),'accessToken' => $accessToken]);
    }
}
