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

    public function getRecents(){
        try {
            $recents = $this->select('id','name','poster')->orderBy('created_at', 'desc')->take(10)->get();
            return [
                'status' => 'success',
                'data' => $recents
            ];
        } catch (Exception $e) {
            return [
                'message' => $e->getMessage(),
                'status' => 'error',
                'data' => []
            ];
        }
    }

    public function getLatinos(){
        try {
            $data = $this
                ->select('animes.id', 'name', 'poster', 'vote_average','status', \DB::raw('MAX(number) as number'),\DB::raw('MAX(players.id) as idplayer'))
                ->LeftJoin('episodes', 'episodes.anime_id', '=', 'animes.id')
                ->LeftJoin('players','episode_id', '=', 'episodes.id')
                ->where('players.languaje', 1)
                ->groupBy('animes.id')
                ->orderBy('idplayer','desc')
                ->limit(14)
                ->get();

            return [
                'status' => 'success',
                'data' => $data
            ];
        } catch (Exception $e) {
            return [
                'message' => $e->getMessage(),
                'status' => 'error',
                'data' => []
            ];
        }
    }

    public function getPopulars(){
        try {
            $populars = $this->select('id','name','poster','vote_average')->orderBy('vote_average', 'desc')->take(10)->get();
            return [
                'status' => 'success',
                'data' => $populars
            ];
        } catch (Exception $e) {
            return [
                'message' => $e->getMessage(),
                'status' => 'error',
                'data' => []
            ];
        }
    }

    public function getMoreViews(){
        try {
            $moreViews = $this->select('id','name','poster','views_app')->orderBy('views_app', 'desc')->take(10)->get();
            return [
                'status' => 'success',
                'data' => $moreViews
            ];
        } catch (Exception $e) {
            return [
                'message' => $e->getMessage(),
                'status' => 'error',
                'data' => []
            ];
        }
    }

    public function getAnimeSearch($request){
        try {
            $search = $this->select('id', 'name', 'poster')
                ->orderBy('name')
                ->where('name','LIKE',"%{$request->search}%")
                ->orwhere('name_alternative','LIKE',"%{$request->search}%")
                ->orwhere('overview','LIKE',"%{$request->search}%")
                ->limit(24)
                ->get();
            return [
                'status' => 'success',
                'data' => $search
            ];
        } catch (Exception $e) {
            return [
                'message' => $e->getMessage(),
                'status' => 'error',
                'data' => []
            ];
        }
    }

    public function getAnime($request){
        try {
            $anime = $this->where('id', $request->id)->first();
            return [
                'status' => 'success',
                'data' => $anime
            ];
        } catch (Exception $e) {
            return [
                'message' => $e->getMessage(),
                'status' => 'error',
                'data' => []
            ];
        }
    }

    public function getCalendar(){
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
					'data' => $animes->toArray(),
				];
			}

            return [
                'status' => 'success',
                'data' => $formattedData
            ];
        } catch (Exception $e) {
            return [
                'message' => $e->getMessage(),
                'status' => 'error',
                'data' => []
            ];
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

	public function getAnimes($request)
    {
        try {
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
            return $data;
        } catch (Exception $e) {
            return [
                'message' => $e->getMessage(),
                'status' => 'error',
                'data' => []
            ];
        }
    }

}
