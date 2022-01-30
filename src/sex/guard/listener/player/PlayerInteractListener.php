<?php

declare(strict_types=1);

namespace sex\guard\listener\player;

use pocketmine\block\BlockLegacyIds;
use pocketmine\block\VanillaBlocks;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\ItemIds;
use pocketmine\math\Vector3;

class PlayerInteractListener extends PlayerListener implements Listener{

	public function onEvent(PlayerInteractEvent $event) : void{
		if($event->getAction() !== PlayerInteractEvent::RIGHT_CLICK_BLOCK){
			return; // thx Yexeed.
		}

		if($event->isCancelled()){
			return;
		}

		$player = $event->getPlayer();
		$block = $event->getBlock();
		$nick = strtolower($player->getName());
		$api = $this->getPlugin();

		if($block->getId() == BlockLegacyIds::SIGN_POST or $block->getId() == BlockLegacyIds::WALL_SIGN){
			if(count($api->sign->getAll()) == 0 or $api->getValue('allow_sell', 'config') === false){
				return;
			}

			foreach($api->sign->getAll() as $name => $data){
				$pos = new Vector3($data['pos'][0], $data['pos'][1], $data['pos'][2]);
				$lvl = $data['level'];

				if($block->getPosition()->equals($pos) and $block->getPosition()->getWorld()->getFolderName() == $lvl){
					if(isset($api->extension['economyapi'])){
						$economy = $api->extension['economyapi'];
						$money = $economy->myMoney($nick);
					}

					if(isset($api->extension['universalmoney'])){
						$economy = $api->extension['universalmoney'];
						$money = $economy->getMoney($nick);
					}

					if(!isset($economy)){
						return;
					}

					$region = $api->getRegion($block->getPosition());

					if(!isset($region)){
						return;
					}

					if($nick == $region->getOwner()){
						$api->sendWarning($player, $api->getValue('player_already_owner'));
						return;
					}

					$val = $api->getGroupValue($player);

					if(count($api->getRegionList($nick)) > $val['max_count']){
						$api->sendWarning($player, str_replace('{max_count}', (array) $val['max_count'], $api->getValue('rg_overcount')));
						return;
					}

					$price = $data['price'];

					if($money < $price){
						$api->sendWarning($player, str_replace('{price}', $price, $api->getValue('player_have_not_money')));
						return;
					}

					$economy->reduceMoney($nick, $price);
					$economy->addMoney($region->getOwner(), $price);

					$region->setOwner($nick);
					$block->getPosition()->getWorld()->setBlock($pos, VanillaBlocks::AIR());

					$api->sign->remove($name);
					$api->sign->save();

					$api->sendWarning($player, str_replace('{region}', $region->getRegionName(), $api->getValue('player_buy_rg')));
					break;
				}
			}

			return;
		}

		$item = $event->getItem();

		if($item->getId() == ItemIds::STICK){
			$event->cancel();

			$region = $api->getRegion($block->getPosition());

			if(!isset($region)){
				$api->sendWarning($player, $api->getValue('rg_not_exist'));
				return;
			}

			$msg = str_replace('{region}', $region->getRegionName(), $api->getValue('rg_info'));
			$msg = str_replace('{owner}', $region->getOwner(), $msg);
			$msg = str_replace('{member}', implode(' ', $region->getMemberList()), $msg);

			$api->sendWarning($player, $msg);
			return;
		}

		if($item->getId() == ItemIds::WOODEN_AXE){
			$event->cancel();

			$region = $api->getRegion($block->getPosition());

			if($region !== null and !$player->hasPermission('sexguard.all')){
				if($region->getOwner() !== $nick){
					$api->sendWarning($player, $api->getValue('rg_override'));
					return;
				}
			}

			if(!isset($api->position[0][$nick])){
				$api->position[0][$nick] = $block->getPosition();

				$api->sendWarning($player, $api->getValue('pos_1_set'));
				return;
			}

			if(!isset($api->position[1][$nick])){
				if($api->position[0][$nick]->getWorld()->getFolderName() !== $block->getPosition()->getWorld()->getFolderName()){
					unset($api->position[0][$nick]);
					$api->sendWarning($player, $api->getValue('pos_another_world'));
					return;
				}

				$val = $api->getGroupValue($player);
				$size = $api->calculateSize($api->position[0][$nick], $block->getPosition());

				if($size > $val['max_size'] and !$player->hasPermission('sexguard.all')){
					$msg = str_replace('{max_size}', $val['max_size'], $api->getValue('rg_oversize'));

					$api->sendWarning($player, $msg);
					return;
				}

				$api->position[1][$nick] = $block->getPosition();

				$api->sendWarning($player, $api->getValue('pos_2_set'));
				return;
			}

			if(isset($api->position[0][$nick]) and isset($api->position[1][$nick])){
				$api->position[0][$nick] = $block->getPosition();

				unset($api->position[1][$nick]);
				$api->sendWarning($player, $api->getValue('pos_1_set'));
				return;
			}
		}

		$flag = 'interact';

		if($block->getId() == BlockLegacyIds::CHEST){
			$flag = 'chest';
		}

		if($block->getId() == BlockLegacyIds::ITEM_FRAME_BLOCK){
			$flag = 'frame';
		}

		if($block->getId() == BlockLegacyIds::GRASS){
			$list = [
				ItemIds::WOODEN_SHOVEL,
				ItemIds::STONE_SHOVEL,
				ItemIds::IRON_SHOVEL,
				ItemIds::GOLD_SHOVEL,
				ItemIds::DIAMOND_SHOVEL,

				ItemIds::WOODEN_HOE,
				ItemIds::STONE_HOE,
				ItemIds::IRON_HOE,
				ItemIds::GOLD_HOE,
				ItemIds::DIAMOND_HOE
			];

			if(in_array($item->getId(), $list)){
				/**
				 * @todo break?
				 */
				$flag = 'break';
			}
		}

		if($this->isFlagDenied($player, $flag, $block)){
			$event->cancel();
		}
	}
}