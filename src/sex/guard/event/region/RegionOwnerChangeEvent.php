<?php

declare(strict_types=1);

namespace sex\guard\event\region;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use sex\guard\data\Region;
use sex\guard\event\RegionEvent;
use sex\guard\Manager;

class RegionOwnerChangeEvent extends RegionEvent implements Cancellable{

	use CancellableTrait;

	public function __construct(Manager $main, Region $region, private string $oldOwner, private string $newOwner){
		parent::__construct($main, $region);
	}

	public function getOldOwner() : string{
		return $this->oldOwner;
	}

	public function getNewOwner() : string{
		return $this->newOwner;
	}
}