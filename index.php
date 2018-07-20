<?php
  require_once("namegame.php");
  $game = new nameGame();
?><html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Name Game</title>
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">
	<link rel="stylesheet" href="namegame.css">
</head>
<body>
	
  <nav id="navbar" class="navbar navbar-default navbar-fixed-top">
    <div class="container-fluid">
      <div class="navbar-header">
        <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#bs-example-navbar-collapse-1" aria-expanded="false">
          <span class="sr-only">Toggle navigation</span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
        </button>
        <a class="navbar-brand" href="#"><img src="https://www.willowtreeapps.com/img/logo-black.png" height=30> The Name Game</a>
      </div>
      <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
        <ul class="nav navbar-nav">
          <li class="xactive"><a href="#" id="newGame_btn" class="btn">Start New Game</a></li>
        </ul>
        <ul class="nav navbar-nav navbar-right" id="gameMenuItems">
          <li class="active"><a href="#" id="menuTimer">Timer: <b></b></a></li>
          <li class="active"><a href="#" id="menuScore">Score: <b></b></a></li>
        </ul>
      </div>
    </div>
  </nav>	
	
  <div id="mainStage">
  
    <div class="container" id="startSplash" style="display: none;">
      <div class="row extraRoom">
        <div class="col-xs-12">
          <h1 class="centered">Welcome to the Name Game!<br />
          <small>Set your game options and learn your fellow employees names by having fun.</small></h1>
        </div>
      </div>

      <form id="gameSelection">
        <div class="row" id="gameOptions">

          <div class="col-xs-12 col-sm-5 col-md-4">
            <h4>Play Type:</h4>
            <div id="gameType" class="btn-group-vertical" data-toggle="buttons">
              <label class="btn btn-default active">
                <input type="radio" name="gameType" value="face" id="gameType1" autocomplete="off" checked>
                <span class="glyphicon glyphicon-user" aria-hidden="true"></span> Match the Face to the Name
              </label>
              <label class="btn btn-default">
                <input type="radio" name="gameType" value="name" id="gameType2" autocomplete="off">
                <span class="glyphicon glyphicon-screenshot" aria-hidden="true"></span> Match the Name to the Face
              </label>
            </div>
          </div>

          <div class="col-xs-12 col-sm-7 col-md-5">
            <h4>Difficulty:</h4>
            <div id="gameDifficulty" class="btn-group-vertical" data-toggle="buttons">
              <label class="btn btn-default active">
                <input type="radio" name="gameDifficulty" value="easy" id="gameDifficulty1" xautocomplete="off" checked>
                <span class="glyphicon glyphicon-pawn" aria-hidden="true"></span> Easy: Keep guessing untill you get it right
              </label>
              <label class="btn btn-default">
                <input type="radio" name="gameDifficulty" value="med" id="gameDifficulty2" xautocomplete="off">
                <span class="glyphicon glyphicon-knight" aria-hidden="true"></span> Medium: 3 guesses, 2x the points
              </label>
              <label class="btn btn-default">
                <input type="radio" name="gameDifficulty" value="hard" id="gameDifficulty3" xautocomplete="off">
                <span class="glyphicon glyphicon-king" aria-hidden="true"></span> Hard: Only 1 guess, 3x the points
              </label>
            </div>
          </div>
        
          <div class="col-xs-12 col-sm-5 col-md-3">
            <h4>Challenge Mode:</h4>
            <div id="gameMode" class="btn-group-vertical" data-toggle="buttons">
              <label class="btn btn-default active">
                <input type="radio" name="gameMode" value="all" id="gameMode1" autocomplete="off" checked>
                <span class="glyphicon glyphicon-picture" aria-hidden="true"></span> Use all names (default)
              </label>
              <label class="btn btn-default">
                <input type="radio" name="gameMode" value="matt" id="gameMode2" autocomplete="off">
                <span class="glyphicon glyphicon-fire" aria-hidden="true"></span> Everyone is named Matt
              </label>
              <label class="btn btn-default">
                <input type="radio" name="gameMode" value="mike" id="gameMode3" autocomplete="off">
                <span class="glyphicon glyphicon-fire" aria-hidden="true"></span> Everyone is named Mike
              </label>
              
            </div>
          </div>
        
        </div>
      </form>

      <div class="row extraRoom">
        <div class="col-xs-12" style="text-align: center;">
          <button id="startGame" type="button" class="btn btn-success btn-md">
            <span class="glyphicon glyphicon-play-circle" aria-hidden="true"></span> Let's Play!
          </button>
        </div>
      </div>

      <div class="row">
        <div class="col-xs-12 hallOfFame">
          <h2>Hall of Fame</h2> 
          <table class="table table-condensed table-striped">
            <thead>
              <th>Rank</th>
              <th>Name</th>
              <th>Score</th>
              <th>Date</th>
              <th>Type</th>
              <th>Difficulty</th>
              <th>Mode</th>
            </thead>
            <tbody>
            </tbody>
          </table>
        </div>
      </div>
    
    </div>

    <div class="container" id="gameContent" style="display: none;">
      <div class="row">
        <div class="col-xs-12">
          Round: 
          <div class="progress">
            <div id="gameProgress" class="progress-bar" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: 10%;">
              1 of 10
            </div>
          </div>
        </div>
      </div>

      <div class="row extraRoom">
        <div class="col-xs-12">
          <h1 class="centered">Match the face to the name<br />
          <small>Guesses Remaining: <b id="remaining">6</b><span id="bonusTimer">
            <br />Timer Bonus: <b></b></span></small></h1>
        </div>
      </div>

      <div class="row">
        <div class="col-xs-12 extraRoom">
          <h2 class="centered">Who is:</h2>
          <p id="theQuestion"></p>
        </div>
      </div>

      <div id="theChoices" class="row">
      </div>


    </div>



    <div class="container" id="gameResults" style="display: none;">

      <div class="row">
        <div class="col-xs-12">
          <form id="highScore" class="form-inline">
            <h1>Congratulations!</h1>
            <h3>You have made the Hall of Fame Leaderboard!</h3>            
            <div class="form-group">
              <label for="userName">Name</label>
              <input type="text" class="form-control" id="userName" placeholder="Jane Doe">
            </div>
            <button id="highScore_btn" class="btn btn-default">Add My Name</button>
          </form>
        </div>
      </div>

      <div class="row">
        <div class="col-xs-12 col-sm-6 col-md-4">
          <h2>Your Game Results</h2> 
          <table class="table table-condensed table-striped " id="gameStats">
            <thead>
              <th>Round</th>
              <th>Guesses</th>
              <th>Time</th>
              <th>Score</th>
            </thead>
            <tbody>
            </tbody>
          </table>
        </div>

        <div class="col-md-offset-1 col-xs-12 col-sm-6 col-md-7 hallOfFame">
          <h2>Hall of Fame</h2> 
          <table class="table table-condensed table-striped">
            <thead>
              <th>Rank</th>
              <th>Name</th>
              <th>Score</th>
              <th>Date</th>
              <th>Type</th>
              <th>Difficulty</th>
              <th>Mode</th>
            </thead>
            <tbody>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

	<footer class="footer">
    <p class="text-muted">
      &copy; 2018 Steven Dunn. <a href="https://github.com/senigami/nameGame" target="_blank">View the game source on GitHub</a>
    </p>
	</footer>
	
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
	<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
	<script src="namegame.js"></script>
  <script type="text/javascript">
    var gameInfo = <?=json_encode($game->getGameState())?>;
    var leaderboard = <?=json_encode($game->getLeaderboard())?>;
  </script>
  <!-- Global site tag (gtag.js) - Google Analytics -->
  <script async src="https://www.googletagmanager.com/gtag/js?id=UA-587088-1"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());

    gtag('config', 'UA-587088-1');
  </script>
</body>
</html>
