<?php

namespace benda95280\Fishing\command;

use pocketmine\command\{Command as PMCommand, CommandSender};
use pocketmine\plugin\PluginOwned;
use pocketmine\player\Player;
use benda95280\Fishing\Form\{SimpleForm, CustomForm};
use benda95280\Fishing\Fishing;
use pocketmine\nbt\tag\CompoundTag;
use benda95280\Fishing\utils\FishingLootTable;

class Command extends PMCommand implements PluginOwned {
	public function __construct(Fishing $main) {
		$this->main = $main;
		parent::__construct("fish", "Fishing Menu");
		$this->setPermission("fishing.command.fish");
	}
	
	public function getOwningPlugin() :Fishing {
		return $this->main;
	}
	
	public function execute(CommandSender $sender, string $commandLabel, array $args) :bool {
		if(!$sender instanceof Player) return false;
		if(!$this->testPermission($sender)) return false;
		$this->mainForm($sender);
		return true;
	}
	
	public function mainForm(Player $player){
		$form = new SimpleForm(function (Player $player, $data){
		    if($data === null){
			    return;
		    }
		    switch($data){
			    case 0:
			        $this->sellForm($player);
			    break;
                case 1:
			        $item = $player->getInventory()->getItemInHand();
	                $name = $player->getName();
			        if($item->getNamedTag()->getTag("FishSize") != null) {
				         $this->main->getServer()->broadcastMessage("§l§1↣§e WOW! Người chơi §a". $name ."§e sở hữu ". $item->getName() ." §l§c【 ". $item->getNamedTag()->getTag("FishSize")->getValue() ." cm 】");
			        }
			        else{
				        $this->msgForm($player,"§l§1↣§e Bạn không thể khoe vật phẩm này!");
			        }
			    break;		
				case 2:
					$this->topFishing($player);
					break;
				case 3:
					$this->getForm($player);
					break;
		    }
		});
		$form->setTitle("§6§l♦ §dHệ Thống Cá §6♦");
		$form->setMessage("§l§c▶§r§7 Bạn có thể §abán cá §evà §akhoe cá §etại đây.");
		$form->addButton("§3§l●§2 Bán Cá §3●", 1 , "https://cdn-icons-png.flaticon.com/512/743/743007.png");
		$form->addButton("§3§l●§2 Khoe Cá §3●§e", 1 , "https://cdn-icons-png.flaticon.com/128/822/822102.png");
		$form->addButton("§3§l●§2 Top Cá §3●§e");
		if($this->main->getServer()->isOp($player->getName())) {
			$form->addButton("§3§l●§2 Nhận Cá §3●§e");
		}
		$player->sendForm($form);
	}
	
	public function getForm(Player $player){
        $form = new CustomForm(function (Player $player, $data){
			if($data === null){
				$this->mainForm($player);
				return;
			}			
			for($i = 0; $i < $data[0]; ++$i) {
				$item = FishingLootTable::getRandom(0);
				if($item->getId() === 349 || $item->getId() === 460 || $item->getId() === 461) {
					$size = strtolower($data[1]) == "rand" ? mt_rand(1, 120) : $data[1];
					$item->setNamedTag(CompoundTag::create()->setString("FishSize", strval($size)))->setLore(array($this->main->lang["fishsize"].": ".$size." cm"));
				}
				$player->getInventory()->addItem($item);
			}
		});
		$form->setTitle("§6§l♦ §dNhận Cá §6♦");
		$form->addInput("Nhập số cá muốn nhận: ");
		$form->addInput("Size cá: ", "Nhập \"rand\" để random size");
        $player->sendForm($form);			
	}
	
	public function topFishing(Player $player) :void {
		$point = $this->main->sl->getAll();
		$message = ""; $rank = "100+";
		if(count($point) > 0){
			arsort($point);
			$i = 1;
			foreach($point as $name => $p){
			    if($i == 1) $message .= "§l§cTOP $i : §6$name §c➢ §f$p §cPoint\n";
			    if($i == 2) $message .= "§l§eTOP $i : §6$name §e➢ §f$p §ePoint\n";
			    if($i == 3) $message .= "§l§aTOP $i : §6$name §a➢ §f$p §aPoint\n";
			    if($i >3) $message .= "§l§fTOP $i : §6$name §f➢ §f$p Point\n";
				if($name == $player->getName())$rank = $i;
				if($i >= 100) break;
				++$i;
			}
		}
		$point = $this->main->sl->get($player->getName());
		$form = new SimpleForm(function (Player $sender, $_){
			$this->mainForm($sender);
			return;
		});
		$form->setTitle("§l§f•§bTop Fishing§f•");
		$form->setMessage("§e•§l§bRank of you: §f$rank \n".$message);
		$form->addButton("§l§f•§b $point §ePoint §f•");
		$player->sendForm($form);
	}
	
	public function sellForm(Player $player){
		$form = new CustomForm(function (Player $player, $data){
			if($data === null){
				$this->mainForm($player);
				return;
			}
			$item = $player->getInventory()->getItemInHand();
			if($item->getNamedTag()->getTag("FishSize") != null){
			    $count = (int) $data[1];
		        if(!isset($count) || !is_numeric($count) || $count < 0 || !preg_match('/^[0-9]+$/', $count, $matches)){
				    $this->msgForm($player,"§l§c▶§r§7 Số bán cá cần phải là số!");
			        return;
			    }
				$size = $item->getNamedTag()->getTag("FishSize")->getValue();
				$money = $count * $size * 40;
			    if($item->getCount() >= $count){
					$item->setCount($item->getCount() - $count);
                    $player->getInventory()->setItemInHand($item);
				    //$this->main->economyapi->addMoney($player, $money);
					$this->msgForm($player,"§l§c▶§r§7 Bán được: §a". $money ." xu.");
			    }
				else{
				    $this->msgForm($player,"§l§c▶§r§7 Cần có: §a". $count ." cá.");
			    }
			}
			else{
				$this->msgForm($player,"§l§c●§e Bạn không thể bán vật phẩm này!");
			}
		});
		$form->setTitle("§6§l♦ §dHệ Thống Cá §6♦");
		$form->addLabel("§l§c▶§r§7 Giá bán cá:§a 1 cm = 40 xu.");
		$form->addInput("§l§c▶§r§7 Nhập số cá cần bán:", 0);
		$player->sendForm($form);
	}
	
	public function msgForm(Player $player, $msg){
        $form = new SimpleForm(function (Player $player, $data){
			if($data === null){
				$this->mainForm($player);
				return;
			}			
			switch($data){
				case 0:
					$this->mainForm($player);
				break;	
			}
		});
		$form->setTitle("§6§l♦ §dHệ Thống Cá §6♦");
        $form->setMessage($msg);
		$form->addButton("§3§l●§2 Quay lại §3●", 1, "https://cdn-icons-png.flaticon.com/128/2698/2698776.png");	
        $player->sendForm($form);			
	}
}
