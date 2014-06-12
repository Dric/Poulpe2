/**
 * Created by cedric.gallard on 21/05/14.
 */
$(function() {

	/**
	 * On ajoute le champ de contrôle de la base actuelle de l'utilisateur sélectionné
	 */
	$('#form_addMove div:eq(0)').after(
		'<div class="form-group"><label>Base actuelle</label><input type="text" class="form-control" id="currentMdb" disabled></div>'
	);

	/**
	 * Autocomplétion du champ de recherche de l'utilisateur
	 */
	$('#field_string_user').typeahead({
		source: function (query, process) {
			// On va chercher la liste des utilisateurs correspondants à ce qui a été saisi dans le champ
			return $.get('index.php?module=Mailboxes&action=returnUsers', { field_string_query: query }, function (data) {
				users = [];
				map = {};
				// data est un tableau à deux clés, 'Name' et 'Mdb' qui indiquent respectivement le nom de l'utilisateur et la base Exchange sur laquelle est hébergée sa boîte email
				$.each(data, function (i, user) {
					map[user.Name] = user;
					users.push(user.Name);
				});
				return process(users);
			});
		},
		// Lorsque l'utilisateur est retourné au champ, on affiche la base Exchange correspondante dans le champ du dessous
		updater: function(item) {
			$('#currentMdb').attr('value', map[item].Mdb);
			// On récupère la liste des bases pour recréer les options de destination en enlevant la base actuelle de l'utilisateur
			$.get('index.php?module=Mailboxes&action=returnDatabases').done(function( data ) {
				$('#field_select_mdb').html('');
				var sel = '';
				$.each(data, function(key, val) {
					if (val != map[item].Mdb){
						sel = sel + '<option value="' + val + '">' + val + '</option>';
					}
				});
				$('#field_select_mdb').append(sel);

			});
			return item;
		},
		minLength: 3
	});
});