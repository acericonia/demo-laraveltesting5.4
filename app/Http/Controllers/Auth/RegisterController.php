<?php

namespace App\Http\Controllers\Auth;

use App\Notifications\UserRegistration;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Registered;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\RegistersUsers;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

        /**
     * Handle a registration request for the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        $this->validator($request->all())->validate();

        event(new Registered($user = $this->create($request->all())));
        $user->notify(new UserRegistration());
        return redirect('/login')->with('success', 'Your account has been created, Please confirm with the email we send you.');
    }

    public function confirm($id, $token)
    {
        $user = User::where('id', $id)->where('confirmation_token', $token)->first();

        if ($user)
        {
            $user->update(['confirmation_token' => null]);
            $this->guard()->login($user);
            return redirect($this->redirectPath())->with('success', 'Your is now active, Thank you.');
        }else{
            return redirect('/login')->with('error', 'This link is not valid');
        }
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => 'required|max:255',
            'username' => 'required|max:255|unique:users',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|min:6|confirmed',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return User
     */
    protected function create(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
            'confirmation_token' => str_replace('/', '', bcrypt(str_random(16))),
        ]);
    }
}
