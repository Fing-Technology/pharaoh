
var reload = true;
var selected = false;
var board_changed = false;

if ('undefined' == typeof board) {
	var board = false;
}

$(document).ready( function($) {
	// invert board button
	$('a#invert').click( function( ) {
		do_clear_laser( );
		invert = ! invert;
		$('#setup_display').find('#the_board').remove( ).end( ).prepend(create_board(board));
		return false;
	});

	// fire silver laser button
	$('a#silver_laser').click( function( ) {
		do_clear_laser( );
		do_fire_laser('silver');
		return false;
	});

	// fire red laser button
	$('a#red_laser').click( function( ) {
		do_clear_laser( );
		do_fire_laser('red');
		return false;
	});

	// clear laser button
	$('a#clear_laser').click( function( ) {
		do_clear_laser( );
		return false;
	});

	// show the pieces
	var piece;
	var $silver_pieces_div = $('#silver_pieces');
	var $red_pieces_div = $('#red_pieces');
	for (piece in pieces) {
		$silver_pieces_div.append(create_piece(piece, false, true));
		$red_pieces_div.append(create_piece(piece.toLowerCase( ), false, true));
	}

	// make the pieces clickable
	var $pieces_div = $('#pieces_display');
	$pieces_div.find('img').click( function( ) {
		$('.selected').removeClass('selected');
		selected = $(this).addClass('selected').attr('alt');

		do_clear_laser( );

		if ('Cancel' == selected) {
			selected = false;
			$('.selected').removeClass('selected');
		}
	});

	// create the board upon selection
	$('#init_setup').change( function( ) {
		var $this = $(this);
		var val = $this.val( );

		if ('' === val) {
			return;
		}

		do_clear_laser( );

		if ((false === board) || confirm('This will delete all changes.\nAre you sure?')) {
			board = setups[val];
			board_changed = true;
			$('#setup_display').find('#the_board').remove( ).end( ).prepend(create_board(board));
			$('#name').val($this.find('option:selected').text( ) + ' Edit');
		}

		$this.val('');
	});

	// make the setup create/edit clicks work
	$('#the_board div:not(.header)').live('click', function( ) {
		var $this = $(this);
		var id = $this.attr('id').slice(4);
		var reflection = $('#reflection').val( );

		if ( ! selected) {
			return;
		}

		do_clear_laser( );

		if ('Delete' == selected) {
			$this.empty( );
			board = board.replaceAt(id, '0');
			board_changed = true;

			if (reflection) {
				id = get_reflected_id(id, reflection);
				$('#idx_'+id).empty( );
				board = board.replaceAt(id, '0');
				board_changed = true;
			}

			return;
		}

		if ('None' == reflection) {
			if (('p' == selected.toLowerCase( )) && (-1 !== board.indexOf(selected))) {
				alert('Only one pharaoh of each color is allowed.\nPlease delete the current pharaoh to place a new one.');
				return;
			}

			if ((selected.match(/[efjk]/) && board.match(/[efjk]/)) || (selected.match(/[EFJK]/) && board.match(/[EFJK]/))) {
				alert('Only one sphynx of each color is allowed.\nPlease delete the current sphynx to place a new one.');
				return;
			}
		}
		else { // there's a reflection at work
			if (('p' == selected.toLowerCase( )) && (-1 !== board.toLowerCase( ).indexOf(selected.toLowerCase( )))) {
				alert('Only one pharaoh of each color is allowed.\nPlease delete any pharaohs before placing the new ones.');
				return;
			}

			if (selected.match(/[efjk]/i) && board.match(/[efjk]/i)) {
				alert('Only one sphynx of each color is allowed.\nPlease delete any sphynxes before placing the new ones.');
				return;
			}
		}

		$this.empty( ).append(create_piece(selected));
		board = board.replaceAt(id, selected);
		board_changed = true;

		// place the reflected piece
		if (reflection && ('None' != reflection)) {
			id = get_reflected_id(id, reflection);

			new_selected = rotate_piece(selected, reflection, true);

			$('#idx_'+id).empty( ).append(create_piece(new_selected));
			board = board.replaceAt(id, new_selected);
			board_changed = true;
		}
	});

	// initialize a blank board
	if (false === board) {
		board = setups[0];
	}
	$('#setup_display').find('#the_board').remove( ).end( ).prepend(create_board(board));

	// upon form submission, ajax validate the board first
	// so we don't lose all the board data
	// people might get a little upset about that
	$('#setup_form').submit( function(event) {
		// store the current board in the form
		$('#setup').val(board);

		do_clear_laser( );

		if ('' == $('#name').val( )) {
			alert('You must enter a Setup Name');
			event.preventDefault( );
			return false;
		}

		if (debug) {
			window.location = 'ajax_helper.php'+debug_query+'&'+$('#setup_form').serialize( )+'&test_setup=1';
			event.preventDefault( );
			return false;
		}

		var valid = false;

		$.ajax({
			type: 'POST',
			url: 'ajax_helper.php',
			async: false, // wait to continue script until this completes
			data: $('#setup_form').serialize( )+'&test_setup=1',
			success: function(msg) {
				var reply = JSON.parse(msg);

				if (reply.error) {
					alert(reply.error);
					event.preventDefault( );
					valid = false;
				}
				else {
					// run our actual form submit here
					// because everything looks good
					valid = true;
				}
			}
		});

		if (valid) {
			return true;
		}

		event.preventDefault( );
		return false;
	});
});


function get_reflected_id(id, reflection) {
	id = parseInt(id);

	switch (reflection) {
		case 'Origin' :
			id = 79 - id;
			break;

		case 'Long' :
			var tens = Math.floor(id / 10);
			id = ((7 - tens) * 10) + (id % 10);
			break;

		case 'Short' :
			var tens = Math.floor(id / 10);
			id = (tens * 10) + (9 - (id % 10));
			break;

		default :
			// do nothing
			break;
	}

	return id;
}

var laser_path = { };
function do_fire_laser(color) {
	color = color || 'silver';

	// this stops the fire_laser function
	// at the bottom from firing with
	// incorrect path data
	if (board_changed) {
		laser_path = { };
	}

	if ( ! laser_path[color]) {
		if (debug) {
			window.location = 'ajax_helper.php'+debug_query+'&'+'color='+color+'&board='+board+'&test_fire=1';
			return false;
		}

		$.ajax({
			type: 'POST',
			url: 'ajax_helper.php',
			data: 'color='+color+'&board='+board+'&test_fire=1',
			success: function(msg) {
				var reply = JSON.parse(msg);

				if (reply.error) {
					alert(reply.error);
				}
				else {
					laser_path[color] = reply.laser_path;
					fire_laser(laser_path[color]);
					board_changed = false;
				}
			}
		});
	}

	fire_laser(laser_path[color]);
}


function do_clear_laser( ) {
	clearTimeout(timer);
	timer = false;
	$('img.laser').remove( );
}


String.prototype.replaceAt = function(index, str) {
	index = parseInt(index);
	return this.slice(0, index) + str + this.slice(index + str.length);
}


String.prototype.repeat = function(num) {
	num = parseInt(num);
	return new Array(num + 1).join(this);
}

