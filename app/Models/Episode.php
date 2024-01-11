<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Exception;

class Episode extends Model
{
    use HasFactory;

    protected $casts = [
        'id' => 'integer',
        'anime_id' => 'integer',
    ];

    public function players()
    {
        return $this->hasMany(\App\Models\Player::class);
    }

    public function anime()
    {
        return $this->belongsTo(\App\Models\Anime::class);
    }

    public function getRecents(){
        try {
            return $this
                ->select('episodes.id','name', 'number', 'banner', 'animes.id as animeId')
                ->join('animes', 'animes.id', '=', 'episodes.anime_id')
                ->orderBy('episodes.created_at', 'desc')
                ->take(10)
                ->get();
        } catch (Exception $e) {
            return array('message' => $e->getMessage());
        }
    }

    public function getEpisodesByAnimeId($request){
        try {
            return response()->json([
                'data' => $this->where('anime_id', $request->anime_id)->orderBy('number', 'desc')->get(),
            ], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 401);
        }
    }

}
