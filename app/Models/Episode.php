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
            $episodes = $this->orderBy('created_at', 'desc')->with(['anime' => function ($q) {
                $q->select('id','name','slug','banner');
            }])->take(10)->get();
            return [
                'status' => 'success',
                'data' => $episodes
            ];
        } catch (Exception $e) {
            return [
                'message' => $e->getMessage(),
                'status' => 'error',
                'data' => []
            ];
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
