<?php

namespace pdm;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\utils\Config;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerJoinEvent;

class Main extends PluginBase implements Listener {

    /** @var Config */
    private $config;

    // Configuration values
    public $autoAuraEnabled;
    public $hitboxEnabled;
    public $kickThreshold;
    public $banDuration;
    public $autoAuraKickMessage;
    public $hitboxKickMessage;
    public $banMessage;
    public $notifyStaffOnDetection;
    public $staffNotifyMessage;
    public $enabled;

    // Initialize the plugin
    public function onEnable(): void {
        $this->saveDefaultConfig();
        $this->config = $this->getConfig();

        // Load configuration values
        $this->autoAuraEnabled = $this->config->get("autoaura_enabled", true);
        $this->hitboxEnabled = $this->config->get("hitbox_enabled", true);
        $this->kickThreshold = $this->config->get("kick_threshold", 3);
        $this->banDuration = $this->config->get("ban_duration", 6);
        $this->autoAuraKickMessage = $this->config->get("autoaura_kick_message", "You have been kicked for using AutoAura!");
        $this->hitboxKickMessage = $this->config->get("hitbox_kick_message", "You have been kicked for using Hitbox!");
        $this->banMessage = $this->config->get("ban_message", "You have been banned for using hacks!");
        $this->notifyStaffOnDetection = $this->config->get("notify_staff_on_detection", true);
        $this->staffNotifyMessage = $this->config->get("staff_notify_message", "Player {player} was detected using hacks and has been kicked/banned.");
        $this->enabled = $this->config->get("enabled", true);

        // Check if the plugin is enabled
        if ($this->enabled) {
            $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
        }
    }

    // Handle player kick actions
    public function handleKick($player, $message): void {
        if ($player instanceof Player) {
            $player->kick($message);
            $this->getLogger()->info("Kicked player " . $player->getName() . " for using hacks.");
            
            // Notify staff if enabled
            if ($this->notifyStaffOnDetection) {
                foreach ($this->getServer()->getOnlinePlayers() as $staffPlayer) {
                    if ($staffPlayer->hasPermission("auradetector.reload")) {
                        $staffPlayer->sendMessage(str_replace("{player}", $player->getName(), $this->staffNotifyMessage));
                    }
                }
            }
        }
    }

    // Placeholder for AutoAura detection logic (needs to be refined)
    public function detectAutoAura($player): bool {
        return rand(0, 10) > 8; // Placeholder random detection logic
    }

    // Placeholder for Hitbox detection logic (needs to be refined)
    public function detectHitbox($player): bool {
        return rand(0, 10) > 8; // Placeholder random detection logic
    }
}
