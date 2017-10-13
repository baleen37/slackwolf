<?php namespace Slackwolf\Game\Roles;

use Slackwolf\Game\Role;

/**
 * Defines the Beholder class.
 *
 * @package Slackwolf\Game\Roles
 */
class Beholder extends Role
{

    /**
     * {@inheritdoc}
     */
	public function getName() {
		return Role::BEHOLDER;
	}

    /**
     * {@inheritdoc}
     */
	public function getDescription() {
		return "[시민] 첫날밤 Seer가 누군지 알수있습니다.";
	}
}
