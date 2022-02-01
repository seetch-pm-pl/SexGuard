<?php

declare(strict_types=1);

namespace sex\guard\listener\entity;

use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\event\Listener;

class EntityExplodeListener extends EntityListener implements Listener{

	public function onEvent(EntityExplodeEvent $event) : void{
		if($event->isCancelled()){
			return;
		}

		$entity = $event->getEntity();

		if($this->isFlagDenied($entity, 'explode')){
			$event->setBlockList([]);
		}
	}
}