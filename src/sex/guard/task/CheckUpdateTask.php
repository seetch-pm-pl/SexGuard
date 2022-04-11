<?php

declare(strict_types=1);

namespace sex\guard\task;

use Exception;
use pocketmine\scheduler\AsyncTask;
use pocketmine\utils\VersionString;
use sex\guard\Manager;

class CheckUpdateTask extends AsyncTask{

	public function onRun() : void{
		$curl = curl_init();
		curl_setopt_array($curl, [
			CURLOPT_URL => "https://api.github.com/repos/seetch-pm-pl/SexGuard/releases",
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_USERAGENT => "php_" . PHP_VERSION,
			CURLOPT_SSL_VERIFYPEER => false
		]);
		$json = curl_exec($curl);
		$errorNo = curl_errno($curl);
		if($errorNo){
			$error = curl_error($curl);
			throw new Exception($error);
		}
		curl_close($curl);
		$data = json_decode($json, true);
		$this->setResult($data);
	}

	public function onCompletion() : void{
		$plugin = Manager::getInstance();
		if($plugin->isEnabled()){
			$data = $this->getResult();
			var_dump($data[0]["name"]);
			if(isset($data[0])){
				$ver = new VersionString(explode(" ", $data[0]["name"])[1]);
				$plugin->compareVersion(true, $ver, $data[0]["html_url"]);
			}else{
				$plugin->compareVersion(false);
			}
		}
	}
}