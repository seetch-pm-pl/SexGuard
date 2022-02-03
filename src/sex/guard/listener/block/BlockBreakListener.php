<?php

declare(strict_types=1);

namespace sex\guard\listener\block;

use pocketmine\block\BlockLegacyIds;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\math\Vector3;

class BlockBreakListener extends BlockListener implements Listener{

	public function onEvent(BlockBreakEvent $event) : void{
		if($event->isCancelled()){
			return;
		}

		$player = $event->getPlayer();
		$block = $event->getBlock();
		$blockPos = $block->getPosition();

		if($this->isFlagDenied($blockPos, 'break', $player)){
			$event->cancel();
			return;
		}

		if($block->getId() == BlockLegacyIds::CHEST and $this->isFlagDenied($blockPos, 'chest', $player)){
			$event->cancel();
			return;
		}

		if($block->getId() !== BlockLegacyIds::SIGN_POST and $block->getId() !== BlockLegacyIds::WALL_SIGN){
			return;
		}

		$api = $this->getPlugin();

		if(count($api->sign->getAll()) > 0 or $api->getValue('allow_sell', 'config') === true){
			foreach($api->sign->getAll() as $name => $data){
				$pos = new Vector3($data['pos'][0], $data['pos'][1], $data['pos'][2]);
				$lvl = $data['level'];

				if($block->getPosition()->equals($pos) and $block->getPosition()->getWorld()->getFolderName() == $lvl){
					$api->sign->remove($name);
					$api->sign->save();
				}
			}
		}
	}
}