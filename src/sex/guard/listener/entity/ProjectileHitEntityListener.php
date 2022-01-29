<?php

declare(strict_types=1);

namespace sex\guard\listener\entity;

use pocketmine\entity\projectile\Arrow;
use pocketmine\event\entity\ProjectileHitEntityEvent;
use pocketmine\event\Listener;
use pocketmine\player\Player;

class ProjectileHitEntityListener extends EntityListener implements Listener{

	public function onEvent(ProjectileHitEntityEvent $event) : void{
		$projectile = $event->getEntity();

		if(!($projectile instanceof Arrow)){
			return;
		}

		$entity = $event->getEntityHit();
		$damager = $projectile->getOwningEntity() ?? $projectile;

		$flag = ($entity instanceof Player and $damager instanceof Player) ? 'pvp' : 'mob';

		if($this->isFlagDenied($damager, $flag, $entity)){
			$event->getEntity()->setPunchKnockback(0.00);
		}
	}
}