<?php

namespace MINSEO\attendancecheck;

use pocketmine\event\EventPriority;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\item\Item;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\TreeRoot;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use MINSEO\attendancecheck\command\AttendanceCommand;

final class AttendanceCheck extends PluginBase {

    public const prefix = '§l§b[출석체크]§r§7 ';

    use SingletonTrait;

    private Config $config;

    protected array $db;

    protected function onEnable(): void {
        $this->config = new Config($this->getDataFolder() . 'config.yml', Config::YAML, [[], []]);
        $this->db = $this->config->getAll();
        $this->getServer()->getCommandMap()->register(strtolower($this->getName()), new AttendanceCommand());
        $this->getServer()->getPluginManager()->registerEvent(PlayerJoinEvent::class, function (PlayerJoinEvent $event): void {
            $id = $event->getPlayer()->getName();
            if (!isset($this->db [0] [$id])) {
                $this->db [0] [$id] = [];
            }
        }, EventPriority::NORMAL, $this);
    }

    protected function onDisable(): void {
        $this->config->setAll($this->db);
        $this->config->save();
    }

    protected function onLoad(): void {
        date_default_timezone_set('Asia/Seoul');
        self::setInstance($this);
    }

    private function nowTimeId(): string {
        return date('Y-m-d');
    }

    public function hasChecked(Player $player): bool {
        return in_array($this->nowTimeId(), $this->db [0] [$player->getName()]);
    }

    public function checkPlayer(Player $player): void {
        $this->db [0] [$player->getName()] [] = $this->nowTimeId();
        foreach ($this->db [1] as $itemData) {
            $player->getInventory()->addItem(self::ItemDataDeserialize($itemData));
        }
    }

    public function addReward(Item $item): void {
        $this->db [1] [] = self::ItemDataSerialize($item);
    }

    public function getRewards(): array {
        return $this->db[1] ?? [];
    }

    public static function ItemDataSerialize(Item $data): string
    {
        return base64_encode((new BigEndianNbtSerializer())->write(new TreeRoot($data->nbtSerialize())));
    }

    public static function ItemDataDeserialize(string $data): Item
    {
        return Item::nbtDeserialize((new BigEndianNbtSerializer())->read(base64_decode($data))->mustGetCompoundTag());
    }
}