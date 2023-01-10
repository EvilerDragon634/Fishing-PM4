
<?php

/*
Bend95280 do not know what should i put here
*/

namespace benda95280\Fishing;

use pocketmine\player\Player;
use pocketmine\Server;
//use pocketmine\level\Level;
//use pocketmine\world\Position;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\network\mcpe\protocol\ActorEventPacket;

use benda95280\Fishing\utils\FishingLootTable;
use benda95280\Fishing\utils\FishingLevel;
use benda95280\Fishing\entity\EntityManager;
use benda95280\Fishing\item\ItemManager;
use benda95280\Fishing\command\Command;

use pocketmine\event\player\PlayerJoinEvent;

class Fishing extends PluginBase {
	/** @var Fishing */
	private static $instance = null;
	/** @var Session[] */
	private $sessions = [];
	/** @var Config */
	public static $cacheFile;
	public static $levelFile;
	
	public $lang;
	
	public static $randomFishingLootTables = true;
	public static $registerVanillaEnchantments = true;

	public static function getInstance(): Fishing{
		return self::$instance;
	}
	
	public function onLoad() :void {
	    if(!self::$instance instanceof Fishing){
	        self::$instance = $this;
	    }
		@mkdir($this->getDataFolder());
		self::$cacheFile = new Config($this->getDataFolder() . "cache.json", Config::JSON);
		//Lang init
        new Config($this->getDataFolder() . 'lang.yml', Config::YAML, array(
            "lvlup" => "! Level Câu Cá Của Bạn Đã Lên Cấp !",
            "lvltoolownight" => "Cấp độ của bạn không đủ để câu cá vào ban đêm",
            "fishsize" => "Độ dài",
            "fishhasgoneaway" => "Cá đã chạy mất!",
            "linebreaklvltoolow" => "Dây của bạn đã bị đứt vì cấp độ của bạn chưa đủ",
            "tooslowfishhasgoneaway" => "Quá muộn, cá đã chạy mất...",
        ));
		
		$this->economyapi = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
		$this->lang = (array)yaml_parse_file($this->getDataFolder() . "lang.yml");
		$this->sl = new Config($this->getDataFolder() . "soluong.yml", Config::YAML);
	}
	
    public function onEnable() :void {
		FishingLootTable::init();
		FishingLevel::init();
		ItemManager::init();
		EntityManager::init();
		$this->getServer()->getCommandMap()->register("Fishing", new Command($this));
		$this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
	}
	
	public function getSessionById(int $id){
		if(isset($this->sessions[$id])){
			return $this->sessions[$id];
		}else{
			return null;
		}
	}	
	
	public function createSession(Player $player): bool{
		if(!isset($this->sessions[$player->getId()])){
			$this->sessions[$player->getId()] = new Session($player);
			$this->getLogger()->debug("Created " . $player->getName() . "'s Session");

			return true;
		}

		return false;
	}	
	
	public function destroySession(Player $player): bool{
		if(isset($this->sessions[$player->getId()])){
			unset($this->sessions[$player->getId()]);
			$this->getLogger()->debug("Destroyed " . $player->getName() . "'s Session");

			return true;
		}

		return false;
	}
	
	/**
	 * @param Player[]|null $players
	 */
	public function broadcastEntityEvent($entity, int $eventId, ?int $eventData = null, ?array $players = null) : void{
		$pk = new ActorEventPacket();
		$pk->actorRuntimeId = $entity->getId();
		$pk->eventId = $eventId;
		$pk->eventData = $eventData ?? 0;
		$this->getServer()->broadcastPackets($players ?? $entity->getViewers(), [$pk]);
	}
}
