<?php namespace Slackwolf\Game\Roles;

use Slackwolf\Game\Role;

/**
 * Defines the Seer class.
 *
 * @package Slackwolf\Game\Roles
 */
class Seer extends Role
{

    /**
     * {@inheritdoc}
     */
	public function getName() {
		return Role::SEER;
	}

    /**
     * {@inheritdoc}
     */
	public function getDescription() {
		return "[시민] 밤마다 플레이어의 직업을 알 수 있습니다.(시민, 울프인지만 표시)";
	}
}
