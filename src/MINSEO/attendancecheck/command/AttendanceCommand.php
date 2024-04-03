<?php

namespace MINSEO\attendancecheck\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use MINSEO\attendancecheck\AttendanceCheck;

final class AttendanceCommand extends Command
{

    public function __construct()
    {
        parent::__construct('출석체크', '출석체크를 진행합니다.', null, ['attendace']);
        $this->setPermission(DefaultPermissions::ROOT_USER);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): void
    {
        if (!($sender instanceof Player) || !$this->testPermission($sender)) {
            return;
        }

        $owner = AttendanceCheck::getInstance();
        $rewards = $owner->getRewards();

        if ((array_shift($args) ?? '') === '보상추가' && $sender->hasPermission(DefaultPermissions::ROOT_OPERATOR)) {
            $item = $sender->getInventory()->getItemInHand();
            if ($item->isNull()) {
                $sender->sendMessage(AttendanceCheck::prefix . '공기는 보상으로 추가할 수 없습니다.');
                return;
            }
            $owner->addReward($item);
            $sender->sendMessage(AttendanceCheck::prefix . '해당 아이템을 보상으로 추가했습니다.');
            return;
        }

        if (!$owner->hasChecked($sender)) {
            if (empty($rewards)) {
                $sender->sendMessage(AttendanceCheck::prefix . '출석체크 보상이 설정되지 않았습니다.');
                return;
            }

            $owner->checkPlayer($sender);
            $sender->sendMessage(AttendanceCheck::prefix . '오늘의 출석체크를 완료했습니다!');
        } else {
            $sender->sendMessage(AttendanceCheck::prefix . '오늘의 출석체크를 이미 완료했습니다.');
        }
    }
}
