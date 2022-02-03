<?php

declare(strict_types=1);

namespace sex\guard\listener\player;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerBucketFillEvent;

class PlayerBucketFillListener extends PlayerListener implements Listener{

	public function onEvent(PlayerBucketFillEvent $event) : void{
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