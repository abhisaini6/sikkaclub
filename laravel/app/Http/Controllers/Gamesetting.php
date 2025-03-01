<?php
                    
//         define('DB_SERVER', 'localhost');
// define('DB_USERNAME', 'seekosoft_adbanaouser');
// define('DB_PASSWORD', 'seekosoft_adbanaouser@11');
// define('DB_NAME', 'seekosoft_adbanao');
// // Try connecting to the Database
// $conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// //Check the connection
// if($conn == false){
//     dir('Error: Cannot connect');
// }
// $sql3 = "SELECT value FROM emredperiod WHERE category=game_between_time_end and id='14'";
// $result3 =$conn->query($sql3);
// $row3 = mysqli_fetch_assoc($result3);
// @$period=$row3['value'];


namespace App\Http\Controllers;

use App\Models\Gameresult;
use App\Models\Setting;
use App\Models\Userbit;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Incrementor;

class Gamesetting extends Controller
{
    
    public function crash_plane()
    {
        return 1;
    }
    public function game_existence(Request $r)
    {
        $event = $r->event;
        if ($event == "check") {
            $new = Setting::where('category', 'game_status')->where('value', '0')->first();
            
            if ($new || (session()->has('gamegenerate') && session()->get('gamegenerate') == 1)) {
                return array('data'=>true);
            }else{
                return array('data'=>false);
            }
            return array('data'=>false);
        }
    }
    public function new_game_generated(Request $r)
    {
        $new = Setting::where('category', 'game_status')->update(['value' => '0']);
        $r->session()->put('gamegenerate','1');
        return response()->json(array("id" => currentid()));
    }
    
public function increamentor(Request $r)
{
    $gamestatusdata = Setting::where('category', 'game_status')->first();
    $res = 0;

    if ($gamestatusdata) {
        // Retrieve the stored array from cache
        $cachedValues = \Cache::get('game_values', []);

        // If the array is empty, generate a new one
        if (empty($cachedValues)) {
            // Create four separate arrays
            $values1 = [];
            $values2 = [];
            $values3 = [];
            $values4 = [];

            // Generate values for each array
            for ($i = 0; $i < 20; $i++) {
                $values1[] = $this->generateRandomValue(1.0, 1.85);
            }
            for ($i = 0; $i < 4; $i++) {
                $values2[] = $this->generateRandomValue(2.1, 3.0);
            }
            for ($i = 0; $i < 1; $i++) {
                $values3[] = $this->generateRandomValue(3.0, 10.0);
            }
            for ($i = 0; $i < 7; $i++) {
                $values4[] = $this->generateRandomValue(1.0, 1.12); // Filling to ensure a total of 20
            }

            // Merge all arrays
            $cachedValues = array_merge($values1, $values2, $values3, $values4);

            // Store the generated array in cache
            \Cache::put('game_values', $cachedValues);

            // Log the generated values for debugging
            \Log::info('Generated new values: ' . implode(', ', $cachedValues));
        }

        // Log the current values in cache for debugging
        \Log::info('Cached values before use: ' . implode(', ', $cachedValues));

        // Pick a random index from the cached array
        $randomIndex = rand(0, count($cachedValues) - 1); // Select a random index
        $res = $cachedValues[$randomIndex]; // Get the value at the random index

        // Remove the selected value from the array
        unset($cachedValues[$randomIndex]);

        // Reindex the array after removing the value
        $cachedValues = array_values($cachedValues);

        // Save the updated array back to the cache
        \Cache::put('game_values', $cachedValues);

        // Log the chosen value for debugging
        \Log::info('Random value chosen: ' . $res);

        // Return the result in JSON response
        return response()->json(['status' => true, 'result' => $res]);
    } else {
        \Log::error('Game status data not found.');
        return response()->json(['status' => false, 'error' => 'Game status data not found.']);
    }
}

// Generate a random value within the specified range
public function generateRandomValue($min, $max) {
    return round(mt_rand($min * 100, $max * 100) / 100, 2); // Generates a float value rounded to two decimal places
}
    // public function increamentor(Request $r)
    // {
    //     // return 1.7;
    //     $totalbet = Userbit::where('gameid',currentid())->count();
    //     $totalamount = Userbit::where('gameid',currentid())->sum('amount');
    //     if ($totalbet == 0) {
    //         return rand(4,11);
    //     }else{
    //         $randomresult = array(1.1,1.1,1.2,1.3,1.4,1.5,1.6,1.7,1.8,1.9);
    //         $res = $randomresult[rand(0,8)];
    //         if (session()->has('result')) {
    //             return session()->get('result');
    //         }
    //         $r->session()->put('result',$res);
    //         return $res;
    //     }
    //     return rand(setting('start_range_game_timer')*10, setting('end_range_game_timer')*10) / 10;
    // }
    
