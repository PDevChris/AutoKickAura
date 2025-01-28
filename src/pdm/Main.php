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
    
        // Load configuration values
        $this->autoAuraEnabled = (bool)$this->getConfig()->get("autoAuraEnabled", true);
        $this->hitboxEnabled = (bool)$this->getConfig()->get("hitboxEnabled", true);
        $this->kickThreshold = max(0, (int)$this->getConfig()->get("kick_threshold", 3));
        $this->banDuration = max(0, (int)$this->getConfig()->get("ban_duration", 6));
    
        // Load messages
        $this->autoAuraKickMessage = $this->getConfig()->getNested("messages.auto_aura_kick", "AutoAura detected!");
        $this->hitboxKickMessage = $this->getConfig()->getNested("messages.hitbox_kick", "Hitbox hack detected!");
    
        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        $this->getLogger()->info("PDM Plugin Enabled.");
    }

    public function onDisable(): void {
        $this->getLogger()->info("PDM Plugin Disabled.");
    }

    public function handleKick(Player $player, string $reason): void {
        $playerName = $player->getName();

        if ($player->hasPermission("pdm.bypass")) {
            $this->getLogger()->info("Skipped punishment for {$player->getName()} (bypass permission).");
            return;
        }

        // Ensure warnings are tracked properly
        if (!isset($this->playerData[$playerName])) {
            $this->playerData[$playerName] = ["warnings" => 0];
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

    public function detectAutoAura(Player $player): bool {
        $radius = 5;
        $nearbyEntities = $player->getWorld()->getNearbyEntities($player->getBoundingBox()->expandedCopy($radius, $radius, $radius));

        foreach ($nearbyEntities as $entity) {
            if ($entity instanceof Player && $entity !== $player) {
                $this->getLogger()->debug("AutoAura detected for player: {$player->getName()}");
                return true;
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
}
