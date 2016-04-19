/**
 * Created by cedric.gallard on 31/03/14.
 */

/** Gestion des infobulles
 * Une infobulle peut être affichée à gauche, à droite, en haut et en bas du conteneur auquel elle est reliée.
 * Il suffit pour cela d'employer les classes suivantes :
 * - tooltip-left
 * - tooltip-right
 * - tooltip-top
 * - tooltip-bottom (défaut pour les balises a)
 * Il faut également renseigner une balise title ou alt.
 */
function toolTips(){
	var positions = ['top', 'left', 'bottom', 'right'];
	$.each(positions, function(key, pos){
		$('.tooltip-'+pos).tooltip({placement: pos, title: function(){return ($(this).attr('title').length > 0 ) ? $(this).attr('title') : $(this).attr('alt');}});
	});
	$('a').tooltip({placement: 'bottom'});

}

/**
 * Gestion du champ de mot de passe
 * Ne fonctionne pas sous IE8
 * @from <http://bootsnipp.com/snippets/featured/windows-8-style-password-reveal>
 */
$(".reveal").mousedown(function() {
	var $group = $(this).closest('.input-group');
	var $pwd = $group.find('.pwd');
	$pwd.replaceWith($pwd.clone().attr('type', 'text'));
})
	.mouseup(function() {
		var $group = $(this).closest('.input-group');
		var $pwd = $group.find('.pwd');
		$($pwd).replaceWith($pwd.clone().attr('type', 'password'));
	})
	.mouseout(function() {
		var $group = $(this).closest('.input-group');
		var $pwd = $group.find('.pwd');
		$($pwd).replaceWith($pwd.clone().attr('type', 'password'));
	});

/**
 * Paramètres généraux de pNotify
 *
 * pNotify est le gestionnaire jQuery des alertes.
 * @see AlertsManager::displayAlert() pour l'affichage des alertes
 */
$.pnotify.defaults.styling = "fontawesome";
$.pnotify.defaults.history = false;
$.pnotify.defaults.icon = false;
$.pnotify.defaults.labels = {redisplay: "Réafficher", all: "Tous", last: "Dernier", close: "Fermer", stick: "Fixer"};
$.pnotify.defaults.width = "350px";
// Position pour les alertes de debug
var stack_bottomright = {"dir1": "up", "dir2": "left", "push": "top"};

/**
 * Affiche une icône d'aide à côté des items de menu
 */
$('.menu-item-desc').hide();
$('.menuItem').hover(function(){
	$(this).children('.menu-item-desc').fadeIn(500);
}, function(){
	$(this).children('.menu-item-desc').stop().hide();
});
/*$('.menuItem').mouseover(function() {
	$(this).children('.menu-item-desc').fadeIn(200);
}).mouseout(function() {
	$(this).children('.menu-item-desc').stop().hide();
	});*/

/**
 * Gestion des switchs remplaçant les checkbox.
 *
 * Paramètres de base :
 *    state	Boolean	The checkbox state	true, false	'checked' attribute or true
 *    size	String	The checkbox state	null, 'mini', 'small', 'normal', 'large'	null
 *    animate	Boolean	Animate the switch	true, false	true
 *    disabled	Boolean	Disable state	true, false	'disabled' attribute or false
 *    readonly	Boolean	Readonly state	true, false	'readonly' attribute or false
 *    onColor	String	Color of the left side of the switch	'primary', 'info', 'success', 'warning', 'danger', 'default'	'primary'
 *    offColor	String	Color of the right side of the switch	'primary', 'info', 'success', 'warning', 'danger', 'default'	'default'
 *    onText	String	Text of the left side of the switch	String	'ON'
 *    offText	String	Text of the right side of the switch	String	'OFF'
 *    labelText	String	Text of the center handle of the switch	String	'&nbsp;'
 *    baseClass	String	Global class prefix	String	'bootstrap-switch'
 *
 * @link http://www.bootstrap-switch.org
 */
