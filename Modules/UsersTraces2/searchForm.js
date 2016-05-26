/**
 * Created by cedric.gallard on 26/03/2015.
 */
$(function() {
	var subjects = ['user', 'server', 'client'];
	//$.each(subjects, function(index, subject){
		$('#field_string_item').typeahead({
			source: function (query, process) {
				var vars = {};
				var classes = $('#field_string_item').attr('class');
				var subject = classes.replace('form-control ', '');
				vars[subject] = query;
				return $.get('index.php?module=UsersTraces2&action=returnInfo&subject='+subject, vars, function (data) {
					console.log(data);
					return process(data.options);
				});
			},
			items: 'all',
			minLength: 3
		});
	//});
});