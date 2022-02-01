<?php

declare(strict_types=1);

namespace sex\guard\listener\player;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;

class PlayerChatListener extends PlayerListener implements Listener{

	public function onEvent(PlayerChatEvent $event) : void{
		$player = $event->getPlayer();

		if($this->isFlagDenied($player, 'chat')){
			$event->cancel();
		}
	}
}