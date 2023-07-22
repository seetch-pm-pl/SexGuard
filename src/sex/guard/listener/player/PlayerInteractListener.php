<?php

declare(strict_types=1);

namespace sex\guard\listener\player;

use pocketmine\block\FloorSign;
use pocketmine\block\tile\ItemFrame;
use pocketmine\block\VanillaBlocks;
use pocketmine\block\WallSign;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\scheduler\ClosureTask;
use pocketmine\world\Position;
use econ\api\EconAPI;

class PlayerInteractListener extends PlayerListener implements Listener{

	public function onEvent(PlayerInteractEvent $event) : void{
		$player = $event->getPlayer();
		$world = $player->getWorld();

		$clickedBlock = $event->getBlock();
		$clickedBlockPos = $clickedBlock->getPosition();

		$frameTile = $world->getTile($clickedBlockPos);

		if($frameTile instanceof ItemFrame){
			if($this->isFlagDenied($player, 'frame', $clickedBlockPos)){
				$event->cancel();
			}
		}

		if($event->getAction() !== PlayerInteractEvent::RIGHT_CLICK_BLOCK){
			return; // thx Yexeed.
		}

		if($event->isCancelled()){
			return;
		}

		$block = $event->getBlock();
		$nick = strtolower($player->getName());
		$api = $this->getPlugin();

		if($block instanceof FloorSign or $block instanceof WallSign){
			if(count($api->sign->getAll()) == 0 or $api->getValue('region-sell', 'config') === false){
				return;
			}

			foreach($api->sign->getAll() as $name => $data){
				$pos = new Vector3($data['pos'][0], $data['pos'][1], $data['pos'][2]);
				$lvl = $data['level'];

				if($clickedBlockPos->equals($pos) and $clickedBlockPos->getWorld()->getFolderName() == $lvl){
					if(isset($api->extension['economyapi'])){
						$economy = $api->extension['economyapi'];
						$money = $economy->myMoney($nick);
					}

					if(isset($api->extension['econ'])){
						$economy = EconAPI::getInstance();
						$money = $economy->get($nick);
					}

					if(!isset($economy)){
						return;
					}

					$region = $api->getRegion($clickedBlockPos);

					if(!isset($region)){
						return;
					}

					if($nick == $region->getOwner()){
						$api->sendWarning($player, $api->getValue('player_already_owner'));
						return;
					}

					$val = $api->getGroupValue($player);

					if(count($api->getRegionList($nick)) > $val['max-count']){
						$api->sendWarning($player, str_replace('{max_count}', (string) $val['max-count'], $api->getValue('rg_overcount')));
						return;
					}

					$price = $data['price'];

					if($money < $price){
						$api->sendWarning($player, str_replace('{price}', (string) $price, $api->getValue('player_have_not_money')));
						return;
					}

					if($economy instanceof EconAPI){
						$economy->deduct($nick, $price);
						$economy->add($region->getOwner(), $price);
					}else{
						$economy->reduceMoney($nick, $price);
						$economy->addMoney($region->getOwner(), $price);
					}


					$region->setOwner($nick);
					$clickedBlockPos->getWorld()->setBlock($pos, VanillaBlocks::AIR());

					$api->sign->remove($name);
					$api->sign->save();

					$api->sendWarning($player, str_replace('{region}', $region->getRegionName(), $api->getValue('player_buy_rg')));
					break;
				}
			}

			return;
		}

		$item = $event->getItem();

		if($item === VanillaItems::STICK()){
			$event->cancel();

			$region = $api->getRegion($clickedBlockPos);

			if(!isset($region)){
				$api->sendWarning($player, $api->getValue('rg_not_exist'));
				return;
			}

			$msg = str_replace('{region}', $region->getRegionName(), $api->getValue('rg_info'));
			$msg = str_replace('{owner}', $region->getOwner(), $msg);
			$msg = str_replace('{member}', implode(' ', $region->getMemberList()), $msg);

			$api->sendWarning($player, $msg);

			$pos1 = new Vector3($region->getMin("x"), $region->getMin("y"), $region->getMin("z"));
			$pos2 = new Vector3($region->getMax("x"), $region->getMax("y"), $region->getMax("z"));

			$this->getPlugin()->updateSelection($player, Position::fromObject($pos1, $player->getWorld()), Position::fromObject($pos2, $player->getWorld()));

			$this->getPlugin()->getScheduler()->scheduleDelayedTask(new ClosureTask(function () use ($player){
				$this->getPlugin()->clearSelection($player);
			}), 60);
			return;
		}

		if($item === VanillaItems::WOODEN_AXE()){
			$event->cancel();

			$region = $api->getRegion($clickedBlockPos);

			if($region !== null and !$player->hasPermission('sexguard.all')){
				if($region->getOwner() !== $nick){
					$api->sendWarning($player, $api->getValue('rg_override'));
					return;
				}
			}

			if(!isset($api->position[0][$nick])){
				$api->position[0][$nick] = $clickedBlockPos;

				$api->sendWarning($player, $api->getValue('pos_1_set'));
				return;
			}

			if(!isset($api->position[1][$nick])){
				if($api->position[0][$nick]->getWorld()->getFolderName() !== $clickedBlockPos->getWorld()->getFolderName()){
					unset($api->position[0][$nick]);
					$api->sendWarning($player, $api->getValue('pos_another_world'));
					return;
				}

				$val = $api->getGroupValue($player);
				$size = $api->calculateSize($api->position[0][$nick], $clickedBlockPos);

				if($size > $val['max-size'] and !$player->hasPermission('sexguard.all')){
					$msg = str_replace('{max_size}', (string) $val['max-size'], $api->getValue('rg_oversize'));

					$api->sendWarning($player, $msg);
					return;
				}

				$api->position[1][$nick] = $clickedBlockPos;

				$api->sendWarning($player, $api->getValue('pos_2_set'));

				$api->updateSelection($player, $api->position[0][$nick], $api->position[1][$nick]);
				return;
			}

			if(isset($api->position[0][$nick]) and isset($api->position[1][$nick])){
				$api->position[0][$nick] = $clickedBlockPos;

				$api->clearSelection($player);

				unset($api->position[1][$nick]);
				$api->sendWarning($player, $api->getValue('pos_1_set'));
				return;
			}
		}

		$flag = 'interact';

		if($block === VanillaBlocks::CHEST() ||
			$block === VanillaBlocks::TRAPPED_CHEST() ||
			$block === VanillaBlocks::SMOKER() ||
			$block === VanillaBlocks::BARREL() ||
			$block === VanillaBlocks::SHULKER_BOX() ||
			$block === VanillaBlocks::BLAST_FURNACE() ||
			$block === VanillaBlocks::DYED_SHULKER_BOX()
		){
			$flag = 'chest';
		}

		if($block === VanillaBlocks::ITEM_FRAME()){
			$flag = 'frame';
		}

		if($block == VanillaBlocks::GRASS()){
			$list = [
				VanillaItems::WOODEN_SHOVEL(),
				VanillaItems::STONE_SHOVEL(),
				VanillaItems::IRON_SHOVEL(),
				VanillaItems::GOLDEN_SHOVEL(),
				VanillaItems::DIAMOND_SHOVEL(),
				VanillaItems::NETHERITE_SHOVEL(),

				VanillaItems::WOODEN_HOE(),
				VanillaItems::STONE_HOE(),
				VanillaItems::IRON_HOE(),
				VanillaItems::GOLDEN_HOE(),
				VanillaItems::DIAMOND_HOE(),
				VanillaItems::NETHERITE_HOE()
			];

			if(in_array($item, $list)){
				/**
				 * @todo break?
				 */
				$flag = 'break';
			}
		}

		if($this->isFlagDenied($player, $flag, $clickedBlockPos)){
			$event->cancel();
		}
	}
}
