/**
 * Created by cedric.gallard on 06/06/14.
 */
$(function() {
	// Editeur Markdown
	$('textarea').pagedownBootstrap();
	// Masquage du formulaire d'ajout de post-it
	$('#toggleEditForm').click(function(e){
		var hideText = 'Masquer l\'ajout de Post-it';
		var showText = 'Afficher l\'ajout de Post-it';
		var $btnShowHide = $(this);
		$('#editForm').fadeToggle(400, 'swing', function(){
			if ($btnShowHide.text() == hideText){
				$btnShowHide.text(showText);
			}else{
				$btnShowHide.text(hideText);
			}
		});
	});
});
// Surlignage de code
hljs.initHighlightingOnLoad();