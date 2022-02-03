<?php

declare(strict_types=1);

namespace sex\guard\command\argument;

use pocketmine\player\Player;

class InfoArgument extends Argument{

	public const NAME = 'info';

	public function execute(Player $sender, array $args) : bool{
		$main = $this->getPlugin();

		$region = $main->getRegion($sender->getPosition());

		if(!isset($region)){
			$sender->sendMessage($main->getValue('rg_not_exist'));
			return true;
		}

		$msg = str_replace('{region}', $region->getRegionName(), $main->getValue('rg_info'));
		$msg = str_replace('{owner}', $region->getOwner(), $msg);
		$msg = str_replace('{member}', implode(' ', $region->getMemberList()), $msg);


		$sender->sendMessage(implode("\n", explode("| ", $msg))); // Rewrite that shit.
		return true;
	}
}