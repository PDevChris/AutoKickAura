<?php

namespace pdm;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\player\Player;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\utils\TextFormat;
use pocketmine\math\Vector3;

class Main extends PluginBase implements Listener {

    /** @var Config */
    private $config;

    /** @var bool */
    public $autoAuraEnabled = false;

    /** @var bool */
    public $hitboxEnabled = false;

    /** @var string */
    public $autoAuraKickMessage;

    /** @var string */
    public $hitboxKickMessage;

    /** @var string */
    public $staffNotifyMessage;

    /** @var Config */
    private $languageConfig;

    /** @var string */
    private $language = 'en'; // Default language is English

    /** @var int */
    public $kickThreshold = 3;

    /** @var int */
    public $banDuration = 6;

    /** @var bool */
    public $enabled = true;

    public function onEnable(): void {
        $this->saveDefaultConfig();
        $this->config = $this->getConfig();

        // Load the language file
        $this->loadLanguage();

        // Read config settings
        $this->autoAuraEnabled = $this->config->get("autoAuraEnabled", true);
        $this->hitboxEnabled = $this->config->get("hitboxEnabled", true);
        $this->autoAuraKickMessage = $this->config->get("autoAuraKickMessage", "You have been kicked for using AutoAura!");
        $this->hitboxKickMessage = $this->config->get("hitboxKickMessage", "You have been kicked for using Hitbox hack!");
        $this->staffNotifyMessage = $this->config->get("staffNotifyMessage", "Player {player} was detected using hacks.");
        $this->kickThreshold = $this->config->get("kick_threshold", 3);
        $this->banDuration = $this->config->get("ban_duration", 6);

        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);
    }

    public function loadLanguage(): void {
        $languagePath = $this->getDataFolder() . "languages/" . $this->language . ".yml";

        if (!file_exists($languagePath)) {
            $this->getLogger()->warning("Language file for {$this->language} not found. Using default.");
            $languagePath = $this->getDataFolder() . "languages/en.yml"; // Default to English
        }

        $this->languageConfig = new Config($languagePath, Config::YAML);
    }

    public function getLanguageMessage(string $key): string {
        return $this->languageConfig->getNested("messages." . $key, "Message not found");
    }

    // Detect AutoAura hack (basic example, needs refinement)
    public function detectAutoAura(Player $player): bool {
        // Example: Player too close to others
        $playerPos = $player->getPosition();
        $players = $this->getServer()->getOnlinePlayers();

        foreach ($players as $target) {
            if ($target->getPosition()->distance($playerPos) < 5) {
                return true; // Detected AutoAura
            }
        }

        return false;
    }

    // Detect Hitbox hack (basic example, needs refinement)
    public function detectHitbox(Player $player): bool {
        // Example: Checking hitbox size
        $playerHitbox = $player->getBoundingBox(); 
        $normalHitbox = new Vector3(0.6, 1.8, 0.6); 

        if ($playerHitbox->getVolume() > $normalHitbox->getVolume() * 1.5) {
            return true; // Detected Hitbox hack
        }

        return false;
    }

    // Handle kick logic after detecting a hack
    public function handleKick(Player $player, string $message): void {
        // Fetch message from language
        $kickMessage = $this->getLanguageMessage($message);
        $player->kick($kickMessage, false);

        // Notify staff if enabled
        if ($this->config->get("notify_staff_on_detection", true)) {
            $staffMessage = str_replace("{player}", $player->getName(), $this->staffNotifyMessage);
            foreach ($this->getServer()->getOnlinePlayers() as $staff) {
                if ($staff->hasPermission("auradetector.reload")) {
                    $staff->sendMessage($staffMessage);
                }
            }
        }
    }

    // Player move detection for AutoAura and Hitbox
    public function onPlayerMove(PlayerMoveEvent $event): void {
        $player = $event->getPlayer();

        if (!$player instanceof Player) return;

        if ($this->autoAuraEnabled && $this->detectAutoAura($player)) {
            $this->handleKick($player, "auto_aura_kick");
        }

        if ($this->hitboxEnabled && $this->detectHitbox($player)) {
            $this->handleKick($player, "hitbox_kick");
        }
    }
}
