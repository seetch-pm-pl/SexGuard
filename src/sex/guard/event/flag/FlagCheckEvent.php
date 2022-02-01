<?php

declare(strict_types=1);

namespace sex\guard\event\flag;

use sex\guard\data\Region;
use sex\guard\event\RegionEvent;
use sex\guard\Manager;

class FlagCheckEvent extends RegionEvent{

	private bool $needCancel = false;

	public function __construct(Manager $main, Region $region, private string $flag){
		parent::__construct($main, $region);
	}

	public function getFlag() : string{
		return strtolower($this->flag);
	}

	public function isMainEventCancelled() : bool{
		return $this->needCancel;
	}

	public function setMainEventCancelled(bool $value = true){
		$this->needCancel = $value;
	}
}