<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Exception;

class Episode extends Model
{
    use HasFactory;

    public function getRecents(){
        try {
            $episodes = $this->orderBy('created_at', 'desc')->take(10)->get();
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
            $episodes = $this->where('anime_id', $request->anime_id)->orderBy('number', 'desc')->get();
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

}
