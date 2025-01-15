<?php

namespace idk\rollback;

use idk\rollback\cmd\RbCommand;
use idk\rollback\serialize\Serialize;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;

class Loader extends PluginBase implements Listener
{

    use SingletonTrait;

    public function onLoad(): void{self::setInstance($this);}
    public function onEnable():void
    {
        if (!InvMenuHandler::isRegistered())InvMenuHandler::register($this);

        if (!is_dir($this->getDataFolder() . "players"))
            @mkdir($this->getDataFolder() . "players");
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getServer()->getCommandMap()->register("rb", new RbCommand);
    }

    public function onDeath(PlayerDeathEvent $event): void
    {
        $player = $event->getPlayer();
        $cause = $player->getLastDamageCause();
        $killer = "Unknown";
        $reason = "Unknown";

        if ($cause instanceof EntityDamageByEntityEvent) {
            $damager = $cause->getDamager();
            if ($damager instanceof Player) {
                $killer = $damager->getName();
            }
            $reason = "Killed by player";
        } elseif ($cause instanceof EntityDamageEvent) {
            $reason = match ($cause->getCause()) {
                EntityDamageEvent::CAUSE_FALL => "Fell from a height",
                EntityDamageEvent::CAUSE_DROWNING => "Drowned",
                EntityDamageEvent::CAUSE_FIRE => "Burned",
                EntityDamageEvent::CAUSE_SUFFOCATION => "Suffocated",
                default => "Unknown cause"
            };
        }

        $items = array_map(fn($item) => Serialize::serialize($item), $player->getInventory()->getContents());
        $armor = array_map(fn($item) => Serialize::serialize($item), $player->getArmorInventory()->getContents());

        $playerDir = $this->getDataFolder() . "players/";
        if (!is_dir($playerDir)) {
            @mkdir($playerDir);
        }

        $configPath = $playerDir . $player->getName() . ".json";
        $config = new Config($configPath, Config::JSON);

        $deathId = count($config->getAll()) + 1;
        $config->set($deathId, [
            "date" => date("d/m/Y g:ia"),
            "killer" => $killer,
            "reason" => $reason,
            "items" => $items ?: [],
            "armor" => $armor ?: []
        ]);
        $config->save();
    }
}