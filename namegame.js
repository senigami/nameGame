$(function(){
    game.init();
});

var game = {
    currentScreen: '',
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
        $('#newGame').click(function(){
            game.submitData('newGame',{},function(data){
                game.info = data.game;
                game.updateDisplay();
            });
        });
        
        $('#startGame').click(function(){
            var params = {
                type: $F('gameType'),
                difficulty: $F('gameDifficulty'),
                mode: $F('gameMode')
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
                game.updateDisplay();
            });
        });

        game.info = gameInfo;
        game.updateDisplay();
    },

    showSplashScreen: function(){
        $('#gameContent').hide();
        $('#gameResults').hide();
        
        $F('gameType', game.info.type);
        $F('gameDifficulty', game.info.difficulty);
        $F('gameMode', game.info.mode);

        $("#startSplash").show();
    },

    showGameContent: function(){
        $("#startSplash").hide();
        
        game.updateProgressBar();
        $("#remaining").text(game.info.remaining);
        game.updateQuestion();
        game.updateChoices();

        // mark all bad choices
        $.each(game.info.wrong,function(idx,num){
            $('#pick_'+num).parent().addClass('wrongChoice');
        });
        $(".wrongChoice b").html('<span class="glyphicon glyphicon-ban-circle"></span>');
        
        // if a good choice then disable picking and display next round button
        if( game.info.right != '' ) {
            $('#pick_'+game.info.right).parent().addClass('rightChoice');
            $('#theChoices .card').not('.rightChoice').hide(1000);

            // if on last round show 'see results' button
            // else show 'next round' button
            var button;
            if( game.info.currentRound < game.info.numRounds )
                button = $('<button id="nextRound">Next Round <span class="glyphicon glyphicon-arrow-right" aria-hidden="true"></span></button>');
            else
                button = $('<button id="showResults"><span class="glyphicon glyphicon glyphicon-ok" aria-hidden="true"></span> Show Results</button>');

            // show score earned this round
            $('#roundResult')
                .html('<div id="scoreCard"><p>Correct!</p>\
                    <span>Points Scored: <b id="roundScore"></b><br>\
                    Total Points: <b id="totalScore"></b></span></div>')
                .append(button)
                .show(1000);
            $('#roundScore').text(game.info.roundScore);
            $('#totalScore').text(game.info.totalScore);
        }

        // update score
        // uddate timer
        // update guesses remaining
        
        $('#gameContent').show();
    },

    showResults: function(data){
        $('#gameContent').hide();
        // data should be game stats
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

        $('#gameResults').show();
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
                item = $('<div class="card col-xs-5 col-sm-4 col-md-2">').append(
                    $('<div class="headshot btn btn-default pick">')
                        .attr('id','pick_'+idx)
                        .css('background-image','url('+item+')')
                ).append($('<b>'));
            else
                item = $('<div class="col-xs-12 col-sm-6 col-md-4">')
                    .append(
                        $('<div class="btn btn-default pick named">')
                        .attr('id','pick_'+idx)
                        .html(item)
                    );
        
           output.append(item);
        });
        output.append( 
            $('<div id="roundResult" class="result col-xs-7 col-sm-8 col-md-10">')
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




function $F(id, val){
	//returns the form field value if it exists and sets the value if passed
	// ex: $F('#checkbox', true) or $F('input_name', 'joe') or $F('input_name') <- returns 'joe'
	var e = null;
	if( typeof(id) == 'object' )
	{
		e = id[0];
		id = id.attr('id');
	}
	else
	{
		if( id.substr(0,1) == '#' ) e = $E(id);
		else e = $("[name='"+id+"']")[0];
	}
	if(!e) return;
	var flag = ((typeof(val)=='number')||(typeof(val)=='boolean'))?true:false;
	switch(e.type)
	{
		case 'textarea': // <textarea name="n" rows="4" cols="40"></textarea>
		case 'submit': // <input type="submit" name="n">
		case 'reset': // <input type="reset">
		case 'file': // <input type="file" name="n" size="16">
		case 'hidden': // <input type="hidden" name="n" value="v">
		case 'password': // <input type="password" name="n" size="24">
		case 'text': // <input type="text" name="n" size="24">
			if( val ) e.value = val;
			return e.value;
		
		case 'select-multiple': // <select name="n" size="4" multiple></select>
		case 'select-one':
			var index = e.selectedIndex;
			if( index < 0 )
			{
				for( i=0; i < e.length; i++ )
					if( e[i].value == val ){ e[i].selected = true; return val; }
				return "";
			}
			if( val && (e[e.selectedIndex].value != val) )
				for( i=0; i < e.length; i++ )
					if( e[i].value == val ){ e[i].selected = true; break; }
			return e[e.selectedIndex].value;

		case 'radio': // <input type="radio" name="n" value="v">
			if( id.substr(0,1) == '#' ) // single item or group
			{
				if(flag) e.checked = val;
				else if(val) e.value = val;
				return e.checked;
			}
			else
			{
				var group = $("input[name='"+id+"']");
				if(val)
				{
					for(i=0;  i < group.length;  i++)
						if(group[i].value == val)
							group[i].checked = true;
				}
				for(i=0;  i < group.length;  i++)
					if(group[i].checked)
						return group[i].value;
				return;
			}

		case 'checkbox': // <input type="checkbox" name="n" value="v">
			if( flag ) e.checked = val;
			else if( val ) e.value = val;
			return e.checked;
			
		default:
		case 'button': // <button name="n" type="button"></button>
		case 'image': // <input type="image" src="url" alt="">
			break;
	}
}
function $E(id){
	// returns the first element found in the selector if it exists
	// ex: $E('#click_btn') or $E('a')
	if( $(id)[0] )
		return $(id)[0];
	return 0;
}