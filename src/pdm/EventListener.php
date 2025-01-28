<?php

namespace pdm;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerInteractEntityEvent;
use pocketmine\player\Player;
use pocketmine\math\Vector3;
use pocketmine\utils\Config;

class EventListener implements Listener {

    /** @var Main */
    private $plugin;

    /** @var array */
    private $playerData = [];

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    // Handle player move event
    public function onPlayerMove(PlayerMoveEvent $event): void {
        $player = $event->getPlayer();

        if (!$player instanceof Player) return;

        $this->detectHacks($player);
    }

    // Handle player join event
    public function onPlayerJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();

        // Initialize player data
        $this->playerData[$player->getName()] = [
            "lastCheckTime" => microtime(true),
            "warnings" => 0
        ];

        if ($this->plugin->debug) {
            $this->plugin->getLogger()->info("Player " . $player->getName() . " joined and data initialized.");
        }
    }

    // Handle player quit event
    public function onPlayerQuit(PlayerQuitEvent $event): void {
        $player = $event->getPlayer();

        // Clean up player data
        unset($this->playerData[$player->getName()]);

        if ($this->plugin->debug) {
            $this->plugin->getLogger()->info("Player " . $player->getName() . " quit and data cleared.");
        }
    }

    // Handle player interacting with an entity
    public function onPlayerInteractEntity(PlayerInteractEntityEvent $event): void {
        $player = $event->getPlayer();

        if (!$player instanceof Player) return;

        $this->detectHacks($player);
    }

    // Centralized detection for hacks
    public function detectHacks(Player $player): void {
        $playerName = $player->getName();
        $currentTime = microtime(true);

        // Ensure player data is tracked
        if (!isset($this->playerData[$playerName])) {
            $this->playerData[$playerName] = ["lastCheckTime" => $currentTime, "warnings" => 0];
        }

        $lastCheckTime = $this->playerData[$playerName]["lastCheckTime"];

        // Cooldown check (to reduce processing load)
        if ($currentTime - $lastCheckTime < 1) return;

        // Update last check time
        $this->playerData[$playerName]["lastCheckTime"] = $currentTime;

        // AutoAura Detection
        if ($this->plugin->autoAuraEnabled && $this->plugin->detectAutoAura($player)) {
            $this->handleWarning($player, "AutoAura");
        }

        // Hitbox Detection
        if ($this->plugin->hitboxEnabled && $this->plugin->detectHitbox($player)) {
            $this->handleWarning($player, "Hitbox");
        }
    }

    // Handle warnings and kick logic
    public function handleWarning(Player $player, string $type): void {
        $playerName = $player->getName();
        $this->playerData[$playerName]["warnings"]++;

        $warnings = $this->playerData[$playerName]["warnings"];
        $kickThreshold = $this->plugin->kickThreshold;

        if ($this->plugin->debug) {
            $this->plugin->getLogger()->info("Player $playerName triggered $type detection. Warnings: $warnings");
        }

        if ($warnings >= $kickThreshold) {
            $message = $type === "AutoAura" ? $this->plugin->autoAuraKickMessage : $this->plugin->hitboxKickMessage;
            $this->plugin->handleKick($player, $message);
        }
    }
}
