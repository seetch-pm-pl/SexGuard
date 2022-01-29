<?php

declare(strict_types=1);

namespace sex\guard\listener\entity;

use pocketmine\event\entity\EntityCombustEvent;
use pocketmine\event\Listener;

class EntityCombustListener extends EntityListener implements Listener{

	public function onEvent(EntityCombustEvent $event) : void{
		if($event->isCancelled()){
			return;
		}

		$entity = $event->getEntity();

		if($this->isFlagDenied($entity, 'combust')){
			$event->cancel();
		}
	}
}