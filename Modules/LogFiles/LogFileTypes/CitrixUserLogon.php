<?php
/**
 * Creator: Dric
 * Date: 07/07/2016
 * Time: 16:59
 */

namespace Modules\LogFiles\LogFileTypes;

use Sanitize;

class CitrixUserLogon extends LogFileType{
	protected $name = 'Log de connexion à Citrix';

	public static function testPattern(Array $text) {
		$pattern = '/^(\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}:\d{2}) : (.*)/';
		$altPattern = '/^(\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}:\d{2}) : (.*)/';
		if (preg_match($pattern, $text[0])){
			// On fait un essai avec la deuxième ligne, des fois que toutes les lignes ne suivent pas le même schéma
			if (preg_match($altPattern, $text[1], $matches)){
				// Le type est détecté, on le retourne
				for ($i=0;$i < 60 and $i < count($text);$i++){
					if (strpos($text[$i], '--- Nouvelle connexion sur') !== false) {
						return true;
					}
				}
			}
		}
		return false;
	}

	public function display(){

		$timeline = array();
		$session = array(
			'events'        => array(),
			'color'         => 'success',
			'reasons'       => array(),
			'clientsNames'  => array(),
			'clientsIPs'    => array(),
			'server'        => 'serveur inconnu',
			'startTime'     => 'Inconnu',
			'endTime'       => 'Inconnu',
			'duration'      => 0
		);
		$currentDate = null;
		$currentTime = null;
		$i = 0;
		$sessionClosed = false;
		$beginFile = true;
		foreach ($this->file as $line){

			// La date de l'événement
			$logDate = substr($line, 0, 10);
			// L'heure de l'événement
			$logTime = substr($line, 11, 8);

			if (empty($currentDate) or strpos($line, '--- Nouvelle connexion') !== false or $sessionClosed){
				if (!empty($currentDate)) {
					if ($timeline[$currentDate.' '.$currentTime]['endTime'] == 'Inconnu'){
						$timeline[$currentDate.' '.$currentTime]['color'] = 'danger';
						$timeline[$currentDate.' '.$currentTime]['reasons'][] = 'La session n\'a pas d\'événement de fermeture !';
					}
				}
				$i = 0;
				// On initialise l'heure et la date du début de la session
				$currentDate = $logDate;
				$currentTime = $logTime;
				// On initialise le tableau de session
				$timeline[$currentDate.' '.$currentTime] = $session;
				if ($beginFile) {
					// début du fichier de log
					$timeline[$currentDate.' '.$currentTime]['color'] = 'warning';
					$timeline[$currentDate.' '.$currentTime]['reasons'][] = 'Début du fichier';
					$beginFile = false;
				}elseif($sessionClosed and strpos($line, '--- Nouvelle connexion') === false){
					// Pas d'événement d'ouverture de session
					$timeline[$currentDate.' '.$currentTime]['color'] = 'danger';
					$timeline[$currentDate.' '.$currentTime]['reasons'][] = 'La session n\'a pas d\'événement d\'ouverture !';
				}else{
					// Si aucun événement d'ouverture de session n'est enregistré, on doit pouvoir signaler qu'on ne sait pas quand a été démarrée la session.
					// C'est pourquoi lorsqu'il n'y a pas de souci, on enregistre l'heure de connexion.
					$timeline[$currentDate.' '.$currentTime]['startTime'] = $currentDate.' '.$currentTime;
				}
				$sessionClosed = false;
			}

			if (strpos($line, 'Erreur') !== false){
				$timeline[$currentDate.' '.$currentTime]['color'] = 'danger';
			}
			// On récupère la ligne en supprimant la date
			$event = substr($line, 22);
			if (strpos($line, '* Poste client') !== false) {
				$timeline[$currentDate . ' ' . $currentTime]['clientsNames'][] = (substr($event, 17) ? substr($event, 17) : 'Inconnu');
			}elseif (strpos($line, '* Nouveau Poste client') !== false) {
				$timeline[$currentDate . ' ' . $currentTime]['clientsNames'][] = (substr($event, 25) ? substr($event, 25) : 'Inconnu');
			}elseif (strpos($line, '* Adresse IP') !== false) {
				$timeline[$currentDate . ' ' . $currentTime]['clientsIPs'][] = (substr($event, 15) ? substr($event, 15) : 'Inconnu');
			}elseif (strpos($line, '--- Session fermée') !== false) {
				$timeline[$currentDate . ' ' . $currentTime]['endTime'] = $logDate.' '.$logTime;
				if ($timeline[$currentDate . ' ' . $currentTime]['server'] == 'serveur inconnu' and substr($event, 23)) $timeline[$currentDate . ' ' . $currentTime]['server'] = str_replace(' ---', '', substr($event, 23));
				$sessionClosed = true;
			}elseif (strpos($line, '--- Nouvelle connexion sur') !== false) {
				$timeline[$currentDate . ' ' . $currentTime]['server'] = (substr($event, 27) ? str_replace(' ---', '', substr($event, 27)) : 'Inconnu');
			}
			$timeline[$currentDate.' '.$currentTime]['events'][$i] = $line;
			$i++;
		}
		//var_dump($timeline);
		?>
		<ul class="timeline">
			<?php
			if ($this->orderDesc) $timeline = array_reverse($timeline, true);
			$odd = true;
			$num = 0;
			foreach ($timeline as $beginDate => $session){
				$num++;
				?>
				<li class="<?php echo ($odd) ? '' : 'timeline-inverted'; ?>">
					<div class="timeline-badge <?php echo $session['color']; ?>"><i class="fa fa-sign-in"></i></div>
					<div class="timeline-panel">
						<div class="timeline-heading">
							<h4 class="timeline-title">Session sur <?php echo $session['server']; ?></h4>
						</div>
						<div class="timeline-body">
							<h5>Client(s) :</h5>
							<ul>
								<?php
								foreach ($session['clientsNames'] as $i => $clientName){
									?><li><?php echo $clientName.' ('.$session['clientsIPs'][$i].')'; ?></li><?php
								}
								?>
							</ul>
							<h5>Durées :</h5>
							<ul>
								<li>Heure d'ouverture : <?php echo $session['startTime']; ?></li>
								<li>Heure de fermeture : <?php echo $session['endTime']; ?></li>
							</ul>
							<h5><a href="#events-<?php echo $num; ?>" data-toggle="collapse" aria-expanded="false" title="Cliquez pour déplier/replier les événements"><i class="fa fa-history"></i> Evénements</a></h5>
							<div id="events-<?php echo $num; ?>" class="collapse">
								<table class="table table-striped table-bordered table-condensed">
									<thead>
									<tr>
										<th>Heure</th>
										<th>Evénement</th>
									</tr>
									</thead>
									<tbody>
									<?php
									foreach ($session['events'] as $fullEvent){
										list($date, $event) = explode (' : ', $fullEvent, 2);
										?>
										<tr>
											<td><small><?php echo substr($date, 11); ?></small></td>
											<td><small><?php echo $event; ?></small></td>
										</tr>
										<?php
									}
									?>
									</tbody>
								</table>
							</div>
							<?php if (!empty($session['reasons'])){ ?>
								<h5>Avertissements :</h5>
								<?php
								foreach ($session['reasons'] as $reason){
									?><div class="alert alert-danger" role="alert"><?php echo $reason; ?></div><?php
								}
								?>
							<?php	} ?>
						</div>
					</div>
					<div class="timeline-date">
						<i class="fa fa-clock-o"></i> <?php echo $beginDate; ?>
					</div>
				</li>
				<?php
				if ($odd){
					$odd = false;
				} else  {
					$odd = true;
				}
			}
			?>

			<?php ?>
		</ul>
		<?php
	}

}