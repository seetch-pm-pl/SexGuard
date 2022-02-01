<?php

declare(strict_types=1);

namespace sex\guard\listener\block;

use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\Listener;

class BlockPlaceListener extends BlockListener implements Listener{

	public function onEvent(BlockPlaceEvent $event) : void{
		if($event->isCancelled()){
			return;
		}

		$player = $event->getPlayer();
		$block = $event->getBlock();

		if($this->isFlagDenied($block, 'place', $player)){
			$event->cancel();
		}
	}
}