/**
 * Created by cedric.gallard on 12/11/2014.
 */

/**
 * Modifie le html renvoyé par PHPInfo pour produire une présentation plus compatible avec Bootstrap
 */
function addCSSToPHPInfo(){
	$('table').addClass('table table-bordered table-striped');
	$('.v').css('word-break', 'break-all');
	$('.e').css('font-weight', 'bold');
}

addCSSToPHPInfo();