<?php

declare(strict_types=1);

namespace sex\guard\command\argument;

use pocketmine\player\Player;

class OwnerArgument extends Argument{

	public const NAME = 'owner';

	public function execute(Player $sender, array $args) : bool{
		$nick = strtolower($sender->getName());
		$main = $this->getPlugin();

		if(count($args) < 2){
			$sender->sendMessage($main->getValue('owner_help'));
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

		$owner = $args[1];

		if(!isset($owner)){
			$sender->sendMessage($main->getValue('owner_help'));
			return false;
		}

		$player = $main->getServer()->getPlayerExact($owner);

		if(!($player instanceof Player)){
			$sender->sendMessage($main->getValue('player_not_exist'));
			return false;
		}

		$val = $main->getGroupValue($player);

		if(count($main->getRegionList($owner)) > $val['max-count']){
			$sender->sendMessage(str_replace('{max_count}', $val['max-count'], $main->getValue('rg_overcount')));
			return false;
		}

		$region->setOwner($owner);
		$region->addMember($nick);

		$sender->sendMessage(str_replace(['{player}', '{region}'], [$owner, $args[0]], $main->getValue('owner_change')));
		$player->sendMessage(str_replace(['{player}', '{region}'], [$nick, $args[0]], $main->getValue('owner_got_region')));
		return true;
	}
}