function bootstrapSwitch(){
	/* Checkbox des ACL */
	$('.checkbox-ACL').each(function(){
		$(this).bootstrapSwitch({
			size: 		'small',
			onText:		'Permis',
			offText:	'Refusé',
			onColor:  'success',
			offColor: 'danger',
			animate:  true
		});
	});
	$('.checkbox-activation').each(function(){
		$(this).bootstrapSwitch({
			size: 		'small',
			onText:		'Oui',
			offText:	'Non',
			onColor:  'success',
			offColor: 'danger',
			animate:  true
		});
	});
	// Switch général. Les paramètres devront être gérés par les attributs data sur les balises html.
	$('.checkboxSwitch').each(function(){
		$(this).bootstrapSwitch({
			onText:		'Oui',
			offText:	'Non',
			animate:  true
		});
		console.log($(this));
	});
}

/**
 * Gère les ajustements de switchs d'ACL.
 * - Si une autorisation est permise, on autorise également les autorisations inférieures.
 * - Si une autorisation est refusée, on refuse également les autorisations supérieures.
 *
 */
function ACLSwitchs(){
	$('.checkbox-ACL').on('switchChange.bootstrapSwitch', function(event, state) {
		var $changed = $(this);
		var $tr = $($changed.data('tr-id'));
		console.log($tr.find('.checkbox-ACL'));
		$tr.find('.checkbox-ACL').each(function(){
			console.log($(this));
			if (($(this).data('type-value') < $changed.data('type-value')) && state == true){
				$(this).bootstrapSwitch('state', true, true);
			}else if(($(this).data('type-value') > $changed.data('type-value')) && state == false){
				$(this).bootstrapSwitch('state', false, true);
			}
		});
	});
}

/**
 * Gère les popovers de confirmation
 */
function confirmation(){
	$('[data-toggle=confirmation]').confirmation({
		title:          'Êtes-vous sûr(e) ?',
		btnOkLabel:     'Oui',
		btnCancelLabel: 'Non'
	});
}

/**
 * Ajoute des boutons de suppression sur les dbTables
 */
function dbTables(){
	$('.tr_dbTable_header').append('<th>Actions</th>');
	$('.tr_dbTable').each(function(){
		var id = $(this).attr('id');
		$(this).append('<td class="td_dbtable_actions"><a href="#" title="Supprimer la ligne" class="btn btn-default btn-sm dbtable_delete_item" data-toggle="confirmation" data-title="Supprimer la ligne ?<br ><small>(La ligne ne sera vraiment supprimée qu\'à la sauvegarde des paramètres</small>"><span class="fa fa-trash-o"></span></a></td>')
		$(this).on('confirmed.bs.confirmation', function(){
			$(this).remove();
		});
	});
}

/**
 * Retourne les enregistrements d'un tableau correspondant à une chaîne de recherche
 * @param strs
 * @returns {findMatches}
 */
var substringMatcher = function(strs) {
	return function findMatches(q, cb) {
		var matches, substringRegex;

// an array that will be populated with substring matches
		matches = [];

// regex used to determine if a string contains the substring `q`
		var substrRegex = new RegExp(q, 'i');

// iterate through the pool of strings and for any string that
// contains the substring `q`, add it to the `matches` array
		$.each(strs, function(i, str) {
			if (substrRegex.test(str)) {
// the typeahead jQuery plugin expects suggestions to a
// JavaScript object, refer to typeahead docs for more info
				matches.push({ value: str });
			}
		});

		cb(matches);
	};
};

/**
 * Masque le menu principal lorsqu'un menu secondaire existe
 */
function menuNavigation(){
	$('.secondary-menu-title').css('margin-bottom', 16).before('<a class="display-main-menu pull-left" href="#" title="Afficher le menu principal"><span class="fa fa-chevron-circle-down"></span></a>');
	// Si un menu secondaire existe, on planque le menu principal
	if ($('.secondary-menu-title').length > 0) {
		$('#menu-main').hide();
		var homeLi = $('#item-home');
		$('.secondary-menu-ul').prepend(homeLi);
	}
	$('.display-main-menu').click(function(e){
		$('#menu-main').slideToggle(300, function() {
			if ($('.display-main-menu span').hasClass('fa-chevron-circle-down')){
				// On veut afficher le menu principal
				$('.secondary-menu-ul').hide();
				$('.display-main-menu span').removeClass('fa-chevron-circle-down').addClass('fa-chevron-circle-up');
				$('.display-main-menu').attr('data-original-title', 'Masquer le menu principal').tooltip('fixTitle');
			}else{
				// On veut masquer le menu principal
				$('.display-main-menu span').removeClass('fa-chevron-circle-up').addClass('fa-chevron-circle-down');
				$('.display-main-menu').attr('data-original-title', 'Afficher le menu principal').tooltip('fixTitle');
				$('.secondary-menu-ul').show();
			}
		});
	});

	if ( ($(window).height() + 100) < $(document).height() ) {
		$('#top-link-block').removeClass('hidden').affix({
			// how far to scroll down before link "slides" into view
			offset: {top:100}
		});
	}
}

