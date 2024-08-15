<?php

declare(strict_types=1);

namespace Mrchlldev\MCrates;

use Symfony\Component\Filesystem\Path;
use customiesdevs\customies\item\CustomiesItemFactory;
use CortexPE\Commando\BaseCommand;
use CortexPE\Commando\exception\HookAlreadyRegistered;
use CortexPE\Commando\PacketHooker;
use DaPigGuy\libPiggyUpdateChecker\libPiggyUpdateChecker;
use DaPigGuy\PiggyCustomEnchants\CustomEnchantManager;
use DaPigGuy\PiggyCustomEnchants\PiggyCustomEnchants;
use Mrchlldev\MCrates\commands\CrateCommand;
use Mrchlldev\MCrates\commands\KeyAllCommand;
use Mrchlldev\MCrates\commands\KeyCommand;
use Mrchlldev\MCrates\commands\PCMenuCommand;
use Mrchlldev\MCrates\commands\MCrateCommand;
use Mrchlldev\MCrates\crates\Crate;
use Mrchlldev\MCrates\crates\CrateItem;
use Mrchlldev\MCrates\tiles\CrateTile;
use Mrchlldev\MCrates\utils\Utils;
use Exception;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\block\tile\TileFactory;
use pocketmine\resourcepacks\ZippedResourcePack;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\StringToEnchantmentParser;
use pocketmine\item\LegacyStringToItemParser;
use pocketmine\item\LegacyStringToItemParserException;
use pocketmine\item\StringToItemParser;
use pocketmine\nbt\JsonNbtParser;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\Config;

class MCrates extends PluginBase
{
    public const PREFIX = "§l§cMCRATES» §r";

    private static MCrates $instance;

    private Config $messages;

    /** @var Crate[] */
    public array $crates = [];
    /** @var CrateTile[] */
    public array $crateTiles = [];
    /** @var Array<string, Crate> */
    public array $crateCreation;

    /**
     * @throws HookAlreadyRegistered
     */
    public function onEnable(): void
    {
        //$this->getLogger()->emergency("This plugin is not supported PiggyCustomEnchants for a while");
        foreach (
            [
                "Commando" => BaseCommand::class,
                "InvMenu" => InvMenuHandler::class,
                "Customies" => CustomiesItemFactory::class,
                "libPiggyUpdateChecker" => libPiggyUpdateChecker::class
            ] as $virion => $class
        ) {
            if (!class_exists($class)) {
                $this->getLogger()->error($virion . " virion not found. Please download MCrates from Poggit-CI or use DEVirion (not recommended).");
                $this->getServer()->getPluginManager()->disablePlugin($this);
                return;
            }
        }

        if (!InvMenuHandler::isRegistered()) {
            InvMenuHandler::register($this);
        }

        self::$instance = $this;

        libPiggyUpdateChecker::init($this);

        TileFactory::getInstance()->register(CrateTile::class);
        $this->saveResource("crates.yml");
        $this->saveResource("messages.yml");
        $this->messages = new Config($this->getDataFolder() . "messages.yml");
        $this->saveDefaultConfig();

        $crateConfig = new Config($this->getDataFolder() . "crates.yml");
        $types = ["item", "command"];
        foreach ($crateConfig->get("crates") as $crateName => $crateData) {
            $this->crates[$crateName] = new Crate($this, $crateName, $crateData["floating-text"] ?? "", array_map(function (array $itemData) use ($crateName, $types): CrateItem {
                $tags = null;
                $item = null;
                if (isset($itemData["nbt"])) {
                    try {
                        $tags = JsonNbtParser::parseJson($itemData["nbt"]);
                    } catch (Exception $e) {
                        $this->getLogger()->warning("Invalid crate item NBT supplied in crate type " . $crateName . ".");
                    }
                }
                try {
                    $item = StringToItemParser::getInstance()->parse($itemData["item"]) ?? LegacyStringToItemParser::getInstance()->parse($itemData["item"]) ?? CustomiesItemFactory::getInstance()->get($itemData["item"]);
                }catch(LegacyStringToItemParserException $e){
                    echo $e->getMessage();
                }
                if($tags !== null) {
                    $item->setNamedTag($tags);
                }
                if (!isset($itemData["amount"])){
                    $this->getLogger()->info("Amount item not found in crate " . $crateName . "!\nAmount item will be set to (int) 1!");
                } else {
                    $item->setCount((int)$itemData["amount"]);
                }
                if ($item->getCustomName() == null && isset($itemData["name"])) $item->setCustomName($itemData["name"]);
                if (isset($itemData["lore"])) $item->setLore(explode("\n", $itemData["lore"]));
                if (isset($itemData["enchantments"])) foreach ($itemData["enchantments"] as $enchantmentData) {
                    if (!isset($enchantmentData["name"]) || !isset($enchantmentData["level"])) {
                        $this->getLogger()->error("Invalid enchantment configuration used in crate " . $crateName);
                        continue;
                    }
                    $enchantment = StringToEnchantmentParser::getInstance()->parse($enchantmentData["name"]) ?? ((($plugin = $this->getServer()->getPluginManager()->getPlugin("PiggyCustomEnchants")) instanceof PiggyCustomEnchants && $plugin->isEnabled()) ? CustomEnchantManager::getEnchantmentByName($enchantmentData["name"]) : null);
                    if ($enchantment !== null) $item->addEnchantment(new EnchantmentInstance($enchantment, $enchantmentData["level"]));
                }
                $itemData["type"] = $itemData["type"] ?? "item";
                if (!in_array($itemData["type"], $types)) {
                    $itemData["type"] = "item";
                    $this->getLogger()->warning("Invalid crate item type supplied in crate type " . $crateName . ". Assuming type item.");
                }
                return new CrateItem($item, $itemData["type"], $itemData["commands"] ?? [], $itemData["chance"] ?? 100);
            }, $crateData["drops"] ?? []), $crateData["amount"], $crateData["commands"] ?? []);
        }

        if (!PacketHooker::isRegistered()) PacketHooker::register($this);
        $this->getServer()->getCommandMap()->register("mcrates", new MCrateCommand($this, "mcrate", "MCrates Command", [], ["mc"]));

        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);

        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function () {
            foreach ($this->crateTiles as $crateTile) {
                $crateTile->onUpdate();
            }
        }), 20);
    }

    public static function getInstance(): MCrates
    {
        return self::$instance;
    }

    public function getMessage(string $key, array $tags = []): string
    {
        return Utils::translateColorTags(str_replace(array_keys($tags), $tags, $this->messages->getNested($key, $key)));
    }

    public function getCrate(string $name): ?Crate
    {
        return $this->crates[$name] ?? null;
    }

    public function getCrates(): array
    {
        return $this->crates;
    }

    public function inCrateCreationMode(Player $player): bool
    {
        return isset($this->crateCreation[$player->getName()]);
    }

    public function setInCrateCreationMode(Player $player, ?Crate $crate): void
    {
        if ($crate === null) {
            unset($this->crateCreation[$player->getName()]);
            return;
        }
        $this->crateCreation[$player->getName()] = $crate;
    }

    public function getCrateToCreate(Player $player): ?Crate
    {
        return $this->crateCreation[$player->getName()] ?? null;
    }
}
