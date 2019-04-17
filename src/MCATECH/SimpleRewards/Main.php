<?php

declare(strict_types=1);

namespace MCATECH\SimpleRewards;

use pocketmine\plugin\PluginBase;

use pocketmine\command\CommandSender;
use pocketmine\command\Command;

use pocketmine\Player;
use pocketmine\Server;

use pocketmine\item\Item;

use pocketmine\utils\Config;

use pocketmine\event\Listener;
use pocketmine\event\player\ PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;

use pocketmine\event\server\DataPacketReceiveEvent;

use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;

//DeadlyDash API for Economy//
use MCATECH\DeadlyDash\DeadlyDash;

//TODO make it multi economy.. to allow for people who dont use simplecoins able.

class Main extends PluginBase implements Listener{

	public function onEnable() : void{
		$this->bonus = new Config($this->getDataFolder(). "bonus.yml" ,Config::YAML);
		$this->button = new Config($this->getDataFolder(). "Buttons.yml" ,Config::YAML);
		$this->messages = new Config($this->getDataFolder(). "Messages.yml" ,Config::YAML);
		$this->config = new Config($this->getDataFolder(). "config.yml" ,Config::YAML,[
			'Notice' => 'Notice',
			'coins' => 400,
			'Amplifier_Beta' => 'false',
			'id' => 388,
			'meta' => 0,
			'amount' => 1,
			]);
		
		date_default_timezone_set('Europe/London');
		
		$this->getServer()->getPluginManager()->registerEvents($this,$this);
	}
	
	public function makeMessages (){
		//TODO
	}
	
	public function onJoin(PlayerJoinEvent $event){
		$player = $event->getPlayer();
		$name = $player->getName();
		if(!$this->bonus->exists($name)){
			$this->bonus->set($name, 0);
			$this->bonus->save();
		}
	}
	
	public function onReceive(DataPacketReceiveEvent $event){
		$player = $event->getPlayer();
		$pk = $event->getPacket();
		$name = $player->getName();
		if($pk->getName() == "ModalFormResponsePacket"){
			$data = $pk->formData;
			$result = json_decode($data);
			if($data == "null\n"){
			}else{
				switch($pk->formId){
				case 180501:
					if($data == 0){
						$this->giveBonus($player);
					break;
					}
				return true;
				}
			}
		}
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		switch($command->getName()){
			case "daily":
				$name = $sender->getName();
				$buttons[] = [
				'text' => "Daily Reward", 
				'image' => [ 'type' => 'path', 'data' => "" ] 
				]; 
				$content[] = [
				'text' => "Join the server everyday for login rewards! \n Soon your rewards will be amplified! \n Current rewards: Emerald X1 and 400 Coins"
				];
				$this->sendForm($sender,"§l§3Rewards","",$buttons,180501);
				$this->info[$name] = "form";
				return true;
			default:
				return false;
		}
	}
	
	public function sendForm(Player $player, $title, $content, $buttons, $id) {
		$pk = new ModalFormRequestPacket(); 
		$pk->formId = $id;
		$this->pdata[$pk->formId] = $player;
		$data = [ 
		'type'    => 'form',
		'title'   => $title, 
		'content' => $content,
		'buttons' => $buttons 
		]; 
		$pk->formData = json_encode( $data, JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING | JSON_UNESCAPED_UNICODE );
		$player->dataPacket($pk);
		$this->lastFormData[$player->getName()] = $data;
	}
	
	public function giveBonus($player){ //TODO add amplifier support
		$day = date("ymd");
		$name = $player->getName();
		if($this->bonus->exists($name)){
			if($this->bonus->get($name) === $day){
				$player->sendMessage("§c§l(!) §r§cYou have already claimed your Daily Reward for today!");
				//$player->sendMessage($this->messages->get('reward-text')); //TODO make this for all texts AKA from message config
			}else{
				$item = Item::get($this->config->get('id'),$this->config->get('meta'),$this->config->get('amount'));
				if($player->getInventory()->canAddItem($item)){
					$player->getInventory()->addItem($item);
					$bonuscoins = $this->config->get('coins');
					DeadlyDash::getInstance()->addCoins($player, $bonuscoins);
					$player->sendMessage("§bLogin Bonus: \n Item: §6". $item->getName() . " \n §bAmount: §6" . $this->config->get('amount') . " \n §bCoins: §6" . $bonuscoins . " \n §bGet more rewards tomorrow!");
					$this->bonus->set($name, $day);
					$this->bonus->save();
				}else{
					$player->sendMessage("§c§l(!) §r§cYour inventory is full and you cannot be given your daily reward.");
				}
			}
		}
	}

	public function sendWindow(Player $player, $data, int $id){
		$pk = new ModalFormRequestPacket();
		$pk->formId = $id;
		$pk->formData = json_encode($data, JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING | JSON_UNESCAPED_UNICODE);
		$player->dataPacket($pk);
	}

	public function onDisable() : void{
		$this->bonus->save();
	}
}
