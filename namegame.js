$(function(){
    game.init(); // begin game code on page load
});

var game = {
    // set up variables for this game sudo class
    currentScreen: '',
    leaderboard: [],
    menuTimerInterval: null,
    menuTimerVal: 0,
    bonusTimerInterval: null,
    bonusTimerVal: 0,

    info: { // reference values, is set by server content
        active: false,

        type: 'face',
        difficulty: 'easy',
        mode: 'all',

        choices: [],
        currentRound: 0,
        numRounds: 0,
        question: '',
        remaining: 1
    },

    init: function(){
        // set buttons to trigger
        $('#newGame_btn').click(function(){
            game.submitData('newGame',{},function(data){
                game.info = data.game;
                game.updateDisplay();
            });
        });
        
        $('#startGame').click(function(){
            var params = {
                type: game.btnGroup('gameType'),
                difficulty: game.btnGroup('gameDifficulty'),
                mode: game.btnGroup('gameMode')
            };
            game.submitData('startGame',params,function(data){
                game.info = data.game;
                game.updateDisplay();
            });
        });

        $('#theChoices').on('click','#showResults', function(){
            game.submitData('getResults',{},game.showResults);
        });

        $('#theChoices').on('click','#nextRound', function(){
            game.submitData('nextRound',{},function(data){
                game.info = data.game;
                game.updateDisplay();
            });
        });
        
        $('#theChoices').on('click','.pick', game.checkPick);

        $('#highScore_btn').click(function(){
            var params = {
                name: $('#userName').val()
            };
            game.submitData('saveHighScore',params,function(data){
                game.info = data.game;
                game.leaderboard = data.leaderboard;
                game.drawLeaderBoard();
                game.updateDisplay();
            });
        });

        // initialize the game data on load to be able to restore state is page is refreshed
        game.info = gameInfo;
        game.leaderboard = leaderboard;
        $('#userName').val(game.info.userName);

        // refresh the display to match the current game state
        game.drawLeaderBoard();
        game.updateDisplay();
    },

    showSplashScreen: function(){
        // initial entry page, make sure all data that shouldn't be shown is hidden
        $('#gameContent').hide();
        $('#gameResults').hide();
        $('#gameMenuItems').hide();

        // set the button state to the last selected option
        game.btnGroup('gameType', game.info.type);
        game.btnGroup('gameDifficulty', game.info.difficulty);
        game.btnGroup('gameMode', game.info.mode);

        // show the rendered content
        $("#startSplash").show();
    },

    btnGroup: function (id, val){
        // my code to work around jQuery's lack of good control for input groups
        // get all the group buttons for the specfic form element name
        var group = $("input[name='"+id+"']");
        if(val) // if setting a value we need to do some display cleanup
        {
            // remove current states
            group.parent().removeClass('active');
            for(i=0;  i < group.length;  i++)
                if(group[i].value == val){
                    // set the state we want and update bootstrap item to match
                    $(group[i]).parent().addClass('active');
                    group[i].checked = true;
                }
        }
        // if we are just querying the value then return the value of the selected item
        for(i=0;  i < group.length;  i++)
            if(group[i].checked)
                return group[i].value;
        return;
    },
    
    showGameContent: function(){
        // for game mode need to make sure other visuals are hidden
        $("#startSplash").hide();
        $('#gameMenuItems').show();
   
        // update the round info
        game.updateProgressBar();
        $("#remaining").text(game.info.remaining);
        game.updateQuestion();
        game.updateChoices();

        // mark all bad choices
        $.each(game.info.wrong,function(idx,num){
            $('#pick_'+num).parent().addClass('wrongChoice');
            $('#pick_'+num+'.named').addClass('disabled btn-danger').removeClass('pick btn-default');
        });
        $(".wrongChoice b").html('<span class="glyphicon glyphicon-ban-circle"></span>');
        
        // if a good choice then disable picking and display next round button
        if( game.info.right != '' ) {
            $('#pick_'+game.info.right).parent().addClass('rightChoice');
            $('#theChoices .choice').not('.rightChoice').hide(100);
            game.nextRoundButton(true);
        }
        else if( !game.info.guesses ) {
            // if we ran out of guesses then proceed to the next round
            $('#theChoices .choice').not('.rightChoice').hide(100);
            game.nextRoundButton(false);
        }

        // update guesses remaining
        $('#remaining').text(game.info.guesses);

        // update score
        $('#menuScore b').text(game.info.totalScore);

        // uddate timers
        game.menuTimerVal = game.info.totalTime;
        game.menuTimer();
        game.bonusTimerVal = 10-game.info.roundTime;
        game.bonusTimer();

        // now show the rendered page
        $('#gameContent').show();
    },
    menuTimer: function(){
        // show a running clock in the menu
        clearTimeout(game.menuTimerInterval);
        if( game.info.active ) {
            $('#menuTimer b').text(game.menuTimerVal++);
            game.menuTimerInterval = setInterval(game.menuTimer, 1000);
        }

    },
    bonusTimer: function(){
        // show the bonus timer countdown
        clearTimeout(game.bonusTimerInterval);
        if( game.bonusTimerVal < 0){
            // don't have a negative bonus and stop timer
            $('#bonusTimer b').text(0);
        }else if( game.info.active ) {
            $('#bonusTimer b').text(game.bonusTimerVal--);
            game.bonusTimerInterval = setInterval(game.bonusTimer, 1000);
        }
    },

    nextRoundButton: function(isCorrect){
        // if on last round show 'see results' button
        // else show 'next round' button
        var button;
        if( game.info.currentRound < game.info.numRounds )
            button = $('<button id="nextRound">Next Round <span class="glyphicon glyphicon-arrow-right" aria-hidden="true"></span></button>');
        else
            button = $('<button id="showResults"><span class="glyphicon glyphicon glyphicon-ok" aria-hidden="true"></span> Show Results</button>');

        // show score earned this round
        scoreClass = isCorrect?'Right':'Wrong';
        $('#roundResult')
            .html('<div id="scoreCard"><p class="score'+scoreClass+'"></p>\
                <span>Points Scored: <b id="roundScore"></b><br>\
                Total Points: <b id="totalScore"></b></span></div>')
            .append(button)
            .show(1000);
        $('#roundScore').text(game.info.roundScore);
        $('#totalScore').text(game.info.totalScore);
    },

    showResults: function(data){
        // game results page, hide other content
        $('#gameContent').hide();
        $('#highScore').hide();

        // show the current game stats
        $('#gameStats tbody').html('');
        $.each(data.table, function(roundIdx,r){
            $('#gameStats tbody').append(
                $('<tr>')
                .append($('<td>').text(r.round))
                .append($('<td>').text(r.guess))
                .append($('<td>').text(r.time))
                .append($('<td>').text(r.score))
            );
        });
        $('#gameStats tbody').append(
            $('<tr>')
            .append($('<th colspan="2">').text('Total'))
            .append($('<th>').text(game.info.totalTime))
            .append($('<th>').text(game.info.totalScore))
        );

        // show the leaderboard and check if the score was high enough for the user to be added
        game.leaderboard = data.leaderboard;
        lowScore = game.drawLeaderBoard();
        if( game.info.totalScore > lowScore )
            $('#highScore').show();

        // show rendered content
        $('#gameResults').show();
    },

    drawLeaderBoard: function(){
        $('.hallOfFame tbody').html(''); // clear existing records
        $.each(game.leaderboard, function(idx,r){
            $('.hallOfFame tbody').append(
                $('<tr>')
                .append($('<td>').text(idx+1))
                .append($('<td>').text(r.name))
                .append($('<td>').text(r.score))
                .append($('<td>').text(r.date))
                .append($('<td>').text(r.type))
                .append($('<td>').text(r.difficulty))
                .append($('<td>').text(r.mode))
            );
        });

        // return lowest score for hall of fame computation
        return game.leaderboard[game.leaderboard.length-1].score;
    },

    updateProgressBar: function(){
        var total = game.info.numRounds;
        var curr = game.info.currentRound;
        var progress = curr/total*100;
        
        $("#gameProgress").attr('aria-valuenow',progress)
            .css("width",progress+'%')
            .text(curr+' of '+total);
    },
    updateQuestion: function(){
        var theQuestion = game.info.question;

        if( game.info.type == 'face' )
            theQuestion = $('<div class="headshot centered">').css('background-image','url('+theQuestion+')');

        $('#theQuestion').html(theQuestion);
    },
    updateChoices: function(){
        var output = $('<div class="col-xs-12">');
        $.each(game.info.choices, function(idx,item){
            // different types of choice content depending on the game mode
            if( game.info.type == 'name' )
                item = $('<div class="card choice col-xs-5 col-sm-4 col-md-2">').append(
                    $('<div class="headshot btn btn-default pick">')
                        .attr('id','pick_'+idx)
                        .css('background-image','url('+item+')')
                ).append($('<b>'));
            else
                item = $('<div class="choice col-xs-12 col-sm-6 col-md-4">')
                    .append(
                        $('<div class="btn btn-default pick named">')
                        .attr('id','pick_'+idx)
                        .html(item)
                    );
        
           output.append(item);
        });
        output.append( 
            $('<div id="roundResult" class="result col-xs-7 col-sm-6 col-md-8">')
        );

        $('#theChoices').html(output);
    },
    checkPick: function(){
        // send the current user choice to the server to see if it was correct
        var params = {
            'choice': this.id.substr(5)
        };
        game.submitData('checkChoice', params, game.processPick);
    },
    processPick(data){
        // process the results from the server on if the game choice was correct
        if( !data.success ) {
            alert('Error: '+data.status);
            return;
        }
        game.info = data.game;
        game.updateDisplay(); // update the display to reflect the new game state
    },
    updateDisplay(){
        // if the game is in progress show content, otherwise show the home screen
        if( game.info.active )
            game.showGameContent();
        else
            game.showSplashScreen();
    },

	submitData: function(action,params,callback){
        // generic function for communicating with the server for various game actions
        if( !params )
            params = {};
        params.action = action;

        if( !callback )
            callback = function(j){
                if( j.success ){
                    game.processResult(j);
                }
                else {
                    alert('There was an error communicating with the server, please try again');
                }
                return false;
            };

        $.post('post.php', params, callback, 'json');
    }	
};
