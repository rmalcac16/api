<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    use HasFactory;

    public function getPlayersByEpisodeId($request){
        try {
            $players = $this
                ->select('code', 'server_id', 'languaje', 'title', 'embed', 'servers.status')
                ->where('animes.id', $request->anime_id)
                ->where('episodes.id', $request->episode_id)
                ->join('episodes', 'episodes.id', 'players.episode_id')
                ->join('animes', 'animes.id', 'episodes.anime_id')
                ->join('servers', 'servers.id', 'players.server_id')
                ->get();
            $groupedData = $players->groupBy('languaje')->map(function ($group) {
                return $group->values();
            });
            $groupedData = $groupedData->all(); 
            $response = [];
            $response[0] = $groupedData[0] ?? [];
            $response[1] = $groupedData[1] ?? [];
            return [
                'status' => 'success',
                'data' => $response
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
