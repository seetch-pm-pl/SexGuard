<?php

declare(strict_types=1);

namespace sex\guard\listener\entity;

use pocketmine\entity\Entity;
use pocketmine\player\Player;
use sex\guard\event\flag\FlagCheckByEntityEvent;
use sex\guard\event\flag\FlagIgnoreEvent;
use sex\guard\Manager;

class EntityListener{

	public function __construct(private Manager $plugin){
	}

	public function getPlugin() : Manager{
		return $this->plugin;
	}

	public function register() : void{
		$listeners = [
			EntityDamageListener::class,
			EntityCombustListener::class,
			EntityExplodeListener::class,
			EntityTeleportListener::class,
			EntityBlockChangeListener::class,
			ProjectileHitEntityListener::class
		];
		foreach($listeners as $listener){
			$this->getPlugin()->getServer()->getPluginManager()->registerEvents(new $listener($this->getPlugin()), $this->getPlugin());
		}
	}

	protected function isFlagDenied(Entity $entity, string $flag, Entity $target = null) : bool{
		$api = $this->getPlugin();
		$result = false;

		if(isset($target)){
			$region = $api->getRegion($target->getPosition());

			if(isset($region) and !$region->getFlagValue($flag)){
				$result = true;
			}
		}

		$region = $api->getRegion($entity->getPosition());

		if(!isset($region)){
			return $result;
		}

		if(($entity instanceof Player)){
			$val = $api->getGroupValue($entity);

			if(in_array($flag, $val['ignored_flag'])){
				if(!in_array($region->getRegionName(), $val['ignored_region'])){
					$event = new FlagIgnoreEvent($api, $region, $flag, $entity);

					$event->call();

					if($event->isCancelled()){
						return $event->isMainEventCancelled();
					}

					return false;
				}
			}
		}

		if(!$region->getFlagValue($flag)){
			$event = new FlagCheckByEntityEvent($api, $region, $flag, $entity, $target);

			$event->call();

			if($event->isCancelled()){
				return $event->isMainEventCancelled();
			}

			if(($entity instanceof Player)){
				$api->sendWarning($entity, $api->getValue('warn_flag_' . $flag));
			}

			return true;
		}

		return $result;
	}
}