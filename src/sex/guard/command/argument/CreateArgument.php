<?php

declare(strict_types=1);

namespace sex\guard\command\argument;

use econ\api\EconAPI;
use pocketmine\player\Player;
use pocketmine\world\Position;
use sex\guard\utils\HighlightingManager;

class CreateArgument extends Argument{

	public const NAME = 'create';

	public function execute(Player $sender, array $args) : bool{
		$nick = strtolower($sender->getName());
		$main = $this->getPlugin();

		if(count($args) < 1){
			$sender->sendMessage($main->getValue('create_help'));
			return false;
		}

		if(!isset($main->position[0][$nick]) or !isset($main->position[1][$nick])){
			$sender->sendMessage($main->getValue('pos_help'));
			return false;
		}

		$name = $args[0];

		if(strlen($name) < 4){
			$sender->sendMessage($main->getValue('short_name'));
			return false;
		}

		if(strlen($name) > 12){
			$sender->sendMessage($main->getValue('long_name'));
			return false;
		}

		if(preg_match('#[^\s\da-z]#is', $name)){
			$sender->sendMessage($main->getValue('bad_name'));
			return false;
		}

		if($main->getRegionByName($name) !== null){
			$sender->sendMessage($main->getValue('rg_exist'));
			return false;
		}

		$val = $main->getGroupValue($sender);

		if(count($main->getRegionList($nick)) >= $val['max-count']){
			if(!$sender->hasPermission('sexguard.all')){
				$sender->sendMessage(str_replace('{max_count}', (string) $val['max-count'], $main->getValue('rg_overcount')));
				return false;
			}
		}

		$pos1 = $main->position[0][$nick];
		$pos2 = $main->position[1][$nick];

		if($main->calculateSize($pos1, $pos2) > $val['max-size'] and !$sender->hasPermission('sexguard.all')){
			$sender->sendMessage(str_replace('{max_size}', (string) $val['max-size'], $main->getValue('rg_oversize')));
			return false;
		}

		$x = [min($pos1->getX(), $pos2->getX()), max($pos1->getX(), $pos2->getX())];
		$y = [min($pos1->getY(), $pos2->getY()), max($pos1->getY(), $pos2->getY())];
		$z = [min($pos1->getZ(), $pos2->getZ()), max($pos1->getZ(), $pos2->getZ())];

		if($main->getValue('full-height', 'config') === true){
			$y = [0, 256];
		}

		$min = new Position($x[0], $y[0], $z[0], $pos1->getWorld());
		$max = new Position($x[1], $y[1], $z[1], $pos2->getWorld());

		$override = $main->getOverride($min, $max);

		if(count($override) > 0 and !$sender->hasPermission('sexguard.all')){
			foreach($override as $rg){
				if($rg->getOwner() !== $nick){
					$sender->sendMessage($main->getValue('rg_override'));
					return false;
				}
			}
		}

		if($main->getValue('region-create-paid', 'config') === true){
			if(isset($main->extension['economyapi'])){
				$economy = $main->extension['economyapi'];
				$money = $economy->myMoney($nick);
			}

			if(isset($main->extension['econ'])){
				$economy = EconAPI::getInstance();
				$money = $economy->get($nick);
			}

			if(isset($economy)){
				if(!$sender->hasPermission('sexguard.all')){
					$price = $main->getValue('region-create-price', 'config');

					if($money >= $price){
						if($economy instanceof EconAPI){
							$economy->deduct($nick, $price);
						}else{
							$economy->reduceMoney($nick, $price);
						}
					}else{
						$sender->sendMessage(str_replace('{price}', $price, $main->getValue('player_have_not_money')));
						return false;
					}
				}
			}
		}

		$main->clearSelection($sender);
		$main->createRegion($nick, $name, $min, $max);
		$sender->sendMessage(str_replace('{region}', $name, $main->getValue('rg_create')));
		return true;
	}
}