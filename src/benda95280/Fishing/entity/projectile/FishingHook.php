<?php

declare(strict_types = 1);

namespace benda95280\Fishing\entity\projectile;

use benda95280\Fishing\Fishing;
use benda95280\Fishing\utils\FishingLevel;
use benda95280\Fishing\Session;
use pocketmine\block\StillWater;
use pocketmine\block\Water;
use pocketmine\block\Liquid;
use pocketmine\entity\{Entity, EntitySizeInfo, Location};
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\ProjectileHitEntityEvent;
use pocketmine\math\RayTraceResult;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\utils\Random;
use pocketmine\network\mcpe\protocol\types\ActorEvent;
use pocketmine\player\Player;
use pocketmine\world\particle\BubbleParticle;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\Server as PMServer;
use pocketmine\nbt\tag\CompoundTag;

class FishingHook extends Projectile {

	public const NETWORK_ID = EntityIds::FISHING_HOOK;

	public $width = 0.25;
	public $length = 0.25;
	public $height = 0.25;
	public $baseTimer = 0;
	public $coughtTimer = 0;
	public $bubbleTimer = 0;
	public $bubbleTicks = 0;
	public $bitesTicks = 0;
	public $attractTimer = 0;
	public $attractTimerTicks = 0;
	public $lightLevelAtHook = 0;
	protected $gravity = 0.1;
	protected $drag = 0.05;
	protected $touchedWater = false;
	
	public function __construct(Location $location, ?Entity $shootingEntity, ?CompoundTag $nbt = null)
    {
        parent::__construct($location, $shootingEntity, $nbt);
        if($shootingEntity instanceof Player) {
        	$this->setMotion($shootingEntity->getDirectionVector()->multiply(0.5));
			$this->handleHookCasting($this->getMotion()->x, $this->getMotion()->y, $this->getMotion()->z, 1.5, 1.0);
		}
    }
	
	private function handleHookCasting(float $x, float $y, float $z, float $f1, float $f2): void
    {
        $rand = new Random();
        $f = sqrt($x * $x + $y * $y + $z * $z);
        $x = $x / (float) $f;
        $y = $y / (float) $f;
        $z = $z / (float) $f;
        $x = $x + $rand->nextSignedFloat() * 0.007499999832361937 * (float) $f2;
        $y = $y + $rand->nextSignedFloat() * 0.007499999832361937 * (float) $f2;
        $z = $z + $rand->nextSignedFloat() * 0.007499999832361937 * (float) $f2;
        $x = $x * (float) $f1;
        $y = $y * (float) $f1;
        $z = $z * (float) $f1;
        $vec3 = new Vector3($x, $y + 0.5, $z);
		$this->setMotion($vec3);
    }

	public static function getNetworkTypeId() :string {
		return self::NETWORK_ID;
	}
	
	public function getInitialSizeInfo() :EntitySizeInfo {
		return new EntitySizeInfo(0.25, 0.25);
	}

