$(function(){
    game.init();
});

var game = {
    currentScreen: '',
    leaderboard: [],
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

        game.info = gameInfo;
        game.leaderboard = leaderboard;
        game.drawLeaderBoard();
        game.updateDisplay();
    },

    showSplashScreen: function(){
        $('#gameContent').hide();
        $('#gameResults').hide();
        $('#menuScore').hide();

        game.btnGroup('gameType', game.info.type);
        game.btnGroup('gameDifficulty', game.info.difficulty);
        game.btnGroup('gameMode', game.info.mode);

        $("#startSplash").show();
    },

    btnGroup: function (id, val){
        var group = $("input[name='"+id+"']");
        if(val)
        {
            group.parent().removeClass('active');
            for(i=0;  i < group.length;  i++)
                if(group[i].value == val){
                    $(group[i]).parent().addClass('active');
                    group[i].checked = true;
                }
        }
        for(i=0;  i < group.length;  i++)
            if(group[i].checked)
                return group[i].value;
        return;
    },
    
    showGameContent: function(){
        $("#startSplash").hide();
        $('#menuScore').show();
   
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
            $('#theChoices .choice').not('.rightChoice').hide(100);
            game.nextRoundButton(false);
        }

        // update guesses remaining
        $('#remaining').text(game.info.guesses);

        // update score
        $('#menuScore b').text(game.info.totalScore);
        
        // uddate timer
        
        $('#gameContent').show();
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
        $('#gameContent').hide();
        $('#highScore').hide();

        // data should be game stats
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

        game.leaderboard = data.leaderboard;
        lowScore = game.drawLeaderBoard();
        if( game.info.totalScore > lowScore )
            $('#highScore').show();

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
        var params = {
            'choice': this.id.substr(5)
        };
        game.submitData('checkChoice', params, game.processPick);
    },
    processPick(data){
        if( !data.success ) {
            alert('Error: '+data.status);
            return;
        }
        game.info = data.game;
        game.updateDisplay();
    },
    updateDisplay(){
        if( game.info.active )
            game.showGameContent();
        else
            game.showSplashScreen();
    },

	submitData: function(action,params,callback){
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




