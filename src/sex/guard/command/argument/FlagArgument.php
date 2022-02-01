<?php

declare(strict_types=1);

namespace sex\guard\command\argument;

use pocketmine\player\Player;

class FlagArgument extends Argument{

	public const NAME = 'flag';

	public function execute(Player $sender, array $args) : bool{
		$nick = strtolower($sender->getName());
		$main = $this->getPlugin();
		$list = $main->getAllowedFlag();

		if(count($args) < 2){
			$sender->sendMessage(str_replace('{flag_list}', implode(' ', $list), $main->getValue('flag_help')));
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

		$flag = $args[1];

		if(!in_array($flag, $list)){
			$sender->sendMessage($main->getValue('flag_not_exist'));
			return false;
		}

		if($region->getFlagValue($flag)){
			$region->setFlag($flag, false);
			$sender->sendMessage(str_replace('{flag}', $flag, $main->getValue('flag_off')));
		}else{
			$region->setFlag($flag, true);
			$sender->sendMessage(str_replace('{flag}', $flag, $main->getValue('flag_on')));
		}

		return true;
	}
}