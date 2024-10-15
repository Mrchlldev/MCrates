<?php

declare(strict_types=1);

namespace Mrchlldev\MCrates\crates;

use Mrchlldev\MCrates\MCrates;
use pocketmine\item\Item;
use pocketmine\item\LegacyStringToItemParser;
use pocketmine\item\LegacyStringToItemParserException;
use pocketmine\item\StringToItemParser;
use pocketmine\nbt\tag\StringTag;
use pocketmine\player\Player;

class Crate
{
    /**
     * @param CrateItem[] $drops
     * @param string[] $commands
     */
    public function __construct(private readonly MCrates $plugin, public string $name, public string $floatingText, public array $drops, public int $dropCount, public array $commands)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getFloatingText(): string
    {
        return $this->floatingText;
    }

    /**
     * @return CrateItem[]
     */
    public function getDrops(): array
    {
        return $this->drops;
    }

    /**
     * @return CrateItem[]
     */
    public function getDrop(int $amount): array
    {
        $dropTable = [];
        foreach ($this->drops as $drop) {
            for ($i = 0; $i < $drop->getChance(); $i++) {
                $dropTable[] = $drop;
            }
        }

        $keys = array_rand($dropTable, $amount);
        if (!is_array($keys)) $keys = [$keys];
        return array_map(function ($key) use ($dropTable) {
            return $dropTable[$key];
        }, $keys);
    }

    public function getDropCount(): int
    {
        return $this->dropCount;
    }

    /**
     * @return string[]
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    public function giveKey(Player $player, int $amount): void
    {
        $key = null;
        try {
            $key = StringToItemParser::getInstance()->parse($this->plugin->getConfig()->getNested("keys.id") . ":" . $this->plugin->getConfig()->getNested("keys.meta")) ?? LegacyStringToItemParser::getInstance()->parse($this->plugin->getConfig()->getNested("keys.id") . ":" . $this->plugin->getConfig()->getNested("keys.meta"));
        }catch (LegacyStringToItemParserException $e){
            $this->plugin->getLogger()->warning($e->getMessage());
        }
        $key?->setCount($amount);
        $key->setCustomName(ucfirst(str_replace("{CRATE}", $this->getName(), $this->plugin->getConfig()->getNested("keys.name"))));
        $key->setLore([str_replace("{CRATE}", $this->getName(), $this->plugin->getConfig()->getNested("keys.lore"))]);
        $key->getNamedTag()->setString("KeyType", $this->getName());
        $player->getInventory()->addItem($key);
    }

    public function isValidKey(Item $item): bool
    {
        $key = null;
        try {
            $key = StringToItemParser::getInstance()->parse($this->plugin->getConfig()->getNested("keys.id") . ":" . $this->plugin->getConfig()->getNested("keys.meta")) ?? LegacyStringToItemParser::getInstance()->parse($this->plugin->getConfig()->getNested("keys.id") . ":" . $this->plugin->getConfig()->getNested("keys.meta"));
        }catch (LegacyStringToItemParserException $e){
            $this->getLogger()->warning($e->getMessage());
        }
        return $item->getTypeId() === $key->getTypeId() && ($keyTypeTag = $item->getNamedTag()->getTag("KeyType")) instanceof StringTag && $keyTypeTag->getValue() === $this->getName();
    }
}
