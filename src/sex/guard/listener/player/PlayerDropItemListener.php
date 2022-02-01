<?php

declare(strict_types=1);

namespace sex\guard\listener\player;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDropItemEvent;

class PlayerDropItemListener extends PlayerListener implements Listener{

	public function onEvent(PlayerDropItemEvent $event) : void{
		if($event->isCancelled()){
			return;
		}

		$player = $event->getPlayer();

		if($this->isFlagDenied($player, 'drop')){
			$event->cancel();
		}
	}
}