<?php


declare(strict_types = 1);

namespace benda95280\Fishing\item;

use benda95280\Fishing\entity\projectile\FishingHook;
use benda95280\Fishing\Fishing;
use benda95280\Fishing\Session;
use benda95280\Fishing\utils\FishingLevel;
use benda95280\Fishing\utils\FishingLootTable;
use pocketmine\block\BlockLegacyIds as BlockIds;
use pocketmine\entity\Entity;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\item\Durable;
use pocketmine\item\{Item, ItemFactory, ItemIds, ItemUseResult, ItemIdentifier};
use pocketmine\data\bedrock\EnchantmentIds;
use pocketmine\world\World;
use pocketmine\world\sound\LaunchSound;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\entity\animation\ArmSwingAnimation;
use pocketmine\Server;

class FishingRod extends Durable {
	public function __construct($meta = 0){
		parent::__construct(new ItemIdentifier(ItemIds::FISHING_ROD, $meta), "Fishing Rod");
	}

	public function getMaxStackSize(): int{
		return 1;
	}
	
	public function getCooldownTicks(): int{
		return 5;
	}

	public function getMaxDurability(): int{
		return 355; // TODO: Know why it breaks early at 65
	}

	public function addSlot($player, $point){
	    $this->sl = Server::getInstance()->getPluginManager()->getPlugin("Fishing")->sl;
        $this->sl->set($player->getName(), ($this->sl->get($player->getName()) + $point));
        $this->sl->save();
	}

