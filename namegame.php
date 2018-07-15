<?php
	// let me handle the errors
	libxml_use_internal_errors(true);
	set_error_handler('exceptions_error_handler',E_ALL);
	function exceptions_error_handler($severity, $message, $filename, $lineno) {
		if (error_reporting() == 0) {
			return;
		}
		if (error_reporting() & $severity) {
			throw new ErrorException($message, 0, $severity, $filename, $lineno);
	  }
	}

class nameGame {
	const CHOICES = 6;

	private $ROOT; // base path of this script
	private $data; // the people data used
	public $info; // the current variables for this session
	public $round; // pointer to the current round

	function __construct(){
		$this->ROOT = getcwd().'/';
		$this->data = (object)array();

		$this->restoreState();
	}
	
	function __destruct() {
		$_SESSION["nameGame"] = $this->info;
    }
	
	public function getGameState(){
		// a mini version of the info for use on the web page
		return (object)array(
			'active' => $this->info->active,

			'type' => $this->info->type,
			'mode' => $this->info->mode,
			'difficulty' => $this->info->difficulty,

			'guesses' => $this->getGuessLeft(),
			'wrong' => $this->round->wrong,
			'right' => (string)$this->round->right,

			'numRounds' => $this->info->rounds,
			'currentRound' => $this->info->currentRound+1,

			'roundScore' => $this->round->score,
			'totalScore' => $this->info->score,

			'roundTime' => $this->timerRead($this->info->round[$this->info->currentRound]->timer),
			'totalTime' => $this->timerRead($this->info->timer),

			'question' => $this->getQuestion(),
			'choices' => $this->getChoices()
		);
	}

	public function makeChoice($pick){
		// make sure they have choices left in this round
		// also don't double count the same choice if it somehow got sent again
		$this->round->remaining = $this->getGuessLeft();
		if( $pick=='' || !$this->round->remaining || in_array($pick,$this->round->wrong) )
			return false; // should never hit this, just a failsafe

		// again, shouldn't hit this, it means they already completed the round successfully
		if( $this->round->correct == 1 )
			return true;


			// increment attempts
		$this->round->attempts++;
		// echo $this->round->attempts;
		$this->round->remaining = $this->getGuessLeft();

		// record timer
		$duration = $this->timerRead($this->round->timer);


		$choice = $this->round->choice[$pick];
		$answer = $this->info->answer[$this->info->currentRound];
		if( $choice == $answer ) {
			$this->round->correct = 1;
			$this->round->right = $pick;
			$this->computeScore();
			return true;
		}
		else{
			$this->round->correct = 0;
			$this->round->wrong[] = $pick;
			return false;
		}
	}

	private function computeScore(){
		// any time under 10 seconds is added as bonus
		// hard mode gets 20 points for a correct guess
		// med mode gets 10 points for 1 guess, 8 points for 2 and 6 for the third try
		// easy mode gets 5 for getting it on the first try and 1 less for each attempt
		
		$multiplier = array(
			'easy' => 1,
			'med' => 2,
			'hard' => 3
		);

		// 1 second leeway for full 10 points
		$timeBonus = $this->round->timer->time<11?11-$this->round->timer->time:0;

		$initialScore = $this::CHOICES - $this->round->attempts;
		if(!$initialScore)
			$timeBonus = 0; // no bonus for no score. prevents guessing quickly for bonus if they are all wrong
		
		$this->round->score = ($initialScore+$timeBonus) * $multiplier[$this->info->difficulty];
		$this->info->score += $this->round->score;
	}

	public function clearGame(){
		// reset current game to begin a new one
		$this->info->active = false;
	}

	public function startGame($opt=null){
		if( !$opt )
			$opt = array();
		$this->info->type = $this->value($opt['type'],'name');
		$this->info->mode = $this->value($opt['mode'],'all');
		$this->info->difficulty = $this->value($opt['difficulty'],'easy');

		$this->loadData(); // get the list of names
		$this->generateNamePool();
		$this->info->active = true;
		$this->info->gameOver = false;
		$this->info->currentRound = 0;
		$this->info->answer = array(); // init the array

		// generate rounds, attempting 10
		$numKeys = count(array_keys($this->info->pool));
		$this->info->rounds = ($numKeys < 10)?$numKeys:10;

		$this->info->question = array();
		$this->info->round = array();
		for($i=0; $i<$this->info->rounds; $i++){
			$nameKeys = $this->getRandNames($this::CHOICES);
			$key = $nameKeys[0]; // first item is our answer
			shuffle($nameKeys); // now shuffle questions
			$this->info->answer[] = $key;
			$this->info->round[] = (object)array(
				'choice' => $nameKeys,
				'wrong' => array(), // attepted wrong answers
				'right' => '', // if correctly guessed this will populate for state save
				'attempts' => 0, // how many attempts it took to get right
				'remaining' => $this->getGuessLeft(), // how many attempts left
				'correct' => -1, // -1: not attempted, 0: wrong, 1: correct
				'score' => 0, // the points scored on this round
				'timer' => (object)array(
					'start' => 0, // start stamp for this round
					'stop' => 0, // end stamp for this round
					'time' => 0 // elapsed seconds
				)
			);
		}
		
		$this->timerStart($this->info->timer);
		$this->beginRound(0);
	}

