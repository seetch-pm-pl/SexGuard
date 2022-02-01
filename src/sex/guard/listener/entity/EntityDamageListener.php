<?php

declare(strict_types=1);

namespace sex\guard\listener\entity;

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\player\Player;

class EntityDamageListener extends EntityListener implements Listener{

	public function onEvent(EntityDamageEvent $event) : void{
		if($event->isCancelled()){
			return;
		}

		$entity = $event->getEntity();

		if($event instanceof EntityDamageByEntityEvent){
			$damager = $event->getDamager();
			$flag = ($entity instanceof Player and $damager instanceof Player) ? 'pvp' : 'mob';

			if($this->isFlagDenied($damager, $flag, $entity)){
				$event->cancel();
			}

			return;
		}

		if($this->isFlagDenied($entity, 'damage')){
			$event->cancel();
		}
	}
}