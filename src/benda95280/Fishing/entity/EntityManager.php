<?php

declare(strict_types = 1);

namespace benda95280\Fishing\entity;


use benda95280\Fishing\entity\projectile\FishingHook;
use benda95280\Fishing\Fishing;
use pocketmine\entity\{EntityFactory, EntityDataHelper};
use pocketmine\data\bedrock\EntityLegacyIds;
use pocketmine\world\World;
use pocketmine\nbt\tag\CompoundTag;

class EntityManager {
	public static function init(): void{
		EntityFactory::getInstance()->register(FishingHook::class, function(World $world, CompoundTag $nbt) : FishingHook{
			return new FishingHook(EntityDataHelper::parseLocation($nbt, $world), null, $nbt);
		}, ['FishingHook', 'minecraft:fishing_hook'], EntityLegacyIds::FISHING_HOOK);
	}
}
