<?php

declare(strict_types=1);

namespace sex\guard\listener\player;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;

class PlayerQuitListener extends PlayerListener implements Listener{

	public function onEvent(PlayerQuitEvent $event) : void{
		$nick = strtolower($event->getPlayer()->getName());
		$api = $this->getPlugin();

		if(isset($api->position[0][$nick])){
			unset($api->position[0][$nick]);
		}

		if(isset($api->position[1][$nick])){
			unset($api->position[1][$nick]);
		}
	}
}