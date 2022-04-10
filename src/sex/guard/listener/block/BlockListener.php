<?php

declare(strict_types=1);

namespace sex\guard\listener\block;

use pocketmine\player\Player;
use pocketmine\world\Position;
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

	protected function isFlagDenied(Position $position, string $flag, Player $player = null) : bool{
		$api = $this->getPlugin();

		if(isset($player)){
			if($player->hasPermission('sexguard.noflag')){
				return false;
			}
		}

		$region = $api->getRegion($position);

		if(!isset($region)){
			if($api->getValue('safe-mode', 'config') === true){
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

		$event = new FlagCheckByBlockEvent($api, $region, $flag, $position, $player);
		$event->call();

		if($event->isCancelled()){
			return $event->isMainEventCancelled();
		}

		if(isset($player)){
			$val = $api->getGroupValue($player);

			if(in_array($flag, $val['ignored-flags'])){
				if(!in_array($region->getRegionName(), $val['ignored-regions'])){
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
				$event = new FlagCheckByPlayerEvent($api, $region, $flag, $player, $position);
				$event->call();

				if($event->isCancelled()){
					return $event->isMainEventCancelled();
				}

				if($flag == 'break'){
					$pos = $player->getPosition()->subtract($position->getX(), $position->getY(), $position->getZ());
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