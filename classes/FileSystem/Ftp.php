<?php
/**
 * Creator: Dric 
 * Date: 02/03/2015
 * Time: 10:39
 */

namespace FileSystem;

use Logs\Alert;
use phpseclib\Net\SFTP;

/**
 * Classe de gestion de connexion à une ressource ftp ou sftp
 *
 *
 * @package FileSystem
 */
class Ftp {

	/**
	 * Type de functions utilisées pour la connexion sftp : `phpseclib` ou la librairie intégrée de php ssh_sftp `libssh` (incomplète en terme de fonctions)
	 * @var string
	 */
	protected $sshType = 'phpseclib';
	/**
	 * Nom ou adresse IP du serveur
	 * @var string
	 */
	protected $host;
	/**
	 * Identifiant utilisé pour la connexion
	 * @var string
	 */
	protected $username;
	/**
	 * Mot de passe de l'identifiant
	 * @var string
	 */
	protected $password;
	/**
	 * Type de connexion : `ftp` ou `sftp`
	 * @var string
	 */
	protected $connectionType;
	/**
	 * Port utilisé pour la connexion
	 * @var int
	 */
	protected $portNumber;
	/**
	 * @var bool|resource|SFTP
	 */
	protected $connection = false;

	/**
	 * Alertes détaillées pour chaque fichier envoyé/reçu
	 *
	 * Si actif, une alerte sera générée pour chaque fichier envoyé ou reçu. Si non, aucune alerte ne sera générée (utile pour des envois/téléchargements multiples).
	 *
	 * @var bool
	 */
	protected $verboseMode = true;

	/**
	 * Etablissement d'une connexion FTP ou SFTP
	 *
	 * @param string $host           Nom ou adresse IP du serveur distant
	 * @param string $username       Identifiant de connexion
	 * @param string $password       Mot de passe de l'identifiant
	 * @param string $connectionType Type de connexion : `ftp` ou `sftp`
	 * @param bool   $portNumber     Port utilisé (`21` par défaut pour `ftp`, `22` pour `sftp`)
	 * @param bool   $verboseMode    Si actif, une alerte sera générée pour chaque fichier envoyé ou reçu. Si non, aucune alerte ne sera générée (utile pour des envois/téléchargements multiples).
	 */
	public function __construct( $host, $username, $password, $connectionType = 'ftp', $portNumber = false, $verboseMode = true ){

		//add the webroot to the beginning of the $this->phpseclibPath (this is bespoke to my own configuration)

		//setting the classes vars
		$this->host         = $host;
		$this->username     = $username;
		$this->password     = $password;
		$this->connectionType = $connectionType;
		$this->verboseMode  = $verboseMode;

		//set the port number to defaults based on connection type if none passed
		if( $portNumber === false ){
			if( $connectionType == 'ftp' ){
				$portNumber = 21;
			} else {
				$portNumber = 22;
			}
		}
		$this->portNumber = $portNumber;

		//now set the server connection into this classes connection var
		$this->connect();
	}

	public function __destruct(){
		if ($this->connectionType == 'ftp'){
			ftp_close($this->connection);
		}
	}


	/**
	 * Etablit la connexion au serveur distant
	 *
	 * @return bool
	 */
	function connect(){
		switch( $this->connectionType )
		{
			case 'ftp':
				$connection = ftp_connect($this->host);
				if (!$connection){
					new Alert('error', 'Impossible de se connecter au serveur distant <code>'.$this->host.'</code>');
					return false;
				}
				$login = ftp_login($connection, $this->username, $this->password);
				if (!$login){
					new Alert('error', 'Les identifiants utilisés pour la connexion FTP sont incorrects');
					return false;
				}
				// enabling passive mode
				ftp_pasv( $connection, true );
				$this->connection = $connection;
				return true;

			case 'sftp':
				//decide which ssh type to use
				switch( $this->sshType ){
					case 'phpseclib':
						$connection = new SFTP($this->host, $this->portNumber);
						if (!$connection){
							new Alert('error', 'Impossible de se connecter au serveur distant <code>'.$this->host.'</code>');
							return false;
						}
						$login = $connection->login($this->username, $this->password);
						if (!$login){
							new Alert('error', 'Les identifiants utilisés pour la connexion FTP sont incorrects');
							return false;
						}
						break;

					case 'libssh2':
						$connection = ssh2_connect($this->host, $this->portNumber);
						if (!$connection){
							new Alert('error', 'Impossible de se connecter au serveur distant <code>'.$this->host.'</code>');
							return false;
						}
						$login = ssh2_auth_password($connection, $this->username, $this->password);
						if (!$login){
							new Alert('error', 'Les identifiants utilisés pour la connexion FTP sont incorrects');
							return false;
						}
						break;

					default:
						new Alert('error', 'No ssh method defined, please define one in: $ftp_sftp->sshType');
						return false;
				}
				$this->connection = $connection;
				return true;
		}
		return false;
	}

