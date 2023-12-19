<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Mail;

use App\Mail\SendCodeRestorePassword;

use Auth;
use Validation;
use Hash;
use Exception;

use Animelhd\AnimesFavorite\Traits\Favoriter;
use Animelhd\AnimesView\Traits\Viewer;
use Animelhd\AnimesWatching\Traits\Watchinger;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    use Favoriter;
	use Viewer;
	use Watchinger;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];


    public function login($request)
    {
        if(!Auth::attempt($request->only('email', 'password')))
        {
            return [
                'message' => 'Credenciales inválidas'
            ];
        }

        $user = User::where('email', $request['email'])->firstOrFail();
        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'message' => 'Hola '.$user->name,
            'access_token'=> $token ,
            'token_type' => 'Bearer',
            'user' => $user,
        ]; 
    }
    
    public function register($request)
    {
        $user = User::create([
            'name' => $request['name'],
            'email' => $request['email'],
            'password'=> Hash::make($request['password']),
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'message' => 'Hola '.$user->name,
            'access_token'=> $token ,
            'token_type' => 'Bearer',
            'user' => $user,
        ]; 
    }

    public function logout($request)
    {
        $request->user()->currentAccessToken()->delete();
    }

    public function sendCode($request)
    {
        try {
            $user = User::where('email', $request->email)->firstOrFail();
            $codeExist = Code::where('user_id', $user->id)->first();
            if($codeExist && now()->diffInMinutes($codeExist->created_at) < 5){
                return [
                    'message' => 'El codigo ya ha sido enviado, por favor revisa tu correo.',
                ];
            }
            else{
                if($codeExist)
                    $codeExist->delete();
                $code = rand(100000, 999999);
                $response = Mail::to($user->email)->send(new SendCodeRestorePassword($code));
                if($response){
                    $sendCode = new Code([
                        'codigo' => $code,
                        'user_id' => $user->id,
                        'expires_at' => now()->addMinutes(5)
                    ]);
                    $sendCode->save();
                    return [
                        'message' => 'Código enviado',
                        'user_email' => $user->email,
                        'user_id' => $user->id,
                    ];
                }else {
                    return [
                        'message' => 'Error al enviar el código',
                    ];
                }

            }
        } catch (Exception $e) {
            return [
                'message' => 'Error al enviar el código',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function restorePassword($request)
    {
        try {
            $user = User::where('email', $request->email)->firstOrFail();
            $codeExist = Code::where('user_id', $user->id)->first();
            if($codeExist && now()->diffInMinutes($codeExist->created_at) < 5){
                if($codeExist->codigo == $request->code){
                    $user->password = Hash::make($request->password);
                    $user->save();
                    $codeExist->delete();
                    return [
                        'message' => 'Contraseña actualizada',
                    ];
                }else{
                    return [
                        'message' => 'Código incorrecto',
                    ];
                }
            }
            else{
                return [
                    'message' => 'El código ha expirado, por favor solicita uno nuevo.',
                ];
            }
        } catch (Exception $e) {
            return [
                'message' => 'Error al actualizar la contraseña',
                'error' => $e->getMessage(),
            ];
        }
    }


    public function updateProfile($request)
    {
        try {
            $user = $request->user();
            $request->validate([
                'name' => [
                    'required',
                    'string',
                    'unique:users,name,' . $user->id,
                    'regex:/^[a-zA-Z0-9\s.-]+$/u'
                ],
                'image' => 'required|url',
            ],
            [
                'name.required' => 'El nombre es requerido',
                'name.string' => 'El nombre debe ser una cadena de texto',
                'name.unique' => 'El nombre ya existe',
                'name.regex' => 'El nombre no es válido',
                'image.required' => 'La imagen es requerida',
                'image.url' => 'La imagen no es válida',
            ]);
            $user->name = $request->input('name');
            $user->image = $request->input('image');
            $user->save();
            return ['status' => true, 'message' => 'Usuario actualizado correctamente'];
        } catch (ValidationException $e) {
            return ['status' => false, 'message' => $e->errors()];
        } catch (Exception $e) {
            return ['status' => false, 'message' => 'Ocurrió un error inesperado', 'error' => $e->getMessage()];
        }
    }

    public function getFavoriteAnimes($user) {
        try {
			return $user->getFavoriteItems(Anime::class)->select('slug','name','poster')->orderBy('name','asc')->get();
        } catch (Exception $e) {
            return [
                'message' => 'Error',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getWatchingAnimes($user) {
        try {
			return $user->getWatchingItems(Anime::class)->select('slug','name','poster')->orderBy('name','asc')->get();
        } catch (Exception $e) {
            return [
                'message' => 'Error',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getEndedAnimes($user) {
        try {
			return $user->getViewItems(Anime::class)->select('slug','name','poster')->orderBy('name','asc')->get();
        } catch (Exception $e) {
            return [
                'message' => 'Error',
                'error' => $e->getMessage(),
            ];
        }
    }

    
}
