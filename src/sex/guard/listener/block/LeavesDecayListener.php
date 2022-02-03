<?php

declare(strict_types=1);

namespace sex\guard\listener\block;

use pocketmine\event\block\LeavesDecayEvent;
use pocketmine\event\Listener;

class LeavesDecayListener extends BlockListener implements Listener{

	public function onEvent(LeavesDecayEvent $event) : void{
		if($event->isCancelled()){
			return;
		}

		$block = $event->getBlock();
		$blockPos = $block->getPosition();

		if($this->isFlagDenied($blockPos, 'decay')){
			$event->cancel();
		}
	}
}