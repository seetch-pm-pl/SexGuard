<?php

declare(strict_types=1);

namespace sex\guard\listener\player;

use pocketmine\block\Block;
use pocketmine\player\Player;
use sex\guard\event\flag\FlagCheckByPlayerEvent;
use sex\guard\event\flag\FlagIgnoreEvent;
use sex\guard\Manager;

class PlayerListener{

	public function __construct(private Manager $plugin){
	}

	public function getPlugin() : Manager{
		return $this->plugin;
	}

	public function register() : void{
		$listeners = [
			PlayerQuitListener::class,
			PlayerChatListener::class,
			PlayerInteractListener::class,
			PlayerDropItemListener::class,
			PlayerBedEnterListener::class,
			PlayerBucketFillListener::class,
			PlayerBucketEmptyListener::class
		];
		foreach($listeners as $listener){
			$this->getPlugin()->getServer()->getPluginManager()->registerEvents(new $listener($this->getPlugin()), $this->getPlugin());
		}
	}

	protected function isFlagDenied(Player $player, string $flag, Block $block = null) : bool{
		if($player->hasPermission('sexguard.noflag')){
			return false;
		}

		$api = $this->getPlugin();

		$region = $api->getRegion($player->getPosition() ?? $block->getPosition());

		if(!isset($region)){
			return false;
		}

		if($region->getFlagValue($flag)){
			return false;
		}

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

		$nick = strtolower($player->getName());

		if($nick !== $region->getOwner()){
			if(!in_array($nick, $region->getMemberList())){
				$event = new FlagCheckByPlayerEvent($api, $region, $flag, $player, $block);

				$event->call();

				if($event->isCancelled()){
					return $event->isMainEventCancelled();
				}

				$api->sendWarning($player, $api->getValue('warn_flag_' . $flag));
				return true;
			}
		}

		return false;
	}
}