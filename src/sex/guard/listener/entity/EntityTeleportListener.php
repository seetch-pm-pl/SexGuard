<?php

declare(strict_types=1);

namespace sex\guard\listener\entity;

use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;

class EntityTeleportListener extends EntityListener implements Listener{

	public function onEvent(EntityTeleportEvent $event) : void{
		if($event->isCancelled()){
			return;
		}

		$entity = $event->getEntity();

		if($this->isFlagDenied($entity, 'teleport')){
			$event->cancel();
		}
	}
}