<?php

declare(strict_types=1);

namespace sex\guard\command\argument;

use pocketmine\player\Player;

class RemoveArgument extends Argument{

	public const NAME = 'remove';

	public function execute(Player $sender, array $args) : bool{
		$nick = strtolower($sender->getName());
		$main = $this->getPlugin();

		if(count($args) < 1){
			$sender->sendMessage($main->getValue('remove_help'));
			return false;
		}

		$region = $main->getRegionByName($args[0]);

		if(!isset($region)){
			$sender->sendMessage($main->getValue('rg_not_exist'));
			return false;
		}

		if($region->getOwner() !== $nick and !$sender->hasPermission('sexguard.all')){
			$sender->sendMessage($main->getValue('player_not_owner'));
			return false;
		}

		$main->removeRegion($region->getRegionName());
		$sender->sendMessage($main->getValue('rg_remove'));
		return true;
	}
}
