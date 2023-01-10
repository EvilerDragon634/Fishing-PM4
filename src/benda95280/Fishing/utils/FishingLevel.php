<?php


declare(strict_types = 1);

namespace benda95280\Fishing\utils;

use benda95280\Fishing\Fishing;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\player\Player;
use pocketmine\network\mcpe\protocol\{LevelEventPacket, types\LevelEvent};


class FishingLevel {

	/** @var Config */
	private static $levelFile;

	public static function init(): void{
		//Load level file
		self::$levelFile = new Config(Fishing::getInstance()->getDataFolder() . "level.yml", Config::YAML);	
		
		//Save To File Task every minute
		Fishing::getInstance()->getScheduler()->scheduleRepeatingTask(new class extends \pocketmine\scheduler\Task{
			  public function onRun() : void{
				 FishingLevel::saveConfig();
			  }
		}, 1200);
		
	}
	
	public static function getFishingExp(Player $player):int {
		$playerId = trim(strtolower($player->getName()));
		return self::$levelFile->get($playerId,0);
	}
	
	public static function addFishingExp(int $exp, Player $player):void {
		$previousLevel = self::getFishingLevel($player);
		$playerId = trim(strtolower($player->getName()));
		$currentExp = self::$levelFile->get($playerId,0);
		self::$levelFile->set($playerId, $currentExp + $exp);
		if ($previousLevel != self::getFishingLevel($player) ) {
			$player->sendTip(Fishing::getInstance()->lang["lvlup"]);
			$te = new LevelEventPacket();
			$te->eventId = LevelEvent::SOUND_TOTEM;
			$te->position = $player->getPosition()->add(0, $player->getEyeHeight(), 0);
			$te->eventData = 0;
			$player->getNetworkSession()->sendDataPacket($te);				
		}
		else self::sendFishingRemainingPopup($player);
	}

	public static function getFishingLevel(Player $player):int {
		$playerId = trim(strtolower($player->getName()));
		$currentExp = self::$levelFile->get($playerId,0);
		
		$scale = 2;
		$level = intval(floor(pow($currentExp/100,1/$scale)))+1;
		if ($level > 10) $level = 10;
		return $level;
	}
	
	public static function sendFishingRemainingPopup(Player $player) {
		$prevLvlExpNeeded = self::getFishingLevelExpNeeded( self::getFishingLevel($player) );
		$nextLvlExpNeeded = self::getFishingLevelExpNeeded( self::getFishingLevel($player) + 1);
		$player->sendTip(self::getProgress(self::getFishingExp($player) - $prevLvlExpNeeded, $nextLvlExpNeeded - $prevLvlExpNeeded));
	}
	
	public static function getFishingLevelExpNeeded(int $level):int {
		return (100*($level)**2) - (200*($level)) + 100;
	}
	
	public static function getProgress(int $progress, int $size): string {
		$divide = 27201030 + (7.578379 - 27201030)/(1 + ($size/129623)**2.597146);
		$percentage = number_format(($progress / $size) * 100, 2);
		$progress = (int) ceil($progress / $divide);
		$size = (int) ceil($size / $divide);

		return TextFormat::GRAY . "[" . TextFormat::GREEN . str_repeat("|", $progress) .
			TextFormat::RED . str_repeat("|", $size - $progress) . TextFormat::GRAY . "] " .
			TextFormat::AQUA . "{$percentage} %%";
	}
	
	public static function saveConfig() {
		self::$levelFile->save();
	}
	
}
