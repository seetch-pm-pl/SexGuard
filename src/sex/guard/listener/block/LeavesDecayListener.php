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

		if($this->isFlagDenied($block, 'decay')){
			$event->cancel();
		}
	}
}