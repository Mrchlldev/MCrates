<?php

namespace Mrchlldev\MCrates\commands;

use Mrchlldev\MCrates\commands\subcommand\KeyAllSubcommand;
use Mrchlldev\MCrates\commands\subcommand\KeySubcommand;
use Mrchlldev\MCrates\commands\subcommand\CrateSubcommand;
use Mrchlldev\MCrates\utils\FormManager;
use Mrchlldev\MCrates\MCrates;
use pocketmine\player\Player;
use CortexPE\Commando\BaseCommand;
use CortexPE\Commando\constraint\InGameRequiredConstraint;
use pocketmine\command\CommandSender;

class MCrateCommand extends BaseCommand {

    private MCrates $plugin;
    
    public function __construct(MCrates $plugin, string $name, string $description = "", array $aliases = []) {
        $this->plugin = $plugin;
        parent::__construct($plugin, $name, $description, $aliases);
        $this->setPermission("mcrate.command");
        $this->setAliases(["mc"]);
    }


    /**
     * @return void
     */
    protected function prepare(): void
    {
        $this->registerSubCommand(new CrateSubcommand($this->plugin));
        $this->registerSubCommand(new KeyAllSubcommand($this->plugin));
        $this->registerSubCommand(new KeySubcommand($this->plugin));
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