	/**
	 * Accéder aux erreurs de phpseclib
	 */
	public function getErrors(){
		if($this->connectionType == 'sftp' && $this->sshType == 'phpseclib'){
			return $this->connection->getErrors();
		}
		return null;
	}


	/**
	 * Vérifie que la connexion est bien établie
	 *
	 * @return bool
	 */
	public function connectionCheck(){
		if( $this->connection === false){
			new Alert('error', 'La connexion au serveur distant n\'est pas établie !');
			return false;
		}
		return true;
	}

	/**
	 * Transfère un fichier vers le serveur distant
	 *
	 * @param string $fileDest Nom et chemin du fichier de destination
	 * @param string $fileFrom Nom et chemin du fichier d'origine
	 *
	 * @return bool
	 */
	public function put($fileDest, $fileFrom){

		$put = false;
		//check the connection
		if ($this->connectionCheck()){
			switch( $this->connectionType )
			{
				case 'ftp':
					//ftp_put the file across
					$put = ftp_put( $this->connection, $fileDest, $fileFrom, FTP_BINARY);
					break;

				case 'sftp':
					//decide which ssh type to use
					switch( $this->sshType ){
						case 'phpseclib':
							$put = $this->connection->put( $fileDest, $fileFrom, SFTP::SOURCE_LOCAL_FILE );
							break;

						case 'libssh2':
							$put = ssh2_scp_send($this->connection, $fileDest, $fileFrom, 0755);
							break;
					}
					break;
			}
		}
		if ($this->verboseMode){
			if ($put){
				new Alert('success', 'Le fichier <code>'.$fileFrom.'</code> a été envoyé !');
			}else{
				new Alert('error', 'Le fichier <code>'.$fileFrom.'</code> n\'a pas pu être envoyé !');
			}
		}
		return $put;
	}

	/**
	 * Transfère un fichier depuis le serveur distant
	 *
	 * @param string $fileFrom Nom et chemin du fichier d'origine
	 * @param string $fileDest Nom et chemin du fichier de destination
	 *
	 * @return bool
	 */
	public function get($fileFrom, $fileDest){

		$get = false;
		//check the connection
		if ($this->connectionCheck()){
			switch( $this->connectionType )
			{
				case 'ftp':
					//ftp_put the file across
					$get = ftp_get( $this->connection, $fileDest, $fileFrom, FTP_BINARY);
					break;

				case 'sftp':
					//decide which ssh type to use
					switch( $this->sshType ){
						case 'phpseclib':
							$get = $this->connection->get( $fileFrom, $fileDest );
							break;

						case 'libssh2':
							$get = ssh2_scp_recv($this->connection, $fileFrom, $fileDest);
							break;
					}
					break;
			}
		}
		if ($this->verboseMode){
			if ($get){
				new Alert('success', 'Le fichier <code>'.$fileFrom.'</code> a été téléchargé !');
			}else{
				new Alert('error', 'Le fichier <code>'.$fileFrom.'</code> n\'a pas pu être téléchargé !');
			}
		}
		return $get;
	}

	/**
	 * Retourne le contenu du répertoire distant
	 *
	 * @param string $dirToList Répertoire à parcourir
	 *
	 * @return array|bool|Mixed
	 */
	public function dirList( $dirToList ){

		//check the connection
		if ($this->connectionCheck()){
			switch( $this->connectionType )	{
				case 'ftp':
					return ftp_nlist($this->connection, $dirToList);
				case 'sftp':
					//decide which ssh type to use
					switch( $this->sshType ){
						case 'phpseclib':
							return $this->connection->nlist( $dirToList );
						case 'libssh2':
							new Alert('debug', 'Sorry there is no support for nlist with libssh2, however this link has a possible answer: http://randomdrake.com/2012/02/08/listing-and-downloading-files-over-sftp-with-php-and-ssh2/');
							return false;
					}
			}
		}
		return false;
	}


