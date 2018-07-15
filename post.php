<?php
header("Content-type: application/json");
error_reporting(E_ERROR | E_PARSE);
require_once("namegame.php");
$game = new nameGame();
// $game->startGame();
  
$action = content('action');
$response = (object)array(
	"success" => false, // status of the procedure
	"status" => 'there was a problem saving' // message to display on completion
);

$response->success = true;
$response->status = '';

switch($action) {
    case 'newGame':
        $game->clearGame();
        break;

    case 'startGame':
        $game->startGame(array(
            'type' => content('type'),
            'mode' => content('mode'),
            'difficulty' => content('difficulty')
        ));
        break;

    case 'checkChoice':
        $response->result = $game->makeChoice( content('choice') );
        $response->pick = content('choice');
        break;

    case 'nextRound':
        $game->advanceRound();
        break;

    case 'getResults':
        $response->table = $game->getResults();
        $response->leaderboard = $game->getLeaderboard();
        break;
    
    case 'getLeaderBoard':
        $response->leaderboard = $game->getLeaderboard();
        break;

    case 'saveHighScore':
        $game->saveHighScore( content('name') );
        $response->leaderboard = $game->getLeaderboard();
        $game->clearGame(); // also resets game
        break;

	default:
        $response->success = false;
    // bad action abort
}
$response->game = $game->getGameState();

// $response->game = $game->round;
//$response->game = $game->info;

echo json_encode($response);

function content($theVar,$defaultValue=null) { // get submitted values
    return isset($_REQUEST[$theVar])?$_REQUEST[$theVar]:$defaultValue;
}
