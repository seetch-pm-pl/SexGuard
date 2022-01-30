<?php

declare(strict_types=1);

namespace sex\guard\event\region;

use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use sex\guard\data\Region;
use sex\guard\event\RegionEvent;
use sex\guard\Manager;

class RegionFlagChangeEvent extends RegionEvent implements Cancellable{

	use CancellableTrait;

	public function __construct(Manager $main, Region $region, private string $flag, private bool $newValue){
		parent::__construct($main, $region);
	}

	public function getFlag() : string{
		return strtolower($this->flag);
	}

	public function getOldValue() : bool{
		return !$this->getNewValue();
	}

	public function getNewValue() : bool{
		return $this->newValue;
	}
}