	public function advanceRound(){
		// make sure the current round has completed
		if( $this->round->correct == -1 )
			return; // can't advance until round has completed

		// see if there is a next round or if the game is done
		$nextRound = $this->info->currentRound+1;
		if( $nextRound < $this->info->rounds ) {
			$this->beginRound($nextRound);
		}
		else
			$this->info->gameOver = true;

	}

	public function getResults(){
		$result = array();

		foreach($this->info->round as $idx => $r){
			$result[] = array(
				'round' => $idx+1,
				'guess' => $r->attempts,
				'time' => $r->timer->time,
				'score' => $r->score
			);
		}

		$this->info->gameOver = true;
		return $result;
	}

	public function getLeaderboard(){}

	public function saveHighScore($name){
		if( !$this->info->active )
			return;
	}

	private function beginRound($roundIdx){
		$this->info->currentRound = $roundIdx;
		$this->round = &$this->info->round[$roundIdx];
		$this->timerStart($this->round->timer);
	}

	private function restoreState(){
		// restore state
		session_start();
		//unset($_SESSION["nameGame"]);
		if( isset($_SESSION["nameGame"]) ) {
			$this->info = (object)$_SESSION["nameGame"];
		} else {
			$this->info = (object)array(
				'active' => false,
				'gameOver' => false,
				'type' => 'name', // name, face
				'mode' => 'all', // all, matt, mike
				'difficulty' => 'easy', // easy, med, hard
				'rounds' => 0, // the number of rounds in this game session
				'currentRound' => 0, // state of where we are at
				'score' => 0, // running total score for the game
				'timer' => (object)array(
					'start' => 0, // start stamp for this game
					'stop' => 0, // end stamp for this game
					'time' => 0 // elapsed seconds
				),
				'answer' => array(), // list of keys answers to each round, gaurenteed not to duplicate
				'round' => array( // template info for documentation purposes, will be cleared on init
					array(
						'choice' => array(), // array of arrays of key choices per round
						'wrong' => array(), // attepted wrong answers
						'right' => '', // if correctly guessed this will populate for state save
						'attempts' => 0, // how many attempts it took to get right
						'remaining' => 6, // how many attempts left
						'correct' => -1, // -1: not attempted, 0: wrong, 1: correct
						'score' => 0, // the points scored on this round
						'timer' => (object)array(
							'start' => 0, // start stamp for this round
							'stop' => 0, // end stamp for this round
							'time' => 0 // elapsed seconds
						)
					)
				),
				'pool' => array( // all the possible names with details by key
					"[KEY]"=> (object)array(
						"id" => "[KEY]", // duplicate reference for easy passing of the object
						"url" => "path to jpg",
						"firstName" => "",
						"lastName" => "",
						"jobTitle" => "",
						"validImage" => true, // all entries in the pool have valid images
						"isMat" => true, // for challange mode
						"isMike" => false // for challange mode
					)
				)
			);			
		}
		if( $this->info->active )
			$this->round = &$this->info->round[$this->info->currentRound];
	}

	private function timerStart(&$timer) {
		// init time values
		$timer->start = $timer->stop = microtime(TRUE);
		$timer->time = 0;
	}
	private function timerRead(&$timer) {
		if( !$this->info->active )
			return 0;
		$timer->stop = microtime(TRUE);
		$timer->time = round(($timer->stop - $timer->start));
		return $timer->time;
	}
	
	private function getQuestion(){
		if( !$this->info->active )
			return '';

		$questionID = $this->info->answer[$this->info->currentRound];
		$answer = $this->info->pool[$questionID];

		return ($this->info->type == 'face')?
			$answer->url:
			"<person>{$answer->firstName} {$answer->lastName}</person>".(empty($answer->jobTitle)?'':" <jobTitle>{$answer->jobTitle}</jobTitle>");
	}
	private function getChoices(){
		$result = array();

		if( !$this->info->active )
			return $result;

		$choices = $this->info->round[$this->info->currentRound]->choice;
		foreach( $choices as $idx => $choiceID){
			$pick = $this->info->pool[$choiceID];
			$result[] = ($this->info->type == 'name')?
				$pick->url:
				"<person>{$pick->firstName} {$pick->lastName}</person>".(empty($pick->jobTitle)?'':" <jobTitle>{$pick->jobTitle}</jobTitle>");
		}
		return $result;
	}