	/**
	 * Retourne le timestamp d'un fichier distant
	 *
	 * @param string $pathToFile Chemin et nom du fichier distant
	 *
	 * @return bool|int
	 */
	public function remoteFilemtime( $pathToFile ){

		//check the connection
		if ($this->connectionCheck()){
			//run appropriate list
			switch( $this->connectionType )	{
				case 'ftp':
					return ftp_mdtm($this->connection, $pathToFile);

				case 'sftp':
					//decide which ssh type to use
					switch( $this->sshType ){
						case 'phpseclib':
							$statinfo = $this->connection->stat( $pathToFile );
							break;

						case 'libssh2':
							$statinfo = ssh2_sftp_stat($this->connection, $pathToFile);
							break;
					}
					return (isset($statinfo['mtime'])) ? $statinfo['mtime'] : false;
			}
		}
		return false;
	}

	/**
	 * Crée un répertoire sur le serveur distant
	 *
	 * @param string $dirToMake Répertoire à créer
	 *
	 * @return bool|string
	 */
	public function makeDir( $dirToMake ){
		//check the connection
		if ($this->connectionCheck()){
			//run appropriate list
			switch( $this->connectionType )
			{
				case 'ftp':
					return ftp_mkdir($this->connection, $dirToMake);

				case 'sftp':
					//decide which ssh type to use
					switch( $this->sshType ){
						case 'phpseclib':
							return $this->connection->mkdir( $dirToMake );
						case 'libssh2':
							return ssh2_sftp_mkdir($this->connection, $dirToMake, 0755);
					}
			}
		}
		return false;
	}

	/**
	 * Change le répertoire courant distant
	 *
	 * @param string $dirToMoveTo Nouveau répertoire distant
	 *
	 * @return bool
	 */
	public function changeDir( $dirToMoveTo ){
		//check the connection
		if ($this->connectionCheck()){
			//run appropriate list
			switch( $this->connectionType )
			{
				case 'ftp': return ftp_chdir($this->connection, $dirToMoveTo );
				case 'sftp':
					//decide which ssh type to use
					switch( $this->sshType ){
						case 'phpseclib':
							return $this->connection->chdir( $dirToMoveTo );
						case 'libssh2':
							new Alert('debug', 'Sorry this feature does exist yet for when using libssh2 with the ftp_sftp class');
							return false;
					}
			}
		}
		return false;
	}

	/**
	 * Renvoie le nom et le chemin du répertoire courant
	 *
	 * @return bool|string
	 */
	public function pwd(){

		//check the connection
		if ($this->connectionCheck()){
			//run appropriate list
			switch( $this->connectionType )
			{
				case 'ftp': return ftp_pwd($this->connection);
				case 'sftp':
					//decide which ssh type to use
					switch( $this->sshType ){
						case 'phpseclib':
							return $this->connection->pwd();
						case 'libssh2':
							new Alert('debug', 'Sorry this feature does exist yet for when using libssh2');
					}
			}
		}
		return false;
	}

	/**
	 * Supprime un fichier
	 *
	 * @param string $fileToDelete Fichier distant à supprimer
	 *
	 * @return bool
	 */
	public function deleteFile($fileToDelete) {
		$unlink = false;
		//check the connection
		if ($this->connectionCheck()) {
			//run appropriate list
			switch( $this->connectionType )	{
				case 'ftp': return ftp_delete($this->connection, $fileToDelete);
				case 'sftp':
					//decide which ssh type to use
					switch( $this->sshType ){
						case 'phpseclib':
							$unlink = $this->connection->delete( $fileToDelete );
							break;
						case 'libssh2':
							$unlink = ssh2_sftp_unlink($this->connection, $fileToDelete);
							break;
					}
					break;
			}
			if ($this->verboseMode){
				if ($unlink){
					new Alert('success', 'le fichier <code>'.$fileToDelete.'</code> a été correctement supprimé !');
				}else{
					new Alert('error', 'le fichier <code>'.$fileToDelete.'</code> n\'a pas pu être supprimé !');
				}
			}
		}
		return $unlink;
	}
}