	public function onClickAir(Player $player, Vector3 $directionVector): ItemUseResult{
			$session = Fishing::getInstance()->getSessionById($player->getId());
			if($session instanceof Session){
			  if(!in_array($player->getWorld()->getFolderName(), ["skycreate"])) {
					return ItemUseResult::NONE();
				}
				$playerFishingLevel = FishingLevel::getFishingLevel($player);
				if(!$session->fishing) {
					//Cannot fish at night when under level 3
					$time = $player->getWorld()->getTimeOfDay();
					if ((($time < World::TIME_SUNSET || $time > World::TIME_SUNRISE) && $playerFishingLevel <= 3) || $playerFishingLevel > 3) {
						//$nbt = Entity::createBaseNBT($player->add(0, $player->getEyeHeight(), 0), $directionVector, $player->getLocation()->yaw, $player->pitch);
						$nbt = CompoundTag::create();

						/** @var FishingHook $projectile */
						$projectile = new FishingHook($player->getLocation(), $player, $nbt);
						if($projectile !== null){
							//Level impact ThrowForce
							$throwForce = $this->getThrowForce();
							
							$throwForcePercent = $playerFishingLevel*6-36;
							$throwForceToAdd = ($throwForce / 100) * $throwForcePercent ;
							$throwForce = $throwForce + $throwForceToAdd;
							$projectile->setMotion($projectile->getMotion()->multiply($throwForce));
						}
						
						//change the location where sending hook projectile

							$degreeToRand = 35/$playerFishingLevel;
							$randomRotation = floor(rand()/getrandmax()*($degreeToRand*2)-$degreeToRand) ;
							$randomRotationRadian = $randomRotation * (M_PI/180);
							$hookMotion = $projectile->getMotion();
							$theta = deg2rad($randomRotation);
							$cos = cos($theta);
							$sin = sin($theta);
							$px = $hookMotion->x * $cos - $hookMotion->z * $sin; 
							$pz = $hookMotion->x * $sin + $hookMotion->z * $cos;
							$projectile->setMotion(new Vector3($px, $hookMotion->y, $pz));

						if($projectile instanceof Projectile){
							($projectileEv = new ProjectileLaunchEvent($projectile))->call();
							if($projectileEv->isCancelled()){
								$projectile->flagForDespawn();
							}else{
								$projectile->spawnToAll();
								$player->broadcastAnimation(new ArmSwingAnimation($player));
								$player->getWorld()->addSound($player->getPosition(), new LaunchSound(), $player->getViewers());
							}
						}

						//Todo: Wait weather support
						// $weather = Fishing::$weatherData[$player->getLevel()->getId()];
						// if(($weather->isRainy() || $weather->isRainyThunder())){
							// $rand = mt_rand(15, 50);
						// }else{
							$rand = mt_rand(10, 20);
						// }
						if($this->hasEnchantments()){
							foreach($this->getEnchantments() as $enchantment){
								switch($enchantment->getId()){
									case EnchantmentIds::LURE:
										$divisor = $enchantment->getLevel() * 0.50;
										$rand = intval(round($rand / $divisor)) + 3;
										break;
								}
							}
						}

						$projectile->baseTimer = $rand * 20;

						$session->fishingHook = $projectile;
						$session->fishing = true;
					}
					else {
						$player->sendTip(Fishing::getInstance()->lang["lvltoolownight"]);
					}
				}else{
					$projectile = $session->fishingHook;
					if($projectile instanceof FishingHook){
						$session->unsetFishing();

						if($player->getWorld()->getBlockAt($projectile->getPosition()->getFloorX(), $projectile->getPosition()->getFloorY(), $projectile->getPosition()->getFloorZ())->getId() == BlockIds::WATER || $player->getWorld()->getBlockAt($projectile->getPosition()->getFloorX(), $projectile->getPosition()->getFloorY(), $projectile->getPosition()->getFloorZ())->getId() == BlockIds::WATER){
							$damage = 5;
						}else{
							$damage = mt_rand(10, 15); // TODO: Implement entity / block collision properly
						}

						$this->applyDamage($damage);

						if($projectile->coughtTimer > 0){
							//$weather = Fishing::$weatherData[$player->getLevel()->getId()];
							$lvl = 0;
							if($this->hasEnchantments()){
								if($this->hasEnchantment(EnchantmentIds::LUCK_OF_THE_SEA)){
									$lvl = $this->getEnchantment(EnchantmentIds::LUCK_OF_THE_SEA)->getLevel();
								}
							}
						//	if(($weather->isRainy() || $weather->isRainyThunder()) && $lvl == 0){
						//		$lvl = 2;
						//	}else{
						//		$lvl = 0;
						//	}
							//Level of player impact chance to catch something
							//Level of Enchant LUCK_OF_THE_SEA impact chance too
							 if (mt_rand($playerFishingLevel, intval(round(11+$lvl+sqrt($playerFishingLevel+2)*2))) <= round(2+$lvl+sqrt($playerFishingLevel)*4.4)) {
								$item = FishingLootTable::getRandom($lvl);
								if($item->getId() === 349 || $item->getId() === 460 || $item->getId() === 461) {
									$size = round(9.3 * $playerFishingLevel * (($lvl+2)/3) * (((-1/15)*$projectile->lightLevelAtHook)+2)); //Max 120
									$item->setNamedTag(CompoundTag::create()->setString("FishSize", strval($size)))->setLore(array(Fishing::getInstance()->lang["fishsize"].": ".$size." cm"));
								}
								$this->addSlot($player, 1);
								$player->getInventory()->addItem($item);
								FishingLevel::addFishingExp(mt_rand(3, 6), $player);
								$player->getXpManager()->addXp(mt_rand(2, 4), false);
							}
							else {
								FishingLevel::addFishingExp(mt_rand(1, 3), $player);
								$player->getXpManager()->addXp(mt_rand(1, 2),false);
								$player->knockBack($player->getLocation()->x - $projectile->getLocation()->x, $player->getLocation()->z - $projectile->getLocation()->z, (0.3/$playerFishingLevel));
								$player->sendTip(Fishing::getInstance()->lang["fishhasgoneaway"]);
							}
						}
					}
				}
			}

		return ItemUseResult::SUCCESS();
	}

	public function getThrowForce(): float{
		return 0.9;
	}
}
