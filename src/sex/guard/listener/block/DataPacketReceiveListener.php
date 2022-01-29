<?php

declare(strict_types=1);

namespace sex\guard\listener\block;

use pocketmine\block\ItemFrame;
use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\ItemFrameDropItemPacket;

class DataPacketReceiveListener extends BlockListener implements Listener{

	public function onEvent(DataPacketReceiveEvent $event) : void{
		$pk = $event->getPacket();

		if($pk instanceof ItemFrameDropItemPacket){
			$player = $event->getOrigin()->getPlayer();
			$tile = $player->getPosition()->getWorld()->getTile($pk->blockPosition);

			if(!($tile instanceof ItemFrame)){
				return;
			}

			$block = $tile->getFramedItem()->getBlock();

			if($block->getPosition() === null){
				return;
			}

			if($this->isFlagDenied($block, 'frame', $player)){
				$event->cancel();
			}
		}
	}
}