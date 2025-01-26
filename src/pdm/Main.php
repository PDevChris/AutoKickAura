<?php

namespace AutoKickAura;

use pocketmine\plugin\PluginBase;
use pocketmine\Player;
use pocketmine\utils\Config;

class Main extends PluginBase {

    /** @var array */
    private $kickCounts = [];

    /** @var EventListener */
    private $eventListener;

    public function onEnable() {
        $this->saveDefaultConfig();
        $this->loadKickCount();
        
        // Register the EventListener class
        $this->eventListener = new EventListener($this);
        $this->getServer()->getPluginManager()->registerEvents($this->eventListener, $this);

        $this->getLogger()->info("AutoKickAura Plugin Enabled!");
    }

    public function onDisable() {
        $this->saveKickCount();
        $this->getLogger()->info("AutoKickAura Plugin Disabled!");
    }

    // Reload command to reload the configuration
    public function reloadConfigCommand() {
        $this->reloadConfig();
        $this->loadKickCount();
        $this->getLogger()->info("Plugin configuration reloaded.");
    }

    // Save kick counts to the configuration file
    public function saveKickCount() {
        $this->getConfig()->set('kick_counts', $this->kickCounts);
        $this->saveConfig();
    }

    // Load kick counts from the configuration file
    public function loadKickCount() {
        if ($this->getConfig()->exists('kick_counts')) {
            $this->kickCounts = $this->getConfig()->get('kick_counts');
        }
    }

    // Function to increase kick counts
    public function increaseKickCount(Player $player) {
        $name = $player->getName();
        if (!isset($this->kickCounts[$name])) {
            $this->kickCounts[$name] = 0;
        }
        $this->kickCounts[$name]++;
        $this->saveKickCount();
    }
}
