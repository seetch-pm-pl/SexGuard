<?php

declare(strict_types=1);

namespace sex\guard\utils;

use pocketmine\math\Vector3;

class ReferencedPosition extends Vector3{

	use ReferencedWorldHolder;

	public function __construct(Vector3 $pos, string $world){
		parent::__construct($pos->x, $pos->y, $pos->z);
		$this->world = $world;
	}
}