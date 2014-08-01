<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 19/05/14
 * Time: 12:11
 */

namespace Modules\PostesAstreintes;


use Check;
use Components\Item;
use FileSystem\Fs;
use Forms\Fields\String;
use Forms\Pattern;
use Front;
use Logs\Alert;
use Modules\Module;
use Modules\ModulesManagement;
use Forms\Field;
use Forms\Form;
use Forms\PostedData;
use Users\ACL;

class PostesAstreintes extends Module{
	protected $name = 'IP des postes astreintes';
	protected $title = 'Gestion des mappages d\'adresses IP des postes d\'astreinte';

	protected $postes = array();


	/**
	 * Permet d'ajouter des items au menu général
	 */
	public static function getMainMenuItems(){
		Front::$mainMenu->add(new Item('postesAstreintes', 'Postes astreintes', MODULE_URL.end(explode('\\', get_class())), 'Gestion des mappages d\'adresses IP des postes d\'astreinte', null, null));
	}

	/**
	 * Installe le module
	 */
	public function install(){
		// Définition des ACL par défaut pour ce module
		$defaultACL = array(
			'type'  => 'modify',
			'value' => true
		);
		return ModulesManagement::installModule($this, $defaultACL);
	}

	/**
	 * Définit les paramètres
	 *
	 * Les paramètres sont définis non pas avec des objets Setting mais avec des objets Field (sans quoi on ne pourra pas créer d'écran de paramétrage)
	 */
	public function defineSettings(){
		$this->settings['filePath'] = new String('filePath', '\\\\intra.epsi.fr\Profils\Xen\Xenlogin\Scripts\IP-Astreintes-test.vbs', 'Fichier du script VBS de gestion des adresses IP', null, 'Saisissez le chemin et le nom du script VBS. Vous pouvez utiliser des partages administratifs ou DFS', new Pattern('string', true), true);
	}

	/**
	 * Affichage principal
	 */
	protected function mainDisplay(){
		?>
		<div class="row">
			<div class="col-md-10 col-md-offset-1">
				<div class="page-header">
					<h1>IP des postes d'astreintes  <?php $this->manageModuleButtons(); ?></h1>
				</div>
				<p>
					Cette page vous permet de gérer les adresses IP affectées aux PC portables d'astreintes.<br >Ça évite d'aller modifier à la main le fichier VBS qui s'occupe de l'affectation d'adresse IP dans la variable d'environnement utilisée par Cariatides.
				</p>
				<?php
				$this->displayIPTable();
				?>
			</div>
		</div>
	<?php
	}

	/********* Méthodes propres au module *********/

	/**
	 * Affiche la liste des correspondances Postes-IP
	 */
	protected function displayIPTable(){
		if ($this->getPostes()){
			$canModify = ACL::canModify('module', $this->id);
			?>
			<div class="alert alert-warning">
				Ne supprimez pas toutes les lignes, sinon il sera impossible d'en ajouter de nouvelles (les nouvelles lignes sont ajoutées après les anciennes - pas d'anciennes lignes, pas d'ajout).
			</div>
			<div class="row">
				<div class="col-md-6">
					<form method="post">
						<table class="table table-bordered table-striped">
							<thead>
								<tr class="<?php if ($canModify){ echo 'tr_dbTable_header'; }?>">
									<th>Nom du poste</th>
									<th>IP du poste</th>
								</tr>
							</thead>
							<tbody>
							<?php
							$i = 0;
							if ($canModify){
								foreach ($this->postes as $poste => $ip){
									?>
									<tr class="tr_dbTable" id="tr_<?php echo $i; ?>">
										<td><input type="text" class="form-control" name="dbTable_IPPostes_string_name_<?php echo $i; ?>" value="<?php echo $poste; ?>"></td>
										<td><input type="text" class="form-control" name="dbTable_IPPostes_string_ip_<?php echo $i; ?>" value="<?php echo $ip; ?>"></td>
									</tr>
									<?php
									$i++;
								}

								?>
								<tr id="tr_new">
									<td><input type="text" class="form-control" name="dbTable_IPPostes_string_name_new"></td>
									<td><input type="text" class="form-control" name="dbTable_IPPostes_string_ip_new"></td>
									<td></td>
								</tr>
							<?php }else{
								foreach ($this->postes as $poste => $ip){
									?>
									<tr>
										<td><?php echo $poste; ?></td>
										<td><?php echo $ip; ?></td>
									</tr>
									<?php
								}
							}
							?>
							</tbody>
						</table>
						<noscript><span class="help-block">Pour supprimer une ligne, il vous suffit d'effacer toutes les valeurs contenues dans celle-ci.</span></noscript>
						<?php if ($canModify) { ?><button name="action" value="savePostes" class="btn btn-primary">Sauvegarder</button><?php }else{ ?>
						<div class="alert alert-warning">Vous pouvez visualiser les correspondances Postes-IP mais pas les modifier car vous n'avez pas les droits pour le faire.</div>
						<?php } ?>
						<?php
							$token = PostedData::setToken('IPPostes');
						?>
						<input id="field_hidden_token" name="field_hidden_token" value="<?php echo $token; ?>" type="hidden">
						<input id="field_hidden_formName" name="field_hidden_formName" value="IPPostes" type="hidden">
					</form>
				</div>
			</div>
			<div class="row"></div>
			<?php
		}
	}

