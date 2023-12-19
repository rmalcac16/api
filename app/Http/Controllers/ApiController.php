<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Episode;
use App\Models\Anime;
use App\Models\Genre;
use App\Models\Server;
use App\Models\Player;

class ApiController extends Controller
{
    
    protected $user, $episode, $anime, $genre, $server, $player;


    public function __construct(User $user, Episode $episode, Anime $anime, Genre $genre, Server $server, Player $player)
    {
        $this->user = $user;
        $this->episode = $episode;
        $this->anime = $anime;
        $this->genre = $genre;
        $this->server = $server;
        $this->player = $player;
    }


    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string'
        ],
        [
            'email.email' => 'El correo no es válido',
            'email.required' => 'El correo es requerido',
            'password.required' => 'La contraseña es requerida',
        ]);

        try
        {
            return $this->user->login($request);
        }
        catch(Exception $e)
        {
            return response()->json(['message' => $e->getMessage()], 401);
        }
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:users,name',
            'email' => 'required|unique:users,email|email',
            'password' => 'required|string|confirmed|min:8',
        ],
        [
            'name.unique' => 'El nombre de usuario ya existe',
            'name.required' => 'El nombre de usuario es requerido',
            'email.unique' => 'El correo ya existe',
            'email.email' => 'El correo no es válido',
            'email.required' => 'El correo es requerido',
            'password.confirmed' => 'Las contraseñas no coinciden',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'password.required' => 'La contraseña es requerida',
        ]);

        try
        {
            return $this->user->register($request);
        }
        catch(Exception $e)
        {
            return response()->json(['message' => $e->getMessage()], 401);
        }
    }

    public function logout(Request $request)
    {
        try
        {
            $user = $this->user->logout($request);
            return response()->json(['message' => 'Sesión cerrada'], 200);
        }
        catch(Exception $e)
        {
            return response()->json(['message' => $e->getMessage()], 401);
        }
    }

    public function sendCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ],
        [
            'email.exists' => 'El correo no es válido',
            'email.email' => 'El correo no es válido',
            'email.required' => 'El correo es requerido',
        ]);

        try
        {
            $response = $this->user->sendCode($request);
            return response()->json($response, 200);
        }
        catch(Exception $e)
        {
            return response()->json(['message' => $e->getMessage()], 401);
        }
    }

    public function restorePassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'user_id' => 'required|numeric|exists:users,id',
            'code' => 'required|numeric|exists:codigos,codigo|digits:6|integer',
            'password' => 'required|string|confirmed|min:8',
        ],
        [
            'code.exists' => 'El código no es válido',
            'code.digits' => 'El código debe tener 6 dígitos',
            'code.integer' => 'El código debe ser un número entero',
            'user_id.exists' => 'El usuario no es válido',
            'user_id.numeric' => 'El usuario debe ser un número',
            'user_id.required' => 'El usuario es requerido',
            'email.exists' => 'El correo no es válido',
            'email.email' => 'El correo no es válido',
            'email.required' => 'El correo es requerido',
            'password.confirmed' => 'Las contraseñas no coinciden',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'password.required' => 'La contraseña es requerida',
        ]);

        try
        {
            $response = $this->user->restorePassword($request);
            return response()->json($response, 200);
        }
        catch(Exception $e)
        {
            return response()->json(['message' => $e->getMessage()], 401);
        }
    }

	public function updateProfile(Request $request)
	{
        try
        {
            $response = $this->user->updateProfile($request);
            return response()->json($response, 200);
        }
        catch(Exception $e)
        {
            return response()->json(['message' => $e->getMessage()], 500);
        }
	}

    public function listsAnimes(Request $request){
        try
        {
            $user = $this->user::find($request->user_id);
            $data =  array(
                'favorites' => $this->user->getFavoriteAnimes($user),
                'watchings' => $this->user->getWatchingAnimes($user),
                'endeds' => $this->user->getEndedAnimes($user)
            );
            return response()->json($data, 200);
        }
        catch(Exception $e)
        {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }


    public function home(Request $request)
    {
        try
        {
            $response = array(
                'episodes' => $this->episode->getRecents(),
                'animesR' => $this->anime->getRecents(),
                'animesL' => $this->anime->getLatinos(),
                'animesV' => $this->anime->getMoreViews(),
                'animesP' => $this->anime->getPopulars(),
            );
            return response()->json($response, 200);
        }
        catch(Exception $e)
        {
            return response()->json(['message' => $e->getMessage()], 401);
        }
    }

    public function search(Request $request)
    {
        $request->validate([
            'search' => 'required|string',
        ],
        [
            'search.required' => 'La búsqueda es requerida',
        ]);

        try
        {
            $response = $this->anime->search($request);
            return response()->json($response, 200);
        }
        catch(Exception $e)
        {
            return response()->json(['message' => $e->getMessage()], 401);
        }
    }

    public function animes(Request $request)
    {
        try
        {
            $response = $this->anime->getAnimes($request);
            return response()->json($response, 200);
        }
        catch(Exception $e)
        {
            return response()->json(['message' => $e->getMessage()], 401);
        }
    }

    public function anime(Request $request)
    {
        $request->validate([
            'id' => 'required|numeric|exists:animes,id',
        ],
        [
            'id.exists' => 'El anime no es válido',
            'id.numeric' => 'El anime debe ser un número',
            'id.required' => 'El anime es requerido',
        ]);

        try
        {
            $response = $this->anime->getAnime($request);
            return response()->json($response, 200);
        }
        catch(Exception $e)
        {
            return response()->json(['message' => $e->getMessage()], 401);
        }
    }

    public function calendar(Request $request)
    {
        try
        {
            $response = $this->anime->getCalendar();
            return response()->json($response, 200);
        }
        catch(Exception $e)
        {
            return response()->json(['message' => $e->getMessage()], 401);
        }
    }

    public function episodes(Request $request) {
        $request->validate([
            'anime_id' => 'required|numeric|exists:animes,id',
        ],
        [
            'anime_id.exists' => 'El anime no es válido',
            'anime_id.numeric' => 'El anime debe ser un número',
            'anime_id.required' => 'El anime es requerido',
        ]);

        try
        {
            $response = $this->episode->getEpisodesByAnimeId($request);
            return response()->json($response, 200);
        }
        catch(Exception $e)
        {
            return response()->json(['message' => $e->getMessage()], 401);
        }
    }

    public function players(Request $request) {
        $request->validate([
            'episode_id' => 'required|numeric|exists:episodes,id',
        ],
        [
            'episode_id.exists' => 'El episodio no es válido',
            'episode_id.numeric' => 'El episodio debe ser un número',
            'episode_id.required' => 'El episodio es requerido',
        ]);

        try
        {
            $response = $this->player->getPlayersByEpisodeId($request);
            return response()->json($response, 200);
        }
        catch(Exception $e)
        {
            return response()->json(['message' => $e->getMessage()], 401);
        }
    }
    
}
