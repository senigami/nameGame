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

switch($action) {
    case 'newGame':
        $game->clearGame();
        break;

    case 'startGame':
        $game->startGame();
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
        $game->leaderboard = $game->getLeaderboard();
        break;
    
    case 'saveHighScore':
        $game->saveHighScore( content('name') ); // also resets game
        $game->leaderboard = $game->getLeaderboard();
        break;

	default:
        $response->success = false;
    // bad action abort
}

$response->status = '';
$response->game = $game->getGameState();
// $response->game = $game->round;
//$response->game = $game->info;

echo json_encode($response);

function content($theVar,$defaultValue=null) { // get submitted values
    return isset($_REQUEST[$theVar])?$_REQUEST[$theVar]:$defaultValue;
}