	public function onUpdate(int $currentTick): bool{
		if($this->isFlaggedForDespawn() || !$this->isAlive()){
			return false;
		}
		
		$oe = $this->getOwningEntity();
		
		//Remove if Owner is null
		if ($oe === null) {
			if(!$this->isFlaggedForDespawn()){
				$this->flagForDespawn();
			}			
		}
			
		//Remove if Owner too far
		if($oe instanceof Player){
			if($this->getPosition()->distance($oe->getPosition()) > (25 - (10 - FishingLevel::getFishingLevel($oe))*2) ) {
				$session = Fishing::getInstance()->getSessionById($oe->getId());
				if($session instanceof Session){
					$oe->sendTip(Fishing::getInstance()->lang["linebreaklvltoolow"]);
					$session->unsetFishing();
				}	
			}
		}
		
		//calculate timer for attractTimer
		$this->lightLevelAtHook = $this->getWorld()->getRealBlockSkyLightAt(intval($this->getLocation()->x), intval($this->getLocation()->y), intval($this->getLocation()->z));
		$this->attractTimer = ($this->baseTimer * (((-1/15)*$this->lightLevelAtHook)+2)) - $this->attractTimerTicks;

		$this->timings->startTiming();

		$hasUpdate = parent::onUpdate($currentTick);
		
		if($this->isInsideOfSolid()){
			$random = new Random((int) (microtime(true) * 1000) + mt_rand());
			$this->motion->x *= $random->nextFloat() * 0.2;
			$this->motion->y *= $random->nextFloat() * 0.2;
			$this->motion->z *= $random->nextFloat() * 0.2;
		}		
		
		if (!$this->isInsideOfSolid()) {
			$f6 = 0.92;

			if($this->onGround or $this->isCollidedHorizontally){
				$f6 = 0.5;
			}
			
			$d10 = 0;
			$bb = $this->getBoundingBox();
			for($j = 0; $j < 5; ++$j){
				$d1 = $bb->minY + ($bb->maxY - $bb->minY) * $j / 5;
				$d3 = $bb->minY + ($bb->maxY - $bb->minY) * ($j + 1) / 5;
				$bb2 = new AxisAlignedBB($bb->minX, $d1, $bb->minZ, $bb->maxX, $d3, $bb->maxZ);
				if($this->isLiquidInBoundingBox($bb2)){
					$d10 += 0.2;
				}
			}

			if ($d10 > 0) {	
				//Little annimation floating
				if ($currentTick % 60 === 0) $this->motion->y =-0.02;
				//Wait Wait, we are waiting the fish
				if($this->attractTimer <= 0){
					//Set bubble timer, fish is near !
					if ($this->bubbleTimer === 0 && $this->coughtTimer <= 0) {
						$this->bubbleTimer = mt_rand(5, 10) * 20;
					}
					else if ($this->bubbleTimer > 0) {
						$this->bubbleTimer--;
					}
					
					//If bubble timer finished, catch it !
					if ($this->bubbleTimer <= 0 && $this->coughtTimer <= 0) {
						$this->coughtTimer = mt_rand(3, 5) * 20;
						if($oe instanceof Player){
							$oe->sendTip("Có một con cá!");
						}
						$this->fishBites();
						$this->bitesTicks = mt_rand(1, 3) * 20;
					}
					//Else do animation every X ticks
					else {
						if ($this->bubbleTicks === 0) {
							$this->attractFish();
							$this->bubbleTicks = 10;
						}
						else {
							$this->bubbleTicks--;
						}
						
					}
				}
				elseif($this->attractTimer > 0){
					$this->attractTimerTicks++;
				}
				
				if($this->coughtTimer > 0){
					$this->coughtTimer--;
					if ($this->bitesTicks === 0) {
						$this->fishBites();
						$this->bitesTicks = mt_rand(1, 3) * 20;
					}
					else {
						$this->bitesTicks--;
					}
	
					//Too late, fish has gone, reset timer
					if ($this->coughtTimer <= 0)
					{
						$oe->sendTip(Fishing::getInstance()->lang["tooslowfishhasgoneaway"]);
						$this->baseTimer = mt_rand(10, 20) * 20;
						$this->attractTimerTicks = 0;
					}
				}
				
			}
			$d11 = $d10 * 2.0 - 1.0;
			
			$this->motion->y += 0.04 * $d11;
				if($d10 > 0.0){
				$f6 = $f6 * 0.9;
				$this->motion->y *= 0.8;
			}		
			
			$this->motion->x *= $f6;
			$this->motion->y *= $f6;
			$this->motion->z *= $f6;
		}
		// var_dump("baseTimer: ".$this->baseTimer." attractTimer: ".$this->attractTimer." attractTimerTicks: ".$this->attractTimerTicks." coughtTimer: ".$this->coughtTimer." bubbleTimer: ".$this->bubbleTimer);
		$this->timings->stopTiming();

		return $hasUpdate;
	}

	public function attractFish(){
		$oe = $this->getOwningEntity();
		if($oe instanceof Player){
			Fishing::getInstance()->broadcastEntityEvent($this, ActorEvent::FISH_HOOK_BUBBLE);
		}
	}

	public function fishBites(){
		$oe = $this->getOwningEntity();
		if($oe instanceof Player){
			Fishing::getInstance()->broadcastEntityEvent($this, ActorEvent::FISH_HOOK_HOOK);
		}
		$this->motion->y =-0.09;
		$this->getWorld()->addParticle(new Vector3($this->getLocation()->x, $this->getLocation()->y - 0.1, $this->getLocation()->z), new BubbleParticle(), $this->getViewers());
	}

	public function onHitEntity(Entity $entityHit, RayTraceResult $hitResult): void{
		(new ProjectileHitEntityEvent($this, $hitResult, $entityHit))->call();

		$damage = $this->getResultDamage();

		if($this->getOwningEntity() === null){
			$ev = new EntityDamageByEntityEvent($this, $entityHit, EntityDamageEvent::CAUSE_PROJECTILE, $damage);
		}else{
			$ev = new EntityDamageByChildEntityEvent($this->getOwningEntity(), $this, $entityHit, EntityDamageEvent::CAUSE_PROJECTILE, $damage);
		}

		$entityHit->attack($ev);
		if($this->getOwningEntity() !== null){
			$entityHit->setMotion($this->getOwningEntity()->getDirectionVector()->multiply(-0.3)->add(0, 0.3, 0));
		}

		$this->isCollided = true;
		$this->flagForDespawn();
	}

	public function getResultDamage(): int{
		return 1;
	}
	
	/**
	 * @param AxisAlignedBB $bb
	 * @param Liquid        $material
	 *
	 * @return bool
	 */
	public function isLiquidInBoundingBox(AxisAlignedBB $bb) : bool{
		$minX = (int) floor($bb->minX);
		$minY = (int) floor($bb->minY);
		$minZ = (int) floor($bb->minZ);
		$maxX = (int) floor($bb->maxX + 1);
		$maxY = (int) floor($bb->maxY + 1);
		$maxZ = (int) floor($bb->maxZ + 1);

		for($x = $minX; $x < $maxX; ++$x){
			for($y = $minY; $y < $maxY; ++$y){
				for($z = $minZ; $z < $maxZ; ++$z){
					$block = $this->getWorld()->getBlockAt($x, $y, $z);

					if($block instanceof Liquid){
						$j2 = $block->getMeta();
						$d0 = $y + 1;

						if($j2 < 8){
							$d0 -= $j2 / 8;
						}

						if($d0 >= $bb->minY){
							return true;
						}
					}
				}
			}
		}

		return false;
	}
	
	protected function tryChangeMovement() : void{
	}
}
