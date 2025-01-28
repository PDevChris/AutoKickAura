<?php

namespace pdm;

use pocketmine\plugin\PluginBase;
use pocketmine\player\Player;

class Main extends PluginBase {

    /** @var bool */
    public $autoAuraEnabled;
    public $hitboxEnabled;

    /** @var int */
    public $kickThreshold;
    public $banDuration;

    /** @var array */
    public $playerData = [];

    /** @var string */
    public $autoAuraKickMessage = "AutoAura detected!";
    public $hitboxKickMessage = "Hitbox hack detected!";

    public function onEnable(): void {
        $this->saveDefaultConfig();
        $this->reloadConfig();

        // Load configuration with defaults
        $this->autoAuraEnabled = $this->getConfig()->get("autoAuraEnabled", true);
        $this->hitboxEnabled = $this->getConfig()->get("hitboxEnabled", true);
        $this->kickThreshold = max(0, $this->getConfig()->get("kick_threshold", 3)); // Ensure non-negative
        $this->banDuration = max(0, $this->getConfig()->get("ban_duration", 6)); // Ensure non-negative

        // Register the EventListener
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        $this->getLogger()->info("PDM Plugin Enabled.");
    }

    public function onDisable(): void {
        $this->getLogger()->info("PDM Plugin Disabled.");
    }

    public function detectAutoAura(Player $player): bool {
        $radius = 5;
        $nearbyEntities = $player->getWorld()->getNearbyEntities($player->getBoundingBox()->expandedCopy($radius, $radius, $radius));
        foreach ($nearbyEntities as $entity) {
            if ($entity instanceof Player && $entity !== $player) {
                return true; // Detected AutoAura
            }
        }
        return false;
    }

    public function detectHitbox(Player $player): bool {
        $normalHitbox = $player->getBoundingBox();
        $playerHitbox = $player->getBoundingBox();
        $tolerance = 1.1; // Allow a small margin of error
        return $playerHitbox->getVolume() > $normalHitbox->getVolume() * $tolerance;
    }

    public function handleKick(Player $player, string $reason): void {
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
    }
}
