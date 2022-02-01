<?php

declare(strict_types=1);

namespace sex\guard\listener\player;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerBedEnterEvent;

class PlayerBedEnterListener extends PlayerListener implements Listener{

	public function onEvent(PlayerBedEnterEvent $event) : void{
		if($event->isCancelled()){
			return;
		}

		$player = $event->getPlayer();

		if($this->isFlagDenied($player, 'sleep')){
			$event->cancel();
		}
	}
}