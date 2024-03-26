<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Mail;

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
        try
        {
            if(!Auth::attempt($request->only('email', 'password')))
            {
                throw new Exception('Credenciales inválidas');
            }

            $user = User::where('email', $request['email'])->firstOrFail();
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Hola '.$user->name,
                'access_token'=> $token ,
                'token_type' => 'Bearer',
                'user' => $user,
            ], 200);
        }
        catch(Exception $e)
        {
            return response()->json(['message' => $e->getMessage()], 401);
        }

    }
    
    public function register($request)
    {
        try
        {
            $user = User::create([
                'name' => $request['name'],
                'email' => $request['email'],
                'password'=> Hash::make($request['password']),
            ]);
            $token = $user->createToken('auth_token')->plainTextToken;
            return response()->json([
                'message' => 'Hola '.$user->name,
                'access_token'=> $token ,
                'token_type' => 'Bearer',
                'user' => $user,
            ], 200);
        }
        catch(Exception $e)
        {
            return response()->json(['message' => $e->getMessage()], 401);
        }
    }

    public function logout($request)
    {
        try
        {
            $request->user()->currentAccessToken()->delete();
            return response()->json(['message' => 'Sesión cerrada'], 200);
        }
        catch(Exception $e)
        {
            return response()->json(['message' => $e->getMessage()], 401);
        }
    }

    public function sendCode($request)
    {
        try {
            $user = User::where('email', $request->email)->firstOrFail();
            $codeExist = Code::where('user_id', $user->id)->first();
            if($codeExist && now()->diffInMinutes($codeExist->created_at) < 5)
                throw new Exception("El codigo ya ha sido enviado, por favor revisa tu correo.", 1);
            if($codeExist)
                $codeExist->delete();
            $code = rand(100000, 999999);
            $response = Mail::to($user->email)->send(new SendCodeRestorePassword($code));
            if(!$response)
                throw new Exception("Error al enviar el código", 1);
            $sendCode = new Code([
                'codigo' => $code,
                'user_id' => $user->id,
                'expires_at' => now()->addMinutes(5)
            ]);
            $sendCode->save();
            return response()->json([
                'message' => 'Código enviado',
                'user_email' => $user->email,
                'user_id' => $user->id,
            ], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 401);
        }
    }

    public function restorePassword($request)
    {
        try {
            $user = User::where('email', $request->email)->firstOrFail();
            $codeExist = Code::where('user_id', $user->id)->first();
            if($codeExist->codigo != $request->code)
                throw new Exception("El código ingresado es incorrecto", 1);
            if(!($codeExist && now()->diffInMinutes($codeExist->created_at) < 5))
                throw new Exception("El código ha expirado, por favor solicita uno nuevo.", 1);
            $user->password = Hash::make($request->password);
            $user->save();
            $codeExist->delete();
            return response()->json([
                'message' => 'Contraseña actualizada',
            ], 200);            
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 401);
        }
    }

    public function updateProfile($request)
    {
        try {
            $user = $request->user();
            $user->name = $request->input('name');
            $user->image = $request->input('image');
            $user->save();
            return response()->json([
                'message' => 'Perfil actualizado',
                'user' => $user,
            ], 200);
        }catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 401);
        }
    }

    public function getListAnimes($request) {
        try {
            $user = $request->user();
            $data =  array(
                'favorites' => $user->getFavoriteItems(Anime::class)->select('id','name','poster')->orderBy('created_at','desc')->get(),
                'watchings' => $user->getWatchingItems(Anime::class)->select('id','name','poster')->orderBy('created_at','desc')->get(),
                'endeds' => $user->getViewItems(Anime::class)->select('id','name','poster')->orderBy('created_at','desc')->get()
            );
            return response()->json($data, 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 401);
        }
    }

    public function addFavorite($request) {
        try {
        $user = $request->user();
        $anime = Anime::find($request->anime_id);
        if(!$anime)
            throw new Exception("El anime no existe", 1);
        $user->favorite($anime);
        return response()->json([
            'message' => 'Agregado a Favoritos',
        ], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 401);
        }
    }

    public function deleteFavorite($request) {
        try {
        $user = $request->user();
        $anime = Anime::find($request->anime_id);
        if(!$anime)
            throw new Exception("El anime no existe", 1);
        $user->unfavorite($anime);
        return response()->json([
            'message' => 'Quitado de Favoritos',
        ], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 401);
        }
    }

    public function addView($request) {
        try {
        $user = $request->user();
        $anime = Anime::find($request->anime_id);
        if(!$anime)
            throw new Exception("El anime no existe", 1);
        $user->view($anime);
        return response()->json([
            'message' => 'Agregado a Vistos',
        ], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 401);
        }
    }

    public function deleteView($request) {
        try {
        $user = $request->user();
        $anime = Anime::find($request->anime_id);
        if(!$anime)
            throw new Exception("El anime no existe", 1);
        $user->unview($anime);
        return response()->json([
            'message' => 'Quitado de Vistos',
        ], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 401);
        }
    }

    public function addWatching($request) {
        try {
        $user = $request->user();
        $anime = Anime::find($request->anime_id);
        if(!$anime)
            throw new Exception("El anime no existe", 1);
        $user->watching($anime);
        return response()->json([
            'message' => 'Agregado a Viendo',
        ], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 401);
        }
    }

    public function deleteWatching($request) {
        try {
        $user = $request->user();
        $anime = Anime::find($request->anime_id);
        if(!$anime)
            throw new Exception("El anime no existe", 1);
        $user->unwatching($anime);
        return response()->json([
            'message' => 'Quitado de Viendo',
        ], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 401);
        }
    }
    
}
