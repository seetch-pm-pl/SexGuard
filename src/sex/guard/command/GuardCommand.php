<?php

declare(strict_types=1);

namespace sex\guard\command;

use core\utils\TextUtils;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use sex\guard\command\argument\Argument;
use sex\guard\command\argument\CreateArgument;
use sex\guard\command\argument\FlagArgument;
use sex\guard\command\argument\InfoArgument;
use sex\guard\command\argument\ListArgument;
use sex\guard\command\argument\MemberArgument;
use sex\guard\command\argument\OwnerArgument;
use sex\guard\command\argument\PositionArgument;
use sex\guard\command\argument\RemoveArgument;
use sex\guard\command\argument\WandArgument;
use sex\guard\Manager;

class GuardCommand extends Command{

	public const NAME = 'rg';
	public const DESCRIPTION = 'Показывает помощь или список команд управления регионами';
	public const PERMISSION = 'sexguard.command.rg';

	/** @var Argument[] */
	private array $argumentList;

	public function __construct(private Manager $plugin){
		parent::__construct(self::NAME, self::DESCRIPTION);

		$this->argumentList = [
			new PositionArgument($plugin),
			new CreateArgument($plugin),
			new MemberArgument($plugin),
			new RemoveArgument($plugin),
			new OwnerArgument($plugin),
			new FlagArgument($plugin),
			new ListArgument($plugin),
			new WandArgument($plugin),
			new InfoArgument($plugin)
		];

		$this->setPermission(self::PERMISSION);
	}

	private function getArgument(string $name) : ?Argument{
		$name = strtolower($name);

		foreach($this->argumentList as $argument){
			if($argument->getName() !== $name){
				continue;
			}

			return $argument;
		}

		return null;
	}

	public function execute(CommandSender $sender, string $label, array $args) : bool{
		$main = $this->plugin;

		if(!($sender instanceof Player)){
			$sender->sendMessage($main->getValue('no_console'));
			return false;
		}

		if(!$this->testPermissionSilent($sender)){
			$sender->sendMessage($main->getValue('no_permission'));
			return false;
		}

		if(count($args) < 1){
			$sender->sendMessage($main->getValue('rg_help'));
			return false;
		}

		$argument = $this->getArgument(array_shift($args));

		if(!isset($argument)){
			$sender->sendMessage($main->getValue('no_argument'));
			return false;
		}

		return $argument->execute($sender, array_map('strtolower', $args));
	}
}