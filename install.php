<?php
session_start();
/** Install script for Simple Photos Contests */
if (isset($_POST['step'])){
	if (isset($_POST['back'])){
		$step = intval($_POST['step']) - 2;
	}else{
		$step = intval($_POST['step']);
	}
}elseif(isset($_GET['step'])){
	$step = intval($_GET['step']);
}else{
	$step = 0;
}
$result = new stdClass;
if (file_exists('classes/Settings.php') and $step != 6 ){
	header('Location: index.php');
}
if (file_exists('classes/Settings/config.php')){
	$importFromOldConfigFile = true;
	require_once ('classes/Settings/config.php');
}else {
	$importFromOldConfigFile = false;
}
?>
	<!DOCTYPE html>
	<html lang="fr">
	<head>
		<title>Poulpe2 - Installation</title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<link rel="stylesheet" href="css/poulpe2.css" type="text/css" media="screen" />
		<link rel="icon" type="image/png" href="img/favicons/favicon.ico" />
		<style>
			.info-icon{
				color: #666;
			}
		</style>
	</head>
	<body id="loginBody">
	<a href="https://github.com/Dric/Poulpe2" title="Github de Poulpe2" style="float: right"><i class="fa fa-2x fa-github"></i></a>
	<div id="">
		<!-- Page content -->
		<div id="page-content-wrapper" class="login-wrap container">
			<!-- Si javascript n'est pas activé, on prévient l'utilisateur que ça va merder... -->
			<noscript>
				<div class="alert alert-danger">
					<p class="text-center">Ce site fonctionne sans Javascript, mais vous devriez quand même l'activer pour un plus grand confort d'utilisation.</p>
				</div>
			</noscript>
			<div class="row">
				<div class="col-md-8 col-md-offset-2">
					<div class="text-center">
						<h1>
							Poulpe2 - Installation
						</h1>
						<img src="img/poulpe2-logo-145x200.png" alt="Logo de Poulpe2">
					</div>
				</div>
			</div>
			<div class="row">
				<div class="col-lg-6 col-lg-offset-3 col-md-8 col-md-offset-2 col-sm-10 col-sm-offset-1">
					<!-- Keep all page content within the page-content inset div! -->
					<div class="page-content inset" id="loginPanel">
		<?php
		switch ($step){
			case 0:
				$_SESSION = null;
				?>
				<h2>Bonjour !</h2>
				<form class="large" method="POST" action="install.php">
					<?php if (!$importFromOldConfigFile) { ?>
						<p>Poulpe2 requiert un peu de paramétrage avant de pouvoir être utilisé, prenez le temps de compléter ces quelques étapes d'installation.</p>
					<?php }else { ?>
						<p>Vous avez un ancien fichier de configuration dans <code>classes/Settings/config.php</code>. Nous allons convertir les paramètres de ce fichier dans un nouveau format.</p>
					<?php } ?>
					<br>
					<?php
						$step = ($importFromOldConfigFile) ? 5 : 1;
					?>
					<div class="form_buttons text-right">
						<input class="btn btn-primary" type="submit" value="Suivant" name="submit"/>
						<input type="hidden" name="step" value="<?php echo $step; ?>"/>
					</div>
				</form>
				<?php
				break;
			case 1:
				?>
				<h2>Installation : Base de données</h2>
				<form class="large" method="POST" action="install.php">
					<p>Poulpe2 nécessite une base de données Mysql ou MariaDB (avec moteur InnoDB).<br>Créez une base de données vide sur votre serveur SQL et complétez les champs ci-dessous :</p>
					<?php
					if (isset($_SESSION['message'])){
						echo $_SESSION['message'];
						unset ($_SESSION['message']);
					}
					?>
					<div class="from-group">
						<label for="DB_NAME">Nom de base de données :</label>
						<input class="form-control" required type="text" name="DB_NAME" id="DB_NAME" value="<?php echo (isset($_SESSION['Settings']['DB_NAME'])) ? $_SESSION['Settings']['DB_NAME'] : 'poulpe2'; ?>" />
					</div>
					<div class="from-group">
						<label for="DB_HOST">Serveur de base de données :</label>
						<input required class="form-control" type="text" name="DB_HOST" id="DB_HOST" value="<?php echo (isset($_SESSION['Settings']['DB_HOST'])) ? $_SESSION['Settings']['DB_HOST'] : 'localhost'; ?>" />
					</div>
					<div class="from-group">
						<label for="DB_USER">Utilisateur maître de la base de données :</label>
						<input required class="form-control" type="text" name="DB_USER" id="DB_USER" value="<?php echo (isset($_SESSION['Settings']['DB_USER'])) ? $_SESSION['Settings']['DB_USER'] : 'poulpe2'; ?>" />
					</div>
					<div class="from-group">
						<label for="DB_PASSWORD">Mot de passe de l'utilisateur :</label>
						<input required class="form-control" type="password" name="DB_PASSWORD" id="DB_PASSWORD" value="<?php echo (isset($_SESSION['Settings']['DB_PASSWORD'])) ? $_SESSION['Settings']['DB_PASSWORD'] : ''; ?>" />
					</div>
					<br>
					<div class="form-group text-right">
						<input class="btn btn-default" type="submit" value="Précédent" name="back"/>
						<input class="btn btn-primary" type="submit" value="Suivant" name="submit"/>
						<input type="hidden" name="step" value="2"/>
					</div>
				</form>
				<?php
				break;
			case 2:
				$result->ok = true;
				$result->message = '';
				if (isset($_POST['submit'])){
					if (isset($_POST['DB_NAME']) and !empty($_POST['DB_NAME'])){
						$_SESSION['Settings']['DB_NAME'] = htmlspecialchars($_POST['DB_NAME']);
					}else{
						$result->ok = false;
						$result->message .= '<div class="alert alert-danger text-center">Le nom de base de données est vide !</div>';
					}
					if (isset($_POST['DB_HOST']) and !empty($_POST['DB_HOST'])){
						$_SESSION['Settings']['DB_HOST'] = htmlspecialchars($_POST['DB_HOST']);
					}else{
						$result->ok = false;
						$result->message .= '<div class="alert alert-danger text-center">Le serveur SQL est vide !</div>';
					}
					if (isset($_POST['DB_USER']) and !empty($_POST['DB_USER'])){
						$_SESSION['Settings']['DB_USER'] = htmlspecialchars($_POST['DB_USER']);
					}else{
						$result->ok = false;
						$result->message .= '<div class="alert alert-danger text-center">Le nom d\'utilisateur est vide !</div>';
					}
					if (isset($_POST['DB_PASSWORD']) and !empty($_POST['DB_PASSWORD'])){
						$_SESSION['Settings']['DB_PASSWORD'] = htmlspecialchars($_POST['DB_PASSWORD']);
					}else{
						$result->ok = false;
						$result->message .= '<div class="alert alert-danger text-center">Le mot de passe est vide !</div>';
					}
					if ($result->ok){
						$bd = mysqli_connect($_SESSION['Settings']['DB_HOST'], $_SESSION['Settings']['DB_USER'], $_SESSION['Settings']['DB_PASSWORD']);
						if (!$bd){
							$result->ok = false;
							$result->message .= '<div class="alert alert-danger text-center">Impossible de se connecter au serveur de base de données !<br>'.mysqli_connect_errno().' : '.mysqli_connect_error().'</div>';
						}else{
							$db_name = $_SESSION['Settings']['DB_NAME'];
							$res = mysqli_select_db($bd, $db_name);
							if (!$res){
								$result->ok = false;
								$result->message .= '<div class="alert alert-danger text-center">Impossible de se connecter à la base de données !<br>'.mysqli_connect_errno().' : '.mysqli_connect_error().'</div>';
							}
						}
					}
				}
				if (!$result->ok){
					$_SESSION['message'] = $result->message;
					header('Location: ?step=1');
				}else{
					?>
					<h2>Installation : Authentification</h2>
					<form class="large" method="POST" action="install.php">
						<p>Pour pouvoir utiliser Poulpe2, il faut pouvoir s'identifier (même s'il peut être utilisé sans authentification, il faut nécessairement l'activer à l'installation).</p>
						<?php
						if (isset($_SESSION['message'])){
							echo $_SESSION['message'];
							unset ($_SESSION['message']);
						}
						?>
						<div class="from-group">
							<label for="AUTH_MODE">Type d'authentification :</label>
							<select class="form-control" name="AUTH_MODE" id="AUTH_MODE">
								<option>SQL</option>
								<option <?php if (isset($_SESSION['Settings']['AUTH_MODE']) and $_SESSION['Settings']['AUTH_MODE'] == 'LDAP') { echo 'selected'; } ?>>LDAP</option>
							</select>
						</div>
						<div class="from-group">
							<label for="PWD_MIN_SIZE">Taille minimale des mots de passe :</label>
							<input required class="form-control" type="number" name="PWD_MIN_SIZE" id="PWD_MIN_SIZE" value="<?php echo (isset($_SESSION['Settings']['PWD_MIN_SIZE'])) ? $_SESSION['Settings']['PWD_MIN_SIZE'] : 6; ?>" />
						</div>
						<div class="from-group">
							<label for="SALT_AUTH">Clé de salage pour l'authentification :</label>
							<input pattern=".{40,}" required class="form-control" type="text" name="SALT_AUTH" id="SALT_AUTH" value="<?php echo (isset($_SESSION['Settings']['SALT_AUTH'])) ? $_SESSION['Settings']['SALT_AUTH'] : ''; ?>" />
							<span class="help-block info-icon">Une clé de salage peut être une phrase de plusieurs mots ou une suite de caractères d'une longueur d'au moins 40 caractères.<br> Elle est utilisée pour renforcer la sécurité de votre instance de Poulpe2.<br>Exemple : <code>Ma grand-mère est riche mais super cupide !</code></code>.</span>
						</div>
						<div class="from-group">
							<label for="COOKIE_NAME">Nom du cookie d'authentification :</label>
							<input required class="form-control" type="text" name="COOKIE_NAME" id="COOKIE_NAME" value="<?php echo (isset($_SESSION['Settings']['COOKIE_NAME'])) ? $_SESSION['Settings']['COOKIE_NAME'] : 'poulpe2'; ?>" />
						</div>
						<div class="from-group">
							<label for="COOKIE_DURATION">Durée de validité du cookie (en heures - <code>4320</code> correspond à 6 mois) :</label>
							<input required class="form-control" type="number" name="COOKIE_DURATION" id="COOKIE_DURATION" value="<?php echo (isset($_SESSION['Settings']['COOKIE_DURATION'])) ? $_SESSION['Settings']['COOKIE_DURATION'] : 4320; ?>" />
						</div>
						<div class="from-group">
							<label for="SALT_COOKIE">Clé de salage du cookie (doit être différente de celle d'authentification) :</label>
							<input pattern=".{40,}" required class="form-control" type="text" name="SALT_COOKIE" id="SALT_COOKIE" value="<?php echo (isset($_SESSION['Settings']['SALT_COOKIE'])) ? $_SESSION['Settings']['SALT_COOKIE'] : ''; ?>" />
							<span class="help-block info-icon">40 caractères minimum.<br>Exemple : <code>Mon chien me bat à la course, mais il a deux pattes de plus que moi.</code>.</span>
						</div>
						<br>
						<div class="form-group text-right">
							<input class="btn btn-default" type="submit" value="Précédent" name="back"/>
							<input class="btn btn-primary" type="submit" value="Suivant" name="submit"/>
							<input type="hidden" name="step" value="3"/>
						</div>
					</form>
					<?php
				}
				break;
			case 3:
				$result->ok = true;
				$result->message = '';
				if (isset($_POST['submit'])){
					if (isset($_POST['AUTH_MODE']) and !empty($_POST['AUTH_MODE'])){
						$_SESSION['Settings']['AUTH_MODE'] = htmlspecialchars($_POST['AUTH_MODE']);
					}else{
						$result->ok = false;
						$result->message .= '<div class="alert alert-danger text-center">Le mode d\'authentification est vide !</div>';
					}
					if (isset($_POST['PWD_MIN_SIZE']) and !empty($_POST['PWD_MIN_SIZE'])){
						$_SESSION['Settings']['PWD_MIN_SIZE'] = (int)$_POST['PWD_MIN_SIZE'];
					}else{
						$result->ok = false;
						$result->message .= '<div class="alert alert-danger text-center">La longueur de mot de passe est vide !</div>';
					}
					if (isset($_POST['SALT_AUTH']) and !empty($_POST['SALT_AUTH']) and strlen($_POST['SALT_AUTH']) > 39){
						$_SESSION['Settings']['SALT_AUTH'] = htmlspecialchars($_POST['SALT_AUTH']);
					}else{
						$result->ok = false;
						$result->message .= '<div class="alert alert-danger text-center">La clé de salage d\'authentification est vide ou elle fait moins de 40 caractères !</div>';
					}
					if (isset($_POST['COOKIE_NAME']) and !empty($_POST['COOKIE_NAME'])){
						$_SESSION['Settings']['COOKIE_NAME'] = htmlspecialchars($_POST['COOKIE_NAME']);
					}else{
						$result->ok = false;
						$result->message .= '<div class="alert alert-danger text-center">Le nom du cookie est vide !</div>';
					}
					if (isset($_POST['COOKIE_DURATION']) and !empty($_POST['COOKIE_DURATION'])){
						$_SESSION['Settings']['COOKIE_DURATION'] = (int)$_POST['COOKIE_DURATION'];
					}else{
						$result->ok = false;
						$result->message .= '<div class="alert alert-danger text-center">La durée de validité du cookie est vide !</div>';
					}
					if (isset($_POST['SALT_COOKIE']) and !empty($_POST['SALT_COOKIE']) and strlen($_POST['SALT_AUTH']) > 39 and htmlspecialchars($_POST['SALT_COOKIE']) != $_SESSION['Settings']['SALT_AUTH']){
						$_SESSION['Settings']['SALT_COOKIE'] = htmlspecialchars($_POST['SALT_COOKIE']);
					}else{
						$result->ok = false;
						$result->message .= '<div class="alert alert-danger text-center">La clé de salage du cookie est vide, elle fait moins de 40 caractères ou elle est identique à la clé de salage d\'authentification !</div>';
					}
				}
				if (!$result->ok){
					$_SESSION['message'] = $result->message;
					header('Location: ?step=2');
				}else{
					/** Connection to DB. */
					$bd = mysqli_connect($_SESSION['Settings']['DB_HOST'], $_SESSION['Settings']['DB_USER'], $_SESSION['Settings']['DB_PASSWORD']);
					if (!$bd){
						$result->ok = false;
						$result->message .= '<div class="alert alert-danger text-center">Impossible de se connecter au serveur de base de données !<br>'.mysqli_connect_errno().' : '.mysqli_connect_error().'</div>';
					}else{
						$db_name = $_SESSION['Settings']['DB_NAME'];
						$res = mysqli_select_db($bd, $db_name);
						if (!$res){
							$result->ok = false;
							$result->message .= '<div class="alert alert-danger text-center">Impossible de se connecter à la base de données !<br>'.mysqli_connect_errno().' : '.mysqli_connect_error().'</div>';
						}
					}
					/** Import sql install file (http://shinephp.com/php-code-to-execute-mysql-script/) */

					@trigger_error("");
					$f = @fopen('install.sql',"r");
					if (!$f){
						$error = error_get_last();
						$err_tab = explode(': ', $error['message'], 2);
						$_SESSION['message'] = '<div class="alert alert-danger text-center">'.$err_tab[1].'</div>';
						$result->ok = false;
					}
					$sqlFile = fread($f, filesize('install.sql'));
					$sqlArray = explode(';',$sqlFile);
					foreach ($sqlArray as $stmt) {
						if (strlen($stmt)>3) {
							$res = mysqli_query($bd, $stmt);
							if (!$res) {
								$sqlErrorCode = mysqli_errno($bd);
								$sqlErrorText = mysqli_error($bd);
								$sqlStmt = $stmt;
								$result->ok = false;
								$result->message .= '<div class="alert alert-danger text-center">Une erreur est survenue pendant la création de la structure de base de données !<br/>'.$sqlErrorCode.' : '.$sqlErrorText.'<br/>Informations complémentaires : '.$sqlStmt.'</div>';
								break;
							}
						}
					}
					if ($result->ok) {
						$result->message =  '<div class="alert alert-success text-center">La structure de base de données a été importée avec succès !</div>';
					}
					fclose($f);
					$_SESSION['message'] = $result->message;
					?>
					<h2>Installation : Importation de la structure de base de données</h2>
					<form class="large" method="POST" action="install.php">
						<p>L'installeur tente d'importer la structure dans la base de données <code><?php echo $_SESSION['Settings']['DB_NAME']; ?></code> avec le contenu de <code>install.sql</code>.</p>
						<?php
						if (isset($_SESSION['message'])){
							echo $_SESSION['message'];
							unset ($_SESSION['message']);
						}
						?>
						<div class="form-group text-right">

							<?php if (!$result->ok){ ?>
								<input class="btn btn-primary" type="submit" value="Précédent" name="back"/>
								<?php }else{ ?>
								<input class="btn btn-primary" type="submit" value="Suivant" name="submit"/>
							<?php }
							if ($_SESSION['Settings']['AUTH_MODE'] == 'LDAP'){
								?><input type="hidden" name="step" value="4"/><?php
							} else {
								?><input type="hidden" name="step" value="5"/><?php
							}
							?>
						</div>
					</form>
					<?php
				}
				break;
			case 4:
				$result->ok = true;
				$result->message = '';
				if (isset($_POST['submit'])){
					//Nothing to put here yet, but I leave this in case of change.
				}
				if (!$result->ok){
					$_SESSION['message'] = $result->message;
					header('Location: ?step=3');
				}else{
					?>
					<h2>Installation : Annuaire LDAP</h2>
					<form class="large" method="POST" action="install.php">
						<p>Vous avez sélectionné <code>LDAP</code> comme méthode d'authentification. Veuillez compléter le paramétrage d'accès à LDAP.</p>
						<?php
						if (isset($_SESSION['message'])){
							echo $_SESSION['message'];
							unset ($_SESSION['message']);
						}
						?>
						<div class="from-group">
							<label for="LDAP_DOMAIN">Nom de domaine :</label>
							<input required placeholder="domaine.fr" class="form-control" type="text" name="LDAP_DOMAIN" id="LDAP_DOMAIN" value="<?php echo (isset($_SESSION['Settings']['LDAP_DOMAIN'])) ? $_SESSION['Settings']['LDAP_DOMAIN'] : ''; ?>" />
						</div>
						<div class="from-group">
							<label for="LDAP_SERVERS">Serveur(s) LDAP :</label>
							<textarea class="form-control" placeholder="192.168.0.1" required rows="4" name="LDAP_SERVERS" id="LDAP_SERVERS"><?php echo (isset($_SESSION['Settings']['LDAP_SERVERS'])) ? implode("\n", $_SESSION['Settings']['LDAP_SERVERS']) : ''; ?></textarea>
							<span class="help-block info-icon">Un serveur par ligne. Vous pouvez indiquer une adresse IP ou le nom du serveur sans son suffixe DNS.</span>
						</div>
						<div class="from-group">
							<label for="LDAP_BIND_NAME">Nom de l'utilisateur pour la connexion à LDAP :</label>
							<input required placeholder="user.poulpe2" class="form-control" type="text" name="LDAP_BIND_NAME" id="LDAP_BIND_NAME" value="<?php echo (isset($_SESSION['Settings']['LDAP_BIND_NAME'])) ? $_SESSION['Settings']['LDAP_BIND_NAME'] : ''; ?>" />
						</div>
						<div class="from-group">
							<label for="LDAP_BIND_PWD">Mot de passe de l'utilisateur LDAP :</label>
							<input required class="form-control" type="password" name="LDAP_BIND_PWD" id="LDAP_BIND_PWD" value="<?php echo (isset($_SESSION['Settings']['LDAP_BIND_PWD'])) ? $_SESSION['Settings']['LDAP_BIND_PWD'] : ''; ?>" />
						</div>
						<div class="from-group">
							<label for="LDAP_DC">Conteneur de recherche dans LDAP :</label>
							<input required placeholder="DC=domaine,DC=fr" class="form-control" type="text" name="LDAP_DC" id="LDAP_DC" value="<?php echo (isset($_SESSION['Settings']['LDAP_DC'])) ? $_SESSION['Settings']['LDAP_DC'] : ''; ?>" />
							<span class="help-block info-icon">Si vous souhaitez que les utilisateurs de l'ensemble de votre annuaire puissent se connecter, il vous suffit d'indiquer votre nom de domaine avec chaque composante préfixée par <code>DC=</code>.<br>Exemple : <code>DC=domaine,DC=fr</code></span>
						</div>
						<div class="from-group">
							<label for="LDAP_AUTH_OU">Unité d'Organisation (OU) dans laquelle aller chercher les comptes utilisateurs autorisés à se connecter :</label>
							<input required placeholder="*" class="form-control" type="text" name="LDAP_AUTH_OU" id="LDAP_AUTH_OU" value="<?php echo (isset($_SESSION['Settings']['LDAP_AUTH_OU'])) ? $_SESSION['Settings']['LDAP_AUTH_OU'] : '*'; ?>" />
							<span class="help-block info-icon">Si vous ne souhaitez pas restreindre les connexions à une Unité d'Organisation particulière, indiquez <code>*</code>.</span>
						</div>
						<div class="from-group">
							<label for="LDAP_GROUP">Groupe LDAP autorisé à se connecter :</label>
							<input class="form-control" type="text" name="LDAP_GROUP" id="LDAP_GROUP" value="<?php echo (isset($_SESSION['Settings']['LDAP_GROUP'])) ? $_SESSION['Settings']['LDAP_GROUP'] : ''; ?>" />
							<span class="help-block info-icon">Si vous spécifiez un groupe LDAP, seuls ses membres pourront se connecter à Poulpe2. Laissez vide pour ne pas restreindre les connexions à un groupe d'utilisateurs.</span>
						</div>
						<div class="form-group text-right">
							<input class="btn btn-default" type="submit" value="Précédent" name="back"/>
							<input class="btn btn-primary" type="submit" value="Suivant" name="submit"/>
							<input type="hidden" name="step" value="5"/>
						</div>
					</form>
					<?php
				}
				break;
			case 5:
				$result->ok = true;
				$result->message = '';
				if (!isset($_SESSION['message'])){
					$_SESSION['message'] = '';
				}
				if (!$importFromOldConfigFile){
					if (isset($_POST['submit'])){
						if ($_SESSION['Settings']['AUTH_MODE'] == 'LDAP'){
							if (isset($_POST['LDAP_DOMAIN']) and !empty($_POST['LDAP_DOMAIN'])){
								$_SESSION['Settings']['LDAP_DOMAIN'] = htmlspecialchars($_POST['LDAP_DOMAIN']);
							}else{
								$result->ok = false;
								$result->message .= '<div class="alert alert-danger text-center">Le domaine LDAP est vide !</div>';
							}
							if (isset($_POST['LDAP_SERVERS']) and !empty($_POST['LDAP_SERVERS'])){
								$_SESSION['Settings']['LDAP_SERVERS'] = explode(PHP_EOL, $_POST['LDAP_SERVERS']);
							}else{
								$result->ok = false;
								$result->message .= '<div class="alert alert-danger text-center">Les serveurs LDAP sont vides !</div>';
							}
							if (isset($_POST['LDAP_BIND_NAME']) and !empty($_POST['LDAP_BIND_NAME'])){
								$_SESSION['Settings']['LDAP_BIND_NAME'] = htmlspecialchars($_POST['LDAP_BIND_NAME']);
							}else{
								$result->ok = false;
								$result->message .= '<div class="alert alert-danger text-center">Le nom d\'utilisateur pour la connexion LDAP est vide !</div>';
							}
							if (isset($_POST['LDAP_BIND_PWD']) and !empty($_POST['LDAP_BIND_PWD'])){
								$_SESSION['Settings']['LDAP_BIND_PWD'] = htmlspecialchars($_POST['LDAP_BIND_PWD']);
							}else{
								$result->ok = false;
								$result->message .= '<div class="alert alert-danger text-center">Le mot de passe pour la connexion LDAP est vide !</div>';
							}
							if (isset($_POST['LDAP_DC']) and !empty($_POST['LDAP_DC'])){
								$_SESSION['Settings']['LDAP_DC'] = htmlspecialchars($_POST['LDAP_DC']);
							}else{
								$result->ok = false;
								$result->message .= '<div class="alert alert-danger text-center">Le conteneur de recherche LDAP est vide !</div>';
							}
							if (isset($_POST['LDAP_AUTH_OU']) and !empty($_POST['LDAP_AUTH_OU'])){
								$_SESSION['Settings']['LDAP_AUTH_OU'] = htmlspecialchars($_POST['LDAP_AUTH_OU']);
							}else{
								$result->ok = false;
								$result->message .= '<div class="alert alert-danger text-center">L\'Unité d\'organisation LDAP est vide ! (spécifiez <code>*</code> pour accéder à l\'ensemble de l\'annuaire)</div>';
							}
							if (isset($_POST['LDAP_GROUP']) and !empty($_POST['LDAP_GROUP'])){
								$_SESSION['Settings']['LDAP_GROUP'] = htmlspecialchars($_POST['LDAP_GROUP']);

							}
							if ($result->ok){
								$long = ip2long($_SESSION['Settings']['LDAP_SERVERS'][0]);
								if (!($long == -1 || $long === FALSE)){
									$connection = ldap_connect($_SESSION['Settings']['LDAP_SERVERS'][0], 389);
								}else{
									$connection = ldap_connect($_SESSION['Settings']['LDAP_SERVERS'][0].'.'.$_SESSION['Settings']['LDAP_DOMAIN'], 389);
								}
								ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3); //Option à ajouter si vous utilisez Windows server2k3 minimum
								ldap_set_option($connection, LDAP_OPT_REFERRALS, 0); //Option à ajouter si vous utilisez Windows server2k3 minimum
								/** Connexion à l'AD avec les identifiants saisis à la connexion.. */
								// Méfiance, si le mot de passe est vide, une connexion anonyme sera tentée et la connexion peut retourner true...
								$r = @ldap_bind($connection, $_SESSION['Settings']['LDAP_BIND_NAME'].'@'.$_SESSION['Settings']['LDAP_DOMAIN'], $_SESSION['Settings']['LDAP_BIND_PWD']);
								ldap_close($connection);
								if ($r === false){
									$result->ok = false;
									$result->message .= '<div class="alert alert-danger text-center">Impossible de se connecter à l\'annuaire LDAP !<br>Vérifiez votre paramétrage.</div>';
								}
							}
						}
					}
					if (!$result->ok){
						$_SESSION['message'] .= $result->message;
						header('Location: ?step=4');
					}
				}else{
					$importedSettings = array();
					if (!empty(SITE_NAME))        $importedSettings['SITE_NAME']        = SITE_NAME;
					if (!empty(DB_NAME))          $importedSettings['DB_NAME']          = DB_NAME;
					if (!empty(DB_HOST))          $importedSettings['DB_HOST']          = DB_HOST;
					if (!empty(DB_USER))          $importedSettings['DB_USER']          = DB_USER;
					if (!empty(DB_PASSWORD))      $importedSettings['DB_PASSWORD']      = DB_PASSWORD;
					if (!empty(AUTH_MODE))        $importedSettings['AUTH_MODE']        = AUTH_MODE;
					if (!empty(PWD_MIN_SIZE))     $importedSettings['PWD_MIN_SIZE']     = PWD_MIN_SIZE;
					if (!empty(SALT_AUTH))        $importedSettings['SALT_AUTH']        = SALT_AUTH;
					if (!empty(COOKIE_NAME))      $importedSettings['COOKIE_NAME']      = COOKIE_NAME;
					if (!empty(COOKIE_DURATION))  $importedSettings['COOKIE_DURATION']  = COOKIE_DURATION;
					if (!empty(SALT_COOKIE))      $importedSettings['SALT_COOKIE']      = SALT_COOKIE;
					if (!empty(LDAP_DOMAIN))      $importedSettings['LDAP_DOMAIN']      = LDAP_DOMAIN;
					if (!empty(LDAP_SERVERS))     $importedSettings['LDAP_SERVERS']['values']     = explode(', ', LDAP_SERVERS);
					if (!empty(LDAP_BIND_NAME))   $importedSettings['LDAP_BIND_NAME']   = LDAP_BIND_NAME;
					if (!empty(LDAP_BIND_PWD))    $importedSettings['LDAP_BIND_PWD']    = LDAP_BIND_PWD;
					if (!empty(LDAP_DC))          $importedSettings['LDAP_DC']          = LDAP_DC;
					if (!empty(LDAP_AUTH_OU))     $importedSettings['LDAP_AUTH_OU']     = LDAP_AUTH_OU;
					if (!empty(LDAP_GROUP))       $importedSettings['LDAP_GROUP']       = LDAP_GROUP;
					if (!empty(HOME_MODULE))      $importedSettings['HOME_MODULE']      = HOME_MODULE;
					if (!empty(MODULE_DIR))       $importedSettings['MODULE_DIR']       = MODULE_DIR;
					if (!empty(MODULE_URL))       $importedSettings['MODULE_URL']       = MODULE_URL;
					$_SESSION['Settings'] = $importedSettings;
				}

				$_SESSION['Settings']['DB_NAME-explain'] = 'Nom de la base de données';
				$_SESSION['Settings']['DB_HOST-explain'] = 'Serveur de base de données';
				$_SESSION['Settings']['DB_USER-explain'] = 'Utilisateur de la base de données';
				$_SESSION['Settings']['DB_PASSWORD-explain'] = 'Mot de passe de la base de données';
				$_SESSION['Settings']['AUTH_MODE-explain'] = 'Authentification via ldap ou sql';
				$_SESSION['Settings']['PWD_MIN_SIZE-explain'] = 'Longueur minimale du mot de passe (authentification sql)';
				$_SESSION['Settings']['SALT_AUTH-explain'] = 'Clé de salage d\'authentification';
				$_SESSION['Settings']['COOKIE_NAME-explain'] = 'Nom du cookie d\'authentification';
				$_SESSION['Settings']['COOKIE_DURATION-explain'] = 'Durée de l\'authentification par cookie (en heures)';
				$_SESSION['Settings']['SALT_COOKIE-explain'] = 'Clé de salage du cookie';
				$_SESSION['Settings']['LDAP_DOMAIN-explain'] = 'Nom du domaine LDAP';
				$_SESSION['Settings']['LDAP_SERVERS-explain'] = 'Nom courant des serveurs LDAP';
				$_SESSION['Settings']['LDAP_BIND_NAME-explain'] = 'Nom utilisateur pour se connecter à LDAP';
				$_SESSION['Settings']['LDAP_BIND_PWD-explain'] = 'Mot de passe utilisateur pour se connecter à LDAP';
				$_SESSION['Settings']['LDAP_DC-explain'] = 'Conteneur de recherche des comptes LDAP (DC=contoso,DC=com)';
				$_SESSION['Settings']['LDAP_AUTH_OU-explain'] = 'OU dans laquelle chercher les comptes utilisateurs';
				$_SESSION['Settings']['LDAP_GROUP-explain'] = 'Groupe autorisé à se connecter (tous les groupes si vide)';
				$_SESSION['Settings']['HOME_MODULE-explain'] = 'Module en page d\'accueil';
				$_SESSION['Settings']['MODULE_DIR-explain'] = 'Répertoire des modules (respecter la casse)';
				$_SESSION['Settings']['MODULE_URL-explain'] = 'URL des appels aux modules';
				$_SESSION['Settings']['SITE_NAME-explain'] = 'Nom du site';

				$fileContent = "
