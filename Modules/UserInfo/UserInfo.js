$(function() {
	$('#field_string_user').typeahead({
		source: function (query, process) {
							return $.get('index.php?module=UserInfo&action=returnUsers', { field_string_query: query }, function (data) {
								return process(data.options);
							});
						},
		items: 'all',
		minLength: 3
   });
});