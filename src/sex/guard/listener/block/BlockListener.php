<?php

declare(strict_types=1);

namespace sex\guard\listener\block;

use pocketmine\block\Block;
use pocketmine\player\Player;
use sex\guard\event\flag\FlagCheckByBlockEvent;
use sex\guard\event\flag\FlagCheckByPlayerEvent;
use sex\guard\event\flag\FlagIgnoreEvent;
use sex\guard\Manager;

class BlockListener{

	public function __construct(private Manager $plugin){
	}

	public function getPlugin() : Manager{
		return $this->plugin;
	}

	public function register() : void{
		$listeners = [
			SignChangeListener::class,
			BlockBreakListener::class,
			BlockPlaceListener::class,
			LeavesDecayListener::class
		];
		foreach($listeners as $listener){
			$this->getPlugin()->getServer()->getPluginManager()->registerEvents(new $listener($this->getPlugin()), $this->getPlugin());
		}
	}

	protected function isFlagDenied(Block $block, string $flag, Player $player = null) : bool{
		$api = $this->getPlugin();

		if(isset($player)){
			if($player->hasPermission('sexguard.noflag')){
				return false;
			}
		}

		$region = $api->getRegion($block->getPosition());

		if(!isset($region)){
			if($api->getValue('safe_mode', 'config') === true){
				if(isset($player)){
					if($player->hasPermission('sexguard.all')){
						return false;
					}

					$api->sendWarning($player, $api->getValue('warn_safe_mode'));
				}

				return true;
			}

			return false;
		}

		if($region->getFlagValue($flag)){
			return false;
		}

		$event = new FlagCheckByBlockEvent($api, $region, $flag, $block, $player);

		$event->call();

		if($event->isCancelled()){
			return $event->isMainEventCancelled();
		}

		if(isset($player)){
			$val = $api->getGroupValue($player);

			if(in_array($flag, $val['ignored_flag'])){
				if(!in_array($region->getRegionName(), $val['ignored_region'])){
					$event = new FlagIgnoreEvent($api, $region, $flag, $player);

					$event->call();

					if($event->isCancelled()){
						return $event->isMainEventCancelled();
					}

					return false;
				}
			}
		}

		if(!isset($player)){
			return true;
		}

		$nick = strtolower($player->getName());

		if($nick !== $region->getOwner()){
			if(!in_array($nick, $region->getMemberList())){
				$event = new FlagCheckByPlayerEvent($api, $region, $flag, $player, $block);

				$event->call();

				if($event->isCancelled()){
					return $event->isMainEventCancelled();
				}

				if($flag == 'break'){
					$pos = $player->getPosition()->subtract($block->getPosition()->getX(), $block->getPosition()->getY(), $block->getPosition()->getZ());
					$pos->y = abs($pos->y + 2);
					$pos = $pos->divide(8);

					$player->setMotion($pos);
				}

				$api->sendWarning($player, $api->getValue('warn_flag_' . $flag));
				return true;
			}
		}

		return false;
	}
}