    public function game_over(Request $r)
    {
        $r->session()->forget('result');
        $result = Gameresult::where('id', currentid())->update([
            "result" => number_format($r->last_time, 2),
        ]);
        $alluserbit = Userbit::where('gameid', currentid())->where('status', 0)->get();
        foreach ($alluserbit as $key) {
			if(floatval($r->last_time) <= 1.20){
			$result = 0;
		    }else{
			$result = $r->last_time;
			}
            $finalamount = floatval($key->amount) * floatval($result);
            Userbit::where('id', $key->id)->update(["status"=> 1]);
            // addwallet($key->userid,$finalamount);
        }
        $new = Setting::where('category', 'game_status')->update(['value' => '0']);
        $r->session()->put('gamegenerate','0');
        $result = new Gameresult;
        $result->result = "pending";
        $result->save();
        return wallet(user('id'));
    }

     public function betNow(Request $r)
    {
        $status = false;
        $message = "Something went wrong!";
        $returnbets = array();
        for($i=0; $i < count($r->all_bets); $i++){
		$result = new Userbit;
        $result->userid = user('id');
        $result->amount = $r->all_bets[$i]['bet_amount'];
        $result->type = $r->all_bets[$i]['bet_type'];
        $result->gameid = currentid();
        $result->section_no = $r->all_bets[$i]['section_no'];
        if ($r->all_bets[$i]['bet_amount'] < wallet(user('id'), 'num')) {
            if ($result->save()) {
                $status = true;
                array_push($returnbets, [
                    "bet_id" => $result->id,
                ]);
				/*array_push($returnbets, [
                    "bet_id" => currentid(),
                ]);*/
                $exact_wallet_balance = addwallet(user('id'), floatval($r->all_bets[$i]['bet_amount']), "-");
                $data = array(
                    "wallet_balance" => wallet(user('id')),
                    "return_bets" => $returnbets
                );
                $message = "";
            }
        } else {
            $status = false;
            $data = array();
            $message = "Insufficient fund!!";
        }
		}
        $response = array("isSuccess" => $status, "data" => $data, "message" => $message);
        return response()->json($response);
    }



    public function currentlybet()
    {
        $allbets = Userbit::where("gameid", currentid())->join('users','users.id','=','userbits.userid')->get();
        $currentGameBet = $allbets;
        for ($i=0; $i < rand(400,900); $i++) { 
            $currentGameBet[]=array(
                "userid" => rand(10000,50000),
                "amount" => rand(999,9999),
				"image"  => "/images/avtar/av-".rand(1,72).".png"
            );
        }
        $currentGame = array("id"=>currentid());
        $currentGameBetCount = count($currentGameBet);
        $response = array("currentGame" => $currentGame, "currentGameBet" => $currentGameBet, "currentGameBetCount" => $currentGameBetCount);
        return response()->json($response);
    }
    public function my_bets_history(){
        $userid = user('id');
        $userbets = Userbit::where("userid", $userid)->where('status',1)->where('created_at', '>=', Carbon::today()->toDateString())->orderBy('id','desc')->get();
        return response()->json($userbets);
    }
	public function cashout(Request $r){
		$game_id = $r->game_id;
		$bet_id = $r->bet_id;
		$win_multiplier = $r->win_multiplier;
		$cash_out_amount = 0;
		$status = false;
        $message = "";
        $data = array();
		$result = resultbyid($game_id) == 0 ? $win_multiplier : resultbyid($game_id);
		if(floatval($result) <= 1.20){
			$result = 0;
		}
		$cash_out_amount = floatval(userbetdetail($bet_id,'amount'))*floatval($result);
		addwallet(user('id'),$cash_out_amount); 
		$data = array(
                    "wallet_balance" => wallet(user('id'),"num"),
                    "cash_out_amount" => $cash_out_amount
                );
        Userbit::where('id', $bet_id)->update(["status"=> 1,"cashout_multiplier"=>$win_multiplier]);
        $status = true;
		$response = array("isSuccess" => $status, "data" => $data, "message" => $message);
        return response()->json($response);
	}
	
	public function cronjob(){
	    //0 = Game end & statrting soon
	    //1 = Game start & and is in proccess
	    $gamestatusdata = Setting::where('category', 'game_status')->first();
	    $game_status = 0;
	    if($gamestatusdata){
	        $game_status = $gamestatusdata->value;
	    }
	    if($game_status == 1){
	    $last_start_time = Setting::where('category', 'game_start_time')->first()->value;
	    $last_till_time = Setting::where('category', 'game_between_time')->first()->value;
	    $bothdifference = datealgebra($last_start_time, '+', ($last_till_time/1000).' seconds', $format = "Y-m-d h:i:s");
	    if(strtotime(date('Y-m-d h:i:s')) >= strtotime($bothdifference)){
	        $gamestatusdata = Setting::where('category', 'game_status')->update([
	             "value"  => 0
	             ]);
	    }
	    }elseif($game_status == 0){
	         $gamestatusdata = Setting::where('category', 'game_status')->update(["value"  => 1]);
	         $gamestatusdata = Setting::where('category', 'game_start_time')->update(["value"  => date('Y-m-d h:i:s')]);
	         $gamestatusdata = Setting::where('category', 'game_between_time')->update(["value"  => 10000]);
	    }else{}
	}
}























