<?php
/**
 * Creator: Dric
 * Date: 17/11/2016
 * Time: 09:48
 */

namespace SSH;

use Exception;
use Logs\Alert;

/**
 * Classe de connexion à une session SSH
 *
 * Pré-requis : Sur ubuntu, il suffit d'installer le package ssh2 pour PHP avec :
 *
 *     sudo apt install php-ssh2
 *     sudo service apache2 restart
 *
 * Serveur SSH Windows :
 *
 *  <https://winscp.net/eng/docs/guide_windows_openssh_server>
 *  Sous Windows il sera très difficile d'accéder à un partage réseau (Windows bloquant le double-rebond), il vaut mieux lancer un script local qui s'authentifiera et lancera des commandes réseau.
 *  De même, les codes de caractères sont particulièrement douteux avec Open-SSH Windows 0.0.5.0. N'espérez pas utiliser les é ou ê par exemple.
 *
 * @package SSH
 */
class SSH {

	/**
	 * Serveur distant
	 * @var string
	 */
	protected $remoteServer = null;
	/** @var string Compte utilisé pour se connecter */
	protected $user = null;
	/** @var string Mot de passe du compte */
	protected $pwd = null;
	/** @var int Port utilisé pour la connexion */
	protected $port = 22;
	/**
	 * Connexion SSH
	 * @var Resource
	 */
	protected $connection = null;
	/** @var bool Etat de la connexion */
	protected $isConnected = false;

	/**
	 * SSH constructor.
	 *
	 * @param string $remoteServer
	 * @param string $SSHUser
	 * @param string $SSHPwd
	 * @param int $SSHPort
	 */
	public function __construct($remoteServer = null, $SSHUser = null, $SSHPwd = null, $SSHPort = null){
		$this->remoteServer = (empty($remoteServer)) ? \Settings::SSH_REMOTE_SERVER : $remoteServer;
		$this->user         = (empty($SSHUser)) ? \Settings::SSH_USER : $SSHUser;
		$this->pwd          = (empty($SSHPwd)) ? \Settings::SSH_PWD : $SSHPwd;
		$this->port         = (int)((empty($SSHPort)) ? \Settings::SSH_PORT : $SSHPort);
		$this->isConnected = $this->connect();
	}

	/**
	 * Etablit la connexion au serveur SSH distant
	 * @return bool
	 */
	protected function connect(){
		if (empty($this->remoteServer)){
			new Alert('error', 'Erreur : <code>remoteServer</code> est vide !<br>Veuillez renseigner les paramètres SSH dans le fichier de configuration <code>Settings.php</code>.');
			return false;
		}
		if (empty($this->user)){
			new Alert('error', 'Erreur : <code>user</code> est vide !<br>Veuillez renseigner les paramètres SSH dans le fichier de configuration <code>Settings.php</code>.');
			return false;
		}
		if (empty($this->pwd)){
			new Alert('error', 'Erreur : <code>pwd</code> est vide !<br>Veuillez renseigner les paramètres SSH dans le fichier de configuration <code>Settings.php</code>.');
			return false;
		}
		if (empty($this->port)){
			new Alert('error', 'Erreur : <code>port</code> est vide !<br>Veuillez renseigner les paramètres SSH dans le fichier de configuration <code>Settings.php</code>.');
			return false;
		}
		try {
			$callback = array('disconnect' => array($this, 'SSHDisconnectMessage'));
			$this->connection = @ssh2_connect($this->remoteServer, $this->port, null, $callback);
			if (!$this->connection) {
				new Alert('error', 'Impossible de se connecter au serveur SSH <code>'.$this->remoteServer.'</code>');
				return false;
			}
			if (@ssh2_auth_password($this->connection, $this->user, $this->pwd)) {
				return true;
			}
		} catch (Exception $e){
			new Alert('error', 'Impossible de se connecter au serveur distant <code>'.$this->remoteServer.'</code> : '.$e->getMessage());
		}
		return false;
	}

	/**
	 * Affiche une alerte si la connexion échoue
	 *
	 * NE SEMBLE PAS FONCTIONNER
	 * @param string $reason
	 * @param string $message
	 * @param string $language
	 */
	protected function SSHDisconnectMessage($reason, $message, $language) {
		new Alert('error', sprintf("Server disconnected with reason code [%d] and message: %s\n",	$reason, $message));
		//var_dump($message);
	}

	/**
	 * Execute une commande dans la session SSH
	 * @param string $command Commande à passer
	 *
	 * @return bool|string[]
	 */
	public function exec($command){
		//var_dump($command);
		if (!$this->isConnected) return false;
		$stream = @ssh2_exec($this->connection, $command);
		if ($stream === false) {
			new Alert('error', 'Erreur : impossible d\'exécuter la commande <code>' . $command . '</code>');
			return false;
		}
		//var_dump(stream_get_contents($stream));
		$err_stream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
		stream_set_blocking($err_stream, true);
		$stream_out = ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
		stream_set_blocking($stream_out, true);
		//var_dump(stream_get_contents($stream));
		$error = str_replace("\r\n", '', mb_convert_encoding( stream_get_contents($err_stream), 'UTF-8' ));
		if ( !empty($error)){
			new Alert('error', 'Erreur : '.$error);
		}
		$ret = str_replace("\r\n", '', mb_convert_encoding( stream_get_contents($stream_out), 'UTF-8' ));
		fclose($err_stream);
		fclose($stream_out);
		fclose($stream);
		return array('return' => $ret, 'error' => $error);
	}

	/**
	 * Shell SSH
	 * @param array $commands
	 *
	 * @return array|bool
	 */
	public function shell(Array $commands){
		$ret = array();
		if (!$this->isConnected) return false;
		//var_dump($commands);
		$shell = ssh2_shell($this->connection, 'xterm', null, 200, 200, SSH2_TERM_UNIT_CHARS);
		foreach ($commands as $command){
			//var_dump($command);
			fwrite( $shell, $command.PHP_EOL);
			//stream_set_blocking($shell, true);
			while($line = fgets($shell)) {
				flush();
				//var_dump($line);
				$ret['return'][] = $line;
			}
		}
		fwrite( $shell, 'exit'.PHP_EOL);
		unset($shell);
		return $ret;
	}

	/**
	 * Déconnecte la session SSH
	 */
	protected function disconnect() {
		unset($this->connection);
		$this->isConnected = false;
	}

	/** Destruction de l'objet */
	public function __destruct() {
		if ($this->isConnected) $this->disconnect();
	}

	/**
	 * Vérifie qu'une session SSH est active
	 * @return boolean
	 */
	public function isConnected() {
		return $this->isConnected;
	}
}