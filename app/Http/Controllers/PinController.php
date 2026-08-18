<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PinController extends Controller
{
    public function show()
    {
        if (session('pin_verified')) {
            return redirect()->route('dashboard');
        }

        return view('auth.pin');
    }

    public function verify(Request $request)
    {
        $request->validate([
            'pin' => 'required|string|digits:4',
        ], [
            'pin.required' => 'Inserisci il codice PIN.',
            'pin.digits' => 'Il PIN deve essere esattamente di 4 cifre numeriche.',
        ]);

        $inputPin = trim((string) $request->input('pin'));
        $correctPin = trim((string) config('app.pin', env('APP_PIN', '1234')));

        if (hash_equals($correctPin, $inputPin)) {
            session(['pin_verified' => true]);
            $intended = session()->pull('url.intended', route('dashboard'));

            return redirect()->to($intended)->with('success', 'Accesso effettuato con successo!');
        }

        return back()->withErrors(['pin' => 'Codice PIN errato. Riprova.'])->withInput();
    }

    public function logout(Request $request)
    {
        session()->forget('pin_verified');

        return redirect()->route('pin.show')->with('success', 'Sessione bloccata.');
    }
}
