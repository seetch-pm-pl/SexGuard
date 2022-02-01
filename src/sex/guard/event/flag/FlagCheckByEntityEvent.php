<?php

declare(strict_types=1);

namespace sex\guard\event\flag;

use pocketmine\entity\Entity;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use sex\guard\data\Region;
use sex\guard\Manager;

class FlagCheckByEntityEvent extends FlagCheckEvent implements Cancellable{

	use CancellableTrait;

	public function __construct(Manager $main, Region $region, string $flag, private Entity $entity, private ?Entity $target = null){
		parent::__construct($main, $region, $flag);
	}

	public function getEntity() : Entity{
		return $this->entity;
	}

	public function getTarget() : Entity{
		return $this->target;
	}
}