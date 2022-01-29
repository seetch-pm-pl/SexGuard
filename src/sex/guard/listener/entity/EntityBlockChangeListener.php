<?php

declare(strict_types=1);

namespace sex\guard\listener\entity;

use pocketmine\event\entity\EntityBlockChangeEvent;
use pocketmine\event\Listener;

class EntityBlockChangeListener extends EntityListener implements Listener{

	public function onEvent(EntityBlockChangeEvent $event) : void{
		if($event->isCancelled()){
			return;
		}

		$entity = $event->getEntity();

		if($this->isFlagDenied($entity, 'change')){
			$event->cancel();
		}
	}
}