	/**
	 * Récupère la liste des correspondances IP-Postes
	 */
	protected function getPostes(){
		$filePath = $this->settings['filePath']->getValue();
		preg_match('/(.*)\\\(.*\.vbs)/i', $filePath, $matches);
		list(, $path, $fileName) = $matches;
		$share = new Fs($path, null, end(explode('\\', get_class())));
		if ($file = $share->readFile($fileName)){
			foreach ($file as $line) {
				if (strtolower(substr($line, 0, 16)) == 'ipastreintes.add'){
					$lineTab = explode(' ', $line);
					$poste = $lineTab[1];
					$poste = str_replace('"', '', $poste);
					$poste = rtrim($poste, ',');
					$ip = $lineTab[2];
					$ip = str_replace('"', '', $ip);
					$this->postes[$poste] = $ip;
				}
			}
			return true;
		}
		new Alert('error', 'Le fichier <code>'.$fileName.'</code> est vide ou inaccessible !');
		return false;
	}

	/**
	 * Sauvegarde les correspondances Postes-Ip dans le fichier VBS
	 * @return bool
	 */
	protected function savePostes(){
		if (!ACL::canModify('module', $this->id)){
			new Alert('error', 'Vous n\'avez pas l\'autorisation de faire ceci !');
			return false;
		}
		$postes = $this->postedData['dbTable']['IPPostes'];
		foreach ($postes as $poste){
			if (!empty($poste['name']) and !empty($poste['ip'])) {
				if (!Check::isIpAddress($poste['ip'])){
					new Alert('error', '<code>'.$poste['ip'].'</code> n\'est pas une adresse IP V4 valide !');
					return false;
				}
				$this->postes[$poste['name']] = $poste['ip'];
			}
		}
		$postesList = $this->postes;
		$filePath = $this->settings['filePath']->getValue();
		preg_match('/(.*)\\\(.*\.vbs)/i', $filePath, $matches);
		list(, $path, $fileName) = $matches;
		$share = new Fs($path, null, end(explode('\\', get_class())));
		if ($file = $share->readFile($fileName)){
			$inPostes = false;
			$fileToSave = array();
			foreach ($file as $line) {
				if (strtolower(substr($line, 0, 16)) == 'ipastreintes.add'){
					$inPostes = true;
					$lineTab = explode(' ', $line);
					$poste = $lineTab[1];
					if (isset($this->postes[$poste])){
						$fileToSave[] = 'IPAstreintes.Add "'.$poste.'", "'.$this->$postes[$poste].'"';
						unset ($postesList[$poste]);
					}
				}elseif($inPostes){
					foreach ($postesList as $poste => $ip){
						$fileToSave[] = 'IPAstreintes.Add "'.$poste.'", "'.$ip.'"';
					}
					$fileToSave[] = $line;
					$inPostes = false;
				}else{
					$fileToSave[] = $line;
				}
			}

			$ret = $share->writeFile($fileName, $fileToSave);
			return $ret;
		}
		return false;
	}
}