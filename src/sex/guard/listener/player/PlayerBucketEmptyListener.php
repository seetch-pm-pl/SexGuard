<?php

declare(strict_types=1);

namespace sex\guard\listener\player;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerBucketEmptyEvent;

class PlayerBucketEmptyListener extends PlayerListener implements Listener{

	public function onEvent(PlayerBucketEmptyEvent $event) : void{
		if($event->isCancelled()){
			return;
		}

		$player = $event->getPlayer();
		$clickedBlock = $event->getBlockClicked();
		$clickedBlockPos = $clickedBlock->getPosition();

		if($this->isFlagDenied($player, 'bucket', $clickedBlockPos)){
			$event->cancel();
		}
	}
}