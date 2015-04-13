/**
 * Created by cedric.gallard on 26/03/2015.
 */
$(function() {
	var subjects = ['user', 'server', 'client'];
	$.each(subjects, function(index, subject){
		$('#field_string_'+subject).typeahead({
			source: function (query, process) {
				var vars = {};
				vars[subject] = query;
				return $.get('index.php?module=UsersTraces2&action=returnInfo&subject='+subject, vars, function (data) {
					return process(data.options);
				});
			},
			items: 'all',
			minLength: 3
		});
	});
	/*
	$('#field_string_user').typeahead({
		source: function (query, process) {
			return $.get('index.php?module=UsersTraces2&action=returnInfo&subject=user', { user: query }, function (data) {
				return process(data.options);
			});
		},
		items: 'all',
		minLength: 3
	});
	$('#field_string_server').typeahead({
		source: function (query, process) {
			return $.get('index.php?module=UsersTraces2&action=returnInfo&subject=server', { server: query }, function (data) {
				return process(data.options);
			});
		},
		items: 'all',
		minLength: 3
	});
	$('#field_string_client').typeahead({
		source: function (query, process) {
			return $.get('index.php?module=UsersTraces2&action=returnInfo&subject=client', { client: query }, function (data) {
				return process(data.options);
			});
		},
		items: 'all',
		minLength: 3
	});*/
});