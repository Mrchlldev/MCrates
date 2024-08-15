<?php

namespace Mrchlldev\MCrates\commands;

use Mrchlldev\MCrates\commands\subcommand\KeyAllSubcommand;
use Mrchlldev\MCrates\commands\subcommand\KeySubcommand;
use Mrchlldev\MCrates\commands\subcommand\KeyShopSubcommand;
use Mrchlldev\MCrates\commands\subcommand\CrateSubcommand;
use Mrchlldev\MCrates\utils\FormManager;
use pocketmine\player\Player;
use CortexPE\Commando\BaseCommand;
use CortexPE\Commando\constraint\InGameRequiredConstraint;
use pocketmine\command\CommandSender;

class MCrateCommand extends BaseCommand {


    /**
     * @return void
     */
    protected function prepare(): void
    {
        $this->setPermission("mcrate.command");
        $this->setAliases(["mc"]);
        $this->registerSubCommand(new CrateSubcommand(MCrates::getInstance(), "crate"));
        $this->registerSubCommand(new KeyAllSubcommand(MCrates::getInstance(), "keyall"));
        $this->registerSubCommand(new KeySubcommand(MCrates::getInstance(), "key"));
        $this->addConstraint(new InGameRequiredConstraint($this));
    }

    /**
     * @param CommandSender $sender
     * @param string $aliasUsed
     * @param array $args
     * @return void
     */
    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void

    {
        if(!$sender instanceof Player){
            $sender->sendMessage("Use Command In-Game Only!");
            return;
        }
        FormManager::getInstance()->sendFormMenu($sender);
    }

    /**
     * @return null
     */
    public function getPermission(): string {
        return "mcrate.command";
    }
}
