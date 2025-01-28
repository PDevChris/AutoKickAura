<?php

namespace pdm;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerInteractEntityEvent;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase implements Listener {

    /** @var bool */
    private $autoAuraEnabled;
    private $hitboxEnabled;
    
    /** @var int */
    private $kickThreshold;
    private $banDuration;

    /** @var array */
    private $playerData = [];
    
    /** @var string */
    private $language;

    public function onEnable(): void {
        $this->saveDefaultConfig();
        $this->reloadConfig();

        // Load configuration with defaults
        $this->autoAuraEnabled = $this->getConfig()->get("autoAuraEnabled", true);
        $this->hitboxEnabled = $this->getConfig()->get("hitboxEnabled", true);
        $this->kickThreshold = max(0, $this->getConfig()->get("kick_threshold", 3)); // Ensure non-negative
        $this->banDuration = max(0, $this->getConfig()->get("ban_duration", 6)); // Ensure non-negative
        $this->language = $this->getConfig()->get("language", "en");
        
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        $this->getLogger()->info("PDM Plugin Enabled.");
    }

    public function onDisable(): void {
        $this->getLogger()->info("PDM Plugin Disabled.");
    }

    private function loadLanguage(): void {
        // Placeholder for language loading logic
        $this->getLogger()->info("Loaded language: {$this->language}");
    }

    public function onPlayerMove(PlayerMoveEvent $event): void {
        $player = $event->getPlayer();
        if (!$player instanceof Player) return;

        $this->detectHacks($player);
    }

    public function onPlayerJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        $this->playerData[$player->getName()] = [
            "warnings" => 0,
            "lastCheckTime" => 0
        ];
    }

    public function onPlayerQuit(PlayerQuitEvent $event): void {
        $player = $event->getPlayer();
        unset($this->playerData[$player->getName()]);
    }

    public function onPlayerInteractEntity(PlayerInteractEntityEvent $event): void {
        $player = $event->getPlayer();
        if (!$player instanceof Player) return;

        $this->detectHacks($player);
    }

    private function detectHacks(Player $player): void {
        $currentTime = microtime(true);
        $playerName = $player->getName();

        // Ensure player data exists
        if (!isset($this->playerData[$playerName])) {
            $this->playerData[$playerName] = [
                "warnings" => 0,
                "lastCheckTime" => 0
            ];
        }

        // Prevent excessive checks
        if ($currentTime - $this->playerData[$playerName]["lastCheckTime"] < 1) {
            return;
        }

        $this->playerData[$playerName]["lastCheckTime"] = $currentTime;

        // AutoAura Detection
        if ($this->autoAuraEnabled && $this->detectAutoAura($player)) {
            $this->handleKick($player, "AutoAura detected!");
            return;
        }

        // Hitbox Detection
        if ($this->hitboxEnabled && $this->detectHitbox($player)) {
            $this->handleKick($player, "Hitbox hack detected!");
        }
    }

    private function detectAutoAura(Player $player): bool {
        $radius = 5;
        $nearbyEntities = $player->getWorld()->getNearbyEntities($player->getBoundingBox()->expandedCopy($radius, $radius, $radius));
        foreach ($nearbyEntities as $entity) {
            if ($entity instanceof Player && $entity !== $player) {
                // Perform further checks if needed
                return true; // Detected AutoAura
            }
        }
        return false;
    }

    private function detectHitbox(Player $player): bool {
        $normalHitbox = $player->getBoundingBox();
        $playerHitbox = $player->getBoundingBox();
        $tolerance = 1.1; // Allow a small margin of error
        return $playerHitbox->getVolume() > $normalHitbox->getVolume() * $tolerance;
    }

    private function handleKick(Player $player, string $reason): void {
        $playerName = $player->getName();

        if (!isset($this->playerData[$playerName]["warnings"])) {
            $this->playerData[$playerName]["warnings"] = 0;
        }

        $this->playerData[$playerName]["warnings"]++;

        if ($this->playerData[$playerName]["warnings"] >= $this->kickThreshold) {
            $player->kick($reason);
            $this->getLogger()->info("Kicked {$playerName} for: {$reason}");
        } else {
            $remainingWarnings = $this->kickThreshold - $this->playerData[$playerName]["warnings"];
            $player->sendMessage("Warning! {$reason} ({$remainingWarnings} warnings left before kick)");
        }

        // Debug logging
        if ($this->getConfig()->get("debug", false)) {
            $this->getLogger()->info("Debug: {$playerName} - {$reason}");
        }
    }
}