/**
 * Gère la saisie des dates et heures
 *
 * Pour spécifier un mode de saisie, l'attribut `data-datetype` doit être renseigné avec une des valeurs suivantes :
 *  - `date`          : saisie de date sans l'heure
 *  - `time`          : saisie d'heure et de minutes
 *  - `fulltime`      : idem que `time`, mais avec les secondes
 *  - `dateTime`      : saisie de date et heure, sans les secondes
 *  - `fullDateTime`  : idem que `dateTime`, mais avec les secondes
 *
 */
function dateTimePick(){
	if (jQuery.fn.datetimepicker != 'undefined') {
		var base = {
			language: 'fr'
		};
		var options = {};
		$('.input-date, .input-time').each(function(){
			var mode = $(this).find('input').data('datetype') || 'date';
			switch (mode){
				case 'date':
					options = {
						pickDate: true,
						pickTime: false
					};
					break;
				case 'time':
					options = {
						pickDate: false,
						pickTime: true,
						useSeconds: false
					};
					break;
				case 'fullTime':
					options = {
						pickDate: false,
						pickTime: true,
						useSeconds: true
					};
					break;
				case 'dateTime':
					options = {
						pickDate: true,
						pickTime: true,
						useSeconds: false
					};
					break;
				case 'fullDateTime':
					options = {
						pickDate: true,
						pickTime: true,
						useSeconds: true
					};
					break;
			}
			$.extend(options, base);
			$(this).datetimepicker(options);
		});
	}
}

var dataTablesOptions = {
	"language": {
		"sProcessing":     "Traitement en cours...",
		"sSearch":         "Rechercher&nbsp;:&nbsp;",
		"sLengthMenu":     "Afficher _MENU_ &eacute;l&eacute;ments",
		"sInfo":           "Affichage de l'&eacute;l&eacute;ment _START_ &agrave; _END_ sur _TOTAL_ &eacute;l&eacute;ments",
		"sInfoEmpty":      "Affichage de l'&eacute;l&eacute;ment 0 &agrave; 0 sur 0 &eacute;l&eacute;ments",
		"sInfoFiltered":   "(filtr&eacute; de _MAX_ &eacute;l&eacute;ments au total)",
		"sInfoPostFix":    "",
		"sLoadingRecords": "Chargement en cours...",
		"sZeroRecords":    "Aucun &eacute;l&eacute;ment &agrave; afficher",
		"sEmptyTable":     "Aucune donn&eacute;e disponible dans le tableau",
		"oPaginate": {
			"sFirst":      "Premier",
			"sPrevious":   "Pr&eacute;c&eacute;dent",
			"sNext":       "Suivant",
			"sLast":       "Dernier"
		},
		"oAria": {
			"sSortAscending":  ": activer pour trier la colonne par ordre croissant",
			"sSortDescending": ": activer pour trier la colonne par ordre décroissant"
		}
	},
	"pageLength": 100,
	"lengthMenu": [ [25, 50, 100, 200, -1], [25, 50, 100, 200, "All"] ],
	"renderer"  : "bootstrap"
};

/**
 * Affiche un message indiquant que la page demandée est en cours de chargement
 */
function displayLoadIcon(){
	$('a').click(function(e){
		var url = null;
		var $this = $(this)[0];
		if ($this.href.indexOf('#') > 0){
			url = $this.href.split('#')[0];
		}
		var currentURI = window.location.href.split('#')[0];
		if (url != currentURI || $(this).parents('.breadcrumb').length > 0){
			setTimeout(function () {waitingDialog.show('Chargement', {dialogSize: 'lg', progressType: 'warning'})}, 200);
		}
	});
	$('form').submit(function(){
		setTimeout(function () {waitingDialog.show('Chargement', {dialogSize: 'lg', progressType: 'warning'})}, 200);
	});
}

menuNavigation();
bootstrapSwitch();
ACLSwitchs();
dbTables();
confirmation();
toolTips();
displayLoadIcon();
