<?php
/**
 * Created by PhpStorm.
 * User: cedric.gallard
 * Date: 11/06/14
 * Time: 13:47
 */

namespace Modules\UsersTraces;


use FileSystem\Fs;
use StdClass;

class Server {
	protected $name = '';
	protected $folder = '';
	protected $logList = array();

	public function __construct($name, $folder){
		$this->name   = $name;
		$this->folder = $folder;
	}

	public function __get($prop){
		if (isset($this->$prop)) return $this->$prop;
		return false;
	}

	public function getLogs(){
		$share = new Fs($this->folder, $this->name, 'UsersTraces');
		$traces = $share->getRecursiveFilesInDir('', 'trc');
		foreach ($traces as $traceFile){
			$client = null;
			$meta = $share->getFileMeta($traceFile, 'dateModified');
			$content = $share->tailFile($traceFile, 5);
			foreach ($content as $line){
				if (strstr($line, '>>> Adresse IP') !== false){
					list(, $client) = explode(' : ', $line);
					break;
				}elseif(strstr($line, 'du poste') !== false and empty($client)){
					$arr = explode('  ', $line);
					list(, $client) = explode(' : ', $arr[1]);
					break;
				}
			}
			if ($client == null){
				$content = $share->readFile($traceFile);
				foreach ($content as $line){
					if (strstr($line, '>>> Adresse IP') !== false){
						list(,$client) = explode(' : ', $line);
						break;
					}
				}
			}
			$arr = explode('_', $traceFile, 3);
			$this->logList[] = new Log($this->name, $meta->dateModified, $arr[1], $arr[0], $client);
		}
		return $this->logList;
	}
} 