	private function getGuessLeft(){
		if( !$this->info->active )
			return 0;
		
		$guesses = array(
			'easy' => count($this->round->choice),
			'med' => 3,
			'hard' => 1
		);

		$possible = $guesses[$this->info->difficulty];
		$attempts = $this->round->attempts;
		
		return ($possible>$attempts)?$possible-$attempts:0;
	}

	private function loadData() {
		if( file_exists($this->ROOT.'data.json') ) {
			$data = file_get_contents($this->ROOT.'data.json');
			$this->data = json_decode($data);
			return;
		}
		else {
			$data = $this->loadFromURL('https://www.willowtreeapps.com/api/v1.0/profiles');
			$data = json_decode($data);
			foreach($data as $idx => $obj) {
				$url = $this->value($obj->headshot->url);
				$this->data->{$obj->id} = (object)array(
					'id' => $obj->id,
					'url' => $url,
					'firstName' => $this->value($obj->firstName),
					'lastName' => $this->value($obj->lastName),
					'jobTitle' => $this->value($obj->jobTitle),
					'validImage' => (!empty($url) && !preg_match('/featured-image/',$url) ),
					'isMat' => preg_match('/^mat/i',$obj->firstName)?true:false,
					'isMike' => preg_match('/^michael|^mike/i',$obj->firstName)?true:false
				);
			}
			file_put_contents($this->ROOT.'data.json',json_encode($this->data));
		}
		/*
			[id] => 5oV9G8eKisW0M0GUCo4eke
			[type] => *people
			[slug] => joel-garrett
			[jobTitle] => Software Engineer (not always defined, bad characters)
			[firstName] => Joseph
			[lastName] => Cherry
			[headshot] => stdClass Object
				[type] => *image
				[mimeType] => image/jpeg|png (not always defined)
				[id] => 1yUCBofluco4muYYsIOwms (sometimes no id configured)
				[url] => //image/Joseph_Cherry.jpg  (not always defined)
				[alt] => Joseph Cherry (sometimes sometimes generic)
				[height] => 170 (not always defined)
				[width] => 170 (not always defined)
			[socialLinks] => Array
			[bio] => Senior Product Researcher (not always defined)
		*/
	}

	private function loadFromURL($siteURI) {
		$curlSession = curl_init();
		curl_setopt($curlSession, CURLOPT_URL, $siteURI);
		curl_setopt($curlSession, CURLOPT_HEADER, 0);
		curl_setopt($curlSession, CURLOPT_TIMEOUT, 13);
		curl_setopt($curlSession, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curlSession, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curlSession, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:60.0) Gecko/20100101 Firefox/60.0");
		$data = curl_exec($curlSession);
		curl_close($curlSession);
		return $data;
	}

	private function generateNamePool(){
		$this->info->pool = array(); // clear the variable

		switch($this->info->mode){
			case 'matt':
				foreach($this->data as $key => $obj)
				if( $obj->isMat )
					$this->info->pool[$key] = $obj;
				break;
	
			case 'mike':
				foreach($this->data as $key => $obj)
					if( $obj->isMike )
						$this->info->pool[$key] = $obj;
				break;

			default:
				$mode = 'face';
			case 'reverse':
			case 'face':
				foreach($this->data as $key => $obj)
					if( $obj->validImage )
						$this->info->pool[$key] = $obj;
		}
	}

	private function getRandNames($numToGenerate=0) {
		$keys = array_keys($this->info->pool);
		if( $numToGenerate < 1 )
			$numToGenerate = $this::CHOICES;
		if( $numToGenerate > count($keys) )
			$numToGenerate = count($keys);

		$randomList = array();
		$allowPrevAnswers = false; // gaurentee first choice is not a previous question
		while (count($randomList) < $numToGenerate) {
		  $randomKey = $keys[mt_rand(0, count($keys)-1)];
		  if( $this->data->$randomKey->validImage
				&& !in_array($randomKey,$randomList) // prevent dup choices
		  		&& ($allowPrevAnswers || !in_array($randomKey,$this->info->answer)) ){
			$randomList[] = $randomKey;
			$allowPrevAnswers = true;
		  }
		}
		return $randomList;
	}

	private function value(&$value,$default=''){
		return isset($value)?$value:$default;
	}
	
}