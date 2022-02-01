<?php

declare(strict_types=1);

namespace sex\guard\command\argument;

use pocketmine\item\VanillaItems;
use pocketmine\player\Player;

class WandArgument extends Argument{

	public const NAME = 'wand';

	public function execute(Player $sender, array $args) : bool{
		$main = $this->getPlugin();
		$wand = VanillaItems::WOODEN_AXE();

		if(!$sender->getInventory()->canAddItem($wand)){
			$sender->sendMessage($main->getValue('inventory_oversize'));
			return false;
		}

		$sender->getInventory()->addItem($wand);
		$sender->sendMessage($main->getValue('got_wand'));
		return true;
	}
}