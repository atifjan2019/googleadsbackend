<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GoogleAuthController extends Controller
{
    /**
     * Redirect to Google OAuth consent screen.
     */
    public function redirect()
    {
        $client = new \Google_Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect_uri'));
        $client->addScope('https://www.googleapis.com/auth/adwords');
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        return redirect($client->createAuthUrl());
    }

    /**
     * Handle callback from Google OAuth.
     */
    public function callback(Request $request)
    {
        if ($request->has('error')) {
            return redirect('/')->with('error', 'Google authorization was denied.');
        }

        $client = new \Google_Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect_uri'));

        $token = $client->fetchAccessTokenWithAuthCode($request->get('code'));

        if (isset($token['error'])) {
            return redirect('/')->with('error', 'Failed to get access token: ' . $token['error_description']);
        }

        $refreshToken = $token['refresh_token'] ?? null;

        if ($refreshToken) {
            // Save refresh token to .env (or display it)
            return view('dashboard.auth-success', [
                'refresh_token' => $refreshToken,
                'access_token' => $token['access_token'],
            ]);
        }

        return redirect('/')->with('error', 'No refresh token received. Try revoking access at myaccount.google.com/permissions and retry.');
    }

    /**
     * Show connection status.
     */
    public function status()
    {
        $connected = !empty(config('services.google.refresh_token'));

        return response()->json([
            'connected' => $connected,
            'manager_account' => config('services.google.manager_account_id'),
        ]);
    }
}
