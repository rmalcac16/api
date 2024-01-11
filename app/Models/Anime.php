<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Animelhd\AnimesFavorite\Traits\Favoriteable;
use Animelhd\AnimesView\Traits\Vieweable;
use Animelhd\AnimesWatching\Traits\Watchingable;

class Anime extends Model
{
    use HasFactory;

    use Favoriteable;
	use Vieweable;
	use Watchingable;

    public function episodes()
    {
        return $this->hasMany(\App\Models\Episode::class);
    }

    public function mylist()
    {
        return $this->hasMany(\App\Models\MyList::class);
    }

    public function getRecents(){
        try {
            return $this->select('id','name','poster')->orderBy('created_at', 'desc')->take(10)->get();
        } catch (Exception $e) {
            return array('message' => $e->getMessage());
        }
    }

    public function getLatinos(){
        try {
            return $this
            ->select('animes.id', 'name', 'poster', 'vote_average','status', \DB::raw('MAX(number) as number'),\DB::raw('MAX(players.id) as idplayer'))
            ->LeftJoin('episodes', 'episodes.anime_id', '=', 'animes.id')
            ->LeftJoin('players','episode_id', '=', 'episodes.id')
            ->where('players.languaje', 1)
            ->groupBy('animes.id')
            ->orderBy('idplayer','desc')
            ->limit(14)
            ->get();
        } catch (Exception $e) {
            return array('message' => $e->getMessage());
        }
    }

    public function getPopulars(){
        try {
            return $this->select('id','name','poster','vote_average')->orderBy('vote_average', 'desc')->take(10)->get();
        } catch (Exception $e) {
            return array('message' => $e->getMessage());
        }
    }

    public function getMoreViews(){
        try {
            return $this->select('id','name','poster','views_app')->orderBy('views_app', 'desc')->take(10)->get();
        } catch (Exception $e) {
            return array('message' => $e->getMessage());
        }
    }

    public function search($request){
        try {
            $data = $this->select('id', 'name', 'poster')
                ->orderBy('name')
                ->where('name','LIKE',"%{$request->search}%")
                ->orwhere('name_alternative','LIKE',"%{$request->search}%")
                ->orwhere('overview','LIKE',"%{$request->search}%")
                ->limit(24)
                ->get();
            return response()->json(['data' => $data], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 401);
        }
    }

    public function getAnime($request){
        try {
            $anime = $this->where('id', $request->id)->first();
            $data = [
                'anime' => $anime,
                'recommendations' => $this->getRecommendations($anime)
            ];
            return response()->json($data, 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 401);
        }
    }

    public function calendar(){
        try {
            $data = $this->select('id','name','poster','broadcast')->orderBy('broadcast')->where('status', 1)->get();
			$daysOfWeek = [
				1 => 'Lunes',
				2 => 'Martes',
				3 => 'Miercoles',
				4 => 'Jueves',
				5 => 'Viernes',
				6 => 'Sábado',
				7 => 'Domingo'
			];
			$groupedData = $data->groupBy(function ($item) use ($daysOfWeek) {
				return $daysOfWeek[$item['broadcast']] ?? 'none';
			});
			$formattedData = [];
			foreach ($groupedData as $day => $animes) {
				$formattedData[] = [
					'day' => $day,
					'animes' => $animes->toArray(),
				];
			}
            return response()->json(['data' => $formattedData], 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 401);
        }
    }

    public function getRecommendations($anime)
    {
		$first_name = explode(' ',trim($anime->name));
		$first_name = $first_name[0];
		$genres = explode(',',trim($anime->genres));
		$first_genre = '';
		$second_genre = '';
		if(count($genres) >= 2){
			$randoms = array_rand($genres, 2);
			$first_genre = $genres[$randoms[0]];
			$second_genre = $genres[$randoms[1]];
		}
        return $this->select('id','name','banner')
			->where('genres','LIKE',"%{$first_genre}%")
			->where('genres','LIKE',"%{$second_genre}%")
			->where('slug','!=',$anime->slug)
			->limit(10)
			->inRandomOrder()
			->get();
    }
    

	public function animes($request)
    {
        try {
            if($request->list == "populars") {
                if($request->page > 1)
                    return response()->json(["data" => []], 200);
                return response()->json(["data" => $this->getPopulars()], 200);
            }else if ($request->list == "recents") {
                if($request->page > 1)
                    return response()->json(["data" => []], 200);
                return response()->json(["data" => $this->getRecents()], 200);
            }else if ($request->list == "moreviews") {
                if($request->page > 1)
                    return response()->json(["data" => []], 200);
                return response()->json(["data" => $this->getMoreViews()], 200);
            }else if( $request->list == "latinos") {
                if($request->page > 1)
                    return response()->json(["data" => []], 200);
                return response()->json(["data" => $this->getLatinos()], 200);
            }

            $data = $this
                ->select('id', 'name', 'poster', 'aired')
                ->orderBy('aired','desc');
            if($request->type){
                if($request->type != 'all')
                    $data = $data->where('type',$request->type);
            }
            if(isset($request->status)){
                if($request->status != 'all')
                    $data = $data->where('status',$request->status);
            }
            if($request->year){
                if(is_numeric($request->year)){
                    $data = $data->whereYear('aired',$request->year);
                }
            }
            if($request->genre){
                if($request->genre != 'all')
                    $data = $data->where('genres','LIKE',"%{$request->genre}%");
            }
            $data = $data->simplePaginate(28);
            return response()->json($data, 200);
        } catch (Exception $e) {
            return response()->json(['message' => $e->getMessage()], 401);
        }
    }

}
