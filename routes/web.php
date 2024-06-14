<?php

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/redirect', function (Request $request) {
    // $state = Str::random(128);
    // $code_verifier = Str::random(128);

    $state = 'puvgjetznlslqxnybedwuvhkpjzirrtlfovjklzpclcshzfadrfealsrzqmkayppavpvhddfsitzobyivgprmlqasawpqnicdanozrkxmguzyrrucrumadcjecyglxrg';
    $code_verifier = 'njicavcysjpzwmjyvjrygrlnyhzeeouogilrzarcorjqkhlzezohdrzqmcsfmrklnfgsrwjfrgchgbnvwhrhleawadvgsfrzlmxdprplacxmmbeumqoqxvmgmoseiyta';

    session('state', $state);
    session('code_verifier', $code_verifier);
    $codeChallenge = strtr(rtrim(
        base64_encode(hash('sha256', $code_verifier, true))
    , '='), '+/', '-_');

    $query = http_build_query([
        'client_id' => '9c488283-2764-4726-9934-037cd6d43d19',
        'redirect_uri' => 'http://localhost:8001/auth/callback',
        'response_type' => 'code',
        'scope' => '*',
        'state' => $state,
        'code_challenge' => $codeChallenge,
        'code_challenge_method' => 'S256',
        // 'prompt' => '', // "none", "consent", or "login"
    ]);

    return redirect('http://localhost:8000/oauth/authorize?'.$query);
});

Route::get('/auth/callback', function (Request $request) {
    // $state = session('state');
    // $codeVerifier = session('code_verifier');
    $state = 'puvgjetznlslqxnybedwuvhkpjzirrtlfovjklzpclcshzfadrfealsrzqmkayppavpvhddfsitzobyivgprmlqasawpqnicdanozrkxmguzyrrucrumadcjecyglxrg';
    $codeVerifier = 'njicavcysjpzwmjyvjrygrlnyhzeeouogilrzarcorjqkhlzezohdrzqmcsfmrklnfgsrwjfrgchgbnvwhrhleawadvgsfrzlmxdprplacxmmbeumqoqxvmgmoseiyta';

    // dd($request->session());

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
});

Route::get('/auth/profile', function (Request $request) {
    $accessToken = $request->cookie('access_token');

    $response = Http::withHeaders([
        'Authorization' => 'Bearer '.$accessToken
    ])->get('http://localhost:8000/api/user');

    return $response->json();
});
