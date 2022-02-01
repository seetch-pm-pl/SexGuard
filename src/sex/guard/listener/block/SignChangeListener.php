<?php

declare(strict_types=1);

namespace sex\guard\listener\block;

use pocketmine\block\utils\SignText;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\Listener;

class SignChangeListener extends BlockListener implements Listener{

	public function onEvent(SignChangeEvent $event) : void{
		if($event->isCancelled()){
			return;
		}

		$player = $event->getPlayer();
		$block = $event->getSign();

		if($this->isFlagDenied($block, 'place', $player)){
			$event->cancel();
			return;
		}

		$line = $event->getNewText()->getLines();

		$list = ['sell rg', 'rg sell', 'region sell', 'sell region'];

		if(!in_array($line[0], $list) or intval($line[1]) <= 0){
			return;
		}

		$api = $this->getPlugin();

		if($api->getValue('allow_sell', 'config') === false){
			return;
		}

		$region = $api->getRegion($block->getPosition());


		if(!isset($region)){
			return;
		}

		$rname = $region->getRegionName();

		if(strtolower($player->getName()) !== $region->getOwner() and !$player->hasPermission('sexguard.all')){
			$api->sendWarning($player, $api->getValue('player_not_owner'));
			return;
		}

		$sign = $api->sign->get($rname, 'жопа');

		if($sign !== 'жопа'){
			$pos = $sign['pos'];

			if($pos[0] !== $block->getPosition()->getX() or $pos[1] !== $block->getPosition()->getY() or $pos[2] !== $block->getPosition()->getZ()){
				$api->sendWarning($player, $api->getValue('sell_exist'));
				return;
			}
		}

		$price = $line[1];

		$lines = [];
		for($i = 0; $i < 4; $i++){
			$text = str_replace('{region}', $rname, $api->getValue('sell_text_' . ($i + 1)));
			$text = str_replace('{price}', $price, $text);

			$lines[$i] = $text;
		}

		$event->setNewText(new SignText($lines));

		$data = [
			'pos' => [$block->getPosition()->getX(), $block->getPosition()->getY(), $block->getPosition()->getZ()],
			'level' => $block->getPosition()->getWorld()->getFolderName(),
			'price' => $price
		];

		$api->sign->set($rname, $data);
		$api->sign->save();
	}
}