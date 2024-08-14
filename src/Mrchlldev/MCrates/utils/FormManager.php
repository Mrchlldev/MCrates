<?php 

namespace Mrchlldev\MCrates\utils;

use jojoe77777\FormAPI\SimpleForm as SF;
use jojoe77777\FormAPI\CustomForm as CF;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as TF;
use Mrchlldev\MCrates\MCrates;
use onebone\economyapi\EconomyAPI;

class FormManager {
    use SingletonTrait;

    public $keyshop;

    public function onEnable(): void {
        self::setInstance($this);
    }

    public function sendFormMenu(Player $player) {
        $form = new SF(function (Player $player, $data){
          if($data === null){
            return;
          }
          switch($data){
            case 0:
              $this->sendFormCrate($player);
              break;
            case 1:
              $this->sendFormKey($player);
              break;
            case 2:
              $this->sendFormKeyAll($player);
              break;
            case 3:
              $player->sendMessage(MCrates::getInstance()->getMessage("form.close-message"));
          }
       });
       $form->setTitle(MCrates::PREFIX . " - Main Menu");
       $form->setContent("Manage M-Crates Here !");
       $form->addButton("Create A Crates");
       $form->addButton("Give Key");
       $form->addButton("Give Key All");
       $form->addButton(TF::RED . "Close", 0, "textures/blocks/barrier");
       $player->sendForm($form);
    }

    public function sendFormCrate(Player $player) {
        $form = new CF(function (Player $player, $data){
          if($data === null){
            return;
          }
          if(!isset($data[0])){
              $player->sendMessage(MCrates::getInstance()->getMessage("form.invalid-crate"));
          }
          $crate = MCrates::getInstance()->getCrate($data[0]);
          if ($crate === null) {
            $player->sendMessage(MCrates::getInstance()->getMessage("form.invalid-crate"));
            return;
          }
          MCrates::getInstance()->setInCrateCreationMode($player, $crate);
          $player->sendMessage(MCrates::getInstance()->getMessage("form.crate-success"));
       });
       $form->setTitle(MCrates::PREFIX . " - Create Crates");
       $form->addInput("Crates Name", "Enter A Crates Name Here");
       $player->sendForm($form);
    }

    public function sendFormKey(Player $player) {
        $amountKey = 64;
        $form = new CF(function (Player $player, $data){
          if($data === null){
            return;
          }
          if(!isset($data[0])){
              $player->sendMessage(MCrates::getInstance()->getMessage("form.invalid-crate"));
          }
          $crate = MCrates::getInstance()->getCrate($data[0]);
          if ($crate === null) {
            $player->sendMessage(MCrates::getInstance()->getMessage("form.invalid-crate"));
            return;
          }
          $target = Server::getInstance()->getPlayerExact($data[1]);
          $amount = (int)$data[2];
          if(!$target instanceof Player) {
            $player->sendMessage(MCrates::getInstance()->getMessage("form.invalid-player"));
            return;
          }
          $crate->giveKey($target, $amount);
          $target->sendMessage(MCrates::getInstance()->getMessage("form.success-sender", ["{CRATE}" => $crate->getName()]));
          $player->sendMessage(MCrates::getInstance()->getMessage("form.success-target", ["{CRATE}" => $crate->getName(), "{TARGET}" => $target->getName()]));
       });
       $form->setTitle(MCrates::PREFIX . " - Give Key");
       $form->addInput("Crates Name", "Enter A Crates Name Here");
       $form->addInput("Player Name", "Enter A Player Name Here");
       $form->addSlider("Amount Key", 1, (int)$amountKey);
       $player->sendForm($form);
    }

    public function sendFormKeyAll(Player $player) {
        $amountKey = 64;
        $form = new CF(function (Player $player, $data){
          if($data === null){
            return;
          }
          if(!isset($data[0])){
              $player->sendMessage(MCrates::getInstance()->getMessage("form.invalid-crate"));
          }
          $crate = MCrates::getInstance()->getCrate($data[0]);
          if ($crate === null) {
            $player->sendMessage(MCrates::getInstance()->getMessage("form.invalid-crate"));
            return;
          }
          foreach(Server::getInstance()->getOnlinePlayers() as $target){
            $amount = (int)$data[1];
            if(!$target instanceof Player) {
              $player->sendMessage(MCrates::getInstance()->getMessage("form.invalid-player"));
              return;
            }
            $crate->giveKey($target, $amount);
            $target->sendMessage(MCrates::getInstance()->getMessage("form.success-sender", ["{CRATE}" => $crate->getName()]));
            $player->sendMessage(MCrates::getInstance()->getMessage("form.success-all-target", ["{CRATE}" => $crate->getName()]));
          }
       });
       $form->setTitle(MCrates::PREFIX . " - Give All Key");
       $form->addInput("Crates Name", "Enter A Crates Name Here");
       $form->addSlider("Amount Key", 1, (int)$amountKey);
       $player->sendForm($form);
    }
}  