<?php
/**
 * Creator: Dric
 * Date: 07/07/2016
 * Time: 16:59
 */

namespace Modules\LogFiles\LogFileTypes;
use Components\Help;
use DateTime;

/**
 * Fichiers de logs de connexion à Citrix XenApp 7
 * 
 * Class CitrixUserLogon
 *
 * @package Modules\LogFiles\LogFileTypes
 */
class CitrixUserLogon extends LogFileType{
	protected $name = 'Log de connexion à Citrix';

	/**
	 * Vérifie si le log est du type connexion à Citrix
	 * 
	 * @param array $text Texte à tester
	 *
	 * @return bool
	 */
	public static function testPattern(Array $text) {
		$pattern = '/^(\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}:\d{2}(?:\.\d{3}|)) : (.*)/';
		$altPattern = '/^(\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}:\d{2}(?:\.\d{3}|)) : (.*)/';
		if (preg_match($pattern, $text[0])){
			// On fait un essai avec la deuxième ligne, des fois que toutes les lignes ne suivent pas le même schéma
			if (preg_match($altPattern, $text[1], $matches)){
				for ($i=0;$i < 60 and $i < count($text);$i++){
					if (strpos($text[$i], '--- Nouvelle connexion sur') !== false) {
						return true;
					}
				}
			}
		}
		return false;
	}

	/**
	 * Affiche les logs
	 */
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
			'startDuration' => 'Inconnu',
			'duration'      => 0
		);
		$currentDate = null;
		$currentTime = null;
		$i = 0;
		$sessionClosed = false;
		$beginFile = true;
		$errorsLines = false;
		foreach ($this->file as $line){

			// La date de l'événement
			$logDate = substr($line, 0, 10);
			// L'heure de l'événement
			$logTime = substr($line, 11, 8);

			if (empty($currentDate) or strpos($line, '--- Nouvelle connexion') !== false or $sessionClosed){
				$errorsLines = false;
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
			}elseif ($event == 'Erreurs :') {
				$errorsLines = true;
			}elseif ($errorsLines and strpos($line, '--- Connexion établie sur') === false){
				$timeline[$currentDate.' '.$currentTime]['reasons'][] = $event;
			}elseif (strpos($line, '--- Connexion établie sur') !== false){
				$errorsLines = false;
				if ($timeline[$currentDate.' '.$currentTime]['startTime'] != 'Inconnu'){
					list($startDay, $startMonth, $startYear) = explode('/', $currentDate);
					$startDate = new DateTime($startYear.'-'.$startMonth.'-'.$startDay.' '.$currentTime);
					list($startDay, $startMonth, $startYear) = explode('/', $logDate);
					$diff = $startDate->diff(new DateTime($startYear.'-'.$startMonth.'-'.$startDay.' '.$logTime));
					$timeline[$currentDate.' '.$currentTime]['startDuration'] = sprintf('%02d', $diff->m).':'.sprintf('%02d', $diff->s);
				}
			}
			$timeline[$currentDate.' '.$currentTime]['events'][$i] = $line;
			$i++;
		}
		// On passe à l'affichage...
		?>
		<p><a title="Afficher/masquer l'aide" href="#citrixLogFileHelp" data-toggle="collapse" aria-expanded="false"><span class="fa fa-question-circle-o"></span> Aide sur les logs de connexion à Citrix</a></p>
		<div id="citrixLogFileHelp" class="collapse panel panel-default">
			<ul class="small">
				<li>Les dates et heures indiquées ci-dessous sont estimatives, car elles sont remontées via des logs du script de connexion et non pas par le gestionnaire d'événements de Windows, qui est le seul endroit où les heures sont obligatoirement exactes. Il se peut donc que le moment réel d'un événement soit décalé de quelques secondes par rapport à ce qui est indiqué ici.</li>
				<li>Pour afficher tous les événements en rapport avec une session, cliquez sur le lien <code>Evénements</code> dans chaque session.</li>
				<li>Si une erreur est survenue pendant l'exécution du script de connexion, elle sera ntofiée en rouge dans la session où l'erreur s'est produite.</li>
				<li>Le gabarit des connexion Citrix prend en compte un certain nombre d'irrégularités dans la génération des événements, mais il n'est pas impossible que l'affichage des événements soit étrange en cas de fichiers de logs corrompu ou mal formé.</li>
			</ul>
		</div>
		<ul class="timeline">
			<?php
			if ($this->orderDesc) $timeline = array_reverse($timeline, true);
			$odd = true;
			$num = 0;
			foreach ($timeline as $beginDate => $session){
				$num++;
				list($currentDate, $currentTime) = explode(' ', $beginDate);
				list($startDay, $startMonth, $startYear) = explode('/', $currentDate);
				list($startHours, $startMinutes, $startSeconds) = explode(':', $currentTime);
				?>
				<li id="<?php echo $startYear.'-'.$startMonth.'-'.$startDay.'_'.$startHours.'-'.$startMinutes.'-'.$startSeconds; ?>" class="<?php echo ($odd) ? '' : 'timeline-inverted'; ?>">
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
									?>
									<li>
										<?php
										echo $clientName;
										if (isset($session['clientsIPs'][$i])) echo ' ('.$session['clientsIPs'][$i].')';
										?>
									</li>
									<?php
								}
								?>
							</ul>
							<h5>Durées :</h5>
							<ul>
								<li>Heure d'ouverture : <?php echo $session['startTime']; ?></li>
								<li>Heure de fermeture : <?php echo $session['endTime']; ?></li>
								<li>Durée d'exécution du script : <?php echo $session['startDuration']; ?><?php if ($session['startDuration'] != 'Inconnu') echo 's'; ?> <?php Help::iconHelp('La durée estimée ne prend pas en compte le temps de lancement de la session Powershell qui exécute le script. De plus, d\'autres scripts s\'exécutent pendant le démarrage de la session.'); ?></li>
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