<?php\n/**\n* Paramètres de poulpe2\n*\n* Importez les constantes de `classes/DefaultSettings` et modifiez-les pour adapter les paramètres à votre instance\n*/\nclass Settings extends DefaultSettings {\n";

				foreach ($_SESSION['Settings'] as $setting => $value){
					// On ne traite que les champs, pas les explications
					if (strpos($setting, '-explain') === false and $setting != 'action'){
						if (!empty($value)){
							if (is_array($value)) {
								if (!empty($value['values'])){
									$stringValue = 'array(';
									foreach ($value['values'] as $subValue) {
										$stringValue .= (is_int($subValue)) ? $subValue : '\'' . $subValue . '\', ';
									}
									$value = rtrim($stringValue, ', ') . ')';
								} else {
									continue;
								}
							}elseif (is_bool($value)) {
								$value = ($value) ? 'true' : 'false';
							}else{
								$value = (is_int($value)) ? $value : '\''.$value.'\'';
							}
							$fileContent .= "\n\t".'/** '.$_SESSION['Settings'][$setting.'-explain'].' */';
							$fileContent .= "\n\t".'const ' . str_replace('-', '_', $setting) . ' = '.$value.';'."\n";
						}
					}
				}
				$fileContent .= '}';
				// On écrit dans le fichier
				/** To reset last php error (if any) */
				@trigger_error("");
				$ret = @file_put_contents('classes/Settings.php', $fileContent);
				if (!$ret){
						$_SESSION['message'] .= '<div class="alert alert-danger text-center">Impossible d\'écrire le fichier de configuration !</div>';
						$result->ok = false;
				}else{
					$_SESSION['message'] .= '<div class="alert success">Le fichier de configuration a été créé avec succès !</div>';
				}
				?>
				<h2>Installation : Création du fichier de configuration</h2>
				<form class="large" method="POST" action="install.php">
					<p>L'installeur tente de créer le fichier de configuration.</p>
					<?php
					if (isset($_SESSION['message'])){
						echo $_SESSION['message'];
						unset ($_SESSION['message']);
					}
					if (!$result->ok){
						?>
						<p>L'installeur n'a pas réussi à créer le fichier. Créez un fichier <code>classes/Settings.php</code> et collez-y le contenu suivant :</p>
						<xmp style="white-space: pre-wrap;font-size: 0.8em;background-color: #eee;color: #666;padding: 8px;">
							<?php echo $fileContent; ?>
						</xmp>
					<?php } ?>
					<div class="form-group text-right">
						<input class="btn btn-default" type="submit" value="Précédent" name="back"/>
						<input class="btn btn-primary" type="submit" value="Suivant" name="submit"/>
						<input type="hidden" name="step" value="6"/>
					</div>
				</form>
				<?php
				break;
			case 6:
				$result->ok = true;
				$result->message = '';
				if (isset($_POST['submit'])){
					$f = @fopen('classes/Settings.php',"r");
					if (!$f){
						$result->message = '<div class="alert alert-danger text-center">Impossible d\'ouvrir le fichier <code>Settings.php</code> !</div>';
						$result->ok = false;
					}
				}
				if (!$result->ok){
					$_SESSION['message'] = $result->message;
					header('Location: ?step=6');
				}else {
					if (!$importFromOldConfigFile) {
						?>
						<h2>Installation : Presque terminée !</h2>
						<form class="large" method="POST" action="install.php">
							<p>Il reste quelques manipulations pour que votre instance de Poulpe2 soit prête :</p>
							<h3>Téléchargement des modules</h3>
							<p>
								Vous devez cloner le dépôt des modules dans un répertoire <code>Modules</code>.<br><br>
								Dans le répertoire racine, lancez une des commandes suivantes : </p>
							<ul>
								<li>Instance personnelle : <code>git clone https://github.com/Dric/Poulpe2_Modules.git Modules</code></li>
								<li>Instance CHGS : <code>git clone http://srv-glpitest/git/Informatique-CHGS/poulpe2_modules_chgs.git Modules</code></li>
							</ul>
							<h3>Instance CHGS</h3>
							<ul>
								<li>Pour effectuer des opérations à distance sur des serveurs Windows, il faut installer un package debian situé dans <code>Modules/BandesTina</code>. Une fois dans ce répertoire, saisissez cette commande : <br><code>sudo dpkg -i winexe_1.00-1_i386.deb</code></li>
								<li>Le répertoire <code>/mnt</code> doit être accessible en écriture à l'utilisateur apache (<code>www-data</code> par défaut)</li>
								<li>La possibilité de monter des répertoires en cifs doit être active sur le serveur. Avec Ubuntu 14, il faut installer : <code>sudo apt-get install cifs-utils</code></li>
								<li>Les modules php suivants doivent être installés sur le serveur : <code>curl</code>, <code>ldap</code></li>
								<li>
									L'utilisateur apache doit avoir le droit d'invoquer sudo sans mot de passe pour la commande <code>mount</code>. Ceci est quand potentiellement un trou de sécurité...
									<ul>
										<li>Dans un terminal, saisir : <code>sudo visudo</code></li>
										<li>Ajouter à la dernière ligne :<br>
											<code>
												&emsp;Defaults verifypw = any<br> &emsp;www-data ALL= (ALL:ALL) NOPASSWD: /bin/mount,/bin/umount,/sbin/mount.cifs,/sbin/umount.cifs,/usr/bin/timeout
											</code>
										</li>
										<li>Attention : si vous lancez apache sous un autre nom (<code>administrateur</code> par exemple), modifiez la ligne du dessus en conséquence.</li>
									</ul>
								</li>
							</ul>
							<h3>Annuaire LDAP</h3>
							<p>
								Si vous avez choisi la connexion à un annuaire LDAP, vous pouvez opter pour une connexion sécurisée.<br>
								Il vous faut pour cela paramétrer le fichier <code>/etc/ldap/ldap.conf</code> et ajouter la ligne suivante :<br>
								<code>
									TLS_REQCERT never
								</code>
							</p>
							<p>Vous devez ensuite activer dans le fichier de configuration l'option <code>Connexion sécurisée à l'annuaire LDAP</code>.</p>
							<h3>Première connexion</h3>

							<p>
								Le premier utilisateur à se connecter est automatiquement bombardé administrateur.<br><br>
								Pensez bien à aller faire un tour dans la rubrique <code>Administration/Configuration</code> pour vérifier que tous les paramètres sont définis comme vous le souhaitez. </p>
							<div class="form-group text-center">
								<a class="btn btn-primary btn-lg" href="index.php" title="Go to SPC">Lancer Poulpe2</a>
							</div>
						</form>
						<?php
					}else{
						?>
						<h2>Mise à jour du fichier de configuration terminé !</h2>
						<p>Pour des raisons de sécurité, supprimez l'ancien fichier de configuration dans <code>classes/Settings/config.php</code></p><br><br>
						<div class="form-group text-center">
							<a class="btn btn-primary btn-lg" href="index.php" title="Go to SPC">Lancer Poulpe2</a>
						</div>
						<?php
					}
				}
				break;
		}
		?>

					</div>
				</div>
			</div>
		</div>
	</div>
	<script type="text/javascript" src="js/jquery-1.11.0.min.js"></script>
	</body>
	</html>
<?php

/**
 * Used to generate a salt key.
 *
 * @param int $length Length of the returned string
 *
 * @return string
 */
function generateRandomString($length = 40) {
	return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!#@&$*%?-+="), 0, $length);
}
?>