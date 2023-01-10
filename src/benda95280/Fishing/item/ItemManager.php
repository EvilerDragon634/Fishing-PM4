<?php


declare(strict_types = 1);

namespace benda95280\Fishing\item;

use benda95280\Fishing\Fishing;
use pocketmine\item\ItemFactory;
use pocketmine\inventory\CreativeInventory;

class ItemManager {
	public static function init(){
		ItemFactory::getInstance()->register(new FishingRod(), true);
		CreativeInventory::reset();
	}
}
