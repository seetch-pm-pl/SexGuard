<?php

declare(strict_types=1);

namespace sex\guard\command\argument;

use pocketmine\player\Player;
use pocketmine\world\Position;

class PositionArgument extends Argument{

	public const NAME = 'pos';

	public function execute(Player $sender, array $args) : bool{
		$nick = strtolower($sender->getName());
		$main = $this->getPlugin();

		if(count($args) < 1){
			$sender->sendMessage($main->getValue('pos_help'));
			return false;
		}

		$pos = new Position(
			$sender->getPosition()->getFloorX(),
			$sender->getPosition()->getFloorY(),
			$sender->getPosition()->getFloorZ(),
			$sender->getWorld()
		);

		$region = $main->getRegion($pos);

		if($region !== null and !$sender->hasPermission('sexguard.all')){
			if($region->getOwner() !== $nick){
				$sender->sendMessage($main->getValue('rg_override'));
				return false;
			}
		}

		if($args[0] == '1'){
			if(isset($main->position[1][$nick])){
				unset($main->position[1][$nick]);
			}

			$main->position[0][$nick] = $pos;

			$sender->sendMessage($main->getValue('pos_1_set'));
			return true;
		}elseif($args[0] == '2'){
			if(!isset($main->position[0][$nick])){
				$sender->sendMessage($main->getValue('pos_help'));
				return false;
			}

			if($main->position[0][$nick]->getWorld()->getFolderName() !== $sender->getWorld()->getFolderName()){
				unset($main->position[0][$nick]);
				$sender->sendMessage($main->getValue('pos_another_world'));
				return false;
			}

			$val = $main->getGroupValue($sender);
			$size = $main->calculateSize($main->position[0][$nick], $pos);

			if($size > $val['max-size'] and !$sender->hasPermission('sexguard.all')){
				$sender->sendMessage(str_replace('{max_size}', $val['max-size'], $main->getValue('rg_oversize')));
				return false;
			}

			$main->position[1][$nick] = $pos;

			$main->updateSelection($sender, $main->position[0][$nick], $main->position[1][$nick]);

			$sender->sendMessage($main->getValue('pos_2_set'));
			return true;
		}else{

			$sender->sendMessage($main->getValue('pos_help'));
			return false;
		}
	}
}