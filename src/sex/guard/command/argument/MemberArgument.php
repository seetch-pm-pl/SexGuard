<?php

declare(strict_types=1);

namespace sex\guard\command\argument;

use pocketmine\player\Player;

class MemberArgument extends Argument{

	public const NAME = 'member';

	public function execute(Player $sender, array $args) : bool{
		$nick = strtolower($sender->getName());
		$main = $this->getPlugin();

		if(count($args) < 3 or !in_array($args[0], ['add', 'remove'])){
			$sender->sendMessage($main->getValue('member_help'));
			return false;
		}

		$region = $main->getRegionByName($args[1]);

		if(!isset($region)){
			$sender->sendMessage($main->getValue('rg_not_exist'));
			return false;
		}

		if($region->getOwner() !== $nick and !$sender->hasPermission('sexguard.all')){
			$sender->sendMessage($main->getValue('player_not_owner'));
			return false;
		}

		$member = $args[2];

		if($args[0] == 'add'){
			if(in_array($member, $region->getMemberList())){
				$sender->sendMessage($main->getValue('player_already_member'));
				return false;
			}

			$region->addMember($member);
			$sender->sendMessage(str_replace('{player}', $member, $main->getValue('member_add')));
		}else{
			if(!in_array($member, $region->getMemberList())){
				$sender->sendMessage($main->getValue('player_not_exist'));
				return false;
			}

			$region->removeMember($member);
			$sender->sendMessage(str_replace('{player}', $member, $main->getValue('member_remove')));
		}

		return true;
	}
}