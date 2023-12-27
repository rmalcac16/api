<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;
use App\Models\Episode;
use App\Models\Anime;
use App\Models\Genre;
use App\Models\Server;
use App\Models\Player;

use Exception;

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
        return $this->user->login($request);
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
        return $this->user->register($request);
    }

    public function logout(Request $request)
    {
        return $this->user->logout($request);
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
        return $this->user->sendCode($request);
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
        return $this->user->restorePassword($request);
    }

	public function updateProfile(Request $request)
	{
        $request->validate([
            'name' => [
                'required',
                'string',
                'unique:users,name,' . $request->user()->id,
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
        return $this->user->updateProfile($request);
	}

    public function listsAnimes(Request $request){
        return $this->user->getListAnimes($request);
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

        return $this->anime->search($request);
    }

    public function animes(Request $request)
    {
        return $this->anime->animes($request);
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
        return $this->anime->getAnime($request);
    }

    public function calendar(Request $request)
    {
        return $this->anime->calendar();
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
        return $this->episode->getEpisodesByAnimeId($request);
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
        return $this->player->getPlayersByEpisodeId($request);
    }
    
}
