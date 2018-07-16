<?php
// gets the action requested from the game
// minimal content is sent, other values are from the saved session
// all responses are done with a json output
header("Content-type: application/json");
error_reporting(E_ERROR | E_PARSE);

// get the users game info from the session via the game class
require_once("namegame.php");
$game = new nameGame();

// status defaults to false in case there is an error
$action = content('action');
$response = (object)array(
	"success" => false, // status of the procedure
	"status" => 'there was a problem accessing the server' // message to display on completion
);

// various actions used by the server
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

// we made it this far we are fine
$response->success = true;
$response->status = '';

echo json_encode($response);

// save function for retrieving submitted values, won't throw an error if nothing was sent
// works with get and post, good for testing, actual game uses post
function content($theVar,$defaultValue=null) { 
    return isset($_REQUEST[$theVar])?$_REQUEST[$theVar]:$defaultValue;
}
