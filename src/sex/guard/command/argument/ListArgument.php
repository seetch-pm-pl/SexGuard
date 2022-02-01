<?php

declare(strict_types=1);

namespace sex\guard\command\argument;

use pocketmine\player\Player;

class ListArgument extends Argument{

	public const NAME = 'list';

	public function execute(Player $sender, array $args) : bool{
		$main = $this->getPlugin();
		$list = $main->getRegionList($sender->getName());

		if(count($list) < 1){
			$sender->sendMessage($main->getValue('list_empty'));
			return true;
		}

		$name = [];

		foreach($list as $region){
			$name[] = $region->getRegionName();
		}

		$message = $main->getValue('list_success');
		$message = str_replace('{list}', implode(', ', $name), $message);

		$sender->sendMessage($message);
		return true;
	}
}